<?php
// Fix script for wishlist display issues
require_once 'config/config.php';

echo "<h1>Fix Wishlist Display Issues</h1>";

if (!is_logged_in()) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<h3>❌ Please Login First</h3>";
    echo "<p>You need to be logged in to access wishlist functionality.</p>";
    echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Login Now</a>";
    echo "</div>";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<h2>Diagnosing Wishlist Issues...</h2>";

try {
    // Step 1: Check if wishlist table exists
    echo "<h3>1. Checking Wishlist Table</h3>";
    $table_exists = $db->query("SHOW TABLES LIKE 'wishlist'")->rowCount() > 0;
    
    if (!$table_exists) {
        echo "❌ Wishlist table doesn't exist<br>";
        echo "<a href='fix_wishlist_table.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Create Wishlist Table</a>";
        exit;
    }
    echo "✅ Wishlist table exists<br>";
    
    // Step 2: Check user's wishlist items
    echo "<h3>2. Checking User's Wishlist</h3>";
    $user_items = $db->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $user_items->execute([$user_id]);
    $item_count = $user_items->fetch()['count'];
    
    echo "User ID: $user_id<br>";
    echo "Wishlist items: <strong>$item_count</strong><br>";
    
    if ($item_count == 0) {
        echo "❌ No items in wishlist - this is why the page is empty<br>";
        
        // Step 3: Add sample products to wishlist
        echo "<h3>3. Adding Sample Products to Wishlist</h3>";
        
        if (isset($_POST['add_samples'])) {
            // Get some active products
            $products = $db->query("SELECT id, title FROM products WHERE is_active = 1 LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($products)) {
                $added_count = 0;
                foreach ($products as $product) {
                    try {
                        $insert = $db->prepare("INSERT IGNORE INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
                        $insert->execute([$user_id, $product['id']]);
                        if ($insert->rowCount() > 0) {
                            $added_count++;
                            echo "✅ Added: {$product['title']}<br>";
                        }
                    } catch (Exception $e) {
                        echo "❌ Failed to add {$product['title']}: " . $e->getMessage() . "<br>";
                    }
                }
                
                if ($added_count > 0) {
                    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 15px 0;'>";
                    echo "<h4>✅ Success!</h4>";
                    echo "<p>Added $added_count products to your wishlist.</p>";
                    echo "<a href='wishlist.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>View Wishlist Now</a>";
                    echo "</div>";
                }
            } else {
                echo "❌ No active products found in database<br>";
            }
        } else {
            echo "<form method='post' style='margin: 15px 0;'>";
            echo "<button type='submit' name='add_samples' style='background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;'>Add Sample Products to Wishlist</button>";
            echo "</form>";
        }
    } else {
        echo "✅ User has $item_count items in wishlist<br>";
        
        // Step 4: Test the wishlist query
        echo "<h3>4. Testing Wishlist Query</h3>";
        
        $wishlist_query = "SELECT w.*, p.title, p.price, p.screenshots, p.short_description, p.slug,
                                   c.name as category_name, w.created_at as added_date
                            FROM wishlist w
                            JOIN products p ON w.product_id = p.id
                            LEFT JOIN categories c ON p.category_id = c.id
                            WHERE w.user_id = ? AND p.is_active = 1
                            ORDER BY w.created_at DESC";
        
        $stmt = $db->prepare($wishlist_query);
        $stmt->execute([$user_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Query returned: <strong>" . count($results) . " results</strong><br>";
        
        if (count($results) > 0) {
            echo "✅ Query working correctly<br>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr><th>Product</th><th>Category</th><th>Price</th><th>Active</th></tr>";
            foreach ($results as $item) {
                echo "<tr>";
                echo "<td>{$item['title']}</td>";
                echo "<td>{$item['category_name']}</td>";
                echo "<td>" . format_currency($item['price']) . "</td>";
                echo "<td>Yes</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 15px 0;'>";
            echo "<h4>✅ Wishlist Should Work!</h4>";
            echo "<p>The query is returning results, so the wishlist page should show products.</p>";
            echo "<a href='wishlist.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>View Wishlist Page</a>";
            echo "</div>";
        } else {
            echo "❌ Query returned no results<br>";
            echo "<p>Possible issues:</p>";
            echo "<ul>";
            echo "<li>Products in wishlist are inactive (is_active = 0)</li>";
            echo "<li>Products have been deleted</li>";
            echo "<li>JOIN query issue</li>";
            echo "</ul>";
            
            // Check raw wishlist data
            echo "<h4>Raw Wishlist Data:</h4>";
            $raw_data = $db->prepare("SELECT w.*, p.title, p.is_active FROM wishlist w LEFT JOIN products p ON w.product_id = p.id WHERE w.user_id = ?");
            $raw_data->execute([$user_id]);
            $raw_results = $raw_data->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Wishlist ID</th><th>Product ID</th><th>Product Title</th><th>Is Active</th></tr>";
            foreach ($raw_results as $item) {
                $row_style = !$item['is_active'] ? 'style="background: #f8d7da;"' : '';
                echo "<tr $row_style>";
                echo "<td>{$item['id']}</td>";
                echo "<td>{$item['product_id']}</td>";
                echo "<td>" . ($item['title'] ?? 'Product not found') . "</td>";
                echo "<td>" . ($item['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "<hr>";
    
    // Step 5: Quick actions
    echo "<h3>5. Quick Actions</h3>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>";
    
    $actions = [
        ['url' => 'wishlist.php', 'title' => 'View Wishlist', 'desc' => 'Check wishlist page', 'color' => '#007bff'],
        ['url' => 'product.php?id=1', 'title' => 'Test Product Page', 'desc' => 'Add items via product page', 'color' => '#28a745'],
        ['url' => 'test_add_to_wishlist.php', 'title' => 'Test Add Items', 'desc' => 'Bulk add test items', 'color' => '#17a2b8'],
        ['url' => 'debug_wishlist.php', 'title' => 'Full Debug', 'desc' => 'Complete diagnostic', 'color' => '#ffc107']
    ];
    
    foreach ($actions as $action) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center;'>";
        echo "<h4>{$action['title']}</h4>";
        echo "<p style='color: #666; font-size: 0.9rem;'>{$action['desc']}</p>";
        echo "<a href='{$action['url']}' style='background: {$action['color']}; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px;'>Go</a>";
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
hr { margin: 20px 0; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>
