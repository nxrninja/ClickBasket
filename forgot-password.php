<?php
$page_title = 'Forgot Password - ClickBasket';
$mobile_title = 'Forgot Password';

require_once 'config/config.php';
require_once 'classes/User.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('profile.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // Validate input
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);
            
            // Check if user exists
            $query = "SELECT id, name, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_data) {
                // Generate reset token
                $reset_token = generate_token(32);
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store reset token
                $update_query = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$reset_token, $expires_at, $user_data['id']]);
                
                // In a real application, you would send an email here
                // For now, we'll just show the reset link (for development)
                $reset_link = SITE_URL . "/reset-password.php?token=" . $reset_token;
                
                $success = true;
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_link'] = $reset_link; // Only for development
            } else {
                // Don't reveal if email exists or not for security
                $success = true;
                $_SESSION['reset_email'] = $email;
            }
        } catch (Exception $e) {
            $errors['general'] = 'Failed to process request. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0; min-height: 70vh; display: flex; align-items: center; justify-content: center;">
    <div style="width: 100%; max-width: 500px;">
        <?php if (!$success): ?>
            <!-- Forgot Password Form -->
            <div class="card">
                <div class="card-header text-center" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; padding: 2rem;">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem;">
                        <i class="fas fa-key"></i>
                    </div>
                    <h2 style="margin-bottom: 0.5rem;">Forgot Password</h2>
                    <p style="margin: 0; opacity: 0.9;">Reset Your Account Password</p>
                </div>
                
                <div class="card-body" style="padding: 2rem;">
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['general']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-bottom: 2rem;">
                        <p style="color: var(--text-secondary); text-align: center; margin-bottom: 1rem;">
                            Enter your email address and we'll send you a link to reset your password.
                        </p>
                    </div>

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

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-paper-plane"></i>
                            Send Reset Link
                        </button>
                    </form>

                    <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid var(--info-color); border-radius: 0.5rem; padding: 1rem; margin-top: 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-info-circle" style="color: var(--info-color);"></i>
                            <strong style="color: var(--info-color);">Important Information</strong>
                        </div>
                        <ul style="color: var(--text-secondary); font-size: 0.875rem; margin: 0; padding-left: 1.5rem;">
                            <li>Reset links expire after 1 hour for security</li>
                            <li>Only registered and active accounts can request password resets</li>
                            <li>Check your spam folder if you don't receive the email</li>
                            <li>Contact support if you continue to have issues</li>
                        </ul>
                    </div>

                    <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                            <a href="login.php" style="color: var(--primary-color); text-decoration: none;">
                                <i class="fas fa-arrow-left"></i>
                                Back to Login
                            </a>
                            <span style="color: var(--text-muted);">|</span>
                            <a href="register.php" style="color: var(--primary-color); text-decoration: none;">
                                <i class="fas fa-user-plus"></i>
                                Create Account
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Success Message -->
            <div class="card">
                <div class="card-header text-center" style="background: linear-gradient(135deg, var(--success-color), var(--success-dark, #059669)); color: white; padding: 2rem;">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem;">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 style="margin-bottom: 0.5rem;">Email Sent</h2>
                    <p style="margin: 0; opacity: 0.9;">Check Your Inbox</p>
                </div>
                
                <div class="card-body" style="padding: 2rem;">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <i class="fas fa-envelope" style="font-size: 4rem; color: var(--success-color); margin-bottom: 1rem;"></i>
                        <h4 style="color: var(--success-color); margin-bottom: 1rem;">Reset Link Sent!</h4>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            If an account exists with the email <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>, 
                            you will receive a password reset link shortly.
                        </p>
                        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                            Please check your email and follow the instructions to reset your password.
                        </p>
                    </div>

                    <?php if (isset($_SESSION['reset_link'])): ?>
                        <!-- Development Only - Show Reset Link -->
                        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 2rem;">
                            <div style="color: var(--warning-color); margin-bottom: 0.5rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Development Mode Only</strong>
                            </div>
                            <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">
                                Since email is not configured, here's your reset link:
                            </p>
                            <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; word-break: break-all; font-family: monospace; font-size: 0.875rem;">
                                <a href="<?php echo $_SESSION['reset_link']; ?>" style="color: var(--primary-color);" id="resetLink">
                                    <?php echo $_SESSION['reset_link']; ?>
                                </a>
                            </div>
                            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                                This link expires in 1 hour. Click to copy to clipboard.
                            </small>
                        </div>
                        <?php unset($_SESSION['reset_link']); ?>
                    <?php endif; ?>

                    <div style="text-align: center;">
                        <div style="margin-bottom: 1.5rem;">
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-left"></i>
                                Back to Login
                            </a>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; font-size: 0.875rem;">
                            <span style="color: var(--text-muted);">
                                Didn't receive the email?
                            </span>
                            <a href="forgot-password.php" style="color: var(--primary-color); text-decoration: none;">
                                Try again
                            </a>
                            <span style="color: var(--text-muted);">|</span>
                            <a href="contact.php" style="color: var(--primary-color); text-decoration: none;">
                                Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['reset_email']); ?>
        <?php endif; ?>

        <!-- Additional Help -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-body" style="padding: 1.5rem;">
                <h6 style="color: var(--text-primary); margin-bottom: 1rem;">
                    <i class="fas fa-question-circle"></i>
                    Need Help?
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary); font-size: 0.875rem;">Common Issues:</strong>
                            <ul style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.5rem; padding-left: 1.5rem;">
                                <li>Check your spam/junk folder</li>
                                <li>Make sure you entered the correct email</li>
                                <li>Wait a few minutes for the email to arrive</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="margin-bottom: 1rem;">
                            <strong style="color: var(--text-primary); font-size: 0.875rem;">Still Having Issues?</strong>
                            <div style="margin-top: 0.5rem;">
                                <a href="contact.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-headset"></i>
                                    Contact Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.form-label i {
    color: var(--primary-color);
    width: 16px;
}

.form-control.error {
    border-color: var(--danger-color);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.btn-block {
    width: 100%;
}

@media (max-width: 767px) {
    .card-header {
        padding: 1.5rem 1rem !important;
    }
    
    .card-body {
        padding: 1.5rem 1rem !important;
    }
    
    .card-header h2 {
        font-size: 1.5rem;
    }
    
    .card-header div[style*="width: 80px"] {
        width: 60px !important;
        height: 60px !important;
        font-size: 1.5rem !important;
    }
}
</style>

<script>
// Form validation and submission
document.querySelector('form[data-validate]')?.addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    submitBtn.disabled = true;
    
    // Re-enable after 10 seconds if form doesn't submit
    setTimeout(() => {
        if (submitBtn.disabled) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }, 10000);
});

// Auto-focus email field
document.getElementById('email')?.focus();

// Copy reset link functionality (development mode)
document.getElementById('resetLink')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    // Copy to clipboard
    navigator.clipboard.writeText(this.href).then(() => {
        // Show copied message
        const originalText = this.textContent;
        this.textContent = 'Copied to clipboard!';
        this.style.color = 'var(--success-color)';
        
        setTimeout(() => {
            this.textContent = originalText;
            this.style.color = 'var(--primary-color)';
        }, 2000);
    }).catch(() => {
        // Fallback - just navigate to the link
        window.location.href = this.href;
    });
});

// Email validation
document.getElementById('email')?.addEventListener('blur', function() {
    const email = this.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        this.classList.add('error');
        
        // Show error message if not already present
        if (!this.parentNode.querySelector('.field-error')) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.style.cssText = 'color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;';
            errorDiv.textContent = 'Please enter a valid email address';
            this.parentNode.appendChild(errorDiv);
        }
    } else {
        this.classList.remove('error');
        const errorDiv = this.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Alt + L to go to login
    if (e.altKey && e.key === 'l') {
        window.location.href = 'login.php';
    }
    
    // Alt + R to go to register
    if (e.altKey && e.key === 'r') {
        window.location.href = 'register.php';
    }
    
    // Alt + H to go home
    if (e.altKey && e.key === 'h') {
        window.location.href = '<?php echo SITE_URL; ?>';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
