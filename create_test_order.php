<?php
// Quick test order creation for debugging
require_once 'config/config.php';

if (!is_logged_in()) {
    redirect('login.php?redirect=create_test_order.php');
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<h1>Create Test Order</h1>";

if (isset($_POST['create_order'])) {
    try {
        $status = $_POST['order_status'] ?? 'pending';
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
            1,
            "Test Product - " . ucfirst($status) . " Order",
            99.99,
            1
        ]);
        
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;'>";
        echo "<h4>✅ Order Created Successfully!</h4>";
        echo "<p><strong>Order Number:</strong> $order_number</p>";
        echo "<p><strong>Status:</strong> $status</p>";
        echo "<p><strong>Order ID:</strong> $order_id</p>";
        echo "<p><a href='orders.php'>View Orders</a> | <a href='orders.php?status=$status'>View $status Orders</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;'>";
        echo "<h4>❌ Error Creating Order</h4>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

// Check existing orders
try {
    $existing_orders = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $existing_orders->execute([$user_id]);
    $order_count = $existing_orders->fetch()['count'];
    
    echo "<h2>Current Status</h2>";
    echo "<p>You currently have <strong>$order_count</strong> orders.</p>";
    
    if ($order_count > 0) {
        $status_breakdown = $db->prepare("SELECT order_status, COUNT(*) as count FROM orders WHERE user_id = ? GROUP BY order_status");
        $status_breakdown->execute([$user_id]);
        $statuses = $status_breakdown->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        foreach ($statuses as $status) {
            echo "<tr><td>{$status['order_status']}</td><td>{$status['count']}</td></tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Error checking orders: " . $e->getMessage();
}

echo "<hr>";

echo "<h2>Create Test Order</h2>";
echo "<form method='post' style='max-width: 400px;'>";
echo "<div style='margin-bottom: 1rem;'>";
echo "<label for='order_status' style='display: block; margin-bottom: 0.5rem;'>Order Status:</label>";
echo "<select name='order_status' id='order_status' style='width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;'>";
echo "<option value='pending'>Pending</option>";
echo "<option value='processing'>Processing</option>";
echo "<option value='completed'>Completed</option>";
echo "<option value='cancelled'>Cancelled</option>";
echo "</select>";
echo "</div>";

echo "<button type='submit' name='create_order' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%;'>Create Test Order</button>";
echo "</form>";

echo "<hr>";

echo "<h2>Quick Actions</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='orders.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>View Orders</a>";
echo "<a href='orders.php?debug=1' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Debug Orders</a>";
echo "<a href='debug_orders_status.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Full Debug</a>";
echo "<a href='checkout.php' style='background: #6f42c1; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Normal Checkout</a>";
echo "</div>";

echo "<hr>";

echo "<h2>Create Multiple Test Orders</h2>";
if (isset($_POST['create_multiple'])) {
    try {
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        $created = 0;
        
        foreach ($statuses as $status) {
            for ($i = 1; $i <= 2; $i++) {
                $order_number = 'MULTI-' . strtoupper($status) . '-' . $i . '-' . time() . '-' . rand(100, 999);
                
                // Insert order
                $insert_order = $db->prepare("INSERT INTO orders (user_id, order_number, total_amount, final_amount, payment_method, order_status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_order->execute([
                    $user_id,
                    $order_number,
                    (50 + ($i * 25)),
                    (50 + ($i * 25)),
                    rand(0, 1) ? 'cod' : 'card',
                    $status,
                    $status === 'completed' ? 'completed' : 'pending'
                ]);
                
                $order_id = $db->lastInsertId();
                
                // Insert order item
                $insert_item = $db->prepare("INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) VALUES (?, ?, ?, ?, ?)");
                $insert_item->execute([
                    $order_id,
                    $i,
                    "Test Product $i - " . ucfirst($status),
                    (50 + ($i * 25)),
                    1
                ]);
                
                $created++;
            }
        }
        
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;'>";
        echo "<h4>✅ Created $created Test Orders!</h4>";
        echo "<p>Created 2 orders for each status (pending, processing, completed, cancelled)</p>";
        echo "<p><a href='orders.php'>View All Orders</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;'>";
        echo "<h4>❌ Error Creating Multiple Orders</h4>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

echo "<form method='post'>";
echo "<button type='submit' name='create_multiple' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Create 8 Test Orders (2 per status)</button>";
echo "</form>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
h1, h2 { color: #333; }
hr { margin: 20px 0; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>
