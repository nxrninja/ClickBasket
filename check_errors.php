<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ClickBasket Error Check</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .error{color:red;background:#fee;padding:10px;margin:10px 0;} .success{color:green;background:#efe;padding:10px;margin:10px 0;} .info{color:blue;background:#eef;padding:10px;margin:10px 0;}</style>";

echo "<div class='info'>Starting error check at " . date('Y-m-d H:i:s') . "</div>";

// Check 1: PHP basics
echo "<h2>1. PHP Configuration</h2>";
echo "<div class='success'>PHP Version: " . phpversion() . "</div>";
echo "<div class='info'>Error Reporting: " . (error_reporting() ? "ON" : "OFF") . "</div>";

// Check 2: File existence
echo "<h2>2. Critical Files</h2>";
$files = [
    'config/config.php',
    'config/database.php', 
    'classes/Product.php',
    'includes/header.php',
    'index.php',
    'login.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✓ $file exists</div>";
    } else {
        echo "<div class='error'>✗ $file missing</div>";
    }
}

// Check 3: Config loading
echo "<h2>3. Configuration Loading</h2>";
try {
    require_once 'config/config.php';
    echo "<div class='success'>✓ Config loaded successfully</div>";
    echo "<div class='info'>Site URL: " . (defined('SITE_URL') ? SITE_URL : 'NOT DEFINED') . "</div>";
} catch (Throwable $e) {
    echo "<div class='error'>✗ Config error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</div>";
}

// Check 4: Database
echo "<h2>4. Database Connection</h2>";
try {
    if (class_exists('Database')) {
        $database = new Database();
        $db = $database->getConnection();
        if ($db) {
            echo "<div class='success'>✓ Database connected</div>";
            
            // Test a simple query
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "<div class='success'>✓ Database query works</div>";
            
        } else {
            echo "<div class='error'>✗ Database connection failed</div>";
        }
    } else {
        echo "<div class='error'>✗ Database class not found</div>";
    }
} catch (Throwable $e) {
    echo "<div class='error'>✗ Database error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</div>";
}

// Check 5: Classes
echo "<h2>5. Class Loading</h2>";
try {
    if (file_exists('classes/Product.php')) {
        require_once 'classes/Product.php';
        if (class_exists('Product')) {
            echo "<div class='success'>✓ Product class loaded</div>";
        } else {
            echo "<div class='error'>✗ Product class not found in file</div>";
        }
    } else {
        echo "<div class='error'>✗ Product.php file missing</div>";
    }
} catch (Throwable $e) {
    echo "<div class='error'>✗ Product class error: " . $e->getMessage() . "</div>";
}

// Check 6: Session
echo "<h2>6. Session Check</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='success'>✓ Session is active</div>";
    echo "<div class='info'>Session ID: " . session_id() . "</div>";
} else {
    echo "<div class='error'>✗ Session not active</div>";
}

// Check 7: Memory and limits
echo "<h2>7. System Resources</h2>";
echo "<div class='info'>Memory Limit: " . ini_get('memory_limit') . "</div>";
echo "<div class='info'>Max Execution Time: " . ini_get('max_execution_time') . "s</div>";
echo "<div class='info'>Current Memory Usage: " . round(memory_get_usage()/1024/1024, 2) . " MB</div>";

echo "<hr>";
echo "<h2>Quick Tests</h2>";
echo "<a href='simple_check.php' style='color:blue;'>Simple Check</a> | ";
echo "<a href='index_simple.php' style='color:blue;'>Simple Homepage</a> | ";
echo "<a href='index.php' style='color:blue;'>Original Homepage</a>";

echo "<div class='info'>Check completed at " . date('Y-m-d H:i:s') . "</div>";
?>
