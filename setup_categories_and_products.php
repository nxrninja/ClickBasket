<?php
/**
 * Complete Setup: Categories and Sample Products
 * This script sets up all categories and adds sample products
 */

require_once 'config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h1>ClickBasket Complete Setup: Categories & Products</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .warning { color: orange; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .step { margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #007cba; }
    </style>";
    
    echo "<div class='section'>";
    echo "<h2>Step 1: Setting up Categories</h2>";
    
    // Check if categories exist
    $check_categories = "SELECT COUNT(*) as count FROM categories";
    $check_stmt = $db->prepare($check_categories);
    $check_stmt->execute();
    $category_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($category_count < 10) {
        echo "<p class='warning'>Found only $category_count categories. Setting up comprehensive category list...</p>";
        
        // Clear existing categories
        $db->exec("DELETE FROM categories");
        $db->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
        
        // Insert comprehensive categories
        $categories_sql = "INSERT INTO categories (name, slug, description, is_active) VALUES
        ('Fashion & Apparel', 'fashion-apparel', 'Clothing, shoes, accessories, and fashion items', 1),
        ('Men\'s Clothing', 'mens-clothing', 'Shirts, pants, suits, jackets, and men\'s fashion wear', 1),
        ('Women\'s Clothing', 'womens-clothing', 'Dresses, tops, bottoms, outerwear, and women\'s fashion', 1),
        ('Kids & Baby', 'kids-baby', 'Children\'s clothing, baby items, toys, and accessories', 1),
        ('Shoes & Footwear', 'shoes-footwear', 'Athletic shoes, dress shoes, boots, sandals, and footwear', 1),
        ('Bags & Accessories', 'bags-accessories', 'Handbags, wallets, jewelry, watches, and fashion accessories', 1),
        ('Electronics', 'electronics', 'Consumer electronics, gadgets, and technology products', 1),
        ('Smartphones & Tablets', 'smartphones-tablets', 'Mobile phones, tablets, and mobile accessories', 1),
        ('Computers & Laptops', 'computers-laptops', 'Desktop computers, laptops, and computer accessories', 1),
        ('Audio & Headphones', 'audio-headphones', 'Speakers, headphones, earbuds, and audio equipment', 1),
        ('Gaming', 'gaming', 'Video games, gaming consoles, and gaming accessories', 1),
        ('Smart Home', 'smart-home', 'Smart devices, home automation, and IoT products', 1),
        ('Home & Garden', 'home-garden', 'Home improvement, furniture, decor, and garden supplies', 1),
        ('Furniture', 'furniture', 'Living room, bedroom, office, and outdoor furniture', 1),
        ('Home Decor', 'home-decor', 'Wall art, lighting, rugs, curtains, and decorative items', 1),
        ('Kitchen & Dining', 'kitchen-dining', 'Cookware, appliances, dinnerware, and kitchen tools', 1),
        ('Garden & Outdoor', 'garden-outdoor', 'Gardening tools, outdoor furniture, and patio accessories', 1),
        ('Tools & Hardware', 'tools-hardware', 'Hand tools, power tools, and hardware supplies', 1),
        ('Health & Beauty', 'health-beauty', 'Personal care, cosmetics, and wellness products', 1),
        ('Skincare', 'skincare', 'Face care, body care, and skincare treatments', 1),
        ('Makeup & Cosmetics', 'makeup-cosmetics', 'Foundation, lipstick, eyeshadow, and beauty products', 1),
        ('Hair Care', 'hair-care', 'Shampoo, conditioner, styling products, and hair tools', 1),
        ('Health & Wellness', 'health-wellness', 'Vitamins, supplements, and health monitoring devices', 1),
        ('Sports & Outdoors', 'sports-outdoors', 'Athletic gear, outdoor equipment, and fitness products', 1),
        ('Fitness Equipment', 'fitness-equipment', 'Exercise machines, weights, and home gym equipment', 1),
        ('Outdoor Recreation', 'outdoor-recreation', 'Camping, hiking, fishing, and outdoor adventure gear', 1),
        ('Books & Media', 'books-media', 'Books, movies, music, and educational content', 1),
        ('Books', 'books', 'Fiction, non-fiction, textbooks, and digital books', 1),
        ('Movies & TV', 'movies-tv', 'DVDs, Blu-rays, and digital video content', 1),
        ('Music', 'music', 'CDs, vinyl records, and digital music', 1),
        ('Automotive', 'automotive', 'Car parts, accessories, and automotive supplies', 1),
        ('Car Electronics', 'car-electronics', 'GPS, dash cams, stereos, and car tech accessories', 1),
        ('Car Care', 'car-care', 'Cleaning supplies, maintenance products, and car care tools', 1),
        ('Food & Beverages', 'food-beverages', 'Gourmet foods, snacks, and specialty beverages', 1),
        ('Pet Supplies', 'pet-supplies', 'Pet food, toys, accessories, and care products', 1),
        ('Dog Supplies', 'dog-supplies', 'Dog food, toys, leashes, and canine accessories', 1),
        ('Cat Supplies', 'cat-supplies', 'Cat food, litter, toys, and feline accessories', 1),
        ('Office & Business', 'office-business', 'Office supplies, business equipment, and professional tools', 1),
        ('Office Supplies', 'office-supplies', 'Pens, paper, folders, and general office materials', 1),
        ('Arts & Crafts', 'arts-crafts', 'Art supplies, craft materials, and creative tools', 1),
        ('Art Supplies', 'art-supplies', 'Paints, brushes, canvases, and drawing materials', 1),
        ('Jewelry & Watches', 'jewelry-watches', 'Fine jewelry, fashion jewelry, and timepieces', 1),
        ('Toys & Games', 'toys-games', 'Children\'s toys, board games, and educational games', 1),
        ('Digital Products', 'digital-products', 'Software, digital downloads, and online services', 1)";
        
        $db->exec($categories_sql);
        
        // Check how many were added
        $check_stmt->execute();
        $new_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p class='success'>✅ Successfully added $new_count categories!</p>";
    } else {
        echo "<p class='success'>✅ Categories already set up ($category_count found)</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>Step 2: Adding Sample Products</h2>";
    
    // Check if products exist
    $check_products = "SELECT COUNT(*) as count FROM products";
    $check_stmt = $db->prepare($check_products);
    $check_stmt->execute();
    $product_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p class='info'>Current products in database: $product_count</p>";
    
    // Get all categories for product creation
    $categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
    $categories_stmt = $db->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $products_added = 0;
    $products_skipped = 0;
    
    // Sample products for major categories
    $sample_products = [
        'Fashion & Apparel' => [
            'Premium Cotton T-Shirt' => ['price' => 29.99, 'desc' => 'Comfortable 100% cotton t-shirt in various colors'],
            'Designer Jeans' => ['price' => 89.99, 'desc' => 'Stylish denim jeans with perfect fit'],
            'Casual Summer Dress' => ['price' => 59.99, 'desc' => 'Light and breezy summer dress']
        ],
        'Electronics' => [
            'Wireless Earbuds' => ['price' => 149.99, 'desc' => 'Premium wireless earbuds with noise cancellation'],
            'Smart Watch' => ['price' => 299.99, 'desc' => 'Feature-rich smartwatch with health tracking'],
            'Portable Charger' => ['price' => 39.99, 'desc' => 'High-capacity portable battery charger']
        ],
        'Home & Garden' => [
            'Decorative Pillow' => ['price' => 24.99, 'desc' => 'Soft decorative pillow with modern design'],
            'Plant Pot Set' => ['price' => 39.99, 'desc' => 'Ceramic plant pots in various sizes'],
            'LED String Lights' => ['price' => 19.99, 'desc' => 'Warm white LED string lights']
        ],
        'Health & Beauty' => [
            'Moisturizing Cream' => ['price' => 34.99, 'desc' => 'Hydrating face cream for all skin types'],
            'Hair Styling Tool' => ['price' => 79.99, 'desc' => 'Professional hair straightener and curler'],
            'Essential Oil Set' => ['price' => 44.99, 'desc' => 'Aromatherapy essential oils collection']
        ],
        'Sports & Outdoors' => [
            'Yoga Mat' => ['price' => 39.99, 'desc' => 'Non-slip yoga mat with carrying strap'],
            'Water Bottle' => ['price' => 24.99, 'desc' => 'Insulated stainless steel water bottle'],
            'Resistance Bands' => ['price' => 29.99, 'desc' => 'Set of resistance bands for home workouts']
        ]
    ];
    
    foreach ($categories as $category) {
        $category_products = $sample_products[$category['name']] ?? [
            'Premium Product' => ['price' => 49.99, 'desc' => 'High-quality product in ' . $category['name']],
            'Standard Product' => ['price' => 29.99, 'desc' => 'Great value product from ' . $category['name']],
            'Budget Product' => ['price' => 19.99, 'desc' => 'Affordable option in ' . $category['name']]
        ];
        
        foreach ($category_products as $title => $data) {
            $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)) . '-' . $category['slug'];
            
            // Check if product exists
            $check_product = "SELECT id FROM products WHERE slug = ?";
            $check_stmt = $db->prepare($check_product);
            $check_stmt->execute([$slug]);
            
            if ($check_stmt->fetch()) {
                $products_skipped++;
                continue;
            }
            
            // Create product
            $description = "This is a premium " . strtolower($title) . " from our " . $category['name'] . " collection. " . 
                          $data['desc'] . ". Perfect for customers looking for quality and value.";
            
            $insert_product = "INSERT INTO products (title, slug, description, short_description, price, category_id, is_active, is_featured) 
                              VALUES (?, ?, ?, ?, ?, ?, 1, ?)";
            
            $is_featured = rand(0, 4) == 0 ? 1 : 0; // 20% chance featured
            
            $stmt = $db->prepare($insert_product);
            $stmt->execute([$title, $slug, $description, $data['desc'], $data['price'], $category['id'], $is_featured]);
            
            $products_added++;
        }
    }
    
    echo "<p class='success'>✅ Added $products_added new products</p>";
    if ($products_skipped > 0) {
        echo "<p class='warning'>⚠️ Skipped $products_skipped existing products</p>";
    }
    echo "</div>";
    
    // Final summary
    echo "<div class='section' style='background: #e8f5e8;'>";
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<div class='step'>";
    echo "<h3>What was set up:</h3>";
    echo "<ul>";
    echo "<li>✅ " . count($categories) . " product categories</li>";
    echo "<li>✅ $products_added sample products added</li>";
    echo "<li>✅ Featured products randomly assigned</li>";
    echo "<li>✅ All products are active and ready for sale</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li>🔗 <a href='admin/products.php'>Manage Products in Admin Panel</a></li>";
    echo "<li>🛍️ <a href='products.php'>Browse Products on Website</a></li>";
    echo "<li>🏠 <a href='index.php'>Visit Homepage</a></li>";
    echo "<li>📱 <a href='admin/dashboard.php'>Admin Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>Setup Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}
?>
