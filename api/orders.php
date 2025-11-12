<?php
// Orders API endpoint for AJAX requests
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to manage orders']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle different actions
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'cancel':
            $order_id = intval($input['order_id'] ?? 0);
            $user_id = get_current_user_id();
            
            if ($order_id <= 0) {
                throw new Exception('Invalid order ID');
            }
            
            // Check if order exists and belongs to current user
            $check_query = "SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$order_id, $user_id]);
            $order = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Order not found or access denied');
            }
            
            // Check if order can be cancelled
            if ($order['order_status'] !== 'pending') {
                throw new Exception('This order cannot be cancelled. Only pending orders can be cancelled.');
            }
            
            // Update order status to cancelled
            $cancel_query = "UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?";
            $cancel_stmt = $db->prepare($cancel_query);
            $cancel_stmt->execute([$order_id, $user_id]);
            
            if ($cancel_stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Order cancelled successfully'
                ]);
            } else {
                throw new Exception('Failed to cancel order');
            }
            break;
            
        case 'get':
            $order_id = intval($_GET['id'] ?? 0);
            $user_id = get_current_user_id();
            
            if ($order_id <= 0) {
                throw new Exception('Invalid order ID');
            }
            
            // Get order details
            $order_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
            $order_stmt = $db->prepare($order_query);
            $order_stmt->execute([$order_id, $user_id]);
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Get order items
            $items_query = "SELECT oi.*, p.title, p.screenshots 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = ?";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->execute([$order_id]);
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get billing information if exists
            $billing_query = "SELECT * FROM order_billing WHERE order_id = ?";
            $billing_stmt = $db->prepare($billing_query);
            $billing_stmt->execute([$order_id]);
            $billing = $billing_stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items,
                'billing' => $billing
            ]);
            break;
            
        case 'list':
            $user_id = get_current_user_id();
            $page = intval($_GET['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
            $count_stmt = $db->prepare($count_query);
            $count_stmt->execute([$user_id]);
            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get orders with pagination
            $orders_query = "SELECT o.*, 
                            COUNT(oi.id) as item_count,
                            GROUP_CONCAT(oi.product_title SEPARATOR ', ') as product_titles
                            FROM orders o
                            LEFT JOIN order_items oi ON o.id = oi.order_id
                            WHERE o.user_id = ?
                            GROUP BY o.id
                            ORDER BY o.created_at DESC
                            LIMIT ? OFFSET ?";
            $orders_stmt = $db->prepare($orders_query);
            $orders_stmt->execute([$user_id, $limit, $offset]);
            $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'orders' => $orders,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_orders' => $total
                ]
            ]);
            break;
            
        case 'create':
            // This would handle order creation, but typically done through checkout
            throw new Exception('Order creation should be done through checkout process');
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
