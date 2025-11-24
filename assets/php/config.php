<?php
// assets/php/config.php
// Database configuration and connections

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ============================================
// MySQL Configuration
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'internship_auth');

// MySQL Connection
$mysqli = null;
try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_errno) {
        throw new Exception("MySQL connection failed: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
} catch (Exception $e) {
    respondWithError("Database connection failed", 500);
}

// ============================================
// Redis Configuration
// ============================================
$redis = null;
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    
    // Test connection
    $redis->ping();
} catch (Exception $e) {
    // Redis not available - log error but continue
    error_log("Redis connection failed: " . $e->getMessage());
}

// ============================================
// MongoDB Configuration
// ============================================
$mongoClient = null;
$mongoDb = null;
$profilesCollection = null;

try {
    // Check if MongoDB library is available
    if (!class_exists('MongoDB\Client')) {
        throw new Exception("MongoDB library not installed");
    }
    
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    $mongoClient = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $mongoDb = $mongoClient->internship_auth;
    $profilesCollection = $mongoDb->profiles;
} catch (Exception $e) {
    // MongoDB not available - log error but continue
    error_log("MongoDB connection failed: " . $e->getMessage());
}

// ============================================
// Helper Functions
// ============================================

/**
 * Send JSON response and exit
 */
function respond($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Send error response and exit
 */
function respondWithError($message, $statusCode = 400) {
    respond([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

/**
 * Send success response and exit
 */
function respondWithSuccess($message, $data = []) {
    respond(array_merge([
        'success' => true,
        'message' => $message
    ], $data));
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input string
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate secure random token
 */
function generateToken($length = 64) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Store session in Redis
 */
function storeSession($token, $userData, $expiry = 3600) {
    global $redis;
    
    if (!$redis) {
        return false;
    }
    
    try {
        $sessionKey = "session:" . $token;
        $sessionData = json_encode($userData);
        return $redis->setex($sessionKey, $expiry, $sessionData);
    } catch (Exception $e) {
        error_log("Redis store failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get session from Redis
 */
function getSession($token) {
    global $redis;
    
    if (!$redis) {
        return null;
    }
    
    try {
        $sessionKey = "session:" . $token;
        $sessionData = $redis->get($sessionKey);
        
        if ($sessionData) {
            // Refresh expiry
            $redis->expire($sessionKey, 3600);
            return json_decode($sessionData, true);
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Redis get failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Delete session from Redis
 */
function deleteSession($token) {
    global $redis;
    
    if (!$redis) {
        return false;
    }
    
    try {
        $sessionKey = "session:" . $token;
        return $redis->del($sessionKey) > 0;
    } catch (Exception $e) {
        error_log("Redis delete failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate session token
 */
function validateSession($token) {
    if (empty($token)) {
        respondWithError("Session token is required", 401);
    }
    
    $session = getSession($token);
    
    if (!$session) {
        respondWithError("Invalid or expired session", 401);
    }
    
    return $session;
}

/**
 * Get request body as JSON
 */
function getRequestBody() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        respondWithError("Invalid JSON in request body", 400);
    }
    
    return $data;
}

// ============================================
// Handle preflight requests
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>