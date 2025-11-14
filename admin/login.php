<?php
$page_title = 'Admin Login - ClickBasket';
$mobile_title = 'Admin Login';

require_once '../config/config.php';

// Redirect if already logged in as admin
if (is_admin_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check admin credentials
            $query = "SELECT id, name, email, password, role, is_active 
                      FROM admin_users 
                      WHERE email = ? AND is_active = 1 
                      LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Clear any user session data to prevent conflicts
                clear_user_session();
                
                // Set admin session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Log admin login (optional - don't fail if logging fails)
                try {
                    $log_query = "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, 'login', ?)";
                    $log_stmt = $db->prepare($log_query);
                    $log_stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
                } catch (Exception $log_error) {
                    // Continue even if logging fails
                }
                
                handle_success('Welcome back, ' . $admin['name'] . '!', 'admin/dashboard.php');
            } else {
                $errors['general'] = 'Invalid email or password';
                
                // Log failed login attempt (optional)
                try {
                    $fail_query = "INSERT INTO admin_logs (admin_id, action, ip_address, details) VALUES (NULL, 'failed_login', ?, ?)";
                    $fail_stmt = $db->prepare($fail_query);
                    $fail_stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', $email]);
                } catch (Exception $log_error) {
                    // Continue even if logging fails
                }
            }
        } catch (Exception $e) {
            $errors['general'] = 'Login failed. Please try again.';
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
        
        .admin-login-container {
            width: 100%;
            max-width: 400px;
        }
        
        .admin-card {
            background: var(--bg-primary);
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .admin-logo {
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
        
        .security-notice {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid var(--info-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
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
        
        .direct-login-btn {
            position: absolute;
            top: 4rem;
            right: 1rem;
            background: rgba(34, 197, 94, 0.9);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 0.5rem;
            padding: 0.4rem 0.6rem;
            color: white;
            text-decoration: none;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .direct-login-btn:hover {
            background: rgba(34, 197, 94, 1);
            transform: translateY(-1px);
        }
        
        .direct-login-btn i {
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">
        <i class="fas fa-moon"></i>
    </button>

    <?php if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false): ?>
        <a href="direct-login.php" class="direct-login-btn">
            <i class="fas fa-rocket"></i>
            Dev Login
        </a>
    <?php endif; ?>

    <div class="admin-login-container">
        <div class="admin-card">
            <div class="admin-header">
                <div class="admin-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2 style="margin-bottom: 0.5rem;">Admin Portal</h2>
                <p style="margin: 0; opacity: 0.9;">ClickBasket Administration</p>
            </div>
            
            <div style="padding: 2rem;">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>

                <?php
                $success_message = get_flash_message('success');
                if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" data-validate>
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Admin Email
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

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div style="position: relative;">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control <?php echo isset($errors['password']) ? 'error' : ''; ?>" 
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" onclick="togglePassword()" 
                                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer;">
                                <i class="fas fa-eye" id="password-toggle-icon"></i>
                            </button>
                        </div>
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
                        <a href="forgot-password.php" style="color: var(--primary-color); text-decoration: none; font-size: 0.875rem;">
                            Forgot Password?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In to Admin
                    </button>
                </form>

                <div class="security-notice">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-shield-alt" style="color: var(--info-color);"></i>
                        <strong style="color: var(--info-color);">Security Notice</strong>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">
                        This is a secure admin area. All login attempts are logged and monitored. 
                        Unauthorized access attempts will be reported.
                    </p>
                </div>

                <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <small style="color: var(--text-muted);">
                        <i class="fas fa-home"></i>
                        <a href="<?php echo SITE_URL; ?>" style="color: var(--primary-color); text-decoration: none;">
                            Back to Website
                        </a>
                    </small>
                </div>
            </div>
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

        // Password toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Form validation
        document.querySelector('form[data-validate]').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            submitBtn.disabled = true;
            
            // Re-enable after 5 seconds if form doesn't submit
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 5000);
        });

        // Auto-focus email field
        document.getElementById('email').focus();

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + H to go home
            if (e.altKey && e.key === 'h') {
                window.location.href = '<?php echo SITE_URL; ?>';
            }
            
            // Alt + T to toggle theme
            if (e.altKey && e.key === 't') {
                toggleTheme();
            }
        });

        // Security: Clear form on page unload
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });
    </script>
</body>
</html>
