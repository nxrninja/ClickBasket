<?php
$page_title = 'Support Management - ClickBasket Admin';
require_once '../config/config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle ticket status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_status') {
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';
        
        $valid_statuses = ['open', 'in_progress', 'resolved', 'closed'];
        if ($ticket_id > 0 && in_array($new_status, $valid_statuses)) {
            try {
                $update_query = "UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $db->prepare($update_query);
                $stmt->execute([$new_status, $ticket_id]);
                handle_success("Ticket status updated to " . ucfirst($new_status));
            } catch (Exception $e) {
                handle_error('Failed to update ticket status.');
            }
        }
    }
}

// Get support tickets with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';

try {
    $where_conditions = [];
    $params = [];
    
    if ($status_filter && $status_filter !== 'all') {
        $where_conditions[] = "st.status = ?";
        $params[] = $status_filter;
    }
    
    if ($priority_filter && $priority_filter !== 'all') {
        $where_conditions[] = "st.priority = ?";
        $params[] = $priority_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $tickets_query = "SELECT st.*, u.name as user_name, u.email as user_email
                     FROM support_tickets st 
                     LEFT JOIN users u ON st.user_id = u.id 
                     $where_clause
                     ORDER BY st.created_at DESC 
                     LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($tickets_query);
    
    // Bind filter parameters first
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
    // Then bind LIMIT and OFFSET as integers
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_query = "SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_tickets
        FROM support_tickets";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $tickets = [];
    $stats = ['total_tickets' => 0, 'open_tickets' => 0, 'in_progress_tickets' => 0, 'resolved_tickets' => 0, 'high_priority_tickets' => 0];
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
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500; text-transform: uppercase; }
        .status-open { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
        .status-in_progress { background: rgba(245, 158, 11, 0.1); color: #d97706; }
        .status-resolved { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .status-closed { background: rgba(107, 114, 128, 0.1); color: #6b7280; }
        .priority-high { color: var(--danger-color); font-weight: bold; }
        .priority-medium { color: var(--warning-color); }
        .priority-low { color: var(--text-muted); }
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
                <a href="downloads.php" class="nav-link"><i class="fas fa-download"></i> Downloads</a>
                <a href="support.php" class="nav-link active"><i class="fas fa-headset"></i> Support</a>
                <a href="../index.php" class="nav-link"><i class="fas fa-external-link-alt"></i> View Website</a>
                <a href="logout.php" class="nav-link" style="color: var(--danger-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1 style="color: var(--text-primary); margin: 0;">Support Management</h1>
                <div style="display: flex; gap: 0.5rem;">
                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="all">All Status</option>
                            <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <select name="priority" class="form-control" onchange="this.form.submit()">
                            <option value="all">All Priority</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </form>
                </div>
            </div>
            
            <div style="padding: 2rem;">
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-ticket-alt"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['total_tickets']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Total Tickets</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--info-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-folder-open"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['open_tickets']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Open Tickets</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--warning-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-clock"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['in_progress_tickets']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">In Progress</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--success-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-check-circle"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['resolved_tickets']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">Resolved</p>
                    </div>
                    <div class="stat-card">
                        <div style="color: var(--danger-color); font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i></div>
                        <h3 style="margin: 0.5rem 0;"><?php echo number_format($stats['high_priority_tickets']); ?></h3>
                        <p style="color: var(--text-muted); margin: 0;">High Priority</p>
                    </div>
                </div>

                <!-- Support Tickets Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 style="margin: 0;"><i class="fas fa-list"></i> Support Tickets</h5>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--bg-secondary);">
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Ticket #</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Subject</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Customer</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Priority</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Status</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Created</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tickets)): ?>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <strong>#<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <strong><?php echo htmlspecialchars($ticket['subject']); ?></strong>
                                                <?php if ($ticket['message']): ?>
                                                    <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars(substr($ticket['message'], 0, 80)); ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($ticket['user_name'] ?? $ticket['name'] ?? 'Guest'); ?></strong>
                                                    <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($ticket['user_email'] ?? $ticket['email'] ?? 'N/A'); ?></small>
                                                </div>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <span class="priority-<?php echo $ticket['priority']; ?>">
                                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                                    <?php echo ucfirst($ticket['priority']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <?php echo date('M j, Y', strtotime($ticket['created_at'])); ?>
                                                <br><small style="color: var(--text-muted);"><?php echo date('g:i A', strtotime($ticket['created_at'])); ?></small>
                                            </td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                    <select name="new_status" class="form-control" style="width: auto; font-size: 0.875rem;" onchange="this.form.submit();">
                                                        <option value="">Change Status</option>
                                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'disabled' : ''; ?>>Open</option>
                                                        <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'disabled' : ''; ?>>In Progress</option>
                                                        <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'disabled' : ''; ?>>Resolved</option>
                                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'disabled' : ''; ?>>Closed</option>
                                                    </select>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                            <i class="fas fa-headset" style="font-size: 3rem; margin-bottom: 1rem;"></i><br>
                                            No support tickets found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
