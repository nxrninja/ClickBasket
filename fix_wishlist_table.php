<?php
// Quick fix script to create wishlist table
require_once 'config/config.php';

echo "<h1>Quick Fix: Create Wishlist Table</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Creating Wishlist Table...</h2>";
    
    // Create wishlist table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_wishlist (user_id, product_id),
        INDEX idx_user_id (user_id),
        INDEX idx_product_id (product_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($create_table_sql);
    
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Success!</h3>";
    echo "<p style='color: #155724;'>Wishlist table has been created successfully!</p>";
    echo "</div>";
    
    // Verify table exists
    $table_check = $db->query("SHOW TABLES LIKE 'wishlist'")->rowCount();
    if ($table_check > 0) {
        echo "<h3>✅ Table Verification</h3>";
        echo "<p>Wishlist table exists and is ready to use.</p>";
        
        // Show table structure
        echo "<h4>Table Structure:</h4>";
        $columns = $db->query("DESCRIBE wishlist")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
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
    }
    
    echo "<hr>";
    
    echo "<h2>🎉 Ready to Test!</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    $test_links = [
        ['url' => 'product.php?id=1', 'title' => 'Test Product Page', 'desc' => 'Test wishlist button', 'color' => '#007bff'],
        ['url' => 'wishlist.php', 'title' => 'View Wishlist', 'desc' => 'Check wishlist page', 'color' => '#28a745'],
        ['url' => 'api/wishlist.php?action=count', 'title' => 'Test API', 'desc' => 'Test API endpoint', 'color' => '#17a2b8']
    ];
    
    foreach ($test_links as $link) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center;'>";
        echo "<h4>{$link['title']}</h4>";
        echo "<p style='color: #666; font-size: 0.9rem; margin-bottom: 15px;'>{$link['desc']}</p>";
        echo "<a href='{$link['url']}' style='background: {$link['color']}; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block;'>Test Now</a>";
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24;'>❌ Error Creating Table</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p style='color: #721c24;'><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p style='color: #721c24;'><strong>Line:</strong> " . $e->getLine() . "</p>";
    
    echo "<h4 style='color: #721c24;'>Manual Solution:</h4>";
    echo "<ol style='color: #721c24;'>";
    echo "<li>Open phpMyAdmin</li>";
    echo "<li>Select your 'clickbasket' database</li>";
    echo "<li>Go to SQL tab</li>";
    echo "<li>Copy and paste the SQL from <a href='create_wishlist_table.sql' target='_blank'>create_wishlist_table.sql</a></li>";
    echo "<li>Click 'Go' to execute</li>";
    echo "</ol>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2, h3, h4 { color: #333; }
hr { margin: 30px 0; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; font-weight: bold; }
</style>
