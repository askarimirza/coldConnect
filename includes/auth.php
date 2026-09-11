<?php
/**
 * COLDCONNECT - Authentication & Session Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape HTML output for XSS protection
 */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Return base URL path for assets and navigation.
 * Dynamically resolves root paths on Vercel/cloud hosts and subdirectories on local XAMPP.
 */
function base_url($path = '') {
    $path = ltrim($path, '/');
    
    // Explicit environment override (useful on Vercel/Railway)
    $envBase = getenv('BASE_URL');
    if ($envBase !== false) {
        $envBase = trim($envBase, '/');
        return ($envBase === '') ? '/' . $path : '/' . $envBase . '/' . $path;
    }

    // Auto-detect base folder from SCRIPT_NAME
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($scriptName));
    
    // Strip trailing /owner if invoked from owner subdirectory
    if (preg_match('#/owner$#i', $dir)) {
        $dir = substr($dir, 0, -6);
    }
    
    $dir = rtrim($dir, '/');
    return ($dir === '' ? '' : $dir) . '/' . $path;
}

/**
 * Check if a user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user data from session
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'name'     => $_SESSION['user_name'] ?? 'User',
        'email'    => $_SESSION['user_email'] ?? '',
        'role'     => $_SESSION['user_role'] ?? 'farmer',
        'location' => $_SESSION['user_location'] ?? '',
        'phone'    => $_SESSION['user_phone'] ?? ''
    ];
}

/**
 * Restrict page access to logged-in users only
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to continue.');
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Restrict page access to Farmers only
 */
function requireFarmer() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'farmer') {
        setFlash('danger', 'Access denied. Farmer privileges required.');
        header('Location: ' . base_url('owner/dashboard.php'));
        exit;
    }
}

/**
 * Restrict page access to Cold Storage Owners only
 */
function requireOwner() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'owner') {
        setFlash('danger', 'Access denied. Cold storage owner privileges required.');
        header('Location: ' . base_url('farmer-dashboard.php'));
        exit;
    }
}

/**
 * Set a session flash message (success, danger, warning, info)
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear the session flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
