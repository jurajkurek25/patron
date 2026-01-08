<?php
// Common functions

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

// Require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /index.php');
        exit();
    }
}

// Sanitize output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Format price
function formatPrice($amount, $currency = 'USD') {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    $symbol = $symbols[$currency] ?? $currency;
    return $symbol . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

// Time ago function
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'práve teraz';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' hod';
    if ($diff < 604800) return floor($diff / 86400) . ' dní';
    if ($diff < 2592000) return floor($diff / 604800) . ' týždňov';
    if ($diff < 31536000) return floor($diff / 2592000) . ' mesiacov';
    return floor($diff / 31536000) . ' rokov';
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Check if user has active subscription
function hasActiveSubscription($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM user_subscriptions
        WHERE user_id = ? AND status = 'active' AND current_period_end > NOW()
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

// Check if user can access video
function canAccessVideo($userId, $video) {
    // Public videos are accessible to everyone
    if ($video['is_public'] && !$video['required_tier_id']) {
        return true;
    }

    // Admin can access everything
    if (isAdmin()) {
        return true;
    }

    // Check if user has required subscription tier
    if ($video['required_tier_id']) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM user_subscriptions
            WHERE user_id = ?
            AND tier_id >= ?
            AND status = 'active'
            AND current_period_end > NOW()
        ");
        $stmt->execute([$userId, $video['required_tier_id']]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    return false;
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Upload file
function uploadFile($file, $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg']) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('No file uploaded');
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File is too large');
    }

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = UPLOAD_DIR . 'videos/' . $filename;

    if (!is_dir(UPLOAD_DIR . 'videos/')) {
        mkdir(UPLOAD_DIR . 'videos/', 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to move uploaded file');
    }

    return 'uploads/videos/' . $filename;
}
