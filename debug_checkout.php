<?php
// Debug version of checkout to test images
require_once 'config/config.php';

if (!is_logged_in()) {
    echo "Please <a href='login.php'>login</a> first.";
    exit;
}

$database = new Database();
$db = $database->getConnection();

echo "<h1>Debug Checkout - Image Test</h1>";

// Get cart items with screenshots
try {
    $cart_query = "SELECT c.*, p.title, p.price, p.short_description, p.file_size, p.screenshots,
                   cat.name as category_name
                   FROM cart c
                   JOIN products p ON c.product_id = p.id
                   LEFT JOIN categories cat ON p.category_id = cat.id
                   WHERE c.user_id = ? AND p.is_active = 1
                   ORDER BY c.created_at DESC";
    $cart_stmt = $db->prepare($cart_query);
    $cart_stmt->execute([get_current_user_id()]);
    $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cart_items = [];
    echo "Error: " . $e->getMessage();
}

if (empty($cart_items)) {
    echo "<p>No items in cart. <a href='products.php'>Add some products first</a></p>";
    exit;
}

echo "<h2>Cart Items Debug</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;'>";

foreach ($cart_items as $item) {
    echo "<div style='border: 2px solid #ddd; padding: 15px; border-radius: 8px; background: #f9f9f9;'>";
    
    // Debug info
    echo "<h3>" . htmlspecialchars($item['title']) . "</h3>";
    echo "<p><strong>Screenshots field:</strong> " . htmlspecialchars($item['screenshots'] ?? 'NULL') . "</p>";
    
    // Test image display
    $screenshots = json_decode($item['screenshots'] ?? '[]', true);
    echo "<p><strong>Parsed screenshots:</strong> " . (is_array($screenshots) ? count($screenshots) . " images" : "Invalid JSON") . "</p>";
    
    if (!empty($screenshots) && isset($screenshots[0])) {
        $image_path = SITE_URL . '/' . $screenshots[0];
        echo "<p><strong>Image path:</strong> " . htmlspecialchars($image_path) . "</p>";
        
        // Test if image exists
        $local_path = $screenshots[0];
        if (file_exists($local_path)) {
            echo "<p style='color: green;'>✅ Image file exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Image file does not exist: " . htmlspecialchars($local_path) . "</p>";
        }
        
        echo "<div style='margin: 10px 0;'>";
        echo "<strong>Image Preview:</strong><br>";
        echo "<img src='$image_path' style='width: 100px; height: 80px; object-fit: contain; border: 2px solid #007bff; background: #f0f0f0;' onerror='this.style.border=\"2px solid red\"; this.alt=\"Image failed to load\";'>";
        echo "</div>";
        
        // Checkout-style thumbnail
        echo "<div style='margin: 10px 0;'>";
        echo "<strong>Checkout Style (50x50):</strong><br>";
        echo "<div style='width: 50px; height: 50px; border-radius: 0.5rem; overflow: hidden; border: 1px solid #ddd; background: #f5f5f5; display: inline-block;'>";
        echo "<img src='$image_path' style='width: 100%; height: 100%; object-fit: contain; display: block;'>";
        echo "</div>";
        echo "</div>";
        
    } else {
        echo "<p style='color: orange;'>⚠️ No screenshots available</p>";
        echo "<div style='margin: 10px 0;'>";
        echo "<strong>Fallback Icon:</strong><br>";
        echo "<div style='width: 50px; height: 50px; background: linear-gradient(45deg, #6366f1, #f59e0b); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem;'>";
        echo "<i class='fas fa-image'></i>";
        echo "</div>";
        echo "</div>";
    }
    
    echo "<p><strong>Category:</strong> " . htmlspecialchars($item['category_name']) . "</p>";
    echo "<p><strong>Price:</strong> " . format_currency($item['price']) . "</p>";
    
    echo "</div>";
}

echo "</div>";

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<a href='checkout.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🔄 Try Real Checkout</a>";
echo "<a href='test_checkout_images.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🧪 Image Fix Tool</a>";
echo "<a href='cart.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🛒 Back to Cart</a>";

echo "<hr>";
echo "<h2>Expected vs Actual</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 4px;'>";
echo "<h4>What should happen:</h4>";
echo "<ol>";
echo "<li>Cart query includes screenshots field ✅ (Fixed)</li>";
echo "<li>Screenshots field contains JSON array of image paths</li>";
echo "<li>Images display in 50x50 thumbnails in checkout order summary</li>";
echo "<li>Fallback to category icons if no images</li>";
echo "</ol>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1400px; margin: 0 auto; padding: 20px; }
hr { margin: 30px 0; }
</style>
