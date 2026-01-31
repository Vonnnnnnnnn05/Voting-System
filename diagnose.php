<?php
// Diagnostic script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>System Diagnostics</h2>";

// Test 1: PHP Version
echo "<h3>1. PHP Version</h3>";
echo "PHP Version: " . phpversion() . "<br><br>";

// Test 2: Database Connection
echo "<h3>2. Database Connection Test</h3>";
$host = 'localhost';
$dbname = 'voting_system';
$user = 'root';
$pass = '';

try {
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ MySQL Connection: <span style='color:green'>SUCCESS</span><br>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'voting_system'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database 'voting_system': <span style='color:green'>EXISTS</span><br>";
        
        // Connect to the database
        $pdo->exec("USE voting_system");
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "✓ Tables found: <span style='color:green'>" . count($tables) . "</span><br>";
            echo "Tables: " . implode(", ", $tables) . "<br>";
            
            // Check users table
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt->fetch()['count'];
            echo "✓ Users in database: <span style='color:green'>" . $userCount . "</span><br>";
            
            // Check admin user
            $stmt = $pdo->query("SELECT email, role, is_verified FROM users WHERE role='admin'");
            $admins = $stmt->fetchAll();
            if (count($admins) > 0) {
                echo "✓ Admin accounts found: <span style='color:green'>" . count($admins) . "</span><br>";
                foreach ($admins as $admin) {
                    echo "&nbsp;&nbsp;- {$admin['email']} (verified: {$admin['is_verified']})<br>";
                }
            } else {
                echo "✗ Admin accounts: <span style='color:red'>NONE FOUND</span><br>";
            }
            
        } else {
            echo "✗ Tables: <span style='color:red'>NO TABLES FOUND - NEED TO IMPORT SCHEMA</span><br>";
        }
        
    } else {
        echo "✗ Database 'voting_system': <span style='color:red'>NOT FOUND - NEED TO CREATE</span><br>";
    }
    
} catch (PDOException $e) {
    echo "✗ Database Error: <span style='color:red'>" . $e->getMessage() . "</span><br>";
}

// Test 3: Password Hash Test
echo "<h3>3. Password Hash Test</h3>";
$testPassword = 'admin123';
$hash = password_hash($testPassword, PASSWORD_DEFAULT);
echo "Test password: admin123<br>";
echo "Generated hash: " . $hash . "<br>";
echo "Hash verification: " . (password_verify($testPassword, $hash) ? '<span style="color:green">PASS</span>' : '<span style="color:red">FAIL</span>') . "<br><br>";

// Test 4: Config file
echo "<h3>4. Configuration Files</h3>";
if (file_exists('config/database.php')) {
    echo "✓ config/database.php: <span style='color:green'>EXISTS</span><br>";
} else {
    echo "✗ config/database.php: <span style='color:red'>MISSING</span><br>";
}

if (file_exists('config/config.php')) {
    echo "✓ config/config.php: <span style='color:green'>EXISTS</span><br>";
} else {
    echo "✗ config/config.php: <span style='color:red'>MISSING</span><br>";
}

echo "<hr>";
echo "<h3>Quick Fix Actions</h3>";
echo "<a href='setup_database.php' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Run Database Setup</a>";
?>
