<?php
require_once 'config/config.php';

// Simple test page to verify order cancellation API
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Order Cancellation - ClickBasket</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
        #result { margin-top: 10px; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Order Cancellation API Test</h1>
    
    <?php if (!is_logged_in()): ?>
        <div class="error">
            <p>You need to be logged in to test order cancellation.</p>
            <a href="login.php">Login here</a>
        </div>
    <?php else: ?>
        <div class="success">
            <p>Logged in as User ID: <?php echo get_current_user_id(); ?></p>
        </div>
        
        <div class="test-section">
            <h3>Test Order Cancellation</h3>
            <p>Enter an order ID to test cancellation:</p>
            <input type="number" id="orderId" placeholder="Order ID" min="1">
            <button onclick="testCancelOrder()">Cancel Order</button>
            <div id="result"></div>
        </div>
        
        <div class="test-section">
            <h3>API Endpoint Status</h3>
            <p>API File: <code>/api/orders.php</code></p>
            <p>Status: <?php echo file_exists('api/orders.php') ? '<span class="success">✓ Exists</span>' : '<span class="error">✗ Missing</span>'; ?></p>
        </div>
    <?php endif; ?>

    <script>
    function testCancelOrder() {
        const orderId = document.getElementById('orderId').value;
        const resultDiv = document.getElementById('result');
        
        if (!orderId) {
            resultDiv.innerHTML = '<div class="error">Please enter an order ID</div>';
            return;
        }
        
        resultDiv.innerHTML = '<div>Testing cancellation...</div>';
        
        fetch('api/orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'cancel',
                order_id: parseInt(orderId)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<div class="success">✓ ' + data.message + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="error">✗ ' + data.message + '</div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">✗ Network error: ' + error.message + '</div>';
        });
    }
    </script>
</body>
</html>
