<?php
// ClickBasket Database Setup Script
// Run this file once to set up the database

require_once 'config/config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>ClickBasket Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #10b981; background: #f0fdf4; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; background: #fef2f2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; background: #eff6ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>ClickBasket Database Setup</h1>";

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=localhost", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    
    echo "<div class='success'>✓ Connected to MySQL server successfully</div>";
    
    // Read and execute SQL file
    $sql_file = 'database/schema.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    $statements = explode(';', $sql);
    
    echo "<div class='info'>📄 Executing database schema...</div>";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<div class='success'>✓ Database schema created successfully</div>";
    
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<div class='success'>✓ Database connection test passed</div>";
        
        // Insert sample data
        echo "<div class='info'>📦 Adding sample data...</div>";
        
        // Sample products
        $sample_products = [
            [
                'title' => 'Modern React Dashboard Template',
                'slug' => 'modern-react-dashboard-template',
                'description' => 'A comprehensive React dashboard template with modern UI components, charts, and responsive design. Perfect for admin panels and business applications.',
                'short_description' => 'Modern React dashboard with charts and responsive design',
                'price' => 49.99,
                'category_id' => 1,
                'file_path' => 'products/react-dashboard.zip',
                'file_size' => '15.2 MB',
                'demo_url' => 'https://demo.example.com/react-dashboard',
                'screenshots' => '["screenshot1.jpg", "screenshot2.jpg"]',
                'tags' => 'react, dashboard, admin, template, responsive',
                'is_active' => 1,
                'is_featured' => 1
            ],
            [
                'title' => 'iOS App UI Kit - E-commerce',
                'slug' => 'ios-app-ui-kit-ecommerce',
                'description' => 'Complete iOS app UI kit for e-commerce applications. Includes 50+ screens, components, and design elements.',
                'short_description' => 'Complete iOS e-commerce app UI kit with 50+ screens',
                'price' => 79.99,
                'category_id' => 2,
                'file_path' => 'products/ios-ecommerce-kit.zip',
                'file_size' => '45.8 MB',
                'demo_url' => '',
                'screenshots' => '["ios-screen1.jpg", "ios-screen2.jpg"]',
                'tags' => 'ios, mobile, ui kit, ecommerce, app design',
                'is_active' => 1,
                'is_featured' => 1
            ],
            [
                'title' => 'Premium Logo Collection - 100 Logos',
                'slug' => 'premium-logo-collection-100-logos',
                'description' => 'A collection of 100 premium logos in various styles. Perfect for businesses, startups, and creative projects.',
                'short_description' => 'Collection of 100 premium logos in various styles',
                'price' => 29.99,
                'category_id' => 3,
                'file_path' => 'products/logo-collection.zip',
                'file_size' => '125.4 MB',
                'demo_url' => '',
                'screenshots' => '["logos1.jpg", "logos2.jpg"]',
                'tags' => 'logo, branding, design, graphics, collection',
                'is_active' => 1,
                'is_featured' => 1
            ]
        ];
        
        $insert_product = $db->prepare("INSERT INTO products (title, slug, description, short_description, price, category_id, file_path, file_size, demo_url, screenshots, tags, is_active, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sample_products as $product) {
            $insert_product->execute([
                $product['title'],
                $product['slug'],
                $product['description'],
                $product['short_description'],
                $product['price'],
                $product['category_id'],
                $product['file_path'],
                $product['file_size'],
                $product['demo_url'],
                $product['screenshots'],
                $product['tags'],
                $product['is_active'],
                $product['is_featured']
            ]);
        }
        
        echo "<div class='success'>✓ Sample products added successfully</div>";
        
        // Create upload directories
        $upload_dirs = [
            'uploads',
            'uploads/products',
            'uploads/screenshots',
            'uploads/temp'
        ];
        
        foreach ($upload_dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "<div class='success'>✓ Created directory: $dir</div>";
            }
        }
        
        echo "<div class='success'>🎉 Setup completed successfully!</div>";
        echo "<div class='info'>
            <h3>Next Steps:</h3>
            <ol>
                <li>Visit <a href='index.php'>your website homepage</a></li>
                <li>Create a user account or login with admin credentials</li>
                <li>Admin login: admin@clickbasket.com / password (change this!)</li>
                <li>Configure payment gateway settings in admin panel</li>
                <li>Add your own products and customize the site</li>
            </ol>
        </div>";
        
        echo "<div class='info'>
            <h3>Important Security Notes:</h3>
            <ul>
                <li>Change the default admin password immediately</li>
                <li>Update JWT_SECRET and ENCRYPTION_KEY in config/config.php</li>
                <li>Configure proper file permissions for uploads directory</li>
                <li>Enable HTTPS in production</li>
                <li>Delete this setup.php file after setup</li>
            </ul>
        </div>";
        
    } else {
        throw new Exception("Failed to connect to ClickBasket database");
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>
        <h3>Troubleshooting:</h3>
        <ul>
            <li>Make sure XAMPP is running</li>
            <li>Check if MySQL service is started</li>
            <li>Verify database credentials in config/database.php</li>
            <li>Ensure PHP has PDO MySQL extension enabled</li>
        </ul>
    </div>";
}

echo "</body></html>";
?>
