<?php
// assets/php/login.php
// User login endpoint (Standalone version)

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
    
    // Extract and sanitize input
    $emailOrUsername = trim($data['emailOrUsername'] ?? '');
    $password = $data['password'] ?? '';
    
    // Validation
    if (empty($emailOrUsername)) {
        throw new Exception("Email or username is required");
    }
    
    if (empty($password)) {
        throw new Exception("Password is required");
    }
    
    // Connect to MySQL
    $mysqli = new mysqli('localhost', 'root', '', 'internship_app');
    
    if ($mysqli->connect_errno) {
        throw new Exception("Database connection failed: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    // Determine if input is email or username
    $isEmail = filter_var($emailOrUsername, FILTER_VALIDATE_EMAIL);
    
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
        throw new Exception("Database error: " . $mysqli->error);
    }
    
    $stmt->bind_param("s", $emailOrUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if user exists
    if ($result->num_rows === 0) {
        $stmt->close();
        $mysqli->close();
        throw new Exception("Invalid email/username or password");
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    $mysqli->close();
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception("Invalid email/username or password");
    }
    
    // Generate session token
    $token = bin2hex(random_bytes(32));
    
    // Prepare session data
    $sessionData = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'logged_in_at' => date('Y-m-d H:i:s')
    ];
    
    // Store session in file
    $sessionDir = __DIR__ . '/../../sessions';
    
    // Create sessions directory if it doesn't exist
    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0755, true);
    }
    
    $sessionFile = $sessionDir . '/session_' . $token . '.json';
    $sessionFileData = [
        'data' => $sessionData,
        'expiry' => time() + 3600 // 1 hour
    ];
    
    $result = file_put_contents($sessionFile, json_encode($sessionFileData));
    
    if ($result === false) {
        error_log("Warning: Failed to write session file");
    }
    
    // Send success response with token
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>