<?php
// Comprehensive fix for product page server error
require_once 'config/config.php';

echo "<h1>ClickBasket Product Page Error Fix</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>1. Checking Database Schema</h2>";
    
    // Check if screenshots column exists in products table
    $screenshots_exists = $db->query("SHOW COLUMNS FROM products LIKE 'screenshots'")->rowCount() > 0;
    
    if (!$screenshots_exists) {
        echo "❌ Screenshots column missing from products table<br>";
        echo "<strong>Adding screenshots column...</strong><br>";
        
        try {
            $db->exec("ALTER TABLE products ADD COLUMN screenshots TEXT DEFAULT NULL");
            echo "✅ Screenshots column added successfully!<br>";
        } catch (Exception $e) {
            echo "❌ Error adding screenshots column: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ Screenshots column exists in products table<br>";
    }
    
    // Check if rating columns exist
    $rating_exists = $db->query("SHOW COLUMNS FROM products LIKE 'average_rating'")->rowCount() > 0;
    
    if (!$rating_exists) {
        echo "❌ Rating columns missing from products table<br>";
        echo "<strong>Adding rating columns...</strong><br>";
        
        try {
            $db->exec("ALTER TABLE products ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00");
            $db->exec("ALTER TABLE products ADD COLUMN rating_count INT DEFAULT 0");
            echo "✅ Rating columns added successfully!<br>";
        } catch (Exception $e) {
            echo "❌ Error adding rating columns: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ Rating columns exist in products table<br>";
    }
    
    echo "<hr>";
    
    // Test product retrieval
    echo "<h2>2. Testing Product Retrieval</h2>";
    
    $test_product_id = 1;
    $product_query = "SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1";
    $product_stmt = $db->prepare($product_query);
    $product_stmt->execute([$test_product_id]);
    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "✅ Product ID $test_product_id found<br>";
        echo "<strong>Product Title:</strong> " . htmlspecialchars($product['title']) . "<br>";
        echo "<strong>Screenshots:</strong> " . htmlspecialchars($product['screenshots'] ?? 'NULL') . "<br>";
    } else {
        echo "❌ No active product found with ID $test_product_id<br>";
        
        // Check if any products exist
        $any_product = $db->query("SELECT id, title FROM products WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($any_product) {
            echo "✅ Found active product: ID {$any_product['id']} - " . htmlspecialchars($any_product['title']) . "<br>";
            echo "<p><a href='product.php?id={$any_product['id']}'>Test this product page</a></p>";
        } else {
            echo "❌ No active products found in database<br>";
        }
    }
    
    echo "<hr>";
    
    // Update products with empty screenshots
    echo "<h2>3. Fixing Empty Screenshots</h2>";
    
    $empty_screenshots = $db->query("SELECT COUNT(*) as count FROM products WHERE screenshots IS NULL OR screenshots = ''")->fetch()['count'];
    
    if ($empty_screenshots > 0) {
        echo "Found $empty_screenshots products with empty screenshots<br>";
        echo "<strong>Setting default empty JSON array...</strong><br>";
        
        $update_stmt = $db->prepare("UPDATE products SET screenshots = '[]' WHERE screenshots IS NULL OR screenshots = ''");
        $update_stmt->execute();
        
        echo "✅ Updated products with empty screenshots array<br>";
    } else {
        echo "✅ All products have screenshots data<br>";
    }
    
    echo "<hr>";
    
    // Test the actual product page
    echo "<h2>4. Testing Product Page</h2>";
    
    $active_products = $db->query("SELECT id, title FROM products WHERE is_active = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($active_products)) {
        echo "<p>Test these product pages:</p>";
        echo "<ul>";
        foreach ($active_products as $prod) {
            echo "<li><a href='product.php?id={$prod['id']}' target='_blank'>" . htmlspecialchars($prod['title']) . "</a></li>";
        }
        echo "</ul>";
    }
    
    echo "<hr>";
    
    // Check for other potential issues
    echo "<h2>5. Additional Checks</h2>";
    
    // Check if Product class exists
    if (class_exists('Product')) {
        echo "✅ Product class is available<br>";
    } else {
        echo "❌ Product class not found<br>";
        if (file_exists('classes/Product.php')) {
            echo "✅ Product.php file exists<br>";
        } else {
            echo "❌ Product.php file missing<br>";
        }
    }
    
    // Check if categories table exists
    $categories_exist = $db->query("SHOW TABLES LIKE 'categories'")->rowCount() > 0;
    if ($categories_exist) {
        echo "✅ Categories table exists<br>";
    } else {
        echo "❌ Categories table missing<br>";
    }
    
    echo "<hr>";
    
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 8px;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Fix Complete!</h3>";
    echo "<p style='color: #155724;'>The product page should now work properly. If you still get errors:</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>Check the debug page: <a href='debug_product.php?id=1'>debug_product.php?id=1</a></li>";
    echo "<li>Ensure you have active products in the database</li>";
    echo "<li>Check server error logs for detailed error messages</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 8px;'>";
    echo "<h3 style='color: #721c24;'>❌ Fix Failed</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p style='color: #721c24;'><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p style='color: #721c24;'><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<a href='product.php?id=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Test Product Page</a>";
echo "<a href='debug_product.php?id=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Debug Product</a>";
echo "<a href='setup_ratings.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Setup Ratings</a>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
hr { margin: 30px 0; }
</style>
