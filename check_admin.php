<?php
// Temporary script to check admin credentials
require_once 'config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Admin User Check</h2>";
    
    // Check if admin_users table exists
    $tables_query = "SHOW TABLES LIKE 'admin_users'";
    $tables_stmt = $db->prepare($tables_query);
    $tables_stmt->execute();
    $table_exists = $tables_stmt->fetch();
    
    if (!$table_exists) {
        echo "<p style='color: red;'>❌ admin_users table does not exist!</p>";
        echo "<p>Please run the setup script: <a href='setup.php'>setup.php</a></p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ admin_users table exists</p>";
    
    // Check all admin users
    $query = "SELECT id, name, email, role, is_active, created_at FROM admin_users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Admin Users:</h3>";
    if (empty($admins)) {
        echo "<p style='color: red;'>❌ No admin users found in database!</p>";
        echo "<p>Creating admin user now...</p>";
        
        // Create the admin user
        $name = 'Super Admin';
        $email = 'pappuali548@gmail.com';
        $password = '700121@Pappu';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO admin_users (name, email, password, role, is_active) VALUES (?, ?, ?, 'super_admin', 1)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([$name, $email, $hashed_password]);
        
        echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<p><strong>Password:</strong> $password</p>";
        
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Created</th></tr>";
        foreach ($admins as $admin) {
            $active_status = $admin['is_active'] ? '✅ Yes' : '❌ No';
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['name']}</td>";
            echo "<td>{$admin['email']}</td>";
            echo "<td>{$admin['role']}</td>";
            echo "<td>$active_status</td>";
            echo "<td>{$admin['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test password verification for the specific admin
    $test_email = 'pappuali548@gmail.com';
    $test_password = '700121@Pappu';
    
    $verify_query = "SELECT id, name, email, password, role, is_active FROM admin_users WHERE email = ? LIMIT 1";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute([$test_email]);
    $admin = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Password Verification Test:</h3>";
    echo "<p><strong>Testing Email:</strong> $test_email</p>";
    echo "<p><strong>Testing Password:</strong> $test_password</p>";
    
    if ($admin) {
        echo "<p style='color: green;'>✅ Admin user found in database</p>";
        echo "<p><strong>User ID:</strong> {$admin['id']}</p>";
        echo "<p><strong>Name:</strong> {$admin['name']}</p>";
        echo "<p><strong>Email:</strong> {$admin['email']}</p>";
        echo "<p><strong>Role:</strong> {$admin['role']}</p>";
        echo "<p><strong>Active:</strong> " . ($admin['is_active'] ? 'Yes' : 'No') . "</p>";
        
        if (password_verify($test_password, $admin['password'])) {
            echo "<p style='color: green;'>✅ Password verification SUCCESSFUL!</p>";
            echo "<p>You should be able to login with these credentials.</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification FAILED!</p>";
            echo "<p>The password hash doesn't match. Updating password...</p>";
            
            // Update the password
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE admin_users SET password = ? WHERE email = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$new_hash, $test_email]);
            
            echo "<p style='color: green;'>✅ Password updated successfully!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Admin user not found with email: $test_email</p>";
        echo "<p>Creating admin user...</p>";
        
        // Create the admin user
        $hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
        $insert_query = "INSERT INTO admin_users (name, email, password, role, is_active) VALUES (?, ?, ?, 'super_admin', 1)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute(['Super Admin', $test_email, $hashed_password]);
        
        echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. Try logging in at: <a href='admin/login.php'>Admin Login</a></p>";
    echo "<p>2. Use these credentials:</p>";
    echo "<p><strong>Email:</strong> pappuali548@gmail.com</p>";
    echo "<p><strong>Password:</strong> 700121@Pappu</p>";
    echo "<p>3. Delete this file after testing: <code>check_admin.php</code></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure your database is running and the schema has been created.</p>";
    echo "<p>Run the setup script: <a href='setup.php'>setup.php</a></p>";
}
?>
