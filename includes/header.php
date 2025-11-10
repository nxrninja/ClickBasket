<?php
require_once 'config/config.php';

// Get current page for navigation
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_title = $page_title ?? 'ClickBasket - Digital Products Store';
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
    <meta name="description" content="<?php echo $meta_description ?? 'ClickBasket - Your trusted digital products marketplace. Download premium templates, apps, and digital resources.'; ?>">
    <meta name="keywords" content="<?php echo $meta_keywords ?? 'digital products, templates, apps, downloads, marketplace'; ?>">
    
    <!-- Open Graph tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo $meta_description ?? 'Your trusted digital products marketplace'; ?>">
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
                
                <ul class="navbar-nav">
                    <li><a href="<?php echo SITE_URL; ?>" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/products.php" class="<?php echo $current_page === 'products' ? 'active' : ''; ?>">Products</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/categories.php" class="<?php echo $current_page === 'categories' ? 'active' : ''; ?>">Categories</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php" class="<?php echo $current_page === 'contact' ? 'active' : ''; ?>">Contact</a></li>
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
                                <a href="<?php echo SITE_URL; ?>/downloads.php" class="dropdown-item">
                                    <i class="fas fa-download"></i> Downloads
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
