<?php
/**
 * Helper Functions
 * Reusable utility functions for the application
 */

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random token
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Redirect to a page
 * @param string $path
 */
function redirect($path) {
    header("Location: " . APP_URL . "/" . ltrim($path, '/'));
    exit;
}

/**
 * Set flash message
 * @param string $type (success, error, warning, info)
 * @param string $message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Log action to audit_logs table
 * @param string $action
 * @param string $details
 * @param int|null $userId
 */
function logAudit($action, $details = '', $userId = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, details, ip_address) 
            VALUES (:user_id, :action, :details, :ip_address)
        ");
        
        $stmt->execute([
            ':user_id' => $userId ?? $_SESSION['user_id'] ?? null,
            ':action' => $action,
            ':details' => $details,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'M d, Y g:i A') {
    return date($format, strtotime($date));
}

/**
 * Check if election is active
 * @param string $startDate
 * @param string $endDate
 * @return bool
 */
function isElectionActive($startDate, $endDate) {
    $now = time();
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    
    return ($now >= $start && $now <= $end);
}

/**
 * Get election status
 * @param string $startDate
 * @param string $endDate
 * @return string
 */
function getElectionStatus($startDate, $endDate) {
    $now = time();
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    
    if ($now < $start) {
        return 'upcoming';
    } elseif ($now > $end) {
        return 'ended';
    } else {
        return 'active';
    }
}

/**
 * Upload candidate photo
 * @param array $file
 * @return string|false
 */
function uploadCandidatePhoto($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Validate file
    if (!in_array($file['type'], $allowedTypes)) {
        setFlash('error', 'Invalid file type. Only JPG and PNG are allowed.');
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        setFlash('error', 'File too large. Maximum size is 2MB.');
        return false;
    }
    
    // Create upload directory if not exists
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('candidate_', true) . '.' . $extension;
    $filepath = UPLOAD_PATH . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

/**
 * Send email (basic implementation - use PHPMailer in production)
 * @param string $to
 * @param string $subject
 * @param string $message
 * @return bool
 */
function sendEmail($to, $subject, $message) {
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Note: For production, use PHPMailer or similar library
    // This is a basic implementation
    return mail($to, $subject, $message, $headers);
}

/**
 * Validate password strength
 * @param string $password
 * @return array
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}
