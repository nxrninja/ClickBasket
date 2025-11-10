<?php
// Simple test to check admin login redirect behavior
require_once '../config/config.php';

echo "<h2>Admin Login Test</h2>";

echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>SITE_URL:</strong> " . SITE_URL . "</p>";

echo "<h3>Session Status:</h3>";
echo "<p><strong>Admin ID:</strong> " . ($_SESSION['admin_id'] ?? 'Not set') . "</p>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";

echo "<h3>Function Tests:</h3>";
echo "<p><strong>is_admin_logged_in():</strong> " . (is_admin_logged_in() ? 'TRUE' : 'FALSE') . "</p>";
echo "<p><strong>is_logged_in():</strong> " . (is_logged_in() ? 'TRUE' : 'FALSE') . "</p>";

echo "<h3>Links:</h3>";
echo "<p><a href='login.php'>Admin Login Page</a></p>";
echo "<p><a href='dashboard.php'>Admin Dashboard</a></p>";
echo "<p><a href='../login.php'>User Login Page</a></p>";
echo "<p><a href='../dashboard.php'>User Dashboard</a></p>";

echo "<h3>Clear Session:</h3>";
echo "<p><a href='?clear=1' style='color: red;'>Clear All Sessions</a></p>";

if (isset($_GET['clear'])) {
    session_destroy();
    session_start();
    echo "<p style='color: green;'>Sessions cleared! <a href='test_login.php'>Refresh</a></p>";
}
?>
