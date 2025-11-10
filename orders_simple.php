<?php
$page_title = 'My Orders - ClickBasket';
$mobile_title = 'My Orders';

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=orders.php');
}

$database = new Database();
$db = $database->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Simplified constant
$offset = ($page - 1) * $limit;

// Get user orders with a simpler query
try {
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        throw new Exception("User not logged in properly");
    }
    
    // Simple query without complex GROUP BY
    $orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute([$current_user_id, $limit, $offset]);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get item count for each order separately
    foreach ($orders as &$order) {
        $items_query = "SELECT COUNT(*) as count, GROUP_CONCAT(product_title SEPARATOR ', ') as titles 
                       FROM order_items WHERE order_id = ?";
        $items_stmt = $db->prepare($items_query);
        $items_stmt->execute([$order['id']]);
        $items_data = $items_stmt->fetch(PDO::FETCH_ASSOC);
        
        $order['item_count'] = $items_data['count'] ?? 0;
        $order['product_titles'] = $items_data['titles'] ?? 'No items';
    }

    // Get total orders count
    $count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute([$current_user_id]);
    $total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_orders / $limit);
    
} catch (Exception $e) {
    error_log("Orders error: " . $e->getMessage());
    $orders = [];
    $total_orders = 0;
    $total_pages = 0;
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-box"></i>
                My Orders (Simple Version)
            </h1>
            <p style="color: var(--text-secondary); margin: 0;">
                <?php echo $total_orders; ?> order<?php echo $total_orders !== 1 ? 's' : ''; ?> found
            </p>
        </div>
        <div class="d-none d-md-block">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Shop More
            </a>
        </div>
    </div>

    <!-- Debug Information -->
    <div class="card mb-4" style="background: #f0f0f0; border: 1px solid #ccc;">
        <div class="card-body">
            <h5>Debug Information:</h5>
            <p><strong>User ID:</strong> <?php echo get_current_user_id(); ?></p>
            <p><strong>Total Orders Found:</strong> <?php echo $total_orders; ?></p>
            <p><strong>Orders Array Count:</strong> <?php echo count($orders); ?></p>
            <p><strong>Current Page:</strong> <?php echo $page; ?></p>
            <p><strong>Limit:</strong> <?php echo $limit; ?></p>
            <p><strong>Offset:</strong> <?php echo $offset; ?></p>
        </div>
    </div>

    <!-- Orders List -->
    <?php if (!empty($orders)): ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                                <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                <p><strong>Status:</strong> <?php echo ucfirst($order['order_status']); ?></p>
                                <p><strong>Payment:</strong> <?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></p>
                                <p><strong>Items:</strong> <?php echo $order['item_count']; ?> item(s)</p>
                                <p><strong>Products:</strong> <?php echo htmlspecialchars($order['product_titles']); ?></p>
                            </div>
                            <div class="col-md-4 text-right">
                                <h4 style="color: var(--primary-color);">
                                    <?php echo format_currency($order['final_amount']); ?>
                                </h4>
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center" style="padding: 4rem 0;">
            <i class="fas fa-box-open" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">No Orders Yet</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">
                You haven't placed any orders yet. Start shopping to see your orders here!
            </p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag"></i>
                Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
