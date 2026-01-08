<?php
// Configuration file for Patron Platform
// Copy this file to config.php and update with your settings

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'patron_platform');

// Stripe Configuration
// Get your keys from https://dashboard.stripe.com/apikeys
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
define('STRIPE_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY_HERE');
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_WEBHOOK_SECRET_HERE');

// Site Configuration
define('SITE_URL', 'http://localhost');
define('SITE_NAME', 'Patron Platform');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 500 * 1024 * 1024); // 500MB

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Europe/Bratislava');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
