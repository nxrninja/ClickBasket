<?php
// Test script to verify the complete order flow
require_once 'config/config.php';

echo "<h1>Order Flow Test</h1>";

if (!is_logged_in()) {
    echo "<p style='color: red;'>❌ Please <a href='login.php'>login</a> first</p>";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<h2>Step 1: User Authentication</h2>";
echo "<p>✅ User ID: $user_id</p>";
echo "<p>✅ User logged in successfully</p>";

echo "<h2>Step 2: Database Connection</h2>";
try {
    $db->query("SELECT 1");
    echo "<p>✅ Database connection working</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Step 3: Check Required Tables</h2>";
$required_tables = ['orders', 'order_items', 'cart', 'products', 'users'];
$missing_tables = [];

foreach ($required_tables as $table) {
    try {
        $check = $db->query("SHOW TABLES LIKE '$table'")->rowCount();
        if ($check > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
            $missing_tables[] = $table;
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
    echo "<h4>Missing Tables Detected</h4>";
    echo "<p>The following tables are missing: " . implode(', ', $missing_tables) . "</p>";
    echo "<p><a href='fix_orders_complete.php' class='btn btn-primary'>Run Complete Fix</a></p>";
    echo "</div>";
}

echo "<h2>Step 4: Test Order Creation</h2>";

if (isset($_POST['create_test_order'])) {
    try {
        $db->beginTransaction();
        
        $order_number = 'FLOW-TEST-' . time() . '-' . rand(100, 999);
        
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
        echo "<p>✅ Order created with ID: $order_id</p>";
        
        // Create test order item
        if (!in_array('order_items', $missing_tables)) {
            $item_query = "INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->execute([
                $order_id,
                1,
                "Flow Test Product",
                99.99,
                1
            ]);
            echo "<p>✅ Order item created</p>";
        }
        
        $db->commit();
        
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "<h4>✅ Test Order Created Successfully!</h4>";
        echo "<p><strong>Order ID:</strong> $order_id</p>";
        echo "<p><strong>Order Number:</strong> $order_number</p>";
        echo "<p><a href='orders.php' class='btn btn-primary'>Check Orders Page</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        $db->rollBack();
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "<h4>❌ Test Order Creation Failed</h4>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Trace:</strong></p>";
        echo "<pre style='font-size: 0.8em;'>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    }
}

echo "<form method='post'>";
echo "<button type='submit' name='create_test_order' class='btn btn-success'>Create Test Order</button>";
echo "</form>";

echo "<h2>Step 5: Check Existing Orders</h2>";

try {
    // Check total orders
    $total_orders = $db->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
    echo "<p>Total orders in database: <strong>$total_orders</strong></p>";
    
    // Check user orders
    $user_orders = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $user_orders->execute([$user_id]);
    $user_order_count = $user_orders->fetch()['count'];
    echo "<p>Orders for current user: <strong>$user_order_count</strong></p>";
    
    if ($user_order_count > 0) {
        echo "<h3>Your Recent Orders:</h3>";
        $recent_orders = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $recent_orders->execute([$user_id]);
        $orders = $recent_orders->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f2f2f2;'><th style='padding: 8px; border: 1px solid #ddd;'>Order #</th><th style='padding: 8px; border: 1px solid #ddd;'>Status</th><th style='padding: 8px; border: 1px solid #ddd;'>Amount</th><th style='padding: 8px; border: 1px solid #ddd;'>Date</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$order['order_number']}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$order['order_status']}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>₹" . number_format($order['final_amount'], 2) . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . date('M j, Y H:i', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No orders found for current user</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking orders: " . $e->getMessage() . "</p>";
}

echo "<h2>Step 6: Test Orders Page Query</h2>";

try {
    // Test the exact query from orders.php
    $orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute([$user_id]);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Orders page query result: <strong>" . count($orders) . " orders found</strong></p>";
    
    if (count($orders) > 0) {
        echo "<p>✅ Orders page query working correctly</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Orders page query returns no results</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Orders page query failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Quick Actions</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='orders.php' class='btn btn-primary'>Orders Page</a>";
echo "<a href='orders.php?debug=1' class='btn btn-secondary'>Orders (Debug)</a>";
echo "<a href='fix_orders_complete.php' class='btn btn-warning'>Complete Fix Tool</a>";
echo "<a href='checkout.php' class='btn btn-success'>Checkout</a>";
echo "<a href='cart.php' class='btn btn-info'>Cart</a>";
echo "</div>";

echo "<h2>Troubleshooting Tips</h2>";
echo "<div style='background: #cce7ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 4px;'>";
echo "<h4>If orders are still not showing:</h4>";
echo "<ol>";
echo "<li><strong>Check browser console</strong> for JavaScript errors</li>";
echo "<li><strong>Clear browser cache</strong> and cookies</li>";
echo "<li><strong>Check XAMPP error logs</strong> for PHP errors</li>";
echo "<li><strong>Verify user session</strong> - try logging out and back in</li>";
echo "<li><strong>Run the complete fix tool</strong> to ensure all tables exist</li>";
echo "<li><strong>Create test orders</strong> using the buttons above</li>";
echo "</ol>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
.btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; display: inline-block; }
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-info { background: #17a2b8; color: white; }
.btn-warning { background: #ffc107; color: black; }
.btn-secondary { background: #6c757d; color: white; }
</style>
