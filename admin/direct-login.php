<?php
// Direct admin login bypass - FOR DEVELOPMENT ONLY
require_once '../config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get admin user
    $query = "SELECT id, name, email, role FROM admin_users WHERE email = 'pappuali548@gmail.com' AND is_active = 1 LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Set admin session directly
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        
        // Optional: Log direct login
        try {
            $log_query = "INSERT INTO admin_logs (admin_id, action, ip_address, details) VALUES (?, 'direct_login', ?, 'Development direct login bypass')";
            $log_stmt = $db->prepare($log_query);
            $log_stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception $e) {
            // Continue even if logging fails
        }
        
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<h2>Admin User Not Found</h2>";
        echo "<p>Admin user with email 'pappuali548@gmail.com' not found.</p>";
        echo "<p><a href='../check_admin.php'>Run Admin Check</a> to create the user.</p>";
        echo "<p><a href='login.php'>Back to Login</a></p>";
    }
} catch (Exception $e) {
    echo "<h2>Database Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='../setup.php'>Run Setup</a> to initialize the database.</p>";
    echo "<p><a href='login.php'>Back to Login</a></p>";
}
?>
