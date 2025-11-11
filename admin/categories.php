<?php
$page_title = 'Categories Management - ClickBasket Admin';
require_once '../config/config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();
$errors = [];

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_category':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name)) {
                $errors['name'] = 'Category name is required';
            } else {
                try {
                    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
                    $insert_query = "INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, 1)";
                    $stmt = $db->prepare($insert_query);
                    $stmt->execute([$name, $slug, $description]);
                    handle_success('Category added successfully!');
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $errors['name'] = 'Category name already exists';
                    } else {
                        $errors['general'] = 'Failed to add category.';
                    }
                }
            }
            break;
            
        case 'update_category':
            $category_id = intval($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($category_id > 0 && !empty($name)) {
                try {
                    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
                    $update_query = "UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?";
                    $stmt = $db->prepare($update_query);
                    $stmt->execute([$name, $slug, $description, $category_id]);
                    handle_success('Category updated successfully!');
                } catch (Exception $e) {
                    $errors['general'] = 'Failed to update category.';
                }
            }
            break;
            
        case 'toggle_status':
            $category_id = intval($_POST['category_id'] ?? 0);
            $new_status = intval($_POST['new_status'] ?? 0);
            
            if ($category_id > 0) {
                try {
                    $update_query = "UPDATE categories SET is_active = ? WHERE id = ?";
                    $stmt = $db->prepare($update_query);
                    $stmt->execute([$new_status, $category_id]);
                    handle_success($new_status ? 'Category activated!' : 'Category deactivated!');
                } catch (Exception $e) {
                    $errors['general'] = 'Failed to update category status.';
                }
            }
            break;
    }
}

// Get categories
try {
    $categories_query = "SELECT c.*, COUNT(p.id) as product_count 
                        FROM categories c 
                        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                        GROUP BY c.id 
                        ORDER BY c.created_at DESC";
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_query = "SELECT 
        COUNT(*) as total_categories,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_categories,
        (SELECT COUNT(*) FROM products WHERE category_id IS NOT NULL AND is_active = 1) as categorized_products
        FROM categories";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $categories = [];
    $stats = ['total_categories' => 0, 'active_categories' => 0, 'categorized_products' => 0];
}

// Get category for editing
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    try {
        $edit_query = "SELECT * FROM categories WHERE id = ? LIMIT 1";
        $edit_stmt = $db->prepare($edit_query);
        $edit_stmt->execute([$edit_id]);
        $edit_category = $edit_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Ignore error
    }
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: var(--bg-primary); border-radius: 0.5rem; padding: 1.5rem; border: 1px solid var(--border-color); text-align: center; }
        .category-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; }
        .status-badge { padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 500; }
        .status-active { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .status-inactive { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
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
                <a href="categories.php" class="nav-link active"><i class="fas fa-tags"></i> Categories</a>
                <a href="../index.php" class="nav-link"><i class="fas fa-external-link-alt"></i> View Website</a>
                <a href="logout.php" class="nav-link" style="color: var(--danger-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1 style="color: var(--text-primary); margin: 0;">Categories Management</h1>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button onclick="toggleTheme()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button onclick="toggleAddForm()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </div>
            
            <div style="padding: 2rem;">
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-tags"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['total_categories']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Total Categories</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--success-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-check-circle"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['active_categories']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Active Categories</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--info-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-box"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['categorized_products']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Categorized Products</p>
                    </div>
                </div>

                <div class="row">
                    <!-- Add/Edit Form -->
                    <div class="col-md-4">
                        <div class="card" id="categoryForm" style="<?php echo !$edit_category ? 'display: none;' : ''; ?>">
                            <div class="card-header">
                                <h5 style="margin: 0;">
                                    <i class="fas <?php echo $edit_category ? 'fa-edit' : 'fa-plus'; ?>"></i>
                                    <?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="<?php echo $edit_category ? 'update_category' : 'add_category'; ?>">
                                    <?php if ($edit_category): ?>
                                        <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <label>Category Name</label>
                                        <input type="text" name="name" class="form-control" 
                                               value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" 
                                               required>
                                        <?php if (isset($errors['name'])): ?>
                                            <small style="color: var(--danger-color);"><?php echo $errors['name']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i>
                                            <?php echo $edit_category ? 'Update' : 'Add'; ?> Category
                                        </button>
                                        <?php if ($edit_category): ?>
                                            <a href="categories.php" class="btn btn-secondary">Cancel</a>
                                        <?php else: ?>
                                            <button type="button" onclick="toggleAddForm()" class="btn btn-secondary">Cancel</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Categories List -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 style="margin: 0;"><i class="fas fa-list"></i> Categories (<?php echo count($categories); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <div class="category-card">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div style="flex: 1;">
                                                    <h6 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                        <span class="status-badge <?php echo $category['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </h6>
                                                    <?php if ($category['description']): ?>
                                                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem; font-size: 0.875rem;">
                                                            <?php echo htmlspecialchars($category['description']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <small style="color: var(--text-muted);">
                                                        <i class="fas fa-box"></i> <?php echo $category['product_count']; ?> products • 
                                                        Created <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div style="display: flex; gap: 0.5rem; margin-left: 1rem;">
                                                    <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                        <input type="hidden" name="new_status" value="<?php echo $category['is_active'] ? 0 : 1; ?>">
                                                        <button type="submit" class="btn <?php echo $category['is_active'] ? 'btn-warning' : 'btn-success'; ?> btn-sm"
                                                                onclick="return confirm('<?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?> this category?')">
                                                            <i class="fas <?php echo $category['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        <i class="fas fa-tags" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                        <br>No categories found. Add your first category!
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/admin/assets/js/admin-theme.js"></script>
    <script>
        function toggleAddForm() {
            const form = document.getElementById('categoryForm');
            const isHidden = form.style.display === 'none';
            form.style.display = isHidden ? 'block' : 'none';
            
            if (isHidden) {
                form.querySelector('input[name="name"]').focus();
            }
        }
    </script>
</body>
</html>
