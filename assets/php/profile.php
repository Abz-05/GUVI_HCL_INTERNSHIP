<?php
// assets/php/profile.php
// Profile management endpoint (get, update, logout)

require_once __DIR__ . '/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondWithError("Method not allowed", 405);
}

// Get request body
$data = getRequestBody();

// Extract action and token
$action = $data['action'] ?? '';
$token = $data['token'] ?? '';

// Validate action
if (empty($action)) {
    respondWithError("Action is required", 400);
}

// Validate and get session
$session = validateSession($token);
$userId = $session['user_id'];

// ============================================
// Handle GET action - Fetch profile data
// ============================================
if ($action === 'get') {
    // Fetch user data from MySQL
    $stmt = $mysqli->prepare(
        "SELECT id, username, email, created_at FROM users WHERE id = ?"
    );
    
    if (!$stmt) {
        respondWithError("Database error: " . $mysqli->error, 500);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        respondWithError("User not found", 404);
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Fetch profile data from MongoDB
    $profileData = [
        'age' => null,
        'dob' => null,
        'contact' => null,
        'address' => null,
        'bio' => null
    ];
    
    try {
        if ($profilesCollection) {
            $profile = $profilesCollection->findOne(['user_id' => $userId]);
            
            if ($profile) {
                $profileData = [
                    'age' => $profile['age'] ?? null,
                    'dob' => $profile['dob'] ?? null,
                    'contact' => $profile['contact'] ?? null,
                    'address' => $profile['address'] ?? null,
                    'bio' => $profile['bio'] ?? null
                ];
            }
        }
    } catch (Exception $e) {
        error_log("MongoDB fetch failed: " . $e->getMessage());
    }
    
    // Log profile access
    logToMongo($userId, 'PROFILE_VIEW', []);
    
    // Merge data
    $userData = array_merge($user, $profileData);
    
    respondWithSuccess("Profile data retrieved", [
        'user' => $userData
    ]);
}

// ============================================
// Handle UPDATE action - Update profile data
// ============================================
elseif ($action === 'update') {
    // Extract profile fields
    $age = isset($data['age']) && $data['age'] !== '' ? (int)$data['age'] : null;
    $dob = !empty($data['dob']) ? sanitize($data['dob']) : null;
    $contact = !empty($data['contact']) ? sanitize($data['contact']) : null;
    $address = !empty($data['address']) ? sanitize($data['address']) : null;
    $bio = !empty($data['bio']) ? sanitize($data['bio']) : null;
    
    // Validate age
    if ($age !== null && ($age < 1 || $age > 120)) {
        respondWithError("Age must be between 1 and 120", 400);
    }
    
    // Validate date of birth
    if ($dob !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        respondWithError("Invalid date format for date of birth", 400);
    }
    
    // Update profile in MongoDB
    try {
        if ($profilesCollection) {
            $updateData = [
                'age' => $age,
                'dob' => $dob,
                'contact' => $contact,
                'address' => $address,
                'bio' => $bio,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $result = $profilesCollection->updateOne(
                ['user_id' => $userId],
                ['$set' => $updateData],
                ['upsert' => true]
            );
            
            if ($result->getModifiedCount() === 0 && $result->getUpsertedCount() === 0 && $result->getMatchedCount() === 0) {
                throw new Exception("Update failed");
            }
        } else {
            respondWithError("MongoDB not available", 500);
        }
    } catch (Exception $e) {
        error_log("MongoDB update failed: " . $e->getMessage());
        respondWithError("Failed to update profile: " . $e->getMessage(), 500);
    }
    
    // Log profile update
    logToMongo($userId, 'PROFILE_UPDATE', $updateData);
    
    respondWithSuccess("Profile updated successfully");
}

// ============================================
// Handle LOGOUT action - Delete session
// ============================================
elseif ($action === 'logout') {
    // Delete session from file storage
    deleteSession($token);
    
    // Log logout event
    logToMongo($userId, 'LOGOUT', []);
    
    respondWithSuccess("Logged out successfully");
}

// ============================================
// Handle unknown action
// ============================================
else {
    respondWithError("Invalid action: " . $action, 400);
}
?>