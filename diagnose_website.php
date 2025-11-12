<?php
// Website Diagnostic Tool for ClickBasket
// This script helps identify issues when users access the website

echo "<h1>ClickBasket Website Diagnostic</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
.section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

echo "<div class='section'>";
echo "<h2>1. Basic PHP Configuration</h2>";
echo "<p class='info'>PHP Version: " . phpversion() . "</p>";
echo "<p class='info'>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p class='info'>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p class='info'>Current URL: " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>2. File System Check</h2>";

$required_files = [
    'config/config.php',
    'config/database.php',
    'includes/header.php',
    'assets/css/style.css',
    'index.php',
    'login.php',
    'products.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $file exists</p>";
    } else {
        echo "<p class='error'>✗ $file missing</p>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>3. Database Connection Test</h2>";

try {
    require_once 'config/config.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p class='success'>✓ Database connection successful</p>";
        
        // Test basic queries
        $tables_to_check = ['users', 'products', 'categories', 'orders'];
        
        foreach ($tables_to_check as $table) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p class='success'>✓ Table '$table' exists with {$result['count']} records</p>";
            } catch (Exception $e) {
                echo "<p class='error'>✗ Table '$table' issue: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p class='error'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database error: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>4. Session Test</h2>";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p class='success'>✓ Sessions are working</p>";
    echo "<p class='info'>Session ID: " . session_id() . "</p>";
    
    if (isset($_SESSION['user_id'])) {
        echo "<p class='info'>User logged in: ID " . $_SESSION['user_id'] . "</p>";
    } else {
        echo "<p class='info'>No user currently logged in</p>";
    }
} else {
    echo "<p class='error'>✗ Sessions not working</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>5. URL Configuration Check</h2>";

$config_url = defined('SITE_URL') ? SITE_URL : 'NOT DEFINED';
$actual_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

echo "<p class='info'>Configured SITE_URL: $config_url</p>";
echo "<p class='info'>Actual URL: $actual_url</p>";

if ($config_url !== 'NOT DEFINED' && strpos($actual_url, str_replace('/ClickBasket', '', $config_url)) !== false) {
    echo "<p class='success'>✓ URL configuration looks correct</p>";
} else {
    echo "<p class='warning'>⚠ URL configuration might need adjustment</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>6. Asset Loading Test</h2>";

$assets_to_check = [
    'assets/css/style.css',
    'assets/images/favicon.ico'
];

foreach ($assets_to_check as $asset) {
    if (file_exists($asset)) {
        $size = filesize($asset);
        echo "<p class='success'>✓ $asset exists ($size bytes)</p>";
    } else {
        echo "<p class='error'>✗ $asset missing</p>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>7. Common Issues & Solutions</h2>";

echo "<h3>If users see login page instead of homepage:</h3>";
echo "<ul>";
echo "<li>Check if index.php is properly configured</li>";
echo "<li>Verify database connection is working</li>";
echo "<li>Check if products table has data</li>";
echo "</ul>";

echo "<h3>If styling looks broken:</h3>";
echo "<ul>";
echo "<li>Verify CSS file exists and is accessible</li>";
echo "<li>Check SITE_URL configuration in config.php</li>";
echo "<li>Ensure web server can serve static files</li>";
echo "</ul>";

echo "<h3>If database errors occur:</h3>";
echo "<ul>";
echo "<li>Verify database credentials in config/database.php</li>";
echo "<li>Check if database server is accessible</li>";
echo "<li>Ensure all required tables exist</li>";
echo "</ul>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>8. Quick Actions</h2>";
echo "<p><a href='index.php' style='color: blue;'>→ Test Homepage</a></p>";
echo "<p><a href='products.php' style='color: blue;'>→ Test Products Page</a></p>";
echo "<p><a href='login.php' style='color: blue;'>→ Test Login Page</a></p>";
echo "<p><a href='register.php' style='color: blue;'>→ Test Registration Page</a></p>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>9. Server Information</h2>";
echo "<p class='info'>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p class='info'>Server Load: " . (function_exists('sys_getloadavg') ? implode(', ', sys_getloadavg()) : 'Not available') . "</p>";
echo "<p class='info'>Memory Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB</p>";
echo "</div>";
?>
