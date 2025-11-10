<?php
// Simplified wishlist page for testing
require_once 'config/config.php';

if (!is_logged_in()) {
    redirect('login.php?redirect=simple_wishlist.php');
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<!DOCTYPE html>";
echo "<html><head><title>Simple Wishlist Test</title>";
echo "<style>body{font-family:Arial;max-width:1000px;margin:0 auto;padding:20px;} .card{border:1px solid #ddd;padding:15px;margin:10px 0;border-radius:8px;} .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;}</style>";
echo "</head><body>";

echo "<h1>Simple Wishlist Test</h1>";
echo "<p><a href='wishlist.php'>← Back to Full Wishlist</a></p>";

try {
    // Get wishlist items
    $query = "SELECT w.*, p.title, p.price, p.screenshots 
              FROM wishlist w 
              JOIN products p ON w.product_id = p.id 
              WHERE w.user_id = ? AND p.is_active = 1 
              ORDER BY w.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Debug Info</h2>";
    echo "<p>User ID: $user_id</p>";
    echo "<p>Items found: " . count($items) . "</p>";
    echo "<p>SQL Query: <code>" . htmlspecialchars($query) . "</code></p>";
    
    if (empty($items)) {
        echo "<div class='card' style='background:#fff3cd;border-color:#ffeaa7;'>";
        echo "<h3>No Items Found</h3>";
        echo "<p>Your wishlist is empty. Here are some options:</p>";
        echo "<ul>";
        echo "<li><a href='product.php?id=1'>Go to a product page</a> and click 'Add to Wishlist'</li>";
        echo "<li><a href='test_add_to_wishlist.php'>Use the test script</a> to add sample items</li>";
        echo "<li><a href='fix_wishlist_display.php'>Run the fix script</a> to diagnose issues</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<h2>Wishlist Items (" . count($items) . ")</h2>";
        echo "<div class='grid'>";
        
        foreach ($items as $item) {
            echo "<div class='card'>";
            echo "<h4>" . htmlspecialchars($item['title']) . "</h4>";
            echo "<p><strong>Price:</strong> " . format_currency($item['price']) . "</p>";
            echo "<p><strong>Product ID:</strong> " . $item['product_id'] . "</p>";
            echo "<p><strong>Added:</strong> " . date('M j, Y', strtotime($item['created_at'])) . "</p>";
            
            // Show image if available
            $screenshots = json_decode($item['screenshots'] ?? '[]', true) ?? [];
            if (!empty($screenshots) && isset($screenshots[0])) {
                echo "<img src='" . htmlspecialchars($screenshots[0]) . "' style='width:100%;max-width:200px;height:auto;border-radius:4px;' alt='Product Image'>";
            } else {
                echo "<div style='background:#f8f9fa;padding:20px;text-align:center;border-radius:4px;'>No Image</div>";
            }
            
            echo "<div style='margin-top:10px;'>";
            echo "<button onclick='removeItem(" . $item['product_id'] . ")' style='background:#dc3545;color:white;padding:8px 12px;border:none;border-radius:4px;cursor:pointer;margin-right:10px;'>Remove</button>";
            echo "<a href='product.php?id=" . $item['product_id'] . "' style='background:#007bff;color:white;padding:8px 12px;text-decoration:none;border-radius:4px;'>View Product</a>";
            echo "</div>";
            
            echo "</div>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='card' style='background:#f8d7da;border-color:#f5c6cb;'>";
    echo "<h3>Error</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Quick Actions</h3>";
echo "<a href='wishlist.php' style='background:#007bff;color:white;padding:10px 15px;text-decoration:none;border-radius:4px;margin-right:10px;'>Full Wishlist</a>";
echo "<a href='product.php?id=1' style='background:#28a745;color:white;padding:10px 15px;text-decoration:none;border-radius:4px;margin-right:10px;'>Test Product</a>";
echo "<a href='fix_wishlist_display.php' style='background:#ffc107;color:black;padding:10px 15px;text-decoration:none;border-radius:4px;'>Fix Script</a>";

echo "</body></html>";

// JavaScript for remove functionality
echo "<script>";
echo "function removeItem(productId) {";
echo "  if (confirm('Remove this item from wishlist?')) {";
echo "    fetch('api/wishlist.php', {";
echo "      method: 'POST',";
echo "      headers: {'Content-Type': 'application/json'},";
echo "      body: JSON.stringify({action: 'remove', product_id: productId})";
echo "    })";
echo "    .then(response => response.json())";
echo "    .then(data => {";
echo "      if (data.success) {";
echo "        alert('Item removed!');";
echo "        location.reload();";
echo "      } else {";
echo "        alert('Error: ' + data.message);";
echo "      }";
echo "    })";
echo "    .catch(error => alert('Network error: ' + error));";
echo "  }";
echo "}";
echo "</script>";
?>
