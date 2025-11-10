<?php
// Test script for order status filtering
require_once 'config/config.php';

if (!is_logged_in()) {
    echo "Please <a href='login.php'>login</a> first to test order status filtering.";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<h1>Order Status Filtering Test</h1>";

// Check current user orders
echo "<h2>1. Current User Orders Overview</h2>";
try {
    $overview_query = "SELECT 
                      COUNT(*) as total_orders,
                      COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending,
                      COUNT(CASE WHEN order_status = 'processing' THEN 1 END) as processing,
                      COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as completed,
                      COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled
                      FROM orders WHERE user_id = ?";
    $overview_stmt = $db->prepare($overview_query);
    $overview_stmt->execute([$user_id]);
    $overview = $overview_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Status</th><th>Count</th><th>Test Link</th></tr>";
    echo "<tr><td><strong>All Orders</strong></td><td>{$overview['total_orders']}</td><td><a href='orders.php?status=all'>Test All</a></td></tr>";
    echo "<tr><td>Pending</td><td>{$overview['pending']}</td><td><a href='orders.php?status=pending'>Test Pending</a></td></tr>";
    echo "<tr><td>Processing</td><td>{$overview['processing']}</td><td><a href='orders.php?status=processing'>Test Processing</a></td></tr>";
    echo "<tr><td>Completed</td><td>{$overview['completed']}</td><td><a href='orders.php?status=completed'>Test Completed</a></td></tr>";
    echo "<tr><td>Cancelled</td><td>{$overview['cancelled']}</td><td><a href='orders.php?status=cancelled'>Test Cancelled</a></td></tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<hr>";

// Show actual orders with details
echo "<h2>2. Detailed Order List</h2>";
try {
    $orders_query = "SELECT id, order_number, order_status, payment_status, final_amount, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute([$user_id]);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($orders)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Order #</th><th>Order Status</th><th>Payment Status</th><th>Amount</th><th>Date</th><th>Actions</th></tr>";
        
        foreach ($orders as $order) {
            $status_class = 'status-' . $order['order_status'];
            echo "<tr>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td><span class='$status_class' style='padding: 4px 8px; border-radius: 4px; font-size: 12px;'>{$order['order_status']}</span></td>";
            echo "<td>{$order['payment_status']}</td>";
            echo "<td>" . format_currency($order['final_amount']) . "</td>";
            echo "<td>" . date('M j, Y', strtotime($order['created_at'])) . "</td>";
            echo "<td>";
            echo "<button onclick='updateOrderStatus({$order['id']}, \"pending\")' style='margin: 2px; padding: 4px 8px; font-size: 11px;'>→ Pending</button>";
            echo "<button onclick='updateOrderStatus({$order['id']}, \"processing\")' style='margin: 2px; padding: 4px 8px; font-size: 11px;'>→ Processing</button>";
            echo "<button onclick='updateOrderStatus({$order['id']}, \"completed\")' style='margin: 2px; padding: 4px 8px; font-size: 11px;'>→ Completed</button>";
            echo "<button onclick='updateOrderStatus({$order['id']}, \"cancelled\")' style='margin: 2px; padding: 4px 8px; font-size: 11px;'>→ Cancelled</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No orders found for this user.</p>";
        echo "<p><a href='create_test_order.php'>Create a test order</a></p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<hr>";

// Create test orders if none exist
echo "<h2>3. Create Test Orders</h2>";

if (isset($_POST['create_test_orders'])) {
    try {
        $test_statuses = ['pending', 'processing', 'completed', 'cancelled'];
        $created_count = 0;
        
        foreach ($test_statuses as $status) {
            $order_number = 'TEST-' . strtoupper($status) . '-' . time() . '-' . rand(100, 999);
            
            $insert_query = "INSERT INTO orders (user_id, order_number, total_amount, final_amount, payment_method, order_status, payment_status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->execute([
                $user_id,
                $order_number,
                99.99,
                99.99,
                'cod',
                $status,
                $status === 'completed' ? 'completed' : 'pending'
            ]);
            
            $created_count++;
        }
        
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
        echo "✅ Created $created_count test orders with different statuses!";
        echo "<p><a href='orders.php'>View orders page</a> | <a href='test_order_status.php'>Refresh this page</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "❌ Error creating test orders: " . $e->getMessage();
        echo "</div>";
    }
}

if (empty($orders)) {
    echo "<p>No orders found. Create some test orders to test the filtering:</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='create_test_orders' value='1'>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Create Test Orders</button>";
    echo "</form>";
}

echo "<hr>";

// Test filtering functionality
echo "<h2>4. Test Status Filtering</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>";

$test_statuses = [
    'all' => 'All Orders',
    'pending' => 'Pending Orders',
    'processing' => 'Processing Orders', 
    'completed' => 'Completed Orders',
    'cancelled' => 'Cancelled Orders'
];

foreach ($test_statuses as $status => $label) {
    echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4>$label</h4>";
    echo "<a href='orders.php?status=$status' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block;'>Test Filter</a>";
    echo "</div>";
}

echo "</div>";

// Handle AJAX status updates
if (isset($_POST['update_status'])) {
    header('Content-Type: application/json');
    
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
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
function updateOrderStatus(orderId, newStatus) {
    if (confirm('Update order status to ' + newStatus + '?')) {
        fetch('test_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'update_status=1&order_id=' + orderId + '&new_status=' + newStatus
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
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
hr { margin: 20px 0; }

.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce5ff; color: #004085; }
.status-completed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }
</style>
