<?php
// Setup script to create the order_billing table
require_once 'config/config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create order_billing table
    $sql = "CREATE TABLE IF NOT EXISTS order_billing (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        billing_name VARCHAR(100) NOT NULL,
        billing_email VARCHAR(100) NOT NULL,
        billing_phone VARCHAR(20) NOT NULL,
        billing_address TEXT,
        billing_city VARCHAR(50),
        billing_state VARCHAR(50),
        billing_zip VARCHAR(20),
        billing_country VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql);
    
    // Create index
    $index_sql = "CREATE INDEX IF NOT EXISTS idx_order_billing_order_id ON order_billing(order_id)";
    $db->exec($index_sql);
    
    echo "✅ Order billing table created successfully!<br>";
    echo "✅ Index created successfully!<br>";
    echo "<br>You can now use the Cash on Delivery feature.<br>";
    echo "<a href='checkout.php'>Test Checkout</a> | <a href='index.php'>Go to Homepage</a>";
    
} catch (Exception $e) {
    echo "❌ Error creating billing table: " . $e->getMessage();
}
?>
