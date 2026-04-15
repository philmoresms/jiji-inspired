<?php
/**
 * Jiji-Inspired-1.0 Schema Update Migration
 */

if (defined('MIGRATION_DONE')) return;
require_once __DIR__ . '/../config/config.php';

if (!isset($pdo)) return;

try {
    // 1. Add video_url to ads if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'video_url'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE ads ADD COLUMN video_url VARCHAR(255) DEFAULT NULL AFTER decline_reason");
        error_log("Migration: Added video_url column to ads table.");
    }

    // 2. Add indexes to ads if not exists
    $indexes = [
        'cat_id' => 'INDEX (cat_id)',
        'state_id' => 'INDEX (state_id)',
        'status' => 'INDEX (status)',
        'is_featured' => 'INDEX (is_featured)'
    ];

    foreach ($indexes as $name => $sql) {
        $stmt = $pdo->query("SHOW INDEX FROM ads WHERE Key_name = '$name'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE ads ADD $sql");
            error_log("Migration: Added $name index to ads table.");
        }
    }

    // 3. Add index to lgas if not exists
    $stmt = $pdo->query("SHOW INDEX FROM lgas WHERE Key_name = 'state_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE lgas ADD INDEX (state_id)");
        error_log("Migration: Added state_id index to lgas table.");
    }

    // 4. Add Ad lifecycle columns
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'expires_at'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE ads ADD COLUMN expires_at TIMESTAMP NULL DEFAULT NULL AFTER video_url");
        $pdo->exec("ALTER TABLE ads ADD COLUMN bumped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER expires_at");
        $pdo->exec("ALTER TABLE ads MODIFY status ENUM('pending', 'active', 'declined', 'sold', 'expired') DEFAULT 'pending'");
        error_log("Migration: Added lifecycle columns to ads table.");
    }

    // 5. Add reject_reason to payments
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'reject_reason'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN reject_reason TEXT DEFAULT NULL AFTER proof_image");
        error_log("Migration: Added reject_reason column to payments table.");
    }

    // 6. Add is_top and sort_order to categories
    $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'is_top'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN is_top TINYINT(1) DEFAULT 0, ADD COLUMN sort_order INT DEFAULT 0");
        error_log("Migration: Added is_top and sort_order to categories.");
    }

    // 7. Add OTP columns to users
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'otp'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN otp VARCHAR(6) DEFAULT NULL, ADD COLUMN otp_expires_at TIMESTAMP NULL DEFAULT NULL");
        error_log("Migration: Added OTP columns to users.");
    }

    // 8. Add Swap Feature columns
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'listing_type'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE ads ADD COLUMN listing_type ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale' AFTER price");
        $pdo->exec("ALTER TABLE ads ADD COLUMN estimated_value DECIMAL(15, 2) DEFAULT NULL AFTER listing_type");
        $pdo->exec("ALTER TABLE ads ADD COLUMN swap_preference TEXT DEFAULT NULL AFTER estimated_value");
        $pdo->exec("ALTER TABLE ads ADD COLUMN allow_cash_topup TINYINT(1) DEFAULT 0 AFTER swap_preference");
        $pdo->exec("ALTER TABLE ads MODIFY COLUMN status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired') DEFAULT 'pending'");
        error_log("Migration: Added swap columns to ads.");
    }

    // 9. Create swap_proposals table
    $pdo->exec("CREATE TABLE IF NOT EXISTS swap_proposals (
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
    )");

    // 10. Create CMS & Blog tables if not exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        content LONGTEXT,
        meta_desc TEXT,
        meta_keys TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(150) UNIQUE NOT NULL,
        summary TEXT,
        content LONGTEXT,
        image VARCHAR(255) DEFAULT NULL,
        meta_desc TEXT,
        meta_keys TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    define('MIGRATION_DONE', true);
} catch (PDOException $e) {
    // Migration might fail if already exists or other DB issues
    error_log("Migration Error: " . $e->getMessage());
}
