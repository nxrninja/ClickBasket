<?php
// Products API endpoint for mobile apps
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? PRODUCTS_PER_PAGE);
            $category_id = intval($_GET['category_id'] ?? 0);
            $search = sanitize_input($_GET['search'] ?? '');
            $offset = ($page - 1) * $limit;
            
            $where_conditions = ["p.is_active = 1"];
            $params = [];
            
            if ($category_id > 0) {
                $where_conditions[] = "p.category_id = ?";
                $params[] = $category_id;
            }
            
            if (!empty($search)) {
                $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Get products with category info
            $query = "SELECT p.*, c.name as category_name,
                     (SELECT AVG(rating) FROM ratings WHERE product_id = p.id) as avg_rating,
                     (SELECT COUNT(*) FROM ratings WHERE product_id = p.id) as rating_count
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE $where_clause 
                     ORDER BY p.created_at DESC 
                     LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process products for mobile
            foreach ($products as &$product) {
                $product['screenshots'] = json_decode($product['screenshots'], true) ?: [];
                $product['avg_rating'] = round(floatval($product['avg_rating']), 1);
                $product['rating_count'] = intval($product['rating_count']);
                $product['price'] = floatval($product['price']);
                
                // Add full image URLs
                $product['image_urls'] = [];
                foreach ($product['screenshots'] as $screenshot) {
                    $product['image_urls'][] = SITE_URL . '/' . SCREENSHOTS_DIR . $screenshot;
                }
            }
            
            // Get total count for pagination
            $count_query = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
            $count_params = array_slice($params, 0, -2); // Remove limit and offset
            $count_stmt = $db->prepare($count_query);
            $count_stmt->execute($count_params);
            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'success' => true,
                'products' => $products,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_products' => intval($total),
                    'per_page' => $limit
                ]
            ]);
            break;
            
        case 'detail':
            $product_id = intval($_GET['id'] ?? 0);
            
            if ($product_id <= 0) {
                throw new Exception('Invalid product ID');
            }
            
            $query = "SELECT p.*, c.name as category_name,
                     (SELECT AVG(rating) FROM ratings WHERE product_id = p.id) as avg_rating,
                     (SELECT COUNT(*) FROM ratings WHERE product_id = p.id) as rating_count
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.id = ? AND p.is_active = 1";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            // Process product data
            $product['screenshots'] = json_decode($product['screenshots'], true) ?: [];
            $product['avg_rating'] = round(floatval($product['avg_rating']), 1);
            $product['rating_count'] = intval($product['rating_count']);
            $product['price'] = floatval($product['price']);
            
            // Add full image URLs
            $product['image_urls'] = [];
            foreach ($product['screenshots'] as $screenshot) {
                $product['image_urls'][] = SITE_URL . '/' . SCREENSHOTS_DIR . $screenshot;
            }
            
            // Get recent reviews
            $reviews_query = "SELECT r.*, u.first_name, u.last_name 
                             FROM ratings r 
                             JOIN users u ON r.user_id = u.id 
                             WHERE r.product_id = ? 
                             ORDER BY r.created_at DESC 
                             LIMIT 5";
            $reviews_stmt = $db->prepare($reviews_query);
            $reviews_stmt->execute([$product_id]);
            $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($reviews as &$review) {
                $review['rating'] = intval($review['rating']);
                $review['user_name'] = $review['first_name'] . ' ' . $review['last_name'];
                unset($review['first_name'], $review['last_name']);
            }
            
            $product['recent_reviews'] = $reviews;
            
            echo json_encode([
                'success' => true,
                'product' => $product
            ]);
            break;
            
        case 'categories':
            $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'categories' => $categories
            ]);
            break;
            
        case 'featured':
            $limit = intval($_GET['limit'] ?? 10);
            
            $query = "SELECT p.*, c.name as category_name,
                     (SELECT AVG(rating) FROM ratings WHERE product_id = p.id) as avg_rating,
                     (SELECT COUNT(*) FROM ratings WHERE product_id = p.id) as rating_count
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE p.is_active = 1 AND p.is_featured = 1
                     ORDER BY p.created_at DESC 
                     LIMIT ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$limit]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$product) {
                $product['screenshots'] = json_decode($product['screenshots'], true) ?: [];
                $product['avg_rating'] = round(floatval($product['avg_rating']), 1);
                $product['rating_count'] = intval($product['rating_count']);
                $product['price'] = floatval($product['price']);
                
                $product['image_urls'] = [];
                foreach ($product['screenshots'] as $screenshot) {
                    $product['image_urls'][] = SITE_URL . '/' . SCREENSHOTS_DIR . $screenshot;
                }
            }
            
            echo json_encode([
                'success' => true,
                'products' => $products
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
