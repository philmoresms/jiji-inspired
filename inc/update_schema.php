<?php
/**
 * Classifieds — Aggressive Schema Sync
 * Ensures all required columns exist by trying to add them.
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/config.php';
}
if (!isset($pdo)) return;

// Simple guard to prevent running on every page load after first successful run in a session
if (isset($_SESSION['schema_verified'])) return;

$is_sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

$tables = [
    'ads' => [
        'ad_data' => "JSON DEFAULT NULL AFTER description",
        'listing_type' => "ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale' AFTER price",
        'estimated_value' => "DECIMAL(15, 2) DEFAULT NULL AFTER listing_type",
        'swap_preference' => "TEXT DEFAULT NULL AFTER estimated_value",
        'allow_cash_topup' => "TINYINT(1) DEFAULT 0 AFTER swap_preference",
        'safety_score' => "INT DEFAULT 50 AFTER status",
        'package_type' => "ENUM('free', 'premium', 'vip', 'diamond') DEFAULT 'free' AFTER safety_score",
        'video_url' => "VARCHAR(255) DEFAULT NULL",
        'expires_at' => "TIMESTAMP NULL DEFAULT NULL",
        'bumped_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ],
    'users' => [
        'business_name' => "VARCHAR(200) DEFAULT NULL AFTER full_name",
        'wallet_balance' => "DECIMAL(15, 2) DEFAULT 0.00 AFTER business_name",
        'verification_tier' => "ENUM('phone_verified', 'nin_verified', 'business_verified') DEFAULT 'phone_verified' AFTER is_verified",
        'nin_number' => "VARCHAR(11) DEFAULT NULL AFTER verification_tier",
        'kyc_reference' => "VARCHAR(100) DEFAULT NULL AFTER nin_number",
        'last_verified_at' => "DATETIME DEFAULT NULL AFTER kyc_reference",
        'verification_fails' => "INT DEFAULT 0 AFTER last_verified_at",
        'locked_until' => "DATETIME DEFAULT NULL AFTER verification_fails"
    ],
    'categories' => [
        'is_top' => "TINYINT(1) DEFAULT 0",
        'sort_order' => "INT DEFAULT 0",
        'slug' => "VARCHAR(255) UNIQUE DEFAULT NULL"
    ],
    'payments' => [
        'payment_method' => "VARCHAR(50) AFTER amount",
        'reject_reason' => "TEXT DEFAULT NULL",
        'proof_image' => "VARCHAR(255) DEFAULT NULL"
    ],
    'reviews' => [
        'reply_text' => "TEXT DEFAULT NULL AFTER body",
        'replied_at' => "DATETIME DEFAULT NULL AFTER reply_text"
    ],
    'seller_analytics' => [
        'interaction_type' => "VARCHAR(50) DEFAULT 'view' AFTER source"
    ]
];

foreach ($tables as $table => $cols) {
    foreach ($cols as $col => $def) {
        try {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$col` $def";
            if ($is_sqlite) {
                // SQLite adjustments
                $parts = explode(' AFTER ', $def);
                $sql = "ALTER TABLE `$table` ADD COLUMN `$col` " . $parts[0];
                $sql = preg_replace('/ENUM\([^)]+\)/', 'TEXT', $sql);
                $sql = str_replace('TINYINT(1)', 'INTEGER', $sql);
                $sql = str_replace('JSON', 'TEXT', $sql);
                $sql = str_replace('DECIMAL(15, 2)', 'REAL', $sql);
            }
            $pdo->exec($sql);
        } catch (Exception $e) {
            // Probably already exists
        }
    }
}

// Update status ENUM
try {
    $pdo->exec("ALTER TABLE ads MODIFY COLUMN status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired', 'moderation') DEFAULT 'pending'");
} catch (Exception $e) {}

// Missing Tables
$missing_tables = [
    "CREATE TABLE IF NOT EXISTS countries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code CHAR(2) NOT NULL UNIQUE,
        status ENUM('active', 'inactive') DEFAULT 'active'
    )",
    "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        full_name VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50),
        ip_address VARCHAR(45),
        is_success TINYINT(1),
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS ip_security (
        ip_address VARCHAR(45) PRIMARY KEY,
        status ENUM('whitelisted', 'blacklisted') DEFAULT 'whitelisted',
        reason TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS seller_reputation (
        user_id INT PRIMARY KEY,
        badge_tier ENUM('new', 'verified', 'active', 'trusted', 'business') DEFAULT 'new',
        transaction_count INT DEFAULT 0,
        avg_rating DECIMAL(3, 2) DEFAULT 0,
        dispute_count INT DEFAULT 0,
        last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS seller_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT NOT NULL,
        viewer_id INT DEFAULT NULL,
        source VARCHAR(50),
        interaction_type VARCHAR(50) DEFAULT 'view',
        ip_address VARCHAR(45),
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content LONGTEXT,
        meta_desc TEXT,
        meta_keys TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        summary TEXT,
        content LONGTEXT,
        image VARCHAR(255) DEFAULT NULL,
        meta_desc TEXT,
        meta_keywords TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS swap_proposals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT,
        offered_ad_id INT,
        sender_id INT,
        receiver_id INT,
        cash_topup DECIMAL(15, 2) DEFAULT 0,
        message TEXT,
        status ENUM('pending', 'accepted', 'declined', 'countered', 'expired') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (offered_ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS saved_ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ad_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, ad_id)
    )",
    "CREATE TABLE IF NOT EXISTS search_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        keyword VARCHAR(255),
        cat_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
    "CREATE TABLE IF NOT EXISTS wallet_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        type ENUM('credit', 'debit') NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($missing_tables as $sql) {
    try {
        $final_sql = $sql;
        if ($is_sqlite) {
            $final_sql = str_replace('INT AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $final_sql);
            $final_sql = str_replace('PRIMARY KEY AUTOINCREMENT PRIMARY KEY', 'PRIMARY KEY AUTOINCREMENT', $final_sql);
            $final_sql = str_replace('INT ', 'INTEGER ', $final_sql);
            $final_sql = str_replace('INT,', 'INTEGER,', $final_sql);
            $final_sql = str_replace('INT)', 'INTEGER)', $final_sql);
            $final_sql = preg_replace('/ENUM\([^)]+\)/', 'TEXT', $final_sql);
            $final_sql = str_replace('TINYINT(1)', 'INTEGER', $final_sql);
            $final_sql = str_replace('DECIMAL(15, 2)', 'REAL', $final_sql);
            $final_sql = str_replace('LONGTEXT', 'TEXT', $final_sql);
            $final_sql = str_replace('JSON', 'TEXT', $final_sql);
            $final_sql = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $final_sql);
            $final_sql = preg_replace('/UNIQUE KEY \([^)]+\)/i', '', $final_sql);
            $final_sql = rtrim(trim($final_sql), ',');
            // If we removed UNIQUE KEY, we might have a trailing comma before the closing parenthesis
            $final_sql = preg_replace('/,\s*\)/', ')', $final_sql);
        }
        $pdo->exec($final_sql);
    } catch (Exception $e) {
        error_log("Schema Error: " . $e->getMessage() . " in SQL: " . $final_sql);
    }
}

// Migration: Handle 'method' to 'payment_method' in payments table
try {
    $pdo->exec("UPDATE payments SET payment_method = method WHERE payment_method IS NULL AND method IS NOT NULL");
} catch (Exception $e) {}

// Seed default package features if not set
$default_settings = [
    'premium_features' => "5x more clients\n15 ads in Cars\nAds auto-renew every 24h",
    'vip_features' => "7x more clients\n30 ads in Cars\nAds auto-renew every 12h\n10 VIP TOP+ promotions",
    'diamond_features' => "20x more clients\n70 ads in Cars\nUnlimited Property listings\nAds auto-renew every 3h\nDedicated Personal Manager",
    'premium_ad_duration' => '30',
    'vip_ad_duration' => '45',
    'diamond_ad_duration' => '60',
    'boost_price' => '2500',
    'vip_price' => '6000',
    'diamond_price' => '12000'
];

foreach ($default_settings as $key => $val) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        if ($is_sqlite) {
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        }
        $stmt->execute([$key, $val]);
    } catch (Exception $e) {}
}

// Seed default packages if empty
try {
    $count = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
    if ($count == 0) {
        $pkgs = [
            ['free', 'Free', 0, 15, 0, "Basic entry\n15 days duration"],
            ['premium', 'Premium', 2500, 30, 500, "5x more clients\n15 ads in Cars\nAds auto-renew every 24h"],
            ['vip', 'VIP', 6000, 45, 1200, "7x more clients\n30 ads in Cars\nAds auto-renew every 12h\n10 VIP TOP+ promotions"],
            ['diamond', 'Diamond', 12000, 60, 2500, "20x more clients\n70 ads in Cars\nUnlimited Property listings\nAds auto-renew every 3h\nDedicated Personal Manager"]
        ];
        $sql = "INSERT INTO packages (slug, name, price, duration_days, cashback_amount, features) VALUES (?, ?, ?, ?, ?, ?)";
        if ($is_sqlite) {
            $sql = str_replace("INSERT INTO", "INSERT OR REPLACE INTO", $sql);
        }
        $stmt = $pdo->prepare($sql);
        foreach ($pkgs as $p) $stmt->execute($p);
    }
} catch (Exception $e) {}

$_SESSION['schema_verified'] = true;
