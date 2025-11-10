<?php
// Fix script to add missing columns to products table
header('Content-Type: application/json');

try {
    require_once 'config/config.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'add_screenshots_column') {
        // Check if screenshots column exists
        $check_column = $db->query("SHOW COLUMNS FROM products LIKE 'screenshots'")->rowCount();
        
        if ($check_column == 0) {
            // Add screenshots column
            $db->exec("ALTER TABLE products ADD COLUMN screenshots TEXT DEFAULT NULL");
            echo json_encode(['success' => true, 'message' => 'Screenshots column added successfully']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Screenshots column already exists']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
