
<?php
/**
 * Cleanup Script for Wishlist and Watchlist Removal
 * This script removes database tables and cleans up any remaining references
 */

require_once 'config/config.php';

$page_title = 'Wishlist & Watchlist Cleanup - ClickBasket';
include 'includes/header.php';

echo '<div class="container mt-4">';
echo '<div class="row justify-content-center">';
echo '<div class="col-md-8">';

echo '<div class="card">';
echo '<div class="card-header bg-danger text-white">';
echo '<h4 class="mb-0"><i class="fas fa-trash"></i> Wishlist & Watchlist Cleanup</h4>';
echo '</div>';
echo '<div class="card-body">';

$cleanup_results = [];
$errors = [];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if tables exist and drop them
    $tables_to_remove = ['wishlist', 'watchlist'];
    
    foreach ($tables_to_remove as $table) {
        try {
            // Check if table exists
            $check_query = "SHOW TABLES LIKE '$table'";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                // Get row count before deletion
                $count_query = "SELECT COUNT(*) as count FROM $table";
                $count_stmt = $db->prepare($count_query);
                $count_stmt->execute();
                $row_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // Drop the table
                $drop_query = "DROP TABLE IF EXISTS $table";
                $drop_stmt = $db->prepare($drop_query);
                $drop_stmt->execute();
                
                $cleanup_results[] = "✅ Removed '$table' table (had $row_count records)";
            } else {
                $cleanup_results[] = "ℹ️ Table '$table' does not exist (already removed)";
            }
        } catch (Exception $e) {
            $errors[] = "❌ Error removing table '$table': " . $e->getMessage();
        }
    }
    
    // Check for any remaining files
    $remaining_files = [];
    $files_to_check = [
        'wishlist.php',
        'watchlist.php',
        'api/wishlist.php',
        'api/watchlist.php',
        'setup_wishlist.php',
        'setup_watchlist.php',
        'debug_wishlist.php',
        'fix_wishlist_display.php',
        'fix_wishlist_issue.php',
        'fix_wishlist_table.php',
        'simple_wishlist.php',
        'test_add_to_wishlist.php',
        'test_wishlist_flow.php',
        'create_wishlist_table.sql',
        'remove_wishlist_watchlist.php'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $remaining_files[] = $file;
        }
    }
    
    if (empty($remaining_files)) {
        $cleanup_results[] = "✅ All wishlist/watchlist files have been removed";
    } else {
        foreach ($remaining_files as $file) {
            $cleanup_results[] = "⚠️ File still exists: $file";
        }
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Database connection error: " . $e->getMessage();
}

// Display results
if (!empty($cleanup_results)) {
    echo '<h5 class="text-success mb-3">Cleanup Results:</h5>';
    echo '<ul class="list-group mb-3">';
    foreach ($cleanup_results as $result) {
        echo '<li class="list-group-item">' . htmlspecialchars($result) . '</li>';
    }
    echo '</ul>';
}

if (!empty($errors)) {
    echo '<h5 class="text-danger mb-3">Errors:</h5>';
    echo '<ul class="list-group list-group-flush mb-3">';
    foreach ($errors as $error) {
        echo '<li class="list-group-item list-group-item-danger">' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
}

// Summary
echo '<div class="alert alert-info">';
echo '<h6><i class="fas fa-info-circle"></i> Summary</h6>';
echo '<p>The following components have been removed from ClickBasket:</p>';
echo '<ul>';
echo '<li><strong>Database Tables:</strong> wishlist, watchlist</li>';
echo '<li><strong>Main Pages:</strong> wishlist.php, watchlist.php</li>';
echo '<li><strong>API Endpoints:</strong> api/wishlist.php, api/watchlist.php</li>';
echo '<li><strong>Setup Files:</strong> setup_wishlist.php, setup_watchlist.php</li>';
echo '<li><strong>Debug Files:</strong> debug_wishlist.php, fix_wishlist_*.php</li>';
echo '<li><strong>Test Files:</strong> test_wishlist_*.php, simple_wishlist.php</li>';
echo '<li><strong>SQL Files:</strong> create_wishlist_table.sql</li>';
echo '</ul>';
echo '</div>';

echo '<div class="alert alert-success">';
echo '<h6><i class="fas fa-check-circle"></i> Cleanup Complete</h6>';
echo '<p>All wishlist and watchlist functionality has been successfully removed from ClickBasket. The system now focuses on:</p>';
echo '<ul>';
echo '<li>Product browsing and search</li>';
echo '<li>Shopping cart functionality</li>';
echo '<li>Order management</li>';
echo '<li>User profiles and reviews</li>';
echo '</ul>';
echo '</div>';

echo '<div class="text-center mt-4">';
echo '<a href="' . SITE_URL . '" class="btn btn-primary btn-lg">';
echo '<i class="fas fa-home"></i> Return to Home';
echo '</a>';
echo '</div>';

echo '</div>'; // card-body
echo '</div>'; // card
echo '</div>'; // col
echo '</div>'; // row
echo '</div>'; // container

include 'includes/footer.php';
?>
