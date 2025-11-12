<?php
// File Integrity Verification for ClickBasket
echo "<!DOCTYPE html><html><head><title>File Verification</title></head><body>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; background: #d1fae5; padding: 8px; margin: 5px 0; border-radius: 4px; }
.error { color: red; background: #fee2e2; padding: 8px; margin: 5px 0; border-radius: 4px; }
.warning { color: orange; background: #fef3c7; padding: 8px; margin: 5px 0; border-radius: 4px; }
.info { color: blue; background: #dbeafe; padding: 8px; margin: 5px 0; border-radius: 4px; }
</style>";

echo "<h1>🔍 ClickBasket File Verification</h1>";

// Essential files with their expected minimum sizes (to detect corruption)
$essential_files = [
    'index.php' => 1000,
    'login.php' => 1000,
    'products.php' => 1000,
    'config/config.php' => 500,
    'config/database.php' => 200,
    'includes/header.php' => 1000,
    'includes/footer.php' => 1000,
    'classes/Product.php' => 1000,
    'classes/User.php' => 1000,
    'assets/css/style.css' => 5000,
    'assets/js/app.js' => 1000
];

echo "<h2>📋 Essential Files Check</h2>";

$missing_files = [];
$corrupted_files = [];
$good_files = [];

foreach ($essential_files as $file => $min_size) {
    if (!file_exists($file)) {
        echo "<div class='error'>❌ MISSING: $file</div>";
        $missing_files[] = $file;
    } else {
        $size = filesize($file);
        if ($size < $min_size) {
            echo "<div class='warning'>⚠️ POSSIBLY CORRUPTED: $file (size: $size bytes, expected: >$min_size bytes)</div>";
            $corrupted_files[] = $file;
        } else {
            echo "<div class='success'>✅ OK: $file (" . round($size/1024, 1) . " KB)</div>";
            $good_files[] = $file;
        }
    }
}

// Check for PHP syntax errors
echo "<h2>🔧 PHP Syntax Check</h2>";

$php_files_to_check = [
    'index.php', 'login.php', 'products.php', 'config/config.php', 
    'config/database.php', 'classes/Product.php', 'classes/User.php'
];

foreach ($php_files_to_check as $file) {
    if (file_exists($file)) {
        $output = [];
        $return_code = 0;
        exec("php -l \"$file\" 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "<div class='success'>✅ SYNTAX OK: $file</div>";
        } else {
            echo "<div class='error'>❌ SYNTAX ERROR: $file<br><small>" . implode('<br>', $output) . "</small></div>";
        }
    }
}

// Summary
echo "<h2>📊 Summary</h2>";
echo "<div class='info'>";
echo "<strong>Good files:</strong> " . count($good_files) . "<br>";
echo "<strong>Missing files:</strong> " . count($missing_files) . "<br>";
echo "<strong>Possibly corrupted:</strong> " . count($corrupted_files) . "<br>";
echo "</div>";

if (!empty($missing_files)) {
    echo "<div class='error'>";
    echo "<h3>❌ Missing Files Need to be Restored:</h3>";
    foreach ($missing_files as $file) {
        echo "• $file<br>";
    }
    echo "</div>";
}

if (!empty($corrupted_files)) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Files That May Need Attention:</h3>";
    foreach ($corrupted_files as $file) {
        echo "• $file<br>";
    }
    echo "</div>";
}

if (empty($missing_files) && empty($corrupted_files)) {
    echo "<div class='success'>";
    echo "<h3>✅ All Essential Files Look Good!</h3>";
    echo "<p>Your ClickBasket installation appears to have all necessary files.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>🚀 Next Steps</h2>";
echo "<a href='cleanup_project.php' style='color: blue;'>🧹 Clean Up Project</a> | ";
echo "<a href='simple_check.php' style='color: blue;'>🔍 Test Website</a> | ";
echo "<a href='index.php' style='color: blue;'>🏠 Go to Homepage</a>";

echo "</body></html>";
?>
