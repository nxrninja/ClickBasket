<?php
$page_title = 'Orders Management - ClickBasket Admin';
require_once '../config/config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';
        
        $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
        if ($order_id > 0 && in_array($new_status, $valid_statuses)) {
            try {
                $update_query = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $db->prepare($update_query);
                $stmt->execute([$new_status, $order_id]);
                handle_success("Order status updated to " . ucfirst($new_status));
            } catch (Exception $e) {
                handle_error('Failed to update order status.');
            }
        }
    }
}

// Get orders with pagination and filtering
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

try {
    $where_conditions = [];
    $params = [];
    
    if ($status_filter && $status_filter !== 'all') {
        $where_conditions[] = "o.order_status = ?";
        $params[] = $status_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $orders_query = "SELECT o.*, u.name as user_name, u.email as user_email,
                     COUNT(oi.id) as item_count
                     FROM orders o 
                     LEFT JOIN users u ON o.user_id = u.id 
                     LEFT JOIN order_items oi ON o.id = oi.order_id
                     $where_clause
                     GROUP BY o.id 
                     ORDER BY o.created_at DESC 
                     LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($orders_query);
    
    // Bind filter parameters first
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
    // Then bind LIMIT and OFFSET as integers
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(DISTINCT o.id) as total FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id $where_clause";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_orders / $limit);
    
    // Get statistics
    $stats_query = "SELECT 
        COUNT(*) as total_orders,
        SUM(final_amount) as total_revenue,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN final_amount ELSE 0 END) as monthly_revenue
        FROM orders";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $orders = [];
    $total_orders = 0;
    $total_pages = 0;
    $stats = ['total_orders' => 0, 'total_revenue' => 0, 'pending_orders' => 0, 'completed_orders' => 0, 'monthly_revenue' => 0];
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
        .admin-header { background: var(--bg-primary); border-bottom: 1px solid var(--border-color); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .sidebar-brand { padding: 1.5rem; border-bottom: 1px solid var(--border-color); text-align: center; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: var(--text-secondary); text-decoration: none; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: var(--bg-primary); border-radius: 0.5rem; padding: 1.5rem; border: 1px solid var(--border-color); text-align: center; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid #d97706; }
        .status-processing { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: 1px solid #2563eb; }
        .status-completed { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid #059669; }
        .status-cancelled { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid #dc2626; }
        .filter-controls { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
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
                <a href="orders.php" class="nav-link active"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="categories.php" class="nav-link"><i class="fas fa-tags"></i> Categories</a>
                <a href="../index.php" class="nav-link"><i class="fas fa-external-link-alt"></i> View Website</a>
                <a href="logout.php" class="nav-link" style="color: var(--danger-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1 style="color: var(--text-primary); margin: 0;">Orders Management</h1>
                <div class="filter-controls">
                    <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                        <select name="status" class="form-control" style="width: auto;" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' || !$status_filter ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search orders..." class="form-control" style="width: 200px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        <?php if ($search || $status_filter): ?>
                            <a href="orders.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div style="padding: 2rem;">
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-shopping-cart"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['total_orders']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Total Orders</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--success-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-rupee-sign"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo format_currency($stats['total_revenue']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Total Revenue</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--warning-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-clock"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['pending_orders']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Pending Orders</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--info-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-chart-line"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo format_currency($stats['monthly_revenue']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Monthly Revenue</p>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 style="margin: 0;"><i class="fas fa-list"></i> Orders (<?php echo number_format($total_orders); ?>)</h5>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-secondary);">
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Order #</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Customer</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Items</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Amount</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Status</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Date</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></strong>
                                                    <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($order['user_email'] ?? 'N/A'); ?></small>
                                                </div>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <strong><?php echo format_currency($order['final_amount']); ?></strong>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                                <br><small style="color: var(--text-muted);"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <select name="new_status" class="form-control" style="width: auto; font-size: 0.875rem;" onchange="if(confirm('Update order status?')) this.form.submit();">
                                                        <option value="">Change Status</option>
                                                        <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'disabled' : ''; ?>>Pending</option>
                                                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'disabled' : ''; ?>>Processing</option>
                                                        <option value="completed" <?php echo $order['order_status'] === 'completed' ? 'disabled' : ''; ?>>Completed</option>
                                                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'disabled' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                            <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem;"></i><br>
                                            <?php echo $search || $status_filter ? 'No orders found matching your criteria.' : 'No orders found.'; ?>
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
                        <?php 
                        $query_params = [];
                        if ($search) $query_params[] = "search=" . urlencode($search);
                        if ($status_filter) $query_params[] = "status=" . urlencode($status_filter);
                        $query_string = !empty($query_params) ? implode("&", $query_params) . "&" : "";
                        
                        for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): 
                        ?>
                            <a href="?<?php echo $query_string; ?>page=<?php echo $i; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm" 
                               style="margin: 0 0.25rem;"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
