<?php
$page_title = 'Product Details - ClickBasket';
$mobile_title = 'Product';

require_once 'config/config.php';
require_once 'classes/Product.php';

// Get product ID from URL
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    handle_error('Invalid product ID', 'products.php');
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

// Get product details
$product_data = $product->getProductById($product_id);

if (!$product_data) {
    handle_error('Product not found', 'products.php');
}

// Get related products
$related_products = $product->getRelatedProducts($product_id, $product_data['category_id'], 4);

// Parse screenshots
$screenshots = json_decode($product_data['screenshots'], true) ?? [];

// Set page title with product name
$page_title = htmlspecialchars($product_data['title']) . ' - ClickBasket';
$meta_description = htmlspecialchars($product_data['short_description'] ?? substr($product_data['description'], 0, 160));

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 1rem;">
    <!-- Breadcrumb -->
    <nav style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-muted); font-size: 0.875rem;">
            <a href="<?php echo SITE_URL; ?>" style="color: var(--text-muted); text-decoration: none;">Home</a>
            <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
            <a href="<?php echo SITE_URL; ?>/products.php" style="color: var(--text-muted); text-decoration: none;">Products</a>
            <?php if ($product_data['category_name']): ?>
                <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($product_data['category_slug']); ?>" style="color: var(--text-muted); text-decoration: none;">
                    <?php echo htmlspecialchars($product_data['category_name']); ?>
                </a>
            <?php endif; ?>
            <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
            <span style="color: var(--text-primary);"><?php echo htmlspecialchars($product_data['title']); ?></span>
        </div>
    </nav>

    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6">
            <div class="product-gallery">
                <?php if (!empty($screenshots)): ?>
                    <div class="main-image" style="margin-bottom: 1rem;">
                        <img id="mainImage" src="<?php echo SITE_URL . '/' . $screenshots[0]; ?>" 
                             alt="<?php echo htmlspecialchars($product_data['title']); ?>"
                             style="width: 100%; height: 400px; object-fit: cover; border-radius: 0.5rem; border: 1px solid var(--border-color);">
                    </div>
                    
                    <?php if (count($screenshots) > 1): ?>
                        <div class="thumbnail-images" style="display: flex; gap: 0.5rem; overflow-x: auto; padding: 0.5rem 0;">
                            <?php foreach ($screenshots as $index => $screenshot): ?>
                                <img src="<?php echo SITE_URL . '/' . $screenshot; ?>" 
                                     alt="Product image <?php echo $index + 1; ?>"
                                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeMainImage('<?php echo SITE_URL . '/' . $screenshot; ?>', this)"
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 0.25rem; border: 2px solid transparent; cursor: pointer; flex-shrink: 0;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-image" style="width: 100%; height: 400px; background: var(--bg-secondary); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
                        <div style="text-align: center; color: var(--text-muted);">
                            <i class="fas fa-image" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                            <p>No images available</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-6">
            <div class="product-info">
                <h1 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 2rem;">
                    <?php echo htmlspecialchars($product_data['title']); ?>
                </h1>

                <?php if ($product_data['category_name']): ?>
                    <div style="margin-bottom: 1rem;">
                        <span class="badge" style="background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.875rem;">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($product_data['category_name']); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="price" style="margin-bottom: 2rem;">
                    <span style="font-size: 2.5rem; font-weight: bold; color: var(--primary-color);">
                        <?php echo format_currency($product_data['price']); ?>
                    </span>
                </div>

                <?php if ($product_data['short_description']): ?>
                    <div class="short-description" style="margin-bottom: 2rem;">
                        <p style="color: var(--text-secondary); font-size: 1.1rem; line-height: 1.6;">
                            <?php echo htmlspecialchars($product_data['short_description']); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Product Stats -->
                <div class="product-stats" style="display: flex; gap: 2rem; margin-bottom: 2rem; padding: 1rem; background: var(--bg-secondary); border-radius: 0.5rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                            <?php echo number_format($product_data['downloads_count']); ?>
                        </div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Downloads</div>
                    </div>
                    <?php if ($product_data['file_size']): ?>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--success-color);">
                                <?php echo htmlspecialchars($product_data['file_size']); ?>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--text-muted);">File Size</div>
                        </div>
                    <?php endif; ?>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: var(--info-color);">
                            <i class="fas fa-star"></i>
                        </div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Premium</div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="product-actions" style="margin-bottom: 2rem;">
                    <?php if (is_logged_in()): ?>
                        <button class="btn btn-primary btn-lg" style="width: 100%; margin-bottom: 1rem;" onclick="addToCart(<?php echo $product_id; ?>)">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart
                        </button>
                        <button class="btn btn-secondary btn-lg" style="width: 100%;" onclick="addToWishlist(<?php echo $product_id; ?>)">
                            <i class="fas fa-heart"></i>
                            Add to Wishlist
                        </button>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary btn-lg" style="width: 100%; margin-bottom: 1rem; text-decoration: none; display: inline-block; text-align: center;">
                            <i class="fas fa-sign-in-alt"></i>
                            Login to Purchase
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Demo Link -->
                <?php if ($product_data['demo_url']): ?>
                    <div style="margin-bottom: 2rem;">
                        <a href="<?php echo htmlspecialchars($product_data['demo_url']); ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-external-link-alt"></i>
                            View Live Demo
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Product Features -->
                <div class="product-features" style="margin-bottom: 2rem;">
                    <h4 style="color: var(--text-primary); margin-bottom: 1rem;">What's Included:</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            <span>Instant Download</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            <span>Lifetime Updates</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            <span>Premium Support</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            <span>Commercial License</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Description -->
    <?php if ($product_data['description']): ?>
        <div class="product-description" style="margin-top: 4rem;">
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">
                        <i class="fas fa-info-circle"></i>
                        Product Description
                    </h3>
                </div>
                <div class="card-body">
                    <div style="line-height: 1.8; color: var(--text-primary);">
                        <?php echo nl2br(htmlspecialchars($product_data['description'])); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <div class="related-products" style="margin-top: 4rem;">
            <h3 style="color: var(--text-primary); margin-bottom: 2rem; text-align: center;">
                <i class="fas fa-th-large"></i>
                Related Products
            </h3>
            
            <div class="row">
                <?php foreach ($related_products as $related): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="card product-card" style="height: 100%; transition: transform 0.3s ease;">
                            <?php 
                            $related_screenshots = json_decode($related['screenshots'], true);
                            if (!empty($related_screenshots)): 
                            ?>
                                <img src="<?php echo SITE_URL . '/' . $related_screenshots[0]; ?>" 
                                     alt="<?php echo htmlspecialchars($related['title']); ?>"
                                     style="width: 100%; height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 100%; height: 200px; background: var(--bg-secondary); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="font-size: 2rem; color: var(--text-muted);"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body" style="display: flex; flex-direction: column;">
                                <h5 style="margin-bottom: 0.5rem;">
                                    <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $related['id']; ?>" style="color: var(--text-primary); text-decoration: none;">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h5>
                                <p style="color: var(--text-secondary); font-size: 0.875rem; flex-grow: 1;">
                                    <?php echo htmlspecialchars(substr($related['short_description'] ?? '', 0, 80)); ?>...
                                </p>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                                    <span style="font-weight: bold; color: var(--primary-color);">
                                        <?php echo format_currency($related['price']); ?>
                                    </span>
                                    <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $related['id']; ?>" class="btn btn-primary btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.thumbnail.active {
    border-color: var(--primary-color) !important;
}

.thumbnail:hover {
    border-color: var(--primary-color);
    opacity: 0.8;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

@media (max-width: 767px) {
    .product-stats {
        flex-direction: column;
        gap: 1rem !important;
    }
    
    .product-stats > div {
        text-align: left !important;
    }
    
    .thumbnail-images {
        justify-content: center;
    }
}
</style>

<script>
function changeMainImage(src, thumbnail) {
    document.getElementById('mainImage').src = src;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    thumbnail.classList.add('active');
}

function addToCart(productId) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    button.disabled = true;
    
    // Add to cart functionality
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
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success state
            button.innerHTML = '<i class="fas fa-check"></i> Added to Cart!';
            button.style.backgroundColor = 'var(--success-color)';
            
            // Show success message
            showNotification('Product added to cart successfully!', 'success');
            
            // Update cart count in header if exists
            updateCartCount();
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.backgroundColor = '';
                button.disabled = false;
            }, 2000);
        } else {
            throw new Error(data.message || 'Failed to add product to cart');
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

function updateCartCount() {
    fetch('<?php echo SITE_URL; ?>/api/cart.php?action=count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartBadges = document.querySelectorAll('.cart-count');
            cartBadges.forEach(badge => {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            });
        }
    })
    .catch(error => {
        console.error('Failed to update cart count:', error);
    });
}

function showNotification(message, type = 'info') {
    // Create notification element
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
        font-weight: 500;
    `;
    
    // Set colors based on type
    const colors = {
        success: { bg: '#10b981', color: 'white' },
        error: { bg: '#ef4444', color: 'white' },
        info: { bg: '#3b82f6', color: 'white' },
        warning: { bg: '#f59e0b', color: 'white' }
    };
    
    const color = colors[type] || colors.info;
    notification.style.backgroundColor = color.bg;
    notification.style.color = color.color;
    
    // Set content
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle'
    };
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="${icons[type] || icons.info}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; font-size: 1.2rem; cursor: pointer; margin-left: auto;">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Add CSS for notification animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

function addToWishlist(productId) {
    // Add to wishlist functionality
    alert('Wishlist functionality coming soon!');
}
</script>

<?php include 'includes/footer.php'; ?>
