<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'classifieds_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // CRITICAL: Die if connection fails to prevent downstream "member function on null" errors.
    die("Database connection failed. Please check config/config.php or run the installer.");
}
