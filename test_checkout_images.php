<?php
// Test checkout images fix
require_once 'config/config.php';

if (!is_logged_in()) {
    echo "Please <a href='login.php'>login</a> first to test checkout images.";
    exit;
}

$database = new Database();
$db = $database->getConnection();

echo "<h1>Checkout Images Test</h1>";

// Test 1: Check cart items with screenshots
echo "<h2>1. Cart Items with Screenshots Data</h2>";
try {
    $cart_query = "SELECT c.*, p.title, p.price, p.screenshots, cat.name as category_name
                   FROM cart c
                   JOIN products p ON c.product_id = p.id
                   LEFT JOIN categories cat ON p.category_id = cat.id
                   WHERE c.user_id = ? AND p.is_active = 1";
    $cart_stmt = $db->prepare($cart_query);
    $cart_stmt->execute([get_current_user_id()]);
    $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cart_items)) {
        echo "<p style='color: orange;'>⚠️ No items in cart. <a href='products.php'>Add products to cart first</a></p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($cart_items) . " items in cart</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product</th><th>Screenshots Data</th><th>Image Preview</th><th>Status</th></tr>";
        
        foreach ($cart_items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['title']) . "</td>";
            echo "<td>" . htmlspecialchars($item['screenshots'] ?? 'NULL') . "</td>";
            
            $screenshots = json_decode($item['screenshots'] ?? '[]', true);
            if (!empty($screenshots) && isset($screenshots[0])) {
                $image_path = SITE_URL . '/' . $screenshots[0];
                echo "<td><img src='$image_path' style='width: 60px; height: 40px; object-fit: contain; border: 1px solid #ccc;'></td>";
                echo "<td style='color: green;'>✅ Has Image</td>";
            } else {
                echo "<td style='background: linear-gradient(45deg, #6366f1, #f59e0b); width: 60px; height: 40px;'></td>";
                echo "<td style='color: red;'>❌ No Image</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Create sample images for products in cart
echo "<h2>2. Fix Products Without Images</h2>";

if (isset($_POST['fix_cart_images'])) {
    try {
        // Get products in cart that don't have images
        $products_query = "SELECT DISTINCT p.id, p.title 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ? AND (p.screenshots IS NULL OR p.screenshots = '' OR p.screenshots = '[]')";
        $products_stmt = $db->prepare($products_query);
        $products_stmt->execute([get_current_user_id()]);
        $products_without_images = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($products_without_images)) {
            $upload_dir = 'uploads/screenshots';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD'];
            $updated_count = 0;
            
            foreach ($products_without_images as $index => $product) {
                $color = $colors[$index % count($colors)];
                $filename = "checkout-product-{$product['id']}.png";
                $filepath = "$upload_dir/$filename";
                
                // Create image
                $image = imagecreate(300, 200);
                $rgb = sscanf($color, "#%02x%02x%02x");
                $bg_color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
                $text_color = imagecolorallocate($image, 255, 255, 255);
                
                // Add product title
                $title = substr($product['title'], 0, 20);
                imagestring($image, 4, 20, 90, $title, $text_color);
                imagestring($image, 2, 20, 110, "Product ID: {$product['id']}", $text_color);
                
                imagepng($image, $filepath);
                imagedestroy($image);
                
                // Update product
                $screenshots_json = json_encode(["$upload_dir/$filename"]);
                $update_stmt = $db->prepare("UPDATE products SET screenshots = ? WHERE id = ?");
                $update_stmt->execute([$screenshots_json, $product['id']]);
                
                $updated_count++;
            }
            
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
            echo "✅ Created images for $updated_count products in your cart!<br>";
            echo "Now refresh the checkout page to see the images.";
            echo "</div>";
        } else {
            echo "<p style='color: green;'>✅ All products in your cart already have images!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Check if any cart products need images
try {
    $need_images_query = "SELECT COUNT(DISTINCT p.id) as count
                         FROM cart c 
                         JOIN products p ON c.product_id = p.id 
                         WHERE c.user_id = ? AND (p.screenshots IS NULL OR p.screenshots = '' OR p.screenshots = '[]')";
    $need_images_stmt = $db->prepare($need_images_query);
    $need_images_stmt->execute([get_current_user_id()]);
    $need_images_count = $need_images_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($need_images_count > 0) {
        echo "<p>Products in cart without images: <strong>$need_images_count</strong></p>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='fix_cart_images' value='1'>";
        echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>🎨 Create Images for Cart Products</button>";
        echo "</form>";
    } else {
        echo "<p style='color: green;'>✅ All cart products have images!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking images: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 3: Preview checkout page
echo "<h2>3. Test Checkout Page</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 4px;'>";
echo "<h4>Steps to test:</h4>";
echo "<ol>";
echo "<li>Make sure you have items in your cart</li>";
echo "<li>If products don't have images, use the button above to create them</li>";
echo "<li>Visit the checkout page to see the images</li>";
echo "<li>Images should appear in the order summary section</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='cart.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🛒 View Cart</a>";
echo "<a href='checkout.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>💳 Go to Checkout</a>";
echo "<a href='products.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🛍️ Add Products</a>";
echo "</div>";

echo "<hr>";

// Test 4: Show the fix applied
echo "<h2>4. Fix Applied</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 4px;'>";
echo "<h4>✅ Fixed checkout.php cart query:</h4>";
echo "<p><strong>Before:</strong> Query was missing 'screenshots' field</p>";
echo "<p><strong>After:</strong> Added 'p.screenshots' to the SELECT statement</p>";
echo "<p>This ensures that product images are available in the checkout order summary.</p>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
hr { margin: 20px 0; }
</style>
