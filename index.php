<?php
$page_title = 'ClickBasket - Trending E-commerce Products';
$mobile_title = 'ClickBasket';
$meta_description = 'Discover trending products in fashion, electronics, beauty, toys, furniture and more. Shop the most popular items with instant delivery.';

require_once 'config/config.php';
require_once 'classes/Product.php';

// Get trending products and featured products
$database = new Database();
$db = $database->getConnection();

// Get trending products (based on orders and recent activity)
try {
    $trending_query = "SELECT p.*, c.name as category_name,
                       COALESCE((SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id), 0) as trending_score
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.is_active = 1 
                       ORDER BY trending_score DESC, p.created_at DESC 
                       LIMIT 12";
    $trending_stmt = $db->prepare($trending_query);
    $trending_stmt->execute();
    $trending_products = $trending_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $trending_products = [];
}

// Get featured products
try {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.is_active = 1 AND p.is_featured = 1 
              ORDER BY p.created_at DESC 
              LIMIT 8";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $featured_products = [];
}

// Get categories
try {
    $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

include 'includes/header.php';
?>

<!-- Trending Products Section -->
<section class="trending-products" style="padding: 3rem 0; background: var(--bg-primary);">
    <div class="container">
        <div class="text-center mb-5">
            <h1 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 2.5rem;">
                <i class="fas fa-fire" style="color: var(--secondary-color);"></i>
                Trending Products
            </h1>
            <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto; font-size: 1.1rem;">
                Discover the most popular products right now. These items are trending based on purchases and customer engagement.
            </p>
        </div>

        <?php if (!empty($trending_products)): ?>
            <div class="products-grid">
                <?php foreach ($trending_products as $product): ?>
                    <div class="product-card fade-in">
                        <?php 
                        $screenshots = json_decode($product['screenshots'] ?? '[]', true);
                        if (!empty($screenshots) && isset($screenshots[0])): 
                        ?>
                            <img src="<?php echo SITE_URL . '/' . $screenshots[0]; ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>"
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                <i class="fas fa-<?php 
                                    echo match($product['category_name']) {
                                        'Fashion' => 'tshirt',
                                        'Mobile' => 'mobile-alt',
                                        'Beauty' => 'palette',
                                        'Electronics' => 'laptop',
                                        'Toys' => 'gamepad',
                                        'Furniture' => 'couch',
                                        // Legacy support
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
                        
                        <div class="product-info">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <span style="background: var(--secondary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                                    <i class="fas fa-fire"></i> Trending
                                </span>
                            </div>
                            
                            <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['short_description']); ?></p>
                            
                            <div style="margin-top: auto;">
                                <div class="product-price" style="margin-bottom: 1rem;"><?php echo format_currency($product['price']); ?></div>
                                
                                <div class="product-actions">
                                    <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                    <?php if (is_logged_in()): ?>
                                        <button class="btn btn-secondary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-cart-plus"></i>
                                            Add to Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-th-large"></i>
                    View All Products
                </a>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 3rem 0;">
                <i class="fas fa-fire" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">No Trending Products Yet</h3>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">
                    Products will appear here as they gain popularity through purchases and customer engagement.
                </p>
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search"></i>
                    Browse All Products
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Stats Section -->
<section style="background: var(--bg-secondary); padding: 2rem 0;">
    <div class="container">
        <div class="row text-center">
            <div class="col-6 col-md-3">
                <div style="padding: 1rem;">
                    <h3 style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-box"></i>
                        1000+
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0;">Digital Products</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="padding: 1rem;">
                    <h3 style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-users"></i>
                        50K+
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0;">Happy Customers</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="padding: 1rem;">
                    <h3 style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-users"></i>
                        50K+
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0;">Happy Customers</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="padding: 1rem;">
                    <h3 style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-star"></i>
                        4.9
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0;">Average Rating</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<?php if (!empty($categories)): ?>
<section style="padding: 3rem 0;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">Browse Categories</h2>
            <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
                Find exactly what you're looking for in our carefully curated categories
            </p>
        </div>
        
        <div class="products-grid">
            <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($category['slug']); ?>" 
                   class="card" style="text-decoration: none; transition: all 0.3s ease;">
                    <div class="card-body text-center" style="padding: 2rem 1rem;">
                        <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="fas fa-<?php 
                                echo match($category['slug']) {
                                    'web-templates' => 'code',
                                    'mobile-apps' => 'mobile-alt',
                                    'graphics-design' => 'palette',
                                    'software-tools' => 'tools',
                                    'ebooks' => 'book',
                                    default => 'folder'
                                };
                            ?>" style="color: white; font-size: 1.5rem;"></i>
                        </div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($category['name']); ?></h4>
                        <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/categories.php" class="btn btn-secondary">
                <i class="fas fa-th"></i>
                View All Categories
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Products Section -->
<section style="background: var(--bg-secondary); padding: 3rem 0;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">Featured Products</h2>
            <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
                Hand-picked premium products from our top creators
            </p>
        </div>
        
        <?php if (!empty($featured_products)): ?>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <?php 
                        $screenshots = json_decode($product['screenshots'] ?? '[]', true);
                        if (!empty($screenshots) && isset($screenshots[0])): 
                        ?>
                            <img src="<?php echo SITE_URL . '/' . $screenshots[0]; ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>"
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                <i class="fas fa-<?php 
                                    echo match($product['category_name']) {
                                        'Fashion' => 'tshirt',
                                        'Mobile' => 'mobile-alt',
                                        'Beauty' => 'palette',
                                        'Electronics' => 'laptop',
                                        'Toys' => 'gamepad',
                                        'Furniture' => 'couch',
                                        // Legacy support
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
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['short_description']); ?></p>
                            <div class="product-price"><?php echo format_currency($product['price']); ?></div>
                            <div class="product-actions">
                                <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i>
                                    View Details
                                </a>
                                <?php if (is_logged_in()): ?>
                                    <button class="btn btn-secondary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-cart-plus"></i>
                                        Add to Cart
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 3rem 0;">
                <i class="fas fa-box-open" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">No Featured Products Yet</h3>
                <p style="color: var(--text-muted);">Check back soon for amazing featured products!</p>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                <i class="fas fa-th-large"></i>
                View All Products
            </a>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section style="padding: 3rem 0;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">How It Works</h2>
            <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
                Get started with ClickBasket in just a few simple steps
            </p>
        </div>
        
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <div style="width: 80px; height: 80px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fas fa-search" style="color: white; font-size: 2rem;"></i>
                </div>
                <h4 style="color: var(--text-primary); margin-bottom: 1rem;">1. Browse & Discover</h4>
                <p style="color: var(--text-secondary);">
                    Explore our vast collection of premium digital products across multiple categories
                </p>
            </div>
            
            <div class="col-md-4 text-center mb-4">
                <div style="width: 80px; height: 80px; background: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fas fa-credit-card" style="color: white; font-size: 2rem;"></i>
                </div>
                <h4 style="color: var(--text-primary); margin-bottom: 1rem;">2. Secure Purchase</h4>
                <p style="color: var(--text-secondary);">
                    Buy with confidence using our secure payment gateway with multiple payment options
                </p>
            </div>
            
            <div class="col-md-4 text-center mb-4">
                <div style="width: 80px; height: 80px; background: var(--success-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fas fa-star" style="color: white; font-size: 2rem;"></i>
                </div>
                <h4 style="color: var(--text-primary); margin-bottom: 1rem;">3. Rate & Review</h4>
                <p style="color: var(--text-secondary);">
                    Share your experience and help other customers make informed decisions
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section style="background: var(--bg-secondary); padding: 3rem 0;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">What Our Customers Say</h2>
            <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
                Join thousands of satisfied customers who trust ClickBasket
            </p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div style="color: var(--secondary-color); margin-bottom: 1rem;">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-style: italic;">
                            "Amazing quality products and instant downloads. ClickBasket has everything I need for my projects!"
                        </p>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 40px; height: 40px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                S
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Sarah Johnson</strong>
                                <div style="color: var(--text-muted); font-size: 0.875rem;">Web Designer</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div style="color: var(--secondary-color); margin-bottom: 1rem;">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-style: italic;">
                            "The mobile app templates saved me weeks of development time. Highly recommended!"
                        </p>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 40px; height: 40px; background: var(--success-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                M
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Mike Chen</strong>
                                <div style="color: var(--text-muted); font-size: 0.875rem;">App Developer</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div style="color: var(--secondary-color); margin-bottom: 1rem;">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-style: italic;">
                            "Excellent customer support and high-quality digital products. My go-to marketplace!"
                        </p>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 40px; height: 40px; background: var(--info-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                E
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Emily Davis</strong>
                                <div style="color: var(--text-muted); font-size: 0.875rem;">Graphic Designer</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; padding: 3rem 0;">
    <div class="container text-center">
        <h2 style="margin-bottom: 1rem;">Ready to Get Started?</h2>
        <p style="margin-bottom: 2rem; opacity: 0.9; max-width: 600px; margin-left: auto; margin-right: auto;">
            Join thousands of creators and businesses who trust ClickBasket for their digital product needs
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <?php if (!is_logged_in()): ?>
                <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-user-plus"></i>
                    Create Free Account
                </a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                <i class="fas fa-shopping-bag"></i>
                Start Shopping
            </a>
        </div>
    </div>
</section>

<style>
.hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.products-grid .card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

@media (max-width: 767px) {
    .hero-title {
        font-size: 1.75rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
