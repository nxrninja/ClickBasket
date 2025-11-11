<?php
/**
 * Add Sample Products for All Categories
 * This script populates the ClickBasket database with sample products across all categories
 */

require_once 'config/config.php';

// Check if admin is logged in (optional - remove if you want to run without login)
// if (!is_admin_logged_in()) {
//     die('Admin login required to run this script.');
// }

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h1>Adding Sample Products to ClickBasket</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .category-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .product-item { margin: 5px 0; padding: 5px; background: #f9f9f9; }
    </style>";
    
    // First, let's get all categories
    $categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categories)) {
        echo "<p class='error'>No categories found! Please run the category setup script first.</p>";
        echo "<p><a href='database/update_ecommerce_categories.sql'>Run Category Setup</a></p>";
        exit;
    }
    
    echo "<p class='info'>Found " . count($categories) . " categories. Adding sample products...</p>";
    
    // Sample products data organized by category
    $sample_products = [
        'Fashion & Apparel' => [
            ['title' => 'Premium Cotton T-Shirt', 'price' => 29.99, 'short_desc' => 'Comfortable 100% cotton t-shirt in various colors'],
            ['title' => 'Designer Jeans', 'price' => 89.99, 'short_desc' => 'Stylish denim jeans with perfect fit'],
            ['title' => 'Casual Summer Dress', 'price' => 59.99, 'short_desc' => 'Light and breezy summer dress for any occasion']
        ],
        'Men\'s Clothing' => [
            ['title' => 'Business Suit', 'price' => 299.99, 'short_desc' => 'Professional business suit for formal occasions'],
            ['title' => 'Polo Shirt', 'price' => 39.99, 'short_desc' => 'Classic polo shirt for casual wear'],
            ['title' => 'Leather Jacket', 'price' => 199.99, 'short_desc' => 'Genuine leather jacket with modern styling']
        ],
        'Women\'s Clothing' => [
            ['title' => 'Elegant Blouse', 'price' => 49.99, 'short_desc' => 'Sophisticated blouse perfect for office wear'],
            ['title' => 'Maxi Dress', 'price' => 79.99, 'short_desc' => 'Flowing maxi dress for special occasions'],
            ['title' => 'Yoga Pants', 'price' => 34.99, 'short_desc' => 'Comfortable yoga pants for active lifestyle']
        ],
        'Kids & Baby' => [
            ['title' => 'Baby Onesie Set', 'price' => 24.99, 'short_desc' => 'Soft cotton onesies for newborns'],
            ['title' => 'Kids Sneakers', 'price' => 44.99, 'short_desc' => 'Comfortable sneakers for active kids'],
            ['title' => 'Toddler Backpack', 'price' => 19.99, 'short_desc' => 'Cute backpack perfect for toddlers']
        ],
        'Shoes & Footwear' => [
            ['title' => 'Running Shoes', 'price' => 129.99, 'short_desc' => 'High-performance running shoes with cushioning'],
            ['title' => 'Dress Shoes', 'price' => 159.99, 'short_desc' => 'Classic leather dress shoes for formal events'],
            ['title' => 'Casual Sneakers', 'price' => 79.99, 'short_desc' => 'Versatile sneakers for everyday wear']
        ],
        'Electronics' => [
            ['title' => 'Wireless Earbuds', 'price' => 149.99, 'short_desc' => 'Premium wireless earbuds with noise cancellation'],
            ['title' => 'Smart Watch', 'price' => 299.99, 'short_desc' => 'Feature-rich smartwatch with health tracking'],
            ['title' => 'Portable Charger', 'price' => 39.99, 'short_desc' => 'High-capacity portable battery charger']
        ],
        'Smartphones & Tablets' => [
            ['title' => 'Smartphone Case', 'price' => 24.99, 'short_desc' => 'Protective case with premium materials'],
            ['title' => 'Screen Protector', 'price' => 14.99, 'short_desc' => 'Tempered glass screen protector'],
            ['title' => 'Tablet Stand', 'price' => 34.99, 'short_desc' => 'Adjustable stand for tablets and phones']
        ],
        'Computers & Laptops' => [
            ['title' => 'Wireless Mouse', 'price' => 49.99, 'short_desc' => 'Ergonomic wireless mouse with precision tracking'],
            ['title' => 'Mechanical Keyboard', 'price' => 129.99, 'short_desc' => 'RGB mechanical keyboard for gaming and productivity'],
            ['title' => 'Laptop Stand', 'price' => 59.99, 'short_desc' => 'Adjustable aluminum laptop stand']
        ],
        'Gaming' => [
            ['title' => 'Gaming Headset', 'price' => 89.99, 'short_desc' => 'Surround sound gaming headset with microphone'],
            ['title' => 'Gaming Mouse Pad', 'price' => 29.99, 'short_desc' => 'Large RGB gaming mouse pad'],
            ['title' => 'Controller Grip', 'price' => 19.99, 'short_desc' => 'Silicone grips for gaming controllers']
        ],
        'Home & Garden' => [
            ['title' => 'Decorative Pillow', 'price' => 24.99, 'short_desc' => 'Soft decorative pillow with modern design'],
            ['title' => 'Plant Pot Set', 'price' => 39.99, 'short_desc' => 'Ceramic plant pots in various sizes'],
            ['title' => 'LED String Lights', 'price' => 19.99, 'short_desc' => 'Warm white LED string lights for decoration']
        ],
        'Kitchen & Dining' => [
            ['title' => 'Stainless Steel Cookware Set', 'price' => 199.99, 'short_desc' => 'Professional-grade cookware set'],
            ['title' => 'Coffee Maker', 'price' => 89.99, 'short_desc' => 'Programmable coffee maker with timer'],
            ['title' => 'Dinner Plate Set', 'price' => 49.99, 'short_desc' => 'Elegant ceramic dinner plates set of 6']
        ],
        'Health & Beauty' => [
            ['title' => 'Moisturizing Cream', 'price' => 34.99, 'short_desc' => 'Hydrating face cream for all skin types'],
            ['title' => 'Hair Styling Tool', 'price' => 79.99, 'short_desc' => 'Professional hair straightener and curler'],
            ['title' => 'Essential Oil Set', 'price' => 44.99, 'short_desc' => 'Aromatherapy essential oils collection']
        ],
        'Sports & Outdoors' => [
            ['title' => 'Yoga Mat', 'price' => 39.99, 'short_desc' => 'Non-slip yoga mat with carrying strap'],
            ['title' => 'Water Bottle', 'price' => 24.99, 'short_desc' => 'Insulated stainless steel water bottle'],
            ['title' => 'Resistance Bands', 'price' => 29.99, 'short_desc' => 'Set of resistance bands for home workouts']
        ],
        'Books & Media' => [
            ['title' => 'Bestselling Novel', 'price' => 14.99, 'short_desc' => 'Popular fiction novel by acclaimed author'],
            ['title' => 'Self-Help Book', 'price' => 19.99, 'short_desc' => 'Motivational book for personal development'],
            ['title' => 'Cookbook', 'price' => 24.99, 'short_desc' => 'Collection of healthy and delicious recipes']
        ],
        'Automotive' => [
            ['title' => 'Car Phone Mount', 'price' => 19.99, 'short_desc' => 'Magnetic phone mount for car dashboard'],
            ['title' => 'Car Charger', 'price' => 14.99, 'short_desc' => 'Fast-charging USB car charger'],
            ['title' => 'Air Freshener', 'price' => 9.99, 'short_desc' => 'Long-lasting car air freshener']
        ],
        'Pet Supplies' => [
            ['title' => 'Dog Toy Set', 'price' => 24.99, 'short_desc' => 'Durable chew toys for dogs'],
            ['title' => 'Cat Scratching Post', 'price' => 49.99, 'short_desc' => 'Tall scratching post with sisal rope'],
            ['title' => 'Pet Food Bowl', 'price' => 19.99, 'short_desc' => 'Stainless steel pet food and water bowls']
        ],
        'Office & Business' => [
            ['title' => 'Desk Organizer', 'price' => 29.99, 'short_desc' => 'Bamboo desk organizer with compartments'],
            ['title' => 'Notebook Set', 'price' => 19.99, 'short_desc' => 'Premium notebooks for note-taking'],
            ['title' => 'Pen Collection', 'price' => 24.99, 'short_desc' => 'Set of professional writing pens']
        ],
        'Arts & Crafts' => [
            ['title' => 'Acrylic Paint Set', 'price' => 34.99, 'short_desc' => 'Professional acrylic paints in 24 colors'],
            ['title' => 'Sketchbook', 'price' => 14.99, 'short_desc' => 'High-quality paper sketchbook for artists'],
            ['title' => 'Craft Scissors', 'price' => 19.99, 'short_desc' => 'Precision craft scissors for detailed work']
        ],
        'Jewelry & Watches' => [
            ['title' => 'Silver Necklace', 'price' => 89.99, 'short_desc' => 'Elegant sterling silver necklace'],
            ['title' => 'Fashion Watch', 'price' => 149.99, 'short_desc' => 'Stylish watch with leather strap'],
            ['title' => 'Earring Set', 'price' => 39.99, 'short_desc' => 'Beautiful earring set in gift box']
        ],
        'Toys & Games' => [
            ['title' => 'Building Blocks', 'price' => 44.99, 'short_desc' => 'Educational building blocks for creativity'],
            ['title' => 'Board Game', 'price' => 29.99, 'short_desc' => 'Fun family board game for all ages'],
            ['title' => 'Puzzle Set', 'price' => 19.99, 'short_desc' => '1000-piece jigsaw puzzle with beautiful image']
        ],
        'Digital Products' => [
            ['title' => 'Photo Editing Software', 'price' => 99.99, 'short_desc' => 'Professional photo editing software license'],
            ['title' => 'Website Template', 'price' => 49.99, 'short_desc' => 'Responsive website template with modern design'],
            ['title' => 'Online Course', 'price' => 79.99, 'short_desc' => 'Comprehensive online course with certificates']
        ]
    ];
    
    $total_added = 0;
    $errors = 0;
    
    foreach ($categories as $category) {
        echo "<div class='category-section'>";
        echo "<h3>Category: " . htmlspecialchars($category['name']) . "</h3>";
        
        $category_products = $sample_products[$category['name']] ?? [
            ['title' => 'Sample Product 1', 'price' => 29.99, 'short_desc' => 'High-quality product in ' . $category['name']],
            ['title' => 'Sample Product 2', 'price' => 49.99, 'short_desc' => 'Premium item from ' . $category['name']],
            ['title' => 'Sample Product 3', 'price' => 19.99, 'short_desc' => 'Affordable option in ' . $category['name']]
        ];
        
        foreach ($category_products as $product_data) {
            try {
                // Generate slug from title
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $product_data['title']));
                $slug = trim($slug, '-');
                
                // Make slug unique by adding category slug
                $unique_slug = $slug . '-' . $category['slug'];
                
                // Check if product already exists
                $check_query = "SELECT id FROM products WHERE slug = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$unique_slug]);
                
                if ($check_stmt->fetch()) {
                    echo "<div class='product-item'>⚠️ Product already exists: " . htmlspecialchars($product_data['title']) . "</div>";
                    continue;
                }
                
                // Create detailed description
                $description = "This is a premium " . strtolower($product_data['title']) . " from our " . $category['name'] . " collection. " . 
                              $product_data['short_desc'] . ". Perfect for customers looking for quality and value. " .
                              "Features include excellent craftsmanship, durable materials, and modern design. " .
                              "Backed by our satisfaction guarantee and fast shipping.";
                
                // Insert product
                $insert_query = "INSERT INTO products (title, slug, description, short_description, price, category_id, is_active, is_featured) 
                                VALUES (?, ?, ?, ?, ?, ?, 1, ?)";
                
                $is_featured = rand(0, 4) == 0 ? 1 : 0; // 20% chance to be featured
                
                $stmt = $db->prepare($insert_query);
                $stmt->execute([
                    $product_data['title'],
                    $unique_slug,
                    $description,
                    $product_data['short_desc'],
                    $product_data['price'],
                    $category['id'],
                    $is_featured
                ]);
                
                echo "<div class='product-item'>✅ Added: " . htmlspecialchars($product_data['title']) . 
                     " - $" . number_format($product_data['price'], 2) . 
                     ($is_featured ? " (Featured)" : "") . "</div>";
                
                $total_added++;
                
            } catch (Exception $e) {
                echo "<div class='product-item error'>❌ Error adding " . htmlspecialchars($product_data['title']) . ": " . $e->getMessage() . "</div>";
                $errors++;
            }
        }
        
        echo "</div>";
    }
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #e8f5e8; border-radius: 5px;'>";
    echo "<h2>Summary</h2>";
    echo "<p class='success'>✅ Successfully added: <strong>$total_added products</strong></p>";
    if ($errors > 0) {
        echo "<p class='error'>❌ Errors encountered: <strong>$errors</strong></p>";
    }
    echo "<p class='info'>📊 Categories processed: <strong>" . count($categories) . "</strong></p>";
    echo "</div>";
    
    echo "<div style='margin-top: 20px; text-align: center;'>";
    echo "<a href='admin/products.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>View Products in Admin</a>";
    echo "<a href='products.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>View Products on Website</a>";
    echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Back to Homepage</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>Database Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please ensure your database is properly configured and the categories table exists.</p>";
    echo "</div>";
}
?>
