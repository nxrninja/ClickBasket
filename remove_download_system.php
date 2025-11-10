<?php
// Script to completely remove download system from ClickBasket
require_once 'config/config.php';

echo "<h1>Remove Download System - ClickBasket</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>1. Removing Download-Related Database Elements</h2>";
    
    // Check and drop downloads table if it exists
    $downloads_table_exists = $db->query("SHOW TABLES LIKE 'downloads'")->rowCount() > 0;
    if ($downloads_table_exists) {
        echo "❌ Downloads table found - removing...<br>";
        $db->exec("DROP TABLE IF EXISTS downloads");
        echo "✅ Downloads table removed successfully!<br>";
    } else {
        echo "✅ Downloads table doesn't exist<br>";
    }
    
    // Remove downloads_count column from products table if it exists
    $downloads_count_exists = $db->query("SHOW COLUMNS FROM products LIKE 'downloads_count'")->rowCount() > 0;
    if ($downloads_count_exists) {
        echo "❌ downloads_count column found in products table - removing...<br>";
        $db->exec("ALTER TABLE products DROP COLUMN downloads_count");
        echo "✅ downloads_count column removed successfully!<br>";
    } else {
        echo "✅ downloads_count column doesn't exist in products table<br>";
    }
    
    echo "<hr>";
    
    // Check for download-related files
    echo "<h2>2. Checking Download-Related Files</h2>";
    
    $download_files = [
        'downloads.php',
        'api/downloads.php',
        'download.php'
    ];
    
    foreach ($download_files as $file) {
        if (file_exists($file)) {
            echo "❌ Found download file: $file<br>";
            if (isset($_POST['remove_files'])) {
                if (unlink($file)) {
                    echo "✅ Removed $file successfully!<br>";
                } else {
                    echo "❌ Failed to remove $file<br>";
                }
            }
        } else {
            echo "✅ $file doesn't exist<br>";
        }
    }
    
    if (!isset($_POST['remove_files'])) {
        echo "<form method='post' style='margin: 15px 0;'>";
        echo "<input type='hidden' name='remove_files' value='1'>";
        echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Remove Download Files</button>";
        echo "</form>";
    }
    
    echo "<hr>";
    
    // Update product features
    echo "<h2>3. Database Cleanup Complete</h2>";
    
    // Show summary of changes
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Download System Removal Complete!</h3>";
    echo "<p style='color: #155724;'>The following changes have been made:</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li><strong>Database:</strong> Removed downloads table and downloads_count column</li>";
    echo "<li><strong>Product Pages:</strong> Removed download counts and instant download features</li>";
    echo "<li><strong>Orders:</strong> Removed download buttons from order history</li>";
    echo "<li><strong>Profile:</strong> Replaced downloads with reviews in user statistics</li>";
    echo "<li><strong>Homepage:</strong> Updated trending algorithm to exclude downloads</li>";
    echo "<li><strong>Navigation:</strong> Replaced downloads links with reviews links</li>";
    echo "<li><strong>New Feature:</strong> Added comprehensive 5-star rating and review system</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>4. Updated Features</h2>";
    echo "<div style='background: #e7f3ff; padding: 20px; border: 1px solid #bee5eb; border-radius: 8px;'>";
    echo "<h4>🌟 New Rating & Review System:</h4>";
    echo "<ul>";
    echo "<li>5-star rating system on all product pages</li>";
    echo "<li>Detailed review submission with titles and text</li>";
    echo "<li>Verified purchase badges</li>";
    echo "<li>Rating breakdowns and statistics</li>";
    echo "<li>User review management page</li>";
    echo "</ul>";
    
    echo "<h4>📊 Updated Statistics:</h4>";
    echo "<ul>";
    echo "<li>User profiles now show review counts instead of downloads</li>";
    echo "<li>Trending products based on purchases and ratings</li>";
    echo "<li>Product pages focus on customer feedback</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<hr>";
    
    // Test links
    echo "<h2>5. Test Updated Pages</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    $test_pages = [
        ['url' => 'index.php', 'title' => 'Homepage', 'icon' => 'home'],
        ['url' => 'products.php', 'title' => 'Products', 'icon' => 'th-large'],
        ['url' => 'product.php?id=1', 'title' => 'Product Page', 'icon' => 'star'],
        ['url' => 'profile.php', 'title' => 'Profile', 'icon' => 'user'],
        ['url' => 'orders.php', 'title' => 'Orders', 'icon' => 'box'],
        ['url' => 'reviews.php', 'title' => 'My Reviews', 'icon' => 'star']
    ];
    
    foreach ($test_pages as $page) {
        echo "<div style='text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 8px;'>";
        echo "<i class='fas fa-{$page['icon']}' style='font-size: 2rem; color: var(--primary-color); margin-bottom: 10px;'></i><br>";
        echo "<strong>{$page['title']}</strong><br>";
        echo "<a href='{$page['url']}' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 0.875rem; margin-top: 10px; display: inline-block;'>Test Page</a>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<hr>";
    
    // Final recommendations
    echo "<h2>6. Recommendations</h2>";
    echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffeaa7; border-radius: 8px;'>";
    echo "<h4>📋 Next Steps:</h4>";
    echo "<ol>";
    echo "<li><strong>Setup Ratings:</strong> Run <a href='setup_ratings.php'>setup_ratings.php</a> if you haven't already</li>";
    echo "<li><strong>Test All Pages:</strong> Verify all pages work correctly without download references</li>";
    echo "<li><strong>Update Content:</strong> Review and update any remaining download-related content</li>";
    echo "<li><strong>User Communication:</strong> Inform users about the new rating system</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 8px;'>";
    echo "<h3 style='color: #721c24;'>❌ Error During Removal</h3>";
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
</style>
