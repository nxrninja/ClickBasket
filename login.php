<?php
$page_title = 'Login - ClickBasket';
$mobile_title = 'Login';

require_once 'config/config.php';
require_once 'classes/User.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $errors = User::validateLogin($_POST);
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        
        // Set user properties
        $user->email = $_POST['email'];
        $user->password = $_POST['password'];
        
        // Attempt login
        if ($user->login()) {
            // Set session variables
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_email'] = $user->email;
            
            // Redirect to intended page or dashboard
            $redirect_url = $_GET['redirect'] ?? 'index.php';
            handle_success('Welcome back, ' . $user->name . '!', $redirect_url);
        } else {
            $errors['general'] = 'Invalid email or password';
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 400px; margin: 2rem auto; padding: 0 1rem;">
    <div class="card">
        <div class="card-header text-center">
            <h2 class="card-title">
                <i class="fas fa-sign-in-alt" style="color: var(--primary-color);"></i>
                Welcome Back
            </h2>
            <p class="card-text">Sign in to your ClickBasket account</p>
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
                        autofocus
                    >
                    <?php if (isset($errors['email'])): ?>
                        <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                            <?php echo htmlspecialchars($errors['email']); ?>
                        </div>
                    <?php endif; ?>
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
                </div>

                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="remember" style="margin: 0;">
                        <span style="font-size: 0.875rem; color: var(--text-secondary);">Remember me</span>
                    </label>
                    <a href="<?php echo SITE_URL; ?>/forgot-password.php" style="color: var(--primary-color); text-decoration: none; font-size: 0.875rem;">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
        </div>
        
        <div class="card-footer text-center">
            <p style="margin: 0; color: var(--text-secondary);">
                Don't have an account? 
                <a href="<?php echo SITE_URL; ?>/register.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                    Sign Up
                </a>
            </p>
        </div>
    </div>

    <!-- Social Login (Optional) -->
    <div class="text-center mt-4">
        <p style="color: var(--text-muted); margin-bottom: 1rem;">Or continue with</p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button class="btn btn-secondary" style="flex: 1; max-width: 120px;">
                <i class="fab fa-google"></i>
                Google
            </button>
            <button class="btn btn-secondary" style="flex: 1; max-width: 120px;">
                <i class="fab fa-facebook"></i>
                Facebook
            </button>
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
