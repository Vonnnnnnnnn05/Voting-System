<?php
/**
 * Authentication Functions
 * Handle user authentication and session management
 */

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is verified
 * @return bool
 */
function isVerified() {
    return isLoggedIn() && isset($_SESSION['is_verified']) && $_SESSION['is_verified'] == 1;
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to access this page.');
        redirect('/auth/login.php');
    }
}

/**
 * Require admin role - redirect if not admin
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        setFlash('error', 'Access denied. Admin privileges required.');
        redirect('/index.php');
    }
}

/**
 * Require verified user - show warning but allow access
 * (Alternative: redirect to a verification notice page)
 */
function requireVerified() {
    requireLogin();
    
    if (!isVerified()) {
        // Don't redirect, just set a warning
        // User can still see the page but can't vote
        if (!isset($_SESSION['verification_warning_shown'])) {
            setFlash('warning', 'Please verify your email address to participate in voting.');
            $_SESSION['verification_warning_shown'] = true;
        }
    }
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, email, full_name, role, is_verified FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get current user failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Register new user
 * @param string $email
 * @param string $password
 * @param string $fullName
 * @return array
 */
function registerUser($email, $password, $fullName) {
    try {
        $db = getDB();
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered.'];
        }
        
        // Validate password strength
        $passwordErrors = validatePassword($password);
        if (!empty($passwordErrors)) {
            return ['success' => false, 'message' => implode('. ', $passwordErrors)];
        }
        
        // Generate verification token
        $verificationToken = generateToken();
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $db->prepare("
            INSERT INTO users (email, password, full_name, verification_token) 
            VALUES (:email, :password, :full_name, :token)
        ");
        
        $stmt->execute([
            ':email' => $email,
            ':password' => $hashedPassword,
            ':full_name' => $fullName,
            ':token' => $verificationToken
        ]);
        
        $userId = $db->lastInsertId();
        
        // Send verification email
        $verificationUrl = APP_URL . "/auth/verify.php?token=" . $verificationToken;
        $emailMessage = "
            <h2>Welcome to " . APP_NAME . "!</h2>
            <p>Hi {$fullName},</p>
            <p>Thank you for registering. Please verify your email address by clicking the link below:</p>
            <p><a href='{$verificationUrl}'>Verify Email Address</a></p>
            <p>Or copy this link: {$verificationUrl}</p>
            <p>This link will expire in 24 hours.</p>
        ";
        
        sendEmail($email, "Verify Your Email - " . APP_NAME, $emailMessage);
        
        // Log registration
        logAudit('user_registration', "New user registered: {$email}", $userId);
        
        return ['success' => true, 'message' => 'Registration successful! Please check your email to verify your account.'];
        
    } catch (PDOException $e) {
        error_log("Registration failed: " . $e->getMessage());
        // More detailed error for debugging (remove in production)
        $errorMsg = 'Registration failed. ';
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $errorMsg .= 'Email already exists.';
        } else {
            $errorMsg .= 'Database error: ' . $e->getMessage();
        }
        return ['success' => false, 'message' => $errorMsg];
    }
}

/**
 * Login user
 * @param string $email
 * @param string $password
 * @return array
 */
function loginUser($email, $password) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            // Log failed attempt
            logAudit('login_failed', "Failed login attempt for: {$email}", null);
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['is_verified'] = $user['is_verified'];
        $_SESSION['login_time'] = time();
        
        // Log successful login
        logAudit('user_login', "User logged in: {$email}", $user['id']);
        
        return ['success' => true, 'message' => 'Login successful!', 'role' => $user['role']];
        
    } catch (PDOException $e) {
        error_log("Login failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed. Database error: ' . $e->getMessage()];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    if (isLoggedIn()) {
        logAudit('user_logout', "User logged out", $_SESSION['user_id']);
    }
    
    // Clear session
    session_unset();
    session_destroy();
    
    // Start new session
    session_start();
    session_regenerate_id(true);
}

/**
 * Verify email with token
 * @param string $token
 * @return bool
 */
function verifyEmail($token) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT id, email FROM users 
            WHERE verification_token = :token AND is_verified = 0
        ");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Update user as verified
        $stmt = $db->prepare("
            UPDATE users 
            SET is_verified = 1, verification_token = NULL 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $user['id']]);
        
        // Log verification
        logAudit('email_verified', "Email verified: {$user['email']}", $user['id']);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Email verification failed: " . $e->getMessage());
        return false;
    }
}
