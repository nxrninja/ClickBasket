<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    if ($method === 'POST') {
        $action = $input['action'] ?? '';
        
        if ($action === 'add_rating') {
            // Check if user is logged in
            if (!is_logged_in()) {
                echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
                exit;
            }
            
            $product_id = intval($input['product_id'] ?? 0);
            $rating = intval($input['rating'] ?? 0);
            $review_title = trim($input['review_title'] ?? '');
            $review_text = trim($input['review_text'] ?? '');
            $user_id = get_current_user_id();
            
            // Validate input
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                exit;
            }
            
            if ($rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5 stars']);
                exit;
            }
            
            // Check if product exists
            $product_check = $db->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
            $product_check->execute([$product_id]);
            if (!$product_check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            // Check if user has already rated this product
            $existing_rating = $db->prepare("SELECT id FROM product_ratings WHERE user_id = ? AND product_id = ?");
            $existing_rating->execute([$user_id, $product_id]);
            
            if ($existing_rating->fetch()) {
                // Update existing rating
                $update_rating = $db->prepare("UPDATE product_ratings SET rating = ?, review_title = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
                $update_rating->execute([$rating, $review_title, $review_text, $user_id, $product_id]);
                $message = 'Review updated successfully!';
            } else {
                // Check if user has purchased this product (for verified purchase badge)
                $purchase_check = $db->prepare("SELECT COUNT(*) as count FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'completed'");
                $purchase_check->execute([$user_id, $product_id]);
                $is_verified = $purchase_check->fetch()['count'] > 0;
                
                // Insert new rating
                $insert_rating = $db->prepare("INSERT INTO product_ratings (product_id, user_id, rating, review_title, review_text, is_verified_purchase) VALUES (?, ?, ?, ?, ?, ?)");
                $insert_rating->execute([$product_id, $user_id, $rating, $review_title, $review_text, $is_verified]);
                $message = 'Review submitted successfully!';
            }
            
            // Update product average rating and count
            updateProductRatingStats($db, $product_id);
            
            echo json_encode(['success' => true, 'message' => $message]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'get_ratings') {
            $product_id = intval($_GET['product_id'] ?? 0);
            
            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                exit;
            }
            
            // Get rating statistics
            $stats_query = "SELECT 
                            AVG(rating) as average_rating,
                            COUNT(*) as total_reviews,
                            COUNT(CASE WHEN rating = 5 THEN 1 END) as rating_5,
                            COUNT(CASE WHEN rating = 4 THEN 1 END) as rating_4,
                            COUNT(CASE WHEN rating = 3 THEN 1 END) as rating_3,
                            COUNT(CASE WHEN rating = 2 THEN 1 END) as rating_2,
                            COUNT(CASE WHEN rating = 1 THEN 1 END) as rating_1
                            FROM product_ratings 
                            WHERE product_id = ?";
            
            $stats_stmt = $db->prepare($stats_query);
            $stats_stmt->execute([$product_id]);
            $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get individual reviews
            $reviews_query = "SELECT pr.*, u.name as user_name 
                             FROM product_ratings pr 
                             JOIN users u ON pr.user_id = u.id 
                             WHERE pr.product_id = ? 
                             ORDER BY pr.created_at DESC 
                             LIMIT 20";
            
            $reviews_stmt = $db->prepare($reviews_query);
            $reviews_stmt->execute([$product_id]);
            $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format rating statistics
            $ratings = [
                'average_rating' => round($stats['average_rating'] ?? 0, 1),
                'total_reviews' => intval($stats['total_reviews'] ?? 0),
                'rating_breakdown' => [
                    5 => intval($stats['rating_5'] ?? 0),
                    4 => intval($stats['rating_4'] ?? 0),
                    3 => intval($stats['rating_3'] ?? 0),
                    2 => intval($stats['rating_2'] ?? 0),
                    1 => intval($stats['rating_1'] ?? 0)
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'ratings' => $ratings,
                'reviews' => $reviews
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Ratings API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function updateProductRatingStats($db, $product_id) {
    $stats_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM product_ratings WHERE product_id = ?";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$product_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    $update_query = "UPDATE products SET average_rating = ?, rating_count = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([
        round($stats['avg_rating'] ?? 0, 2),
        intval($stats['count'] ?? 0),
        $product_id
    ]);
}
?>
