<?php
/**
 * Jiji-Inspired-1.0 Schema Update Migration
 * Robustly ensures all required columns and tables exist.
 */

require_once __DIR__ . '/../config/config.php';

if (!isset($pdo)) return;

try {
    // Helper function to check if column exists
    $column_exists = function($table, $column) use ($pdo) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM \`$table\` LIKE '$column'");
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    };

    // 1. Core Ad Columns - Direct approach
    $ads_cols = [
        'ad_data' => "JSON DEFAULT NULL AFTER description",
        'listing_type' => "ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale' AFTER price",
        'estimated_value' => "DECIMAL(15, 2) DEFAULT NULL AFTER listing_type",
        'swap_preference' => "TEXT DEFAULT NULL AFTER estimated_value",
        'allow_cash_topup' => "TINYINT(1) DEFAULT 0 AFTER swap_preference",
        'video_url' => "VARCHAR(255) DEFAULT NULL AFTER decline_reason",
        'expires_at' => "TIMESTAMP NULL DEFAULT NULL AFTER video_url",
        'bumped_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER expires_at"
    ];

    foreach ($ads_cols as $col => $def) {
        if (!$column_exists('ads', $col)) {
            try {
                $pdo->exec("ALTER TABLE ads ADD COLUMN \`$col\` $def");
            } catch (Exception $e) {
                if ($col == 'ad_data' && (strpos($e->getMessage(), 'JSON') !== false || strpos($e->getMessage(), 'syntax error') !== false)) {
                    try {
                        $pdo->exec("ALTER TABLE ads ADD COLUMN \`ad_data\` LONGTEXT DEFAULT NULL AFTER description");
                    } catch (Exception $e2) {}
                }
            }
        }
    }

    // Ensure status enum is up to date
    try {
        $pdo->exec("ALTER TABLE ads MODIFY COLUMN status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired') DEFAULT 'pending'");
    } catch (Exception $e) {}

    // 2. Category Metadata
    if (!$column_exists('categories', 'is_top')) { $pdo->exec("ALTER TABLE categories ADD COLUMN is_top TINYINT(1) DEFAULT 0"); }
    if (!$column_exists('categories', 'sort_order')) { $pdo->exec("ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0"); }

    // 3. User OTP
    if (!$column_exists('users', 'otp')) { $pdo->exec("ALTER TABLE users ADD COLUMN otp VARCHAR(6) DEFAULT NULL"); }
    if (!$column_exists('users', 'otp_expires_at')) { $pdo->exec("ALTER TABLE users ADD COLUMN otp_expires_at TIMESTAMP NULL DEFAULT NULL"); }

    // 4. Payment Rejection
    if (!$column_exists('payments', 'reject_reason')) { $pdo->exec("ALTER TABLE payments ADD COLUMN reject_reason TEXT DEFAULT NULL AFTER proof_image"); }

    // 5. Indexes
    $indexes = [
        ['table' => 'ads', 'name' => 'cat_id', 'col' => 'cat_id'],
        ['table' => 'ads', 'name' => 'state_id', 'col' => 'state_id'],
        ['table' => 'ads', 'name' => 'status', 'col' => 'status'],
        ['table' => 'ads', 'name' => 'is_featured', 'col' => 'is_featured'],
        ['table' => 'lgas', 'name' => 'state_id', 'col' => 'state_id'],
    ];
    foreach ($indexes as $idx) {
        try {
            $stmt = $pdo->query("SHOW INDEX FROM \`{$idx['table']}\` WHERE Key_name = '{$idx['name']}'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE \`{$idx['table']}\` ADD INDEX \`{$idx['name']}\` (\`{$idx['col']}\`)");
            }
        } catch (Exception $e) {}
    }

    // 6. Missing Tables
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
        meta_keywords TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ad_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, ad_id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS search_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        keyword VARCHAR(255),
        cat_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

} catch (PDOException $e) {
    error_log("Migration Error: " . $e->getMessage());
}
