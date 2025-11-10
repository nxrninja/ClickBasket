<?php
require_once 'config/config.php';

// Get current page for navigation
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_title = $page_title ?? 'ClickBasket - Digital Products Store';

// Get categories for navigation
$nav_categories = [];
try {
    $database = new Database();
    $db = $database->getConnection();
    $nav_query = "SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY name";
    $nav_stmt = $db->prepare($nav_query);
    $nav_stmt->execute();
    $nav_categories = $nav_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $nav_categories = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="<?php echo $meta_description ?? 'ClickBasket - Your trusted e-commerce marketplace. Shop fashion, electronics, beauty, toys, furniture and more.'; ?>">
    <meta name="keywords" content="<?php echo $meta_keywords ?? 'ecommerce, fashion, electronics, beauty, toys, furniture, online shopping, marketplace'; ?>">
    
    <!-- Open Graph tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo $meta_description ?? 'Your trusted e-commerce marketplace'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- Additional head content -->
    <?php if (isset($additional_head)): ?>
        <?php echo $additional_head; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Mobile App Bar -->
    <div class="mobile-appbar">
        <div class="mobile-appbar-title">
            <?php echo $mobile_title ?? SITE_NAME; ?>
        </div>
        <div class="mobile-appbar-actions">
            <button class="theme-toggle btn-icon">
                <i class="fas fa-moon"></i>
            </button>
            <?php if (is_logged_in()): ?>
                <a href="cart.php" class="btn-icon" style="position: relative;">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" style="position: absolute; top: -5px; right: -5px; background: var(--danger-color); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center;">0</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Desktop Navigation -->
    <nav class="desktop-nav">
        <div class="container">
            <div class="navbar">
                <a href="<?php echo SITE_URL; ?>" class="navbar-brand">
                    <i class="fas fa-shopping-bag"></i>
                    <?php echo SITE_NAME; ?>
                </a>
                
                <!-- Mobile Navigation Toggle -->
                <button class="mobile-nav-toggle" onclick="toggleMobileNav()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="navbar-nav" id="navbar-nav">
                    <li><a href="<?php echo SITE_URL; ?>" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Home
                    </a></li>
                    
                    <!-- All Categories Link -->
                    <li><a href="<?php echo SITE_URL; ?>/categories.php" class="<?php echo $current_page === 'categories' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> All Categories
                    </a></li>
                </ul>
                
                <div class="navbar-actions">
                    <!-- Search -->
                    <form class="search-form" style="display: flex; gap: 0.5rem;">
                        <input type="text" name="q" placeholder="Search products..." class="form-control" style="width: 200px;">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <!-- Theme Toggle -->
                    <button class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <?php if (is_logged_in()): ?>
                        <!-- Cart -->
                        <a href="<?php echo SITE_URL; ?>/cart.php" class="btn btn-secondary btn-sm" style="position: relative;">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count" style="position: absolute; top: -5px; right: -5px; background: var(--danger-color); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center;">0</span>
                        </a>
                        
                        <!-- User Menu -->
                        <div class="dropdown">
                            <button class="dropdown-toggle btn btn-secondary btn-sm">
                                <i class="fas fa-user"></i>
                                Account
                            </button>
                            <div class="dropdown-menu">
                                <a href="<?php echo SITE_URL; ?>/profile.php" class="dropdown-item">
                                    <i class="fas fa-user-circle"></i> Profile
                                </a>
                                <a href="<?php echo SITE_URL; ?>/orders.php" class="dropdown-item">
                                    <i class="fas fa-box"></i> My Orders
                                </a>
                                <a href="<?php echo SITE_URL; ?>/wishlist.php" class="dropdown-item">
                                    <i class="fas fa-heart"></i> My Wishlist
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo SITE_URL; ?>/logout.php" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-secondary btn-sm">Login</a>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Category Navigation Bar -->
    <nav class="category-nav">
        <div class="container">
            <div class="category-navbar">
                <div class="category-nav-scroll">
                    <ul class="category-nav-list" id="category-nav-list">
                        <?php if (!empty($nav_categories)): ?>
                            <?php foreach ($nav_categories as $category): ?>
                                <li class="category-nav-item">
                                    <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo urlencode($category['slug']); ?>" 
                                       class="category-nav-link <?php echo (isset($_GET['category']) && $_GET['category'] === $category['slug']) ? 'active' : ''; ?>">
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
                                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Scroll buttons for better navigation -->
                <button class="category-scroll-btn category-scroll-left" onclick="scrollCategoryNav('left')">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="category-scroll-btn category-scroll-right" onclick="scrollCategoryNav('right')">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="mobile-content">
        <?php
        // Display flash messages
        $success_message = get_flash_message('success');
        $error_message = get_flash_message('error');
        $warning_message = get_flash_message('warning');
        $info_message = get_flash_message('info');
        
        if ($success_message): ?>
            <div class="container mt-3">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            </div>
        <?php endif;
        
        if ($error_message): ?>
            <div class="container mt-3">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
        <?php endif;
        
        if ($warning_message): ?>
            <div class="container mt-3">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($warning_message); ?>
                </div>
            </div>
        <?php endif;
        
        if ($info_message): ?>
            <div class="container mt-3">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <?php echo htmlspecialchars($info_message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Content Starts Here -->
        
        <!-- Navigation Overlay -->
        <div class="nav-overlay" id="nav-overlay" onclick="closeMobileNav()"></div>
        
        <script>
        // Mobile Navigation Functions
        function toggleMobileNav() {
            const navMenu = document.getElementById('navbar-nav');
            const overlay = document.getElementById('nav-overlay');
            const toggle = document.querySelector('.mobile-nav-toggle i');
            
            navMenu.classList.toggle('show');
            overlay.classList.toggle('show');
            
            // Toggle hamburger/close icon
            if (navMenu.classList.contains('show')) {
                toggle.className = 'fas fa-times';
            } else {
                toggle.className = 'fas fa-bars';
            }
        }
        
        function closeMobileNav() {
            const navMenu = document.getElementById('navbar-nav');
            const overlay = document.getElementById('nav-overlay');
            const toggle = document.querySelector('.mobile-nav-toggle i');
            
            navMenu.classList.remove('show');
            overlay.classList.remove('show');
            toggle.className = 'fas fa-bars';
        }
        
        // Close mobile nav on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileNav();
            }
        });
        
        // Enhanced navigation functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling for horizontal navigation
            const navbar = document.querySelector('.navbar-nav');
            if (navbar) {
                // Add scroll buttons for better UX on desktop
                let isScrolling = false;
                
                navbar.addEventListener('wheel', function(e) {
                    if (window.innerWidth > 768 && !isScrolling) {
                        e.preventDefault();
                        isScrolling = true;
                        
                        this.scrollLeft += e.deltaY;
                        
                        setTimeout(() => {
                            isScrolling = false;
                        }, 100);
                    }
                });
            }
            
            // Highlight active category based on current page
            const currentUrl = window.location.href;
            const navLinks = document.querySelectorAll('.category-nav-link');
            
            navLinks.forEach(link => {
                if (currentUrl.includes(link.getAttribute('href'))) {
                    link.classList.add('active');
                }
            });
            
            // Initialize category navigation scroll buttons
            updateCategoryScrollButtons();
        });
        
        // Category navigation scroll functionality
        function scrollCategoryNav(direction) {
            const scrollContainer = document.querySelector('.category-nav-scroll');
            const scrollAmount = 200;
            
            if (direction === 'left') {
                scrollContainer.scrollLeft -= scrollAmount;
            } else {
                scrollContainer.scrollLeft += scrollAmount;
            }
            
            setTimeout(updateCategoryScrollButtons, 100);
        }
        
        function updateCategoryScrollButtons() {
            const scrollContainer = document.querySelector('.category-nav-scroll');
            const leftBtn = document.querySelector('.category-scroll-left');
            const rightBtn = document.querySelector('.category-scroll-right');
            
            if (scrollContainer && leftBtn && rightBtn) {
                const isAtStart = scrollContainer.scrollLeft <= 0;
                const isAtEnd = scrollContainer.scrollLeft >= (scrollContainer.scrollWidth - scrollContainer.clientWidth);
                
                leftBtn.style.display = isAtStart ? 'none' : 'flex';
                rightBtn.style.display = isAtEnd ? 'none' : 'flex';
            }
        }
        
        // Update scroll buttons on window resize
        window.addEventListener('resize', updateCategoryScrollButtons);
        </script>
