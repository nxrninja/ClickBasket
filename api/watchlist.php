<?php
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to manage your watch list']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $product_id = intval($input['product_id'] ?? 0);

        if (!$product_id) {
            throw new Exception('Invalid product ID');
        }

        // Verify product exists
        $product_check = $db->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
        $product_check->execute([$product_id]);
        if (!$product_check->fetch()) {
            throw new Exception('Product not found');
        }

        switch ($action) {
            case 'add':
                // Check if already in watchlist
                $check_query = "SELECT id FROM watchlist WHERE user_id = ? AND product_id = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$user_id, $product_id]);
                
                if ($check_stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Product already in watchlist']);
                    exit;
                }

                // Add to watchlist
                $insert_query = "INSERT INTO watchlist (user_id, product_id, created_at) VALUES (?, ?, NOW())";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([$user_id, $product_id]);

                echo json_encode(['success' => true, 'message' => 'Product added to watch list']);
                break;

            case 'remove':
                $delete_query = "DELETE FROM watchlist WHERE user_id = ? AND product_id = ?";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->execute([$user_id, $product_id]);

                echo json_encode(['success' => true, 'message' => 'Product removed from watch list']);
                break;

            default:
                throw new Exception('Invalid action');
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        $product_id = intval($_GET['product_id'] ?? 0);

        switch ($action) {
            case 'check':
                if (!$product_id) {
                    throw new Exception('Invalid product ID');
                }

                $check_query = "SELECT id FROM watchlist WHERE user_id = ? AND product_id = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$user_id, $product_id]);
                
                $in_watchlist = $check_stmt->fetch() ? true : false;
                echo json_encode(['success' => true, 'in_watchlist' => $in_watchlist]);
                break;

            case 'list':
                $page = max(1, intval($_GET['page'] ?? 1));
                $limit = 20;
                $offset = ($page - 1) * $limit;

                // Get watchlist items with product details
                $list_query = "SELECT w.*, p.title, p.price, p.screenshots, p.short_description, p.slug,
                                     c.name as category_name
                              FROM watchlist w
                              JOIN products p ON w.product_id = p.id
                              LEFT JOIN categories c ON p.category_id = c.id
                              WHERE w.user_id = ? AND p.is_active = 1
                              ORDER BY w.created_at DESC
                              LIMIT ? OFFSET ?";
                $list_stmt = $db->prepare($list_query);
                $list_stmt->execute([$user_id, $limit, $offset]);
                $items = $list_stmt->fetchAll(PDO::FETCH_ASSOC);

                // Get total count
                $count_query = "SELECT COUNT(*) as total FROM watchlist w 
                               JOIN products p ON w.product_id = p.id 
                               WHERE w.user_id = ? AND p.is_active = 1";
                $count_stmt = $db->prepare($count_query);
                $count_stmt->execute([$user_id]);
                $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

                echo json_encode([
                    'success' => true,
                    'items' => $items,
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / $limit)
                ]);
                break;

            case 'count':
                $count_query = "SELECT COUNT(*) as total FROM watchlist w 
                               JOIN products p ON w.product_id = p.id 
                               WHERE w.user_id = ? AND p.is_active = 1";
                $count_stmt = $db->prepare($count_query);
                $count_stmt->execute([$user_id]);
                $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

                echo json_encode(['success' => true, 'count' => $count]);
                break;

            default:
                throw new Exception('Invalid action');
        }

    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
