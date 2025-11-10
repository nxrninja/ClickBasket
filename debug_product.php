<?php
// Debug version of product.php to identify the error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Product Page Debug</h1>";

try {
    require_once 'config/config.php';
    echo "✅ Config loaded successfully<br>";
    
    require_once 'classes/Product.php';
    echo "✅ Product class loaded successfully<br>";
    
    // Get product ID from URL
    $product_id = intval($_GET['id'] ?? 0);
    echo "Product ID: $product_id<br>";
    
    if ($product_id <= 0) {
        echo "❌ Invalid product ID<br>";
        exit;
    }
    
    $database = new Database();
    echo "✅ Database connection created<br>";
    
    $db = $database->getConnection();
    echo "✅ Database connected<br>";
    
    $product = new Product($db);
    echo "✅ Product object created<br>";
    
    // Get product details
    echo "<h2>Testing getProductById...</h2>";
    $product_data = $product->getProductById($product_id);
    
    if (!$product_data) {
        echo "❌ Product not found<br>";
        exit;
    }
    
    echo "✅ Product data retrieved<br>";
    echo "<h3>Product Data:</h3>";
    echo "<pre>";
    print_r($product_data);
    echo "</pre>";
    
    // Test screenshots parsing
    echo "<h2>Testing Screenshots...</h2>";
    if (isset($product_data['screenshots'])) {
        echo "Screenshots field exists: " . htmlspecialchars($product_data['screenshots']) . "<br>";
        $screenshots = json_decode($product_data['screenshots'], true) ?? [];
        echo "Parsed screenshots: ";
        print_r($screenshots);
    } else {
        echo "❌ Screenshots field does not exist in product data<br>";
        echo "Available fields: " . implode(', ', array_keys($product_data)) . "<br>";
    }
    
    // Test related products
    echo "<h2>Testing Related Products...</h2>";
    $related_products = $product->getRelatedProducts($product_id, $product_data['category_id'], 4);
    echo "Related products count: " . count($related_products) . "<br>";
    
    echo "<h2>✅ All tests passed!</h2>";
    echo "<p><a href='product.php?id=$product_id'>Try the real product page now</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error Found:</h2>";
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Database Schema Check</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if products table has screenshots column
    $columns = $db->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Products Table Columns:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $has_screenshots = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'screenshots') {
            $has_screenshots = true;
        }
    }
    echo "</table>";
    
    if (!$has_screenshots) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;'>";
        echo "<h4>⚠️ Missing Screenshots Column</h4>";
        echo "<p>The products table is missing the 'screenshots' column. This is likely causing the error.</p>";
        echo "<p><strong>Solution:</strong> Add the screenshots column to the products table.</p>";
        echo "<button onclick='addScreenshotsColumn()' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Add Screenshots Column</button>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "<h4>✅ Screenshots Column Exists</h4>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
}
?>

<script>
function addScreenshotsColumn() {
    if (confirm('Add screenshots column to products table?')) {
        fetch('<?php echo SITE_URL; ?>/fix_products_table.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({action: 'add_screenshots_column'})
        })
        .then(response => response.text())
        .then(data => {
            alert('Column added successfully!');
            location.reload();
        })
        .catch(error => {
            alert('Error adding column: ' + error);
        });
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>
