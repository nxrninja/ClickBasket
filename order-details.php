<?php
$page_title = 'Order Details - ClickBasket';
$mobile_title = 'Order Details';

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=order-details.php');
}

$database = new Database();
$db = $database->getConnection();
$current_user_id = get_current_user_id();

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    handle_error('Invalid order ID');
    redirect('orders.php');
}

// Get order details
$order = null;
$order_items = [];
$billing_info = null;

try {
    // Get order information
    $order_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $order_stmt = $db->prepare($order_query);
    $order_stmt->execute([$order_id, $current_user_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        handle_error('Order not found or you do not have permission to view it');
        redirect('orders.php');
    }
    
    // Get order items
    $items_query = "SELECT oi.*, p.screenshots, p.category_id, c.name as category_name 
                    FROM order_items oi 
                    LEFT JOIN products p ON oi.product_id = p.id 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE oi.order_id = ? 
                    ORDER BY oi.id";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get billing information
    try {
        $billing_query = "SELECT * FROM order_billing WHERE order_id = ?";
        $billing_stmt = $db->prepare($billing_query);
        $billing_stmt->execute([$order_id]);
        $billing_info = $billing_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Billing table might not exist
        $billing_info = null;
    }
    
} catch (Exception $e) {
    error_log("Order details error: " . $e->getMessage());
    handle_error('Error loading order details: ' . $e->getMessage());
    redirect('orders.php');
}

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'cancel_order':
            if ($order['order_status'] === 'pending') {
                try {
                    $cancel_query = "UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?";
                    $cancel_stmt = $db->prepare($cancel_query);
                    $cancel_stmt->execute([$order_id, $current_user_id]);
                    
                    handle_success('Order cancelled successfully');
                    redirect('order-details.php?id=' . $order_id);
                } catch (Exception $e) {
                    handle_error('Error cancelling order: ' . $e->getMessage());
                }
            } else {
                handle_error('This order cannot be cancelled');
            }
            break;
    }
}

include 'includes/header.php';

// Show flash messages
$success_message = get_flash_message('success');
$error_message = get_flash_message('error');
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

    <!-- Page Header -->
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-receipt"></i>
                Order Details
            </h1>
            <p style="color: var(--text-secondary); margin: 0;">
                Order #<?php echo htmlspecialchars($order['order_number']); ?>
            </p>
        </div>
        <div class="d-none d-md-block">
            <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Orders
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-md-8">
            <!-- Order Summary Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-info-circle"></i>
                        Order Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="order-info-item">
                                <strong>Order Number:</strong>
                                <span><?php echo htmlspecialchars($order['order_number']); ?></span>
                            </div>
                            <div class="order-info-item">
                                <strong>Order Date:</strong>
                                <span><?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-info-item">
                                <strong>Order Status:</strong>
                                <span class="order-status status-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="order-info-item">
                                <strong>Payment Method:</strong>
                                <span><?php echo ucfirst($order['payment_method']); ?></span>
                            </div>
                            <div class="order-info-item">
                                <strong>Payment Status:</strong>
                                <span class="payment-status status-<?php echo $order['payment_status']; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </div>
                            <?php if ($order['transaction_id']): ?>
                                <div class="order-info-item">
                                    <strong>Transaction ID:</strong>
                                    <span><?php echo htmlspecialchars($order['transaction_id']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-shopping-bag"></i>
                        Order Items (<?php echo count($order_items); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($order_items)): ?>
                        <div class="order-items-list">
                            <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <?php if (!empty($item['screenshots'])): ?>
                                            <?php $screenshots = json_decode($item['screenshots'], true); ?>
                                            <?php if (!empty($screenshots) && is_array($screenshots)): ?>
                                                <img src="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars($screenshots[0]); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_title']); ?>"
                                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/no-image.png'">
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <h6><?php echo htmlspecialchars($item['product_title']); ?></h6>
                                        <?php if ($item['category_name']): ?>
                                            <p class="item-category">
                                                <i class="fas fa-tag"></i>
                                                <?php echo htmlspecialchars($item['category_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="item-price-qty">
                                            <span class="item-price"><?php echo format_currency($item['product_price']); ?></span>
                                            <span class="item-qty">Qty: <?php echo $item['quantity']; ?></span>
                                        </div>
                                    </div>
                                    <div class="item-total">
                                        <strong><?php echo format_currency($item['product_price'] * $item['quantity']); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-items">
                            <i class="fas fa-box-open"></i>
                            <p>No items found for this order</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Billing Information -->
            <?php if ($billing_info): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-map-marker-alt"></i>
                            Billing Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="billing-info-item">
                                    <strong>Name:</strong>
                                    <span><?php echo htmlspecialchars($billing_info['billing_name']); ?></span>
                                </div>
                                <div class="billing-info-item">
                                    <strong>Email:</strong>
                                    <span><?php echo htmlspecialchars($billing_info['billing_email']); ?></span>
                                </div>
                                <div class="billing-info-item">
                                    <strong>Phone:</strong>
                                    <span><?php echo htmlspecialchars($billing_info['billing_phone']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php if ($billing_info['billing_address']): ?>
                                    <div class="billing-info-item">
                                        <strong>Address:</strong>
                                        <div class="billing-address">
                                            <?php echo nl2br(htmlspecialchars($billing_info['billing_address'])); ?><br>
                                            <?php if ($billing_info['billing_city']): ?>
                                                <?php echo htmlspecialchars($billing_info['billing_city']); ?>
                                                <?php if ($billing_info['billing_zip']): ?>
                                                    - <?php echo htmlspecialchars($billing_info['billing_zip']); ?>
                                                <?php endif; ?><br>
                                            <?php endif; ?>
                                            <?php if ($billing_info['billing_state']): ?>
                                                <?php echo htmlspecialchars($billing_info['billing_state']); ?>
                                                <?php if ($billing_info['billing_country']): ?>
                                                    , <?php echo htmlspecialchars($billing_info['billing_country']); ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order Summary Sidebar -->
        <div class="col-md-4">
            <!-- Price Breakdown -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-calculator"></i>
                        Price Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    <div class="price-breakdown">
                        <div class="price-item">
                            <span>Subtotal:</span>
                            <span><?php echo format_currency($order['total_amount']); ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="price-item discount">
                                <span>Discount:</span>
                                <span>-<?php echo format_currency($order['discount_amount']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($order['tax_amount'] > 0): ?>
                            <div class="price-item">
                                <span>Tax:</span>
                                <span><?php echo format_currency($order['tax_amount']); ?></span>
                            </div>
                        <?php endif; ?>
                        <hr>
                        <div class="price-item total">
                            <span><strong>Total:</strong></span>
                            <span><strong><?php echo format_currency($order['final_amount']); ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-cogs"></i>
                        Order Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="order-actions">
                        <?php if ($order['order_status'] === 'pending'): ?>
                            <form method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                <input type="hidden" name="action" value="cancel_order">
                                <button type="submit" class="btn btn-danger btn-block">
                                    <i class="fas fa-times"></i>
                                    Cancel Order
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-list"></i>
                            View All Orders
                        </a>
                        
                        <button onclick="window.print()" class="btn btn-info btn-block">
                            <i class="fas fa-print"></i>
                            Print Order
                        </button>
                        
                        <?php if ($order['order_status'] === 'completed'): ?>
                            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-success btn-block">
                                <i class="fas fa-shopping-cart"></i>
                                Shop Again
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-clock"></i>
                        Order Timeline
                    </h5>
                </div>
                <div class="card-body">
                    <div class="order-timeline">
                        <div class="timeline-item <?php echo $order['order_status'] !== 'cancelled' ? 'completed' : 'cancelled'; ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="timeline-content">
                                <h6>Order Placed</h6>
                                <p><?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if ($order['order_status'] !== 'cancelled'): ?>
                            <div class="timeline-item <?php echo in_array($order['order_status'], ['processing', 'completed']) ? 'completed' : 'pending'; ?>">
                                <div class="timeline-icon">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Processing</h6>
                                    <p><?php echo $order['order_status'] === 'processing' ? 'In progress' : ($order['order_status'] === 'completed' ? 'Completed' : 'Pending'); ?></p>
                                </div>
                            </div>
                            
                            <div class="timeline-item <?php echo $order['order_status'] === 'completed' ? 'completed' : 'pending'; ?>">
                                <div class="timeline-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Completed</h6>
                                    <p><?php echo $order['order_status'] === 'completed' ? date('M j, Y \a\t g:i A', strtotime($order['updated_at'])) : 'Pending'; ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="timeline-item cancelled">
                                <div class="timeline-icon">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Cancelled</h6>
                                    <p><?php echo date('M j, Y \a\t g:i A', strtotime($order['updated_at'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Order Details Styles */
.order-info-item, .billing-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.order-info-item:last-child, .billing-info-item:last-child {
    border-bottom: none;
}

.order-status, .payment-status {
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

.status-cancelled, .status-failed {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
    border: 1px solid var(--danger-color);
}

/* Order Items */
.order-items-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
}

.item-image {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 0.375rem;
}

.no-image {
    width: 100%;
    height: 100%;
    background: var(--bg-primary);
    border: 2px dashed var(--border-color);
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
}

.item-details {
    flex: 1;
}

.item-details h6 {
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
    font-weight: 600;
}

.item-category {
    margin: 0 0 0.5rem 0;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.item-price-qty {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.item-price {
    color: var(--primary-color);
    font-weight: 500;
}

.item-qty {
    color: var(--text-muted);
    font-size: 0.875rem;
}

.item-total {
    text-align: right;
    color: var(--text-primary);
    font-size: 1.1rem;
}

.no-items {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.no-items i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Price Breakdown */
.price-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price-item.discount span:last-child {
    color: var(--success-color);
}

.price-item.total {
    font-size: 1.1rem;
    color: var(--primary-color);
}

/* Order Actions */
.order-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.btn-block {
    width: 100%;
    text-align: center;
}

/* Order Timeline */
.order-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 1rem;
    top: 2.5rem;
    width: 2px;
    height: calc(100% + 1rem);
    background: var(--border-color);
}

.timeline-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    flex-shrink: 0;
    z-index: 1;
    background: var(--bg-primary);
}

.timeline-item.completed .timeline-icon {
    background: var(--success-color);
    color: white;
}

.timeline-item.pending .timeline-icon {
    background: var(--warning-color);
    color: white;
}

.timeline-item.cancelled .timeline-icon {
    background: var(--danger-color);
    color: white;
}

.timeline-content h6 {
    margin: 0 0 0.25rem 0;
    color: var(--text-primary);
    font-size: 0.875rem;
    font-weight: 600;
}

.timeline-content p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.75rem;
}

/* Billing Address */
.billing-address {
    line-height: 1.4;
    color: var(--text-secondary);
}

/* Alert Styles */
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
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .item-image {
        width: 120px;
        height: 120px;
    }
    
    .item-price-qty {
        justify-content: center;
    }
    
    .order-info-item, .billing-info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .d-none.d-md-block {
        display: none !important;
    }
}

/* Print Styles */
@media print {
    .btn, .order-actions, .card-header {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .container {
        max-width: none !important;
        padding: 0 !important;
    }
}
</style>

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
</script>

<?php include 'includes/footer.php'; ?>
