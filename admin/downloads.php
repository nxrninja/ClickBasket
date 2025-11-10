<?php
$page_title = 'Downloads Management - ClickBasket Admin';
require_once '../config/config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'reset_downloads') {
        $download_id = intval($_POST['download_id'] ?? 0);
        
        if ($download_id > 0) {
            try {
                $reset_query = "UPDATE downloads SET download_count = 0 WHERE id = ?";
                $stmt = $db->prepare($reset_query);
                $stmt->execute([$download_id]);
                handle_success('Download count reset successfully!');
            } catch (Exception $e) {
                handle_error('Failed to reset download count.');
            }
        }
    }
    
    if ($action === 'extend_expiry') {
        $download_id = intval($_POST['download_id'] ?? 0);
        $days = intval($_POST['extend_days'] ?? 30);
        
        if ($download_id > 0 && $days > 0) {
            try {
                $extend_query = "UPDATE downloads SET expires_at = DATE_ADD(expires_at, INTERVAL ? DAY) WHERE id = ?";
                $stmt = $db->prepare($extend_query);
                $stmt->execute([$days, $download_id]);
                handle_success("Download expiry extended by $days days!");
            } catch (Exception $e) {
                handle_error('Failed to extend download expiry.');
            }
        }
    }
}

// Get downloads with pagination and filtering
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

try {
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR p.title LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    }
    
    if ($status_filter) {
        switch ($status_filter) {
            case 'active':
                $where_conditions[] = "d.expires_at > NOW() AND d.download_count < d.max_downloads";
                break;
            case 'expired':
                $where_conditions[] = "d.expires_at <= NOW()";
                break;
            case 'exhausted':
                $where_conditions[] = "d.download_count >= d.max_downloads";
                break;
        }
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $downloads_query = "SELECT d.*, u.name as user_name, u.email as user_email, 
                       p.title as product_title, p.file_size, p.price,
                       CASE 
                           WHEN d.expires_at <= NOW() THEN 'expired'
                           WHEN d.download_count >= d.max_downloads THEN 'exhausted'
                           ELSE 'active'
                       END as status
                       FROM downloads d
                       LEFT JOIN users u ON d.user_id = u.id
                       LEFT JOIN products p ON d.product_id = p.id
                       $where_clause
                       ORDER BY d.created_at DESC
                       LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($downloads_query);
    
    // Bind filter parameters first
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
    // Then bind LIMIT and OFFSET as integers
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM downloads d
                    LEFT JOIN users u ON d.user_id = u.id
                    LEFT JOIN products p ON d.product_id = p.id
                    $where_clause";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_downloads = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_downloads / $limit);
    
    // Get statistics
    $stats_query = "SELECT 
        COUNT(*) as total_downloads,
        SUM(download_count) as total_download_count,
        COUNT(CASE WHEN expires_at > NOW() AND download_count < max_downloads THEN 1 END) as active_downloads,
        COUNT(CASE WHEN expires_at <= NOW() THEN 1 END) as expired_downloads,
        COUNT(CASE WHEN download_count >= max_downloads THEN 1 END) as exhausted_downloads,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_downloads
        FROM downloads";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get top downloaded products
    $top_products_query = "SELECT p.title, COUNT(d.id) as download_records, SUM(d.download_count) as total_downloads
                          FROM downloads d
                          JOIN products p ON d.product_id = p.id
                          GROUP BY p.id, p.title
                          ORDER BY total_downloads DESC
                          LIMIT 5";
    $top_products_stmt = $db->prepare($top_products_query);
    $top_products_stmt->execute();
    $top_products = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $downloads = [];
    $total_downloads = 0;
    $total_pages = 0;
    $stats = ['total_downloads' => 0, 'total_download_count' => 0, 'active_downloads' => 0, 'expired_downloads' => 0, 'exhausted_downloads' => 0, 'recent_downloads' => 0];
    $top_products = [];
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: var(--bg-primary); border-radius: 0.5rem; padding: 1.5rem; border: 1px solid var(--border-color); text-align: center; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; }
        .status-active { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid #059669; }
        .status-expired { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid #dc2626; }
        .status-exhausted { background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid #d97706; }
        .progress-bar { background: var(--bg-secondary); border-radius: 0.25rem; height: 8px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--primary-color); transition: width 0.3s ease; }
        .filter-controls { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .top-products { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; }
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
                <a href="downloads.php" class="nav-link active"><i class="fas fa-download"></i> Downloads</a>
                <a href="../index.php" class="nav-link"><i class="fas fa-external-link-alt"></i> View Website</a>
                <a href="logout.php" class="nav-link" style="color: var(--danger-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1 style="color: var(--text-primary); margin: 0;">Downloads Management</h1>
                <div class="filter-controls">
                    <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                        <select name="status" class="form-control" style="width: auto;" onchange="this.form.submit()">
                            <option value="" <?php echo !$status_filter ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="exhausted" <?php echo $status_filter === 'exhausted' ? 'selected' : ''; ?>>Exhausted</option>
                        </select>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search downloads..." class="form-control" style="width: 200px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        <?php if ($search || $status_filter): ?>
                            <a href="downloads.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div style="padding: 2rem;">
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-download"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['total_downloads']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Total Download Records</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--success-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-arrow-down"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['total_download_count']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Total Downloads</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--info-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-check-circle"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['active_downloads']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Active Downloads</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--danger-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-clock"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['expired_downloads']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Expired Downloads</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--warning-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-ban"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['exhausted_downloads']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Exhausted Downloads</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--secondary-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-calendar"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['recent_downloads']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Recent (30 days)</p>
                    </div>
                </div>

                <div class="row">
                    <!-- Downloads Table -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 style="margin: 0;"><i class="fas fa-list"></i> Downloads (<?php echo number_format($total_downloads); ?>)</h5>
                            </div>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background: var(--bg-secondary);">
                                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">User</th>
                                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Product</th>
                                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Progress</th>
                                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Status</th>
                                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Expires</th>
                                            <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($downloads)): ?>
                                            <?php foreach ($downloads as $download): ?>
                                                <?php 
                                                $progress_percent = $download['max_downloads'] > 0 ? ($download['download_count'] / $download['max_downloads']) * 100 : 0;
                                                $is_expired = strtotime($download['expires_at']) <= time();
                                                $is_exhausted = $download['download_count'] >= $download['max_downloads'];
                                                ?>
                                                <tr>
                                                    <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($download['user_name'] ?? 'Unknown'); ?></strong>
                                                            <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($download['user_email'] ?? 'N/A'); ?></small>
                                                        </div>
                                                    </td>
                                                    <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($download['product_title'] ?? 'Unknown Product'); ?></strong>
                                                            <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($download['file_size'] ?? 'N/A'); ?></small>
                                                        </div>
                                                    </td>
                                                    <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                        <div style="margin-bottom: 0.25rem;">
                                                            <small><?php echo $download['download_count']; ?> / <?php echo $download['max_downloads']; ?> downloads</small>
                                                        </div>
                                                        <div class="progress-bar">
                                                            <div class="progress-fill" style="width: <?php echo min(100, $progress_percent); ?>%;"></div>
                                                        </div>
                                                    </td>
                                                    <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                        <span class="status-badge status-<?php echo $download['status']; ?>">
                                                            <?php echo ucfirst($download['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                        <?php echo date('M j, Y', strtotime($download['expires_at'])); ?>
                                                        <br><small style="color: var(--text-muted);"><?php echo date('g:i A', strtotime($download['expires_at'])); ?></small>
                                                    </td>
                                                    <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                                            <?php if ($download['download_count'] > 0): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="reset_downloads">
                                                                    <input type="hidden" name="download_id" value="<?php echo $download['id']; ?>">
                                                                    <button type="submit" class="btn btn-warning btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="return confirm('Reset download count?')">
                                                                        <i class="fas fa-undo"></i> Reset
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($is_expired || $is_exhausted): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="extend_expiry">
                                                                    <input type="hidden" name="download_id" value="<?php echo $download['id']; ?>">
                                                                    <input type="hidden" name="extend_days" value="30">
                                                                    <button type="submit" class="btn btn-success btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="return confirm('Extend expiry by 30 days?')">
                                                                        <i class="fas fa-plus"></i> Extend
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                                    <i class="fas fa-download" style="font-size: 3rem; margin-bottom: 1rem;"></i><br>
                                                    <?php echo $search || $status_filter ? 'No downloads found matching your criteria.' : 'No downloads found.'; ?>
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

                    <!-- Top Products Sidebar -->
                    <div class="col-md-4">
                        <div class="top-products">
                            <h6 style="color: var(--text-primary); margin-bottom: 1rem;">
                                <i class="fas fa-trophy"></i>
                                Top Downloaded Products
                            </h6>
                            <?php if (!empty($top_products)): ?>
                                <?php foreach ($top_products as $index => $product): ?>
                                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                        <div style="width: 30px; height: 30px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.875rem;">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div style="flex: 1;">
                                            <div style="font-weight: 500; color: var(--text-primary); font-size: 0.875rem; margin-bottom: 0.25rem;">
                                                <?php echo htmlspecialchars(strlen($product['title']) > 25 ? substr($product['title'], 0, 25) . '...' : $product['title']); ?>
                                            </div>
                                            <small style="color: var(--text-muted);">
                                                <?php echo number_format($product['total_downloads']); ?> downloads • 
                                                <?php echo $product['download_records']; ?> users
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: var(--text-muted); text-align: center; margin: 2rem 0;">No download data available yet.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Stats -->
                        <div class="top-products" style="margin-top: 1rem;">
                            <h6 style="color: var(--text-primary); margin-bottom: 1rem;">
                                <i class="fas fa-chart-pie"></i>
                                Download Statistics
                            </h6>
                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Active Downloads</span>
                                    <span style="color: var(--success-color); font-weight: 500;"><?php echo number_format($stats['active_downloads']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Expired Downloads</span>
                                    <span style="color: var(--danger-color); font-weight: 500;"><?php echo number_format($stats['expired_downloads']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Exhausted Downloads</span>
                                    <span style="color: var(--warning-color); font-weight: 500;"><?php echo number_format($stats['exhausted_downloads']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Recent (30 days)</span>
                                    <span style="color: var(--info-color); font-weight: 500;"><?php echo number_format($stats['recent_downloads']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
