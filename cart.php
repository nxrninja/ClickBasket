<?php
$page_title = 'Shopping Cart - ClickBasket';
$mobile_title = 'Cart';

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=cart.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_quantity':
            $cart_id = $_POST['cart_id'] ?? 0;
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));
            
            try {
                $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$quantity, $cart_id, get_current_user_id()]);
                handle_success('Cart updated successfully!');
            } catch (Exception $e) {
                handle_error('Failed to update cart.');
            }
            break;
            
        case 'remove_item':
            $cart_id = $_POST['cart_id'] ?? 0;
            
            try {
                $remove_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
                $remove_stmt = $db->prepare($remove_query);
                $remove_stmt->execute([$cart_id, get_current_user_id()]);
                handle_success('Item removed from cart!');
            } catch (Exception $e) {
                handle_error('Failed to remove item.');
            }
            break;
            
        case 'clear_cart':
            try {
                $clear_query = "DELETE FROM cart WHERE user_id = ?";
                $clear_stmt = $db->prepare($clear_query);
                $clear_stmt->execute([get_current_user_id()]);
                handle_success('Cart cleared successfully!');
            } catch (Exception $e) {
                handle_error('Failed to clear cart.');
            }
            break;
    }
}

// Get cart items
try {
    $cart_query = "SELECT c.*, p.title, p.price, p.short_description, p.file_size, 
                   cat.name as category_name
                   FROM cart c
                   JOIN products p ON c.product_id = p.id
                   LEFT JOIN categories cat ON p.category_id = cat.id
                   WHERE c.user_id = ? AND p.is_active = 1
                   ORDER BY c.created_at DESC";
    $cart_stmt = $db->prepare($cart_query);
    $cart_stmt->execute([get_current_user_id()]);
    $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cart_items = [];
}

// Calculate totals
$subtotal = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

$tax_rate = 0; // Get from settings
$tax_amount = $subtotal * ($tax_rate / 100);
$total = $subtotal + $tax_amount;

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-shopping-cart"></i>
                Shopping Cart
            </h1>
            <p style="color: var(--text-secondary); margin: 0;">
                <?php echo $total_items; ?> item<?php echo $total_items !== 1 ? 's' : ''; ?> in your cart
            </p>
        </div>
        <div class="d-none d-md-block">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-secondary">
                <i class="fas fa-plus"></i>
                Continue Shopping
            </a>
        </div>
    </div>

    <?php if (!empty($cart_items)): ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-between align-center">
                            <h5 style="margin: 0;">
                                <i class="fas fa-list"></i>
                                Cart Items
                            </h5>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="btn btn-danger btn-sm" 
                                        onclick="return confirm('Are you sure you want to clear your cart?')">
                                    <i class="fas fa-trash"></i>
                                    Clear Cart
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="cart-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item" style="padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
                                <div class="row align-center">
                                    <!-- Product Image -->
                                    <div class="col-md-2 col-3">
                                        <div class="product-thumbnail" style="background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; height: 80px; border-radius: 0.5rem;">
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
                                    </div>
                                    
                                    <!-- Product Info -->
                                    <div class="col-md-5 col-9">
                                        <div style="padding-left: 1rem;">
                                            <h6 style="color: var(--text-primary); margin-bottom: 0.5rem; line-height: 1.4;">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </h6>
                                            <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">
                                                <?php echo htmlspecialchars($item['short_description']); ?>
                                            </p>
                                            <div style="display: flex; gap: 1rem; font-size: 0.75rem; color: var(--text-muted);">
                                                <span>
                                                    <i class="fas fa-tag"></i>
                                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-file"></i>
                                                    <?php echo htmlspecialchars($item['file_size']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Quantity & Price -->
                                    <div class="col-md-3 col-6">
                                        <div class="quantity-controls" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                                            <label style="font-size: 0.875rem; color: var(--text-secondary); min-width: 60px;">Qty:</label>
                                            <form method="POST" style="display: flex; align-items: center; gap: 0.5rem;">
                                                <input type="hidden" name="action" value="update_quantity">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                       min="1" max="10" class="form-control" 
                                                       style="width: 70px; padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                                       onchange="this.form.submit()">
                                            </form>
                                        </div>
                                        <div class="item-price">
                                            <strong style="color: var(--primary-color); font-size: 1.125rem;">
                                                <?php echo format_currency($item['price'] * $item['quantity']); ?>
                                            </strong>
                                            <?php if ($item['quantity'] > 1): ?>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                    <?php echo format_currency($item['price']); ?> each
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="col-md-2 col-6">
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $item['product_id']; ?>" 
                                               class="btn btn-secondary btn-sm">
                                                <i class="fas fa-eye"></i>
                                                View
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Remove this item from cart?')">
                                                    <i class="fas fa-trash"></i>
                                                    Remove
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Continue Shopping (Mobile) -->
                <div class="d-block d-md-none mt-3">
                    <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-plus"></i>
                        Continue Shopping
                    </a>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card order-summary">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-calculator"></i>
                            Order Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.5rem;">
                            <span style="color: var(--text-secondary);">Subtotal (<?php echo $total_items; ?> items):</span>
                            <span style="color: var(--text-primary); font-weight: 500;"><?php echo format_currency($subtotal); ?></span>
                        </div>
                        
                        <?php if ($tax_amount > 0): ?>
                            <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.5rem;">
                                <span style="color: var(--text-secondary);">Tax (<?php echo $tax_rate; ?>%):</span>
                                <span style="color: var(--text-primary); font-weight: 500;"><?php echo format_currency($tax_amount); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--border-color);">
                            <span style="color: var(--text-secondary);">Shipping:</span>
                            <span style="color: var(--success-color); font-weight: 500;">
                                <i class="fas fa-check"></i>
                                Digital Download
                            </span>
                        </div>
                        
                        <div class="summary-total" style="display: flex; justify-content: space-between; margin-bottom: 2rem; font-size: 1.25rem;">
                            <strong style="color: var(--text-primary);">Total:</strong>
                            <strong style="color: var(--primary-color);"><?php echo format_currency($total); ?></strong>
                        </div>
                        
                        <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-credit-card"></i>
                            Proceed to Checkout
                        </a>
                        
                        <div class="security-badges" style="margin-top: 1.5rem; text-align: center;">
                            <small style="color: var(--text-muted); display: block; margin-bottom: 0.5rem;">Secure Payment</small>
                            <div style="display: flex; justify-content: center; gap: 0.5rem; opacity: 0.7;">
                                <i class="fab fa-cc-visa" style="font-size: 1.5rem;"></i>
                                <i class="fab fa-cc-mastercard" style="font-size: 1.5rem;"></i>
                                <i class="fab fa-cc-paypal" style="font-size: 1.5rem;"></i>
                                <i class="fas fa-lock" style="font-size: 1.2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coupon Code -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 style="margin: 0;">
                            <i class="fas fa-ticket-alt"></i>
                            Coupon Code
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="apply_coupon">
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" name="coupon_code" class="form-control" 
                                       placeholder="Enter coupon code">
                                <button type="submit" class="btn btn-secondary">
                                    Apply
                                </button>
                            </div>
                        </form>
                        <small style="color: var(--text-muted); margin-top: 0.5rem; display: block;">
                            Have a discount code? Enter it above to save on your order.
                        </small>
                    </div>
                </div>

                <!-- Recommended Products -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 style="margin: 0;">
                            <i class="fas fa-thumbs-up"></i>
                            You Might Also Like
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get recommended products (simplified)
                        try {
                            $recommended_query = "SELECT * FROM products 
                                                 WHERE is_active = 1 AND is_featured = 1 
                                                 AND id NOT IN (SELECT product_id FROM cart WHERE user_id = ?)
                                                 ORDER BY downloads_count DESC 
                                                 LIMIT 2";
                            $recommended_stmt = $db->prepare($recommended_query);
                            $recommended_stmt->execute([get_current_user_id()]);
                            $recommended_products = $recommended_stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {
                            $recommended_products = [];
                        }
                        ?>
                        
                        <?php if (!empty($recommended_products)): ?>
                            <?php foreach ($recommended_products as $product): ?>
                                <div class="recommended-item" style="display: flex; gap: 0.75rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <div style="width: 50px; height: 50px; background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem; flex-shrink: 0;">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <h6 style="color: var(--text-primary); margin-bottom: 0.25rem; font-size: 0.875rem; line-height: 1.3;">
                                            <?php echo htmlspecialchars(strlen($product['title']) > 30 ? substr($product['title'], 0, 30) . '...' : $product['title']); ?>
                                        </h6>
                                        <div style="color: var(--primary-color); font-weight: bold; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                            <?php echo format_currency($product['price']); ?>
                                        </div>
                                        <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                                No recommendations available at the moment.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Empty Cart -->
        <div class="text-center" style="padding: 4rem 0;">
            <i class="fas fa-shopping-cart" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">Your Cart is Empty</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem; max-width: 400px; margin-left: auto; margin-right: auto;">
                Looks like you haven't added any items to your cart yet. 
                Start shopping to fill it up with amazing digital products!
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i>
                    Start Shopping
                </a>
                <a href="<?php echo SITE_URL; ?>/categories.php" class="btn btn-secondary">
                    <i class="fas fa-th-large"></i>
                    Browse Categories
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.cart-item {
    transition: all 0.3s ease;
}

.cart-item:hover {
    background: var(--bg-secondary);
}

.cart-item:last-child {
    border-bottom: none !important;
}

.order-summary {
    position: sticky;
    top: 100px;
}

.quantity-controls input[type="number"] {
    -moz-appearance: textfield;
}

.quantity-controls input[type="number"]::-webkit-outer-spin-button,
.quantity-controls input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.security-badges i {
    color: var(--text-muted);
}

@media (max-width: 767px) {
    .cart-item .row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .cart-item .col-md-2,
    .cart-item .col-md-5,
    .cart-item .col-md-3,
    .cart-item .col-md-2 {
        width: 100%;
        max-width: 100%;
    }
    
    .product-thumbnail {
        width: 100px;
        margin: 0 auto;
    }
    
    .quantity-controls {
        justify-content: center;
    }
    
    .item-price {
        text-align: center;
    }
    
    .order-summary {
        position: static;
        margin-top: 2rem;
    }
}

.btn-block {
    width: 100%;
}
</style>

<script>
// Auto-save cart changes
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count in navigation
    updateCartCount();
    
    // Add loading states to buttons
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds if form doesn't submit
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }, 3000);
            }
        });
    });
});

function updateCartCount() {
    // This would typically make an AJAX call to get the current cart count
    const cartItems = <?php echo count($cart_items); ?>;
    const cartBadges = document.querySelectorAll('.cart-count');
    cartBadges.forEach(badge => {
        badge.textContent = cartItems;
        badge.style.display = cartItems > 0 ? 'flex' : 'none';
    });
}

// Quantity change confirmation for large quantities
document.querySelectorAll('input[name="quantity"]').forEach(input => {
    input.addEventListener('change', function() {
        const quantity = parseInt(this.value);
        if (quantity > 5) {
            if (!confirm(`Are you sure you want ${quantity} copies of this digital product?`)) {
                this.value = this.defaultValue;
                return false;
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
