<?php
// Debug script for orders status issue
require_once 'config/config.php';

echo "<h1>Debug Orders Status Issue</h1>";

if (!is_logged_in()) {
    echo "❌ <strong>User not logged in</strong><br>";
    echo "<a href='login.php'>Please login first</a>";
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

echo "<h2>3. Orders Table Check</h2>";
try {
    // Check if orders table exists
    $table_check = $db->query("SHOW TABLES LIKE 'orders'")->rowCount();
    if ($table_check > 0) {
        echo "✅ Orders table exists<br>";
        
        // Check table structure
        $columns = $db->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
        echo "<details><summary>View orders table structure</summary>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table></details>";
    } else {
        echo "❌ Orders table does not exist<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error checking orders table: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>4. Total Orders in Database</h2>";
try {
    $total_orders = $db->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
    echo "Total orders in database: <strong>$total_orders</strong><br>";
    
    if ($total_orders == 0) {
        echo "❌ No orders found in database<br>";
        echo "<p><strong>This is likely the issue!</strong> There are no orders to display.</p>";
    } else {
        echo "✅ Orders exist in database<br>";
    }
} catch (Exception $e) {
    echo "❌ Error counting orders: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>5. Orders for Current User</h2>";
try {
    $user_orders = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $user_orders->execute([$user_id]);
    $user_order_count = $user_orders->fetch()['count'];
    
    echo "Orders for user $user_id: <strong>$user_order_count</strong><br>";
    
    if ($user_order_count == 0) {
        echo "❌ No orders found for current user<br>";
        echo "<p><strong>This is the issue!</strong> The current user has no orders.</p>";
    } else {
        echo "✅ User has orders<br>";
        
        // Show user's orders
        $user_orders_detail = $db->prepare("SELECT id, order_number, order_status, payment_status, final_amount, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $user_orders_detail->execute([$user_id]);
        $orders = $user_orders_detail->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr><th>ID</th><th>Order #</th><th>Status</th><th>Payment</th><th>Amount</th><th>Date</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['order_status']}</td>";
            echo "<td>{$order['payment_status']}</td>";
            echo "<td>" . format_currency($order['final_amount']) . "</td>";
            echo "<td>" . date('M j, Y H:i', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error checking user orders: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>6. Test Orders Query (Same as orders.php)</h2>";
try {
    // Test the exact query from orders.php
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $valid_statuses = ['all', 'pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($status_filter, $valid_statuses)) {
        $status_filter = 'all';
    }
    
    echo "Status filter: <strong>$status_filter</strong><br>";
    
    // Build the WHERE clause based on status filter
    $where_clause = "WHERE o.user_id = ?";
    $query_params = [$user_id];
    
    if ($status_filter !== 'all') {
        $where_clause .= " AND o.order_status = ?";
        $query_params[] = $status_filter;
    }
    
    $orders_query = "SELECT o.*, 
                     COALESCE(COUNT(oi.id), 0) as item_count,
                     COALESCE(GROUP_CONCAT(oi.product_title SEPARATOR ', '), 'No items') as product_titles
                     FROM orders o
                     LEFT JOIN order_items oi ON o.id = oi.order_id
                     $where_clause
                     GROUP BY o.id, o.user_id, o.order_number, o.total_amount, o.discount_amount, 
                              o.tax_amount, o.final_amount, o.coupon_id, o.payment_method, 
                              o.payment_status, o.transaction_id, o.payment_gateway, 
                              o.order_status, o.created_at, o.updated_at
                     ORDER BY o.created_at DESC
                     LIMIT 10 OFFSET 0";
    
    echo "<details><summary>View SQL Query</summary>";
    echo "<pre>" . htmlspecialchars($orders_query) . "</pre>";
    echo "<p>Parameters: " . implode(', ', $query_params) . "</p>";
    echo "</details>";
    
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute($query_params);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query result: <strong>" . count($orders) . " orders found</strong><br>";
    
    if (count($orders) > 0) {
        echo "✅ Query successful, orders found<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr><th>Order #</th><th>Status</th><th>Items</th><th>Amount</th><th>Product Titles</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['order_status']}</td>";
            echo "<td>{$order['item_count']}</td>";
            echo "<td>" . format_currency($order['final_amount']) . "</td>";
            echo "<td>" . htmlspecialchars($order['product_titles']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Query returned no results<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing orders query: " . $e->getMessage() . "<br>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

echo "<h2>7. Check Order Items Table</h2>";
try {
    $order_items_count = $db->query("SELECT COUNT(*) as count FROM order_items")->fetch()['count'];
    echo "Total order items: <strong>$order_items_count</strong><br>";
    
    if ($order_items_count == 0) {
        echo "⚠️ No order items found - this might affect the GROUP BY query<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking order_items: " . $e->getMessage() . "<br>";
}

echo "<hr>";

echo "<h2>8. Solutions</h2>";

if ($user_order_count == 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px;'>";
    echo "<h4>🔧 Solution: Create Test Orders</h4>";
    echo "<p>The user has no orders. Create some test orders to test the functionality:</p>";
    
    if (isset($_POST['create_orders'])) {
        try {
            $test_statuses = ['pending', 'processing', 'completed', 'cancelled'];
            $created = 0;
            
            foreach ($test_statuses as $status) {
                $order_number = 'TEST-' . strtoupper($status) . '-' . time() . '-' . rand(100, 999);
                
                // Insert order
                $insert_order = $db->prepare("INSERT INTO orders (user_id, order_number, total_amount, final_amount, payment_method, order_status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_order->execute([
                    $user_id,
                    $order_number,
                    99.99,
                    99.99,
                    'cod',
                    $status,
                    $status === 'completed' ? 'completed' : 'pending'
                ]);
                
                $order_id = $db->lastInsertId();
                
                // Insert order item
                $insert_item = $db->prepare("INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) VALUES (?, ?, ?, ?, ?)");
                $insert_item->execute([
                    $order_id,
                    1, // Assuming product ID 1 exists
                    "Test Product for $status Order",
                    99.99,
                    1
                ]);
                
                $created++;
            }
            
            echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
            echo "✅ Created $created test orders successfully!";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
            echo "❌ Error creating orders: " . $e->getMessage();
            echo "</div>";
        }
    }
    
    echo "<form method='post'>";
    echo "<input type='hidden' name='create_orders' value='1'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Create Test Orders</button>";
    echo "</form>";
    echo "</div>";
}

echo "<hr>";

echo "<h2>9. Test Links</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='orders.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Orders Page</a>";
echo "<a href='orders.php?status=all' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>All Orders</a>";
echo "<a href='orders.php?status=pending' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Pending</a>";
echo "<a href='orders.php?status=completed' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Completed</a>";
echo "<a href='checkout.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Create Order</a>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Refresh this page after creating orders to see updated results.</strong></p>";
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
