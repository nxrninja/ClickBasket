<?php
// Test script to verify the SQL syntax fix
require_once 'config/config.php';

echo "<h1>SQL Syntax Fix Test</h1>";

if (!is_logged_in()) {
    echo "<p style='color: red;'>❌ Please <a href='login.php'>login</a> first</p>";
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = get_current_user_id();

echo "<h2>Testing Fixed SQL Query</h2>";

// Test the fixed pagination parameters
$page = 1;
$limit = 10;
$offset = 0;
$status_filter = 'all';

echo "<p><strong>Parameters:</strong></p>";
echo "<ul>";
echo "<li>Page: $page</li>";
echo "<li>Limit: $limit</li>";
echo "<li>Offset: $offset</li>";
echo "<li>User ID: $user_id</li>";
echo "</ul>";

try {
    // Test the fixed query
    $where_clause = "WHERE user_id = ?";
    $query_params = [$user_id];
    
    if ($status_filter !== 'all') {
        $where_clause .= " AND order_status = ?";
        $query_params[] = $status_filter;
    }
    
    // Fixed query with direct integer values for LIMIT/OFFSET
    $orders_query = "SELECT * FROM orders $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    
    echo "<h3>SQL Query:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>" . htmlspecialchars($orders_query) . "</pre>";
    
    echo "<h3>Query Parameters:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>" . json_encode($query_params, JSON_PRETTY_PRINT) . "</pre>";
    
    $orders_stmt = $db->prepare($orders_query);
    $orders_stmt->execute($query_params);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
    echo "<h4>✅ Query Executed Successfully!</h4>";
    echo "<p><strong>Orders found:</strong> " . count($orders) . "</p>";
    echo "</div>";
    
    if (count($orders) > 0) {
        echo "<h3>Sample Results:</h3>";
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f2f2f2;'>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>ID</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Order #</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Status</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Amount</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Date</th>";
        echo "</tr>";
        
        foreach (array_slice($orders, 0, 5) as $order) {
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$order['id']}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$order['order_number']}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$order['order_status']}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>₹" . number_format($order['final_amount'], 2) . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . date('M j, Y H:i', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No orders found for current user</p>";
        echo "<p><a href='fix_orders_complete.php' class='btn btn-primary'>Create Test Orders</a></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
    echo "<h4>❌ Query Failed</h4>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
}

echo "<h2>Test Different Scenarios</h2>";

// Test different pagination values
$test_scenarios = [
    ['page' => 1, 'limit' => 5, 'offset' => 0],
    ['page' => 2, 'limit' => 5, 'offset' => 5],
    ['page' => 1, 'limit' => 20, 'offset' => 0],
];

foreach ($test_scenarios as $i => $scenario) {
    echo "<h4>Scenario " . ($i + 1) . ": Page {$scenario['page']}, Limit {$scenario['limit']}</h4>";
    
    try {
        $test_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ? LIMIT {$scenario['limit']} OFFSET {$scenario['offset']}";
        $test_stmt = $db->prepare($test_query);
        $test_stmt->execute([$user_id]);
        $result = $test_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>✅ Query successful - Count: {$result['count']}</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<h2>Quick Actions</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='orders.php' class='btn btn-primary'>Test Orders Page</a>";
echo "<a href='orders.php?debug=1' class='btn btn-secondary'>Orders (Debug Mode)</a>";
echo "<a href='fix_orders_complete.php' class='btn btn-warning'>Create Test Orders</a>";
echo "</div>";

echo "<h2>Fix Summary</h2>";
echo "<div style='background: #cce7ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 4px;'>";
echo "<h4>What was fixed:</h4>";
echo "<ul>";
echo "<li><strong>SQL Syntax Error:</strong> LIMIT and OFFSET parameters cannot be used as prepared statement placeholders in MySQL/MariaDB</li>";
echo "<li><strong>Solution:</strong> Use direct integer values in the SQL query instead of parameterized placeholders</li>";
echo "<li><strong>Security:</strong> Values are cast to integers to prevent SQL injection</li>";
echo "<li><strong>Validation:</strong> Added min/max validation for page, limit, and offset values</li>";
echo "</ul>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
h1, h2, h3, h4 { color: #333; }
.btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; display: inline-block; }
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn-warning { background: #ffc107; color: black; }
pre { font-size: 0.9em; overflow-x: auto; }
</style>
