<?php
// assets/php/register.php
// User registration endpoint

require_once __DIR__ . '/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondWithError("Method not allowed", 405);
}

// Get request body
$data = getRequestBody();

// Extract and sanitize input
$email = sanitize($data['email'] ?? '');
$username = sanitize($data['username'] ?? '');
$password = $data['password'] ?? '';

// Validation
$errors = [];

// Validate email
if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!isValidEmail($email)) {
    $errors[] = "Invalid email format";
}

// Validate username
if (empty($username)) {
    $errors[] = "Username is required";
} elseif (strlen($username) < 3) {
    $errors[] = "Username must be at least 3 characters";
} elseif (strlen($username) > 50) {
    $errors[] = "Username must not exceed 50 characters";
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = "Username can only contain letters, numbers, and underscores";
}

// Validate password
if (empty($password)) {
    $errors[] = "Password is required";
} elseif (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters";
} elseif (strlen($password) > 255) {
    $errors[] = "Password is too long";
}

// Return validation errors
if (!empty($errors)) {
    respondWithError(implode(", ", $errors), 400);
}

// Check if email already exists
$stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    respondWithError("Database error: " . $mysqli->error, 500);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    respondWithError("Email is already registered", 409);
}
$stmt->close();

// Check if username already exists
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
if (!$stmt) {
    respondWithError("Database error: " . $mysqli->error, 500);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    respondWithError("Username is already taken", 409);
}
$stmt->close();

// Hash password
$passwordHash = hashPassword($password);

// Insert user into MySQL
$stmt = $mysqli->prepare(
    "INSERT INTO users (email, username, password_hash, created_at) VALUES (?, ?, ?, NOW())"
);

if (!$stmt) {
    respondWithError("Database error: " . $mysqli->error, 500);
}

$stmt->bind_param("sss", $email, $username, $passwordHash);

if (!$stmt->execute()) {
    $stmt->close();
    respondWithError("Registration failed: " . $stmt->error, 500);
}

$userId = $stmt->insert_id;
$stmt->close();

// Create profile document in MongoDB
try {
    if ($profilesCollection) {
        $profilesCollection->insertOne([
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'age' => null,
            'dob' => null,
            'contact' => null,
            'address' => null,
            'bio' => null,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ]);
    }
} catch (Exception $e) {
    error_log("MongoDB profile insert failed: " . $e->getMessage());
}

// Log registration event
logToMongo($userId, 'REGISTER', ['email' => $email, 'username' => $username]);

// Send success response
respondWithSuccess("Registration successful! You can now login.", [
    'user_id' => $userId
]);
?>