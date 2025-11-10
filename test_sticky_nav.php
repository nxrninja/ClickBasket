<?php
$page_title = 'Test Sticky Navigation - ClickBasket';
$mobile_title = 'Sticky Nav Test';

require_once 'config/config.php';
include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <h1>Test Sticky Category Navigation</h1>
    <p>This page is designed to test the sticky category navigation. Scroll down to see if the category navigation bar stays fixed at the top.</p>
    
    <div style="margin: 2rem 0; padding: 2rem; background: var(--bg-secondary); border-radius: 8px;">
        <h2>Navigation Test Instructions</h2>
        <ol>
            <li><strong>Scroll down</strong> this page to test the sticky navigation</li>
            <li>The <strong>main navigation</strong> should stick to the top of the page</li>
            <li>The <strong>category navigation</strong> should stick below the main navigation</li>
            <li>Both navigation bars should remain visible while scrolling</li>
        </ol>
    </div>
    
    <!-- Generate content to enable scrolling -->
    <?php for ($i = 1; $i <= 20; $i++): ?>
        <div style="margin: 2rem 0; padding: 1.5rem; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px;">
            <h3 style="color: var(--primary-color);">Content Section <?php echo $i; ?></h3>
            <p>This is test content section <?php echo $i; ?>. The purpose of this content is to create enough vertical space to test the sticky navigation functionality. When you scroll through this page, both the main navigation and category navigation should remain visible at the top of the viewport.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; background: var(--bg-secondary); border-radius: 4px;">
                    <h4>Feature <?php echo $i; ?>A</h4>
                    <p>Sample feature description for testing scroll behavior.</p>
                </div>
                <div style="padding: 1rem; background: var(--bg-secondary); border-radius: 4px;">
                    <h4>Feature <?php echo $i; ?>B</h4>
                    <p>Another sample feature to create more content for scrolling.</p>
                </div>
                <div style="padding: 1rem; background: var(--bg-secondary); border-radius: 4px;">
                    <h4>Feature <?php echo $i; ?>C</h4>
                    <p>Additional content to ensure proper testing of sticky navigation.</p>
                </div>
            </div>
        </div>
    <?php endfor; ?>
    
    <div style="margin: 2rem 0; padding: 2rem; background: var(--success-color); color: white; border-radius: 8px; text-align: center;">
        <h2>🎉 Navigation Test Complete!</h2>
        <p>If you can see both navigation bars at the top while viewing this section, the sticky navigation is working correctly!</p>
        <div style="margin-top: 1rem;">
            <a href="index.php" style="background: white; color: var(--success-color); padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold;">← Back to Home</a>
            <a href="products.php" style="background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 1rem;">Browse Products →</a>
        </div>
    </div>
    
    <!-- Test category navigation functionality -->
    <div style="margin: 2rem 0; padding: 2rem; background: var(--info-color); color: white; border-radius: 8px;">
        <h3>Category Navigation Test</h3>
        <p>Click on any category in the navigation bar above to test the functionality:</p>
        <ul style="margin-top: 1rem;">
            <li>✅ Categories should be clickable</li>
            <li>✅ Active category should be highlighted</li>
            <li>✅ Horizontal scrolling should work on mobile</li>
            <li>✅ Navigation should remain sticky while browsing</li>
        </ul>
    </div>
</div>

<style>
/* Additional test styles */
.test-highlight {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-weight: bold;
}

/* Smooth scrolling for better testing experience */
html {
    scroll-behavior: smooth;
}

/* Visual indicator for sticky elements */
.category-nav {
    border-top: 2px solid var(--primary-color);
}
</style>

<script>
// Add scroll position indicator
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const rate = scrolled * -0.5;
    
    // Add visual feedback for sticky navigation
    const categoryNav = document.querySelector('.category-nav');
    if (categoryNav) {
        if (scrolled > 100) {
            categoryNav.style.boxShadow = 'var(--shadow-lg)';
        } else {
            categoryNav.style.boxShadow = 'var(--shadow-sm)';
        }
    }
});

// Test category navigation scroll functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sticky Navigation Test Page Loaded');
    console.log('Main navigation element:', document.querySelector('.desktop-nav'));
    console.log('Category navigation element:', document.querySelector('.category-nav'));
    
    // Check if elements have sticky positioning
    const categoryNav = document.querySelector('.category-nav');
    if (categoryNav) {
        const styles = window.getComputedStyle(categoryNav);
        console.log('Category nav position:', styles.position);
        console.log('Category nav top:', styles.top);
        console.log('Category nav z-index:', styles.zIndex);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
