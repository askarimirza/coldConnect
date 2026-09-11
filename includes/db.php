<?php
/**
 * AGRI STORAGE - Universal Database Connection
 * Supports:
 *  1. Cloud MySQL (Aiven, Railway, PlanetScale, etc.) via environment variables.
 *  2. Local MySQL (XAMPP default localhost:3306).
 *  3. Seamless Zero-Config SQLite Fallback for Vercel Serverless / Instant Preview.
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

$isVercel = !empty(getenv('VERCEL')) || !empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']);
$dbHost   = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: null);
$dbPort   = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306');
$dbUser   = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: null);
$dbPass   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : null);
$dbName   = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: null);

$pdo = null;
$dbDriver = 'none';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 2, // Fast fail (2s) to prevent Vercel 10s invocation timeout
];

// 1. Attempt Cloud MySQL if explicitly configured in environment
if ($dbHost && $dbName && $dbUser) {
    try {
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass ?? '', $options);
        $dbDriver = 'mysql_cloud';
    } catch (PDOException $e) {
        error_log("Cloud MySQL connection failed: " . $e->getMessage());
    }
}

// 2. Attempt Local MySQL (XAMPP) if NOT on Vercel and no cloud DB succeeded
if (!$pdo && !$isVercel) {
    try {
        $localDsn = "mysql:host=127.0.0.1;port=3306;dbname=agristorage;charset=utf8mb4";
        $pdo = new PDO($localDsn, 'root', '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 1,
        ]);
        $dbDriver = 'mysql_local';
    } catch (Exception $e) {
        // Local XAMPP MySQL not running; fall through to SQLite
    }
}

// 3. Fallback to SQLite (Guarantees zero-error Vercel cloud deployment & local offline use)
if (!$pdo) {
    try {
        $sqlitePath = $isVercel 
            ? (rtrim(sys_get_temp_dir(), '/\\') . '/agristorage.sqlite') 
            : (__DIR__ . '/../agristorage.sqlite');
        $needsSeed = !file_exists($sqlitePath) || filesize($sqlitePath) === 0;

        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');

        if ($needsSeed) {
            initSqliteDatabase($pdo);
        }

        $dbDriver = 'sqlite';
        define('IS_SQLITE_DEMO', true);
    } catch (Exception $e) {
        die("Fatal Database Error: " . htmlspecialchars($e->getMessage()));
    }
}

if (!defined('IS_SQLITE_DEMO')) {
    define('IS_SQLITE_DEMO', false);
}

/**
 * Initialize and seed the SQLite fallback database
 */
function initSqliteDatabase(PDO $pdo) {
    $schema = "
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL UNIQUE,
      password TEXT NOT NULL,
      phone TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'farmer',
      location TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS cold_storages (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      owner_id INTEGER NOT NULL,
      name TEXT NOT NULL,
      location TEXT NOT NULL,
      available_capacity REAL NOT NULL,
      total_capacity REAL NOT NULL,
      temperature TEXT NOT NULL,
      price_per_kg REAL NOT NULL,
      supported_crops TEXT NOT NULL,
      minimum_quantity REAL NOT NULL DEFAULT 50.00,
      contact TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'Available',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS bookings (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      farmer_id INTEGER NOT NULL,
      storage_id INTEGER NOT NULL,
      pickup_location TEXT NOT NULL DEFAULT 'Ahmedabad',
      crop TEXT NOT NULL,
      quantity REAL NOT NULL,
      start_date DATE NOT NULL,
      end_date DATE NOT NULL,
      total_cost REAL NOT NULL,
      has_insurance INTEGER NOT NULL DEFAULT 0,
      insurance_rate REAL NOT NULL DEFAULT 85.00,
      insurance_fee REAL NOT NULL DEFAULT 0.00,
      damage_status TEXT NOT NULL DEFAULT 'None',
      damage_cause TEXT NULL,
      affected_crop TEXT NULL,
      affected_quantity REAL NULL,
      compensation_amount REAL NULL,
      status TEXT NOT NULL DEFAULT 'Pending',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (farmer_id) REFERENCES users (id) ON DELETE CASCADE,
      FOREIGN KEY (storage_id) REFERENCES cold_storages (id) ON DELETE CASCADE
    );";

    $pdo->exec($schema);

    $seeds = "
    INSERT INTO users (id, name, email, password, phone, role, location, created_at) VALUES
    (1, 'Ramesh Patel (Demo Farmer)', 'demo@coldconnect.test', '\$2y\$10\$ZNESvTtsEi4o3zxHOjBx2OsVOY4QUHq2OisOv6yKB7jP.nvZbmIsK', '+91 98765 43210', 'farmer', 'Ahmedabad', datetime('now')),
    (2, 'Haresh Shah (Demo Owner)', 'owner@coldconnect.test', '\$2y\$10\$YmPFiIdty9qtJ/FzOnJJZe3KJQ7NJ9HkRy..FdAyO9i7hxbrdZUHO', '+91 98250 11223', 'owner', 'Ahmedabad', datetime('now')),
    (3, 'Mahesh Choudhary', 'mahesh.owner@coldconnect.test', '\$2y\$10\$YmPFiIdty9qtJ/FzOnJJZe3KJQ7NJ9HkRy..FdAyO9i7hxbrdZUHO', '+91 98251 44556', 'owner', 'Vadodara', datetime('now')),
    (4, 'Govind Bhai Rabari', 'govind.farmer@coldconnect.test', '\$2y\$10\$ZNESvTtsEi4o3zxHOjBx2OsVOY4QUHq2OisOv6yKB7jP.nvZbmIsK', '+91 98980 99887', 'farmer', 'Surat', datetime('now'));

    INSERT INTO cold_storages (id, owner_id, name, location, available_capacity, total_capacity, temperature, price_per_kg, supported_crops, minimum_quantity, contact, status, created_at) VALUES
    (1, 2, 'Shree Cold Storage', 'Ahmedabad', 800.00, 2000.00, '2°C - 8°C', 2.00, 'Tomato, Potato, Onion, Apple', 100.00, '+91 98250 11223', 'Available', datetime('now')),
    (2, 3, 'Baroda Fresh Agro Vault', 'Vadodara', 1200.00, 2500.00, '4°C - 10°C', 2.30, 'Tomato, Potato, Mango, Onion', 50.00, '+91 98251 44556', 'Available', datetime('now')),
    (3, 2, 'Surat Diamond Agro Chillers', 'Surat', 1500.00, 3500.00, '1°C - 7°C', 2.20, 'Potato, Onion, Tomato, Banana', 100.00, '+91 98252 77889', 'Available', datetime('now')),
    (4, 2, 'Rajkot Saurashtra Cold Hub', 'Rajkot', 3000.00, 6000.00, '0°C - 5°C', 1.90, 'Apple, Potato, Carrot, Tomato', 200.00, '+91 98253 99001', 'Available', datetime('now')),
    (5, 3, 'Sardar Patel Warehouse', 'Anand', 1800.00, 4000.00, '3°C - 9°C', 2.10, 'Onion, Garlic, Tomato, Potato', 150.00, '+91 98254 33221', 'Available', datetime('now')),
    (6, 2, 'Gujarat Agro Cold Preserve', 'Ahmedabad', 900.00, 2000.00, '1°C - 6°C', 2.80, 'Mango, Apple, Strawberry, Tomato', 50.00, '+91 98255 66778', 'Available', datetime('now'));

    INSERT INTO bookings (id, farmer_id, storage_id, pickup_location, crop, quantity, start_date, end_date, total_cost, has_insurance, insurance_rate, insurance_fee, damage_status, damage_cause, affected_crop, affected_quantity, compensation_amount, status, created_at) VALUES
    (1, 1, 2, 'Ahmedabad', 'Potato', 300.00, date('now', '+1 day'), date('now', '+11 days'), 7245.00, 1, 85.00, 345.00, 'None', NULL, NULL, NULL, NULL, 'Accepted', datetime('now', '-2 days')),
    (2, 4, 1, 'Surat', 'Onion', 250.00, date('now', '+2 days'), date('now', '+16 days'), 7350.00, 1, 85.00, 350.00, 'Reported', 'Chamber Compressor Failure & Refrigerant Gas Leak', 'Onion', 200.00, 5950.00, 'Accepted', datetime('now', '-4 hours')),
    (3, 1, 1, 'Ahmedabad', 'Tomato', 400.00, date('now', '+3 days'), date('now', '+15 days'), 9600.00, 0, 85.00, 0.00, 'None', NULL, NULL, NULL, NULL, 'Pending', datetime('now', '-1 hour'));
    ";

    $pdo->exec($seeds);
}
