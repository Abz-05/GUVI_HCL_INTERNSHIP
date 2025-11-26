<?php
header('Content-Type: application/json');

$tests = [];

// Test 1: MySQL Connection
try {
    $mysqli = new mysqli('localhost', 'root', '', 'internship_app');
    if ($mysqli->connect_errno) {
        $tests['mysql'] = ['status' => 'FAILED', 'error' => $mysqli->connect_error];
    } else {
        $tests['mysql'] = ['status' => 'SUCCESS', 'message' => 'Connected to MySQL'];
        $mysqli->close();
    }
} catch (Exception $e) {
    $tests['mysql'] = ['status' => 'ERROR', 'error' => $e->getMessage()];
}

// Test 2: Check if MongoDB extension is loaded
if (extension_loaded('mongodb')) {
    $tests['mongodb_extension'] = ['status' => 'SUCCESS', 'message' => 'MongoDB extension loaded'];
    
    // Test 3: MongoDB Library
    if (file_exists(__DIR__ . '/assets/php/vendor/autoload.php')) {
        require_once __DIR__ . '/assets/php/vendor/autoload.php';
        $tests['mongodb_library'] = ['status' => 'SUCCESS', 'message' => 'MongoDB library installed'];
        
        // Test 4: MongoDB Connection
        try {
            $client = new MongoDB\Client(
                "mongodb+srv://abzanavarhath_db_user:Abzu%232005@abzanacluster21.veewqjw.mongodb.net/?retryWrites=true&w=majority&appName=AbzanaCluster21"
            );
            
            $client->selectDatabase('admin')->command(['ping' => 1]);
            $tests['mongodb_connection'] = ['status' => 'SUCCESS', 'message' => 'Connected to MongoDB Atlas'];
        } catch (Exception $e) {
            $tests['mongodb_connection'] = ['status' => 'FAILED', 'error' => $e->getMessage()];
        }
    } else {
        $tests['mongodb_library'] = ['status' => 'FAILED', 'error' => 'Vendor folder not found - run composer install'];
    }
} else {
    $tests['mongodb_extension'] = ['status' => 'DISABLED', 'message' => 'MongoDB extension not loaded (app will work without it)'];
    $tests['mongodb_library'] = ['status' => 'SKIPPED', 'message' => 'Skipped - extension not loaded'];
    $tests['mongodb_connection'] = ['status' => 'SKIPPED', 'message' => 'Skipped - extension not loaded'];
}

// Test 5: Sessions Directory
$sessionDir = __DIR__ . '/sessions';
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    $tests['sessions'] = ['status' => 'SUCCESS', 'message' => 'Sessions directory exists and is writable'];
} else {
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0755, true);
        if (is_dir($sessionDir) && is_writable($sessionDir)) {
            $tests['sessions'] = ['status' => 'SUCCESS', 'message' => 'Sessions directory created'];
        } else {
            $tests['sessions'] = ['status' => 'FAILED', 'error' => 'Could not create sessions directory'];
        }
    } else {
        @chmod($sessionDir, 0755);
        $tests['sessions'] = ['status' => 'WARNING', 'message' => 'Sessions directory exists but permissions may be incorrect'];
    }
}

// Test 6: PHP Version
$tests['php_version'] = [
    'status' => 'INFO',
    'message' => 'PHP ' . phpversion()
];

// Test 7: Required PHP Extensions
$required_extensions = ['mysqli', 'json', 'mbstring'];
$missing = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}

if (empty($missing)) {
    $tests['php_extensions'] = ['status' => 'SUCCESS', 'message' => 'All required extensions loaded'];
} else {
    $tests['php_extensions'] = ['status' => 'FAILED', 'error' => 'Missing extensions: ' . implode(', ', $missing)];
}

echo json_encode($tests, JSON_PRETTY_PRINT);
?>