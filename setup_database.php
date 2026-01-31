<?php
// Automatic Database Setup Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Setup</h2>";

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'voting_system';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✓ Database '$dbname' created/verified</p>";
    
    // Use the database
    $pdo->exec("USE $dbname");
    
    // Drop existing tables
    echo "<p>Dropping existing tables...</p>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS audit_logs");
    $pdo->exec("DROP TABLE IF EXISTS votes");
    $pdo->exec("DROP TABLE IF EXISTS candidates");
    $pdo->exec("DROP TABLE IF EXISTS elections");
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<p>✓ Old tables dropped</p>";
    
    // Create users table
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'voter') DEFAULT 'voter',
            is_verified TINYINT(1) DEFAULT 0,
            verification_token VARCHAR(64) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_verification_token (verification_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ Users table created</p>";
    
    // Create elections table
    $pdo->exec("
        CREATE TABLE elections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ Elections table created</p>";
    
    // Create candidates table
    $pdo->exec("
        CREATE TABLE candidates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            election_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            photo VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
            INDEX idx_election (election_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ Candidates table created</p>";
    
    // Create votes table
    $pdo->exec("
        CREATE TABLE votes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            election_id INT NOT NULL,
            candidate_id INT NOT NULL,
            user_id INT NOT NULL,
            voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
            FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_vote (election_id, user_id),
            INDEX idx_election (election_id),
            INDEX idx_candidate (candidate_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ Votes table created</p>";
    
    // Create audit_logs table
    $pdo->exec("
        CREATE TABLE audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user (user_id),
            INDEX idx_action (action),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ Audit logs table created</p>";
    
    // Create admin user with properly hashed password
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, full_name, role, is_verified) 
        VALUES (:email, :password, :full_name, :role, :verified)
    ");
    $stmt->execute([
        ':email' => 'admin@votingsystem.com',
        ':password' => $adminPassword,
        ':full_name' => 'System Administrator',
        ':role' => 'admin',
        ':verified' => 1
    ]);
    echo "<p>✓ Admin user created</p>";
    
    echo "<div style='padding:20px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:20px 0;'>";
    echo "<h3 style='color:#155724;margin-top:0;'>✓ Setup Complete!</h3>";
    echo "<p style='color:#155724;'><strong>Database and tables created successfully!</strong></p>";
    echo "<p>Default Admin Account:</p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> admin@votingsystem.com</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "<p><a href='auth/login.php' style='display:inline-block;padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Go to Login</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='padding:20px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:20px 0;'>";
    echo "<h3 style='color:#721c24;margin-top:0;'>✗ Setup Failed</h3>";
    echo "<p style='color:#721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<p><a href="diagnose.php">← Back to Diagnostics</a></p>
