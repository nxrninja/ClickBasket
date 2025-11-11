<?php
$page_title = 'Admin Dashboard - ClickBasket';
$mobile_title = 'Dashboard';

require_once '../config/config.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
try {
    // Total users
    $users_query = "SELECT COUNT(*) as total FROM users WHERE is_active = 1";
    $users_stmt = $db->prepare($users_query);
    $users_stmt->execute();
    $total_users = $users_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total products
    $products_query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    $products_stmt = $db->prepare($products_query);
    $products_stmt->execute();
    $total_products = $products_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total orders
    $orders_query = "SELECT COUNT(*) as total, SUM(final_amount) as revenue FROM orders";
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute();
    $orders_data = $orders_stmt->fetch(PDO::FETCH_ASSOC);
    $total_orders = $orders_data['total'];
    $total_revenue = $orders_data['revenue'] ?? 0;

    // Total downloads
    $downloads_query = "SELECT COUNT(*) as total FROM downloads";
    $downloads_stmt = $db->prepare($downloads_query);
    $downloads_stmt->execute();
    $total_downloads = $downloads_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent orders
    $recent_orders_query = "SELECT o.*, u.name as user_name, u.email as user_email 
                           FROM orders o 
                           LEFT JOIN users u ON o.user_id = u.id 
                           ORDER BY o.created_at DESC 
                           LIMIT 10";
    $recent_orders_stmt = $db->prepare($recent_orders_query);
    $recent_orders_stmt->execute();
    $recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent users
    $recent_users_query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 10";
    $recent_users_stmt = $db->prepare($recent_users_query);
    $recent_users_stmt->execute();
    $recent_users = $recent_users_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top products
    $top_products_query = "SELECT p.*, COUNT(oi.id) as order_count 
                          FROM products p 
                          LEFT JOIN order_items oi ON p.id = oi.product_id 
                          WHERE p.is_active = 1 
                          GROUP BY p.id 
                          ORDER BY order_count DESC, p.downloads_count DESC 
                          LIMIT 10";
    $top_products_stmt = $db->prepare($top_products_query);
    $top_products_stmt->execute();
    $top_products = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (Exception $e) {
    $total_users = 0;
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
    $total_downloads = 0;
    $recent_orders = [];
    $recent_users = [];
    $top_products = [];
}

// Admin helper functions are now in config.php
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
        /* Ensure CSS variables are loaded with fallbacks */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
        }
        
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --border-color: #334155;
        }
        
        body {
            background-color: var(--bg-secondary, #f8fafc) !important;
            color: var(--text-primary, #1e293b) !important;
            margin: 0;
            padding: 0;
        }
        
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
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .admin-sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        
        .admin-main {
            flex: 1;
            margin-left: 250px;
            background: var(--bg-secondary);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }
        
        .admin-main.expanded {
            margin-left: 0;
        }
        
        .admin-header {
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
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
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bg-primary);
            border-radius: 0.75rem;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
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
        
        .table-responsive {
            overflow-x: auto;
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
        
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-primary);
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-header {
                padding: 1rem;
            }
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border: 1px solid var(--warning-color);
        }
        
        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-brand">
                <h3 style="color: var(--primary-color); margin: 0;">
                    <i class="fas fa-shopping-basket"></i>
                    ClickBasket
                </h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.5rem 0 0;">Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                </div>
                <div class="nav-item">
                    <a href="orders.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                    </a>
                </div>
                <div class="nav-item">
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        Users
                    </a>
                </div>
                <div class="nav-item">
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-th-large"></i>
                        Categories
                    </a>
                </div>
                <div class="nav-item">
                    <a href="downloads.php" class="nav-link">
                        <i class="fas fa-download"></i>
                        Downloads
                    </a>
                </div>
                <div class="nav-item">
                    <a href="support.php" class="nav-link">
                        <i class="fas fa-headset"></i>
                        Support
                    </a>
                </div>
                <div class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </div>
                <div class="nav-item" style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                    <a href="<?php echo SITE_URL; ?>" class="nav-link">
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
        <div class="admin-main" id="adminMain">
            <!-- Header -->
            <div class="admin-header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 style="color: var(--text-primary); margin: 0;">Dashboard</h1>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button onclick="toggleTheme()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 40px; height: 40px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div style="font-weight: 500; color: var(--text-primary);"><?php echo htmlspecialchars(get_admin_name()); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo ucfirst(get_admin_role()); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div style="padding: 2rem;">
                <!-- Welcome Message -->
                <div style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: 0.75rem; padding: 2rem; margin-bottom: 2rem;">
                    <h2 style="margin-bottom: 0.5rem;">Welcome back, <?php echo htmlspecialchars(get_admin_name()); ?>!</h2>
                    <p style="margin: 0; opacity: 0.9;">Here's what's happening with your store today.</p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: var(--primary-color);">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo number_format($total_users); ?></h3>
                        <p style="color: var(--text-secondary); margin: 0;">Total Users</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: var(--success-color);">
                            <i class="fas fa-box"></i>
                        </div>
                        <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo number_format($total_products); ?></h3>
                        <p style="color: var(--text-secondary); margin: 0;">Total Products</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: var(--info-color);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo number_format($total_orders); ?></h3>
                        <p style="color: var(--text-secondary); margin: 0;">Total Orders</p>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: var(--secondary-color);">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo format_currency($total_revenue); ?></h3>
                        <p style="color: var(--text-secondary); margin: 0;">Total Revenue</p>
                    </div>
                </div>
                
                
                <!-- Data Tables Row -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="table-header">
                                <h5 style="margin: 0;">
                                    <i class="fas fa-clock"></i>
                                    Recent Orders
                                </h5>
                                <a href="orders.php" class="btn btn-primary btn-sm">View All</a>
                            </div>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent_orders)): ?>
                                            <?php foreach (array_slice($recent_orders, 0, 5) as $order): ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></td>
                                                    <td><?php echo format_currency($order['final_amount']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                                            <?php echo ucfirst($order['order_status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" style="text-align: center; color: var(--text-muted);">No recent orders</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="data-table">
                            <div class="table-header">
                                <h5 style="margin: 0;">
                                    <i class="fas fa-star"></i>
                                    Top Products
                                </h5>
                                <a href="products.php" class="btn btn-primary btn-sm">View All</a>
                            </div>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Orders</th>
                                            <th>Downloads</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($top_products)): ?>
                                            <?php foreach (array_slice($top_products, 0, 5) as $product): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(strlen($product['title']) > 30 ? substr($product['title'], 0, 30) . '...' : $product['title']); ?></td>
                                                    <td><?php echo format_currency($product['price']); ?></td>
                                                    <td><?php echo number_format($product['order_count']); ?></td>
                                                    <td><?php echo number_format($product['downloads_count']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" style="text-align: center; color: var(--text-muted);">No products found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Users -->
                <div class="data-table" style="margin-top: 2rem;">
                    <div class="table-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-user-plus"></i>
                            Recent Users
                        </h5>
                        <a href="users.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_users)): ?>
                                    <?php foreach (array_slice($recent_users, 0, 8) as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $user['is_active'] ? 'status-completed' : 'status-cancelled'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-muted);">No recent users</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dashboard-specific initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure body has proper background for dashboard
            document.body.style.backgroundColor = 'var(--bg-secondary)';
            document.body.style.color = 'var(--text-primary)';
        });

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const main = document.getElementById('adminMain');
            
            sidebar.classList.toggle('show');
        }


        // Auto-refresh dashboard data every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);
        
        

        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            // Update if there's a clock element
        }
        setInterval(updateClock, 10000);
    </script>
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/admin-theme.js"></script>
</body>
</html>
