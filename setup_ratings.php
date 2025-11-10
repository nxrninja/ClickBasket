<?php
// Setup script for ratings and reviews system
require_once 'config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h1>Setting up Ratings & Reviews System</h1>";
    
    // Create product_ratings table
    echo "<h2>1. Creating product_ratings table...</h2>";
    $ratings_table_sql = "CREATE TABLE IF NOT EXISTS product_ratings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_title VARCHAR(200),
        review_text TEXT,
        is_verified_purchase BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_product_rating (user_id, product_id)
    )";
    
    $db->exec($ratings_table_sql);
    echo "✅ Product ratings table created successfully!<br>";
    
    // Create indexes
    echo "<h2>2. Creating indexes...</h2>";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_product_ratings_product_id ON product_ratings(product_id)",
        "CREATE INDEX IF NOT EXISTS idx_product_ratings_rating ON product_ratings(rating)",
        "CREATE INDEX IF NOT EXISTS idx_product_ratings_created_at ON product_ratings(created_at)"
    ];
    
    foreach ($indexes as $index_sql) {
        $db->exec($index_sql);
    }
    echo "✅ Indexes created successfully!<br>";
    
    // Check if rating columns exist in products table
    echo "<h2>3. Adding rating columns to products table...</h2>";
    
    $check_columns = $db->query("SHOW COLUMNS FROM products LIKE 'average_rating'")->rowCount();
    if ($check_columns == 0) {
        $db->exec("ALTER TABLE products ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00");
        echo "✅ Added average_rating column to products table<br>";
    } else {
        echo "✅ average_rating column already exists<br>";
    }
    
    $check_columns = $db->query("SHOW COLUMNS FROM products LIKE 'rating_count'")->rowCount();
    if ($check_columns == 0) {
        $db->exec("ALTER TABLE products ADD COLUMN rating_count INT DEFAULT 0");
        echo "✅ Added rating_count column to products table<br>";
    } else {
        echo "✅ rating_count column already exists<br>";
    }
    
    // Create indexes for new columns
    $product_indexes = [
        "CREATE INDEX IF NOT EXISTS idx_products_average_rating ON products(average_rating)",
        "CREATE INDEX IF NOT EXISTS idx_products_rating_count ON products(rating_count)"
    ];
    
    foreach ($product_indexes as $index_sql) {
        $db->exec($index_sql);
    }
    echo "✅ Product rating indexes created successfully!<br>";
    
    // Create some sample ratings
    echo "<h2>4. Creating sample ratings...</h2>";
    
    // Get first few products
    $products = $db->query("SELECT id FROM products LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $users = $db->query("SELECT id FROM users LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($products) && !empty($users)) {
        $sample_ratings = [
            ['rating' => 5, 'title' => 'Excellent Product!', 'text' => 'This product exceeded my expectations. Highly recommended!'],
            ['rating' => 4, 'title' => 'Very Good', 'text' => 'Great quality and easy to use. Worth the price.'],
            ['rating' => 5, 'title' => 'Amazing!', 'text' => 'Perfect for my project. Great documentation and support.'],
            ['rating' => 3, 'title' => 'Good but could be better', 'text' => 'Decent product but has room for improvement.'],
            ['rating' => 4, 'title' => 'Satisfied', 'text' => 'Good value for money. Works as expected.']
        ];
        
        $rating_count = 0;
        foreach ($products as $product) {
            foreach ($users as $user_index => $user) {
                if ($rating_count < count($sample_ratings)) {
                    $sample = $sample_ratings[$rating_count];
                    
                    try {
                        $insert_rating = $db->prepare("INSERT IGNORE INTO product_ratings (product_id, user_id, rating, review_title, review_text, is_verified_purchase) VALUES (?, ?, ?, ?, ?, ?)");
                        $insert_rating->execute([
                            $product['id'],
                            $user['id'],
                            $sample['rating'],
                            $sample['title'],
                            $sample['text'],
                            rand(0, 1) // Random verified purchase status
                        ]);
                        $rating_count++;
                    } catch (Exception $e) {
                        // Skip if already exists
                    }
                }
            }
        }
        echo "✅ Created $rating_count sample ratings!<br>";
    }
    
    // Update product rating averages
    echo "<h2>5. Updating product rating averages...</h2>";
    $update_sql = "UPDATE products p SET 
                   average_rating = (SELECT COALESCE(AVG(rating), 0) FROM product_ratings WHERE product_id = p.id),
                   rating_count = (SELECT COUNT(*) FROM product_ratings WHERE product_id = p.id)";
    $db->exec($update_sql);
    echo "✅ Updated product rating averages!<br>";
    
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Setup Complete!</h3>";
    echo "<p style='color: #155724;'>The ratings and reviews system has been successfully set up. You can now:</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>View products with rating displays</li>";
    echo "<li>Submit ratings and reviews on product pages</li>";
    echo "<li>See average ratings in product listings</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>6. Quick Links</h2>";
    echo "<a href='products.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>View Products</a>";
    echo "<a href='product.php?id=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Test Product Page</a>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 8px;'>";
    echo "<h3 style='color: #721c24;'>❌ Setup Failed</h3>";
    echo "<p style='color: #721c24;'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2 { color: #333; }
hr { margin: 30px 0; }
</style>
