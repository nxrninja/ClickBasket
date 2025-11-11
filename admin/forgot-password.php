<?php
$page_title = 'Admin Forgot Password - ClickBasket';
$mobile_title = 'Forgot Password';

require_once '../config/config.php';

// Redirect if already logged in as admin
if (is_admin_logged_in()) {
    redirect('dashboard.php');
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
            
            // Check if admin exists
            $query = "SELECT id, name, email FROM admin_users WHERE email = ? AND is_active = 1 LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Generate reset token
                $reset_token = generate_token(32);
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store reset token
                $update_query = "UPDATE admin_users SET reset_token = ?, reset_token_expires = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$reset_token, $expires_at, $admin['id']]);
                
                // Log password reset request
                $log_query = "INSERT INTO admin_logs (admin_id, action, ip_address, details) VALUES (?, 'password_reset_request', ?, ?)";
                $log_stmt = $db->prepare($log_query);
                $log_stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR'] ?? '', $email]);
                
                // In a real application, you would send an email here
                // For now, we'll just show the reset link (for development)
                $reset_link = SITE_URL . "/admin/reset-password.php?token=" . $reset_token;
                
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
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .forgot-password-container {
            width: 100%;
            max-width: 450px;
        }
        
        .forgot-password-card {
            background: var(--bg-primary);
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .forgot-password-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .forgot-password-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        
        .form-control.error {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-label i {
            color: var(--primary-color);
            width: 16px;
        }
        
        .security-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid var(--info-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .success-message {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success-color);
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
        }
        
        .theme-toggle {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 0.5rem;
            padding: 0.5rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .reset-link-display {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            word-break: break-all;
            font-family: monospace;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">
        <i class="fas fa-moon"></i>
    </button>

    <div class="forgot-password-container">
        <div class="forgot-password-card">
            <?php if (!$success): ?>
                <div class="forgot-password-header">
                    <div class="forgot-password-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h2 style="margin-bottom: 0.5rem;">Forgot Password</h2>
                    <p style="margin: 0; opacity: 0.9;">Admin Password Recovery</p>
                </div>
                
                <div style="padding: 2rem;">
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['general']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-bottom: 2rem;">
                        <p style="color: var(--text-secondary); text-align: center; margin-bottom: 1rem;">
                            Enter your admin email address and we'll send you a link to reset your password.
                        </p>
                    </div>

                    <form method="POST" data-validate>
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i>
                                Admin Email Address
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                placeholder="Enter your admin email"
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

                    <div class="security-info">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-shield-alt" style="color: var(--info-color);"></i>
                            <strong style="color: var(--info-color);">Security Notice</strong>
                        </div>
                        <ul style="color: var(--text-secondary); font-size: 0.875rem; margin: 0; padding-left: 1.5rem;">
                            <li>Reset links expire after 1 hour for security</li>
                            <li>Only active admin accounts can request password resets</li>
                            <li>All password reset attempts are logged and monitored</li>
                        </ul>
                    </div>

                    <div class="back-to-login">
                        <small style="color: var(--text-muted);">
                            Remember your password?
                            <a href="login.php" style="color: var(--primary-color); text-decoration: none;">
                                <i class="fas fa-arrow-left"></i>
                                Back to Login
                            </a>
                        </small>
                    </div>
                </div>
            <?php else: ?>
                <!-- Success Message -->
                <div class="forgot-password-header">
                    <div class="forgot-password-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 style="margin-bottom: 0.5rem;">Email Sent</h2>
                    <p style="margin: 0; opacity: 0.9;">Check Your Inbox</p>
                </div>
                
                <div style="padding: 2rem;">
                    <div class="success-message">
                        <i class="fas fa-envelope" style="font-size: 3rem; color: var(--success-color); margin-bottom: 1rem;"></i>
                        <h4 style="color: var(--success-color); margin-bottom: 1rem;">Reset Link Sent!</h4>
                        <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                            If an admin account exists with the email <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>, 
                            you will receive a password reset link shortly.
                        </p>
                        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                            Please check your email and follow the instructions to reset your password.
                        </p>
                    </div>

                    <?php if (isset($_SESSION['reset_link'])): ?>
                        <!-- Development Only - Show Reset Link -->
                        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning-color); border-radius: 0.5rem; padding: 1rem; margin-top: 1rem;">
                            <div style="color: var(--warning-color); margin-bottom: 0.5rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Development Mode Only</strong>
                            </div>
                            <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">
                                Since email is not configured, here's your reset link:
                            </p>
                            <div class="reset-link-display">
                                <a href="<?php echo $_SESSION['reset_link']; ?>" style="color: var(--primary-color);">
                                    <?php echo $_SESSION['reset_link']; ?>
                                </a>
                            </div>
                            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                                This link expires in 1 hour.
                            </small>
                        </div>
                        <?php unset($_SESSION['reset_link']); ?>
                    <?php endif; ?>

                    <div style="text-align: center; margin-top: 2rem;">
                        <div style="margin-bottom: 1rem;">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i>
                                Back to Login
                            </a>
                        </div>
                        <small style="color: var(--text-muted);">
                            Didn't receive the email? Check your spam folder or
                            <a href="forgot-password.php" style="color: var(--primary-color);">try again</a>
                        </small>
                    </div>
                </div>
                <?php unset($_SESSION['reset_email']); ?>
            <?php endif; ?>
        </div>

        <!-- Help Information -->
        <div style="text-align: center; margin-top: 1.5rem;">
            <small style="color: rgba(255,255,255,0.8);">
                <i class="fas fa-home"></i>
                <a href="<?php echo SITE_URL; ?>" style="color: rgba(255,255,255,0.9); text-decoration: none;">
                    Back to Website
                </a>
                |
                <i class="fas fa-headset"></i>
                <a href="<?php echo SITE_URL; ?>/contact.php" style="color: rgba(255,255,255,0.9); text-decoration: none;">
                    Contact Support
                </a>
            </small>
        </div>
    </div>

    <script>
        // Theme toggle functionality
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            const themeIcon = document.querySelector('.theme-toggle i');
            themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }

        // Initialize theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const themeIcon = document.querySelector('.theme-toggle i');
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });

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
        document.querySelector('.reset-link-display a')?.addEventListener('click', function(e) {
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

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + H to go home
            if (e.altKey && e.key === 'h') {
                window.location.href = '<?php echo SITE_URL; ?>';
            }
            
            // Alt + L to go to login
            if (e.altKey && e.key === 'l') {
                window.location.href = 'login.php';
            }
            
            // Alt + T to toggle theme
            if (e.altKey && e.key === 't') {
                toggleTheme();
            }
        });
    </script>
</body>
</html>
