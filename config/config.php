<?php
/**
 * Application Configuration
 * Define application-wide constants and settings
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application settings
define('APP_NAME', 'Online Voting System');
define('APP_URL', 'http://localhost/voting%20system');
define('APP_VERSION', '1.0.0');

// Path settings
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/images/candidates/');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour

// Email settings (configure for production)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@votingsystem.com');
define('SMTP_FROM_NAME', APP_NAME);

// Timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/helpers.php';
require_once BASE_PATH . '/core/auth.php';
require_once BASE_PATH . '/core/csrf.php';
