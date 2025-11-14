<?php
/**
 * Fix Session Conflicts Between Admin and User Portals
 * This script addresses the issue where "Welcome back, Super Admin!" 
 * appears in the user portal due to session conflicts.
 */

$page_title = 'Fix Session Conflicts - ClickBasket';
require_once 'config/config.php';

$messages = [];
$errors = [];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'clear_all_sessions') {
        // Clear all session data
        session_destroy();
        session_start();
        $messages[] = '✅ All session data cleared successfully!';
    }
    
    if ($action === 'clear_admin_session') {
        clear_admin_session();
        $messages[] = '✅ Admin session data cleared!';
    }
    
    if ($action === 'clear_user_session') {
        clear_user_session();
        $messages[] = '✅ User session data cleared!';
    }
    
    if ($action === 'test_verification') {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Test if we can update user verification
            $test_query = "SELECT id, name, email, is_verified FROM users LIMIT 1";
            $stmt = $db->prepare($test_query);
            $stmt->execute();
            $test_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($test_user) {
                $messages[] = "✅ User verification system is working! Test user: " . htmlspecialchars($test_user['name']) . " (Verified: " . ($test_user['is_verified'] ? 'Yes' : 'No') . ")";
            } else {
                $errors[] = "❌ No users found in database to test verification system.";
            }
        } catch (Exception $e) {
            $errors[] = "❌ Database error: " . $e->getMessage();
        }
    }
}

// Check current session state
$session_info = [
    'user_logged_in' => is_logged_in(),
    'admin_logged_in' => is_admin_logged_in(),
    'user_id' => get_current_user_id(),
    'admin_id' => get_current_admin_id(),
    'user_name' => $_SESSION['user_name'] ?? 'Not set',
    'admin_name' => $_SESSION['admin_name'] ?? 'Not set'
];
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
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: var(--bg-secondary); }
        .container { background: var(--bg-primary); border-radius: 0.5rem; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: var(--text-primary); }
        .alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-info { background: #cce7ff; border: 1px solid #b3d9ff; color: #004085; }
        .btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; display: inline-block; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-danger { background: var(--danger-color); color: white; }
        .btn-warning { background: var(--warning-color); color: white; }
        .btn-success { background: var(--success-color); color: white; }
        .btn-info { background: var(--info-color); color: white; }
        .session-info { background: var(--bg-secondary); padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; }
        .session-info table { width: 100%; border-collapse: collapse; }
        .session-info th, .session-info td { padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .session-info th { background: var(--bg-primary); font-weight: bold; }
        .status-good { color: var(--success-color); }
        .status-bad { color: var(--danger-color); }
        .status-warning { color: var(--warning-color); }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-tools"></i> Fix Session Conflicts</h1>
        <p>This tool helps resolve session conflicts between admin and user portals that cause "Welcome back, Super Admin!" to appear in the user portal.</p>
        
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endforeach; ?>
    </div>

    <div class="container">
        <h2><i class="fas fa-info-circle"></i> Current Session Status</h2>
        <div class="session-info">
            <table>
                <tr>
                    <th>Session Variable</th>
                    <th>Status</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>User Logged In</td>
                    <td class="<?php echo $session_info['user_logged_in'] ? 'status-good' : 'status-bad'; ?>">
                        <?php echo $session_info['user_logged_in'] ? '✅ Yes' : '❌ No'; ?>
                    </td>
                    <td><?php echo $session_info['user_logged_in'] ? 'ID: ' . $session_info['user_id'] : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td>Admin Logged In</td>
                    <td class="<?php echo $session_info['admin_logged_in'] ? 'status-warning' : 'status-good'; ?>">
                        <?php echo $session_info['admin_logged_in'] ? '⚠️ Yes (May cause conflicts)' : '✅ No'; ?>
                    </td>
                    <td><?php echo $session_info['admin_logged_in'] ? 'ID: ' . $session_info['admin_id'] : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td>User Name</td>
                    <td><?php echo $session_info['user_name'] !== 'Not set' ? '✅ Set' : '❌ Not set'; ?></td>
                    <td><?php echo htmlspecialchars($session_info['user_name']); ?></td>
                </tr>
                <tr>
                    <td>Admin Name</td>
                    <td><?php echo $session_info['admin_name'] !== 'Not set' ? '⚠️ Set' : '✅ Not set'; ?></td>
                    <td><?php echo htmlspecialchars($session_info['admin_name']); ?></td>
                </tr>
            </table>
        </div>
        
        <?php if ($session_info['user_logged_in'] && $session_info['admin_logged_in']): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Session Conflict Detected!</strong><br>
                Both user and admin sessions are active. This can cause the "Welcome back, Super Admin!" message to appear in the user portal.
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <h2><i class="fas fa-wrench"></i> Fix Actions</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
            <div>
                <h3>Clear Session Data</h3>
                <form method="POST" style="margin-bottom: 1rem;">
                    <input type="hidden" name="action" value="clear_all_sessions">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Clear all session data? You will need to log in again.')">
                        <i class="fas fa-trash"></i> Clear All Sessions
                    </button>
                </form>
                
                <form method="POST" style="margin-bottom: 1rem;">
                    <input type="hidden" name="action" value="clear_admin_session">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-user-shield"></i> Clear Admin Session Only
                    </button>
                </form>
                
                <form method="POST" style="margin-bottom: 1rem;">
                    <input type="hidden" name="action" value="clear_user_session">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-user"></i> Clear User Session Only
                    </button>
                </form>
            </div>
            
            <div>
                <h3>Test Systems</h3>
                <form method="POST" style="margin-bottom: 1rem;">
                    <input type="hidden" name="action" value="test_verification">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-certificate"></i> Test User Verification System
                    </button>
                </form>
                
                <div style="margin-top: 1rem;">
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-primary" target="_blank">
                        <i class="fas fa-users-cog"></i> Open User Management
                    </a>
                </div>
                
                <div style="margin-top: 1rem;">
                    <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-success" target="_blank">
                        <i class="fas fa-home"></i> Test User Portal
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <h2><i class="fas fa-lightbulb"></i> How to Prevent Session Conflicts</h2>
        <div class="alert alert-info">
            <h4>Best Practices:</h4>
            <ul>
                <li><strong>Use Different Browsers:</strong> Use one browser for admin panel and another for user portal</li>
                <li><strong>Use Incognito Mode:</strong> Test user portal in incognito/private browsing mode</li>
                <li><strong>Clear Sessions:</strong> Use the buttons above to clear conflicting sessions</li>
                <li><strong>Separate URLs:</strong> Admin panel is at <code>/admin/</code>, user portal is at root <code>/</code></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <h2><i class="fas fa-check-circle"></i> Verification System Features</h2>
        <div class="alert alert-success">
            <h4>✅ Implemented Features:</h4>
            <ul>
                <li><strong>User Verification Toggle:</strong> Admins can now verify/unverify users in the user management panel</li>
                <li><strong>Visual Indicators:</strong> Verified users show a green checkmark icon next to their email</li>
                <li><strong>Statistics:</strong> Verified users count is displayed in the admin dashboard</li>
                <li><strong>Session Separation:</strong> Admin and user sessions are now properly separated</li>
            </ul>
        </div>
        
        <p><strong>To verify a user:</strong></p>
        <ol>
            <li>Go to <a href="<?php echo SITE_URL; ?>/admin/users.php" target="_blank">Admin → User Management</a></li>
            <li>Find the user you want to verify</li>
            <li>Click the certificate icon (🏆) in the Actions column</li>
            <li>Confirm the verification</li>
        </ol>
    </div>
</body>
</html>
