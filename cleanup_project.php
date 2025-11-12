<?php
// ClickBasket Project Cleanup Tool
// Identifies and removes unnecessary files and folders

echo "<!DOCTYPE html><html><head><title>ClickBasket Cleanup</title></head><body>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
.success { color: green; background: #d1fae5; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee2e2; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fef3c7; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #dbeafe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.section { margin: 30px 0; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; }
.btn { padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
.btn-danger { background: #ef4444; }
.file-list { max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px; }
</style>";

echo "<div class='container'>";
echo "<h1>🧹 ClickBasket Project Cleanup</h1>";

// Define essential files and folders
$essential_files = [
    // Core application files
    'index.php', 'login.php', 'register.php', 'logout.php', 'products.php', 'product.php',
    'cart.php', 'checkout.php', 'orders.php', 'profile.php', 'categories.php',
    'order-confirmation.php', 'order-details.php', 'contact.php', 'dashboard.php',
    'forgot-password.php', 'reset-password.php', 'reviews.php',
    
    // Configuration
    'config/config.php', 'config/database.php',
    
    // Classes
    'classes/Product.php', 'classes/User.php',
    
    // Templates
    'includes/header.php', 'includes/footer.php',
    
    // Assets
    'assets/css/style.css', 'assets/js/app.js',
    
    // Git and project files
    '.gitignore', '.gitattributes', 'README.md', 'LICENSE'
];

$essential_folders = [
    'config', 'classes', 'includes', 'assets', 'admin', 'uploads', 'database', '.git'
];

// Files to definitely remove (debug, test, setup files)
$files_to_remove = [
    // Debug files
    'debug_checkout.php', 'debug_order_status.php', 'debug_orders.php', 'debug_orders_status.php',
    'debug_product.php', 'debug_uuid_issue.php',
    
    // Fix files
    'fix_database.php', 'fix_orders.php', 'fix_orders_complete.php', 'fix_orders_debug.php',
    'fix_product_page_error.php', 'fix_products_table.php', 'fix_screenshots_warnings.php',
    'fix_status_links.php', 'fix_user_access_issues.php',
    
    // Test files
    'test_all_images.php', 'test_cart_images.php', 'test_checkout_images.php',
    'test_mobile_theme_fix.html', 'test_mobile_visibility.html', 'test_order_flow.php',
    'test_order_status.php', 'test_order_status_messages.php', 'test_processing_status.php',
    'test_ratings_system.php', 'test_sql_fix.php', 'test_sticky_nav.php', 'test_users.php',
    'test_website.php',
    
    // Setup files
    'setup.php', 'setup_billing_table.php', 'setup_categories_and_products.php', 'setup_ratings.php',
    
    // Cleanup and utility files
    'cleanup_wishlist_watchlist.php', 'remove_download_system.php', 'add_sample_products.php',
    'create_test_order.php', 'create_test_orders.sql', 'check_admin.php', 'check_session.php',
    
    // Diagnostic files (we can remove these after cleanup)
    'diagnose_website.php', 'simple_check.php', 'check_errors.php', 'index_simple.php',
    
    // Instruction files
    'CATEGORY_UPDATE_INSTRUCTIONS.md', 'UPDATE_6_CATEGORIES_INSTRUCTIONS.md',
    
    // Legacy files
    'downloads.php', 'orders_simple.php'
];

$cleanup_actions = [];
$errors = [];

// Section 1: Identify files to remove
echo "<div class='section'>";
echo "<h2>📋 Step 1: Files Analysis</h2>";

$existing_files_to_remove = [];
$missing_essential_files = [];

// Check which files to remove actually exist
foreach ($files_to_remove as $file) {
    if (file_exists($file)) {
        $existing_files_to_remove[] = $file;
    }
}

// Check for missing essential files
foreach ($essential_files as $file) {
    if (!file_exists($file)) {
        $missing_essential_files[] = $file;
    }
}

echo "<div class='info'>";
echo "<strong>Files found for removal:</strong> " . count($existing_files_to_remove) . "<br>";
echo "<strong>Missing essential files:</strong> " . count($missing_essential_files);
echo "</div>";

if (!empty($missing_essential_files)) {
    echo "<div class='error'>";
    echo "<strong>⚠️ Missing Essential Files:</strong><br>";
    echo "<div class='file-list'>";
    foreach ($missing_essential_files as $file) {
        echo "• $file<br>";
    }
    echo "</div>";
    echo "</div>";
}

if (!empty($existing_files_to_remove)) {
    echo "<div class='warning'>";
    echo "<strong>🗑️ Files to be removed:</strong><br>";
    echo "<div class='file-list'>";
    foreach ($existing_files_to_remove as $file) {
        $size = file_exists($file) ? filesize($file) : 0;
        echo "• $file (" . round($size/1024, 1) . " KB)<br>";
    }
    echo "</div>";
    echo "</div>";
}
echo "</div>";

// Section 2: Perform cleanup (if requested)
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'yes') {
    echo "<div class='section'>";
    echo "<h2>🧹 Step 2: Performing Cleanup</h2>";
    
    $removed_count = 0;
    $total_size_saved = 0;
    
    foreach ($existing_files_to_remove as $file) {
        try {
            if (file_exists($file)) {
                $size = filesize($file);
                if (unlink($file)) {
                    echo "<div class='success'>✅ Removed: $file (" . round($size/1024, 1) . " KB)</div>";
                    $removed_count++;
                    $total_size_saved += $size;
                } else {
                    echo "<div class='error'>❌ Failed to remove: $file</div>";
                    $errors[] = "Could not remove $file";
                }
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error removing $file: " . $e->getMessage() . "</div>";
            $errors[] = "Error removing $file: " . $e->getMessage();
        }
    }
    
    echo "<div class='success'>";
    echo "<h3>✅ Cleanup Complete</h3>";
    echo "<p><strong>Files removed:</strong> $removed_count</p>";
    echo "<p><strong>Space saved:</strong> " . round($total_size_saved/1024/1024, 2) . " MB</p>";
    echo "</div>";
    
    if (!empty($errors)) {
        echo "<div class='error'>";
        echo "<h3>❌ Errors encountered:</h3>";
        foreach ($errors as $error) {
            echo "<p>• $error</p>";
        }
        echo "</div>";
    }
    echo "</div>";
}

// Section 3: Current project structure
echo "<div class='section'>";
echo "<h2>📁 Step 3: Current Project Structure</h2>";

$current_files = [];
$current_folders = [];

// Scan current directory
$items = scandir('.');
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    
    if (is_dir($item)) {
        $current_folders[] = $item;
    } else {
        $current_files[] = $item;
    }
}

echo "<div class='info'>";
echo "<strong>Current folders:</strong> " . count($current_folders) . "<br>";
echo "<strong>Current files:</strong> " . count($current_files) . "<br>";
echo "</div>";

echo "<div class='file-list'>";
echo "<strong>Folders:</strong><br>";
foreach ($current_folders as $folder) {
    $essential = in_array($folder, $essential_folders) ? " (Essential)" : "";
    echo "📁 $folder$essential<br>";
}
echo "<br><strong>Files (showing first 20):</strong><br>";
$file_count = 0;
foreach ($current_files as $file) {
    if ($file_count >= 20) {
        echo "... and " . (count($current_files) - 20) . " more files<br>";
        break;
    }
    $essential = in_array($file, $essential_files) ? " (Essential)" : "";
    echo "📄 $file$essential<br>";
    $file_count++;
}
echo "</div>";
echo "</div>";

// Section 4: Actions
echo "<div class='section'>";
echo "<h2>🚀 Actions</h2>";

if (!isset($_GET['cleanup'])) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Ready to Clean Up?</h3>";
    echo "<p>This will remove " . count($existing_files_to_remove) . " unnecessary files.</p>";
    echo "<p><strong>Files will be permanently deleted!</strong></p>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<a href='?cleanup=yes' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to delete " . count($existing_files_to_remove) . " files? This cannot be undone!\")'>🗑️ Start Cleanup</a>";
    echo "<a href='simple_check.php' class='btn'>🔍 Test Website</a>";
    echo "</div>";
} else {
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<a href='index.php' class='btn'>🏠 Go to Homepage</a>";
    echo "<a href='simple_check.php' class='btn'>🔍 Test Website</a>";
    echo "<a href='cleanup_project.php' class='btn'>🔄 Run Cleanup Again</a>";
    echo "</div>";
}
echo "</div>";

echo "</div>"; // Close container
echo "</body></html>";
?>
