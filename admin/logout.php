<?php
require_once '../config/config.php';

// Check if admin is logged in
if (is_admin_logged_in()) {
    try {
        // Log admin logout
        $database = new Database();
        $db = $database->getConnection();
        
        $log_query = "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, 'logout', ?)";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->execute([get_current_admin_id(), $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        // Log error but continue with logout
    }
}

// Destroy admin session
session_start();
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to admin login
handle_success('You have been logged out successfully.', 'admin/login.php');
?>
