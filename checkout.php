<?php
$page_title = 'Checkout - ClickBasket';
$mobile_title = 'Checkout';

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=checkout.php');
}

$database = new Database();
$db = $database->getConnection();

// Get cart items
try {
    $cart_query = "SELECT c.*, p.title, p.price, p.short_description, p.file_size, p.screenshots,
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

// Redirect if cart is empty
if (empty($cart_items)) {
    handle_error('Your cart is empty. Please add items before checkout.');
    redirect('cart.php');
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $billing_name = trim($_POST['billing_name'] ?? '');
    $billing_email = trim($_POST['billing_email'] ?? '');
    $billing_phone = trim($_POST['billing_phone'] ?? '');
    $billing_address = trim($_POST['billing_address'] ?? '');
    $billing_city = trim($_POST['billing_city'] ?? '');
    $billing_state = trim($_POST['billing_state'] ?? '');
    $billing_zip = trim($_POST['billing_zip'] ?? '');
    $billing_country = trim($_POST['billing_country'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($billing_name)) $errors[] = 'Name is required';
    if (empty($billing_email) || !filter_var($billing_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($billing_phone)) $errors[] = 'Phone number is required';
    if (empty($payment_method)) $errors[] = 'Payment method is required';
    
    // Additional validation for COD
    if ($payment_method === 'cod') {
        if (empty($billing_address)) $errors[] = 'Address is required for Cash on Delivery';
        if (empty($billing_city)) $errors[] = 'City is required for Cash on Delivery';
        if (empty($billing_state)) $errors[] = 'State is required for Cash on Delivery';
        if (empty($billing_zip)) $errors[] = 'ZIP code is required for Cash on Delivery';
        if (empty($billing_country)) $errors[] = 'Country is required for Cash on Delivery';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Generate order number
            $order_number = 'CB' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create order (compatible with existing schema)
            $order_query = "INSERT INTO orders (user_id, order_number, total_amount, discount_amount, 
                           tax_amount, final_amount, payment_method, payment_status, order_status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $payment_status = ($payment_method === 'cod') ? 'pending' : 'pending';
            $order_status = ($payment_method === 'cod') ? 'pending' : 'pending';
            
            $order_stmt = $db->prepare($order_query);
            $order_stmt->execute([
                get_current_user_id(),
                $order_number,
                $subtotal,
                0, // discount_amount
                $tax_amount,
                $total,
                $payment_method,
                $payment_status,
                $order_status
            ]);
            
            $order_id = $db->lastInsertId();
            
            // Store billing information in a separate table or user notes
            // For now, we'll create a simple billing_info table entry
            try {
                $billing_query = "INSERT INTO order_billing (order_id, billing_name, billing_email, billing_phone, 
                                 billing_address, billing_city, billing_state, billing_zip, billing_country) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $billing_stmt = $db->prepare($billing_query);
                $billing_stmt->execute([
                    $order_id,
                    $billing_name,
                    $billing_email,
                    $billing_phone,
                    $billing_address,
                    $billing_city,
                    $billing_state,
                    $billing_zip,
                    $billing_country
                ]);
            } catch (Exception $e) {
                // If billing table doesn't exist, we'll store as JSON in a notes field or skip for now
                error_log("Billing info storage failed: " . $e->getMessage());
                error_log("Order ID: $order_id, User ID: " . get_current_user_id());
                
                // Don't fail the entire order if billing fails
                // Just log the error and continue
            }
            
            // Create order items
            foreach ($cart_items as $item) {
                $item_query = "INSERT INTO order_items (order_id, product_id, product_title, 
                              product_price, quantity) 
                              VALUES (?, ?, ?, ?, ?)";
                $item_stmt = $db->prepare($item_query);
                $item_stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['title'],
                    $item['price'],
                    $item['quantity']
                ]);
            }
            
            // Clear cart
            $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
            $clear_cart_stmt = $db->prepare($clear_cart_query);
            $clear_cart_stmt->execute([get_current_user_id()]);
            
            $db->commit();
            
            // Log successful order creation
            error_log("Order created successfully: Order ID $order_id, Order Number $order_number, User ID " . get_current_user_id());
            
            // Store success info in session
            $_SESSION['last_order'] = [
                'id' => $order_id,
                'number' => $order_number,
                'amount' => $total,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Redirect based on payment method
            if ($payment_method === 'cod') {
                handle_success('Order placed successfully! You will pay cash on delivery.');
                redirect('order-confirmation.php?order=' . $order_number);
            } else {
                // Redirect to payment gateway
                handle_success('Order created! Redirecting to payment...');
                redirect('payment.php?order=' . $order_number);
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Order creation failed: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("User ID: " . get_current_user_id());
            error_log("Order data: " . json_encode([
                'order_number' => $order_number ?? 'N/A',
                'total' => $total ?? 'N/A',
                'payment_method' => $payment_method ?? 'N/A'
            ]));
            
            // Store error in session for debugging
            $_SESSION['checkout_error'] = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            handle_error('Failed to create order: ' . $e->getMessage() . '. Please try again or contact support. Error logged for debugging.');
        }
    } else {
        foreach ($errors as $error) {
            handle_error($error);
        }
    }
}

// Get user info for pre-filling
$user_info = [];
try {
    $user_query = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->execute([get_current_user_id()]);
    $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user_info = [];
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-credit-card"></i>
                Checkout
            </h1>
            <p style="color: var(--text-secondary); margin: 0;">
                Complete your order securely
            </p>
        </div>
        <div class="d-none d-md-block">
            <a href="<?php echo SITE_URL; ?>/cart.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Cart
            </a>
        </div>
    </div>

    <form method="POST" id="checkoutForm">
        <div class="row">
            <!-- Billing Information -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-user"></i>
                            Billing Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="billing_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="billing_name" name="billing_name" 
                                       value="<?php echo htmlspecialchars($user_info['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="billing_email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="billing_email" name="billing_email" 
                                       value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="billing_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="billing_phone" name="billing_phone" 
                                       value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <!-- Address fields (shown/hidden based on payment method) -->
                        <div id="address-fields" style="display: none;">
                            <hr style="margin: 1.5rem 0;">
                            <h6 style="color: var(--text-primary); margin-bottom: 1rem;">
                                <i class="fas fa-map-marker-alt"></i>
                                Delivery Address (Required for Cash on Delivery)
                            </h6>
                            <div class="mb-3">
                                <label for="billing_address" class="form-label">Street Address</label>
                                <textarea class="form-control" id="billing_address" name="billing_address" 
                                         rows="2" placeholder="Enter your full address"><?php echo htmlspecialchars($user_info['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="billing_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="billing_city" name="billing_city" 
                                           value="<?php echo htmlspecialchars($user_info['city'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="billing_state" class="form-label">State/Province</label>
                                    <input type="text" class="form-control" id="billing_state" name="billing_state" 
                                           value="<?php echo htmlspecialchars($user_info['state'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="billing_zip" class="form-label">ZIP/Postal Code</label>
                                    <input type="text" class="form-control" id="billing_zip" name="billing_zip" 
                                           value="<?php echo htmlspecialchars($user_info['zip'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="billing_country" class="form-label">Country</label>
                                    <select class="form-control" id="billing_country" name="billing_country">
                                        <option value="">Select Country</option>
                                        <option value="India" <?php echo ($user_info['country'] ?? '') === 'India' ? 'selected' : ''; ?>>India</option>
                                        <option value="United States" <?php echo ($user_info['country'] ?? '') === 'United States' ? 'selected' : ''; ?>>United States</option>
                                        <option value="United Kingdom" <?php echo ($user_info['country'] ?? '') === 'United Kingdom' ? 'selected' : ''; ?>>United Kingdom</option>
                                        <option value="Canada" <?php echo ($user_info['country'] ?? '') === 'Canada' ? 'selected' : ''; ?>>Canada</option>
                                        <option value="Australia" <?php echo ($user_info['country'] ?? '') === 'Australia' ? 'selected' : ''; ?>>Australia</option>
                                        <!-- Add more countries as needed -->
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-credit-card"></i>
                            Payment Method
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="payment-methods">
                            <!-- Cash on Delivery -->
                            <div class="payment-option mb-3">
                                <label class="payment-method-label" for="cod">
                                    <input type="radio" id="cod" name="payment_method" value="cod" class="payment-radio">
                                    <div class="payment-method-card">
                                        <div class="payment-method-icon">
                                            <i class="fas fa-money-bill-wave" style="color: #10b981;"></i>
                                        </div>
                                        <div class="payment-method-info">
                                            <h6>Cash on Delivery</h6>
                                            <p>Pay with cash when your order is delivered</p>
                                            <div class="cod-benefits">
                                                <small><i class="fas fa-check text-success"></i> No online payment required</small><br>
                                                <small><i class="fas fa-check text-success"></i> Pay only when you receive the product</small><br>
                                                <small><i class="fas fa-info-circle text-info"></i> Delivery address required</small>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Credit/Debit Card -->
                            <div class="payment-option mb-3">
                                <label class="payment-method-label" for="card">
                                    <input type="radio" id="card" name="payment_method" value="card" class="payment-radio">
                                    <div class="payment-method-card">
                                        <div class="payment-method-icon">
                                            <i class="fas fa-credit-card" style="color: #6366f1;"></i>
                                        </div>
                                        <div class="payment-method-info">
                                            <h6>Credit/Debit Card</h6>
                                            <p>Pay securely with your card</p>
                                            <div class="card-icons">
                                                <i class="fab fa-cc-visa"></i>
                                                <i class="fab fa-cc-mastercard"></i>
                                                <i class="fab fa-cc-amex"></i>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- PayPal -->
                            <div class="payment-option mb-3">
                                <label class="payment-method-label" for="paypal">
                                    <input type="radio" id="paypal" name="payment_method" value="paypal" class="payment-radio">
                                    <div class="payment-method-card">
                                        <div class="payment-method-icon">
                                            <i class="fab fa-paypal" style="color: #0070ba;"></i>
                                        </div>
                                        <div class="payment-method-info">
                                            <h6>PayPal</h6>
                                            <p>Pay with your PayPal account</p>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- UPI -->
                            <div class="payment-option mb-3">
                                <label class="payment-method-label" for="upi">
                                    <input type="radio" id="upi" name="payment_method" value="upi" class="payment-radio">
                                    <div class="payment-method-card">
                                        <div class="payment-method-icon">
                                            <i class="fas fa-mobile-alt" style="color: #ff6b35;"></i>
                                        </div>
                                        <div class="payment-method-info">
                                            <h6>UPI Payment</h6>
                                            <p>Pay using UPI apps like GPay, PhonePe, Paytm</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card order-summary">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-shopping-cart"></i>
                            Order Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Cart Items -->
                        <div class="order-items mb-3">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="order-item" style="display: flex; gap: 0.75rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <?php 
                                    $screenshots = json_decode($item['screenshots'] ?? '[]', true);
                                    if (!empty($screenshots) && isset($screenshots[0])): 
                                    ?>
                                        <div class="item-thumbnail" style="width: 50px; height: 50px; border-radius: 0.5rem; overflow: hidden; border: 1px solid var(--border-color); background: var(--bg-secondary); flex-shrink: 0;">
                                            <img src="<?php echo SITE_URL . '/' . $screenshots[0]; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                                 style="width: 100%; height: 100%; object-fit: contain; display: block;">
                                        </div>
                                    <?php else: ?>
                                        <div class="item-thumbnail" style="width: 50px; height: 50px; background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem; flex-shrink: 0;">
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
                                        <h6 style="color: var(--text-primary); margin-bottom: 0.25rem; font-size: 0.875rem; line-height: 1.3;">
                                            <?php echo htmlspecialchars(strlen($item['title']) > 30 ? substr($item['title'], 0, 30) . '...' : $item['title']); ?>
                                        </h6>
                                        <div style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 0.25rem;">
                                            Qty: <?php echo $item['quantity']; ?>
                                        </div>
                                        <div style="color: var(--primary-color); font-weight: bold; font-size: 0.875rem;">
                                            <?php echo format_currency($item['price'] * $item['quantity']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Order Totals -->
                        <div class="order-totals">
                            <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                                <span style="color: var(--text-secondary);">Subtotal (<?php echo $total_items; ?> items):</span>
                                <span style="color: var(--text-primary); font-weight: 500;"><?php echo format_currency($subtotal); ?></span>
                            </div>
                            
                            <?php if ($tax_amount > 0): ?>
                                <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                                    <span style="color: var(--text-secondary);">Tax (<?php echo $tax_rate; ?>%):</span>
                                    <span style="color: var(--text-primary); font-weight: 500;"><?php echo format_currency($tax_amount); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 2px solid var(--border-color);">
                                <span style="color: var(--text-secondary);">Delivery:</span>
                                <span style="color: var(--success-color); font-weight: 500;">
                                    <i class="fas fa-check"></i>
                                    Digital Download
                                </span>
                            </div>
                            
                            <div class="summary-total" style="display: flex; justify-content: space-between; margin-bottom: 2rem; font-size: 1.25rem;">
                                <strong style="color: var(--text-primary);">Total:</strong>
                                <strong style="color: var(--primary-color);"><?php echo format_currency($total); ?></strong>
                            </div>
                        </div>

                        <!-- Place Order Button -->
                        <button type="submit" class="btn btn-primary btn-block btn-lg" id="placeOrderBtn">
                            <i class="fas fa-check"></i>
                            Place Order
                        </button>

                        <!-- Security Info -->
                        <div class="security-info mt-3" style="text-align: center;">
                            <small style="color: var(--text-muted); display: block; margin-bottom: 0.5rem;">
                                <i class="fas fa-shield-alt"></i>
                                Your payment information is secure
                            </small>
                            <div style="display: flex; justify-content: center; gap: 0.5rem; opacity: 0.7;">
                                <i class="fas fa-lock"></i>
                                <span style="font-size: 0.75rem;">256-bit SSL encryption</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.payment-method-label {
    cursor: pointer;
    margin: 0;
}

.payment-method-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid var(--border-color);
    border-radius: 0.75rem;
    background: var(--bg-primary);
    transition: all 0.3s ease;
}

.payment-method-card:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow-sm);
}

.payment-radio {
    display: none;
}

.payment-radio:checked + .payment-method-card {
    border-color: var(--primary-color);
    background: var(--primary-color-light);
    box-shadow: var(--shadow-md);
}

.payment-method-icon {
    font-size: 2rem;
    width: 60px;
    text-align: center;
}

.payment-method-info h6 {
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.payment-method-info p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 0.875rem;
}

.cod-benefits {
    margin-top: 0.5rem;
}

.cod-benefits small {
    display: block;
    margin-bottom: 0.25rem;
}

.card-icons i {
    font-size: 1.5rem;
    margin-right: 0.5rem;
    opacity: 0.7;
}

.order-summary {
    position: sticky;
    top: 100px;
}

.order-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

@media (max-width: 767px) {
    .payment-method-card {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .payment-method-icon {
        width: auto;
    }
    
    .order-summary {
        position: static;
        margin-top: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const addressFields = document.getElementById('address-fields');
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const checkoutForm = document.getElementById('checkoutForm');
    
    // Handle payment method selection
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'cod') {
                addressFields.style.display = 'block';
                // Make address fields required
                document.getElementById('billing_address').required = true;
                document.getElementById('billing_city').required = true;
                document.getElementById('billing_state').required = true;
                document.getElementById('billing_zip').required = true;
                document.getElementById('billing_country').required = true;
                
                // Update button text
                placeOrderBtn.innerHTML = '<i class="fas fa-truck"></i> Place COD Order';
            } else {
                addressFields.style.display = 'none';
                // Remove required attribute from address fields
                document.getElementById('billing_address').required = false;
                document.getElementById('billing_city').required = false;
                document.getElementById('billing_state').required = false;
                document.getElementById('billing_zip').required = false;
                document.getElementById('billing_country').required = false;
                
                // Update button text based on payment method
                if (this.value === 'card') {
                    placeOrderBtn.innerHTML = '<i class="fas fa-credit-card"></i> Pay with Card';
                } else if (this.value === 'paypal') {
                    placeOrderBtn.innerHTML = '<i class="fab fa-paypal"></i> Pay with PayPal';
                } else if (this.value === 'upi') {
                    placeOrderBtn.innerHTML = '<i class="fas fa-mobile-alt"></i> Pay with UPI';
                } else {
                    placeOrderBtn.innerHTML = '<i class="fas fa-check"></i> Place Order';
                }
            }
        });
    });
    
    // Form submission handling
    checkoutForm.addEventListener('submit', function(e) {
        const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
        
        if (!selectedPayment) {
            e.preventDefault();
            alert('Please select a payment method');
            return false;
        }
        
        // Add loading state
        placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        placeOrderBtn.disabled = true;
    });
    
    // Auto-fill country based on user location (optional)
    if (navigator.geolocation) {
        // This is a basic implementation - you might want to use a proper geolocation service
    }
});
</script>

<?php include 'includes/footer.php'; ?>
