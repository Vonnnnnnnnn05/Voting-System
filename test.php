<?php
// Test file to verify PHP is working
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection
try {
    require_once 'config/database.php';
    $db = getDB();
    echo "Database connection: SUCCESS<br>";
    
    // Test if tables exist
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Database tables found: " . count($tables) . "<br>";
    if (count($tables) > 0) {
        echo "Tables: " . implode(", ", $tables);
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
}
