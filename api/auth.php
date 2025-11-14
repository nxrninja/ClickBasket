<?php
// Authentication API endpoint for mobile apps
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            $email = sanitize_input($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                throw new Exception('Email and password are required');
            }
            
            // Check user credentials
            $query = "SELECT id, email, password, first_name, last_name, phone FROM users WHERE email = ? AND is_active = 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception('Invalid email or password');
            }
            
            // Generate JWT token (simple implementation)
            $token_data = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'exp' => time() + (7 * 24 * 60 * 60) // 7 days
            ];
            $token = base64_encode(json_encode($token_data));
            
            // Update last login
            $update_query = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$user['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'phone' => $user['phone']
                ]
            ]);
            break;
            
        case 'register':
            $first_name = sanitize_input($input['first_name'] ?? '');
            $last_name = sanitize_input($input['last_name'] ?? '');
            $email = sanitize_input($input['email'] ?? '');
            $phone = sanitize_input($input['phone'] ?? '');
            $password = $input['password'] ?? '';
            
            if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
                throw new Exception('All fields are required');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters');
            }
            
            // Check if email already exists
            $check_query = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                throw new Exception('Email already registered');
            }
            
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (first_name, last_name, email, phone, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password]);
            
            $user_id = $db->lastInsertId();
            
            // Generate token for new user
            $token_data = [
                'user_id' => $user_id,
                'email' => $email,
                'exp' => time() + (7 * 24 * 60 * 60)
            ];
            $token = base64_encode(json_encode($token_data));
            
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful',
                'token' => $token,
                'user' => [
                    'id' => $user_id,
                    'email' => $email,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone
                ]
            ]);
            break;
            
        case 'verify':
            $token = $input['token'] ?? '';
            
            if (empty($token)) {
                throw new Exception('Token is required');
            }
            
            $token_data = json_decode(base64_decode($token), true);
            
            if (!$token_data || $token_data['exp'] < time()) {
                throw new Exception('Invalid or expired token');
            }
            
            // Verify user still exists and is active
            $query = "SELECT id, email, first_name, last_name, phone FROM users WHERE id = ? AND is_active = 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$token_data['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('User not found or inactive');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Token is valid',
                'user' => $user
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
