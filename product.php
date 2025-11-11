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

// Get related products (with error handling)
try {
    $related_products = $product->getRelatedProducts($product_id, $product_data['category_id'] ?? 0, 4);
} catch (Exception $e) {
    error_log("Error getting related products: " . $e->getMessage());
    $related_products = [];
}

// Parse screenshots (handle missing column gracefully)
$screenshots = json_decode($product_data['screenshots'] ?? '[]', true) ?? [];

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
                             style="width: 100%; height: 400px; object-fit: contain; border-radius: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-secondary);">
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
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);" id="average-rating">
                            0.0
                        </div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Rating</div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="product-actions" style="margin-bottom: 2rem;">
                    <?php if (is_logged_in()): ?>
                        <!-- Primary Actions -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <button class="btn btn-success btn-lg" onclick="orderNow(<?php echo $product_id; ?>)">
                                <i class="fas fa-bolt"></i>
                                Order Now
                            </button>
                            <button class="btn btn-primary btn-lg" onclick="addToCart(<?php echo $product_id; ?>)">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                        </div>
                        
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

    <!-- Ratings and Reviews Section -->
    <div class="ratings-reviews-section" style="margin-top: 4rem;">
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">
                    <i class="fas fa-star"></i>
                    Ratings & Reviews
                </h3>
            </div>
            <div class="card-body">
                <!-- Rating Overview -->
                <div class="rating-overview" style="display: flex; gap: 2rem; margin-bottom: 2rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: 0.75rem;">
                    <div style="text-align: center; flex: 1;">
                        <div style="font-size: 3rem; font-weight: bold; color: var(--primary-color);" id="product-average-rating">
                            0.0
                        </div>
                        <div class="star-display" id="product-star-display" style="font-size: 1.5rem; color: #ffc107; margin: 0.5rem 0;">
                            ★★★★★
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.875rem;" id="product-rating-count">
                            0 reviews
                        </div>
                    </div>
                    <div style="flex: 2;">
                        <div class="rating-breakdown" id="rating-breakdown">
                            <!-- Rating bars will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Add Your Rating (for logged-in users) -->
                <?php if (is_logged_in()): ?>
                    <div class="add-rating-section" style="margin-bottom: 2rem; padding: 1.5rem; border: 2px dashed var(--border-color); border-radius: 0.75rem;">
                        <h4 style="color: var(--text-primary); margin-bottom: 1rem;">
                            <i class="fas fa-edit"></i>
                            Write a Review
                        </h4>
                        <form id="rating-form" style="display: none;">
                            <input type="hidden" id="product-id" value="<?php echo $product_id; ?>">
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Your Rating:</label>
                                <div class="star-rating" id="user-star-rating" style="font-size: 2rem; color: #ddd; cursor: pointer;">
                                    <span data-rating="1">★</span>
                                    <span data-rating="2">★</span>
                                    <span data-rating="3">★</span>
                                    <span data-rating="4">★</span>
                                    <span data-rating="5">★</span>
                                </div>
                                <input type="hidden" id="selected-rating" value="0">
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label for="review-title" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Review Title:</label>
                                <input type="text" id="review-title" maxlength="200" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem;" placeholder="Summarize your experience...">
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label for="review-text" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Your Review:</label>
                                <textarea id="review-text" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; resize: vertical;" placeholder="Tell others about your experience with this product..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Submit Review
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="hideRatingForm()">
                                Cancel
                            </button>
                        </form>
                        
                        <button id="show-rating-form-btn" class="btn btn-primary" onclick="showRatingForm()">
                            <i class="fas fa-star"></i>
                            Rate this Product
                        </button>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 1.5rem; background: var(--bg-secondary); border-radius: 0.75rem; margin-bottom: 2rem;">
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            <i class="fas fa-sign-in-alt"></i>
                            Login to write a review
                        </p>
                        <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">
                            Login
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Reviews List -->
                <div class="reviews-list" id="reviews-list">
                    <div class="loading-reviews" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading reviews...
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                     style="width: 100%; height: 200px; object-fit: contain; background: var(--bg-secondary);">
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
                if (data.count > 0) {
                    badge.style.display = 'flex';
                    badge.classList.add('animate');
                    setTimeout(() => badge.classList.remove('animate'), 600);
                } else {
                    badge.style.display = 'none';
                }
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


// Rating System Functions
let selectedRating = 0;

function showRatingForm() {
    document.getElementById('rating-form').style.display = 'block';
    document.getElementById('show-rating-form-btn').style.display = 'none';
}

function hideRatingForm() {
    document.getElementById('rating-form').style.display = 'none';
    document.getElementById('show-rating-form-btn').style.display = 'block';
    resetRatingForm();
}

function resetRatingForm() {
    selectedRating = 0;
    document.getElementById('selected-rating').value = '0';
    document.getElementById('review-title').value = '';
    document.getElementById('review-text').value = '';
    updateStarDisplay(document.getElementById('user-star-rating'), 0);
}

function updateStarDisplay(container, rating) {
    const stars = container.querySelectorAll('span');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.style.color = '#ffc107';
        } else {
            star.style.color = '#ddd';
        }
    });
}

function displayStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<span style="color: #ffc107;">★</span>';
        } else {
            stars += '<span style="color: #ddd;">★</span>';
        }
    }
    return stars;
}

// Star rating click handlers
document.addEventListener('DOMContentLoaded', function() {
    const starRating = document.getElementById('user-star-rating');
    if (starRating) {
        const stars = starRating.querySelectorAll('span');
        
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                selectedRating = index + 1;
                document.getElementById('selected-rating').value = selectedRating;
                updateStarDisplay(starRating, selectedRating);
            });
            
            star.addEventListener('mouseover', function() {
                updateStarDisplay(starRating, index + 1);
            });
        });
        
        starRating.addEventListener('mouseleave', function() {
            updateStarDisplay(starRating, selectedRating);
        });
    }
    
    // Rating form submission
    const ratingForm = document.getElementById('rating-form');
    if (ratingForm) {
        ratingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitRating();
        });
    }
    
    // Load ratings and reviews
    loadProductRatings();
});

function submitRating() {
    const productId = document.getElementById('product-id').value;
    const rating = document.getElementById('selected-rating').value;
    const title = document.getElementById('review-title').value;
    const text = document.getElementById('review-text').value;
    
    if (rating == 0) {
        showNotification('Please select a rating', 'error');
        return;
    }
    
    const submitBtn = document.querySelector('#rating-form button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitBtn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>/api/ratings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add_rating',
            product_id: productId,
            rating: rating,
            review_title: title,
            review_text: text
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Review submitted successfully!', 'success');
            hideRatingForm();
            loadProductRatings(); // Reload ratings
        } else {
            showNotification(data.message || 'Failed to submit review', 'error');
        }
    })
    .catch(error => {
        showNotification('Error submitting review', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function loadProductRatings() {
    const productId = <?php echo $product_id; ?>;
    
    fetch(`<?php echo SITE_URL; ?>/api/ratings.php?action=get_ratings&product_id=${productId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateRatingDisplay(data.ratings);
            displayReviews(data.reviews);
        }
    })
    .catch(error => {
        console.error('Error loading ratings:', error);
        document.getElementById('reviews-list').innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted);">Failed to load reviews</div>';
    });
}

function updateRatingDisplay(ratings) {
    // Update average rating in stats
    document.getElementById('average-rating').textContent = ratings.average_rating || '0.0';
    document.getElementById('product-average-rating').textContent = ratings.average_rating || '0.0';
    document.getElementById('product-rating-count').textContent = `${ratings.total_reviews} review${ratings.total_reviews !== 1 ? 's' : ''}`;
    
    // Update star display
    const starDisplay = document.getElementById('product-star-display');
    starDisplay.innerHTML = displayStars(Math.round(ratings.average_rating || 0));
    
    // Update rating breakdown
    const breakdown = document.getElementById('rating-breakdown');
    let breakdownHTML = '';
    
    for (let i = 5; i >= 1; i--) {
        const count = ratings.rating_breakdown[i] || 0;
        const percentage = ratings.total_reviews > 0 ? (count / ratings.total_reviews) * 100 : 0;
        
        breakdownHTML += `
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <span style="font-size: 0.875rem; color: var(--text-muted); width: 20px;">${i}★</span>
                <div style="flex: 1; height: 8px; background: var(--border-color); border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; background: #ffc107; width: ${percentage}%; transition: width 0.3s ease;"></div>
                </div>
                <span style="font-size: 0.875rem; color: var(--text-muted); width: 30px;">${count}</span>
            </div>
        `;
    }
    
    breakdown.innerHTML = breakdownHTML;
}

function displayReviews(reviews) {
    const reviewsList = document.getElementById('reviews-list');
    
    if (!reviews || reviews.length === 0) {
        reviewsList.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted);">No reviews yet. Be the first to review this product!</div>';
        return;
    }
    
    let reviewsHTML = '';
    
    reviews.forEach(review => {
        const reviewDate = new Date(review.created_at).toLocaleDateString();
        const isVerified = review.is_verified_purchase == 1;
        
        reviewsHTML += `
            <div class="review-item" style="border-bottom: 1px solid var(--border-color); padding: 1.5rem 0;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                            <strong style="color: var(--text-primary);">${escapeHtml(review.user_name)}</strong>
                            ${isVerified ? '<span style="background: var(--success-color); color: white; font-size: 0.75rem; padding: 0.125rem 0.5rem; border-radius: 1rem;">Verified Purchase</span>' : ''}
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="color: #ffc107; font-size: 1rem;">
                                ${displayStars(review.rating)}
                            </div>
                            <span style="color: var(--text-muted); font-size: 0.875rem;">${reviewDate}</span>
                        </div>
                    </div>
                </div>
                
                ${review.review_title ? `<h5 style="color: var(--text-primary); margin-bottom: 0.5rem;">${escapeHtml(review.review_title)}</h5>` : ''}
                
                ${review.review_text ? `<p style="color: var(--text-secondary); line-height: 1.6; margin: 0;">${escapeHtml(review.review_text)}</p>` : ''}
            </div>
        `;
    });
    
    reviewsList.innerHTML = reviewsHTML;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Order Now functionality
function orderNow(productId) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    button.disabled = true;
    
    // Add to cart first, then redirect to checkout
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
            // Redirect to checkout
            window.location.href = '<?php echo SITE_URL; ?>/checkout.php';
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


</script>

<?php include 'includes/footer.php'; ?>
