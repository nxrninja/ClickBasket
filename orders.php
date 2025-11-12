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

// Handle test order creation
if (isset($_POST['create_test_orders'])) {
    try {
        $user_id = get_current_user_id();
        $test_statuses = ['pending', 'processing', 'completed'];
        $created = 0;
        
        foreach ($test_statuses as $status) {
            $order_number = 'TEST-' . strtoupper($status) . '-' . time() . '-' . rand(100, 999);
            
            // Insert order
            $insert_order = $db->prepare("INSERT INTO orders (user_id, order_number, total_amount, final_amount, payment_method, order_status, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $insert_order->execute([
                $user_id,
                $order_number,
                99.99,
                99.99,
                'cod',
                $status,
                $status === 'completed' ? 'completed' : 'pending'
            ]);
            
            $order_id = $db->lastInsertId();
            
            // Insert order item
            $insert_item = $db->prepare("INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) VALUES (?, ?, ?, ?, ?)");
            $insert_item->execute([
                $order_id,
                1,
                "Test Product for " . ucfirst($status) . " Order",
                99.99,
                1
            ]);
            
            $created++;
        }
        
        $_SESSION['success'] = "Created $created test orders successfully!";
        redirect('orders.php');
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error creating test orders: " . $e->getMessage();
    }
}

// Pagination - ensure integers for LIMIT/OFFSET
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$limit = max(1, (int)ORDERS_PER_PAGE);
$offset = max(0, ($page - 1) * $limit);

// Status filtering
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['all', 'pending', 'processing', 'completed', 'cancelled'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// Get user orders with improved error handling
$current_user_id = get_current_user_id();
$orders = [];
$total_orders = 0;
$total_pages = 0;
$debug_info = [];

try {
    if (!$current_user_id) {
        throw new Exception("User not logged in properly");
    }
    
    $debug_info[] = "User ID: " . $current_user_id;
    $debug_info[] = "Status Filter: " . $status_filter;
    $debug_info[] = "Page: $page, Limit: $limit, Offset: $offset";
    
    // First, get orders without complex GROUP BY
    $where_clause = "WHERE user_id = ?";
    $query_params = [$current_user_id];
    
    if ($status_filter !== 'all') {
        $where_clause .= " AND order_status = ?";
        $query_params[] = $status_filter;
    }
    
    // Simple orders query (LIMIT and OFFSET cannot be parameterized in MySQL/MariaDB)
    $orders_query = "SELECT * FROM orders $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    
    $debug_info[] = "SQL Query: " . $orders_query;
    $debug_info[] = "Query Params: " . json_encode($query_params);
    
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute($query_params);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug_info[] = "Orders found: " . count($orders);
    
    // Get item count and product titles for each order separately
    foreach ($orders as &$order) {
        try {
            $items_query = "SELECT COUNT(*) as item_count, GROUP_CONCAT(product_title SEPARATOR ', ') as product_titles FROM order_items WHERE order_id = ?";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->execute([$order['id']]);
            $items_data = $items_stmt->fetch(PDO::FETCH_ASSOC);
            
            $order['item_count'] = $items_data['item_count'] ?? 0;
            $order['product_titles'] = $items_data['product_titles'] ?? 'No items';
        } catch (Exception $e) {
            $order['item_count'] = 0;
            $order['product_titles'] = 'Error loading items';
        }
    }
    
    // Get total count
    $count_params = [$current_user_id];
    if ($status_filter !== 'all') {
        $count_params[] = $status_filter;
    }
    
    $count_query = "SELECT COUNT(*) as total FROM orders " . str_replace('LIMIT ? OFFSET ?', '', $where_clause);
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($count_params);
    $total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_orders / $limit);
    
    $debug_info[] = "Total orders: " . $total_orders;
    
} catch (Exception $e) {
    $debug_info[] = "ERROR: " . $e->getMessage();
    error_log("Orders.php error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading orders: " . $e->getMessage();
}

// Get status counts for filter buttons
$status_counts = [
    'total_count' => 0,
    'pending_count' => 0,
    'processing_count' => 0,
    'completed_count' => 0,
    'cancelled_count' => 0
];

try {
    if ($current_user_id) {
        $status_counts_query = "SELECT 
                               COUNT(*) as total_count,
                               COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_count,
                               COUNT(CASE WHEN order_status = 'processing' THEN 1 END) as processing_count,
                               COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as completed_count,
                               COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled_count
                               FROM orders WHERE user_id = ?";
        $status_counts_stmt = $db->prepare($status_counts_query);
        $status_counts_stmt->execute([$current_user_id]);
        $db_status_counts = $status_counts_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Merge with default values to ensure all keys exist
        if ($db_status_counts) {
            // Ensure all count values are integers, not null
            foreach ($db_status_counts as $key => $value) {
                $db_status_counts[$key] = (int)$value;
            }
            $status_counts = array_merge($status_counts, $db_status_counts);
        }
    }
} catch (Exception $e) {
    $debug_info[] = "Status counts error: " . $e->getMessage();
}

include 'includes/header.php';

// Show flash messages
$success_message = get_flash_message('success');
$error_message = get_flash_message('error');

// Get checkout debugging info
$checkout_error = $_SESSION['checkout_error'] ?? null;
$last_order = $_SESSION['last_order'] ?? null;

// Clear checkout debugging info after displaying
if (isset($_SESSION['checkout_error'])) {
    unset($_SESSION['checkout_error']);
}
if (isset($_SESSION['last_order']) && isset($_GET['clear_debug'])) {
    unset($_SESSION['last_order']);
}

// Debug status counts (remove this after fixing)
if (isset($_GET['debug_status'])) {
    echo "<div class='container'><div class='alert alert-info'>";
    echo "<h4>Debug Status Counts:</h4>";
    echo "<pre>";
    print_r($status_counts);
    echo "</pre>";
    echo "<p><strong>User ID:</strong> " . $current_user_id . "</p>";
    echo "<p><a href='debug_order_status.php' target='_blank'>Full Debug Report</a></p>";
    echo "</div></div>";
}
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Flash Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Checkout Debug Information -->
    <?php if ($checkout_error): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h5><i class="fas fa-exclamation-triangle"></i> Checkout Error Detected</h5>
            <p><strong>Error:</strong> <?php echo htmlspecialchars($checkout_error['message']); ?></p>
            <p><strong>Time:</strong> <?php echo htmlspecialchars($checkout_error['timestamp']); ?></p>
            <details>
                <summary>Technical Details</summary>
                <pre style="font-size: 0.8em; margin-top: 10px;"><?php echo htmlspecialchars($checkout_error['trace']); ?></pre>
            </details>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($last_order): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <h5><i class="fas fa-info-circle"></i> Last Order Information</h5>
            <p><strong>Order ID:</strong> <?php echo htmlspecialchars($last_order['id']); ?></p>
            <p><strong>Order Number:</strong> <?php echo htmlspecialchars($last_order['number']); ?></p>
            <p><strong>Amount:</strong> ₹<?php echo number_format($last_order['amount'], 2); ?></p>
            <p><strong>Created:</strong> <?php echo htmlspecialchars($last_order['timestamp']); ?></p>
            <p><em>If this order is not showing in the list below, there might be a database or query issue.</em></p>
            <a href="?clear_debug=1" class="btn btn-sm btn-secondary">Clear Debug Info</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Page Header -->
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-box"></i>
                My Orders
            </h1>
            <p style="color: var(--text-secondary); margin: 0;">
                <?php echo $total_orders; ?> 
                <?php if ($status_filter !== 'all'): ?>
                    <?php echo ucfirst($status_filter); ?>
                <?php endif; ?>
                order<?php echo $total_orders !== 1 ? 's' : ''; ?> found
            </p>
        </div>
        <div class="d-none d-md-block">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Shop More
            </a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="card mb-4">
        <div class="card-body" style="padding: 1rem;">
            <div style="display: flex; gap: 0.5rem; overflow-x: auto;">
                <a href="?status=all" class="btn btn-sm <?php echo (!isset($_GET['status']) || $_GET['status'] === 'all') ? 'btn-primary' : 'btn-secondary'; ?>">
                    All Orders
                    <span class="badge" style="background: rgba(255,255,255,0.3); margin-left: 0.5rem;"><?php echo $status_counts['total_count']; ?></span>
                </a>
                <a href="?status=pending" class="btn btn-sm <?php echo ($_GET['status'] ?? '') === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Pending
                    <span class="badge" style="background: rgba(255,255,255,0.3); margin-left: 0.5rem;"><?php echo $status_counts['pending_count']; ?></span>
                </a>
                <a href="?status=processing" class="btn btn-sm <?php echo ($_GET['status'] ?? '') === 'processing' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Processing
                    <span class="badge" style="background: rgba(255,255,255,0.3); margin-left: 0.5rem;"><?php echo $status_counts['processing_count']; ?></span>
                </a>
                <a href="?status=completed" class="btn btn-sm <?php echo ($_GET['status'] ?? '') === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Completed
                    <span class="badge" style="background: rgba(255,255,255,0.3); margin-left: 0.5rem;"><?php echo $status_counts['completed_count']; ?></span>
                </a>
                <a href="?status=cancelled" class="btn btn-sm <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Cancelled
                    <span class="badge" style="background: rgba(255,255,255,0.3); margin-left: 0.5rem;"><?php echo $status_counts['cancelled_count']; ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Debug Information (Remove in production) -->
    <?php if (isset($_GET['debug'])): ?>
        <div class="card mb-4" style="background: #f0f0f0; border: 1px solid #ccc;">
            <div class="card-body">
                <h5>Debug Information:</h5>
                <p><strong>User ID:</strong> <?php echo get_current_user_id(); ?></p>
                <p><strong>Total Orders Found:</strong> <?php echo $total_orders; ?></p>
                <p><strong>Orders Array Count:</strong> <?php echo count($orders); ?></p>
                <p><strong>Limit:</strong> <?php echo $limit; ?></p>
                <p><strong>Offset:</strong> <?php echo $offset; ?></p>
                <?php 
                // Check total orders in database
                try {
                    $all_orders_query = "SELECT COUNT(*) as total FROM orders";
                    $all_orders_stmt = $db->prepare($all_orders_query);
                    $all_orders_stmt->execute();
                    $all_orders_count = $all_orders_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    echo "<p><strong>Total Orders in Database:</strong> " . $all_orders_count . "</p>";
                } catch (Exception $e) {
                    echo "<p><strong>Database Error:</strong> " . $e->getMessage() . "</p>";
                }
                ?>
                <?php if (!empty($orders)): ?>
                    <p><strong>Sample Order:</strong></p>
                    <pre><?php print_r($orders[0]); ?></pre>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Orders List -->
    <?php if (!empty($orders)): ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="card mb-3 order-card fade-in">
                    <div class="card-body">
                        <div class="row align-center">
                            <!-- Order Info -->
                            <div class="col-md-8">
                                <div class="d-flex align-center mb-2">
                                    <h5 style="color: var(--text-primary); margin: 0 1rem 0 0;">
                                        Order #<?php echo htmlspecialchars($order['order_number']); ?>
                                    </h5>
                                    <span class="order-status status-<?php echo $order['order_status']; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                    <span class="payment-status status-<?php echo $order['payment_status']; ?>" style="margin-left: 0.5rem;">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                                
                                <p style="color: var(--text-secondary); margin-bottom: 0.5rem; font-size: 0.875rem;">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                </p>
                                
                                <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.875rem;">
                                    <i class="fas fa-box"></i>
                                    <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] !== '1' ? 's' : ''; ?>:
                                    <?php echo htmlspecialchars(strlen($order['product_titles']) > 60 ? substr($order['product_titles'], 0, 60) . '...' : $order['product_titles']); ?>
                                </p>

                                <div class="order-amounts" style="font-size: 0.875rem;">
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <span style="color: var(--text-muted);">
                                            Subtotal: <?php echo format_currency($order['total_amount']); ?>
                                        </span>
                                        <span style="color: var(--success-color); margin-left: 1rem;">
                                            Discount: -<?php echo format_currency($order['discount_amount']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($order['tax_amount'] > 0): ?>
                                        <span style="color: var(--text-muted); margin-left: 1rem;">
                                            Tax: <?php echo format_currency($order['tax_amount']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Order Total & Actions -->
                            <div class="col-md-4 text-center text-md-right">
                                <div class="order-total mb-3">
                                    <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                                        <?php echo format_currency($order['final_amount']); ?>
                                    </h4>
                                    <?php if ($order['payment_method']): ?>
                                        <small style="color: var(--text-muted);">
                                            via <?php echo ucfirst($order['payment_method']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-actions" style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                    
                                    
                                    <?php if ($order['order_status'] === 'pending'): ?>
                                        <button class="btn btn-danger btn-sm" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                            Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="text-center mt-4">
                <nav>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" 
                               class="btn btn-secondary btn-sm">
                                <i class="fas fa-chevron-left"></i>
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?>" 
                               class="btn btn-secondary btn-sm">
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Enhanced No Orders State -->
        <div class="no-orders-container">
            <div class="no-orders-content">
                <!-- Icon and Animation -->
                <div class="no-orders-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="floating-elements">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-heart"></i>
                        <i class="fas fa-gift"></i>
                    </div>
                </div>
                
                <!-- Main Message -->
                <div class="no-orders-message">
                    <h2>
                        <?php if ($status_filter !== 'all'): ?>
                            No <?php echo ucfirst($status_filter); ?> Orders Found
                        <?php else: ?>
                            Your Shopping Journey Starts Here!
                        <?php endif; ?>
                    </h2>
                    
                    <p class="lead">
                        <?php if ($status_filter !== 'all'): ?>
                            You don't have any <?php echo $status_filter; ?> orders at the moment.
                            <?php if ($status_filter === 'pending'): ?>
                                All your orders have been processed successfully!
                            <?php elseif ($status_filter === 'completed'): ?>
                                Complete an order to see it here.
                            <?php elseif ($status_filter === 'cancelled'): ?>
                                Great! You haven't cancelled any orders.
                            <?php endif; ?>
                        <?php else: ?>
                            Discover amazing products and create your first order to get started with ClickBasket!
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Test Orders Section (for development) -->
                <?php if ($status_filter === 'all' && $status_counts['total_count'] == 0): ?>
                    <div class="test-orders-section">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> For Testing Purposes</h5>
                            <p>If you're testing the orders functionality, you can create some sample orders to see how the page works.</p>
                            <form method="post" style="margin-top: 1rem;">
                                <button type="submit" name="create_test_orders" class="btn btn-warning">
                                    <i class="fas fa-plus"></i>
                                    Create Test Orders
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Stats -->
                <?php if ($status_filter === 'all' && $status_counts['total_count'] == 0): ?>
                    <div class="quick-stats">
                        <div class="stat-item">
                            <i class="fas fa-truck"></i>
                            <span>Fast Delivery</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Payment</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-headset"></i>
                            <span>24/7 Support</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="no-orders-actions">
                    <?php if ($status_filter !== 'all'): ?>
                        <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-list"></i>
                            View All Orders
                        </a>
                        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-shopping-bag"></i>
                            Continue Shopping
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg pulse">
                            <i class="fas fa-shopping-bag"></i>
                            Start Shopping Now
                        </a>
                        <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-home"></i>
                            Browse Homepage
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Helpful Links -->
                <div class="helpful-links">
                    <h4>Popular Categories</h4>
                    <div class="category-links">
                        <?php
                        // Get popular categories
                        try {
                            $categories_query = "SELECT c.*, COUNT(p.id) as product_count 
                                               FROM categories c 
                                               LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                                               WHERE c.status = 'active' 
                                               GROUP BY c.id 
                                               ORDER BY product_count DESC, c.name ASC 
                                               LIMIT 6";
                            $categories_stmt = $db->prepare($categories_query);
                            $categories_stmt->execute();
                            $popular_categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($popular_categories as $category):
                        ?>
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['id']; ?>" class="category-link">
                                <i class="fas fa-<?php echo $category['icon'] ?? 'tag'; ?>"></i>
                                <?php echo htmlspecialchars($category['name']); ?>
                                <span class="product-count">(<?php echo $category['product_count']; ?>)</span>
                            </a>
                        <?php 
                            endforeach;
                        } catch (Exception $e) {
                            // Fallback categories if query fails
                            $fallback_categories = [
                                ['name' => 'Electronics', 'icon' => 'laptop'],
                                ['name' => 'Fashion', 'icon' => 'tshirt'],
                                ['name' => 'Home & Garden', 'icon' => 'home'],
                                ['name' => 'Sports', 'icon' => 'dumbbell']
                            ];
                            foreach ($fallback_categories as $category):
                        ?>
                            <a href="<?php echo SITE_URL; ?>/products.php" class="category-link">
                                <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                                <?php echo $category['name']; ?>
                            </a>
                        <?php 
                            endforeach;
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Customer Support -->
                <div class="customer-support">
                    <p>Need help? <a href="<?php echo SITE_URL; ?>/contact.php">Contact our support team</a> or check our <a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a></p>
                </div>
            </div>
            
            <!-- Debug Information -->
            <?php if (isset($_GET['debug']) || !empty($debug_info)): ?>
                <div class="debug-info">
                    <h5><i class="fas fa-bug"></i> Debug Information</h5>
                    <div class="debug-grid">
                        <?php foreach ($debug_info as $info): ?>
                            <div class="debug-item">
                                <span><?php echo htmlspecialchars($info); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="debug-item">
                            <strong>Status Filter:</strong>
                            <span><?php echo $status_filter; ?></span>
                        </div>
                        <div class="debug-item">
                            <strong>Total Orders:</strong>
                            <span><?php echo $status_counts['total_count']; ?></span>
                        </div>
                    </div>
                    <div class="debug-actions">
                        <a href="<?php echo SITE_URL; ?>/debug_orders_status.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-tools"></i>
                            Full Debug & Create Test Orders
                        </a>
                        <a href="<?php echo SITE_URL; ?>/orders.php?debug=1" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i>
                            Show Debug Info
                        </a>
                        <a href="<?php echo SITE_URL; ?>/fix_orders_complete.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-tools"></i>
                            Complete Fix Tool
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for enhanced functionality -->
<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        if (alert.classList.contains('alert-success')) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }
    });
}, 5000);

// Cancel order function
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        // Add AJAX call to cancel order
        fetch('api/orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'cancel',
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error cancelling order: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error cancelling order');
        });
    }
}
</script>

<style>
.order-card {
    transition: all 0.3s ease;
    border-left: 4px solid var(--border-color);
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.order-status,
.payment-status {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-pending {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
    border: 1px solid var(--warning-color);
}

.status-processing {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info-color);
    border: 1px solid var(--info-color);
}

.status-completed {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
    border: 1px solid var(--success-color);
}

.status-cancelled,
.status-failed {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
    border: 1px solid var(--danger-color);
}

.order-total h4 {
    font-weight: bold;
}

.order-actions .btn {
    min-width: 100px;
}

@media (max-width: 767px) {
    .order-card .row {
        flex-direction: column;
    }
    
    .order-actions {
        justify-content: stretch !important;
        margin-top: 1rem;
    }
    
    .order-actions .btn {
        flex: 1;
        min-width: auto;
    }
    
    .col-md-4.text-center.text-md-right {
        text-align: center !important;
    }
    
    .test-orders-section {
        padding: 1rem;
    }
}

.fade-in {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced No Orders State Styles */
.no-orders-container {
    padding: 3rem 1rem;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.no-orders-content {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
    border-radius: 1rem;
    padding: 3rem 2rem;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
}

.no-orders-icon {
    position: relative;
    margin-bottom: 2rem;
}

.no-orders-icon > i {
    font-size: 5rem;
    color: var(--primary-color);
    opacity: 0.8;
    animation: bounce 2s infinite;
}

.floating-elements {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
}

.floating-elements i {
    position: absolute;
    font-size: 1.5rem;
    color: var(--secondary-color);
    animation: float 3s ease-in-out infinite;
}

.floating-elements i:nth-child(1) {
    top: -60px;
    left: -40px;
    animation-delay: 0s;
}

.floating-elements i:nth-child(2) {
    top: -40px;
    right: -50px;
    animation-delay: 1s;
}

.floating-elements i:nth-child(3) {
    bottom: -50px;
    left: -30px;
    animation-delay: 2s;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
        opacity: 0.7;
    }
    50% {
        transform: translateY(-20px) rotate(180deg);
        opacity: 1;
    }
}

.no-orders-message h2 {
    color: var(--text-primary);
    font-size: 2rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.no-orders-message .lead {
    color: var(--text-secondary);
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.quick-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin: 2rem 0;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    min-width: 120px;
}

.stat-item i {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.stat-item span {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.no-orders-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
    flex-wrap: wrap;
}

.no-orders-actions .btn {
    min-width: 180px;
}

.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(var(--primary-color-rgb), 0.7);
    }
    70% {
        transform: scale(1.05);
        box-shadow: 0 0 0 10px rgba(var(--primary-color-rgb), 0);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(var(--primary-color-rgb), 0);
    }
}

.helpful-links {
    margin: 3rem 0 2rem 0;
    padding: 2rem;
    background: var(--bg-secondary);
    border-radius: 0.75rem;
    border: 1px solid var(--border-color);
}

.helpful-links h4 {
    color: var(--text-primary);
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
}

.category-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.category-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.3s ease;
}

.category-link:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--primary-color-rgb), 0.3);
    text-decoration: none;
}

.category-link i {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.product-count {
    margin-left: auto;
    font-size: 0.875rem;
    opacity: 0.7;
}

.customer-support {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
}

.customer-support p {
    color: var(--text-muted);
    margin: 0;
}

.customer-support a {
    color: var(--primary-color);
    text-decoration: none;
}

.customer-support a:hover {
    text-decoration: underline;
}

.debug-info {
    margin-top: 2rem;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    text-align: left;
}

.debug-info h5 {
    color: var(--text-primary);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.debug-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.debug-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem;
    background: var(--bg-primary);
    border-radius: 0.25rem;
    border: 1px solid var(--border-color);
}

.debug-actions {
    text-align: center;
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
}

.test-orders-section {
    margin: 2rem 0;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border-radius: 0.75rem;
    border: 1px solid var(--border-color);
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    position: relative;
}

.alert-success {
    color: #0f5132;
    background-color: #d1e7dd;
    border-color: #badbcc;
}

.alert-danger {
    color: #842029;
    background-color: #f8d7da;
    border-color: #f5c2c7;
}

.alert-info {
    color: #055160;
    background-color: #cff4fc;
    border-color: #b6effb;
}

.alert-warning {
    color: #664d03;
    background-color: #fff3cd;
    border-color: #ffecb5;
}

.btn-close {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    opacity: 0.5;
}

.btn-close:hover {
    opacity: 1;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .no-orders-content {
        padding: 2rem 1rem;
        margin: 0 0.5rem;
    }
    
    .no-orders-icon > i {
        font-size: 4rem;
    }
    
    .no-orders-message h2 {
        font-size: 1.5rem;
    }
    
    .quick-stats {
        gap: 1rem;
    }
    
    .debug-actions {
        flex-direction: column;
    }
    
    .alert {
        padding: 0.75rem;
        font-size: 0.875rem;
    }
}
    
    .stat-item {
        min-width: 100px;
        padding: 0.75rem;
    }
    
    .no-orders-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .no-orders-actions .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .category-links {
        grid-template-columns: 1fr;
    }
    
    .floating-elements {
        display: none; /* Hide floating elements on mobile */
    }
}
</style>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        // Implement order cancellation
        fetch('api/orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'cancel',
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to cancel order: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error cancelling order. Please try again.');
        });
    }
}

// Auto-refresh order status every 30 seconds for pending orders
if (document.querySelector('.status-pending')) {
    setInterval(() => {
        // Only refresh if there are pending orders
        const pendingOrders = document.querySelectorAll('.status-pending');
        if (pendingOrders.length > 0) {
            location.reload();
        }
    }, 30000);
}
</script>

<?php include 'includes/footer.php'; ?>
