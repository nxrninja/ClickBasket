<?php
// Simple test page to verify website functionality
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClickBasket - Website Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 2rem;
            color: #6366f1;
            margin-bottom: 10px;
        }
        .nav-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .nav-links a {
            padding: 10px 20px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: #4f46e5;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🛒 ClickBasket</div>
            <h1>Website Status Test</h1>
            <p>Testing basic website functionality for user access</p>
        </div>

        <?php
        // Test 1: Basic PHP functionality
        echo '<div class="status success">';
        echo '<h3>✅ PHP is working</h3>';
        echo '<p>PHP Version: ' . phpversion() . '</p>';
        echo '<p>Current time: ' . date('Y-m-d H:i:s') . '</p>';
        echo '</div>';

        // Test 2: File system access
        $required_files = ['index.php', 'login.php', 'products.php', 'config/config.php'];
        $missing_files = [];
        
        foreach ($required_files as $file) {
            if (!file_exists($file)) {
                $missing_files[] = $file;
            }
        }

        if (empty($missing_files)) {
            echo '<div class="status success">';
            echo '<h3>✅ Core files are present</h3>';
            echo '<p>All essential website files are accessible</p>';
            echo '</div>';
        } else {
            echo '<div class="status error">';
            echo '<h3>❌ Missing files detected</h3>';
            echo '<p>Missing: ' . implode(', ', $missing_files) . '</p>';
            echo '</div>';
        }

        // Test 3: Database connection
        try {
            require_once 'config/config.php';
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                echo '<div class="status success">';
                echo '<h3>✅ Database connection successful</h3>';
                echo '<p>Connected to database successfully</p>';
                echo '</div>';
            } else {
                echo '<div class="status error">';
                echo '<h3>❌ Database connection failed</h3>';
                echo '<p>Unable to connect to the database</p>';
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="status error">';
            echo '<h3>❌ Database error</h3>';
            echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }

        // Test 4: Session functionality
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo '<div class="status success">';
            echo '<h3>✅ Sessions are working</h3>';
            echo '<p>Session management is functional</p>';
            echo '</div>';
        } else {
            echo '<div class="status error">';
            echo '<h3>❌ Session issues</h3>';
            echo '<p>Sessions are not working properly</p>';
            echo '</div>';
        }

        // Test 5: URL configuration
        $site_url = defined('SITE_URL') ? SITE_URL : 'Not configured';
        echo '<div class="status info">';
        echo '<h3>ℹ️ Configuration Info</h3>';
        echo '<p><strong>Site URL:</strong> ' . htmlspecialchars($site_url) . '</p>';
        echo '<p><strong>Current URL:</strong> ' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '</p>';
        echo '</div>';
        ?>

        <div class="nav-links">
            <a href="index.php">🏠 Homepage</a>
            <a href="products.php">📦 Products</a>
            <a href="login.php">🔐 Login</a>
            <a href="register.php">📝 Register</a>
            <a href="diagnose_website.php">🔍 Full Diagnosis</a>
        </div>

        <div style="text-align: center; margin-top: 30px; color: #666;">
            <p>If you can see this page, the basic website functionality is working.</p>
            <p>If users are having issues, try the links above to test specific pages.</p>
        </div>
    </div>
</body>
</html>
