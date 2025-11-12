<?php
require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    die('Please login first');
}

$database = new Database();
$db = $database->getConnection();
$current_user_id = get_current_user_id();

echo "<h2>Order Status Debug Information</h2>";
echo "<p><strong>Current User ID:</strong> " . $current_user_id . "</p>";

// Check all orders for this user
try {
    $all_orders_query = "SELECT id, order_number, order_status, payment_status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    $all_orders_stmt = $db->prepare($all_orders_query);
    $all_orders_stmt->execute([$current_user_id]);
    $all_orders = $all_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Orders for User:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Order Number</th><th>Order Status</th><th>Payment Status</th><th>Created</th></tr>";
    
    foreach ($all_orders as $order) {
        echo "<tr>";
        echo "<td>" . $order['id'] . "</td>";
        echo "<td>" . $order['order_number'] . "</td>";
        echo "<td><strong>" . $order['order_status'] . "</strong></td>";
        echo "<td>" . $order['payment_status'] . "</td>";
        echo "<td>" . $order['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Total Orders Found:</strong> " . count($all_orders) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching orders: " . $e->getMessage() . "</p>";
}

// Get status counts using the same query as orders.php
try {
    $status_counts_query = "SELECT 
                           COUNT(*) as total_count,
                           COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_count,
                           COUNT(CASE WHEN order_status = 'processing' THEN 1 END) as processing_count,
                           COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as completed_count,
                           COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled_count
                           FROM orders WHERE user_id = ?";
    $status_counts_stmt = $db->prepare($status_counts_query);
    $status_counts_stmt->execute([$current_user_id]);
    $status_counts = $status_counts_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Status Counts (from SQL query):</h3>";
    echo "<ul>";
    echo "<li><strong>Total:</strong> " . $status_counts['total_count'] . "</li>";
    echo "<li><strong>Pending:</strong> " . $status_counts['pending_count'] . "</li>";
    echo "<li><strong>Processing:</strong> " . $status_counts['processing_count'] . "</li>";
    echo "<li><strong>Completed:</strong> " . $status_counts['completed_count'] . "</li>";
    echo "<li><strong>Cancelled:</strong> " . $status_counts['cancelled_count'] . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting status counts: " . $e->getMessage() . "</p>";
}

// Manual count verification
try {
    $manual_counts = [];
    $statuses = ['pending', 'processing', 'completed', 'cancelled'];
    
    echo "<h3>Manual Status Counts (verification):</h3>";
    echo "<ul>";
    
    foreach ($statuses as $status) {
        $count_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND order_status = ?";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute([$current_user_id, $status]);
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $manual_counts[$status] = $count;
        echo "<li><strong>" . ucfirst($status) . ":</strong> " . $count . "</li>";
    }
    
    $total_manual = array_sum($manual_counts);
    echo "<li><strong>Total (manual):</strong> " . $total_manual . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error with manual counts: " . $e->getMessage() . "</p>";
}

// Check for any data type issues
try {
    echo "<h3>Order Status Values (unique):</h3>";
    $unique_status_query = "SELECT DISTINCT order_status, COUNT(*) as count FROM orders WHERE user_id = ? GROUP BY order_status";
    $unique_status_stmt = $db->prepare($unique_status_query);
    $unique_status_stmt->execute([$current_user_id]);
    $unique_statuses = $unique_status_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($unique_statuses as $status_info) {
        echo "<li><strong>'" . $status_info['order_status'] . "'</strong> (length: " . strlen($status_info['order_status']) . ") - Count: " . $status_info['count'] . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking unique statuses: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='orders.php'>← Back to Orders</a></p>";
?>
