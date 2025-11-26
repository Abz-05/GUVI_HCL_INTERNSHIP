<?php
// Minimal register.php for testing
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON: " . json_last_error_msg());
    }
    
    // Extract data
    $email = trim($data['email'] ?? '');
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($username) || empty($password)) {
        throw new Exception("All fields are required");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    
    if (strlen($username) < 3) {
        throw new Exception("Username must be at least 3 characters");
    }
    
    if (strlen($password) < 6) {
        throw new Exception("Password must be at least 6 characters");
    }
    
    // Connect to MySQL
    $mysqli = new mysqli('localhost', 'root', '', 'internship_app');
    
    if ($mysqli->connect_errno) {
        throw new Exception("Database connection failed: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    // Check if email exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $mysqli->close();
        throw new Exception("Email is already registered");
    }
    $stmt->close();
    
    // Check if username exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $mysqli->close();
        throw new Exception("Username is already taken");
    }
    $stmt->close();
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insert user
    $stmt = $mysqli->prepare("INSERT INTO users (email, username, password_hash, created_at) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("sss", $email, $username, $passwordHash);
    
    if (!$stmt->execute()) {
        throw new Exception("Insert failed: " . $stmt->error);
    }
    
    $userId = $stmt->insert_id;
    $stmt->close();
    $mysqli->close();
    
    // Success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! You can now login.',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>