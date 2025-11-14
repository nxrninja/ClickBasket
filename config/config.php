<?php
// Global configuration for ClickBasket
session_start();

// Site configuration
define('SITE_URL', 'http://pali.c0m.in/ClickBasket');
define('SITE_NAME', 'ClickBasket');
define('ADMIN_EMAIL', 'admin@clickbasket.com');

// File upload configuration
define('UPLOAD_DIR', 'uploads/');
define('PRODUCTS_DIR', 'uploads/products/');
define('SCREENSHOTS_DIR', 'uploads/screenshots/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Security
define('JWT_SECRET', 'your-secret-key-here-change-in-production');
define('ENCRYPTION_KEY', 'your-encryption-key-here');

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// Download settings
define('MAX_DOWNLOADS', 5);
define('DOWNLOAD_EXPIRY_DAYS', 30);

// Include database connection
require_once 'database.php';

// Utility functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function redirect($url) {
    header("Location: " . SITE_URL . "/" . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Error handling
function handle_error($message, $redirect_url = null) {
    $_SESSION['error'] = $message;
    if ($redirect_url) {
        redirect($redirect_url);
    }
}

function handle_success($message, $redirect_url = null) {
    $_SESSION['success'] = $message;
    if ($redirect_url) {
        redirect($redirect_url);
    }
}

// Get flash messages
function get_flash_message($type) {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return $message;
    }
    return null;
}

// Admin helper functions
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function get_current_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}

function get_admin_name() {
    return $_SESSION['admin_name'] ?? 'Admin';
}

// Clear admin session data when user logs in
function clear_admin_session() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_email']);
    unset($_SESSION['admin_role']);
}

// Clear user session data when admin logs in
function clear_user_session() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
}

// Get user name for display
function get_user_name() {
    return $_SESSION['user_name'] ?? 'User';
}

function get_admin_role() {
    return $_SESSION['admin_role'] ?? 'admin';
}

// Format currency in Indian Rupees
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}
?>
