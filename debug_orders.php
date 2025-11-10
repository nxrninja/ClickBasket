<?php
// Debug script to check orders and user session
require_once 'config/config.php';

echo "<h1>Order Debug Information</h1>";

// Check if user is logged in
echo "<h2>User Session Check</h2>";
if (is_logged_in()) {
    $user_id = get_current_user_id();
    echo "✅ User is logged in<br>";
    echo "User ID: " . $user_id . "<br>";
    
    // Get user info
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $user_query = "SELECT * FROM users WHERE id = ?";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "User Name: " . htmlspecialchars($user['name']) . "<br>";
            echo "User Email: " . htmlspecialchars($user['email']) . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error getting user info: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ User is NOT logged in<br>";
    echo "<a href='login.php'>Login here</a><br>";
}

echo "<hr>";

// Check database connection
echo "<h2>Database Connection Check</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// Check orders table structure
echo "<h2>Orders Table Structure</h2>";
try {
    $structure_query = "DESCRIBE orders";
    $structure_stmt = $db->prepare($structure_query);
    $structure_stmt->execute();
    $columns = $structure_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Error checking orders table: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Check total orders in database
echo "<h2>Orders in Database</h2>";
try {
    $total_query = "SELECT COUNT(*) as total FROM orders";
    $total_stmt = $db->prepare($total_query);
    $total_stmt->execute();
    $total_orders = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total orders in database: " . $total_orders . "<br>";
    
    if ($total_orders > 0) {
        // Show recent orders
        $recent_query = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 5";
        $recent_stmt = $db->prepare($recent_query);
        $recent_stmt->execute();
        $recent_orders = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Recent Orders:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Order Number</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
        foreach ($recent_orders as $order) {
            echo "<tr>";
            echo "<td>" . $order['id'] . "</td>";
            echo "<td>" . $order['user_id'] . "</td>";
            echo "<td>" . $order['order_number'] . "</td>";
            echo "<td>" . $order['final_amount'] . "</td>";
            echo "<td>" . $order['order_status'] . "</td>";
            echo "<td>" . $order['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error checking orders: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Check orders for current user
if (is_logged_in()) {
    echo "<h2>Orders for Current User</h2>";
    try {
        $user_orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
        $user_orders_stmt = $db->prepare($user_orders_query);
        $user_orders_stmt->execute([get_current_user_id()]);
        $user_orders = $user_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Orders for user ID " . get_current_user_id() . ": " . count($user_orders) . "<br>";
        
        if (!empty($user_orders)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Order Number</th><th>Amount</th><th>Payment Method</th><th>Status</th><th>Created</th></tr>";
            foreach ($user_orders as $order) {
                echo "<tr>";
                echo "<td>" . $order['id'] . "</td>";
                echo "<td>" . $order['order_number'] . "</td>";
                echo "<td>" . $order['final_amount'] . "</td>";
                echo "<td>" . $order['payment_method'] . "</td>";
                echo "<td>" . $order['order_status'] . "</td>";
                echo "<td>" . $order['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No orders found for this user.<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error checking user orders: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";

// Check order_items table
echo "<h2>Order Items Check</h2>";
try {
    $items_query = "SELECT COUNT(*) as total FROM order_items";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute();
    $total_items = $items_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total order items in database: " . $total_items . "<br>";
} catch (Exception $e) {
    echo "❌ Error checking order items: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='orders.php'>Go to Orders Page</a> | <a href='orders.php?debug=1'>Orders with Debug</a></p>";
?>
