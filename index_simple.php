<?php
// Simplified index page for ClickBasket
$page_title = 'ClickBasket - E-commerce Store';
$mobile_title = 'ClickBasket';

// Try to load config safely
try {
    require_once 'config/config.php';
} catch (Exception $e) {
    // Fallback if config fails
    define('SITE_URL', 'http://pali.c0m.in/ClickBasket');
    define('SITE_NAME', 'ClickBasket');
    session_start();
}

// Simple database connection test
$products = [];
$categories = [];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Get some products
        $stmt = $db->prepare("SELECT * FROM products WHERE is_active = 1 LIMIT 8");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get categories
        $stmt = $db->prepare("SELECT * FROM categories WHERE is_active = 1 LIMIT 6");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Database error - continue with empty arrays
    $db_error = $e->getMessage();
}

// Simple header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: #6366f1; color: white; padding: 1rem; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links a { color: white; text-decoration: none; margin: 0 1rem; }
        .hero { background: white; padding: 3rem 0; text-align: center; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0; }
        .product-card { background: white; border-radius: 8px; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .categories { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; margin: 2rem 0; }
        .category { background: #6366f1; color: white; padding: 0.5rem 1rem; border-radius: 20px; text-decoration: none; }
        .btn { background: #6366f1; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 5px; text-decoration: none; display: inline-block; }
        .error { background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="nav">
                <div class="logo">🛒 ClickBasket</div>
                <div>
                    <a href="index.php">Home</a>
                    <a href="products.php">Products</a>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Hero Section -->
        <div class="hero">
            <h1>Welcome to ClickBasket</h1>
            <p>Your trusted e-commerce marketplace for digital products</p>
            <?php if (isset($db_error)): ?>
                <div class="error">
                    <strong>Database Connection Issue:</strong> <?php echo htmlspecialchars($db_error); ?>
                    <br><small>Please check database configuration in config/database.php</small>
                </div>
            <?php endif; ?>
        </div>

        <!-- Categories -->
        <?php if (!empty($categories)): ?>
            <h2>Shop by Category</h2>
            <div class="categories">
                <?php foreach ($categories as $category): ?>
                    <a href="products.php?category=<?php echo urlencode($category['slug'] ?? $category['id']); ?>" class="category">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="categories">
                <a href="products.php?category=fashion" class="category">Fashion</a>
                <a href="products.php?category=electronics" class="category">Electronics</a>
                <a href="products.php?category=beauty" class="category">Beauty</a>
                <a href="products.php?category=toys" class="category">Toys</a>
                <a href="products.php?category=furniture" class="category">Furniture</a>
                <a href="products.php?category=mobile" class="category">Mobile</a>
            </div>
        <?php endif; ?>

        <!-- Products -->
        <h2>Featured Products</h2>
        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)); ?>...</p>
                        <div style="margin-top: 1rem;">
                            <strong>₹<?php echo number_format($product['price'] ?? 0, 2); ?></strong>
                        </div>
                        <div style="margin-top: 1rem;">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>No products found.</strong> 
                <?php if (!isset($db_error)): ?>
                    This might be because there are no products in the database yet.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="hero">
            <h2>Ready to Start Shopping?</h2>
            <p>Browse our collection of amazing products</p>
            <a href="products.php" class="btn" style="font-size: 1.1rem;">Browse All Products</a>
        </div>
    </div>

    <!-- Debug Info -->
    <div style="background: #f8f9fa; padding: 1rem; margin-top: 2rem; font-size: 0.9rem; color: #666;">
        <div class="container">
            <strong>Debug Info:</strong>
            PHP Version: <?php echo phpversion(); ?> | 
            Time: <?php echo date('Y-m-d H:i:s'); ?> | 
            Products: <?php echo count($products); ?> | 
            Categories: <?php echo count($categories); ?>
            <?php if (defined('SITE_URL')): ?>
                | Site URL: <?php echo SITE_URL; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
