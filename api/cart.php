<?php
// Cart API endpoint for AJAX requests
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle different actions
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $product_id = intval($input['product_id'] ?? 0);
            $quantity = intval($input['quantity'] ?? 1);
            
            if ($product_id <= 0) {
                throw new Exception('Invalid product ID');
            }
            
            // Check if product exists
            $product_query = "SELECT id, title, price FROM products WHERE id = ? AND is_active = 1";
            $product_stmt = $db->prepare($product_query);
            $product_stmt->execute([$product_id]);
            $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception('Product not found or inactive');
            }
            
            $user_id = get_current_user_id();
            
            // Check if item already in cart
            $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$user_id, $product_id]);
            $existing_item = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_item) {
                // Update quantity
                $new_quantity = $existing_item['quantity'] + $quantity;
                $update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$new_quantity, $existing_item['id']]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cart updated successfully',
                    'action' => 'updated'
                ]);
            } else {
                // Add new item
                $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([$user_id, $product_id, $quantity]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Product added to cart successfully',
                    'action' => 'added'
                ]);
            }
            break;
            
        case 'remove':
            $product_id = intval($input['product_id'] ?? 0);
            $user_id = get_current_user_id();
            
            $delete_query = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->execute([$user_id, $product_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product removed from cart'
            ]);
            break;
            
        case 'update':
            $product_id = intval($input['product_id'] ?? 0);
            $quantity = intval($input['quantity'] ?? 1);
            $user_id = get_current_user_id();
            
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or less
                $delete_query = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->execute([$user_id, $product_id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Product removed from cart'
                ]);
            } else {
                // Update quantity
                $update_query = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$quantity, $user_id, $product_id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cart updated successfully'
                ]);
            }
            break;
            
        case 'count':
            $user_id = get_current_user_id();
            $count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
            $count_stmt = $db->prepare($count_query);
            $count_stmt->execute([$user_id]);
            $result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'count' => intval($result['total'] ?? 0)
            ]);
            break;
            
        case 'get':
            $user_id = get_current_user_id();
            $cart_query = "SELECT c.*, p.title, p.price, p.screenshots 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ? AND p.is_active = 1
                          ORDER BY c.created_at DESC";
            $cart_stmt = $db->prepare($cart_query);
            $cart_stmt->execute([$user_id]);
            $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate totals
            $subtotal = 0;
            foreach ($cart_items as &$item) {
                $item['total'] = $item['price'] * $item['quantity'];
                $subtotal += $item['total'];
                
                // Parse screenshots
                $screenshots = json_decode($item['screenshots'], true);
                $item['image'] = !empty($screenshots) ? $screenshots[0] : null;
            }
            
            echo json_encode([
                'success' => true,
                'items' => $cart_items,
                'subtotal' => $subtotal,
                'count' => count($cart_items)
            ]);
            break;
            
        case 'clear':
            $user_id = get_current_user_id();
            $clear_query = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = $db->prepare($clear_query);
            $clear_stmt->execute([$user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
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
