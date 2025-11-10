<?php
// Debug script for wishlist issues
require_once 'config/config.php';

echo "<h1>Debug Wishlist Issues</h1>";

// Check if user is logged in
if (!is_logged_in()) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<h3>❌ User Not Logged In</h3>";
    echo "<p>You need to be logged in to view wishlist items.</p>";
    echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Login</a>";
    echo "</div>";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<h2>1. User Information</h2>";
echo "✅ User ID: $user_id<br>";
echo "✅ User logged in successfully<br>";

echo "<hr>";

echo "<h2>2. Database Connection Test</h2>";
try {
    $test_query = $db->query("SELECT 1");
    echo "✅ Database connection working<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

echo "<h2>3. Check Wishlist Table</h2>";
try {
    $table_check = $db->query("SHOW TABLES LIKE 'wishlist'")->rowCount();
    if ($table_check > 0) {
        echo "✅ Wishlist table exists<br>";
        
        // Show table structure
        $columns = $db->query("DESCRIBE wishlist")->fetchAll(PDO::FETCH_ASSOC);
        echo "<details><summary>View table structure</summary>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table></details>";
    } else {
        echo "❌ Wishlist table does not exist<br>";
        echo "<a href='fix_wishlist_table.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Create Table</a>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error checking wishlist table: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>4. Total Wishlist Items in Database</h2>";
try {
    $total_wishlist = $db->query("SELECT COUNT(*) as count FROM wishlist")->fetch()['count'];
    echo "Total wishlist items in database: <strong>$total_wishlist</strong><br>";
    
    if ($total_wishlist == 0) {
        echo "❌ No wishlist items found in database<br>";
        echo "<p><strong>This is likely the issue!</strong> No products have been added to any wishlist yet.</p>";
    } else {
        echo "✅ Wishlist items exist in database<br>";
        
        // Show all wishlist items
        $all_items = $db->query("SELECT w.*, u.name as user_name, p.title as product_title FROM wishlist w LEFT JOIN users u ON w.user_id = u.id LEFT JOIN products p ON w.product_id = p.id ORDER BY w.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr><th>ID</th><th>User</th><th>Product</th><th>Added Date</th></tr>";
        foreach ($all_items as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['user_name']} (ID: {$item['user_id']})</td>";
            echo "<td>{$item['product_title']} (ID: {$item['product_id']})</td>";
            echo "<td>" . date('M j, Y H:i', strtotime($item['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error counting wishlist items: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>5. Wishlist Items for Current User</h2>";
try {
    $user_wishlist = $db->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $user_wishlist->execute([$user_id]);
    $user_wishlist_count = $user_wishlist->fetch()['count'];
    
    echo "Wishlist items for user $user_id: <strong>$user_wishlist_count</strong><br>";
    
    if ($user_wishlist_count == 0) {
        echo "❌ No wishlist items found for current user<br>";
        echo "<p><strong>This is the issue!</strong> The current user has no items in their wishlist.</p>";
        
        // Create a test wishlist item
        if (isset($_POST['create_test_item'])) {
            try {
                // Get a sample product
                $sample_product = $db->query("SELECT id FROM products WHERE is_active = 1 LIMIT 1")->fetch();
                if ($sample_product) {
                    $insert_test = $db->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
                    $insert_test->execute([$user_id, $sample_product['id']]);
                    
                    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
                    echo "✅ Test wishlist item created successfully!";
                    echo "</div>";
                    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
                } else {
                    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
                    echo "❌ No active products found to add to wishlist";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
                echo "❌ Error creating test item: " . $e->getMessage();
                echo "</div>";
            }
        }
        
        echo "<form method='post' style='margin: 10px 0;'>";
        echo "<button type='submit' name='create_test_item' style='background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;'>Create Test Wishlist Item</button>";
        echo "</form>";
        
    } else {
        echo "✅ User has wishlist items<br>";
        
        // Show user's wishlist items with details
        $user_items_detail = $db->prepare("SELECT w.*, p.title, p.price, p.is_active FROM wishlist w LEFT JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.created_at DESC");
        $user_items_detail->execute([$user_id]);
        $user_items = $user_items_detail->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr><th>Product ID</th><th>Product Title</th><th>Price</th><th>Active</th><th>Added Date</th></tr>";
        foreach ($user_items as $item) {
            $row_style = $item['is_active'] ? '' : 'style="background: #f8d7da;"';
            echo "<tr $row_style>";
            echo "<td>{$item['product_id']}</td>";
            echo "<td>" . ($item['title'] ?? 'Product not found') . "</td>";
            echo "<td>" . ($item['price'] ? format_currency($item['price']) : 'N/A') . "</td>";
            echo "<td>" . ($item['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . date('M j, Y H:i', strtotime($item['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error checking user wishlist: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>6. Test Wishlist Query (Same as wishlist.php)</h2>";
try {
    $wishlist_query = "SELECT w.*, p.title, p.price, p.screenshots, p.short_description, p.slug,
                               c.name as category_name, w.created_at as added_date
                        FROM wishlist w
                        JOIN products p ON w.product_id = p.id
                        LEFT JOIN categories c ON p.category_id = c.id
                        WHERE w.user_id = ? AND p.is_active = 1
                        ORDER BY w.created_at DESC
                        LIMIT 12 OFFSET 0";
    
    echo "<details><summary>View SQL Query</summary>";
    echo "<pre>" . htmlspecialchars($wishlist_query) . "</pre>";
    echo "<p>Parameters: user_id = $user_id</p>";
    echo "</details>";
    
    $wishlist_stmt = $db->prepare($wishlist_query);
    $wishlist_stmt->execute([$user_id]);
    $wishlist_items = $wishlist_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query result: <strong>" . count($wishlist_items) . " items found</strong><br>";
    
    if (count($wishlist_items) > 0) {
        echo "✅ Query successful, items found<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr><th>Product</th><th>Category</th><th>Price</th><th>Added Date</th></tr>";
        foreach ($wishlist_items as $item) {
            echo "<tr>";
            echo "<td>{$item['title']}</td>";
            echo "<td>{$item['category_name']}</td>";
            echo "<td>" . format_currency($item['price']) . "</td>";
            echo "<td>" . date('M j, Y', strtotime($item['added_date'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Query returned no results<br>";
        echo "<p>Possible reasons:</p>";
        echo "<ul>";
        echo "<li>No wishlist items for this user</li>";
        echo "<li>Products in wishlist are inactive (is_active = 0)</li>";
        echo "<li>Products have been deleted</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing wishlist query: " . $e->getMessage() . "<br>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

echo "<h2>7. Check Products Table</h2>";
try {
    $products_count = $db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1")->fetch()['count'];
    echo "Active products in database: <strong>$products_count</strong><br>";
    
    if ($products_count == 0) {
        echo "⚠️ No active products found - this might affect wishlist display<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking products: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>8. Solutions</h2>";

if ($user_wishlist_count == 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px;'>";
    echo "<h4>🔧 Solution: Add Products to Wishlist</h4>";
    echo "<p>The user has no wishlist items. Add some products to test the functionality:</p>";
    echo "<ol>";
    echo "<li>Go to any product page</li>";
    echo "<li>Click 'Add to Wishlist' button</li>";
    echo "<li>Then check the wishlist page again</li>";
    echo "</ol>";
    echo "<p><strong>Quick Test Links:</strong></p>";
    echo "<a href='product.php?id=1' style='background: #007bff; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Product Page</a>";
    echo "<a href='products.php' style='background: #28a745; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px;'>Browse Products</a>";
    echo "</div>";
}

echo "<hr>";

echo "<h2>9. Test Links</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='wishlist.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Wishlist Page</a>";
echo "<a href='product.php?id=1' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Test Product</a>";
echo "<a href='api/wishlist.php?action=count' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>API Test</a>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Refresh this page after adding items to wishlist to see updated results.</strong></p>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2 { color: #333; }
hr { margin: 20px 0; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
details { margin: 10px 0; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>
