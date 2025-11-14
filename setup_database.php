<?php
// Database setup script for local XAMPP
echo "<h2>Setting up ClickBasket Database...</h2>";

try {
    // Connect to MySQL without database
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute the schema file
    $schema = file_get_contents('database/schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "<p>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
            } catch (PDOException $e) {
                echo "<p>⚠ Warning: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3>✅ Database setup completed successfully!</h3>";
    echo "<p>You can now use the mobile app to register and login.</p>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Database setup failed!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure XAMPP MySQL is running.</p>";
}
?>
