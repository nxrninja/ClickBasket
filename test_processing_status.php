<?php
// Test script specifically for processing status issue
require_once 'config/config.php';

if (!is_logged_in()) {
    echo "Please <a href='login.php'>login</a> first.";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<h1>Test Processing Status Filter</h1>";

// Check what status parameter is being received
$status_param = $_GET['status'] ?? 'not_set';
echo "<h2>1. URL Parameter Check</h2>";
echo "<p><strong>Status Parameter:</strong> '$status_param'</p>";
echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<hr>";

// Check all orders for current user
echo "<h2>2. All User Orders</h2>";
try {
    $all_orders = $db->prepare("SELECT id, order_number, order_status, payment_status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $all_orders->execute([$user_id]);
    $orders = $all_orders->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<p style='color: red;'>❌ <strong>No orders found for user!</strong></p>";
        echo "<p>This is why the status filter isn't working - there are no orders to filter.</p>";
        
        // Create a processing order
        if (isset($_POST['create_processing'])) {
            try {
                $order_number = 'PROC-' . time() . '-' . rand(100, 999);
                
                $insert_order = $db->prepare("INSERT INTO orders (user_id, order_number, total_amount, final_amount, payment_method, order_status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_order->execute([
                    $user_id,
                    $order_number,
                    99.99,
                    99.99,
                    'cod',
                    'processing',
                    'pending'
                ]);
                
                $order_id = $db->lastInsertId();
                
                // Insert order item
                $insert_item = $db->prepare("INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) VALUES (?, ?, ?, ?, ?)");
                $insert_item->execute([
                    $order_id,
                    1,
                    "Test Processing Order Product",
                    99.99,
                    1
                ]);
                
                echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
                echo "✅ Created processing order: $order_number";
                echo "</div>";
                
                // Refresh to show the new order
                echo "<script>setTimeout(() => location.reload(), 1000);</script>";
                
            } catch (Exception $e) {
                echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
                echo "❌ Error: " . $e->getMessage();
                echo "</div>";
            }
        }
        
        echo "<form method='post'>";
        echo "<button type='submit' name='create_processing' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Create Processing Order</button>";
        echo "</form>";
        
    } else {
        echo "<p>✅ Found " . count($orders) . " orders</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr><th>Order #</th><th>Status</th><th>Payment</th><th>Date</th><th>Actions</th></tr>";
        
        foreach ($orders as $order) {
            $row_style = $order['order_status'] === 'processing' ? 'background: #e7f3ff;' : '';
            echo "<tr style='$row_style'>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td><strong>{$order['order_status']}</strong></td>";
            echo "<td>{$order['payment_status']}</td>";
            echo "<td>" . date('M j, Y H:i', strtotime($order['created_at'])) . "</td>";
            echo "<td>";
            if ($order['order_status'] !== 'processing') {
                echo "<button onclick='changeStatus({$order['id']}, \"processing\")' style='background: #28a745; color: white; padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;'>→ Processing</button>";
            } else {
                echo "<span style='color: #28a745;'>✅ Processing</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test the exact query used in orders.php for processing status
echo "<h2>3. Test Processing Filter Query</h2>";
try {
    $status_filter = 'processing';
    $where_clause = "WHERE o.user_id = ? AND o.order_status = ?";
    $query_params = [$user_id, $status_filter];
    
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
                     ORDER BY o.created_at DESC";
    
    echo "<details><summary>View SQL Query</summary>";
    echo "<pre>" . htmlspecialchars($orders_query) . "</pre>";
    echo "<p>Parameters: " . implode(', ', $query_params) . "</p>";
    echo "</details>";
    
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute($query_params);
    $processing_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Processing orders found:</strong> " . count($processing_orders) . "</p>";
    
    if (count($processing_orders) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Order #</th><th>Status</th><th>Items</th><th>Product Titles</th></tr>";
        foreach ($processing_orders as $order) {
            echo "<tr>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['order_status']}</td>";
            echo "<td>{$order['item_count']}</td>";
            echo "<td>" . htmlspecialchars($order['product_titles']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No processing orders found with the filter query</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Query Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test status counts
echo "<h2>4. Status Counts Test</h2>";
try {
    $status_counts_query = "SELECT 
                           COUNT(*) as total_count,
                           COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_count,
                           COUNT(CASE WHEN order_status = 'processing' THEN 1 END) as processing_count,
                           COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as completed_count,
                           COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled_count
                           FROM orders WHERE user_id = ?";
    $status_counts_stmt = $db->prepare($status_counts_query);
    $status_counts_stmt->execute([$user_id]);
    $status_counts = $status_counts_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Status</th><th>Count</th><th>Test Link</th></tr>";
    echo "<tr><td>All</td><td>{$status_counts['total_count']}</td><td><a href='orders.php?status=all'>Test</a></td></tr>";
    echo "<tr><td>Pending</td><td>{$status_counts['pending_count']}</td><td><a href='orders.php?status=pending'>Test</a></td></tr>";
    echo "<tr style='background: #e7f3ff;'><td><strong>Processing</strong></td><td><strong>{$status_counts['processing_count']}</strong></td><td><a href='orders.php?status=processing'><strong>Test Processing</strong></a></td></tr>";
    echo "<tr><td>Completed</td><td>{$status_counts['completed_count']}</td><td><a href='orders.php?status=completed'>Test</a></td></tr>";
    echo "<tr><td>Cancelled</td><td>{$status_counts['cancelled_count']}</td><td><a href='orders.php?status=cancelled'>Test</a></td></tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Status counts error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h2>5. Quick Actions</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='orders.php?status=processing' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>🔄 Test Processing Filter</a>";
echo "<a href='orders.php?status=all' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>📋 All Orders</a>";
echo "<a href='create_test_order.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>➕ Create Orders</a>";
echo "<a href='orders.php?debug=1' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>🐛 Debug Mode</a>";
echo "</div>";

// Handle status change via AJAX
if (isset($_POST['change_status'])) {
    header('Content-Type: application/json');
    
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    try {
        $update_query = "UPDATE orders SET order_status = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([$new_status, $order_id, $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
?>

<script>
function changeStatus(orderId, newStatus) {
    if (confirm('Change order status to ' + newStatus + '?')) {
        fetch('test_processing_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'change_status=1&order_id=' + orderId + '&new_status=' + newStatus
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Status updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Network error: ' + error);
        });
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2 { color: #333; }
hr { margin: 20px 0; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
details { margin: 10px 0; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>
