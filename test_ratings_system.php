<?php
// Test page for the new ratings system
require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();

echo "<h1>ClickBasket Ratings System Test</h1>";

// Check if ratings table exists
echo "<h2>1. Database Setup Check</h2>";
try {
    $check_table = $db->query("SHOW TABLES LIKE 'product_ratings'");
    if ($check_table->rowCount() > 0) {
        echo "✅ Product ratings table exists<br>";
        
        // Check table structure
        $columns = $db->query("DESCRIBE product_ratings")->fetchAll(PDO::FETCH_ASSOC);
        echo "<details><summary>View table structure</summary>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table></details>";
    } else {
        echo "❌ Product ratings table does not exist<br>";
        echo "<p><a href='setup_ratings.php'>Run setup script</a></p>";
    }
    
    // Check if products table has rating columns
    $rating_columns = $db->query("SHOW COLUMNS FROM products LIKE 'average_rating'")->rowCount();
    if ($rating_columns > 0) {
        echo "✅ Products table has rating columns<br>";
    } else {
        echo "❌ Products table missing rating columns<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Check sample data
echo "<h2>2. Sample Data Check</h2>";
try {
    $ratings_count = $db->query("SELECT COUNT(*) as count FROM product_ratings")->fetch()['count'];
    echo "Total ratings in database: <strong>$ratings_count</strong><br>";
    
    if ($ratings_count > 0) {
        echo "✅ Sample ratings exist<br>";
        
        // Show sample ratings
        $sample_ratings = $db->query("SELECT pr.*, p.title as product_title, u.name as user_name FROM product_ratings pr JOIN products p ON pr.product_id = p.id JOIN users u ON pr.user_id = u.id LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Product</th><th>User</th><th>Rating</th><th>Title</th><th>Date</th></tr>";
        foreach ($sample_ratings as $rating) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($rating['product_title']) . "</td>";
            echo "<td>" . htmlspecialchars($rating['user_name']) . "</td>";
            echo "<td>" . str_repeat('★', $rating['rating']) . str_repeat('☆', 5 - $rating['rating']) . "</td>";
            echo "<td>" . htmlspecialchars($rating['review_title']) . "</td>";
            echo "<td>" . date('M j, Y', strtotime($rating['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No ratings found. <a href='setup_ratings.php'>Create sample data</a><br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking sample data: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test API endpoints
echo "<h2>3. API Endpoints Test</h2>";

// Test get ratings API
echo "<h3>Get Ratings API Test</h3>";
$products = $db->query("SELECT id, title FROM products LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px;'>";
    echo "<h4>" . htmlspecialchars($product['title']) . " (ID: {$product['id']})</h4>";
    
    echo "<div id='rating-test-{$product['id']}'>";
    echo "<button onclick='testGetRatings({$product['id']})' style='background: #007bff; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer;'>Test Get Ratings</button>";
    echo "</div>";
    
    echo "</div>";
}

echo "<hr>";

// Test product page links
echo "<h2>4. Product Page Tests</h2>";
echo "<p>Test the new rating system on actual product pages:</p>";

$test_products = $db->query("SELECT id, title FROM products WHERE is_active = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>";
foreach ($test_products as $product) {
    echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4>" . htmlspecialchars($product['title']) . "</h4>";
    echo "<a href='product.php?id={$product['id']}' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block;'>View Product</a>";
    echo "</div>";
}
echo "</div>";

echo "<hr>";

// User login status
echo "<h2>5. User Login Status</h2>";
if (is_logged_in()) {
    $user_id = get_current_user_id();
    echo "✅ User is logged in (ID: $user_id)<br>";
    echo "<p>You can submit ratings and reviews on product pages.</p>";
} else {
    echo "❌ User is not logged in<br>";
    echo "<p>You need to <a href='login.php'>login</a> to submit ratings and reviews.</p>";
}

echo "<hr>";

// Features summary
echo "<h2>6. Features Implemented</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";
echo "<h3>✅ Completed Features:</h3>";
echo "<ul>";
echo "<li><strong>Removed Download Elements:</strong> Downloads count and instant download feature removed from product pages</li>";
echo "<li><strong>5-Star Rating System:</strong> Interactive star rating with hover effects</li>";
echo "<li><strong>Review Submission:</strong> Users can write detailed reviews with titles</li>";
echo "<li><strong>Rating Display:</strong> Average ratings and rating breakdowns</li>";
echo "<li><strong>Verified Purchase Badges:</strong> Shows if reviewer actually purchased the product</li>";
echo "<li><strong>Rating Statistics:</strong> Average rating displayed in product stats</li>";
echo "<li><strong>Reviews List:</strong> Displays all reviews with user names and dates</li>";
echo "<li><strong>API Integration:</strong> RESTful API for rating operations</li>";
echo "</ul>";

echo "<h3>🎯 Key Benefits:</h3>";
echo "<ul>";
echo "<li>Better user engagement with review system</li>";
echo "<li>Social proof through ratings and reviews</li>";
echo "<li>Improved product discovery</li>";
echo "<li>Enhanced user experience</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";

echo "<h2>7. Quick Actions</h2>";
echo "<a href='setup_ratings.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Setup Database</a>";
echo "<a href='products.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>View Products</a>";
echo "<a href='product.php?id=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Test Product Page</a>";
?>

<script>
function testGetRatings(productId) {
    const container = document.getElementById(`rating-test-${productId}`);
    container.innerHTML = '<div style="color: #007bff;"><i class="fas fa-spinner fa-spin"></i> Testing API...</div>';
    
    fetch(`<?php echo SITE_URL; ?>/api/ratings.php?action=get_ratings&product_id=${productId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const ratings = data.ratings;
            const reviews = data.reviews;
            
            let html = '<div style="background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0;">';
            html += '<h5 style="color: #155724; margin: 0 0 10px 0;">✅ API Test Successful</h5>';
            html += `<p><strong>Average Rating:</strong> ${ratings.average_rating}/5</p>`;
            html += `<p><strong>Total Reviews:</strong> ${ratings.total_reviews}</p>`;
            html += `<p><strong>Rating Breakdown:</strong> 5★(${ratings.rating_breakdown[5]}) 4★(${ratings.rating_breakdown[4]}) 3★(${ratings.rating_breakdown[3]}) 2★(${ratings.rating_breakdown[2]}) 1★(${ratings.rating_breakdown[1]})</p>`;
            html += `<p><strong>Recent Reviews:</strong> ${reviews.length} loaded</p>`;
            html += '</div>';
            
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div style="background: #f8d7da; padding: 10px; border-radius: 4px; color: #721c24;">❌ API Error: ${data.message}</div>`;
        }
    })
    .catch(error => {
        container.innerHTML = `<div style="background: #f8d7da; padding: 10px; border-radius: 4px; color: #721c24;">❌ Network Error: ${error.message}</div>`;
    });
}
</script>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
hr { margin: 30px 0; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
details { margin: 10px 0; }
</style>
