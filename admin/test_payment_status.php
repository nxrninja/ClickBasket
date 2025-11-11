<?php
// Test script to verify payment status functionality
require_once '../config/config.php';

if (!is_admin_logged_in()) {
    echo "<h1>Admin Login Required</h1>";
    echo "<p><a href='login.php'>Please login as admin</a></p>";
    exit;
}

$database = new Database();
$db = $database->getConnection();

echo "<h1>Payment Status Test</h1>";

// Test updating payment status
if (isset($_POST['test_update'])) {
    try {
        $order_id = intval($_POST['order_id']);
        $new_status = $_POST['new_status'];
        
        $update_query = "UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->execute([$new_status, $order_id]);
        
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "✅ Payment status updated successfully for Order ID: $order_id to: $new_status";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "❌ Error: " . $e->getMessage();
        echo "</div>";
    }
}

// Get sample orders
try {
    $orders_query = "SELECT id, order_number, payment_status, order_status, final_amount, created_at FROM orders ORDER BY created_at DESC LIMIT 10";
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute();
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample Orders for Testing</h2>";
    
    if (!empty($orders)) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Order #</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Current Payment Status</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Order Status</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Amount</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Test Update</th>";
        echo "</tr>";
        
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$order['order_number']}</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
            echo "<span style='padding: 4px 8px; border-radius: 12px; font-size: 0.8em; background: ";
            switch ($order['payment_status']) {
                case 'pending': echo "#fff3cd; color: #856404;"; break;
                case 'completed': echo "#d1e7dd; color: #0f5132;"; break;
                case 'failed': echo "#f8d7da; color: #842029;"; break;
                case 'refunded': echo "#e2e3ff; color: #383d41;"; break;
                default: echo "#f8f9fa; color: #495057;";
            }
            echo "'>{$order['payment_status']}</span>";
            echo "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$order['order_status']}</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>₹" . number_format($order['final_amount'], 2) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
            echo "<form method='post' style='display: inline-block;'>";
            echo "<input type='hidden' name='order_id' value='{$order['id']}'>";
            echo "<select name='new_status' style='padding: 4px; margin-right: 5px;'>";
            echo "<option value=''>Select Status</option>";
            echo "<option value='pending'" . ($order['payment_status'] === 'pending' ? ' disabled' : '') . ">Pending</option>";
            echo "<option value='completed'" . ($order['payment_status'] === 'completed' ? ' disabled' : '') . ">Completed</option>";
            echo "<option value='failed'" . ($order['payment_status'] === 'failed' ? ' disabled' : '') . ">Failed</option>";
            echo "<option value='refunded'" . ($order['payment_status'] === 'refunded' ? ' disabled' : '') . ">Refunded</option>";
            echo "</select>";
            echo "<button type='submit' name='test_update' style='padding: 4px 8px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Update</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No orders found. <a href='../fix_orders_complete.php'>Create test orders</a></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
    echo "❌ Database Error: " . $e->getMessage();
    echo "</div>";
}

// Test payment statistics
try {
    $stats_query = "SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
        SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
        SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
        SUM(CASE WHEN payment_status = 'refunded' THEN 1 ELSE 0 END) as refunded_payments
        FROM orders";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Payment Statistics</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #ddd;'>";
    echo "<h3 style='margin: 0; color: #495057;'>{$stats['total_orders']}</h3>";
    echo "<p style='margin: 5px 0 0; color: #6c757d;'>Total Orders</p>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #ffeaa7;'>";
    echo "<h3 style='margin: 0; color: #856404;'>{$stats['pending_payments']}</h3>";
    echo "<p style='margin: 5px 0 0; color: #856404;'>Pending Payments</p>";
    echo "</div>";
    
    echo "<div style='background: #d1e7dd; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #badbcc;'>";
    echo "<h3 style='margin: 0; color: #0f5132;'>{$stats['completed_payments']}</h3>";
    echo "<p style='margin: 5px 0 0; color: #0f5132;'>Completed Payments</p>";
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #f5c6cb;'>";
    echo "<h3 style='margin: 0; color: #842029;'>{$stats['failed_payments']}</h3>";
    echo "<p style='margin: 5px 0 0; color: #842029;'>Failed Payments</p>";
    echo "</div>";
    
    echo "<div style='background: #e2e3ff; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #c7c8ff;'>";
    echo "<h3 style='margin: 0; color: #383d41;'>{$stats['refunded_payments']}</h3>";
    echo "<p style='margin: 5px 0 0; color: #383d41;'>Refunded Payments</p>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
    echo "❌ Statistics Error: " . $e->getMessage();
    echo "</div>";
}

echo "<h2>Quick Links</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='orders.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Admin Orders Page</a>";
echo "<a href='orders.php?payment=pending' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Pending Payments</a>";
echo "<a href='orders.php?payment=completed' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Completed Payments</a>";
echo "<a href='orders.php?payment=failed' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Failed Payments</a>";
echo "</div>";

echo "<h2>Features Added</h2>";
echo "<div style='background: #cce7ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 4px;'>";
echo "<h4>Payment Status Management:</h4>";
echo "<ul>";
echo "<li><strong>Payment Status Filter:</strong> Filter orders by payment status (pending, completed, failed, refunded)</li>";
echo "<li><strong>Payment Status Updates:</strong> Update payment status directly from the orders table</li>";
echo "<li><strong>Payment Statistics:</strong> Dashboard shows payment status statistics</li>";
echo "<li><strong>Visual Indicators:</strong> Color-coded payment status badges</li>";
echo "<li><strong>Separate Controls:</strong> Independent order status and payment status management</li>";
echo "</ul>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2 { color: #333; }
</style>
