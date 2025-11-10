<?php
$page_title = 'Contact Us - ClickBasket';
$mobile_title = 'Contact';

require_once 'config/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters long';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($subject) || strlen($subject) < 5) {
        $errors['subject'] = 'Subject must be at least 5 characters long';
    }
    
    if (empty($message) || strlen($message) < 10) {
        $errors['message'] = 'Message must be at least 10 characters long';
    }
    
    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Insert support ticket
            $query = "INSERT INTO support_tickets (user_id, name, email, subject, message, status) 
                      VALUES (?, ?, ?, ?, ?, 'open')";
            $stmt = $db->prepare($query);
            $stmt->execute([
                is_logged_in() ? get_current_user_id() : null,
                $name,
                $email,
                $subject,
                $message
            ]);
            
            handle_success('Thank you for contacting us! We\'ll get back to you within 24 hours.');
        } catch (Exception $e) {
            $errors['general'] = 'Failed to send message. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="text-center mb-5">
        <h1 style="color: var(--text-primary); margin-bottom: 1rem;">
            <i class="fas fa-headset"></i>
            Contact Us
        </h1>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
            Have questions, need support, or want to share feedback? We're here to help! 
            Get in touch with our team and we'll respond as quickly as possible.
        </p>
    </div>

    <div class="row">
        <!-- Contact Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">
                        <i class="fas fa-envelope"></i>
                        Send us a Message
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['general']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" data-validate>
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
                                        value="<?php echo htmlspecialchars($_POST['name'] ?? ($_SESSION['user_name'] ?? '')); ?>"
                                        placeholder="Enter your full name"
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
                                        name="email" 
                                        class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ($_SESSION['user_email'] ?? '')); ?>"
                                        placeholder="Enter your email address"
                                        required
                                    >
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                                            <?php echo htmlspecialchars($errors['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject" class="form-label">
                                <i class="fas fa-tag"></i>
                                Subject
                            </label>
                            <select id="subject" name="subject" class="form-control <?php echo isset($errors['subject']) ? 'error' : ''; ?>" required>
                                <option value="">Select a subject</option>
                                <option value="General Inquiry" <?php echo ($_POST['subject'] ?? '') === 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
                                <option value="Technical Support" <?php echo ($_POST['subject'] ?? '') === 'Technical Support' ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="Download Issues" <?php echo ($_POST['subject'] ?? '') === 'Download Issues' ? 'selected' : ''; ?>>Download Issues</option>
                                <option value="Payment Problems" <?php echo ($_POST['subject'] ?? '') === 'Payment Problems' ? 'selected' : ''; ?>>Payment Problems</option>
                                <option value="Account Issues" <?php echo ($_POST['subject'] ?? '') === 'Account Issues' ? 'selected' : ''; ?>>Account Issues</option>
                                <option value="Product Request" <?php echo ($_POST['subject'] ?? '') === 'Product Request' ? 'selected' : ''; ?>>Product Request</option>
                                <option value="Bug Report" <?php echo ($_POST['subject'] ?? '') === 'Bug Report' ? 'selected' : ''; ?>>Bug Report</option>
                                <option value="Feature Request" <?php echo ($_POST['subject'] ?? '') === 'Feature Request' ? 'selected' : ''; ?>>Feature Request</option>
                                <option value="Other" <?php echo ($_POST['subject'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <?php if (isset($errors['subject'])): ?>
                                <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars($errors['subject']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="message" class="form-label">
                                <i class="fas fa-comment"></i>
                                Message
                            </label>
                            <textarea 
                                id="message" 
                                name="message" 
                                class="form-control <?php echo isset($errors['message']) ? 'error' : ''; ?>" 
                                rows="6"
                                placeholder="Please describe your inquiry in detail..."
                                required
                            ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <div class="field-error" style="color: var(--danger-color); font-size: 0.75rem; margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars($errors['message']); ?>
                                </div>
                            <?php endif; ?>
                            <small style="color: var(--text-muted);">
                                Please provide as much detail as possible to help us assist you better.
                            </small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i>
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="col-md-4">
            <!-- Contact Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-info-circle"></i>
                        Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="contact-item mb-3">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 40px; height: 40px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Email</strong>
                                <div style="color: var(--text-secondary); font-size: 0.875rem;">support@clickbasket.com</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-item mb-3">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 40px; height: 40px; background: var(--success-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Phone</strong>
                                <div style="color: var(--text-secondary); font-size: 0.875rem;">+1 (555) 123-4567</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-item mb-3">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 40px; height: 40px; background: var(--info-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Business Hours</strong>
                                <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                    Mon-Fri: 9:00 AM - 6:00 PM<br>
                                    Sat-Sun: 10:00 AM - 4:00 PM
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 40px; height: 40px; background: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Address</strong>
                                <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                    123 Digital Street<br>
                                    Tech City, TC 12345
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Quick Links -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-question-circle"></i>
                        Quick Help
                    </h5>
                </div>
                <div class="card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="<?php echo SITE_URL; ?>/faq.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-question"></i>
                            Frequently Asked Questions
                        </a>
                        <a href="<?php echo SITE_URL; ?>/reviews.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-star"></i>
                            My Reviews
                        </a>
                        <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-box"></i>
                            Order Status
                        </a>
                        <a href="<?php echo SITE_URL; ?>/profile.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-user"></i>
                            Account Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="card">
                <div class="card-header">
                    <h5 style="margin: 0;">
                        <i class="fas fa-share-alt"></i>
                        Follow Us
                    </h5>
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <a href="#" class="social-link" style="width: 40px; height: 40px; background: #1877f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" style="width: 40px; height: 40px; background: #1da1f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" style="width: 40px; height: 40px; background: #e4405f; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" style="width: 40px; height: 40px; background: #0077b5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none;">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                    <p style="text-align: center; color: var(--text-muted); font-size: 0.875rem; margin-top: 1rem; margin-bottom: 0;">
                        Stay updated with our latest products and news
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Response Time Notice -->
    <div class="card mt-4">
        <div class="card-body text-center">
            <h5 style="color: var(--text-primary); margin-bottom: 1rem;">
                <i class="fas fa-clock"></i>
                Response Times
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <div style="padding: 1rem;">
                        <h6 style="color: var(--success-color);">General Inquiries</h6>
                        <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Within 24 hours</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="padding: 1rem;">
                        <h6 style="color: var(--warning-color);">Technical Support</h6>
                        <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Within 12 hours</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="padding: 1rem;">
                        <h6 style="color: var(--danger-color);">Urgent Issues</h6>
                        <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Within 4 hours</p>
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
}

.form-label i {
    color: var(--primary-color);
    width: 16px;
}

.form-control.error {
    border-color: var(--danger-color);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.social-link {
    transition: transform 0.3s ease;
}

.social-link:hover {
    transform: translateY(-2px);
}

@media (max-width: 767px) {
    .contact-item {
        margin-bottom: 1rem;
    }
    
    .contact-item div[style*="display: flex"] {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
}
</style>

<script>
// Auto-fill form for logged-in users
document.addEventListener('DOMContentLoaded', function() {
    // Form validation enhancement
    const form = document.querySelector('form[data-validate]');
    const messageTextarea = document.getElementById('message');
    
    // Character counter for message
    if (messageTextarea) {
        const maxLength = 1000;
        const counter = document.createElement('small');
        counter.style.color = 'var(--text-muted)';
        counter.style.float = 'right';
        messageTextarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - messageTextarea.value.length;
            counter.textContent = `${remaining} characters remaining`;
            counter.style.color = remaining < 50 ? 'var(--danger-color)' : 'var(--text-muted)';
        }
        
        messageTextarea.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    // Form submission enhancement
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Re-enable after 3 seconds if form doesn't submit
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 3000);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
