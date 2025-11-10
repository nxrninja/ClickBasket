<?php
$page_title = 'Sign Up - ClickBasket';
$mobile_title = 'Sign Up';

require_once 'config/config.php';
require_once 'classes/User.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $errors = User::validateRegistration($_POST);
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        
        // Check if email already exists
        $user->email = $_POST['email'];
        if ($user->emailExists()) {
            $errors['email'] = 'Email address is already registered';
        } else {
            // Set user properties
            $user->name = $_POST['name'];
            $user->email = $_POST['email'];
            $user->password = $_POST['password'];
            $user->phone = $_POST['phone'] ?? '';
            
            // Register user
            if ($user->register()) {
                handle_success('Registration successful! Please login to continue.', 'login.php');
            } else {
                $errors['general'] = 'Registration failed. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 400px; margin: 2rem auto; padding: 0 1rem;">
    <div class="card">
        <div class="card-header text-center">
            <h2 class="card-title">
                <i class="fas fa-user-plus" style="color: var(--primary-color);"></i>
                Create Account
            </h2>
            <p class="card-text">Join ClickBasket to access premium digital products</p>
        </div>
        
        <div class="card-body">
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" data-validate>
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
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        placeholder="Enter your full name"
                        required
                    >
                    <?php if (isset($errors['name'])): ?>
                        <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                            <?php echo htmlspecialchars($errors['name']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        placeholder="Enter your email address"
                        required
                    >
                    <?php if (isset($errors['email'])): ?>
                        <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                            <?php echo htmlspecialchars($errors['email']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">
                        <i class="fas fa-phone"></i>
                        Phone Number (Optional)
                    </label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                        placeholder="Enter your phone number"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control <?php echo isset($errors['password']) ? 'error' : ''; ?>" 
                        placeholder="Enter your password"
                        required
                    >
                    <?php if (isset($errors['password'])): ?>
                        <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                            <?php echo htmlspecialchars($errors['password']); ?>
                        </div>
                    <?php endif; ?>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">
                        Password must be at least 6 characters long
                    </small>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Confirm Password
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" 
                        placeholder="Confirm your password"
                        required
                    >
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                            <?php echo htmlspecialchars($errors['confirm_password']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" required style="margin: 0;">
                        <span style="font-size: 0.875rem; color: var(--text-secondary);">
                            I agree to the 
                            <a href="<?php echo SITE_URL; ?>/terms.php" style="color: var(--primary-color);">Terms of Service</a> 
                            and 
                            <a href="<?php echo SITE_URL; ?>/privacy.php" style="color: var(--primary-color);">Privacy Policy</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>
        </div>
        
        <div class="card-footer text-center">
            <p style="margin: 0; color: var(--text-secondary);">
                Already have an account? 
                <a href="<?php echo SITE_URL; ?>/login.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                    Sign In
                </a>
            </p>
        </div>
    </div>
</div>

<style>
.form-control.error {
    border-color: var(--danger-color);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.card {
    box-shadow: var(--shadow-lg);
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
    .container {
        margin: 1rem auto;
        padding: 0 0.5rem;
    }
    
    .card {
        border-radius: 0;
        border-left: none;
        border-right: none;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
