<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Testing register.php</h2>";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = []; // Empty POST to trigger the script

// Capture the raw input
$testData = [
    'email' => 'test@example.com',
    'username' => 'testuser',
    'password' => 'test123'
];

// Simulate the request body
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($testData);

echo "<h3>Attempting to include register.php...</h3>";

try {
    // Include the register script
    include 'assets/php/register.php';
} catch (Exception $e) {
    echo "<div style='color:red; padding:20px; background:#ffe0e0; border:2px solid red;'>";
    echo "<h3>ERROR:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>