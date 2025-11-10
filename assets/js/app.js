// ClickBasket - Main JavaScript functionality
class ClickBasket {
    constructor() {
        this.init();
        this.bindEvents();
        this.initTheme();
    }

    init() {
        // Initialize mobile navigation
        this.updateActiveNavItem();
        
        // Initialize cart
        this.updateCartCount();
        
        // Initialize tooltips and other UI components
        this.initUIComponents();
    }

    bindEvents() {
        // Theme toggle - handle all theme toggle buttons
        const themeToggles = document.querySelectorAll('.theme-toggle');
        themeToggles.forEach(toggle => {
            toggle.addEventListener('click', () => this.toggleTheme());
        });

        // Mobile navigation
        const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
        mobileNavItems.forEach(item => {
            item.addEventListener('click', (e) => this.handleNavClick(e));
        });

        // Add to cart buttons
        const addToCartBtns = document.querySelectorAll('.add-to-cart');
        addToCartBtns.forEach(btn => {
            btn.addEventListener('click', (e) => this.addToCart(e));
        });

        // Search functionality
        const searchForm = document.querySelector('.search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => this.handleSearch(e));
        }

        // Filter functionality
        const filterBtns = document.querySelectorAll('.filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleFilter(e));
        });

        // Form validation
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => this.validateForm(e));
        });

        // Image lazy loading
        this.initLazyLoading();

        // Smooth scrolling
        this.initSmoothScrolling();
    }

    // Theme management
    initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        this.updateThemeIcon(savedTheme);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        console.log('Theme toggle:', currentTheme, '->', newTheme);
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        this.updateThemeIcon(newTheme);
        
        // Add visual feedback
        this.showNotification(`Switched to ${newTheme} mode`, 'info');
    }

    updateThemeIcon(theme) {
        const themeIcons = document.querySelectorAll('.theme-toggle i');
        themeIcons.forEach(icon => {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    }

    // Navigation
    updateActiveNavItem() {
        const currentPath = window.location.pathname;
        const navItems = document.querySelectorAll('.mobile-nav-item, .navbar-nav a');
        
        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('href') === currentPath) {
                item.classList.add('active');
            }
        });
    }

    handleNavClick(e) {
        const target = e.currentTarget;
        const href = target.getAttribute('href');
        
        if (href && href.startsWith('#')) {
            e.preventDefault();
            const element = document.querySelector(href);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

    // Cart functionality
    async addToCart(e) {
        e.preventDefault();
        const btn = e.currentTarget;
        const productId = btn.getAttribute('data-product-id');
        const originalText = btn.innerHTML;
        
        // Show loading state
        btn.innerHTML = '<i class="loading"></i> Adding...';
        btn.disabled = true;

        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: 1
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Product added to cart!', 'success');
                this.updateCartCount();
                btn.innerHTML = '<i class="fas fa-check"></i> Added';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2000);
            } else {
                throw new Error(result.message || 'Failed to add to cart');
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    async updateCartCount() {
        try {
            const response = await fetch('api/cart.php?action=count');
            const result = await response.json();
            
            const cartBadges = document.querySelectorAll('.cart-count');
            cartBadges.forEach(badge => {
                badge.textContent = result.count || 0;
                badge.style.display = result.count > 0 ? 'block' : 'none';
            });
        } catch (error) {
            console.error('Failed to update cart count:', error);
        }
    }

    // Search functionality
    handleSearch(e) {
        e.preventDefault();
        const form = e.currentTarget;
        const query = form.querySelector('input[name="q"]').value.trim();
        
        if (query) {
            window.location.href = `products.php?search=${encodeURIComponent(query)}`;
        }
    }

    // Filter functionality
    handleFilter(e) {
        e.preventDefault();
        const btn = e.currentTarget;
        const filterType = btn.getAttribute('data-filter');
        const filterValue = btn.getAttribute('data-value');
        
        // Update active filter button
        const filterGroup = btn.closest('.filter-group');
        if (filterGroup) {
            filterGroup.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        // Apply filter
        this.applyFilter(filterType, filterValue);
    }

    applyFilter(type, value) {
        const url = new URL(window.location);
        
        if (value === 'all') {
            url.searchParams.delete(type);
        } else {
            url.searchParams.set(type, value);
        }
        
        window.location.href = url.toString();
    }

    // Form validation
    validateForm(e) {
        const form = e.currentTarget;
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            const value = field.value.trim();
            const fieldGroup = field.closest('.form-group');
            
            // Remove existing error
            const existingError = fieldGroup.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
            
            field.classList.remove('error');

            if (!value) {
                isValid = false;
                field.classList.add('error');
                this.showFieldError(fieldGroup, 'This field is required');
            } else if (field.type === 'email' && !this.isValidEmail(value)) {
                isValid = false;
                field.classList.add('error');
                this.showFieldError(fieldGroup, 'Please enter a valid email address');
            } else if (field.type === 'password' && value.length < 6) {
                isValid = false;
                field.classList.add('error');
                this.showFieldError(fieldGroup, 'Password must be at least 6 characters');
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    }

    showFieldError(fieldGroup, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = 'var(--danger-color)';
        errorDiv.style.fontSize = '0.75rem';
        errorDiv.style.marginTop = '0.25rem';
        errorDiv.textContent = message;
        fieldGroup.appendChild(errorDiv);
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Notifications
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;

        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;

        const colors = {
            success: 'var(--success-color)',
            error: 'var(--danger-color)',
            warning: 'var(--warning-color)',
            info: 'var(--info-color)'
        };

        notification.style.backgroundColor = colors[type] || colors.info;
        notification.style.color = 'white';

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);

        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
    }

    // Lazy loading
    initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            images.forEach(img => {
                img.src = img.dataset.src;
            });
        }
    }

    // Smooth scrolling
    initSmoothScrolling() {
        const links = document.querySelectorAll('a[href^="#"]');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    }

    // UI Components
    initUIComponents() {
        // Initialize dropdowns
        this.initDropdowns();
        
        // Initialize modals
        this.initModals();
        
        // Initialize tabs
        this.initTabs();
    }

    initDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            if (toggle && menu) {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    menu.classList.toggle('show');
                });
                
                // Close on outside click
                document.addEventListener('click', (e) => {
                    if (!dropdown.contains(e.target)) {
                        menu.classList.remove('show');
                    }
                });
            }
        });
    }

    initModals() {
        const modalTriggers = document.querySelectorAll('[data-modal]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('show');
                }
            });
        });

        const modalCloses = document.querySelectorAll('.modal-close');
        modalCloses.forEach(close => {
            close.addEventListener('click', () => {
                const modal = close.closest('.modal');
                if (modal) {
                    modal.classList.remove('show');
                }
            });
        });
    }

    initTabs() {
        const tabGroups = document.querySelectorAll('.tabs');
        tabGroups.forEach(tabGroup => {
            const tabButtons = tabGroup.querySelectorAll('.tab-button');
            const tabPanes = tabGroup.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = button.getAttribute('data-tab');
                    
                    // Update active button
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    
                    // Update active pane
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    const targetPane = tabGroup.querySelector(`#${targetId}`);
                    if (targetPane) {
                        targetPane.classList.add('active');
                    }
                });
            });
        });
    }

    // Utility methods
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR'
        }).format(amount);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.clickBasket = new ClickBasket();
});

// Add CSS for notifications
const notificationStyles = `
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.notification-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-close {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.2rem;
    cursor: pointer;
    margin-left: 1rem;
}

.form-control.error {
    border-color: var(--danger-color);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
`;

// Inject notification styles
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);
