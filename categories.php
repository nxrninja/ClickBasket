<?php
$page_title = 'Categories - ClickBasket';
$mobile_title = 'Categories';

require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();

// Get all categories with product counts
try {
    $categories_query = "SELECT c.*, 
                         COUNT(p.id) as product_count,
                         AVG(p.price) as avg_price,
                         MIN(p.price) as min_price,
                         MAX(p.price) as max_price
                         FROM categories c
                         LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                         WHERE c.is_active = 1
                         GROUP BY c.id
                         ORDER BY c.name";
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Get featured products for each category
$featured_products = [];
if (!empty($categories)) {
    try {
        foreach ($categories as $category) {
            $featured_query = "SELECT * FROM products 
                              WHERE category_id = ? AND is_active = 1 
                              ORDER BY downloads_count DESC, created_at DESC 
                              LIMIT 3";
            $featured_stmt = $db->prepare($featured_query);
            $featured_stmt->execute([$category['id']]);
            $featured_products[$category['id']] = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $featured_products = [];
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="text-center mb-5">
        <h1 style="color: var(--text-primary); margin-bottom: 1rem;">
            <i class="fas fa-th-large"></i>
            Product Categories
        </h1>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
            Explore our comprehensive range of product categories. 
            From fashion to electronics, find everything you need in one place.
        </p>
    </div>

    <!-- Categories Overview -->
    <div class="row mb-5">
        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo count($categories); ?>
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Categories</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--success-color); margin-bottom: 0.5rem;">
                        <?php echo array_sum(array_column($categories, 'product_count')); ?>
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Total Products</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--secondary-color); margin-bottom: 0.5rem;">
                        <?php 
                        $all_prices = [];
                        foreach ($categories as $cat) {
                            if ($cat['min_price']) $all_prices[] = $cat['min_price'];
                        }
                        echo !empty($all_prices) ? format_currency(min($all_prices)) : '₹0';
                        ?>
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Starting From</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--info-color); margin-bottom: 0.5rem;">
                        <i class="fas fa-star"></i>
                        4.9
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Avg Rating</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Grid -->
    <?php if (!empty($categories)): ?>
        <div class="categories-list">
            <?php foreach ($categories as $index => $category): ?>
                <div class="category-section mb-5 fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <div class="card category-card">
                        <!-- Category Header -->
                        <div class="category-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 2rem;">
                            <div class="row align-center">
                                <div class="col-md-8">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                            <i class="fas fa-<?php 
                                                echo match($category['slug']) {
                                                    'fashion' => 'tshirt',
                                                    'mobile' => 'mobile-alt',
                                                    'beauty' => 'palette',
                                                    'electronics' => 'laptop',
                                                    'toys' => 'gamepad',
                                                    'furniture' => 'couch',
                                                    // Legacy support
                                                    'web-templates' => 'code',
                                                    'mobile-apps' => 'mobile-alt',
                                                    'graphics-design' => 'palette',
                                                    'software-tools' => 'tools',
                                                    'ebooks' => 'book',
                                                    default => 'folder'
                                                };
                                            ?>"></i>
                                        </div>
                                        <div>
                                            <h2 style="margin: 0; font-size: 1.75rem;"><?php echo htmlspecialchars($category['name']); ?></h2>
                                            <p style="margin: 0; opacity: 0.9; font-size: 1rem;"><?php echo htmlspecialchars($category['description']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center text-md-right">
                                    <div class="category-stats">
                                        <h3 style="margin-bottom: 0.5rem;"><?php echo $category['product_count']; ?></h3>
                                        <p style="margin: 0; opacity: 0.9;">Products Available</p>
                                        <?php if ($category['product_count'] > 0): ?>
                                            <div style="margin-top: 1rem; font-size: 0.875rem; opacity: 0.8;">
                                                <?php if ($category['min_price'] == $category['max_price']): ?>
                                                    <?php echo format_currency($category['min_price']); ?>
                                                <?php else: ?>
                                                    <?php echo format_currency($category['min_price']); ?> - <?php echo format_currency($category['max_price']); ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Featured Products -->
                        <?php if (!empty($featured_products[$category['id']])): ?>
                            <div class="card-body">
                                <div class="d-flex justify-between align-center mb-3">
                                    <h5 style="color: var(--text-primary); margin: 0;">Featured Products</h5>
                                    <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($category['slug']); ?>" 
                                       class="btn btn-primary btn-sm">
                                        View All
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                                
                                <div class="row">
                                    <?php foreach ($featured_products[$category['id']] as $product): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card product-preview">
                                                <?php 
                                                $product_screenshots = json_decode($product['screenshots'], true);
                                                if (!empty($product_screenshots) && isset($product_screenshots[0])): 
                                                ?>
                                                    <img src="<?php echo SITE_URL . '/' . $product_screenshots[0]; ?>" 
                                                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                                                         style="width: 100%; height: 150px; object-fit: contain; background: var(--bg-secondary); border-radius: 0.5rem 0.5rem 0 0;">
                                                <?php else: ?>
                                                    <div class="product-image" style="background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; height: 150px;">
                                                        <i class="fas fa-<?php 
                                                            echo match($category['slug']) {
                                                                'fashion' => 'tshirt',
                                                                'mobile' => 'mobile-alt',
                                                                'beauty' => 'palette',
                                                                'electronics' => 'laptop',
                                                                'toys' => 'gamepad',
                                                                'furniture' => 'couch',
                                                                // Legacy support
                                                                'web-templates' => 'code',
                                                                'mobile-apps' => 'mobile-alt',
                                                                'graphics-design' => 'palette',
                                                                'software-tools' => 'tools',
                                                                'ebooks' => 'book',
                                                                default => 'file'
                                                            };
                                                        ?>"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="card-body" style="padding: 1rem;">
                                                    <h6 style="color: var(--text-primary); margin-bottom: 0.5rem; line-height: 1.3;">
                                                        <?php echo htmlspecialchars(strlen($product['title']) > 40 ? substr($product['title'], 0, 40) . '...' : $product['title']); ?>
                                                    </h6>
                                                    <div class="d-flex justify-between align-center">
                                                        <span style="color: var(--primary-color); font-weight: bold;">
                                                            <?php echo format_currency($product['price']); ?>
                                                        </span>
                                                        <small style="color: var(--text-muted);">
                                                            <i class="fas fa-download"></i>
                                                            <?php echo number_format($product['downloads_count']); ?>
                                                        </small>
                                                    </div>
                                                    <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" 
                                                       class="btn btn-primary btn-sm btn-block mt-2">
                                                        View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card-body text-center" style="padding: 3rem;">
                                <i class="fas fa-box-open" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                                <h5 style="color: var(--text-secondary); margin-bottom: 1rem;">No Products Yet</h5>
                                <p style="color: var(--text-muted); margin-bottom: 2rem;">
                                    Products in this category are coming soon. Check back later!
                                </p>
                                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-secondary">
                                    <i class="fas fa-envelope"></i>
                                    Request Products
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Category Footer -->
                        <div class="card-footer" style="background: var(--bg-secondary);">
                            <div class="d-flex justify-between align-center">
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <small style="color: var(--text-muted);">
                                        <i class="fas fa-tag"></i>
                                        <?php echo $category['product_count']; ?> Products
                                    </small>
                                    <?php if ($category['avg_price']): ?>
                                        <small style="color: var(--text-muted);">
                                            <i class="fas fa-rupee-sign"></i>
                                            Avg: <?php echo format_currency($category['avg_price']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($category['slug']); ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-th-large"></i>
                                    Browse Category
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Call to Action -->
        <div class="card text-center" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; margin-top: 3rem;">
            <div class="card-body" style="padding: 3rem;">
                <h3 style="margin-bottom: 1rem;">Can't Find What You're Looking For?</h3>
                <p style="margin-bottom: 2rem; opacity: 0.9; max-width: 600px; margin-left: auto; margin-right: auto;">
                    We're constantly adding new categories and products. Let us know what you need, 
                    and we'll do our best to add it to our marketplace.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-envelope"></i>
                        Request Category
                    </a>
                    <a href="<?php echo SITE_URL; ?>/products.php" class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                        <i class="fas fa-search"></i>
                        Browse All Products
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- No Categories -->
        <div class="text-center" style="padding: 4rem 0;">
            <i class="fas fa-th-large" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">No Categories Available</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">
                Categories are being set up. Please check back soon!
            </p>
            <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-home"></i>
                Back to Home
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.category-card {
    border: none;
    box-shadow: var(--shadow-lg);
    border-radius: 1rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.category-header {
    border-radius: 1rem 1rem 0 0;
}

.product-preview {
    transition: all 0.3s ease;
    height: 100%;
}

.product-preview:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.product-preview .product-image {
    border-radius: 0.5rem 0.5rem 0 0;
}

.fade-in {
    animation: fadeInUp 0.6s ease-out;
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
    .category-header {
        padding: 1.5rem 1rem;
    }
    
    .category-header .row {
        flex-direction: column;
        text-align: center;
    }
    
    .category-header h2 {
        font-size: 1.5rem;
    }
    
    .category-stats {
        margin-top: 1rem;
    }
    
    .d-flex.justify-between {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .d-flex.justify-between .btn {
        width: 100%;
    }
}

.btn-block {
    width: 100%;
}
</style>

<script>
// Smooth scroll to category when coming from external links
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const element = document.querySelector(hash);
        if (element) {
            setTimeout(() => {
                element.scrollIntoView({ behavior: 'smooth' });
            }, 100);
        }
    }
});

// Category search functionality
function searchCategories() {
    const searchTerm = document.getElementById('categorySearch').value.toLowerCase();
    const categories = document.querySelectorAll('.category-section');
    
    categories.forEach(category => {
        const categoryName = category.querySelector('h2').textContent.toLowerCase();
        const categoryDesc = category.querySelector('p').textContent.toLowerCase();
        
        if (categoryName.includes(searchTerm) || categoryDesc.includes(searchTerm)) {
            category.style.display = 'block';
        } else {
            category.style.display = 'none';
        }
    });
}

// Add search functionality if needed
const searchHTML = `
<div class="card mb-4">
    <div class="card-body">
        <div style="display: flex; gap: 0.5rem;">
            <input type="text" id="categorySearch" class="form-control" 
                   placeholder="Search categories..." onkeyup="searchCategories()">
            <button class="btn btn-primary" onclick="searchCategories()">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
</div>
`;

// Uncomment to add search functionality
// document.querySelector('.categories-list').insertAdjacentHTML('beforebegin', searchHTML);
</script>

<?php include 'includes/footer.php'; ?>
