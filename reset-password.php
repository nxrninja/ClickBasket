<?php
$page_title = 'Reset Password - ClickBasket';
$mobile_title = 'Reset Password';

require_once 'config/config.php';
require_once 'classes/User.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('profile.php');
}

$token = $_GET['token'] ?? '';
$errors = [];
$success = false;
$user_data = null;

// Validate token
if (empty($token)) {
    handle_error('Invalid reset link.', 'forgot-password.php');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if token is valid and not expired
    $query = "SELECT id, name, email FROM users 
              WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$token]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data) {
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
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter, lowercase letter, and number';
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
            $update_query = "UPDATE users 
                            SET password = ?, reset_token = NULL, reset_expires = NULL, updated_at = NOW() 
                            WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$hashed_password, $user_data['id']]);
            
            $success = true;
        } catch (Exception $e) {
            $errors['general'] = 'Failed to reset password. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0; min-height: 70vh; display: flex; align-items: center; justify-content: center;">
    <div style="width: 100%; max-width: 500px;">
        <?php if (!$success): ?>
            <!-- Reset Password Form -->
            <div class="card">
                <div class="card-header text-center" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; padding: 2rem;">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2 style="margin-bottom: 0.5rem;">Reset Password</h2>
                    <p style="margin: 0; opacity: 0.9;">Create Your New Password</p>
                </div>
                
                <div class="card-body" style="padding: 2rem;">
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['general']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-bottom: 1.5rem; text-center;">
                        <p style="color: var(--text-secondary);">
                            Hello <strong><?php echo htmlspecialchars($user_data['name']); ?></strong>!<br>
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
                            
                            <div class="password-strength" style="margin-top: 0.5rem;">
                                <div class="strength-bar" style="height: 4px; background: var(--bg-secondary); border-radius: 2px; overflow: hidden; margin-bottom: 0.5rem;">
                                    <div class="strength-fill" id="strengthBar" style="height: 100%; transition: all 0.3s ease; border-radius: 2px;"></div>
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

                        <div style="background: var(--bg-secondary); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
                            <h6 style="color: var(--text-primary); margin-bottom: 0.75rem;">Password Requirements:</h6>
                            <div class="requirement" id="req-length" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; margin-bottom: 0.25rem; color: var(--text-muted);">
                                <i class="fas fa-times" style="width: 16px;"></i>
                                At least 8 characters long
                            </div>
                            <div class="requirement" id="req-uppercase" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; margin-bottom: 0.25rem; color: var(--text-muted);">
                                <i class="fas fa-times" style="width: 16px;"></i>
                                Contains uppercase letter (A-Z)
                            </div>
                            <div class="requirement" id="req-lowercase" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; margin-bottom: 0.25rem; color: var(--text-muted);">
                                <i class="fas fa-times" style="width: 16px;"></i>
                                Contains lowercase letter (a-z)
                            </div>
                            <div class="requirement" id="req-number" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; margin-bottom: 0.25rem; color: var(--text-muted);">
                                <i class="fas fa-times" style="width: 16px;"></i>
                                Contains number (0-9)
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn" disabled>
                            <i class="fas fa-key"></i>
                            Reset Password
                        </button>
                    </form>

                    <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                        <small style="color: var(--text-muted);">
                            <a href="login.php" style="color: var(--primary-color); text-decoration: none;">
                                <i class="fas fa-arrow-left"></i>
                                Back to Login
                            </a>
                        </small>
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
                    <h2 style="margin-bottom: 0.5rem;">Password Reset</h2>
                    <p style="margin: 0; opacity: 0.9;">Successfully Updated</p>
                </div>
                
                <div class="card-body" style="padding: 2rem;">
                    <div style="text-align: center;">
                        <i class="fas fa-shield-alt" style="font-size: 4rem; color: var(--success-color); margin-bottom: 1rem;"></i>
                        <h4 style="color: var(--success-color); margin-bottom: 1rem;">Password Reset Successful!</h4>
                        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
                            Your password has been successfully reset. You can now log in with your new password.
                        </p>
                        <a href="login.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt"></i>
                            Login to Your Account
                        </a>
                    </div>

                    <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                        <small style="color: var(--text-muted);">
                            <i class="fas fa-info-circle"></i>
                            Your account security has been updated. Please keep your password safe.
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Security Tips -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-body" style="padding: 1.5rem;">
                <h6 style="color: var(--text-primary); margin-bottom: 1rem;">
                    <i class="fas fa-shield-alt"></i>
                    Security Tips
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul style="font-size: 0.875rem; color: var(--text-secondary); margin: 0; padding-left: 1.5rem;">
                            <li>Use a unique password for your account</li>
                            <li>Don't share your password with others</li>
                            <li>Consider using a password manager</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul style="font-size: 0.875rem; color: var(--text-secondary); margin: 0; padding-left: 1.5rem;">
                            <li>Enable two-factor authentication if available</li>
                            <li>Log out from shared computers</li>
                            <li>Contact support if you notice suspicious activity</li>
                        </ul>
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

.requirement.met {
    color: var(--success-color) !important;
}

.strength-weak { background: var(--danger-color); width: 25%; }
.strength-fair { background: var(--warning-color); width: 50%; }
.strength-good { background: var(--info-color); width: 75%; }
.strength-strong { background: var(--success-color); width: 100%; }

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
        number: /\d/.test(password)
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
    
    if (metRequirements === 4) {
        strength = 'strong';
        strengthClass = 'strength-strong';
    } else if (metRequirements >= 3) {
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

// Auto-focus password field
document.getElementById('password')?.focus();
</script>

<?php include 'includes/footer.php'; ?>
