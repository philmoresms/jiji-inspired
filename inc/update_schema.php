<?php
/**
 * Tibu 1.1 — Robust Auto-Schema Synchronization
 * Automatically creates and repairs database tables and columns.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/config.php';
}
if (!isset($pdo)) return;

$is_sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

// --- STAGE 0: CORE TABLE PROVISIONING ---
$core_tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255),
        email VARCHAR(255) UNIQUE,
        password VARCHAR(255),
        phone VARCHAR(20),
        business_name VARCHAR(200) DEFAULT NULL,
        wallet_balance DECIMAL(15, 2) DEFAULT 0.00,
        is_verified TINYINT(1) DEFAULT 0,
        verification_tier ENUM('phone_verified', 'nin_verified', 'business_verified') DEFAULT 'phone_verified',
        is_suspended TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parent_id INT DEFAULT 0,
        name VARCHAR(100),
        slug VARCHAR(255) UNIQUE,
        icon_class VARCHAR(50) DEFAULT 'fa-th-large',
        sort_order INT DEFAULT 0,
        is_top TINYINT(1) DEFAULT 0
    )",
    "CREATE TABLE IF NOT EXISTS ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        cat_id INT,
        state_id INT,
        lga_id INT,
        title VARCHAR(255),
        description TEXT,
        price DECIMAL(15,2),
        status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired', 'moderation') DEFAULT 'pending',
        package_type ENUM('free', 'premium', 'vip', 'diamond') DEFAULT 'free',
        is_featured TINYINT(1) DEFAULT 0,
        views INT DEFAULT 0,
        ad_data JSON DEFAULT NULL,
        listing_type ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale',
        expires_at TIMESTAMP NULL DEFAULT NULL,
        bumped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT
    )",
    "CREATE TABLE IF NOT EXISTS packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(15, 2) NOT NULL,
        duration_days INT NOT NULL,
        cashback_amount DECIMAL(15, 2) DEFAULT 0,
        features TEXT
    )",
    "CREATE TABLE IF NOT EXISTS seller_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT NOT NULL,
        viewer_id INT DEFAULT NULL,
        interaction_type VARCHAR(50) DEFAULT 'view',
        source VARCHAR(50) DEFAULT 'direct',
        ip_address VARCHAR(45),
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS wallet_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        type ENUM('credit', 'debit') NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT,
        user_id INT,
        amount DECIMAL(15, 2),
        status ENUM('pending', 'successful', 'failed') DEFAULT 'pending',
        payment_method VARCHAR(50),
        reference VARCHAR(100),
        proof_image VARCHAR(255) DEFAULT NULL,
        reject_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT,
        reviewer_id INT,
        stars INT,
        body TEXT,
        reply_text TEXT DEFAULT NULL,
        replied_at DATETIME DEFAULT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS ad_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT,
        image_path VARCHAR(255),
        is_main TINYINT(1) DEFAULT 0
    )",
    "CREATE TABLE IF NOT EXISTS states (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    )",
    "CREATE TABLE IF NOT EXISTS lgas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        state_id INT,
        name VARCHAR(100) NOT NULL
    )"
];

foreach ($core_tables as $sql) {
    try {
        $final_sql = $sql;
        if ($is_sqlite) {
            $final_sql = str_replace('INT AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $final_sql);
            $final_sql = str_replace('PRIMARY KEY AUTOINCREMENT PRIMARY KEY', 'PRIMARY KEY AUTOINCREMENT', $final_sql);
            $final_sql = preg_replace('/ENUM\([^)]+\)/', 'TEXT', $final_sql);
            $final_sql = str_replace('TINYINT(1)', 'INTEGER', $final_sql);
            $final_sql = str_replace('DECIMAL(15, 2)', 'REAL', $final_sql);
            $final_sql = str_replace('DECIMAL(15,2)', 'REAL', $final_sql);
            $final_sql = str_replace('JSON', 'TEXT', $final_sql);
        }
        $pdo->exec($final_sql);
    } catch (Exception $e) {
        // Silently skip if creation fails (likely table already exists)
    }
}

// --- STAGE 1: INCREMENTAL COLUMN UPDATES (REPAIR EXISTING TABLES) ---
$column_updates = [
    'ads' => [
        'state_id' => "INT DEFAULT 0",
        'lga_id' => "INT DEFAULT 0",
        'is_featured' => "INTEGER DEFAULT 0",
        'package_type' => "TEXT DEFAULT 'free'",
        'views' => "INTEGER DEFAULT 0",
        'ad_data' => "TEXT DEFAULT NULL",
        'listing_type' => "TEXT DEFAULT 'for_sale'",
        'expires_at' => "TIMESTAMP NULL DEFAULT NULL",
        'bumped_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ],
    'users' => [
        'wallet_balance' => "REAL DEFAULT 0.00",
        'business_name' => "VARCHAR(200) DEFAULT NULL",
        'verification_tier' => "TEXT DEFAULT 'phone_verified'",
        'is_suspended' => "INTEGER DEFAULT 0"
    ],
    'payments' => [
        'payment_method' => "VARCHAR(50)"
    ],
    'categories' => [
        'is_top' => "INTEGER DEFAULT 0",
        'sort_order' => "INTEGER DEFAULT 0",
        'slug' => "VARCHAR(255) DEFAULT NULL"
    ]
];

foreach ($column_updates as $table => $cols) {
    foreach ($cols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        } catch (Exception $e) {}
    }
}

// --- STAGE 2: DATA SEEDING ---

// Seed Categories
$count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if ($count == 0) {
    $default_cats = [
        ['Vehicles', 'vehicles', 'fa-car'],
        ['Mobile Phones & Tablets', 'mobile-phones-tablets', 'fa-mobile-alt'],
        ['Electronics', 'electronics', 'fa-plug'],
        ['Property', 'property', 'fa-home'],
        ['Home, Furniture & Appliances', 'home-furniture-appliances', 'fa-couch'],
        ['Health & Beauty', 'health-beauty', 'fa-heartbeat'],
        ['Fashion', 'fashion', 'fa-tshirt'],
        ['Sports, Arts & Outdoors', 'sports-arts-outdoors', 'fa-running'],
        ['Jobs', 'jobs', 'fa-briefcase'],
        ['Services', 'services', 'fa-concierge-bell']
    ];
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_class) VALUES (?, ?, ?)");
    foreach ($default_cats as $cat) $stmt->execute($cat);
}

// Seed Packages
$count = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
if ($count == 0) {
    $pkgs = [
        ['free', 'Free', 0, 15, 0, "Basic entry\n15 days duration"],
        ['premium', 'Premium', 2500, 30, 500, "5x more clients\n15 ads in Cars\nAds auto-renew every 24h"],
        ['vip', 'VIP', 6000, 45, 1200, "7x more clients\n30 ads in Cars\nAds auto-renew every 12h\n10 VIP TOP+ promotions"],
        ['diamond', 'Diamond', 12000, 60, 2500, "20x more clients\n70 ads in Cars\nUnlimited Property listings\nAds auto-renew every 3h\nDedicated Personal Manager"]
    ];
    $stmt = $pdo->prepare("INSERT INTO packages (slug, name, price, duration_days, cashback_amount, features) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($pkgs as $p) $stmt->execute($p);
}

// Seed States
$count = $pdo->query("SELECT COUNT(*) FROM states")->fetchColumn();
if ($count == 0) {
    $states = ['Lagos', 'Abuja (FCT)', 'Rivers', 'Oyo', 'Kano', 'Kaduna', 'Edo', 'Ogun', 'Delta', 'Anambra'];
    $stmt = $pdo->prepare("INSERT INTO states (name) VALUES (?)");
    foreach ($states as $s) $stmt->execute([$s]);
}

$_SESSION['schema_verified'] = true;
