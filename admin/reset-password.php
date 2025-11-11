<?php
$page_title = 'Reset Admin Password - ClickBasket';
$mobile_title = 'Reset Password';

require_once '../config/config.php';

// Redirect if already logged in as admin
if (is_admin_logged_in()) {
    redirect('dashboard.php');
}

$token = $_GET['token'] ?? '';
$errors = [];
$success = false;
$admin = null;

// Validate token
if (empty($token)) {
    handle_error('Invalid reset link.', 'forgot-password.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if token is valid and not expired
    $query = "SELECT id, name, email FROM admin_users 
              WHERE reset_token = ? AND reset_token_expires > NOW() AND is_active = 1 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$token]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        handle_error('Invalid or expired reset link. Please request a new password reset.', 'forgot-password.php');
    }
} catch (Exception $e) {
    handle_error('Failed to validate reset link.', 'forgot-password.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter, lowercase letter, number, and special character';
    }
    
    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Please confirm your password';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token
            $update_query = "UPDATE admin_users 
                            SET password = ?, reset_token = NULL, reset_token_expires = NULL, updated_at = NOW() 
                            WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$hashed_password, $admin['id']]);
            
            // Log password reset
            $log_query = "INSERT INTO admin_logs (admin_id, action, ip_address, details) VALUES (?, 'password_reset_completed', ?, ?)";
            $log_stmt = $db->prepare($log_query);
            $log_stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR'] ?? '', 'Password reset successful']);
            
            $success = true;
        } catch (Exception $e) {
            $errors['general'] = 'Failed to reset password. Please try again.';
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
        
        .reset-password-container {
            width: 100%;
            max-width: 450px;
        }
        
        .reset-password-card {
            background: var(--bg-primary);
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .reset-password-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .reset-password-icon {
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
        
        .password-strength {
            margin-top: 0.5rem;
        }
        
        .strength-bar {
            height: 4px;
            background: var(--bg-secondary);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-weak { background: var(--danger-color); width: 25%; }
        .strength-fair { background: var(--warning-color); width: 50%; }
        .strength-good { background: var(--info-color); width: 75%; }
        .strength-strong { background: var(--success-color); width: 100%; }
        
        .password-requirements {
            background: var(--bg-secondary);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            color: var(--text-muted);
        }
        
        .requirement.met {
            color: var(--success-color);
        }
        
        .requirement i {
            width: 16px;
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
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">
        <i class="fas fa-moon"></i>
    </button>

    <div class="reset-password-container">
        <div class="reset-password-card">
            <?php if (!$success): ?>
                <div class="reset-password-header">
                    <div class="reset-password-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2 style="margin-bottom: 0.5rem;">Reset Password</h2>
                    <p style="margin: 0; opacity: 0.9;">Create New Admin Password</p>
                </div>
                
                <div style="padding: 2rem;">
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['general']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-bottom: 1.5rem;">
                        <p style="color: var(--text-secondary); text-align: center;">
                            Hello <strong><?php echo htmlspecialchars($admin['name']); ?></strong>!<br>
                            Please enter your new password below.
                        </p>
                    </div>

                    <form method="POST" data-validate>
                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i>
                                New Password
                            </label>
                            <div style="position: relative;">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    class="form-control <?php echo isset($errors['password']) ? 'error' : ''; ?>" 
                                    placeholder="Enter new password"
                                    required
                                    autofocus
                                    onkeyup="checkPasswordStrength(this.value)"
                                >
                                <button type="button" onclick="togglePassword('password')" 
                                        style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer;">
                                    <i class="fas fa-eye" id="password-toggle-icon"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars($errors['password']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthBar"></div>
                                </div>
                                <small id="strengthText" style="color: var(--text-muted);">Password strength: Weak</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-check"></i>
                                Confirm Password
                            </label>
                            <div style="position: relative;">
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-control <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" 
                                    placeholder="Confirm new password"
                                    required
                                    onkeyup="checkPasswordMatch()"
                                >
                                <button type="button" onclick="togglePassword('confirm_password')" 
                                        style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer;">
                                    <i class="fas fa-eye" id="confirm-password-toggle-icon"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars($errors['confirm_password']); ?>
                                </div>
                            <?php endif; ?>
                            <div id="passwordMatchMessage" style="font-size: 0.75rem; margin-top: 0.25rem;"></div>
                        </div>

                        <div class="password-requirements">
                            <h6 style="color: var(--text-primary); margin-bottom: 0.75rem;">Password Requirements:</h6>
                            <div class="requirement" id="req-length">
                                <i class="fas fa-times"></i>
                                At least 8 characters long
                            </div>
                            <div class="requirement" id="req-uppercase">
                                <i class="fas fa-times"></i>
                                Contains uppercase letter (A-Z)
                            </div>
                            <div class="requirement" id="req-lowercase">
                                <i class="fas fa-times"></i>
                                Contains lowercase letter (a-z)
                            </div>
                            <div class="requirement" id="req-number">
                                <i class="fas fa-times"></i>
                                Contains number (0-9)
                            </div>
                            <div class="requirement" id="req-special">
                                <i class="fas fa-times"></i>
                                Contains special character (@$!%*?&)
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;" id="submitBtn" disabled>
                            <i class="fas fa-key"></i>
                            Reset Password
                        </button>
                    </form>

                    <div style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                        <small style="color: var(--text-muted);">
                            <a href="login.php" style="color: var(--primary-color); text-decoration: none;">
                                <i class="fas fa-arrow-left"></i>
                                Back to Login
                            </a>
                        </small>
                    </div>
                </div>
            <?php else: ?>
                <!-- Success Message -->
                <div class="reset-password-header">
                    <div class="reset-password-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 style="margin-bottom: 0.5rem;">Password Reset</h2>
                    <p style="margin: 0; opacity: 0.9;">Successfully Updated</p>
                </div>
                
                <div style="padding: 2rem;">
                    <div class="success-message">
                        <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--success-color); margin-bottom: 1rem;"></i>
                        <h4 style="color: var(--success-color); margin-bottom: 1rem;">Password Reset Successful!</h4>
                        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                            Your admin password has been successfully reset. You can now log in with your new password.
                        </p>
                        <a href="login.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt"></i>
                            Login to Admin Panel
                        </a>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <small style="color: var(--text-muted);">
                            <i class="fas fa-shield-alt"></i>
                            Your account security has been updated and all sessions have been cleared.
                        </small>
                    </div>
                </div>
            <?php endif; ?>
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
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(fieldId === 'password' ? 'password-toggle-icon' : 'confirm-password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const submitBtn = document.getElementById('submitBtn');
            
            // Check requirements
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[@$!%*?&]/.test(password)
            };
            
            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(`req-${req}`);
                const icon = element.querySelector('i');
                
                if (requirements[req]) {
                    element.classList.add('met');
                    icon.className = 'fas fa-check';
                } else {
                    element.classList.remove('met');
                    icon.className = 'fas fa-times';
                }
            });
            
            // Calculate strength
            const metRequirements = Object.values(requirements).filter(Boolean).length;
            let strength = 'weak';
            let strengthClass = 'strength-weak';
            
            if (metRequirements === 5) {
                strength = 'strong';
                strengthClass = 'strength-strong';
            } else if (metRequirements >= 4) {
                strength = 'good';
                strengthClass = 'strength-good';
            } else if (metRequirements >= 2) {
                strength = 'fair';
                strengthClass = 'strength-fair';
            }
            
            // Update strength bar
            strengthBar.className = `strength-fill ${strengthClass}`;
            strengthText.textContent = `Password strength: ${strength.charAt(0).toUpperCase() + strength.slice(1)}`;
            
            // Enable/disable submit button
            const allRequirementsMet = Object.values(requirements).every(Boolean);
            submitBtn.disabled = !allRequirementsMet;
            
            return allRequirementsMet;
        }

        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const messageElement = document.getElementById('passwordMatchMessage');
            
            if (confirmPassword === '') {
                messageElement.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                messageElement.textContent = 'Passwords match';
                messageElement.style.color = 'var(--success-color)';
            } else {
                messageElement.textContent = 'Passwords do not match';
                messageElement.style.color = 'var(--danger-color)';
            }
        }

        // Form submission
        document.querySelector('form[data-validate]')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!checkPasswordStrength(password)) {
                e.preventDefault();
                alert('Please ensure your password meets all requirements.');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
