<?php
$page_title = 'Products - ClickBasket';
$mobile_title = 'Products';

require_once 'config/config.php';
require_once 'classes/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

// Get filters from URL
$filters = [
    'category' => $_GET['category'] ?? '',
    'search' => $_GET['search'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'sort' => $_GET['sort'] ?? 'newest'
];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = PRODUCTS_PER_PAGE;

// Get products and total count
$products = $product->getProducts($page, $limit, $filters);
$total_products = $product->getTotalProducts($filters);
$total_pages = ceil($total_products / $limit);

// Get categories for filter
try {
    $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Get price ranges
$price_ranges = Product::getPriceRanges($db);

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="text-center mb-4">
        <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
            <?php if (!empty($filters['search'])): ?>
                Search Results for "<?php echo htmlspecialchars($filters['search']); ?>"
            <?php elseif (!empty($filters['category'])): ?>
                <?php
                $category_name = '';
                foreach ($categories as $cat) {
                    if ($cat['slug'] === $filters['category']) {
                        $category_name = $cat['name'];
                        break;
                    }
                }
                echo htmlspecialchars($category_name ?: 'Products');
                ?>
            <?php else: ?>
                All Products
            <?php endif; ?>
        </h1>
        <p style="color: var(--text-secondary);">
            <?php echo $total_products; ?> product<?php echo $total_products !== 1 ? 's' : ''; ?> found
        </p>
    </div>

    <!-- Filter Navigation Bar -->
    <div class="filter-navbar" style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
        <form method="GET" id="filters-form" class="filter-form">
            <!-- Preserve search query -->
            <?php if (!empty($filters['search'])): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>">
            <?php endif; ?>
            
            <div class="filter-slots">
                <!-- Category Slot -->
                <div class="filter-slot">
                    <label class="filter-label">
                        <i class="fas fa-th-large"></i>
                        Category
                    </label>
                    <select name="category" class="filter-select" onchange="submitFilters()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['slug']); ?>" 
                                    <?php echo $filters['category'] === $category['slug'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price Range Slot -->
                <div class="filter-slot">
                    <label class="filter-label">
                        <i class="fas fa-rupee-sign"></i>
                        Price Range
                    </label>
                    <div class="price-inputs">
                        <input type="number" name="min_price" class="filter-input" 
                               placeholder="Min" value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                               min="0" step="0.01">
                        <span class="price-separator">-</span>
                        <input type="number" name="max_price" class="filter-input" 
                               placeholder="Max" value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                               min="0" step="0.01">
                    </div>
                </div>

                <!-- Sort Slot -->
                <div class="filter-slot">
                    <label class="filter-label">
                        <i class="fas fa-sort"></i>
                        Sort By
                    </label>
                    <select name="sort" class="filter-select" onchange="submitFilters()">
                        <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="popular" <?php echo $filters['sort'] === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="price_low" <?php echo $filters['sort'] === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $filters['sort'] === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>

                <!-- Action Buttons Slot -->
                <div class="filter-slot filter-actions">
                    <button type="submit" class="btn btn-primary filter-btn">
                        <i class="fas fa-search"></i>
                        Apply
                    </button>
                    <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-secondary filter-btn">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                </div>
            </div>

            <!-- Active Filters Display -->
            <?php if (!empty($filters['category']) || !empty($filters['min_price']) || !empty($filters['max_price']) || $filters['sort'] !== 'newest'): ?>
                <div class="active-filters">
                    <span class="active-filters-label">Active Filters:</span>
                    <?php if (!empty($filters['category'])): ?>
                        <?php
                        $category_name = '';
                        foreach ($categories as $cat) {
                            if ($cat['slug'] === $filters['category']) {
                                $category_name = $cat['name'];
                                break;
                            }
                        }
                        ?>
                        <span class="filter-tag">
                            <i class="fas fa-th-large"></i>
                            <?php echo htmlspecialchars($category_name); ?>
                            <a href="?<?php echo http_build_query(array_merge($filters, ['category' => ''])); ?>" class="remove-filter">×</a>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($filters['min_price']) || !empty($filters['max_price'])): ?>
                        <span class="filter-tag">
                            <i class="fas fa-rupee-sign"></i>
                            <?php 
                            if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
                                echo format_currency($filters['min_price']) . ' - ' . format_currency($filters['max_price']);
                            } elseif (!empty($filters['min_price'])) {
                                echo 'From ' . format_currency($filters['min_price']);
                            } else {
                                echo 'Up to ' . format_currency($filters['max_price']);
                            }
                            ?>
                            <a href="?<?php echo http_build_query(array_merge($filters, ['min_price' => '', 'max_price' => ''])); ?>" class="remove-filter">×</a>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($filters['sort'] !== 'newest'): ?>
                        <span class="filter-tag">
                            <i class="fas fa-sort"></i>
                            <?php 
                            echo match($filters['sort']) {
                                'popular' => 'Most Popular',
                                'price_low' => 'Price: Low to High',
                                'price_high' => 'Price: High to Low',
                                default => 'Newest First'
                            };
                            ?>
                            <a href="?<?php echo http_build_query(array_merge($filters, ['sort' => 'newest'])); ?>" class="remove-filter">×</a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Products Grid -->
    <div class="products-section">
            <!-- Search Bar (Mobile) -->
            <div class="d-block d-md-none mb-3">
                <form method="GET" class="search-form">
                    <?php if (!empty($filters['category'])): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($filters['category']); ?>">
                    <?php endif; ?>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Products Results -->
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product_item): ?>
                        <div class="product-card fade-in">
                            <?php 
                            $screenshots = json_decode($product_item['screenshots'] ?? '[]', true);
                            if (!empty($screenshots) && isset($screenshots[0])): 
                            ?>
                                <img src="<?php echo SITE_URL . '/' . $screenshots[0]; ?>" 
                                     alt="<?php echo htmlspecialchars($product_item['title']); ?>"
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image" style="background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                    <i class="fas fa-<?php 
                                        echo match($product_item['category_name']) {
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
                                    <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                                        <?php echo htmlspecialchars($product_item['category_name']); ?>
                                    </span>
                                </div>
                                
                                <h3 class="product-title"><?php echo htmlspecialchars($product_item['title']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($product_item['short_description']); ?></p>
                                
                                <div class="product-price"><?php echo format_currency($product_item['price']); ?></div>
                                
                                <div class="product-actions">
                                    <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $product_item['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                    <?php if (is_logged_in()): ?>
                                        <button class="btn btn-secondary btn-sm add-to-cart" 
                                                data-product-id="<?php echo $product_item['id']; ?>">
                                            <i class="fas fa-cart-plus"></i>
                                            Add to Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="text-center mt-5">
                        <nav>
                            <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo SITE_URL; ?>/products.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="btn btn-secondary btn-sm">
                                        <i class="fas fa-chevron-left"></i>
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="<?php echo SITE_URL; ?>/products.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo SITE_URL; ?>/products.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="btn btn-secondary btn-sm">
                                        Next
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <p style="color: var(--text-muted); margin-top: 1rem; font-size: 0.875rem;">
                                Showing <?php echo (($page - 1) * $limit) + 1; ?> to 
                                <?php echo min($page * $limit, $total_products); ?> of 
                                <?php echo $total_products; ?> products
                            </p>
                        </nav>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Products Found -->
                <div class="text-center" style="padding: 4rem 0;">
                    <i class="fas fa-search" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">No Products Found</h3>
                    <p style="color: var(--text-muted); margin-bottom: 2rem;">
                        <?php if (!empty($filters['search'])): ?>
                            No products match your search criteria. Try different keywords or clear filters.
                        <?php else: ?>
                            No products available in this category. Check back soon for new additions!
                        <?php endif; ?>
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                            <i class="fas fa-th-large"></i>
                            View All Products
                        </a>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-secondary">
                            <i class="fas fa-home"></i>
                            Back to Home
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function submitFilters() {
    document.getElementById('filters-form').submit();
}

// Auto-submit price filters after user stops typing
document.addEventListener('DOMContentLoaded', function() {
    let priceTimeout;
    const priceInputs = document.querySelectorAll('input[name="min_price"], input[name="max_price"]');
    
    priceInputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(priceTimeout);
            priceTimeout = setTimeout(() => {
                document.getElementById('filters-form').submit();
            }, 1500);
        });
    });
    
    // Add smooth scroll behavior for filter navbar
    const filterNavbar = document.querySelector('.filter-navbar');
    if (filterNavbar) {
        // Add scroll event listener for sticky behavior enhancement
        let lastScrollY = window.scrollY;
        
        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > lastScrollY && currentScrollY > 100) {
                // Scrolling down - hide navbar
                filterNavbar.style.transform = 'translateY(-100%)';
            } else {
                // Scrolling up - show navbar
                filterNavbar.style.transform = 'translateY(0)';
            }
            
            lastScrollY = currentScrollY;
        });
    }
    
    // Enhanced filter interactions
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Add visual feedback
            this.style.borderColor = 'var(--primary-color)';
            setTimeout(() => {
                this.style.borderColor = '';
            }, 300);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
