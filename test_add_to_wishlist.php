<?php
// Test script to add products to wishlist
require_once 'config/config.php';

if (!is_logged_in()) {
    echo "Please <a href='login.php'>login</a> first to test wishlist functionality.";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<h1>Test Add to Wishlist</h1>";

// Get some sample products
try {
    $products = $db->query("SELECT id, title, price FROM products WHERE is_active = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<p style='color: red;'>No active products found in database.</p>";
        exit;
    }
    
    echo "<h2>Available Products</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>";
    
    foreach ($products as $product) {
        // Check if already in wishlist
        $check_stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_stmt->execute([$user_id, $product['id']]);
        $in_wishlist = $check_stmt->fetch() ? true : false;
        
        echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
        echo "<h4>{$product['title']}</h4>";
        echo "<p>Price: " . format_currency($product['price']) . "</p>";
        echo "<p>Product ID: {$product['id']}</p>";
        
        if ($in_wishlist) {
            echo "<button style='background: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: not-allowed;' disabled>Already in Wishlist</button>";
            echo "<button onclick='removeFromWishlist({$product['id']})' style='background: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;'>Remove</button>";
        } else {
            echo "<button onclick='addToWishlist({$product['id']})' style='background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;'>Add to Wishlist</button>";
        }
        
        echo "</div>";
    }
    echo "</div>";
    
    echo "<hr>";
    
    // Show current wishlist count
    $count_stmt = $db->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $wishlist_count = $count_stmt->fetch()['count'];
    
    echo "<h3>Current Wishlist: $wishlist_count items</h3>";
    
    if ($wishlist_count > 0) {
        echo "<a href='wishlist.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>View Wishlist Page</a>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<script>
function addToWishlist(productId) {
    fetch('<?php echo SITE_URL; ?>/api/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added to wishlist!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
    });
}

function removeFromWishlist(productId) {
    if (confirm('Remove this product from wishlist?')) {
        fetch('<?php echo SITE_URL; ?>/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove',
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Product removed from wishlist!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Network error: ' + error);
        });
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
hr { margin: 20px 0; }
</style>
