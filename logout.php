<?php
require_once 'config/config.php';

// Destroy session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to home page with success message
session_start();
handle_success('You have been logged out successfully.', 'index.php');
?>
