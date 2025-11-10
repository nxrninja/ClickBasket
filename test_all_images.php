<?php
// Comprehensive test for all product image displays
require_once 'config/config.php';

if (!is_logged_in()) {
    echo "Please <a href='login.php'>login</a> first to test images.";
    exit;
}

$database = new Database();
$db = $database->getConnection();

echo "<h1>ClickBasket Image Display Test</h1>";

// Test 1: Cart Images
echo "<h2>1. Cart Images Test</h2>";
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
        echo "<p style='color: orange;'>⚠️ No items in cart. <a href='products.php'>Add products to cart</a> to test cart images.</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($cart_items) . " items in cart</p>";
        echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
        foreach ($cart_items as $item) {
            $screenshots = json_decode($item['screenshots'], true);
            echo "<div style='border: 1px solid #ddd; padding: 10px; border-radius: 4px;'>";
            if (!empty($screenshots) && isset($screenshots[0])) {
                echo "<img src='" . SITE_URL . "/" . $screenshots[0] . "' style='width: 80px; height: 80px; object-fit: contain; border: 1px solid #ccc; background: #f5f5f5;'><br>";
                echo "<small>✅ Image: " . basename($screenshots[0]) . "</small>";
            } else {
                echo "<div style='width: 80px; height: 80px; background: linear-gradient(45deg, #6366f1, #f59e0b); display: flex; align-items: center; justify-content: center; color: white;'><i class='fas fa-image'></i></div><br>";
                echo "<small>❌ No image</small>";
            }
            echo "<br><strong>" . htmlspecialchars(substr($item['title'], 0, 15)) . "...</strong>";
            echo "</div>";
        }
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Products with Images
echo "<h2>2. Products with Images</h2>";
try {
    $products_query = "SELECT id, title, screenshots FROM products WHERE is_active = 1 LIMIT 10";
    $products_stmt = $db->prepare($products_query);
    $products_stmt->execute();
    $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;'>";
    foreach ($products as $product) {
        $screenshots = json_decode($product['screenshots'], true);
        echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
        echo "<h4>" . htmlspecialchars(substr($product['title'], 0, 20)) . "...</h4>";
        
        if (!empty($screenshots) && isset($screenshots[0])) {
            echo "<img src='" . SITE_URL . "/" . $screenshots[0] . "' style='width: 100%; height: 120px; object-fit: contain; border: 1px solid #ccc; background: #f5f5f5; border-radius: 4px;'><br>";
            echo "<small style='color: green;'>✅ Has image</small><br>";
            echo "<small>Path: " . $screenshots[0] . "</small>";
        } else {
            echo "<div style='width: 100%; height: 120px; background: linear-gradient(45deg, #6366f1, #f59e0b); display: flex; align-items: center; justify-content: center; color: white; border-radius: 4px;'><i class='fas fa-image'></i></div><br>";
            echo "<small style='color: red;'>❌ No image</small>";
        }
        echo "</div>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 3: Create Sample Images
echo "<h2>3. Create Sample Images</h2>";
if (isset($_POST['create_sample_images'])) {
    $upload_dir = 'uploads/screenshots';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "<p>✅ Created directory: $upload_dir</p>";
    }
    
    $colors = [
        ['#FF6B6B', 'Product 1'],
        ['#4ECDC4', 'Product 2'], 
        ['#45B7D1', 'Product 3'],
        ['#96CEB4', 'Product 4'],
        ['#FFEAA7', 'Product 5'],
        ['#DDA0DD', 'Product 6'],
        ['#98D8C8', 'Product 7'],
        ['#F7DC6F', 'Product 8']
    ];
    
    $created_images = [];
    
    foreach ($colors as $index => $color_info) {
        $filename = "sample-product-" . ($index + 1) . ".png";
        $filepath = "$upload_dir/$filename";
        
        // Create image with GD
        $image = imagecreate(300, 200);
        $hex = $color_info[0];
        $rgb = sscanf($hex, "#%02x%02x%02x");
        $bg_color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        $text_color = imagecolorallocate($image, 255, 255, 255);
        $shadow_color = imagecolorallocate($image, 0, 0, 0);
        
        // Add text with shadow
        imagestring($image, 5, 101, 91, $color_info[1], $shadow_color);
        imagestring($image, 5, 100, 90, $color_info[1], $text_color);
        
        imagepng($image, $filepath);
        imagedestroy($image);
        
        $created_images[] = $filename;
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
    echo "<h4>✅ Created " . count($created_images) . " sample images!</h4>";
    echo "<div style='display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;'>";
    foreach ($created_images as $img) {
        echo "<img src='$upload_dir/$img' style='width: 80px; height: 60px; object-fit: contain; border: 1px solid #ccc;'>";
    }
    echo "</div>";
    echo "</div>";
    
    // Update products with images
    try {
        $products_query = "SELECT id FROM products WHERE is_active = 1 ORDER BY id LIMIT " . count($created_images);
        $products_stmt = $db->prepare($products_query);
        $products_stmt->execute();
        $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated_count = 0;
        foreach ($products as $index => $product) {
            if (isset($created_images[$index])) {
                $screenshots_json = json_encode(["$upload_dir/{$created_images[$index]}"]);
                $update_query = "UPDATE products SET screenshots = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$screenshots_json, $product['id']]);
                $updated_count++;
            }
        }
        
        echo "<p style='color: green;'>✅ Updated $updated_count products with sample images!</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error updating products: " . $e->getMessage() . "</p>";
    }
}

echo "<form method='post' style='margin: 15px 0;'>";
echo "<input type='hidden' name='create_sample_images' value='1'>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px;'>🎨 Create Sample Product Images</button>";
echo "</form>";

echo "<hr>";

// Test 4: Quick Links
echo "<h2>4. Test Pages</h2>";
echo "<div style='display: flex; gap: 15px; flex-wrap: wrap;'>";
echo "<a href='cart.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🛒 View Cart</a>";
echo "<a href='checkout.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>💳 Checkout</a>";
echo "<a href='products.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🛍️ Products</a>";
echo "<a href='orders.php' style='background: #fd7e14; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>📦 Orders</a>";
echo "</div>";

echo "<hr>";
echo "<h2>5. Image Requirements</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 4px;'>";
echo "<h4>For images to display properly:</h4>";
echo "<ul>";
echo "<li>✅ Products must have screenshots field with JSON data</li>";
echo "<li>✅ Image files must exist in the specified path</li>";
echo "<li>✅ Images should be in uploads/screenshots/ directory</li>";
echo "<li>✅ Supported formats: JPG, PNG, GIF</li>";
echo "<li>✅ Recommended size: 300x200 pixels or similar aspect ratio</li>";
echo "</ul>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1400px; margin: 0 auto; padding: 20px; line-height: 1.6; }
h1, h2 { color: #333; }
hr { margin: 30px 0; border: none; border-top: 2px solid #eee; }
</style>
