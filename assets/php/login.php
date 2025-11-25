<?php
// assets/php/login.php
// User login endpoint

require_once __DIR__ . '/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondWithError("Method not allowed", 405);
}
// Log successful login (if MongoDB available)
if (function_exists('logToMongo')) {
    logToMongo($user['id'], 'LOGIN', ['email' => $email]);
}
// Get request body
$data = getRequestBody();

// Extract and sanitize input
$emailOrUsername = sanitize($data['emailOrUsername'] ?? '');
$password = $data['password'] ?? '';

// Validation
if (empty($emailOrUsername)) {
    respondWithError("Email or username is required", 400);
}

if (empty($password)) {
    respondWithError("Password is required", 400);
}

// Determine if input is email or username
$isEmail = isValidEmail($emailOrUsername);

// Prepare SQL query based on input type
if ($isEmail) {
    $stmt = $mysqli->prepare(
        "SELECT id, username, email, password_hash, created_at FROM users WHERE email = ?"
    );
} else {
    $stmt = $mysqli->prepare(
        "SELECT id, username, email, password_hash, created_at FROM users WHERE username = ?"
    );
}

if (!$stmt) {
    respondWithError("Database error: " . $mysqli->error, 500);
}

$stmt->bind_param("s", $emailOrUsername);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows === 0) {
    $stmt->close();
    respondWithError("Invalid email/username or password", 401);
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify password
if (!verifyPassword($password, $user['password_hash'])) {
    respondWithError("Invalid email/username or password", 401);
}

// Generate session token
$token = generateToken(32);

// Prepare session data
$sessionData = [
    'user_id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'logged_in_at' => date('Y-m-d H:i:s')
];

// Store session in Redis (1 hour expiry)
$sessionStored = storeSession($token, $sessionData, 3600);

if (!$sessionStored) {
    // If Redis fails, still allow login but log the error
    error_log("Warning: Session not stored in Redis for user " . $user['id']);
}

// Send success response with token
respondWithSuccess("Login successful", [
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ]
]);
?>
