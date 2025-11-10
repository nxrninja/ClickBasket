<?php
// Debug page to help troubleshoot admin login issues
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; }
        .info { background: #e3f2fd; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .error { background: #ffebee; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .success { background: #e8f5e8; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.5rem; border: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>ClickBasket Admin Debug</h1>
    
    <div class="info">
        <h3>Current Request Information</h3>
        <table>
            <tr><th>Request URI</th><td><?php echo $_SERVER['REQUEST_URI'] ?? 'Not set'; ?></td></tr>
            <tr><th>HTTP Host</th><td><?php echo $_SERVER['HTTP_HOST'] ?? 'Not set'; ?></td></tr>
            <tr><th>Server Name</th><td><?php echo $_SERVER['SERVER_NAME'] ?? 'Not set'; ?></td></tr>
            <tr><th>Document Root</th><td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Not set'; ?></td></tr>
            <tr><th>Script Name</th><td><?php echo $_SERVER['SCRIPT_NAME'] ?? 'Not set'; ?></td></tr>
            <tr><th>Current Directory</th><td><?php echo __DIR__; ?></td></tr>
        </table>
    </div>

    <div class="info">
        <h3>Expected URLs</h3>
        <ul>
            <li><strong>Admin Login:</strong> <a href="http://localhost/ClickBasket/admin/login.php">http://localhost/ClickBasket/admin/login.php</a></li>
            <li><strong>Admin Dashboard:</strong> <a href="http://localhost/ClickBasket/admin/dashboard.php">http://localhost/ClickBasket/admin/dashboard.php</a></li>
            <li><strong>User Login:</strong> <a href="http://localhost/ClickBasket/login.php">http://localhost/ClickBasket/login.php</a></li>
            <li><strong>User Dashboard:</strong> <a href="http://localhost/ClickBasket/dashboard.php">http://localhost/ClickBasket/dashboard.php</a></li>
        </ul>
    </div>

    <div class="info">
        <h3>File Existence Check</h3>
        <table>
            <tr><th>File</th><th>Exists</th><th>Readable</th></tr>
            <tr>
                <td>admin/login.php</td>
                <td><?php echo file_exists('login.php') ? '✅ Yes' : '❌ No'; ?></td>
                <td><?php echo is_readable('login.php') ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
            <tr>
                <td>admin/dashboard.php</td>
                <td><?php echo file_exists('dashboard.php') ? '✅ Yes' : '❌ No'; ?></td>
                <td><?php echo is_readable('dashboard.php') ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
            <tr>
                <td>../login.php (user)</td>
                <td><?php echo file_exists('../login.php') ? '✅ Yes' : '❌ No'; ?></td>
                <td><?php echo is_readable('../login.php') ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
            <tr>
                <td>../dashboard.php (user)</td>
                <td><?php echo file_exists('../dashboard.php') ? '✅ Yes' : '❌ No'; ?></td>
                <td><?php echo is_readable('../dashboard.php') ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
        </table>
    </div>

    <div class="success">
        <h3>Quick Actions</h3>
        <p>
            <a href="login.php" style="background: #007cba; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;">Go to Admin Login</a>
            <a href="../login.php" style="background: #28a745; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin-left: 0.5rem;">Go to User Login</a>
        </p>
    </div>

    <div class="info">
        <h3>Troubleshooting Steps</h3>
        <ol>
            <li>Make sure you're accessing: <code>http://localhost/ClickBasket/admin/login.php</code></li>
            <li>Clear your browser cache and cookies</li>
            <li>Check if you have any browser extensions that might redirect</li>
            <li>Try accessing in an incognito/private browser window</li>
            <li>Make sure XAMPP is running and the ClickBasket folder is in htdocs</li>
        </ol>
    </div>
</body>
</html>
