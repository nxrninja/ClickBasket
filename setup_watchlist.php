<?php
// Setup script for watchlist functionality
require_once 'config/config.php';

echo "<h1>Watchlist Setup - ClickBasket</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>1. Creating Watchlist Table</h2>";
    
    // Check if watchlist table exists
    $table_exists = $db->query("SHOW TABLES LIKE 'watchlist'")->rowCount() > 0;
    
    if (!$table_exists) {
        echo "Creating watchlist table...<br>";
        
        $create_table_sql = "
        CREATE TABLE watchlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_watchlist (user_id, product_id),
            INDEX idx_user_id (user_id),
            INDEX idx_product_id (product_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($create_table_sql);
        echo "✅ Watchlist table created successfully!<br>";
    } else {
        echo "✅ Watchlist table already exists<br>";
    }
    
    echo "<hr>";
    
    // Test the watchlist functionality
    echo "<h2>2. Testing Watchlist Functionality</h2>";
    
    // Check if we have any users and products for testing
    $users_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $products_count = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
    
    echo "Users in database: <strong>$users_count</strong><br>";
    echo "Active products in database: <strong>$products_count</strong><br>";
    
    if ($users_count > 0 && $products_count > 0) {
        echo "✅ Database has users and products for testing<br>";
        
        // Get sample user and product
        $sample_user = $db->query("SELECT id, name FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $sample_product = $db->query("SELECT id, title FROM products WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if ($sample_user && $sample_product) {
            echo "<br><strong>Sample Test Data:</strong><br>";
            echo "Sample User: {$sample_user['name']} (ID: {$sample_user['id']})<br>";
            echo "Sample Product: {$sample_product['title']} (ID: {$sample_product['id']})<br>";
        }
    } else {
        echo "⚠️ Need users and products in database for full testing<br>";
    }
    
    echo "<hr>";
    
    // Show table structure
    echo "<h2>3. Watchlist Table Structure</h2>";
    $columns = $db->query("DESCRIBE watchlist")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    // API endpoints information
    echo "<h2>4. API Endpoints</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
    echo "<h4>Watchlist API Endpoints:</h4>";
    echo "<ul>";
    echo "<li><strong>POST /api/watchlist.php</strong> - Add/Remove items</li>";
    echo "<li><strong>GET /api/watchlist.php?action=check&product_id=X</strong> - Check if product is in watchlist</li>";
    echo "<li><strong>GET /api/watchlist.php?action=list</strong> - Get user's watchlist</li>";
    echo "<li><strong>GET /api/watchlist.php?action=count</strong> - Get watchlist count</li>";
    echo "</ul>";
    
    echo "<h4>Request Examples:</h4>";
    echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 4px;'>";
    echo "// Add to watchlist\n";
    echo "POST /api/watchlist.php\n";
    echo "{\n";
    echo '  "action": "add",'."\n";
    echo '  "product_id": 1'."\n";
    echo "}\n\n";
    echo "// Remove from watchlist\n";
    echo "POST /api/watchlist.php\n";
    echo "{\n";
    echo '  "action": "remove",'."\n";
    echo '  "product_id": 1'."\n";
    echo "}\n";
    echo "</pre>";
    echo "</div>";
    
    echo "<hr>";
    
    // Navigation links
    echo "<h2>5. Test Links</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>";
    
    $test_links = [
        ['url' => 'product.php?id=1', 'title' => 'Test Product Page', 'desc' => 'Test watchlist buttons'],
        ['url' => 'watchlist.php', 'title' => 'Watchlist Page', 'desc' => 'View watchlist items'],
        ['url' => 'api/watchlist.php?action=count', 'title' => 'API Count Test', 'desc' => 'Test API endpoint'],
        ['url' => 'profile.php', 'title' => 'User Profile', 'desc' => 'Check profile integration']
    ];
    
    foreach ($test_links as $link) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center;'>";
        echo "<h4>{$link['title']}</h4>";
        echo "<p style='color: #666; font-size: 0.9rem;'>{$link['desc']}</p>";
        echo "<a href='{$link['url']}' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Test</a>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<hr>";
    
    // Success message
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 8px;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Watchlist Setup Complete!</h3>";
    echo "<p style='color: #155724;'>The watchlist functionality has been successfully set up:</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li><strong>Database:</strong> Watchlist table created with proper indexes</li>";
    echo "<li><strong>API:</strong> RESTful endpoints for watchlist management</li>";
    echo "<li><strong>Frontend:</strong> JavaScript integration in product pages</li>";
    echo "<li><strong>Security:</strong> User authentication and data validation</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 8px;'>";
    echo "<h3 style='color: #721c24;'>❌ Setup Error</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p style='color: #721c24;'><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p style='color: #721c24;'><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2, h3, h4 { color: #333; }
hr { margin: 30px 0; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>
