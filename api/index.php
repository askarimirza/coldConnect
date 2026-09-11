<?php
/**
 * COLDCONNECT - Vercel Serverless Front Controller Router
 * Dynamically routes incoming serverless requests to project PHP endpoints.
 */

// Output buffering prevents premature output from breaking session_start() and header redirects
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Extract path and query string
$parts = parse_url($_SERVER['REQUEST_URI'] ?? '/');
$uri = trim($parts['path'] ?? '/', '/');
if (!empty($parts['query'])) {
    parse_str($parts['query'], $queryParams);
    $_GET = array_merge($queryParams, $_GET);
}
$rootDir = dirname(__DIR__);

// Ignore favicon.ico if not present
if ($uri === 'favicon.ico') {
    http_response_code(204);
    exit;
}

// Default to index.php if root is requested
if ($uri === '' || $uri === 'index.php') {
    $targetFile = $rootDir . '/index.php';
} else {
    $candidate = $rootDir . '/' . $uri;

    if (is_file($candidate) && pathinfo($candidate, PATHINFO_EXTENSION) === 'php') {
        $targetFile = $candidate;
    } elseif (is_file($candidate . '.php')) {
        $targetFile = $candidate . '.php';
    } elseif (is_dir($candidate) && is_file($candidate . '/index.php')) {
        $targetFile = $candidate . '/index.php';
    } elseif (is_dir($candidate) && is_file($candidate . '/dashboard.php')) {
        $targetFile = $candidate . '/dashboard.php';
    } else {
        $targetFile = null;
    }
}

if (!$targetFile || !file_exists($targetFile)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style='font-family:sans-serif;text-align:center;padding:50px;'><h1>404 - Page Not Found</h1><p>The requested page <code>/" . htmlspecialchars($uri) . "</code> does not exist.</p><p><a href='/'>Return to Home</a></p></body></html>";
    exit;
}

// Normalize $_SERVER environment variables so scripts function as if directly invoked
$relPath = str_replace('\\', '/', substr($targetFile, strlen($rootDir)));
$relPath = '/' . ltrim($relPath, '/');
$_SERVER['SCRIPT_NAME'] = $relPath;
$_SERVER['PHP_SELF'] = $relPath;
$_SERVER['SCRIPT_FILENAME'] = $targetFile;

// Switch working directory to the target file's directory for relative includes
chdir(dirname($targetFile));

// Execute the requested PHP script
require $targetFile;
