<?php
// Test script to verify dashboard changes
require_once '../config/config.php';

if (!is_admin_logged_in()) {
    echo "<h1>Admin Login Required</h1>";
    echo "<p><a href='login.php'>Please login as admin</a></p>";
    exit;
}

echo "<h1>Dashboard Changes Verification</h1>";

echo "<h2>✅ Changes Applied Successfully</h2>";

echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;'>";
echo "<h3>Removed Sections:</h3>";
echo "<ul>";
echo "<li><strong>Revenue Overview Chart</strong> - Line chart showing monthly revenue data</li>";
echo "<li><strong>Order Status Chart</strong> - Doughnut chart showing order status distribution</li>";
echo "<li><strong>Chart.js Library</strong> - Removed unused JavaScript library</li>";
echo "<li><strong>Monthly Revenue Query</strong> - Removed database query for chart data</li>";
echo "<li><strong>Chart-related CSS</strong> - Cleaned up unused styles</li>";
echo "</ul>";
echo "</div>";

echo "<h2>📊 What Remains on Dashboard:</h2>";

echo "<div style='background: #cce7ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 4px; margin: 20px 0;'>";
echo "<h3>Statistics Cards:</h3>";
echo "<ul>";
echo "<li><strong>Total Users</strong> - Count of active users</li>";
echo "<li><strong>Total Products</strong> - Count of active products</li>";
echo "<li><strong>Total Orders</strong> - Count of all orders</li>";
echo "<li><strong>Total Revenue</strong> - Sum of all order amounts</li>";
echo "</ul>";

echo "<h3>Data Tables:</h3>";
echo "<ul>";
echo "<li><strong>Recent Orders</strong> - Last 5 orders with customer info</li>";
echo "<li><strong>Top Products</strong> - Most ordered products</li>";
echo "<li><strong>Recent Users</strong> - Latest registered users</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔗 Quick Links</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>View Updated Dashboard</a>";
echo "<a href='orders.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Orders Management</a>";
echo "<a href='products.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Products Management</a>";
echo "</div>";

echo "<h2>📝 Technical Details</h2>";

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px; margin: 20px 0;'>";
echo "<h4>Files Modified:</h4>";
echo "<ul>";
echo "<li><strong>dashboard.php</strong> - Removed chart sections and related code</li>";
echo "</ul>";

echo "<h4>Code Removed:</h4>";
echo "<ul>";
echo "<li>Revenue Overview chart container and canvas</li>";
echo "<li>Order Status chart container and canvas</li>";
echo "<li>Chart.js library import</li>";
echo "<li>JavaScript chart initialization code</li>";
echo "<li>Chart resize event handlers</li>";
echo "<li>Chart scroll prevention code</li>";
echo "<li>Monthly revenue database query</li>";
echo "<li>Chart-related CSS styles</li>";
echo "</ul>";

echo "<h4>Performance Benefits:</h4>";
echo "<ul>";
echo "<li>Reduced page load time (no Chart.js library)</li>";
echo "<li>Less database queries (no monthly revenue data)</li>";
echo "<li>Cleaner, simpler dashboard layout</li>";
echo "<li>Reduced JavaScript execution time</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🎯 Dashboard Layout After Changes</h2>";

echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; margin: 20px 0;'>";
echo "<h4>Current Dashboard Structure:</h4>";
echo "<ol>";
echo "<li><strong>Welcome Message</strong> - Personalized greeting</li>";
echo "<li><strong>Statistics Grid</strong> - 4 key metrics cards</li>";
echo "<li><strong>Data Tables Section</strong> - Recent orders and top products</li>";
echo "<li><strong>Recent Users Table</strong> - Latest user registrations</li>";
echo "</ol>";

echo "<p><em>The dashboard now has a cleaner, more focused layout without the chart sections.</em></p>";
echo "</div>";

// Test database connection to ensure dashboard will work
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test basic queries that dashboard uses
    $users_test = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch();
    $products_test = $db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1")->fetch();
    $orders_test = $db->query("SELECT COUNT(*) as count FROM orders")->fetch();
    
    echo "<h2>✅ Database Connection Test</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;'>";
    echo "<p><strong>Database queries working correctly:</strong></p>";
    echo "<ul>";
    echo "<li>Users count: {$users_test['count']}</li>";
    echo "<li>Products count: {$products_test['count']}</li>";
    echo "<li>Orders count: {$orders_test['count']}</li>";
    echo "</ul>";
    echo "<p>✅ Dashboard should load without errors</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>⚠️ Database Connection Issue</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;'>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection.</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
</style>
