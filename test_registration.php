<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Registration Test</title></head><body>";
echo "<h2>Registration Test & Debug</h2>";

// Test database connection first
echo "<h3>Step 1: Database Connection</h3>";
try {
    require_once 'config/database.php';
    $db = getDB();
    echo "<p style='color:green;'>✓ Database connection successful</p>";
    
    // Test if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✓ Users table exists</p>";
        
        // Get table structure
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Table columns: " . implode(", ", $columns) . "</p>";
        
    } else {
        echo "<p style='color:red;'>✗ Users table does NOT exist!</p>";
        echo "<p><a href='setup_database.php'>Click here to setup database</a></p>";
        die();
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Database Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='setup_database.php'>Click here to setup database</a></p>";
    die();
}

// Test helper functions
echo "<h3>Step 2: Helper Functions</h3>";
try {
    require_once 'core/helpers.php';
    
    // Test sanitize
    $testText = "<script>alert('test')</script>";
    $sanitized = sanitize($testText);
    echo "<p>✓ Sanitize function works: '" . htmlspecialchars($testText) . "' → '" . $sanitized . "'</p>";
    
    // Test email validation
    $validEmail = "test@example.com";
    $invalidEmail = "notanemail";
    echo "<p>✓ Email validation: '{$validEmail}' = " . (isValidEmail($validEmail) ? 'valid' : 'invalid') . "</p>";
    echo "<p>✓ Email validation: '{$invalidEmail}' = " . (isValidEmail($invalidEmail) ? 'valid' : 'invalid') . "</p>";
    
    // Test password validation
    $weakPass = "123";
    $strongPass = "Admin123";
    $errors = validatePassword($weakPass);
    echo "<p>✓ Weak password errors: " . count($errors) . " errors</p>";
    $errors = validatePassword($strongPass);
    echo "<p>✓ Strong password errors: " . count($errors) . " errors</p>";
    
    // Test token generation
    $token = generateToken();
    echo "<p>✓ Token generation works (length: " . strlen($token) . ")</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Helper Error: " . $e->getMessage() . "</p>";
    die();
}

// Test auth functions
echo "<h3>Step 3: Authentication Functions</h3>";
try {
    require_once 'core/auth.php';
    echo "<p>✓ Auth functions loaded</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Auth Error: " . $e->getMessage() . "</p>";
    die();
}

// Test registration
echo "<h3>Step 4: Test Registration</h3>";
$testEmail = "testuser_" . time() . "@example.com";
$testPassword = "TestPass123";
$testName = "Test User";

echo "<p>Attempting to register:</p>";
echo "<ul>";
echo "<li>Email: {$testEmail}</li>";
echo "<li>Password: {$testPassword}</li>";
echo "<li>Name: {$testName}</li>";
echo "</ul>";

try {
    $result = registerUser($testEmail, $testPassword, $testName);
    
    if ($result['success']) {
        echo "<p style='color:green;'>✓ Registration successful!</p>";
        echo "<p>Message: {$result['message']}</p>";
        
        // Verify user was created
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $testEmail]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p style='color:green;'>✓ User found in database</p>";
            echo "<pre>";
            echo "ID: {$user['id']}\n";
            echo "Email: {$user['email']}\n";
            echo "Name: {$user['full_name']}\n";
            echo "Role: {$user['role']}\n";
            echo "Verified: {$user['is_verified']}\n";
            echo "Token: " . substr($user['verification_token'], 0, 20) . "...\n";
            echo "</pre>";
            
            // Test password verification
            if (password_verify($testPassword, $user['password'])) {
                echo "<p style='color:green;'>✓ Password hash verification works!</p>";
            } else {
                echo "<p style='color:red;'>✗ Password hash verification FAILED!</p>";
            }
        } else {
            echo "<p style='color:red;'>✗ User NOT found in database after registration!</p>";
        }
        
    } else {
        echo "<p style='color:red;'>✗ Registration failed!</p>";
        echo "<p>Error: {$result['message']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Registration Exception: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>Next Steps</h3>";
echo "<p><a href='auth/register.php'>Go to Registration Page</a></p>";
echo "<p><a href='auth/login.php'>Go to Login Page</a></p>";
echo "<p><a href='diagnose.php'>Run Full Diagnostics</a></p>";

echo "</body></html>";
?>
