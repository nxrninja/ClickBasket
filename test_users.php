<?php
// Test script to check users table
require_once 'config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Users Table Test</h2>";
    
    // Check if users table exists
    $tables_query = "SHOW TABLES LIKE 'users'";
    $tables_stmt = $db->prepare($tables_query);
    $tables_stmt->execute();
    $table_exists = $tables_stmt->fetch();
    
    if (!$table_exists) {
        echo "<p style='color: red;'>❌ users table does not exist!</p>";
        echo "<p>Please run the setup script to create the database tables.</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ users table exists</p>";
    
    // Check table structure
    $structure_query = "DESCRIBE users";
    $structure_stmt = $db->prepare($structure_query);
    $structure_stmt->execute();
    $columns = $structure_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count total users
    $count_query = "SELECT COUNT(*) as total FROM users";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<h3>Total Users: {$total_users}</h3>";
    
    if ($total_users > 0) {
        // Show sample users
        $sample_query = "SELECT id, name, email, is_active, created_at FROM users LIMIT 5";
        $sample_stmt = $db->prepare($sample_query);
        $sample_stmt->execute();
        $sample_users = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Users:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Active</th><th>Created</th></tr>";
        foreach ($sample_users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No users found in the database.</p>";
        echo "<p>You can create a test user:</p>";
        
        // Create a test user
        if (isset($_POST['create_test_user'])) {
            $test_name = 'Test User';
            $test_email = 'test@example.com';
            $test_password = password_hash('password123', PASSWORD_DEFAULT);
            
            $insert_query = "INSERT INTO users (name, email, password, is_active) VALUES (?, ?, ?, 1)";
            $insert_stmt = $db->prepare($insert_query);
            
            try {
                $insert_stmt->execute([$test_name, $test_email, $test_password]);
                echo "<p style='color: green;'>✅ Test user created successfully!</p>";
                echo "<p><strong>Email:</strong> test@example.com</p>";
                echo "<p><strong>Password:</strong> password123</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Failed to create test user: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<form method='POST'>";
            echo "<button type='submit' name='create_test_user' style='padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer;'>Create Test User</button>";
            echo "</form>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='admin/users.php'>Go to Admin Users Page</a></p>";
    echo "<p><a href='register.php'>Register New User</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>
