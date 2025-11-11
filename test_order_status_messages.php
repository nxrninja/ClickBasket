<?php
// Test script to demonstrate different order status filter messages
$page_title = 'Test Order Status Messages - ClickBasket';
require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=test_order_status_messages.php');
}

$database = new Database();
$db = $database->getConnection();
$current_user_id = get_current_user_id();

// Simulate different status filters
$test_statuses = ['all', 'pending', 'processing', 'completed', 'cancelled'];

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>$page_title</title>";
echo "<link rel='stylesheet' href='assets/css/style.css'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>";
echo "</head>";
echo "<body>";

echo "<div class='container' style='padding: 2rem 0;'>";
echo "<h1 style='text-align: center; color: var(--primary-color); margin-bottom: 2rem;'>";
echo "<i class='fas fa-test-tube'></i> Order Status Messages Test";
echo "</h1>";

echo "<p style='text-align: center; margin-bottom: 3rem; color: var(--text-secondary);'>";
echo "This page demonstrates how the messaging changes for different order status filters when no orders exist.";
echo "</p>";

// Mock status counts (all zero for testing empty state)
$status_counts = [
    'total_count' => 0,
    'pending_count' => 0,
    'processing_count' => 0,
    'completed_count' => 0,
    'cancelled_count' => 0
];

foreach ($test_statuses as $status_filter) {
    echo "<div class='test-section' style='margin-bottom: 4rem; border: 2px solid var(--border-color); border-radius: 1rem; padding: 2rem;'>";
    echo "<h2 style='color: var(--primary-color); margin-bottom: 1rem; text-align: center;'>";
    echo "<i class='fas fa-filter'></i> Status Filter: " . ucfirst($status_filter);
    echo "</h2>";
    
    // Display the actual message content that would appear
    echo "<div class='no-orders-container'>";
    echo "<div class='no-orders-content'>";
    
    // Icon and Animation
    echo "<div class='no-orders-icon'>";
    echo "<i class='fas fa-shopping-cart'></i>";
    echo "<div class='floating-elements'>";
    echo "<i class='fas fa-star'></i>";
    echo "<i class='fas fa-heart'></i>";
    echo "<i class='fas fa-gift'></i>";
    echo "</div>";
    echo "</div>";
    
    // Main Message
    echo "<div class='no-orders-message'>";
    echo "<h2>";
    if ($status_filter !== 'all') {
        echo "No " . ucfirst($status_filter) . " Orders Found";
    } else {
        echo "Your Shopping Journey Starts Here!";
    }
    echo "</h2>";
    
    echo "<p class='lead'>";
    if ($status_filter !== 'all') {
        echo "You don't have any $status_filter orders at the moment. ";
        if ($status_filter === 'pending') {
            echo "All your orders have been processed successfully!";
        } elseif ($status_filter === 'completed') {
            echo "Complete an order to see it here.";
        } elseif ($status_filter === 'cancelled') {
            echo "Great! You haven't cancelled any orders.";
        } elseif ($status_filter === 'processing') {
            echo "Your orders are being processed efficiently!";
        }
    } else {
        echo "Discover amazing products and create your first order to get started with ClickBasket!";
    }
    echo "</p>";
    echo "</div>";
    
    // Quick Stats (only for 'all' status)
    if ($status_filter === 'all' && $status_counts['total_count'] == 0) {
        echo "<div class='quick-stats'>";
        echo "<div class='stat-item'>";
        echo "<i class='fas fa-truck'></i>";
        echo "<span>Fast Delivery</span>";
        echo "</div>";
        echo "<div class='stat-item'>";
        echo "<i class='fas fa-shield-alt'></i>";
        echo "<span>Secure Payment</span>";
        echo "</div>";
        echo "<div class='stat-item'>";
        echo "<i class='fas fa-headset'></i>";
        echo "<span>24/7 Support</span>";
        echo "</div>";
        echo "</div>";
    }
    
    // Action Buttons
    echo "<div class='no-orders-actions'>";
    if ($status_filter !== 'all') {
        echo "<a href='" . SITE_URL . "/orders.php' class='btn btn-primary btn-lg'>";
        echo "<i class='fas fa-list'></i> View All Orders";
        echo "</a>";
        echo "<a href='" . SITE_URL . "/products.php' class='btn btn-secondary btn-lg'>";
        echo "<i class='fas fa-shopping-bag'></i> Continue Shopping";
        echo "</a>";
    } else {
        echo "<a href='" . SITE_URL . "/products.php' class='btn btn-primary btn-lg pulse'>";
        echo "<i class='fas fa-shopping-bag'></i> Start Shopping Now";
        echo "</a>";
        echo "<a href='" . SITE_URL . "/index.php' class='btn btn-secondary btn-lg'>";
        echo "<i class='fas fa-home'></i> Browse Homepage";
        echo "</a>";
    }
    echo "</div>";
    
    echo "</div>"; // no-orders-content
    echo "</div>"; // no-orders-container
    
    // Show actual URL for this filter
    echo "<div style='text-align: center; margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 0.5rem;'>";
    echo "<strong>Actual URL:</strong> ";
    if ($status_filter === 'all') {
        echo "<code>orders.php</code> or <code>orders.php?status=all</code>";
    } else {
        echo "<code>orders.php?status=$status_filter</code>";
    }
    echo "</div>";
    
    echo "</div>"; // test-section
}

// Add navigation links
echo "<div style='text-align: center; margin: 3rem 0; padding: 2rem; background: var(--primary-color); color: white; border-radius: 1rem;'>";
echo "<h3 style='margin-bottom: 1rem;'><i class='fas fa-link'></i> Test These Links</h3>";
echo "<div style='display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;'>";

foreach ($test_statuses as $status) {
    $url = ($status === 'all') ? 'orders.php' : "orders.php?status=$status";
    $label = ucfirst($status);
    $icon = [
        'all' => 'list',
        'pending' => 'clock',
        'processing' => 'spinner',
        'completed' => 'check-circle',
        'cancelled' => 'times-circle'
    ][$status] ?? 'tag';
    
    echo "<a href='$url' class='btn btn-light btn-sm' style='color: var(--primary-color);'>";
    echo "<i class='fas fa-$icon'></i> $label Orders";
    echo "</a>";
}

echo "</div>";
echo "<p style='margin-top: 1rem; opacity: 0.9;'>";
echo "Click the buttons above to see the actual orders page with different status filters.";
echo "</p>";
echo "</div>";

echo "</div>"; // container

// Include the CSS from orders.php for proper styling
?>

<style>
/* Enhanced No Orders State Styles */
.no-orders-container {
    padding: 2rem 1rem;
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.no-orders-content {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
    border-radius: 1rem;
    padding: 2rem 1.5rem;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
}

.no-orders-icon {
    position: relative;
    margin-bottom: 1.5rem;
}

.no-orders-icon > i {
    font-size: 4rem;
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
    font-size: 1.2rem;
    color: var(--secondary-color);
    animation: float 3s ease-in-out infinite;
}

.floating-elements i:nth-child(1) {
    top: -50px;
    left: -30px;
    animation-delay: 0s;
}

.floating-elements i:nth-child(2) {
    top: -30px;
    right: -40px;
    animation-delay: 1s;
}

.floating-elements i:nth-child(3) {
    bottom: -40px;
    left: -25px;
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
        transform: translateY(-15px) rotate(180deg);
        opacity: 1;
    }
}

.no-orders-message h2 {
    color: var(--text-primary);
    font-size: 1.75rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.no-orders-message .lead {
    color: var(--text-secondary);
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.quick-stats {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin: 1.5rem 0;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    min-width: 100px;
}

.stat-item i {
    font-size: 1.25rem;
    color: var(--primary-color);
}

.stat-item span {
    font-size: 0.8rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.no-orders-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin: 1.5rem 0;
    flex-wrap: wrap;
}

.no-orders-actions .btn {
    min-width: 160px;
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

.test-section {
    background: var(--bg-primary);
    box-shadow: var(--shadow-md);
}

.test-section h2 {
    background: var(--primary-color);
    color: white;
    margin: -2rem -2rem 2rem -2rem;
    padding: 1rem 2rem;
    border-radius: 1rem 1rem 0 0;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .no-orders-content {
        padding: 1.5rem 1rem;
    }
    
    .no-orders-icon > i {
        font-size: 3rem;
    }
    
    .no-orders-message h2 {
        font-size: 1.5rem;
    }
    
    .quick-stats {
        gap: 1rem;
    }
    
    .no-orders-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .no-orders-actions .btn {
        width: 100%;
        max-width: 250px;
    }
    
    .floating-elements {
        display: none;
    }
}
</style>

</body>
</html>
