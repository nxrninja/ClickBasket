<?php
// Test database connection for cPanel hosting
// Delete this file after successful testing

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Test a simple query
        $query = "SELECT 1 as test";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Database connection successful!',
            'test_query' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception('Database connection returned null');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
