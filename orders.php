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
$limit = ORDERS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get user orders
try {
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
    $orders_stmt->execute([get_current_user_id(), $limit, $offset]);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total orders count
    $count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute([get_current_user_id()]);
    $total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_orders / $limit);
} catch (Exception $e) {
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
                My Orders
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

    <!-- Filter Tabs -->
    <div class="card mb-4">
        <div class="card-body" style="padding: 1rem;">
            <div style="display: flex; gap: 0.5rem; overflow-x: auto;">
                <a href="?status=all" class="btn btn-sm <?php echo (!isset($_GET['status']) || $_GET['status'] === 'all') ? 'btn-primary' : 'btn-secondary'; ?>">
                    All Orders
                </a>
                <a href="?status=pending" class="btn btn-sm <?php echo ($_GET['status'] ?? '') === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Pending
                </a>
                <a href="?status=processing" class="btn btn-sm <?php echo ($_GET['status'] ?? '') === 'processing' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Processing
                </a>
                <a href="?status=completed" class="btn btn-sm <?php echo ($_GET['status'] ?? '') === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Completed
                </a>
                <a href="?status=cancelled" class="btn btn-sm <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Cancelled
                </a>
            </div>
        </div>
    </div>

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
                                    
                                    <?php if ($order['order_status'] === 'completed' && $order['payment_status'] === 'completed'): ?>
                                        <a href="<?php echo SITE_URL; ?>/downloads.php?order=<?php echo $order['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-download"></i>
                                            Download
                                        </a>
                                    <?php endif; ?>
                                    
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
        <!-- No Orders -->
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
