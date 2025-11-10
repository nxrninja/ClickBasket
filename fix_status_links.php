<?php
// Quick fix for status filter links
require_once 'config/config.php';

if (!is_logged_in()) {
    echo "Please <a href='login.php'>login</a> first.";
    exit;
}

echo "<h1>Fix Status Filter Links</h1>";

// Test different URL formats
echo "<h2>1. Test Different URL Formats</h2>";

$current_url = $_SERVER['REQUEST_URI'];
$base_url = strtok($current_url, '?'); // Remove existing query parameters

echo "<p><strong>Current URL:</strong> $current_url</p>";
echo "<p><strong>Base URL:</strong> $base_url</p>";

echo "<h3>Test Links:</h3>";
echo "<div style='display: flex; flex-direction: column; gap: 10px; max-width: 400px;'>";

$statuses = [
    'all' => 'All Orders',
    'pending' => 'Pending Orders',
    'processing' => 'Processing Orders',
    'completed' => 'Completed Orders',
    'cancelled' => 'Cancelled Orders'
];

foreach ($statuses as $status => $label) {
    $url = "orders.php?status=$status";
    $full_url = SITE_URL . "/" . $url;
    
    echo "<div style='border: 1px solid #ddd; padding: 10px; border-radius: 4px;'>";
    echo "<strong>$label</strong><br>";
    echo "<small>Relative: <code>$url</code></small><br>";
    echo "<small>Full: <code>$full_url</code></small><br>";
    echo "<a href='$url' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px;'>Test Link</a>";
    echo "</div>";
}

echo "</div>";

echo "<hr>";

// Check current status parameter
echo "<h2>2. Current Status Detection</h2>";

$current_status = $_GET['status'] ?? 'not_set';
echo "<p><strong>Status Parameter:</strong> '$current_status'</p>";

if (isset($_GET['status'])) {
    echo "<p style='color: green;'>✅ Status parameter is being received</p>";
} else {
    echo "<p style='color: red;'>❌ No status parameter in URL</p>";
}

// Test the status detection logic from orders.php
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['all', 'pending', 'processing', 'completed', 'cancelled'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

echo "<p><strong>Processed Status Filter:</strong> '$status_filter'</p>";

echo "<hr>";

// Test button class logic
echo "<h2>3. Button Class Logic Test</h2>";

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Status</th><th>Is Active?</th><th>CSS Class</th><th>Logic</th></tr>";

foreach ($statuses as $status => $label) {
    if ($status === 'all') {
        $is_active = (!isset($_GET['status']) || $_GET['status'] === 'all');
        $logic = "(!isset(\$_GET['status']) || \$_GET['status'] === 'all')";
    } else {
        $is_active = ($_GET['status'] ?? '') === $status;
        $logic = "(\$_GET['status'] ?? '') === '$status'";
    }
    
    $css_class = $is_active ? 'btn-primary' : 'btn-secondary';
    $active_text = $is_active ? 'YES' : 'NO';
    $row_style = $is_active ? 'background: #e7f3ff;' : '';
    
    echo "<tr style='$row_style'>";
    echo "<td><strong>$label</strong></td>";
    echo "<td>$active_text</td>";
    echo "<td><code>$css_class</code></td>";
    echo "<td><small><code>$logic</code></small></td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";

// Generate correct filter buttons
echo "<h2>4. Corrected Filter Buttons</h2>";

echo "<div style='display: flex; gap: 0.5rem; overflow-x: auto; padding: 10px; background: #f8f9fa; border-radius: 4px;'>";

foreach ($statuses as $status => $label) {
    if ($status === 'all') {
        $is_active = (!isset($_GET['status']) || $_GET['status'] === 'all');
    } else {
        $is_active = ($_GET['status'] ?? '') === $status;
    }
    
    $css_class = $is_active ? 'btn-primary' : 'btn-secondary';
    $url = "orders.php?status=$status";
    
    echo "<a href='$url' class='btn btn-sm $css_class' style='text-decoration: none; padding: 8px 12px; border-radius: 4px; color: white; font-size: 14px;'>";
    echo "$label";
    echo "</a>";
}

echo "</div>";

echo "<hr>";

// JavaScript test
echo "<h2>5. JavaScript Navigation Test</h2>";

echo "<p>Test navigation using JavaScript:</p>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";

foreach ($statuses as $status => $label) {
    echo "<button onclick=\"window.location.href='orders.php?status=$status'\" style='background: #28a745; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer;'>$label</button>";
}

echo "</div>";

echo "<hr>";

echo "<h2>6. Debug Current Page State</h2>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 4px;'>";
echo "<h4>Current Page Information:</h4>";
echo "<p><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";
echo "<p><strong>QUERY_STRING:</strong> " . ($_SERVER['QUERY_STRING'] ?? 'Not set') . "</p>";
echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</p>";
echo "<p><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</p>";

echo "<h4>GET Parameters:</h4>";
if (empty($_GET)) {
    echo "<p>No GET parameters</p>";
} else {
    echo "<ul>";
    foreach ($_GET as $key => $value) {
        echo "<li><strong>$key:</strong> " . htmlspecialchars($value) . "</li>";
    }
    echo "</ul>";
}
echo "</div>";

echo "<hr>";

echo "<h2>7. Quick Fix Test</h2>";
echo "<p>If the links above work but the ones in orders.php don't, there might be a caching issue or a problem with the orders.php file itself.</p>";

echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='orders.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>🏠 Orders Home</a>";
echo "<a href='orders.php?status=processing&debug=1' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>🐛 Processing + Debug</a>";
echo "<a href='test_processing_status.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>🔧 Processing Test</a>";
echo "<a href='create_test_order.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>➕ Create Orders</a>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
hr { margin: 20px 0; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
code { background: #f1f1f1; padding: 2px 4px; border-radius: 2px; font-family: monospace; }

.btn { 
    display: inline-block; 
    text-decoration: none; 
    border: none; 
    cursor: pointer; 
}
.btn-primary { background: #007bff; }
.btn-secondary { background: #6c757d; }
.btn-sm { padding: 6px 12px; font-size: 14px; }
</style>
