<?php
/**
 * AGRI STORAGE - Database Connection
 * Supports local XAMPP as well as cloud deployments (Vercel, Railway, Render, etc.) via environment variables.
 */

// 1. Resolve connection parameters from environment or defaults
$dbHost = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'sql311.infinityfree.com');
$dbPort = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306');
$dbUser = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'if0_42889765');
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : 'MCDA1234');

// Primary database name
$configuredDb = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'f0_42889765_agristorage');

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$configuredDb};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // If database name was f0_... attempt fallback to if0_... in case of prefix variation
    $altDb = (strpos($configuredDb, 'f0_') === 0 && strpos($configuredDb, 'if0_') !== 0) ? 'i' . $configuredDb : null;
    if ($altDb) {
        try {
            $altDsn = "mysql:host={$dbHost};port={$dbPort};dbname={$altDb};charset=utf8mb4";
            $pdo = new PDO($altDsn, $dbUser, $dbPass, $options);
            $configuredDb = $altDb;
        } catch (PDOException $eAlt) {
            renderDbError($configuredDb, $eAlt->getMessage());
        }
    } else {
        renderDbError($configuredDb, $e->getMessage());
    }
}

function renderDbError($dbName, $errorMsg) {
    die("
        <div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #f87171; border-radius: 12px; background-color: #fef2f2; color: #991b1b; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'>
            <h2 style='margin-top: 0; color: #991b1b;'>Agri Storage - Database Connection Error</h2>
            <p>Could not connect to the <strong>" . htmlspecialchars($dbName) . "</strong> MySQL database.</p>
            <p><strong>Troubleshooting Steps:</strong></p>
            <ol style='padding-left: 20px; line-height: 1.6;'>
                <li><strong>Local (XAMPP):</strong> Ensure MySQL service is running in XAMPP control panel.</li>
                <li><strong>Database Import:</strong> Import <code>database.sql</code> into phpMyAdmin.</li>
                <li><strong>Cloud Deployment (Vercel / Railway):</strong> Set environment variables: <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_PORT</code>.</li>
            </ol>
            <p style='font-size: 13px; color: #b91c1c; background: #fee2e2; padding: 8px 12px; border-radius: 6px; word-break: break-all;'>Error details: " . htmlspecialchars($errorMsg) . "</p>
        </div>
    ");
}

