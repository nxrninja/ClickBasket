<?php
// Ultra-simple website check
echo "<!DOCTYPE html><html><head><title>ClickBasket Check</title></head><body>";
echo "<h1>ClickBasket Simple Check</h1>";

// 1. Basic PHP test
echo "<p><strong>1. PHP Working:</strong> YES - " . date('Y-m-d H:i:s') . "</p>";

// 2. Check if config files exist
echo "<p><strong>2. Config file:</strong> ";
if (file_exists('config/config.php')) {
    echo "EXISTS";
} else {
    echo "MISSING";
}
echo "</p>";

// 3. Try to include config
echo "<p><strong>3. Config loading:</strong> ";
try {
    require_once 'config/config.php';
    echo "SUCCESS";
} catch (Exception $e) {
    echo "FAILED - " . $e->getMessage();
}
echo "</p>";

// 4. Database test
echo "<p><strong>4. Database:</strong> ";
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "CONNECTED";
    } else {
        echo "FAILED TO CONNECT";
    }
} catch (Exception $e) {
    echo "ERROR - " . $e->getMessage();
}
echo "</p>";

// 5. Check main files
$files = ['index.php', 'login.php', 'products.php'];
echo "<p><strong>5. Main files:</strong><br>";
foreach ($files as $file) {
    echo "$file: " . (file_exists($file) ? "EXISTS" : "MISSING") . "<br>";
}
echo "</p>";

// 6. Test URL
echo "<p><strong>6. Current URL:</strong> " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>7. Site URL:</strong> " . (defined('SITE_URL') ? SITE_URL : 'NOT SET') . "</p>";

echo "<hr>";
echo "<h2>Quick Links:</h2>";
echo "<a href='index.php'>Homepage</a> | ";
echo "<a href='products.php'>Products</a> | ";
echo "<a href='login.php'>Login</a>";

echo "</body></html>";
?>
