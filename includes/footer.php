    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav">
        <div class="mobile-nav-items">
            <a href="<?php echo SITE_URL; ?>" class="mobile-nav-item <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                <i class="fas fa-home mobile-nav-icon"></i>
                <span class="mobile-nav-text">Home</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/products.php" class="mobile-nav-item <?php echo $current_page === 'products' ? 'active' : ''; ?>">
                <i class="fas fa-th-large mobile-nav-icon"></i>
                <span class="mobile-nav-text">Products</span>
            </a>
            <?php if (is_logged_in()): ?>
                <a href="<?php echo SITE_URL; ?>/cart.php" class="mobile-nav-item <?php echo $current_page === 'cart' ? 'active' : ''; ?>" style="position: relative;">
                    <i class="fas fa-shopping-cart mobile-nav-icon"></i>
                    <span class="mobile-nav-text">Cart</span>
                    <span class="cart-count" style="position: absolute; top: 5px; right: 15px; background: var(--danger-color); color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 0.6rem; display: flex; align-items: center; justify-content: center;">0</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/profile.php" class="mobile-nav-item <?php echo in_array($current_page, ['profile', 'orders', 'downloads']) ? 'active' : ''; ?>">
                    <i class="fas fa-user mobile-nav-icon"></i>
                    <span class="mobile-nav-text">Profile</span>
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/login.php" class="mobile-nav-item <?php echo $current_page === 'login' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-in-alt mobile-nav-icon"></i>
                    <span class="mobile-nav-text">Login</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/register.php" class="mobile-nav-item <?php echo $current_page === 'register' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus mobile-nav-icon"></i>
                    <span class="mobile-nav-text">Sign Up</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Footer (Desktop) -->
    <footer class="desktop-footer" style="background: var(--bg-secondary); border-top: 1px solid var(--border-color); margin-top: 4rem; display: none;">
        <div class="container">
            <div class="row" style="padding: 3rem 0;">
                <div class="col-md-4">
                    <h5 style="color: var(--text-primary); margin-bottom: 1rem;">
                        <i class="fas fa-shopping-bag"></i>
                        <?php echo SITE_NAME; ?>
                    </h5>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                        Your trusted marketplace for premium digital products. Download templates, apps, and digital resources from top creators.
                    </p>
                    <div style="display: flex; gap: 1rem;">
                        <a href="#" style="color: var(--text-secondary); font-size: 1.2rem; transition: color 0.3s ease;">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" style="color: var(--text-secondary); font-size: 1.2rem; transition: color 0.3s ease;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="color: var(--text-secondary); font-size: 1.2rem; transition: color 0.3s ease;">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" style="color: var(--text-secondary); font-size: 1.2rem; transition: color 0.3s ease;">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <h6 style="color: var(--text-primary); margin-bottom: 1rem;">Quick Links</h6>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo SITE_URL; ?>" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Home</a>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo SITE_URL; ?>/products.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Products</a>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo SITE_URL; ?>/categories.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Categories</a>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo SITE_URL; ?>/contact.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Contact</a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-2">
                    <h6 style="color: var(--text-primary); margin-bottom: 1rem;">Account</h6>
                    <ul style="list-style: none; padding: 0;">
                        <?php if (is_logged_in()): ?>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="<?php echo SITE_URL; ?>/profile.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">My Profile</a>
                            </li>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="<?php echo SITE_URL; ?>/orders.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">My Orders</a>
                            </li>
                        <?php else: ?>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="<?php echo SITE_URL; ?>/login.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Login</a>
                            </li>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="<?php echo SITE_URL; ?>/register.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Sign Up</a>
                            </li>
                        <?php endif; ?>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo SITE_URL; ?>/faq.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">FAQ</a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-2">
                    <h6 style="color: var(--text-primary); margin-bottom: 1rem;">Support</h6>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo SITE_URL; ?>/contact.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Contact Us</a>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo SITE_URL; ?>/faq.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Help Center</a>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo SITE_URL; ?>/terms.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Terms of Service</a>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="<?php echo SITE_URL; ?>/privacy.php" style="color: var(--text-secondary); text-decoration: none; transition: color 0.3s ease;">Privacy Policy</a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-2">
                    <h6 style="color: var(--text-primary); margin-bottom: 1rem;">Newsletter</h6>
                    <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;">
                        Subscribe to get updates on new products and offers.
                    </p>
                    <form style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <input type="email" placeholder="Your email" class="form-control" style="font-size: 0.875rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
                    </form>
                </div>
            </div>
            
            <div style="border-top: 1px solid var(--border-color); padding: 1.5rem 0; text-align: center;">
                <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/app.js"></script>
    
    <!-- Additional scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>

    <!-- Show desktop footer on larger screens -->
    <style>
        @media (min-width: 768px) {
            .desktop-footer {
                display: block !important;
            }
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            min-width: 200px;
            z-index: 1000;
            display: none;
            padding: 0.5rem 0;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: block;
            padding: 0.5rem 1rem;
            color: var(--text-primary);
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        
        .dropdown-item:hover {
            background: var(--bg-secondary);
        }
        
        .dropdown-divider {
            height: 1px;
            background: var(--border-color);
            margin: 0.5rem 0;
        }
    </style>
</body>
</html>
