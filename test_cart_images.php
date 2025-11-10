<?php
// Test script to check cart images
require_once 'config/config.php';

if (!is_logged_in()) {
    echo "Please login first to test cart images.";
    exit;
}

$database = new Database();
$db = $database->getConnection();

echo "<h1>Cart Images Test</h1>";

// Check if user has items in cart
try {
    $cart_query = "SELECT c.*, p.title, p.screenshots, cat.name as category_name
                   FROM cart c
                   JOIN products p ON c.product_id = p.id
                   LEFT JOIN categories cat ON p.category_id = cat.id
                   WHERE c.user_id = ? AND p.is_active = 1";
    $cart_stmt = $db->prepare($cart_query);
    $cart_stmt->execute([get_current_user_id()]);
    $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cart_items)) {
        echo "<p>No items in cart. <a href='products.php'>Add some products to cart first</a></p>";
    } else {
        echo "<h2>Cart Items with Images:</h2>";
        
        foreach ($cart_items as $item) {
            echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; display: flex; gap: 15px;'>";
            
            // Display image
            $screenshots = json_decode($item['screenshots'], true);
            if (!empty($screenshots) && isset($screenshots[0])) {
                $image_path = SITE_URL . '/' . $screenshots[0];
                echo "<div>";
                echo "<img src='$image_path' alt='" . htmlspecialchars($item['title']) . "' style='width: 80px; height: 80px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;'>";
                echo "<br><small>Image: {$screenshots[0]}</small>";
                echo "</div>";
            } else {
                echo "<div style='width: 80px; height: 80px; background: linear-gradient(45deg, #6366f1, #f59e0b); display: flex; align-items: center; justify-content: center; color: white; border-radius: 4px;'>";
                echo "<i class='fas fa-image'></i>";
                echo "</div>";
            }
            
            // Display product info
            echo "<div>";
            echo "<h4>" . htmlspecialchars($item['title']) . "</h4>";
            echo "<p>Category: " . htmlspecialchars($item['category_name']) . "</p>";
            echo "<p>Screenshots data: " . htmlspecialchars($item['screenshots']) . "</p>";
            echo "</div>";
            
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<hr>";
echo "<h2>Create Sample Product Images</h2>";

if (isset($_POST['create_images'])) {
    // Create sample images directory
    $upload_dir = 'uploads/screenshots';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Create simple colored rectangles as sample images
    $colors = [
        ['255', '99', '132'],  // Red
        ['54', '162', '235'],  // Blue
        ['255', '205', '86'],  // Yellow
        ['75', '192', '192'],  // Green
        ['153', '102', '255'], // Purple
    ];
    
    $created_images = [];
    
    for ($i = 1; $i <= 5; $i++) {
        $filename = "sample-product-$i.png";
        $filepath = "$upload_dir/$filename";
        
        // Create a simple colored image
        $image = imagecreate(300, 200);
        $color_index = ($i - 1) % count($colors);
        $bg_color = imagecolorallocate($image, 
            $colors[$color_index][0], 
            $colors[$color_index][1], 
            $colors[$color_index][2]
        );
        $text_color = imagecolorallocate($image, 255, 255, 255);
        
        // Add text
        imagestring($image, 5, 80, 90, "Product $i", $text_color);
        
        // Save image
        imagepng($image, $filepath);
        imagedestroy($image);
        
        $created_images[] = $filename;
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<h4>✅ Sample images created successfully!</h4>";
    echo "<p>Created images:</p>";
    echo "<ul>";
    foreach ($created_images as $img) {
        echo "<li>$upload_dir/$img</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Update products with sample images
    try {
        $products_query = "SELECT id FROM products WHERE is_active = 1 LIMIT 5";
        $products_stmt = $db->prepare($products_query);
        $products_stmt->execute();
        $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $index => $product) {
            if (isset($created_images[$index])) {
                $screenshots_json = json_encode(["$upload_dir/{$created_images[$index]}"]);
                $update_query = "UPDATE products SET screenshots = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$screenshots_json, $product['id']]);
            }
        }
        
        echo "<p>✅ Updated products with sample images!</p>";
        echo "<p><a href='cart.php'>View Cart</a> | <a href='products.php'>View Products</a></p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error updating products: " . $e->getMessage() . "</p>";
    }
}

echo "<form method='post'>";
echo "<input type='hidden' name='create_images' value='1'>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Create Sample Product Images</button>";
echo "</form>";

echo "<hr>";
echo "<p><a href='cart.php'>← Back to Cart</a> | <a href='products.php'>View Products</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
</style>
