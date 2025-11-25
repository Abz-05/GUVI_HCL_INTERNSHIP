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
define('DB_NAME', 'internship_app');

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
// Session Storage Configuration (File-Based)
// ============================================
define('SESSION_DIR', __DIR__ . '/../../sessions');

// Create sessions directory if it doesn't exist
if (!is_dir(SESSION_DIR)) {
    mkdir(SESSION_DIR, 0755, true);
}

// ============================================
// MongoDB Configuration (Optional)
// ============================================
$mongoClient = null;
$mongoDb = null;
$profilesCollection = null;

// Check if MongoDB extension is loaded
if (extension_loaded('mongodb')) {
    try {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $mongoClient = new MongoDB\Client(
            "mongodb+srv://abzanavarhath_db_user:Abzu%232005@abzanacluster21.veewqjw.mongodb.net/?retryWrites=true&w=majority&appName=AbzanaCluster21"
        );
        
        $mongoDb = $mongoClient->selectDatabase("internship_app");
        $profilesCollection = $mongoDb->profiles;
        
    } catch (Exception $e) {
        error_log("MongoDB connection failed: " . $e->getMessage());
        $profilesCollection = null;
    }
} else {
    // MongoDB extension not loaded - continue without it
    error_log("MongoDB extension not loaded - logging will be disabled");
}

/**
 * Log to MongoDB (safe wrapper)
 */
function logToMongo($userId, $action, $details = []) {
    global $profilesCollection;
    
    if ($profilesCollection === null) {
        // MongoDB not available - skip logging
        return false;
    }
    
    try {
        $profilesCollection->insertOne([
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        return true;
    } catch (Exception $e) {
        error_log("MongoDB logging failed: " . $e->getMessage());
        return false;
    }
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
 * Store session in file
 */
function storeSession($token, $userData, $expiry = 3600) {
    try {
        $sessionFile = SESSION_DIR . '/session_' . $token . '.json';
        $sessionData = [
            'data' => $userData,
            'expiry' => time() + $expiry
        ];
        
        $result = file_put_contents($sessionFile, json_encode($sessionData));
        
        if ($result === false) {
            error_log("Failed to write session file: " . $sessionFile);
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Session store failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get session from file
 */
function getSession($token) {
    try {
        $sessionFile = SESSION_DIR . '/session_' . $token . '.json';
        
        if (!file_exists($sessionFile)) {
            return null;
        }
        
        $content = file_get_contents($sessionFile);
        
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        
        if (!$data) {
            return null;
        }
        
        // Check if session has expired
        if (time() > $data['expiry']) {
            @unlink($sessionFile);
            return null;
        }
        
        return $data['data'];
    } catch (Exception $e) {
        error_log("Session get failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Delete session from file
 */
function deleteSession($token) {
    try {
        $sessionFile = SESSION_DIR . '/session_' . $token . '.json';
        
        if (file_exists($sessionFile)) {
            return @unlink($sessionFile);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Session delete failed: " . $e->getMessage());
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