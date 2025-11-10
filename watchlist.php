<?php
$page_title = 'My Watch List - ClickBasket';
$mobile_title = 'Watch List';

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=watchlist.php');
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get watchlist items
try {
    $watchlist_query = "SELECT w.*, p.title, p.price, p.screenshots, p.short_description, p.slug,
                               c.name as category_name, w.created_at as added_date
                        FROM watchlist w
                        JOIN products p ON w.product_id = p.id
                        LEFT JOIN categories c ON p.category_id = c.id
                        WHERE w.user_id = ? AND p.is_active = 1
                        ORDER BY w.created_at DESC
                        LIMIT ? OFFSET ?";
    $watchlist_stmt = $db->prepare($watchlist_query);
    $watchlist_stmt->execute([$user_id, $limit, $offset]);
    $watchlist_items = $watchlist_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM watchlist w 
                   JOIN products p ON w.product_id = p.id 
                   WHERE w.user_id = ? AND p.is_active = 1";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute([$user_id]);
    $total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $limit);

} catch (Exception $e) {
    $watchlist_items = [];
    $total_items = 0;
    $total_pages = 0;
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-eye"></i>
                My Watch List
            </h1>
            <p style="color: var(--text-secondary); margin: 0;">
                <?php echo $total_items; ?> product<?php echo $total_items !== 1 ? 's' : ''; ?> in your watch list
            </p>
        </div>
        <div class="d-none d-md-block">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Browse Products
            </a>
        </div>
    </div>

    <?php if (!empty($watchlist_items)): ?>
        <!-- Watchlist Items Grid -->
        <div class="products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
            <?php foreach ($watchlist_items as $item): ?>
                <div class="product-card watchlist-item" style="border: 1px solid var(--border-color); border-radius: 1rem; overflow: hidden; background: var(--bg-primary); transition: all 0.3s ease; position: relative;" data-product-id="<?php echo $item['product_id']; ?>">
                    <!-- Remove Button -->
                    <button class="remove-from-watchlist" onclick="removeFromWatchlist(<?php echo $item['product_id']; ?>)" 
                            style="position: absolute; top: 10px; right: 10px; z-index: 10; background: rgba(220, 53, 69, 0.9); color: white; border: none; border-radius: 50%; width: 35px; height: 35px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                        <i class="fas fa-times"></i>
                    </button>

                    <!-- Product Image -->
                    <div class="product-image" style="height: 200px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); position: relative; overflow: hidden;">
                        <?php
                        $screenshots = json_decode($item['screenshots'] ?? '[]', true) ?? [];
                        if (!empty($screenshots) && isset($screenshots[0])):
                        ?>
                            <img src="<?php echo htmlspecialchars($screenshots[0]); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white; font-size: 3rem;">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Added Date Badge -->
                        <div style="position: absolute; bottom: 10px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                            Added <?php echo date('M j', strtotime($item['added_date'])); ?>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div style="padding: 1.5rem;">
                        <!-- Category -->
                        <?php if ($item['category_name']): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <span class="badge" style="background: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.75rem; font-size: 0.75rem;">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Title -->
                        <h3 style="margin-bottom: 0.5rem; font-size: 1.1rem;">
                            <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $item['product_id']; ?>" 
                               style="color: var(--text-primary); text-decoration: none;">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        </h3>

                        <!-- Description -->
                        <?php if ($item['short_description']): ?>
                            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem; line-height: 1.4;">
                                <?php echo htmlspecialchars(substr($item['short_description'], 0, 100)); ?>...
                            </p>
                        <?php endif; ?>

                        <!-- Price -->
                        <div style="margin-bottom: 1rem;">
                            <span style="font-size: 1.25rem; font-weight: bold; color: var(--primary-color);">
                                <?php echo format_currency($item['price']); ?>
                            </span>
                        </div>

                        <!-- Action Buttons -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <button class="btn btn-success btn-sm" onclick="addToCartFromWatchlist(<?php echo $item['product_id']; ?>)">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                            <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $item['product_id']; ?>" 
                               class="btn btn-outline-primary btn-sm" style="text-decoration: none; text-align: center;">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="text-center">
                <nav>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-chevron-left"></i>
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center" style="padding: 4rem 0;">
            <i class="fas fa-eye-slash" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">Your Watch List is Empty</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">
                Start adding products to your watch list to keep track of items you're interested in. 
                You can add products from any product page using the "Add to Watch List" button.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i>
                    Browse Products
                </a>
                <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-home"></i>
                    Go to Homepage
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.watchlist-item {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.watchlist-item:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.remove-from-watchlist:hover {
    background: rgba(220, 53, 69, 1) !important;
    transform: scale(1.1);
}

.product-card .btn {
    transition: all 0.3s ease;
}

.product-card .btn:hover {
    transform: translateY(-2px);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)) !important;
        gap: 1rem !important;
    }
    
    .d-flex.justify-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-none.d-md-block {
        display: block !important;
        text-align: center;
    }
}
</style>

<script>
// Remove from watchlist
function removeFromWatchlist(productId) {
    if (!confirm('Are you sure you want to remove this product from your watch list?')) {
        return;
    }

    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
    const removeBtn = productCard.querySelector('.remove-from-watchlist');
    
    // Show loading state
    removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    removeBtn.disabled = true;

    fetch('<?php echo SITE_URL; ?>/api/watchlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animate removal
            productCard.style.transform = 'scale(0.8)';
            productCard.style.opacity = '0';
            
            setTimeout(() => {
                productCard.remove();
                
                // Check if this was the last item
                const remainingItems = document.querySelectorAll('.watchlist-item');
                if (remainingItems.length === 0) {
                    location.reload(); // Reload to show empty state
                }
            }, 300);
            
            showNotification('Product removed from watch list', 'success');
        } else {
            throw new Error(data.message || 'Failed to remove from watch list');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error: ' + error.message, 'error');
        
        // Reset button
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.disabled = false;
    });
}

// Add to cart from watchlist
function addToCartFromWatchlist(productId) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    button.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success state
            button.innerHTML = '<i class="fas fa-check"></i> Added!';
            button.style.backgroundColor = 'var(--success-color)';
            
            showNotification('Product added to cart successfully!', 'success');
            
            // Update cart count if function exists
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.backgroundColor = '';
                button.disabled = false;
            }, 2000);
        } else {
            throw new Error(data.message || 'Failed to add to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error: ' + error.message, 'error');
        
        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 350px;
        animation: slideIn 0.3s ease-out;
        color: white;
        font-weight: 500;
    `;
    
    // Set background color based on type
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    notification.style.backgroundColor = colors[type] || colors.info;
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>
