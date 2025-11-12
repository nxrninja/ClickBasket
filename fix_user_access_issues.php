<?php
// Fix User Access Issues for ClickBasket
// This script addresses common problems users face when accessing the website

echo "<h1>ClickBasket - User Access Issues Fix</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { color: green; background: #d1fae5; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee2e2; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: orange; background: #fef3c7; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #dbeafe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.section { margin: 30px 0; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; }
.btn { padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
.btn:hover { background: #4f46e5; }
</style>";

echo "<div class='container'>";

$fixes_applied = [];
$issues_found = [];

// Fix 1: Check and fix database connection
echo "<div class='section'>";
echo "<h2>🔧 Fix 1: Database Connection</h2>";

try {
    require_once 'config/config.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<div class='success'>✅ Database connection is working</div>";
        
        // Check if essential tables exist
        $essential_tables = ['users', 'products', 'categories'];
        foreach ($essential_tables as $table) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM $table LIMIT 1");
                $stmt->execute();
                echo "<div class='success'>✅ Table '$table' exists and is accessible</div>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Table '$table' issue: " . $e->getMessage() . "</div>";
                $issues_found[] = "Missing or inaccessible table: $table";
            }
        }
    } else {
        echo "<div class='error'>❌ Database connection failed</div>";
        $issues_found[] = "Database connection failure";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database configuration error: " . $e->getMessage() . "</div>";
    $issues_found[] = "Database configuration error";
}
echo "</div>";

// Fix 2: Check URL configuration
echo "<div class='section'>";
echo "<h2>🔧 Fix 2: URL Configuration</h2>";

$current_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
$configured_url = defined('SITE_URL') ? SITE_URL : 'NOT CONFIGURED';

echo "<div class='info'>";
echo "<strong>Current URL:</strong> $current_url<br>";
echo "<strong>Configured URL:</strong> $configured_url";
echo "</div>";

if ($configured_url === 'NOT CONFIGURED') {
    echo "<div class='error'>❌ SITE_URL not configured in config.php</div>";
    $issues_found[] = "SITE_URL not configured";
} else {
    // Check if URLs match
    $current_base = str_replace('/ClickBasket', '', $current_url);
    $configured_base = str_replace('/ClickBasket', '', $configured_url);
    
    if (strpos($configured_base, $current_base) !== false || strpos($current_base, $configured_base) !== false) {
        echo "<div class='success'>✅ URL configuration appears correct</div>";
    } else {
        echo "<div class='warning'>⚠ URL configuration might need adjustment</div>";
        echo "<div class='info'>Consider updating SITE_URL in config/config.php to match your current domain</div>";
    }
}
echo "</div>";

// Fix 3: Check file permissions and accessibility
echo "<div class='section'>";
echo "<h2>🔧 Fix 3: File System Check</h2>";

$critical_files = [
    'index.php' => 'Homepage',
    'login.php' => 'Login page',
    'products.php' => 'Products page',
    'config/config.php' => 'Main configuration',
    'config/database.php' => 'Database configuration',
    'includes/header.php' => 'Header template',
    'assets/css/style.css' => 'Main stylesheet'
];

$all_files_ok = true;
foreach ($critical_files as $file => $description) {
    if (file_exists($file) && is_readable($file)) {
        echo "<div class='success'>✅ $description ($file) - OK</div>";
    } else {
        echo "<div class='error'>❌ $description ($file) - Missing or not readable</div>";
        $issues_found[] = "Missing file: $file";
        $all_files_ok = false;
    }
}

if ($all_files_ok) {
    echo "<div class='success'>✅ All critical files are present and accessible</div>";
    $fixes_applied[] = "File system check passed";
}
echo "</div>";

// Fix 4: Session configuration
echo "<div class='section'>";
echo "<h2>🔧 Fix 4: Session Configuration</h2>";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='success'>✅ PHP sessions are working</div>";
    echo "<div class='info'>Session ID: " . session_id() . "</div>";
    $fixes_applied[] = "Sessions are functional";
} else {
    echo "<div class='error'>❌ PHP sessions not working</div>";
    $issues_found[] = "Session functionality broken";
}
echo "</div>";

// Fix 5: Create a simple .htaccess for better URL handling
echo "<div class='section'>";
echo "<h2>🔧 Fix 5: Web Server Configuration</h2>";

if (!file_exists('.htaccess')) {
    $htaccess_content = "# ClickBasket - Basic Apache Configuration
RewriteEngine On

# Redirect to HTTPS (uncomment if you have SSL)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Handle missing trailing slashes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !(.*)/$
RewriteRule ^(.*)$ $1/ [L,R=301]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection \"1; mode=block\"
</IfModule>

# Prevent access to sensitive files
<Files ~ \"^\\.(htaccess|htpasswd|ini|log|sh|sql)$\">
    Order allow,deny
    Deny from all
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css \"access plus 1 month\"
    ExpiresByType application/javascript \"access plus 1 month\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType image/jpg \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
    ExpiresByType image/gif \"access plus 1 month\"
</IfModule>";

    if (file_put_contents('.htaccess', $htaccess_content)) {
        echo "<div class='success'>✅ Created .htaccess file for better web server configuration</div>";
        $fixes_applied[] = "Created .htaccess configuration";
    } else {
        echo "<div class='warning'>⚠ Could not create .htaccess file (check permissions)</div>";
    }
} else {
    echo "<div class='info'>ℹ️ .htaccess file already exists</div>";
}
echo "</div>";

// Fix 6: Test basic functionality
echo "<div class='section'>";
echo "<h2>🔧 Fix 6: Functionality Test</h2>";

// Test if we can load products
try {
    if (isset($db)) {
        $test_query = "SELECT COUNT(*) as count FROM products WHERE is_active = 1";
        $test_stmt = $db->prepare($test_query);
        $test_stmt->execute();
        $result = $test_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo "<div class='success'>✅ Found {$result['count']} active products in database</div>";
            $fixes_applied[] = "Product data is available";
        } else {
            echo "<div class='warning'>⚠ No active products found - users might see empty pages</div>";
            echo "<div class='info'>Consider running the sample data setup script</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Could not test product data: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Summary
echo "<div class='section'>";
echo "<h2>📋 Summary</h2>";

if (empty($issues_found)) {
    echo "<div class='success'>";
    echo "<h3>✅ No Critical Issues Found</h3>";
    echo "<p>The website appears to be functioning correctly. If users are still experiencing issues, they might be:</p>";
    echo "<ul>";
    echo "<li>Using an outdated browser</li>";
    echo "<li>Having network connectivity issues</li>";
    echo "<li>Accessing from a restricted network</li>";
    echo "<li>Having JavaScript disabled</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Issues Found</h3>";
    echo "<ul>";
    foreach ($issues_found as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (!empty($fixes_applied)) {
    echo "<div class='success'>";
    echo "<h3>✅ Fixes Applied</h3>";
    echo "<ul>";
    foreach ($fixes_applied as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}
echo "</div>";

// Quick actions
echo "<div class='section'>";
echo "<h2>🚀 Quick Actions</h2>";
echo "<div style='text-align: center;'>";
echo "<a href='test_website.php' class='btn'>🧪 Run Website Test</a>";
echo "<a href='index.php' class='btn'>🏠 Test Homepage</a>";
echo "<a href='products.php' class='btn'>📦 Test Products</a>";
echo "<a href='login.php' class='btn'>🔐 Test Login</a>";
echo "<a href='diagnose_website.php' class='btn'>🔍 Full Diagnosis</a>";
echo "</div>";
echo "</div>";

echo "</div>"; // Close container
?>
