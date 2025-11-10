<?php
// Database fix script for missing columns
require_once 'config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>ClickBasket Database Fix</h2>";
    echo "<p>Checking and fixing database structure...</p>";
    
    // Check if is_active column exists in products table
    $check_query = "SHOW COLUMNS FROM products LIKE 'is_active'";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    $column_exists = $check_stmt->fetch();
    
    if (!$column_exists) {
        echo "<p style='color: orange;'>⚠️ Missing 'is_active' column in products table. Adding...</p>";
        
        $add_column_query = "ALTER TABLE products ADD COLUMN is_active BOOLEAN DEFAULT TRUE";
        $add_stmt = $db->prepare($add_column_query);
        $add_stmt->execute();
        
        echo "<p style='color: green;'>✅ Added 'is_active' column to products table.</p>";
    } else {
        echo "<p style='color: green;'>✅ 'is_active' column already exists in products table.</p>";
    }
    
    // Check if is_featured column exists in products table
    $check_featured_query = "SHOW COLUMNS FROM products LIKE 'is_featured'";
    $check_featured_stmt = $db->prepare($check_featured_query);
    $check_featured_stmt->execute();
    $featured_column_exists = $check_featured_stmt->fetch();
    
    if (!$featured_column_exists) {
        echo "<p style='color: orange;'>⚠️ Missing 'is_featured' column in products table. Adding...</p>";
        
        $add_featured_query = "ALTER TABLE products ADD COLUMN is_featured BOOLEAN DEFAULT FALSE";
        $add_featured_stmt = $db->prepare($add_featured_query);
        $add_featured_stmt->execute();
        
        echo "<p style='color: green;'>✅ Added 'is_featured' column to products table.</p>";
    } else {
        echo "<p style='color: green;'>✅ 'is_featured' column already exists in products table.</p>";
    }
    
    // Check if screenshots column exists in products table
    $check_screenshots_query = "SHOW COLUMNS FROM products LIKE 'screenshots'";
    $check_screenshots_stmt = $db->prepare($check_screenshots_query);
    $check_screenshots_stmt->execute();
    $screenshots_column_exists = $check_screenshots_stmt->fetch();
    
    if (!$screenshots_column_exists) {
        echo "<p style='color: orange;'>⚠️ Missing 'screenshots' column in products table. Adding...</p>";
        
        $add_screenshots_query = "ALTER TABLE products ADD COLUMN screenshots JSON";
        $add_screenshots_stmt = $db->prepare($add_screenshots_query);
        $add_screenshots_stmt->execute();
        
        echo "<p style='color: green;'>✅ Added 'screenshots' column to products table.</p>";
    } else {
        echo "<p style='color: green;'>✅ 'screenshots' column already exists in products table.</p>";
    }
    
    // Check categories table structure
    echo "<h3>Checking Categories Table</h3>";
    
    $check_cat_active_query = "SHOW COLUMNS FROM categories LIKE 'is_active'";
    $check_cat_active_stmt = $db->prepare($check_cat_active_query);
    $check_cat_active_stmt->execute();
    $cat_active_exists = $check_cat_active_stmt->fetch();
    
    if (!$cat_active_exists) {
        echo "<p style='color: orange;'>⚠️ Missing 'is_active' column in categories table. Adding...</p>";
        
        $add_cat_active_query = "ALTER TABLE categories ADD COLUMN is_active BOOLEAN DEFAULT TRUE";
        $add_cat_active_stmt = $db->prepare($add_cat_active_query);
        $add_cat_active_stmt->execute();
        
        echo "<p style='color: green;'>✅ Added 'is_active' column to categories table.</p>";
    } else {
        echo "<p style='color: green;'>✅ 'is_active' column already exists in categories table.</p>";
    }
    
    // Check users table structure
    echo "<h3>Checking Users Table</h3>";
    
    $check_user_active_query = "SHOW COLUMNS FROM users LIKE 'is_active'";
    $check_user_active_stmt = $db->prepare($check_user_active_query);
    $check_user_active_stmt->execute();
    $user_active_exists = $check_user_active_stmt->fetch();
    
    if (!$user_active_exists) {
        echo "<p style='color: orange;'>⚠️ Missing 'is_active' column in users table. Adding...</p>";
        
        $add_user_active_query = "ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE";
        $add_user_active_stmt = $db->prepare($add_user_active_query);
        $add_user_active_stmt->execute();
        
        echo "<p style='color: green;'>✅ Added 'is_active' column to users table.</p>";
    } else {
        echo "<p style='color: green;'>✅ 'is_active' column already exists in users table.</p>";
    }
    
    $check_user_verified_query = "SHOW COLUMNS FROM users LIKE 'is_verified'";
    $check_user_verified_stmt = $db->prepare($check_user_verified_query);
    $check_user_verified_stmt->execute();
    $user_verified_exists = $check_user_verified_stmt->fetch();
    
    if (!$user_verified_exists) {
        echo "<p style='color: orange;'>⚠️ Missing 'is_verified' column in users table. Adding...</p>";
        
        $add_user_verified_query = "ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE";
        $add_user_verified_stmt = $db->prepare($add_user_verified_query);
        $add_user_verified_stmt->execute();
        
        echo "<p style='color: green;'>✅ Added 'is_verified' column to users table.</p>";
    } else {
        echo "<p style='color: green;'>✅ 'is_verified' column already exists in users table.</p>";
    }
    
    // Show current table structure
    echo "<h3>Current Products Table Structure:</h3>";
    $structure_query = "DESCRIBE products";
    $structure_stmt = $db->prepare($structure_query);
    $structure_stmt->execute();
    $columns = $structure_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 1rem 0;'>";
    echo "<tr style='background: #f0f0f0;'><th style='padding: 0.5rem;'>Field</th><th style='padding: 0.5rem;'>Type</th><th style='padding: 0.5rem;'>Null</th><th style='padding: 0.5rem;'>Key</th><th style='padding: 0.5rem;'>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$column['Field']}</td>";
        echo "<td style='padding: 0.5rem;'>{$column['Type']}</td>";
        echo "<td style='padding: 0.5rem;'>{$column['Null']}</td>";
        echo "<td style='padding: 0.5rem;'>{$column['Key']}</td>";
        echo "<td style='padding: 0.5rem;'>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check cart table structure
    echo "<h3>Checking Cart Table</h3>";
    
    $check_cart_updated_query = "SHOW COLUMNS FROM cart LIKE 'updated_at'";
    $check_cart_updated_stmt = $db->prepare($check_cart_updated_query);
    $check_cart_updated_stmt->execute();
    $cart_updated_exists = $check_cart_updated_stmt->fetch();
    
    if (!$cart_updated_exists) {
        echo "<p style='color: orange;'>⚠️ Missing 'updated_at' column in cart table. Adding...</p>";
        
        $add_cart_updated_query = "ALTER TABLE cart ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $add_cart_updated_stmt = $db->prepare($add_cart_updated_query);
        $add_cart_updated_stmt->execute();
        
        echo "<p style='color: green;'>✅ Added 'updated_at' column to cart table.</p>";
    } else {
        echo "<p style='color: green;'>✅ 'updated_at' column already exists in cart table.</p>";
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✅ Database Fix Complete!</h3>";
    echo "<p>All required columns have been added to the database tables.</p>";
    echo "<p><a href='admin/products.php' class='btn btn-primary'>Go to Admin Products</a></p>";
    echo "<p><a href='admin/dashboard.php' class='btn btn-secondary'>Go to Admin Dashboard</a></p>";
    echo "<p><a href='product.php?id=1' class='btn btn-info'>Test Product Page</a></p>";
    
    // Add some basic styling
    echo "<style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; }
        .btn { display: inline-block; padding: 0.5rem 1rem; margin: 0.25rem; text-decoration: none; border-radius: 0.25rem; }
        .btn-primary { background: #007cba; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; }
        th { background: #f8f9fa; }
    </style>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>
