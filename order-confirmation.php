<?php
$page_title = 'Order Confirmation - ClickBasket';
$mobile_title = 'Order Placed';

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$order_number = $_GET['order'] ?? '';
if (empty($order_number)) {
    handle_error('Invalid order reference.');
    redirect('orders.php');
}

$database = new Database();
$db = $database->getConnection();

// Get order details
try {
    $order_query = "SELECT o.*, 
                    COUNT(oi.id) as item_count,
                    GROUP_CONCAT(oi.product_title SEPARATOR ', ') as product_titles
                    FROM orders o
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.order_number = ? AND o.user_id = ?
                    GROUP BY o.id";
    $order_stmt = $db->prepare($order_query);
    $order_stmt->execute([$order_number, get_current_user_id()]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        handle_error('Order not found.');
        redirect('orders.php');
    }
    
    // Get billing information if available
    $billing_info = null;
    try {
        $billing_query = "SELECT * FROM order_billing WHERE order_id = ?";
        $billing_stmt = $db->prepare($billing_query);
        $billing_stmt->execute([$order['id']]);
        $billing_info = $billing_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Billing table might not exist, that's okay
        $billing_info = null;
    }
    
    // Get order items
    $items_query = "SELECT oi.*, p.short_description, p.screenshots, c.name as category_name
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE oi.order_id = ?";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([$order['id']]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    handle_error('Failed to load order details.');
    redirect('orders.php');
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Success Header -->
    <div class="text-center mb-5">
        <div class="success-icon" style="width: 100px; height: 100px; background: var(--success-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; animation: successPulse 2s ease-in-out;">
            <i class="fas fa-check" style="font-size: 3rem; color: white;"></i>
        </div>
        <h1 style="color: var(--success-color); margin-bottom: 0.5rem;">
            Order Placed Successfully!
        </h1>
        <p style="color: var(--text-secondary); font-size: 1.125rem; margin-bottom: 1rem;">
            Thank you for your order. We've received your request and will process it shortly.
        </p>
        <div class="order-number-badge" style="display: inline-block; background: var(--primary-color-light); color: var(--primary-color); padding: 0.75rem 1.5rem; border-radius: 2rem; font-weight: 600; font-size: 1.125rem; border: 2px solid var(--primary-color);">
            Order #<?php echo htmlspecialchars($order['order_number']); ?>
        </div>
    </div>

    <div class="row">
        <!-- Order Details -->
        <div class="col-md-8">
            <!-- Payment Method Info -->
            <?php if ($order['payment_method'] === 'cod'): ?>
                <div class="card mb-4 cod-info">
                    <div class="card-body" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 0.75rem;">
                        <div class="d-flex align-center mb-3">
                            <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                                <i class="fas fa-money-bill-wave" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h5 style="margin: 0; color: white;">Cash on Delivery Selected</h5>
                                <p style="margin: 0; opacity: 0.9;">You will pay when your order is delivered</p>
                            </div>
                        </div>
                        <div class="cod-instructions">
                            <h6 style="color: white; margin-bottom: 0.75rem;">
                                <i class="fas fa-info-circle"></i>
                                What happens next?
                            </h6>
                            <ul style="margin: 0; padding-left: 1.5rem;">
                                <li style="margin-bottom: 0.5rem;">We'll process your order and prepare it for delivery</li>
                                <li style="margin-bottom: 0.5rem;">Our delivery partner will contact you to schedule delivery</li>
                                <li style="margin-bottom: 0.5rem;">Pay the exact amount in cash when you receive your order</li>
                                <li>Keep the exact change ready: <strong><?php echo format_currency($order['final_amount']); ?></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 style="color: var(--text-primary); margin-bottom: 1rem;">
                            <i class="fas fa-credit-card"></i>
                            Payment Information
                        </h5>
                        <p style="color: var(--text-secondary);">
                            Payment method: <strong><?php echo ucfirst($order['payment_method']); ?></strong><br>
                            Payment status: <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Delivery Information -->
            <?php if ($order['payment_method'] === 'cod' && $billing_info && !empty($billing_info['billing_address'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-truck"></i>
                            Delivery Address
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="delivery-address">
                            <h6 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($billing_info['billing_name']); ?>
                            </h6>
                            <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($billing_info['billing_address']); ?>
                            </p>
                            <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($billing_info['billing_city']); ?>, 
                                <?php echo htmlspecialchars($billing_info['billing_state']); ?> 
                                <?php echo htmlspecialchars($billing_info['billing_zip']); ?>
                            </p>
                            <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($billing_info['billing_country']); ?>
                            </p>
                            <p style="color: var(--text-secondary); margin: 0;">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($billing_info['billing_phone']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-box"></i>
                        Order Items (<?php echo count($order_items); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                            <?php 
                            $screenshots = json_decode($item['screenshots'] ?? '[]', true);
                            if (!empty($screenshots) && isset($screenshots[0])): 
                            ?>
                                <div class="item-thumbnail" style="width: 80px; height: 80px; border-radius: 0.75rem; overflow: hidden; border: 1px solid var(--border-color); background: var(--bg-secondary); flex-shrink: 0;">
                                    <img src="<?php echo SITE_URL . '/' . $screenshots[0]; ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_title']); ?>"
                                         style="width: 100%; height: 100%; object-fit: contain; display: block;">
                                </div>
                            <?php else: ?>
                                <div class="item-thumbnail" style="width: 80px; height: 80px; background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; flex-shrink: 0;">
                                    <i class="fas fa-<?php 
                                        echo match($item['category_name']) {
                                            'Web Templates' => 'code',
                                            'Mobile Apps' => 'mobile-alt',
                                            'Graphics & Design' => 'palette',
                                            'Software Tools' => 'tools',
                                            'E-books' => 'book',
                                            default => 'file'
                                        };
                                    ?>"></i>
                                </div>
                            <?php endif; ?>
                            <div style="flex: 1;">
                                <h6 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($item['product_title']); ?>
                                </h6>
                                <?php if (!empty($item['short_description'])): ?>
                                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">
                                        <?php echo htmlspecialchars($item['short_description']); ?>
                                    </p>
                                <?php endif; ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <span style="color: var(--text-muted); font-size: 0.875rem;">
                                            Quantity: <?php echo $item['quantity']; ?>
                                        </span>
                                        <span style="color: var(--text-muted); font-size: 0.875rem; margin-left: 1rem;">
                                            Price: <?php echo format_currency($item['product_price']); ?>
                                        </span>
                                    </div>
                                    <div style="color: var(--primary-color); font-weight: bold; font-size: 1.125rem;">
                                        <?php echo format_currency($item['product_price'] * $item['quantity']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons text-center">
                <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-list"></i>
                    View All Orders
                </a>
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-shopping-bag"></i>
                    Continue Shopping
                </a>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-md-4">
            <div class="card order-summary">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-receipt"></i>
                        Order Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="color: var(--text-secondary);">Order Date:</span>
                        <span style="color: var(--text-primary); font-weight: 500;">
                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="color: var(--text-secondary);">Order Status:</span>
                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                    
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="color: var(--text-secondary);">Items (<?php echo $order['item_count']; ?>):</span>
                        <span style="color: var(--text-primary); font-weight: 500;">
                            <?php echo format_currency($order['total_amount']); ?>
                        </span>
                    </div>
                    
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="color: var(--text-secondary);">Discount:</span>
                            <span style="color: var(--success-color); font-weight: 500;">
                                -<?php echo format_currency($order['discount_amount']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['tax_amount'] > 0): ?>
                        <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="color: var(--text-secondary);">Tax:</span>
                            <span style="color: var(--text-primary); font-weight: 500;">
                                <?php echo format_currency($order['tax_amount']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 2px solid var(--border-color);">
                        <span style="color: var(--text-secondary);">Service:</span>
                        <span style="color: var(--success-color); font-weight: 500;">
                            <i class="fas fa-check"></i>
                            Premium Product
                        </span>
                    </div>
                    
                    <div class="summary-total" style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; font-size: 1.5rem;">
                        <strong style="color: var(--text-primary);">Total:</strong>
                        <strong style="color: var(--primary-color);">
                            <?php echo format_currency($order['final_amount']); ?>
                        </strong>
                    </div>
                    
                    <?php if ($order['payment_method'] === 'cod'): ?>
                        <div class="cod-reminder" style="background: var(--warning-color-light, rgba(245, 158, 11, 0.1)); border: 1px solid var(--warning-color); border-radius: 0.5rem; padding: 1rem; text-align: center;">
                            <i class="fas fa-money-bill-wave" style="color: var(--warning-color); font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                            <p style="color: var(--warning-color); font-weight: 600; margin: 0; font-size: 0.875rem;">
                                Cash on Delivery<br>
                                Pay <?php echo format_currency($order['final_amount']); ?> when delivered
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="card mt-3">
                <div class="card-body text-center">
                    <h6 style="color: var(--text-primary); margin-bottom: 0.75rem;">
                        <i class="fas fa-headset"></i>
                        Need Help?
                    </h6>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;">
                        Have questions about your order? Our support team is here to help.
                    </p>
                    <a href="<?php echo SITE_URL; ?>/support.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-envelope"></i>
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes successPulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 20px rgba(16, 185, 129, 0);
    }
}

.status-badge {
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

.status-cancelled {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
    border: 1px solid var(--danger-color);
}

.order-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

.action-buttons {
    margin: 2rem 0;
}

.action-buttons .btn {
    margin: 0 0.5rem 0.5rem;
}

@media (max-width: 767px) {
    .action-buttons .btn {
        display: block;
        width: 100%;
        margin: 0 0 0.75rem;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .item-thumbnail {
        width: 100px;
        height: 100px;
        margin: 0 auto 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add some celebratory effects
    setTimeout(function() {
        // You could add confetti or other celebration effects here
        console.log('Order confirmation loaded successfully!');
    }, 1000);
    
    // Auto-redirect to orders page after 30 seconds (optional)
    // setTimeout(function() {
    //     window.location.href = '<?php echo SITE_URL; ?>/orders.php';
    // }, 30000);
});
</script>

<?php include 'includes/footer.php'; ?>
