<?php
$page_title = 'My Dashboard - ClickBasket';
$mobile_title = 'Dashboard';

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=dashboard.php');
}

$database = new Database();
$db = $database->getConnection();

// Get user information
$user_id = get_current_user_id();
try {
    $user_query = "SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        redirect('login.php');
    }
} catch (Exception $e) {
    redirect('login.php');
}

// Get user statistics
try {
    // Total orders
    $orders_query = "SELECT COUNT(*) as total, SUM(final_amount) as spent FROM orders WHERE user_id = ?";
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute([$user_id]);
    $orders_data = $orders_stmt->fetch(PDO::FETCH_ASSOC);
    $total_orders = $orders_data['total'] ?? 0;
    $total_spent = $orders_data['spent'] ?? 0;

    // Total downloads
    $downloads_query = "SELECT COUNT(*) as total FROM downloads WHERE user_id = ?";
    $downloads_stmt = $db->prepare($downloads_query);
    $downloads_stmt->execute([$user_id]);
    $total_downloads = $downloads_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Cart items
    $cart_query = "SELECT COUNT(*) as total FROM cart WHERE user_id = ?";
    $cart_stmt = $db->prepare($cart_query);
    $cart_stmt->execute([$user_id]);
    $cart_items = $cart_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Recent orders
    $recent_orders_query = "SELECT o.*, COUNT(oi.id) as item_count 
                           FROM orders o 
                           LEFT JOIN order_items oi ON o.id = oi.order_id 
                           WHERE o.user_id = ? 
                           GROUP BY o.id 
                           ORDER BY o.created_at DESC 
                           LIMIT 5";
    $recent_orders_stmt = $db->prepare($recent_orders_query);
    $recent_orders_stmt->execute([$user_id]);
    $recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent downloads
    $recent_downloads_query = "SELECT d.*, p.title, p.file_size 
                              FROM downloads d 
                              JOIN products p ON d.product_id = p.id 
                              WHERE d.user_id = ? 
                              ORDER BY d.created_at DESC 
                              LIMIT 5";
    $recent_downloads_stmt = $db->prepare($recent_downloads_query);
    $recent_downloads_stmt->execute([$user_id]);
    $recent_downloads = $recent_downloads_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recommended products (based on user's purchase history)
    $recommended_query = "SELECT p.* FROM products p 
                         WHERE p.is_active = 1 AND p.is_featured = 1 
                         AND p.id NOT IN (
                             SELECT DISTINCT oi.product_id FROM order_items oi 
                             JOIN orders o ON oi.order_id = o.id 
                             WHERE o.user_id = ?
                         )
                         ORDER BY p.downloads_count DESC 
                         LIMIT 6";
    $recommended_stmt = $db->prepare($recommended_query);
    $recommended_stmt->execute([$user_id]);
    $recommended_products = $recommended_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $total_orders = 0;
    $total_spent = 0;
    $total_downloads = 0;
    $cart_items = 0;
    $recent_orders = [];
    $recent_downloads = [];
    $recommended_products = [];
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Welcome Section -->
    <div class="welcome-section" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: 1rem; padding: 2rem; margin-bottom: 2rem;">
        <div class="row align-center">
            <div class="col-md-8">
                <h1 style="margin-bottom: 0.5rem;">
                    <i class="fas fa-tachometer-alt"></i>
                    Welcome back, <?php echo htmlspecialchars($user['name']); ?>!
                </h1>
                <p style="margin: 0; opacity: 0.9; font-size: 1.1rem;">
                    Here's what's happening with your account today.
                </p>
            </div>
            <div class="col-md-4 text-center text-md-right">
                <div style="opacity: 0.8;">
                    <small>Member since</small><br>
                    <strong><?php echo date('F Y', strtotime($user['created_at'])); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo $total_orders; ?></h3>
                <p style="color: var(--text-secondary); margin: 0;">Total Orders</p>
                <a href="orders.php" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">View Orders</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="width: 60px; height: 60px; background: var(--success-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem;">
                    <i class="fas fa-download"></i>
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo $total_downloads; ?></h3>
                <p style="color: var(--text-secondary); margin: 0;">Downloads</p>
                <a href="downloads.php" class="btn btn-success btn-sm" style="margin-top: 0.5rem;">View Downloads</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="width: 60px; height: 60px; background: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem;">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo format_currency($total_spent); ?></h3>
                <p style="color: var(--text-secondary); margin: 0;">Total Spent</p>
                <a href="orders.php" class="btn btn-secondary btn-sm" style="margin-top: 0.5rem;">View History</a>
            </div>
        </div>

        <div class="stat-card">
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="width: 60px; height: 60px; background: var(--info-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem;">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo $cart_items; ?></h3>
                <p style="color: var(--text-secondary); margin: 0;">Cart Items</p>
                <a href="cart.php" class="btn btn-info btn-sm" style="margin-top: 0.5rem;">View Cart</a>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Recent Activity -->
        <div class="col-md-8">
            <!-- Recent Orders -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-between align-center">
                        <h5 style="margin: 0;">
                            <i class="fas fa-clock"></i>
                            Recent Orders
                        </h5>
                        <a href="orders.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_orders)): ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="order-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--border-color);">
                                <div>
                                    <h6 style="color: var(--text-primary); margin-bottom: 0.25rem;">
                                        Order #<?php echo htmlspecialchars($order['order_number']); ?>
                                    </h6>
                                    <small style="color: var(--text-muted);">
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?> • 
                                        <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] !== '1' ? 's' : ''; ?>
                                    </small>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: var(--primary-color); font-weight: bold; margin-bottom: 0.25rem;">
                                        <?php echo format_currency($order['final_amount']); ?>
                                    </div>
                                    <span class="status-badge status-<?php echo $order['order_status']; ?>" style="padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; text-transform: uppercase;">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>No orders yet. Start shopping to see your orders here!</p>
                            <a href="products.php" class="btn btn-primary">Browse Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Downloads -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-between align-center">
                        <h5 style="margin: 0;">
                            <i class="fas fa-download"></i>
                            Recent Downloads
                        </h5>
                        <a href="downloads.php" class="btn btn-success btn-sm">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_downloads)): ?>
                        <?php foreach ($recent_downloads as $download): ?>
                            <div class="download-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 40px; height: 40px; background: var(--success-color); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div>
                                        <h6 style="color: var(--text-primary); margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($download['title']); ?>
                                        </h6>
                                        <small style="color: var(--text-muted);">
                                            <?php echo date('M j, Y', strtotime($download['created_at'])); ?> • 
                                            <?php echo htmlspecialchars($download['file_size']); ?>
                                        </small>
                                    </div>
                                </div>
                                <div>
                                    <small style="color: var(--text-muted);">
                                        <?php echo $download['download_count']; ?>/<?php echo $download['max_downloads']; ?> downloads
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            <i class="fas fa-download" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>No downloads yet. Purchase products to access downloads!</p>
                            <a href="products.php" class="btn btn-success">Browse Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="products.php" class="btn btn-primary btn-block">
                            <i class="fas fa-shopping-bag"></i>
                            Browse Products
                        </a>
                        <a href="cart.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-shopping-cart"></i>
                            View Cart (<?php echo $cart_items; ?>)
                        </a>
                        <a href="profile.php" class="btn btn-info btn-block">
                            <i class="fas fa-user"></i>
                            Edit Profile
                        </a>
                        <a href="contact.php" class="btn btn-warning btn-block">
                            <i class="fas fa-headset"></i>
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-user-circle"></i>
                        Account Info
                    </h5>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 1rem;">
                        <strong style="color: var(--text-primary);">Name:</strong><br>
                        <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong style="color: var(--text-primary);">Email:</strong><br>
                        <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <?php if ($user['phone']): ?>
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary);">Phone:</strong><br>
                            <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <strong style="color: var(--text-primary);">Member Since:</strong><br>
                        <span style="color: var(--text-secondary);"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Recommended Products -->
            <?php if (!empty($recommended_products)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-star"></i>
                            Recommended for You
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach (array_slice($recommended_products, 0, 3) as $product): ?>
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
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="products.php" class="btn btn-secondary btn-sm btn-block">
                            View All Products
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.welcome-section {
    animation: fadeInUp 0.6s ease-out;
}

.stat-card {
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.stat-card .card {
    height: 100%;
    border: none;
    box-shadow: var(--shadow-md);
}

.order-item:last-child,
.download-item:last-child,
.recommended-item:last-child {
    border-bottom: none !important;
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}

.status-badge {
    font-weight: 500;
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

.btn-block {
    width: 100%;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 767px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .welcome-section {
        padding: 1.5rem;
    }
    
    .welcome-section h1 {
        font-size: 1.5rem;
    }
    
    .welcome-section .row {
        flex-direction: column;
        text-align: center;
    }
    
    .order-item,
    .download-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .order-item > div:last-child,
    .download-item > div:last-child {
        text-align: left;
    }
}
</style>

<script>
// Auto-refresh dashboard data every 5 minutes
setInterval(() => {
    // Only refresh if user is actively viewing the page
    if (!document.hidden) {
        location.reload();
    }
}, 300000);

// Add loading states to quick action buttons
document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (this.href && !this.href.includes('#')) {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            this.style.pointerEvents = 'none';
            
            // Reset after 3 seconds if navigation doesn't happen
            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
            }, 3000);
        }
    });
});

// Welcome animation
document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.style.animation = 'fadeInUp 0.6s ease-out forwards';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
