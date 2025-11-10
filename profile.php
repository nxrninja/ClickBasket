<?php
$page_title = 'My Profile - ClickBasket';
$mobile_title = 'Profile';

require_once 'config/config.php';
require_once 'classes/User.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=profile.php');
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Get current user data
$user->getUserById(get_current_user_id());

$errors = [];
$success = false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                // Validate profile update
                if (empty($_POST['name']) || strlen(trim($_POST['name'])) < 2) {
                    $errors['name'] = 'Name must be at least 2 characters long';
                }
                
                if (empty($errors)) {
                    $user->name = $_POST['name'];
                    $user->phone = $_POST['phone'] ?? '';
                    
                    if ($user->updateProfile()) {
                        $_SESSION['user_name'] = $user->name;
                        handle_success('Profile updated successfully!');
                    } else {
                        $errors['general'] = 'Failed to update profile. Please try again.';
                    }
                }
                break;
                
            case 'change_password':
                // Validate password change
                if (empty($_POST['current_password'])) {
                    $errors['current_password'] = 'Current password is required';
                }
                
                if (empty($_POST['new_password']) || strlen($_POST['new_password']) < 6) {
                    $errors['new_password'] = 'New password must be at least 6 characters long';
                }
                
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    $errors['confirm_password'] = 'Passwords do not match';
                }
                
                if (empty($errors)) {
                    // Verify current password
                    $temp_user = new User($db);
                    $temp_user->email = $user->email;
                    $temp_user->password = $_POST['current_password'];
                    
                    if ($temp_user->login()) {
                        if ($user->changePassword($_POST['new_password'])) {
                            handle_success('Password changed successfully!');
                        } else {
                            $errors['general'] = 'Failed to change password. Please try again.';
                        }
                    } else {
                        $errors['current_password'] = 'Current password is incorrect';
                    }
                }
                break;
        }
    }
}

// Get user statistics
try {
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status = 'completed') as total_orders,
        (SELECT COUNT(*) FROM downloads WHERE user_id = ?) as total_downloads,
        (SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE user_id = ? AND payment_status = 'completed') as total_spent
    ";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([get_current_user_id(), get_current_user_id(), get_current_user_id()]);
    $user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user_stats = ['total_orders' => 0, 'total_downloads' => 0, 'total_spent' => 0];
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div style="width: 80px; height: 80px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 2rem; font-weight: bold;">
                        <?php echo strtoupper(substr($user->name, 0, 1)); ?>
                    </div>
                    <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($user->name); ?></h4>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($user->email); ?></p>
                    <span style="background: var(--success-color); color: white; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 0.75rem;">
                        <i class="fas fa-check-circle"></i>
                        Verified
                    </span>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 style="margin: 0;">
                        <i class="fas fa-chart-bar"></i>
                        Quick Stats
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-12 mb-3">
                            <h4 style="color: var(--primary-color); margin-bottom: 0.25rem;"><?php echo $user_stats['total_orders']; ?></h4>
                            <small style="color: var(--text-secondary);">Total Orders</small>
                        </div>
                        <div class="col-12 mb-3">
                            <h4 style="color: var(--success-color); margin-bottom: 0.25rem;"><?php echo $user_stats['total_downloads']; ?></h4>
                            <small style="color: var(--text-secondary);">Downloads</small>
                        </div>
                        <div class="col-12">
                            <h4 style="color: var(--secondary-color); margin-bottom: 0.25rem;"><?php echo format_currency($user_stats['total_spent']); ?></h4>
                            <small style="color: var(--text-secondary);">Total Spent</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="card mt-3 d-none d-md-block">
                <div class="card-body" style="padding: 0;">
                    <nav style="list-style: none; padding: 0; margin: 0;">
                        <a href="#profile-info" class="profile-nav-item active" onclick="showTab('profile-info', this)">
                            <i class="fas fa-user"></i>
                            Profile Information
                        </a>
                        <a href="#security" class="profile-nav-item" onclick="showTab('security', this)">
                            <i class="fas fa-lock"></i>
                            Security
                        </a>
                        <a href="<?php echo SITE_URL; ?>/orders.php" class="profile-nav-item">
                            <i class="fas fa-box"></i>
                            My Orders
                        </a>
                        <a href="<?php echo SITE_URL; ?>/downloads.php" class="profile-nav-item">
                            <i class="fas fa-download"></i>
                            Downloads
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Mobile Tab Navigation -->
            <div class="d-block d-md-none mb-3">
                <div class="card">
                    <div class="card-body" style="padding: 0.5rem;">
                        <div style="display: flex; gap: 0.5rem; overflow-x: auto;">
                            <button class="btn btn-sm btn-primary" onclick="showTab('profile-info', this)">
                                <i class="fas fa-user"></i>
                                Profile
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="showTab('security', this)">
                                <i class="fas fa-lock"></i>
                                Security
                            </button>
                            <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-box"></i>
                                Orders
                            </a>
                            <a href="<?php echo SITE_URL; ?>/downloads.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-download"></i>
                                Downloads
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Information Tab -->
            <div id="profile-info" class="profile-tab active">
                <div class="card">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-user"></i>
                            Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors['general']) && $_POST['action'] === 'update_profile'): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['general']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-user"></i>
                                            Full Name
                                        </label>
                                        <input 
                                            type="text" 
                                            id="name" 
                                            name="name" 
                                            class="form-control <?php echo isset($errors['name']) ? 'error' : ''; ?>" 
                                            value="<?php echo htmlspecialchars($user->name); ?>"
                                            required
                                        >
                                        <?php if (isset($errors['name'])): ?>
                                            <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                                                <?php echo htmlspecialchars($errors['name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope"></i>
                                            Email Address
                                        </label>
                                        <input 
                                            type="email" 
                                            id="email" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($user->email); ?>"
                                            disabled
                                        >
                                        <small style="color: var(--text-muted);">Email cannot be changed</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">
                                            <i class="fas fa-phone"></i>
                                            Phone Number
                                        </label>
                                        <input 
                                            type="tel" 
                                            id="phone" 
                                            name="phone" 
                                            class="form-control" 
                                            value="<?php echo htmlspecialchars($user->phone ?? ''); ?>"
                                        >
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calendar"></i>
                                            Member Since
                                        </label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            value="<?php echo date('F j, Y', strtotime($user->created_at)); ?>"
                                            disabled
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Tab -->
            <div id="security" class="profile-tab">
                <div class="card">
                    <div class="card-header">
                        <h5 style="margin: 0;">
                            <i class="fas fa-lock"></i>
                            Security Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors['general']) && $_POST['action'] === 'change_password'): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['general']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password" class="form-label">
                                    <i class="fas fa-key"></i>
                                    Current Password
                                </label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    class="form-control <?php echo isset($errors['current_password']) ? 'error' : ''; ?>" 
                                    required
                                >
                                <?php if (isset($errors['current_password'])): ?>
                                    <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                                        <?php echo htmlspecialchars($errors['current_password']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="new_password" class="form-label">
                                            <i class="fas fa-lock"></i>
                                            New Password
                                        </label>
                                        <input 
                                            type="password" 
                                            id="new_password" 
                                            name="new_password" 
                                            class="form-control <?php echo isset($errors['new_password']) ? 'error' : ''; ?>" 
                                            required
                                        >
                                        <?php if (isset($errors['new_password'])): ?>
                                            <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                                                <?php echo htmlspecialchars($errors['new_password']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">
                                            <i class="fas fa-lock"></i>
                                            Confirm New Password
                                        </label>
                                        <input 
                                            type="password" 
                                            id="confirm_password" 
                                            name="confirm_password" 
                                            class="form-control <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" 
                                            required
                                        >
                                        <?php if (isset($errors['confirm_password'])): ?>
                                            <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                                                <?php echo htmlspecialchars($errors['confirm_password']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Change Password
                                </button>
                            </div>
                        </form>

                        <hr style="margin: 2rem 0;">

                        <!-- Account Actions -->
                        <h6 style="color: var(--text-primary); margin-bottom: 1rem;">Account Actions</h6>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-secondary">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                            <button class="btn btn-danger" onclick="confirmDeleteAccount()">
                                <i class="fas fa-trash"></i>
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-nav-item {
    display: block;
    padding: 1rem;
    color: var(--text-primary);
    text-decoration: none;
    border-bottom: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.profile-nav-item:hover,
.profile-nav-item.active {
    background: var(--bg-secondary);
    color: var(--primary-color);
}

.profile-nav-item:last-child {
    border-bottom: none;
}

.profile-nav-item i {
    width: 20px;
    margin-right: 0.5rem;
}

.profile-tab {
    display: none;
}

.profile-tab.active {
    display: block;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.form-label i {
    color: var(--primary-color);
    width: 16px;
}

@media (max-width: 767px) {
    .profile-nav-item {
        padding: 0.75rem;
        font-size: 0.875rem;
    }
}
</style>

<script>
function showTab(tabId, element) {
    // Hide all tabs
    document.querySelectorAll('.profile-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all nav items
    document.querySelectorAll('.profile-nav-item, .btn').forEach(item => {
        item.classList.remove('active', 'btn-primary');
        if (item.classList.contains('btn')) {
            item.classList.add('btn-secondary');
        }
    });
    
    // Show selected tab
    document.getElementById(tabId).classList.add('active');
    
    // Add active class to clicked element
    element.classList.add('active');
    if (element.classList.contains('btn')) {
        element.classList.remove('btn-secondary');
        element.classList.add('btn-primary');
    }
}

function confirmDeleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        // Implement account deletion
        alert('Account deletion feature will be implemented in the admin panel.');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
