<?php
// assets/php/profile.php
// Profile management endpoint (Standalone version)

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
    
    // Extract action and token
    $action = $data['action'] ?? '';
    $token = $data['token'] ?? '';
    
    // Validate action
    if (empty($action)) {
        throw new Exception("Action is required");
    }
    
    // Validate token
    if (empty($token)) {
        http_response_code(401);
        throw new Exception("Session token is required");
    }
    
    // Validate session
    $sessionDir = __DIR__ . '/../../sessions';
    $sessionFile = $sessionDir . '/session_' . $token . '.json';
    
    if (!file_exists($sessionFile)) {
        http_response_code(401);
        throw new Exception("Invalid or expired session");
    }
    
    $content = file_get_contents($sessionFile);
    $sessionFileData = json_decode($content, true);
    
    if (!$sessionFileData) {
        http_response_code(401);
        throw new Exception("Invalid session data");
    }
    
    // Check if session has expired
    if (time() > $sessionFileData['expiry']) {
        @unlink($sessionFile);
        http_response_code(401);
        throw new Exception("Session has expired");
    }
    
    $session = $sessionFileData['data'];
    $userId = $session['user_id'];
    
    // Connect to MySQL
    $mysqli = new mysqli('localhost', 'root', '', 'internship_app');
    
    if ($mysqli->connect_errno) {
        throw new Exception("Database connection failed: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    // ============================================
    // Handle GET action - Fetch profile data
    // ============================================
    if ($action === 'get') {
        // Fetch user data from MySQL
        $stmt = $mysqli->prepare(
            "SELECT id, username, email, created_at FROM users WHERE id = ?"
        );
        
        if (!$stmt) {
            throw new Exception("Database error: " . $mysqli->error);
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $mysqli->close();
            throw new Exception("User not found");
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        $mysqli->close();
        
        // For now, profile data (age, dob, etc.) will be null
        // since we don't have MongoDB
        $profileData = [
            'age' => null,
            'dob' => null,
            'contact' => null,
            'address' => null,
            'bio' => null
        ];
        
        // Merge data
        $userData = array_merge($user, $profileData);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Profile data retrieved',
            'user' => $userData
        ]);
    }
    
    // ============================================
    // Handle UPDATE action - Update profile data
    // ============================================
    elseif ($action === 'update') {
        // Extract profile fields
        $age = isset($data['age']) && $data['age'] !== '' ? (int)$data['age'] : null;
        $dob = !empty($data['dob']) ? trim($data['dob']) : null;
        $contact = !empty($data['contact']) ? trim($data['contact']) : null;
        $address = !empty($data['address']) ? trim($data['address']) : null;
        $bio = !empty($data['bio']) ? trim($data['bio']) : null;
        
        // Validate age
        if ($age !== null && ($age < 1 || $age > 120)) {
            throw new Exception("Age must be between 1 and 120");
        }
        
        // Validate date of birth
        if ($dob !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            throw new Exception("Invalid date format for date of birth");
        }
        
        // Since we don't have MongoDB, we'll just return success
        // In a full implementation, you'd store this in MongoDB
        
        $mysqli->close();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully (Note: Extended profile data not stored without MongoDB)'
        ]);
    }
    
    // ============================================
    // Handle LOGOUT action - Delete session
    // ============================================
    elseif ($action === 'logout') {
        // Delete session file
        @unlink($sessionFile);
        
        $mysqli->close();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
    
    // ============================================
    // Handle unknown action
    // ============================================
    else {
        $mysqli->close();
        throw new Exception("Invalid action: " . $action);
    }
    
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    $statusCode = http_response_code();
    if ($statusCode === 200) {
        $statusCode = 400;
    }
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>