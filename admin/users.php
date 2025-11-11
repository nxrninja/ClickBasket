<?php
$page_title = 'Users Management - ClickBasket Admin';
require_once '../config/config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_status = intval($_POST['new_status'] ?? 0);
        
        try {
            $update_query = "UPDATE users SET is_active = ? WHERE id = ?";
            $stmt = $db->prepare($update_query);
            $stmt->execute([$new_status, $user_id]);
            handle_success($new_status ? 'User activated successfully!' : 'User deactivated successfully!');
        } catch (Exception $e) {
            handle_error('Failed to update user status.');
        }
    }
}

// Get users with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

try {
    $where_clause = "";
    $params = [];
    
    if ($search) {
        $where_clause = "WHERE name LIKE ? OR email LIKE ?";
        $params = ["%$search%", "%$search%"];
    }
    
    $users_query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($users_query);
    
    // Bind search parameters first
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
    // Then bind LIMIT and OFFSET as integers
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count_query = "SELECT COUNT(*) as total FROM users $where_clause";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_users / $limit);
    
    // Get statistics
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users
        FROM users";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $users = [];
    $total_users = 0;
    $total_pages = 0;
    $stats = ['total' => 0, 'active' => 0, 'verified' => 0, 'new_users' => 0];
    
    // Debug: Show error message
    $debug_error = $e->getMessage();
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
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
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
                <a href="users.php" class="nav-link active"><i class="fas fa-users"></i> Users</a>
                <a href="orders.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="categories.php" class="nav-link"><i class="fas fa-tags"></i> Categories</a>
                <a href="../index.php" class="nav-link"><i class="fas fa-external-link-alt"></i> View Website</a>
                <a href="logout.php" class="nav-link" style="color: var(--danger-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1 style="color: var(--text-primary); margin: 0;">Users Management</h1>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button onclick="toggleTheme()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-moon"></i>
                    </button>
                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search users..." class="form-control" style="width: 200px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        <?php if ($search): ?>
                            <a href="users.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div style="padding: 2rem;">
                <!-- Debug Information -->
                <?php if (isset($debug_error)): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 2rem;">
                        <h6 style="color: var(--danger-color); margin-bottom: 0.5rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Database Error
                        </h6>
                        <p style="color: var(--text-secondary); margin: 0; font-family: monospace; font-size: 0.875rem;">
                            <?php echo htmlspecialchars($debug_error); ?>
                        </p>
                        <p style="margin-top: 1rem; margin-bottom: 0;">
                            <a href="../test_users.php" class="btn btn-info btn-sm">
                                <i class="fas fa-tools"></i>
                                Test Database Connection
                            </a>
                            <a href="../setup.php" class="btn btn-warning btn-sm">
                                <i class="fas fa-database"></i>
                                Run Database Setup
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-users"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['total']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Total Users</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--success-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-user-check"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['active']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Active Users</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--info-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-certificate"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['verified']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Verified Users</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--warning-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-user-plus"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['new_users']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">New (30 days)</p>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 style="margin: 0;"><i class="fas fa-list"></i> Users (<?php echo number_format($total_users); ?>)</h5>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-secondary);">
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">User</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Email</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Status</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Joined</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                        <?php if ($user['phone']): ?>
                                                            <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($user['phone']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                                <?php if ($user['is_verified']): ?>
                                                    <i class="fas fa-check-circle" style="color: var(--success-color); margin-left: 0.5rem;" title="Verified"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                                    <button type="submit" class="btn <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?> btn-sm" 
                                                            onclick="return confirm('<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> this user?')">
                                                        <i class="fas <?php echo $user['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                        <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                            <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem;"></i><br>
                                            <?php if ($search): ?>
                                                No users found matching your search.
                                            <?php else: ?>
                                                No users found in the database.
                                                <br><br>
                                                <div style="margin-top: 1rem;">
                                                    <a href="../test_users.php" class="btn btn-info btn-sm">
                                                        <i class="fas fa-tools"></i>
                                                        Test Database & Create Sample User
                                                    </a>
                                                    <a href="../register.php" class="btn btn-primary btn-sm" target="_blank">
                                                        <i class="fas fa-user-plus"></i>
                                                        Register New User
                                                    </a>
                                                </div>
                                            <?php endif; ?>
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
                        $query_params = $search ? "search=" . urlencode($search) . "&" : "";
                        for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): 
                        ?>
                            <a href="?<?php echo $query_params; ?>page=<?php echo $i; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm" 
                               style="margin: 0 0.25rem;"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/admin-theme.js"></script>
</body>
</html>
