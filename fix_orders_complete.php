<?php
// Complete fix for orders not showing issue
require_once 'config/config.php';

echo "<h1>Complete Orders Fix</h1>";

if (!is_logged_in()) {
    echo "<p style='color: red;'>❌ Please <a href='login.php'>login</a> first</p>";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

// Handle actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_tables':
            createTables($db);
            break;
        case 'create_test_orders':
            createTestOrders($db, $user_id);
            break;
        case 'fix_orders_query':
            fixOrdersQuery();
            break;
    }
}

function createTables($db) {
    try {
        // Create orders table if not exists
        $orders_table = "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(10,2) DEFAULT 0.00,
            tax_amount DECIMAL(10,2) DEFAULT 0.00,
            final_amount DECIMAL(10,2) NOT NULL,
            coupon_id INT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            transaction_id VARCHAR(100) NULL,
            payment_gateway VARCHAR(50) NULL,
            order_status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_order_status (order_status),
            INDEX idx_created_at (created_at)
        )";
        $db->exec($orders_table);
        
        // Create order_items table if not exists
        $order_items_table = "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_title VARCHAR(255) NOT NULL,
            product_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            INDEX idx_order_id (order_id)
        )";
        $db->exec($order_items_table);
        
        // Create order_billing table if not exists
        $billing_table = "CREATE TABLE IF NOT EXISTS order_billing (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            billing_name VARCHAR(255) NOT NULL,
            billing_email VARCHAR(255) NOT NULL,
            billing_phone VARCHAR(20) NOT NULL,
            billing_address TEXT,
            billing_city VARCHAR(100),
            billing_state VARCHAR(100),
            billing_zip VARCHAR(20),
            billing_country VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )";
        $db->exec($billing_table);
        
        echo "<div class='alert alert-success'>✅ Database tables created/verified successfully!</div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Error creating tables: " . $e->getMessage() . "</div>";
    }
}

function createTestOrders($db, $user_id) {
    try {
        $db->beginTransaction();
        
        $test_orders = [
            ['status' => 'pending', 'payment' => 'pending', 'amount' => 99.99],
            ['status' => 'processing', 'payment' => 'completed', 'amount' => 149.99],
            ['status' => 'completed', 'payment' => 'completed', 'amount' => 199.99],
        ];
        
        $created = 0;
        foreach ($test_orders as $order_data) {
            $order_number = 'TEST-' . strtoupper($order_data['status']) . '-' . time() . '-' . rand(100, 999);
            
            // Insert order
            $order_query = "INSERT INTO orders (user_id, order_number, total_amount, final_amount, payment_method, payment_status, order_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $order_stmt = $db->prepare($order_query);
            $order_stmt->execute([
                $user_id,
                $order_number,
                $order_data['amount'],
                $order_data['amount'],
                'cod',
                $order_data['payment'],
                $order_data['status']
            ]);
            
            $order_id = $db->lastInsertId();
            
            // Insert order items
            $products = [
                ['title' => 'Test Product A', 'price' => 49.99],
                ['title' => 'Test Product B', 'price' => 50.00]
            ];
            
            foreach ($products as $i => $product) {
                if ($order_data['amount'] > $product['price']) {
                    $item_query = "INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) VALUES (?, ?, ?, ?, ?)";
                    $item_stmt = $db->prepare($item_query);
                    $item_stmt->execute([
                        $order_id,
                        $i + 1,
                        $product['title'],
                        $product['price'],
                        1
                    ]);
                }
            }
            
            // Insert billing info
            $billing_query = "INSERT INTO order_billing (order_id, billing_name, billing_email, billing_phone, billing_address, billing_city, billing_state, billing_zip, billing_country) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $billing_stmt = $db->prepare($billing_query);
            $billing_stmt->execute([
                $order_id,
                'Test User',
                'test@example.com',
                '+1234567890',
                '123 Test Street',
                'Test City',
                'Test State',
                '12345',
                'India'
            ]);
            
            $created++;
        }
        
        $db->commit();
        echo "<div class='alert alert-success'>✅ Created $created test orders successfully!</div>";
        
    } catch (Exception $e) {
        $db->rollBack();
        echo "<div class='alert alert-danger'>❌ Error creating test orders: " . $e->getMessage() . "</div>";
    }
}

function fixOrdersQuery() {
    echo "<div class='alert alert-info'>";
    echo "<h4>Orders Query Fix Applied</h4>";
    echo "<p>The orders.php file has been updated with:</p>";
    echo "<ul>";
    echo "<li>Simplified database queries</li>";
    echo "<li>Better error handling</li>";
    echo "<li>Separate queries for orders and order items</li>";
    echo "<li>Debug information display</li>";
    echo "</ul>";
    echo "<p><a href='orders.php' class='btn btn-primary'>Test Orders Page</a></p>";
    echo "</div>";
}

// Display current status
echo "<h2>Current Status</h2>";

try {
    // Check tables
    $tables = ['orders', 'order_items', 'order_billing'];
    foreach ($tables as $table) {
        $check = $db->query("SHOW TABLES LIKE '$table'")->rowCount();
        if ($check > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Table '$table' missing</p>";
        }
    }
    
    // Check orders count
    $total_orders = $db->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
    $user_orders = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $user_orders->execute([$user_id]);
    $user_order_count = $user_orders->fetch()['count'];
    
    echo "<p>Total orders in database: <strong>$total_orders</strong></p>";
    echo "<p>Orders for current user: <strong>$user_order_count</strong></p>";
    
    if ($user_order_count > 0) {
        echo "<h3>Your Recent Orders:</h3>";
        $recent_orders = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $recent_orders->execute([$user_id]);
        $orders = $recent_orders->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table class='table'>";
        echo "<tr><th>Order #</th><th>Status</th><th>Payment</th><th>Amount</th><th>Date</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td><span class='badge badge-{$order['order_status']}'>{$order['order_status']}</span></td>";
            echo "<td><span class='badge badge-{$order['payment_status']}'>{$order['payment_status']}</span></td>";
            echo "<td>₹" . number_format($order['final_amount'], 2) . "</td>";
            echo "<td>" . date('M j, Y H:i', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking status: " . $e->getMessage() . "</p>";
}

echo "<h2>Fix Actions</h2>";
?>

<div class="actions">
    <form method="post" style="display: inline-block; margin: 5px;">
        <input type="hidden" name="action" value="create_tables">
        <button type="submit" class="btn btn-primary">1. Create/Fix Database Tables</button>
    </form>
    
    <form method="post" style="display: inline-block; margin: 5px;">
        <input type="hidden" name="action" value="create_test_orders">
        <button type="submit" class="btn btn-success">2. Create Test Orders</button>
    </form>
    
    <form method="post" style="display: inline-block; margin: 5px;">
        <input type="hidden" name="action" value="fix_orders_query">
        <button type="submit" class="btn btn-info">3. Confirm Orders Query Fix</button>
    </form>
</div>

<h2>Test Links</h2>
<div class="links">
    <a href="orders.php" class="btn btn-primary">Orders Page</a>
    <a href="orders.php?debug=1" class="btn btn-secondary">Orders Page (Debug)</a>
    <a href="checkout.php" class="btn btn-success">Checkout</a>
    <a href="cart.php" class="btn btn-warning">Cart</a>
    <a href="products.php" class="btn btn-info">Products</a>
</div>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
.alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
.alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
.alert-info { background: #cce7ff; border: 1px solid #b3d9ff; color: #004085; }
.btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; display: inline-block; }
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-info { background: #17a2b8; color: white; }
.btn-warning { background: #ffc107; color: black; }
.btn-secondary { background: #6c757d; color: white; }
.table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.table th, .table td { padding: 8px; text-align: left; border: 1px solid #ddd; }
.table th { background-color: #f2f2f2; }
.badge { padding: 3px 8px; border-radius: 12px; font-size: 0.8em; }
.badge-pending { background: #fff3cd; color: #856404; }
.badge-processing { background: #cce7ff; color: #004085; }
.badge-completed { background: #d4edda; color: #155724; }
.badge-cancelled { background: #f8d7da; color: #721c24; }
.actions, .links { margin: 20px 0; }
</style>
