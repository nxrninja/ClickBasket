<?php
$page_title = 'Products Management - ClickBasket Admin';
$mobile_title = 'Products';

require_once '../config/config.php';
require_once '../classes/Product.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$errors = [];
$success = false;

// Check if is_active column exists (needed for queries)
$has_is_active = false;
try {
    $check_column_query = "SHOW COLUMNS FROM products LIKE 'is_active'";
    $check_stmt = $db->prepare($check_column_query);
    $check_stmt->execute();
    $has_is_active = $check_stmt->fetch() ? true : false;
} catch (Exception $e) {
    // Column check failed, assume it doesn't exist
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            // Add new product logic
            $title = trim($_POST['title'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $category_id = intval($_POST['category_id'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $short_description = trim($_POST['short_description'] ?? '');
            
            if (empty($title)) $errors['title'] = 'Title is required';
            if ($price <= 0) $errors['price'] = 'Valid price is required';
            if ($category_id <= 0) $errors['category_id'] = 'Category is required';
            
            // Handle image upload
            $uploaded_images = [];
            if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
                $upload_dir = '../' . PRODUCTS_DIR;
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['product_images']['name'] as $key => $filename) {
                    if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['product_images']['tmp_name'][$key];
                        $file_size = $_FILES['product_images']['size'][$key];
                        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        // Validate image
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (in_array($file_ext, $allowed_types) && $file_size <= MAX_FILE_SIZE) {
                            $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                $uploaded_images[] = PRODUCTS_DIR . $new_filename;
                            }
                        } else {
                            $errors['images'] = 'Invalid image format or size too large';
                        }
                    }
                }
            }
            
            if (empty($errors)) {
                try {
                    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $title));
                    $screenshots_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;
                    
                    if ($has_is_active) {
                        $insert_query = "INSERT INTO products (title, slug, description, short_description, price, category_id, screenshots, is_active) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                        $stmt = $db->prepare($insert_query);
                        $stmt->execute([$title, $slug, $description, $short_description, $price, $category_id, $screenshots_json]);
                    } else {
                        $insert_query = "INSERT INTO products (title, slug, description, short_description, price, category_id, screenshots) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($insert_query);
                        $stmt->execute([$title, $slug, $description, $short_description, $price, $category_id, $screenshots_json]);
                    }
                    handle_success('Product added successfully!');
                } catch (Exception $e) {
                    $errors['general'] = 'Failed to add product.';
                }
            }
            break;
            
        case 'delete_product':
            $product_id = intval($_POST['product_id'] ?? 0);
            if ($product_id > 0) {
                try {
                    if ($has_is_active) {
                        $delete_query = "UPDATE products SET is_active = 0 WHERE id = ?";
                    } else {
                        $delete_query = "DELETE FROM products WHERE id = ?";
                    }
                    $stmt = $db->prepare($delete_query);
                    $stmt->execute([$product_id]);
                    handle_success('Product deleted successfully!');
                } catch (Exception $e) {
                    $errors['general'] = 'Failed to delete product.';
                }
            }
            break;
    }
}

// Get products with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Check if is_active column exists
    $check_column_query = "SHOW COLUMNS FROM products LIKE 'is_active'";
    $check_stmt = $db->prepare($check_column_query);
    $check_stmt->execute();
    $has_is_active = $check_stmt->fetch();
    
    if ($has_is_active) {
        $products_query = "SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.is_active = 1 
                          ORDER BY p.created_at DESC 
                          LIMIT ? OFFSET ?";
        $count_query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    } else {
        $products_query = "SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          ORDER BY p.created_at DESC 
                          LIMIT ? OFFSET ?";
        $count_query = "SELECT COUNT(*) as total FROM products";
    }
    
    $products_stmt = $db->prepare($products_query);
    $products_stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $products_stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $products_stmt->execute();
    $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_products / $limit);
    
    // Get categories
    $check_cat_column_query = "SHOW COLUMNS FROM categories LIKE 'is_active'";
    $check_cat_stmt = $db->prepare($check_cat_column_query);
    $check_cat_stmt->execute();
    $cat_has_is_active = $check_cat_stmt->fetch();
    
    if ($cat_has_is_active) {
        $categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
    } else {
        $categories_query = "SELECT * FROM categories ORDER BY name";
    }
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $products = [];
    $categories = [];
    $total_products = 0;
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 250px;
            background: var(--bg-primary);
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 250px;
            background: var(--bg-secondary);
            min-height: 100vh;
        }
        
        .admin-header {
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin: 0.25rem 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .data-table {
            background: var(--bg-primary);
            border-radius: 0.75rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--bg-secondary);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: var(--bg-secondary);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-main {
                margin-left: 0;
            }
        }
        
        /* Image upload styles */
        .image-upload-container {
            position: relative;
        }
        
        .image-upload-container input[type="file"] {
            padding: 0.75rem;
            border: 2px dashed var(--border-color);
            border-radius: 0.5rem;
            background: var(--bg-secondary);
            transition: all 0.3s ease;
        }
        
        .image-upload-container input[type="file"]:hover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
        }
        
        .image-preview-container {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            background: var(--bg-secondary);
        }
        
        .preview-image {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 2px solid var(--border-color);
            background: var(--bg-primary);
        }
        
        .preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .preview-image .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }
        
        .preview-image .remove-image:hover {
            background: #dc2626;
        }
        
        .upload-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 120px;
            border: 2px dashed var(--border-color);
            border-radius: 0.5rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-placeholder:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .upload-placeholder i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-brand">
                <h3 style="color: var(--primary-color); margin: 0;">
                    <i class="fas fa-shopping-basket"></i>
                    ClickBasket
                </h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.5rem 0 0;">Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="products.php" class="nav-link active">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../index.php" class="nav-link">
                        <i class="fas fa-external-link-alt"></i>
                        View Website
                    </a>
                </div>
                <div class="nav-item">
                    <a href="logout.php" class="nav-link" style="color: var(--danger-color);">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <!-- Header -->
            <div class="admin-header">
                <h1 style="color: var(--text-primary); margin: 0;">Products Management</h1>
                <button onclick="toggleAddForm()" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add Product
                </button>
            </div>
            
            <!-- Content -->
            <div style="padding: 2rem;">
                <!-- Add Product Form -->
                <div id="addProductForm" class="card" style="display: none; margin-bottom: 2rem;">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-plus"></i>
                            Add New Product
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_product">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Product Title</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Price (₹)</label>
                                        <input type="number" name="price" step="0.01" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select name="category_id" class="form-control" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Short Description</label>
                                <input type="text" name="short_description" class="form-control" maxlength="500">
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Product Images</label>
                                <div class="image-upload-container">
                                    <input type="file" name="product_images[]" id="product_images" class="form-control" multiple accept="image/*">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Select multiple images (JPG, PNG, GIF, WebP). Max size: 50MB per image.
                                    </small>
                                    <?php if (isset($errors['images'])): ?>
                                        <div style="color: var(--danger-color); font-size: 0.875rem; margin-top: 0.5rem;">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo htmlspecialchars($errors['images']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div id="image-preview" class="image-preview-container" style="margin-top: 1rem; display: none;">
                                    <div class="preview-images" style="display: flex; flex-wrap: wrap; gap: 1rem;"></div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Save Product
                                </button>
                                <button type="button" onclick="toggleAddForm()" class="btn btn-secondary">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="data-table">
                    <div class="table-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-list"></i>
                            Products (<?php echo $total_products; ?>)
                        </h5>
                    </div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Downloads</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($products)): ?>
                                    <?php foreach ($products as $prod): ?>
                                        <tr>
                                            <td><?php echo $prod['id']; ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <?php 
                                                    $screenshots = json_decode($prod['screenshots'], true);
                                                    if (!empty($screenshots) && isset($screenshots[0])): 
                                                    ?>
                                                        <img src="<?php echo SITE_URL . '/' . $screenshots[0]; ?>" 
                                                             alt="Product image" 
                                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 0.25rem; border: 1px solid var(--border-color);">
                                                    <?php else: ?>
                                                        <div style="width: 40px; height: 40px; background: var(--bg-secondary); border-radius: 0.25rem; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
                                                            <i class="fas fa-image" style="color: var(--text-muted); font-size: 0.875rem;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($prod['title']); ?></strong>
                                                        <?php if ($prod['short_description']): ?>
                                                            <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars(substr($prod['short_description'], 0, 60)); ?>...</small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($screenshots)): ?>
                                                            <br><small style="color: var(--success-color);">
                                                                <i class="fas fa-images"></i>
                                                                <?php echo count($screenshots); ?> image<?php echo count($screenshots) > 1 ? 's' : ''; ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($prod['category_name'] ?? 'No Category'); ?></td>
                                            <td><strong><?php echo format_currency($prod['price']); ?></strong></td>
                                            <td><?php echo number_format($prod['downloads_count']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($prod['created_at'])); ?></td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <a href="../product.php?id=<?php echo $prod['id']; ?>" class="btn btn-info btn-sm" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this product?')">
                                                        <input type="hidden" name="action" value="delete_product">
                                                        <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                            <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                            <br>No products found. Add your first product!
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div style="text-align: center; margin-top: 2rem;">
                        <div style="display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>" class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleAddForm() {
            const form = document.getElementById('addProductForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Image preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('product_images');
            const previewContainer = document.getElementById('image-preview');
            const previewImages = previewContainer.querySelector('.preview-images');
            
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files);
                    previewImages.innerHTML = '';
                    
                    if (files.length > 0) {
                        previewContainer.style.display = 'block';
                        
                        files.forEach((file, index) => {
                            if (file.type.startsWith('image/')) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const previewDiv = document.createElement('div');
                                    previewDiv.className = 'preview-image';
                                    previewDiv.innerHTML = `
                                        <img src="${e.target.result}" alt="Preview ${index + 1}">
                                        <button type="button" class="remove-image" onclick="removePreviewImage(${index})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    `;
                                    previewImages.appendChild(previewDiv);
                                };
                                reader.readAsDataURL(file);
                            }
                        });
                    } else {
                        previewContainer.style.display = 'none';
                    }
                });
            }
        });
        
        function removePreviewImage(index) {
            const fileInput = document.getElementById('product_images');
            const dt = new DataTransfer();
            const files = Array.from(fileInput.files);
            
            files.forEach((file, i) => {
                if (i !== index) {
                    dt.items.add(file);
                }
            });
            
            fileInput.files = dt.files;
            fileInput.dispatchEvent(new Event('change'));
        }

        // Auto-refresh every 5 minutes
        setInterval(() => {
            if (!document.hidden) {
                location.reload();
            }
        }, 300000);
    </script>
</body>
</html>
