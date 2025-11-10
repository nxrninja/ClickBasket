<?php
// Fix script for screenshots undefined array key warnings
require_once 'config/config.php';

echo "<h1>Fix Screenshots Warnings</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if screenshots column exists
    echo "<h2>1. Check Screenshots Column</h2>";
    $check_column = $db->query("SHOW COLUMNS FROM products LIKE 'screenshots'");
    if ($check_column->rowCount() > 0) {
        echo "✅ Screenshots column exists in products table<br>";
        
        // Check column details
        $column_info = $check_column->fetch(PDO::FETCH_ASSOC);
        echo "Column type: " . $column_info['Type'] . "<br>";
        echo "Null allowed: " . $column_info['Null'] . "<br>";
        echo "Default: " . ($column_info['Default'] ?? 'NULL') . "<br>";
    } else {
        echo "❌ Screenshots column does NOT exist in products table<br>";
        echo "<p><strong>Creating screenshots column...</strong></p>";
        
        try {
            $db->exec("ALTER TABLE products ADD COLUMN screenshots TEXT DEFAULT NULL");
            echo "✅ Screenshots column created successfully!<br>";
        } catch (Exception $e) {
            echo "❌ Error creating screenshots column: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<hr>";
    
    // Check products with NULL screenshots
    echo "<h2>2. Check Products with NULL Screenshots</h2>";
    $null_screenshots = $db->query("SELECT COUNT(*) as count FROM products WHERE screenshots IS NULL OR screenshots = ''")->fetch();
    echo "Products with NULL/empty screenshots: " . $null_screenshots['count'] . "<br>";
    
    if ($null_screenshots['count'] > 0) {
        echo "<p><strong>Fixing NULL screenshots...</strong></p>";
        
        // Set default empty JSON array for products with NULL screenshots
        $fix_null = $db->prepare("UPDATE products SET screenshots = '[]' WHERE screenshots IS NULL OR screenshots = ''");
        $fix_null->execute();
        
        echo "✅ Fixed NULL screenshots by setting them to empty JSON array<br>";
    }
    
    echo "<hr>";
    
    // Show sample products and their screenshots
    echo "<h2>3. Sample Products Screenshots Data</h2>";
    $sample_products = $db->query("SELECT id, title, screenshots FROM products LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Screenshots</th><th>Parsed</th></tr>";
    
    foreach ($sample_products as $product) {
        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($product['title'], 0, 30)) . "...</td>";
        echo "<td>" . htmlspecialchars($product['screenshots'] ?? 'NULL') . "</td>";
        
        // Test parsing
        $screenshots = json_decode($product['screenshots'] ?? '[]', true);
        if (is_array($screenshots) && !empty($screenshots)) {
            echo "<td style='color: green;'>✅ " . count($screenshots) . " images</td>";
        } else {
            echo "<td style='color: red;'>❌ No images</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    // Create sample images for products without screenshots
    echo "<h2>4. Fix Products Without Images</h2>";
    
    if (isset($_POST['fix_products'])) {
        $products_without_images = $db->query("SELECT id, title FROM products WHERE screenshots IS NULL OR screenshots = '' OR screenshots = '[]'")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($products_without_images)) {
            // Create uploads directory
            $upload_dir = 'uploads/screenshots';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F'];
            $updated_count = 0;
            
            foreach ($products_without_images as $index => $product) {
                $color = $colors[$index % count($colors)];
                $filename = "product-{$product['id']}.png";
                $filepath = "$upload_dir/$filename";
                
                // Create simple image
                $image = imagecreate(300, 200);
                $rgb = sscanf($color, "#%02x%02x%02x");
                $bg_color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
                $text_color = imagecolorallocate($image, 255, 255, 255);
                
                // Add product title
                $title = substr($product['title'], 0, 15);
                imagestring($image, 4, 50, 90, $title, $text_color);
                
                imagepng($image, $filepath);
                imagedestroy($image);
                
                // Update product with screenshot
                $screenshots_json = json_encode(["$upload_dir/$filename"]);
                $update_stmt = $db->prepare("UPDATE products SET screenshots = ? WHERE id = ?");
                $update_stmt->execute([$screenshots_json, $product['id']]);
                
                $updated_count++;
                
                if ($updated_count >= 20) break; // Limit to 20 products
            }
            
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
            echo "✅ Created images and updated $updated_count products!<br>";
            echo "Images created in: $upload_dir/<br>";
            echo "</div>";
        } else {
            echo "<p>✅ All products already have screenshots data!</p>";
        }
    }
    
    $products_without_images = $db->query("SELECT COUNT(*) as count FROM products WHERE screenshots IS NULL OR screenshots = '' OR screenshots = '[]'")->fetch();
    echo "Products without images: " . $products_without_images['count'] . "<br>";
    
    if ($products_without_images['count'] > 0) {
        echo "<form method='post' style='margin: 15px 0;'>";
        echo "<input type='hidden' name='fix_products' value='1'>";
        echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer;'>🔧 Create Images for Products</button>";
        echo "</form>";
    }
    
    echo "<hr>";
    
    // Test the fixes
    echo "<h2>5. Test Fixes</h2>";
    echo "<p>After running the fixes above, test these pages:</p>";
    echo "<ul>";
    echo "<li><a href='cart.php'>Cart Page</a> - Should show product images without warnings</li>";
    echo "<li><a href='checkout.php'>Checkout Page</a> - Order summary should show images</li>";
    echo "<li><a href='products.php'>Products Page</a> - All products should have images</li>";
    echo "<li><a href='orders.php'>Orders Page</a> - Should work without warnings</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>6. Code Fix for Undefined Array Key</h2>";
echo "<p>To prevent 'Undefined array key' warnings, use this pattern in your code:</p>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 4px;'>";
echo htmlspecialchars('<?php
// Safe way to access screenshots
$screenshots = json_decode($item[\'screenshots\'] ?? \'[]\', true);
if (!empty($screenshots) && isset($screenshots[0])) {
    // Display image
    echo "<img src=\'" . SITE_URL . "/" . $screenshots[0] . "\'>";
} else {
    // Display fallback
    echo "<div>No image</div>";
}
?>');
echo "</pre>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
hr { margin: 20px 0; }
pre { overflow-x: auto; }
</style>
