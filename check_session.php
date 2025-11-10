<?php
// Simple session and user check
require_once 'config/config.php';

echo "<h1>Session & User Check</h1>";

echo "<h2>Session Information</h2>";
echo "<pre>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION ?? []);
echo "</pre>";

echo "<h2>Login Status</h2>";
if (is_logged_in()) {
    echo "✅ User is logged in<br>";
    echo "User ID: " . get_current_user_id() . "<br>";
    
    // Get user details
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $user_query = "SELECT * FROM users WHERE id = ?";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([get_current_user_id()]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<h3>User Details:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($user as $key => $value) {
                if ($key !== 'password') { // Don't show password
                    echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
                }
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "Error getting user details: " . $e->getMessage();
    }
} else {
    echo "❌ User is NOT logged in<br>";
    echo "<a href='login.php'>Login here</a>";
}

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<a href='fix_orders.php'>🔧 Fix Orders Tool</a> | ";
echo "<a href='orders.php'>📋 View Orders</a> | ";
echo "<a href='login.php'>🔑 Login</a> | ";
echo "<a href='register.php'>📝 Register</a>";
?>
