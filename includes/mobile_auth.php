<?php
// Mobile authentication helper functions

function authenticate_mobile_user() {
    $headers = getallheaders();
    $token = null;
    
    // Check Authorization header
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (strpos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
        }
    }
    
    // Check token in request body
    if (!$token) {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? null;
    }
    
    // Check token in GET parameters
    if (!$token) {
        $token = $_GET['token'] ?? null;
    }
    
    if (!$token) {
        return false;
    }
    
    try {
        $token_data = json_decode(base64_decode($token), true);
        
        if (!$token_data || !isset($token_data['user_id']) || !isset($token_data['exp'])) {
            return false;
        }
        
        // Check if token is expired
        if ($token_data['exp'] < time()) {
            return false;
        }
        
        // Verify user exists and is active
        global $db;
        if (!$db) {
            $database = new Database();
            $db = $database->getConnection();
        }
        
        $query = "SELECT id FROM users WHERE id = ? AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$token_data['user_id']]);
        
        if ($stmt->fetch()) {
            return $token_data['user_id'];
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

function get_mobile_user_info($user_id) {
    global $db;
    if (!$db) {
        $database = new Database();
        $db = $database->getConnection();
    }
    
    $query = "SELECT id, email, first_name, last_name, phone FROM users WHERE id = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
