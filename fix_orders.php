<?php
// Quick fix script for orders not showing
require_once 'config/config.php';

echo "<h1>ClickBasket Orders Fix Tool</h1>";

// Check if user is logged in
if (!is_logged_in()) {
    echo "<div style='color: red; font-weight: bold;'>❌ You are not logged in!</div>";
    echo "<p><a href='login.php'>Please login first</a></p>";
    exit;
}

$current_user_id = get_current_user_id();
echo "<div style='color: green; font-weight: bold;'>✅ User is logged in (ID: $current_user_id)</div>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<div style='color: green;'>✅ Database connection successful</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

echo "<hr>";

// Check if orders table exists and has the right structure
echo "<h2>1. Checking Orders Table</h2>";
try {
    $check_table = $db->query("SHOW TABLES LIKE 'orders'");
    if ($check_table->rowCount() > 0) {
        echo "✅ Orders table exists<br>";
        
        // Check table structure
        $structure = $db->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
        echo "<details><summary>View table structure</summary>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($structure as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table></details>";
    } else {
        echo "❌ Orders table does not exist!<br>";
        echo "<p>You need to create the database tables first.</p>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error checking orders table: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Check total orders in database
echo "<h2>2. Checking Orders Data</h2>";
try {
    $total_orders_query = $db->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $total_orders_query->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total orders in database: <strong>$total_orders</strong><br>";
    
    if ($total_orders > 0) {
        echo "✅ Orders exist in database<br>";
        
        // Show recent orders
        $recent_orders = $db->query("SELECT id, user_id, order_number, final_amount, order_status, created_at FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Recent Orders:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Order Number</th><th>Amount</th><th>Status</th><th>Date</th></tr>";
        foreach ($recent_orders as $order) {
            $highlight = ($order['user_id'] == $current_user_id) ? "style='background-color: yellow;'" : "";
            echo "<tr $highlight>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['user_id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['final_amount']}</td>";
            echo "<td>{$order['order_status']}</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><em>Your orders are highlighted in yellow</em></p>";
    } else {
        echo "❌ No orders found in database<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking orders: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Check orders for current user
echo "<h2>3. Checking Your Orders</h2>";
try {
    $user_orders_query = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $user_orders_query->execute([$current_user_id]);
    $user_orders = $user_orders_query->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Orders for your account (User ID $current_user_id): <strong>" . count($user_orders) . "</strong><br>";
    
    if (count($user_orders) > 0) {
        echo "✅ You have orders!<br>";
        foreach ($user_orders as $order) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
            echo "<strong>Order #{$order['order_number']}</strong><br>";
            echo "Amount: {$order['final_amount']}<br>";
            echo "Status: {$order['order_status']}<br>";
            echo "Payment: {$order['payment_method']}<br>";
            echo "Date: {$order['created_at']}<br>";
            echo "</div>";
        }
    } else {
        echo "❌ No orders found for your account<br>";
        echo "<p><strong>This is why the orders page shows 'No Orders Yet'</strong></p>";
    }
} catch (Exception $e) {
    echo "❌ Error checking your orders: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Check if order_items table exists
echo "<h2>4. Checking Order Items</h2>";
try {
    $check_items_table = $db->query("SHOW TABLES LIKE 'order_items'");
    if ($check_items_table->rowCount() > 0) {
        echo "✅ Order items table exists<br>";
        
        $total_items = $db->query("SELECT COUNT(*) as total FROM order_items")->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Total order items: <strong>$total_items</strong><br>";
    } else {
        echo "❌ Order items table does not exist<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking order items: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Provide solutions
echo "<h2>5. Solutions</h2>";

if (count($user_orders ?? []) == 0) {
    echo "<div style='background: #fffbf0; border: 1px solid #f0ad4e; padding: 15px; margin: 10px 0;'>";
    echo "<h3>🔧 Fix Options:</h3>";
    
    echo "<h4>Option 1: Create a Test Order</h4>";
    echo "<form method='post' style='margin: 10px 0;'>";
    echo "<input type='hidden' name='action' value='create_test_order'>";
    echo "<button type='submit' style='background: #5cb85c; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Create Test Order</button>";
    echo "</form>";
    
    echo "<h4>Option 2: Place a Real Order</h4>";
    echo "<ol>";
    echo "<li><a href='products.php'>Go to Products Page</a></li>";
    echo "<li>Add items to cart</li>";
    echo "<li><a href='checkout.php'>Go to Checkout</a></li>";
    echo "<li>Complete the order process</li>";
    echo "</ol>";
    
    echo "<h4>Option 3: Check Database Setup</h4>";
    echo "<p>If you just set up the system, make sure to:</p>";
    echo "<ul>";
    echo "<li><a href='setup_billing_table.php'>Run the billing table setup</a></li>";
    echo "<li>Ensure all database tables are created properly</li>";
    echo "</ul>";
    
    echo "</div>";
}

// Handle test order creation
if ($_POST['action'] ?? '' === 'create_test_order') {
    try {
        $db->beginTransaction();
        
        $order_number = 'TEST' . date('Ymd') . rand(1000, 9999);
        
        $insert_order = $db->prepare("INSERT INTO orders (user_id, order_number, total_amount, final_amount, payment_method, order_status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert_order->execute([$current_user_id, $order_number, 99.99, 99.99, 'cod', 'pending', 'pending']);
        
        $order_id = $db->lastInsertId();
        
        // Add test order item
        $insert_item = $db->prepare("INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) VALUES (?, ?, ?, ?, ?)");
        $insert_item->execute([$order_id, 1, 'Test Product', 99.99, 1]);
        
        $db->commit();
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0;'>";
        echo "✅ <strong>Test order created successfully!</strong><br>";
        echo "Order Number: $order_number<br>";
        echo "<a href='orders.php'>View Your Orders Now</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        $db->rollBack();
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0;'>";
        echo "❌ <strong>Failed to create test order:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>6. Quick Links</h2>";
echo "<p>";
echo "<a href='orders.php' style='margin-right: 10px;'>📋 View Orders</a>";
echo "<a href='orders.php?debug=1' style='margin-right: 10px;'>🔍 Orders with Debug</a>";
echo "<a href='products.php' style='margin-right: 10px;'>🛍️ Shop Products</a>";
echo "<a href='checkout.php' style='margin-right: 10px;'>💳 Checkout</a>";
echo "</p>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
details { margin: 10px 0; }
hr { margin: 20px 0; }
</style>
