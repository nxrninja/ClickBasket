<?php
/**
 * Simple Category Update Script for ClickBasket
 * Updates categories to: Fashion, Mobile, Beauty, Electronics, Toys, Furniture
 */

$page_title = 'Update Categories - ClickBasket Admin';
require_once '../config/config.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();
$message = '';
$error = '';
$categories_updated = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_update'])) {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Backup existing categories
        $backup_query = "CREATE TABLE IF NOT EXISTS categories_backup_" . date('Y_m_d_H_i_s') . " AS SELECT * FROM categories";
        $db->exec($backup_query);
        
        // Clear existing categories
        $db->exec("DELETE FROM categories");
        
        // Reset auto increment
        $db->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
        
        // Prepare insert statement
        $insert_query = "INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        
        // Simple 6 categories as requested
        $simple_categories = [
            ['Fashion', 'fashion', 'Clothing, accessories, and fashion items for all ages', 1],
            ['Mobile', 'mobile', 'Smartphones, tablets, and mobile accessories', 1],
            ['Beauty', 'beauty', 'Cosmetics, skincare, and beauty products', 1],
            ['Electronics', 'electronics', 'Consumer electronics and gadgets', 1],
            ['Toys', 'toys', 'Toys and games for children and adults', 1],
            ['Furniture', 'furniture', 'Home and office furniture', 1]
        ];
        
        // Insert all categories
        foreach ($simple_categories as $category) {
            $stmt->execute($category);
        }
        
        // Commit transaction
        $db->commit();
        
        $categories_updated = true;
        $message = 'Successfully updated to 6 new categories: Fashion, Mobile, Beauty, Electronics, Toys, Furniture!';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        $error = 'Error updating categories: ' . $e->getMessage();
    }
}

// Get current category count
try {
    $count_query = "SELECT COUNT(*) as count FROM categories";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $current_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    $current_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: var(--bg-primary); border-right: 1px solid var(--border-color); position: fixed; height: 100vh; overflow-y: auto; }
        .admin-main { flex: 1; margin-left: 250px; background: var(--bg-secondary); }
        .admin-header { background: var(--bg-primary); border-bottom: 1px solid var(--border-color); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-brand { padding: 1.5rem; border-bottom: 1px solid var(--border-color); text-align: center; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: var(--text-secondary); text-decoration: none; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        .warning-box { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem; }
        .success-box { background: #d1edff; border: 1px solid #74b9ff; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem; }
        .error-box { background: #ffe0e0; border: 1px solid #ff7675; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem; }
        .category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0; }
        .category-item { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1.5rem; text-align: center; }
        .category-icon { font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="admin-sidebar">
            <div class="sidebar-brand">
                <h3 style="color: var(--primary-color); margin: 0;"><i class="fas fa-shopping-basket"></i> ClickBasket</h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.5rem 0 0;">Admin Panel</p>
            </div>
            <nav style="padding: 1rem 0;">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="products.php" class="nav-link"><i class="fas fa-box"></i> Products</a>
                <a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                <a href="orders.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="categories.php" class="nav-link"><i class="fas fa-tags"></i> Categories</a>
                <a href="../index.php" class="nav-link"><i class="fas fa-external-link-alt"></i> View Website</a>
                <a href="logout.php" class="nav-link" style="color: var(--danger-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1 style="color: var(--text-primary); margin: 0;">Update Categories</h1>
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Categories
                </a>
            </div>
            
            <div style="padding: 2rem;">
                <?php if ($message): ?>
                    <div class="success-box">
                        <h4 style="color: #00b894; margin-bottom: 0.5rem;"><i class="fas fa-check-circle"></i> Success!</h4>
                        <p style="margin: 0;"><?php echo htmlspecialchars($message); ?></p>
                        <div style="margin-top: 1rem;">
                            <a href="categories.php" class="btn btn-primary">View Updated Categories</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="error-box">
                        <h4 style="color: #e74c3c; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Error!</h4>
                        <p style="margin: 0;"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!$categories_updated): ?>
                    <div class="warning-box">
                        <h4 style="color: #f39c12; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Update Categories</h4>
                        <p><strong>This will replace ALL existing categories with 6 new categories.</strong></p>
                        <ul style="margin: 1rem 0;">
                            <li>Current categories: <strong><?php echo $current_count; ?></strong></li>
                            <li>New categories: <strong>6 categories (Fashion, Mobile, Beauty, Electronics, Toys, Furniture)</strong></li>
                            <li>Existing products will need to be reassigned to new categories</li>
                            <li>A backup will be created automatically</li>
                        </ul>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 style="margin: 0;"><i class="fas fa-list"></i> New Categories Preview</h5>
                        </div>
                        <div class="card-body">
                            <p>The following 6 categories will be created:</p>
                            <div class="category-grid">
                                <div class="category-item">
                                    <div class="category-icon"><i class="fas fa-tshirt"></i></div>
                                    <h6>Fashion</h6>
                                    <small>Clothing, accessories, and fashion items</small>
                                </div>
                                <div class="category-item">
                                    <div class="category-icon"><i class="fas fa-mobile-alt"></i></div>
                                    <h6>Mobile</h6>
                                    <small>Smartphones, tablets, and accessories</small>
                                </div>
                                <div class="category-item">
                                    <div class="category-icon"><i class="fas fa-palette"></i></div>
                                    <h6>Beauty</h6>
                                    <small>Cosmetics, skincare, and beauty products</small>
                                </div>
                                <div class="category-item">
                                    <div class="category-icon"><i class="fas fa-laptop"></i></div>
                                    <h6>Electronics</h6>
                                    <small>Consumer electronics and gadgets</small>
                                </div>
                                <div class="category-item">
                                    <div class="category-icon"><i class="fas fa-gamepad"></i></div>
                                    <h6>Toys</h6>
                                    <small>Toys and games for all ages</small>
                                </div>
                                <div class="category-item">
                                    <div class="category-icon"><i class="fas fa-couch"></i></div>
                                    <h6>Furniture</h6>
                                    <small>Home and office furniture</small>
                                </div>
                            </div>
                            
                            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to update all categories? This will replace existing categories!');">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <input type="checkbox" id="confirm_understand" required>
                                        <label for="confirm_understand">I understand this will replace all existing categories</label>
                                    </div>
                                    <button type="submit" name="confirm_update" class="btn btn-primary">
                                        <i class="fas fa-sync-alt"></i> Update to 6 Categories
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
