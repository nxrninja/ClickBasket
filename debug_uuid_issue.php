<?php
// Debug script for UUID database query issue
require_once 'config/config.php';

echo "<h1>🔍 Debug UUID Database Issue</h1>";
echo "<p>Investigating UUID: <code>e14ada4a-626f-4483-859e-e68419afce22</code></p>";

$database = new Database();
$db = $database->getConnection();
$uuid = 'e14ada4a-626f-4483-859e-e68419afce22';

echo "<h2>1. Search for UUID in All Tables</h2>";

try {
    // Get all tables in the database
    $tables_query = "SHOW TABLES";
    $tables_result = $db->query($tables_query);
    $tables = $tables_result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Searching in " . count($tables) . " tables...</p>";
    
    $found_in_tables = [];
    
    foreach ($tables as $table) {
        try {
            // Get column information for each table
            $columns_query = "DESCRIBE `$table`";
            $columns_result = $db->query($columns_query);
            $columns = $columns_result->fetchAll(PDO::FETCH_ASSOC);
            
            // Search in each column that might contain the UUID
            foreach ($columns as $column) {
                $column_name = $column['Field'];
                $column_type = $column['Type'];
                
                // Skip non-text columns that can't contain UUIDs
                if (strpos(strtolower($column_type), 'int') !== false || 
                    strpos(strtolower($column_type), 'decimal') !== false ||
                    strpos(strtolower($column_type), 'float') !== false ||
                    strpos(strtolower($column_type), 'double') !== false) {
                    continue;
                }
                
                try {
                    $search_query = "SELECT * FROM `$table` WHERE `$column_name` = ? OR `$column_name` LIKE ?";
                    $search_stmt = $db->prepare($search_query);
                    $search_stmt->execute([$uuid, "%$uuid%"]);
                    $results = $search_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($results)) {
                        $found_in_tables[] = [
                            'table' => $table,
                            'column' => $column_name,
                            'results' => $results
                        ];
                        
                        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
                        echo "<h4>✅ Found in table: <code>$table</code>, column: <code>$column_name</code></h4>";
                        echo "<p>Found " . count($results) . " record(s)</p>";
                        echo "</div>";
                    }
                } catch (Exception $e) {
                    // Skip columns that can't be searched
                    continue;
                }
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Could not search table '$table': " . $e->getMessage() . "</p>";
            continue;
        }
    }
    
    if (empty($found_in_tables)) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "<h4>❌ UUID Not Found in Database</h4>";
        echo "<p>The UUID <code>$uuid</code> was not found in any table in the database.</p>";
        echo "<p>This could mean:</p>";
        echo "<ul>";
        echo "<li>The record has been deleted</li>";
        echo "<li>The UUID is from a different database</li>";
        echo "<li>The UUID is used in a different context (session, file, etc.)</li>";
        echo "<li>There's a typo in the UUID</li>";
        echo "</ul>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
    echo "❌ Error searching database: " . $e->getMessage();
    echo "</div>";
}

echo "<h2>2. Detailed Results</h2>";

if (!empty($found_in_tables)) {
    foreach ($found_in_tables as $found) {
        echo "<div style='background: #e7f3ff; padding: 15px; border: 1px solid #b3d9ff; border-radius: 4px; margin: 15px 0;'>";
        echo "<h4>Table: <code>{$found['table']}</code> | Column: <code>{$found['column']}</code></h4>";
        
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        
        // Table headers
        if (!empty($found['results'])) {
            $first_row = $found['results'][0];
            foreach (array_keys($first_row) as $header) {
                echo "<th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>$header</th>";
            }
            echo "</tr>";
            
            // Table data
            foreach ($found['results'] as $row) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    $display_value = $value;
                    
                    // Highlight the UUID if found
                    if (strpos($value, $uuid) !== false) {
                        $display_value = str_replace($uuid, "<mark style='background: yellow;'>$uuid</mark>", htmlspecialchars($value));
                    } else {
                        $display_value = htmlspecialchars($value);
                    }
                    
                    // Truncate long values
                    if (strlen($display_value) > 100) {
                        $display_value = substr($display_value, 0, 100) . '...';
                    }
                    
                    echo "<td style='padding: 8px; border: 1px solid #ddd;'>$display_value</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";
        echo "</div>";
    }
}

echo "<h2>3. Common UUID Usage in ClickBasket</h2>";

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px; margin: 15px 0;'>";
echo "<h4>🔍 Where UUIDs are typically used:</h4>";
echo "<ul>";
echo "<li><strong>Session IDs</strong> - PHP session identifiers</li>";
echo "<li><strong>Order Numbers</strong> - Unique order identifiers</li>";
echo "<li><strong>User Tokens</strong> - Password reset, email verification</li>";
echo "<li><strong>File Names</strong> - Uploaded file identifiers</li>";
echo "<li><strong>API Keys</strong> - Authentication tokens</li>";
echo "<li><strong>Transaction IDs</strong> - Payment gateway references</li>";
echo "</ul>";
echo "</div>";

// Check specific common places for UUIDs
echo "<h2>4. Check Common UUID Locations</h2>";

// Check sessions table if it exists
try {
    $session_check = $db->query("SHOW TABLES LIKE 'sessions'")->rowCount();
    if ($session_check > 0) {
        echo "<h4>Sessions Table:</h4>";
        $session_query = "SELECT * FROM sessions WHERE session_id = ? OR session_data LIKE ?";
        $session_stmt = $db->prepare($session_query);
        $session_stmt->execute([$uuid, "%$uuid%"]);
        $session_results = $session_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($session_results)) {
            echo "<p>✅ Found in sessions table</p>";
            foreach ($session_results as $session) {
                echo "<pre>" . print_r($session, true) . "</pre>";
            }
        } else {
            echo "<p>❌ Not found in sessions table</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>⚠️ Could not check sessions table</p>";
}

// Check orders table for order numbers
try {
    echo "<h4>Orders Table:</h4>";
    $order_query = "SELECT * FROM orders WHERE order_number = ? OR transaction_id = ?";
    $order_stmt = $db->prepare($order_query);
    $order_stmt->execute([$uuid, $uuid]);
    $order_results = $order_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($order_results)) {
        echo "<p>✅ Found in orders table</p>";
        foreach ($order_results as $order) {
            echo "<pre>" . print_r($order, true) . "</pre>";
        }
    } else {
        echo "<p>❌ Not found in orders table</p>";
    }
} catch (Exception $e) {
    echo "<p>⚠️ Could not check orders table: " . $e->getMessage() . "</p>";
}

// Check users table for tokens
try {
    echo "<h4>Users Table:</h4>";
    $user_query = "SELECT id, name, email, reset_token, verification_token FROM users WHERE reset_token = ? OR verification_token = ?";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->execute([$uuid, $uuid]);
    $user_results = $user_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($user_results)) {
        echo "<p>✅ Found in users table</p>";
        foreach ($user_results as $user) {
            echo "<pre>" . print_r($user, true) . "</pre>";
        }
    } else {
        echo "<p>❌ Not found in users table</p>";
    }
} catch (Exception $e) {
    echo "<p>⚠️ Could not check users table: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Troubleshooting Steps</h2>";

echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 4px; margin: 15px 0;'>";
echo "<h4>🛠️ Next Steps to Resolve the Issue:</h4>";
echo "<ol>";
echo "<li><strong>If UUID was found:</strong> Check the context and see why it's not working</li>";
echo "<li><strong>If UUID not found:</strong> The record may have been deleted or expired</li>";
echo "<li><strong>Check application logs:</strong> Look for error messages related to this UUID</li>";
echo "<li><strong>Verify the source:</strong> Where did you get this UUID from?</li>";
echo "<li><strong>Check expiration:</strong> Some UUIDs (like tokens) have expiration times</li>";
echo "</ol>";
echo "</div>";

echo "<h2>6. Manual UUID Check</h2>";
echo "<form method='post' style='background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0;'>";
echo "<h4>Search for a different UUID:</h4>";
echo "<input type='text' name='custom_uuid' placeholder='Enter UUID to search' style='padding: 8px; width: 300px; margin-right: 10px;' value='" . ($_POST['custom_uuid'] ?? '') . "'>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 4px;'>Search</button>";
echo "</form>";

if (isset($_POST['custom_uuid']) && !empty($_POST['custom_uuid'])) {
    $custom_uuid = trim($_POST['custom_uuid']);
    echo "<h4>Search Results for: <code>$custom_uuid</code></h4>";
    
    // Quick search in main tables
    $search_tables = ['users', 'orders', 'products', 'sessions'];
    
    foreach ($search_tables as $search_table) {
        try {
            $quick_search = $db->prepare("SELECT * FROM `$search_table` WHERE CONCAT_WS('|', " . 
                "COALESCE(id, ''), COALESCE(name, ''), COALESCE(title, ''), " .
                "COALESCE(order_number, ''), COALESCE(transaction_id, ''), " .
                "COALESCE(reset_token, ''), COALESCE(verification_token, ''), " .
                "COALESCE(session_id, '')) LIKE ?");
            $quick_search->execute(["%$custom_uuid%"]);
            $quick_results = $quick_search->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($quick_results)) {
                echo "<p>✅ Found " . count($quick_results) . " result(s) in <strong>$search_table</strong></p>";
            }
        } catch (Exception $e) {
            // Table might not exist
            continue;
        }
    }
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; line-height: 1.6; }
h1, h2, h3, h4 { color: #333; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
mark { background: yellow; padding: 2px; }
</style>
