<?php
// Fix users table structure for mobile app compatibility
echo "<h2>Fixing Users Table Structure...</h2>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=clickbasket', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check current table structure
    $result = $pdo->query("DESCRIBE users");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Current columns: " . implode(', ', $columns) . "</p>";
    
    // Add first_name and last_name columns if they don't exist
    if (!in_array('first_name', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT '' AFTER id");
        echo "<p>✓ Added first_name column</p>";
    }
    
    if (!in_array('last_name', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(100) NOT NULL DEFAULT '' AFTER first_name");
        echo "<p>✓ Added last_name column</p>";
    }
    
    if (!in_array('last_login', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER reset_expires");
        echo "<p>✓ Added last_login column</p>";
    }
    
    // If name column exists, split it into first_name and last_name
    if (in_array('name', $columns)) {
        $pdo->exec("UPDATE users SET first_name = SUBSTRING_INDEX(name, ' ', 1), last_name = SUBSTRING_INDEX(name, ' ', -1) WHERE first_name = '' OR last_name = ''");
        echo "<p>✓ Migrated existing name data to first_name and last_name</p>";
        
        // Optionally drop the name column (uncomment if you want to remove it)
        // $pdo->exec("ALTER TABLE users DROP COLUMN name");
        // echo "<p>✓ Dropped old name column</p>";
    }
    
    echo "<h3>✅ Users table structure fixed successfully!</h3>";
    echo "<p>Mobile app registration should now work.</p>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Failed to fix users table!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
