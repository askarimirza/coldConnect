<?php
/**
 * COLDCONNECT - Logout Controller
 */
require_once __DIR__ . '/includes/auth.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session to pass flash message
session_start();
setFlash('info', 'You have been safely logged out.');

header('Location: ' . base_url('login.php'));
exit;
