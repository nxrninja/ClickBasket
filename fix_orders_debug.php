<?php
// Quick diagnostic script for orders issue
require_once 'config/config.php';

echo "<h1>Orders Debug & Fix</h1>";

if (!is_logged_in()) {
    echo "<p style='color: red;'>❌ User not logged in. <a href='login.php'>Please login first</a></p>";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<h2>1. User Information</h2>";
echo "<p>✅ User ID: $user_id</p>";
echo "<p>✅ User logged in successfully</p>";

echo "<h2>2. Database Tables Check</h2>";

// Check if orders table exists
try {
    $tables_check = $db->query("SHOW TABLES LIKE 'orders'")->rowCount();
    if ($tables_check > 0) {
        echo "<p>✅ Orders table exists</p>";
        
        // Check orders table structure
        $columns = $db->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
        echo "<details><summary>Orders table structure</summary>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table></details>";
    } else {
        echo "<p style='color: red;'>❌ Orders table does not exist!</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking orders table: " . $e->getMessage() . "</p>";
    exit;
}

// Check if order_items table exists
try {
    $items_check = $db->query("SHOW TABLES LIKE 'order_items'")->rowCount();
    if ($items_check > 0) {
        echo "<p>✅ Order_items table exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Order_items table does not exist - this might cause issues</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking order_items table: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Orders Count</h2>";

// Total orders in database
try {
    $total_orders = $db->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
    echo "<p>Total orders in database: <strong>$total_orders</strong></p>";
    
    if ($total_orders == 0) {
        echo "<p style='color: orange;'>⚠️ No orders found in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error counting orders: " . $e->getMessage() . "</p>";
}

// Orders for current user
try {
    $user_orders = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $user_orders->execute([$user_id]);
    $user_order_count = $user_orders->fetch()['count'];
    
    echo "<p>Orders for current user: <strong>$user_order_count</strong></p>";
    
    if ($user_order_count == 0) {
        echo "<p style='color: orange;'>⚠️ No orders found for current user</p>";
    } else {
        // Show user's orders
        $user_orders_detail = $db->prepare("SELECT id, order_number, order_status, payment_status, final_amount, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $user_orders_detail->execute([$user_id]);
        $orders = $user_orders_detail->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Recent Orders:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Order #</th><th>Status</th><th>Payment</th><th>Amount</th><th>Date</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['order_status']}</td>";
            echo "<td>{$order['payment_status']}</td>";
            echo "<td>₹" . number_format($order['final_amount'], 2) . "</td>";
            echo "<td>" . date('M j, Y H:i', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking user orders: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Test Order Creation</h2>";

if (isset($_POST['create_test_order'])) {
    try {
        $db->beginTransaction();
        
        $order_number = 'TEST-' . time() . '-' . rand(100, 999);
        
        // Create test order
        $order_query = "INSERT INTO orders (user_id, order_number, total_amount, discount_amount, tax_amount, final_amount, payment_method, payment_status, order_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $order_stmt = $db->prepare($order_query);
        $order_stmt->execute([
            $user_id,
            $order_number,
            99.99,
            0,
            0,
            99.99,
            'cod',
            'pending',
            'pending'
        ]);
        
        $order_id = $db->lastInsertId();
        
        // Create test order item if table exists
        if ($items_check > 0) {
            $item_query = "INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->execute([
                $order_id,
                1,
                "Test Product",
                99.99,
                1
            ]);
        }
        
        $db->commit();
        
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "✅ Test order created successfully! Order ID: $order_id, Order Number: $order_number";
        echo "</div>";
        
        echo "<p><a href='orders.php'>Go to Orders Page</a> to see the new order.</p>";
        
    } catch (Exception $e) {
        $db->rollBack();
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "❌ Error creating test order: " . $e->getMessage();
        echo "</div>";
    }
}

echo "<form method='post'>";
echo "<button type='submit' name='create_test_order' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Create Test Order</button>";
echo "</form>";

echo "<h2>5. Quick Links</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='orders.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Orders Page</a>";
echo "<a href='checkout.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Checkout</a>";
echo "<a href='cart.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Cart</a>";
echo "<a href='products.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Products</a>";
echo "</div>";

echo "<h2>6. Possible Issues & Solutions</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px;'>";
echo "<h4>Common Issues:</h4>";
echo "<ul>";
echo "<li><strong>Orders not showing:</strong> Check if orders are actually being created during checkout</li>";
echo "<li><strong>Database issues:</strong> Ensure orders and order_items tables exist with correct structure</li>";
echo "<li><strong>User session:</strong> Make sure user is properly logged in</li>";
echo "<li><strong>Query issues:</strong> Check for SQL errors in orders.php</li>";
echo "</ul>";
echo "<h4>Solutions:</h4>";
echo "<ul>";
echo "<li>Create a test order using the button above</li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Enable debug mode: <a href='orders.php?debug=1'>orders.php?debug=1</a></li>";
echo "<li>Check error logs in XAMPP</li>";
echo "</ul>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
details { margin: 10px 0; }
</style>
