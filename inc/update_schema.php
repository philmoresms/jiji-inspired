<?php
/**
 * Tiki.ng — Comprehensive Schema Update v2
 * Based on April 2026 Developer Feature Specification
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/config.php';
}
if (!isset($pdo)) return;

try {
    $column_exists = function($table, $column) use ($pdo) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            return (bool)$stmt->fetch();
        } catch (Exception $e) { return false; }
    };

    // 1. Users Table Enhancements (Feature 01 & 11)
    $user_cols = [
        'verification_tier' => "ENUM('phone_verified', 'nin_verified', 'business_verified') DEFAULT 'phone_verified' AFTER is_verified",
        'nin_number' => "VARCHAR(11) DEFAULT NULL AFTER verification_tier",
        'kyc_reference' => "VARCHAR(100) DEFAULT NULL AFTER nin_number",
        'last_verified_at' => "DATETIME DEFAULT NULL AFTER kyc_reference",
        'verification_fails' => "INT DEFAULT 0 AFTER last_verified_at",
        'locked_until' => "DATETIME DEFAULT NULL AFTER verification_fails"
    ];
    foreach ($user_cols as $col => $def) {
        if (!$column_exists('users', $col)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN `$col` $def");
        }
    }

    // 2. Ads Table Enhancements (Feature 02, 03, 08, 12)
    $ads_cols = [
        'ad_data' => "JSON DEFAULT NULL AFTER description",
        'safety_score' => "INT DEFAULT 50 AFTER status",
        'listing_type' => "ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale' AFTER price",
        'estimated_value' => "DECIMAL(15, 2) DEFAULT NULL AFTER listing_type",
        'swap_preference' => "TEXT DEFAULT NULL AFTER estimated_value",
        'allow_cash_topup' => "TINYINT(1) DEFAULT 0 AFTER swap_preference",
        'expires_at' => "TIMESTAMP NULL DEFAULT NULL",
        'bumped_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'video_url' => "VARCHAR(255) DEFAULT NULL" // To be removed later or kept for compatibility
    ];
    foreach ($ads_cols as $col => $def) {
        if (!$column_exists('ads', $col)) {
            try {
                $pdo->exec("ALTER TABLE ads ADD COLUMN `$col` $def");
            } catch (Exception $e) {
                if ($col == 'ad_data') {
                    $pdo->exec("ALTER TABLE ads ADD COLUMN `ad_data` LONGTEXT DEFAULT NULL AFTER description");
                }
            }
        }
    }

    // Update status enum
    $pdo->exec("ALTER TABLE ads MODIFY COLUMN status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired', 'moderation') DEFAULT 'pending'");

    // 3. New Table: Image Hashes (Feature 03 & 06)
    $pdo->exec("CREATE TABLE IF NOT EXISTS image_hashes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT,
        user_id INT,
        phash VARCHAR(64) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (phash),
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )");

    // 4. New Tables: Property (Feature 12)
    $pdo->exec("CREATE TABLE IF NOT EXISTS property_declarations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT NOT NULL,
        user_id INT NOT NULL,
        declared_role ENUM('owner', 'agent') NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        verification_method VARCHAR(50) DEFAULT NULL,
        verified_at DATETIME DEFAULT NULL,
        verified_by_admin_id INT DEFAULT NULL,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS property_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        declaration_id INT NOT NULL,
        document_type VARCHAR(50),
        document_reference VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        reviewed_at DATETIME DEFAULT NULL,
        reviewer_id INT DEFAULT NULL,
        rejection_reason TEXT,
        FOREIGN KEY (declaration_id) REFERENCES property_declarations(id) ON DELETE CASCADE
    )");

    // 5. New Tables: Reviews & Challenges (Feature 05)
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_id INT NOT NULL,
        seller_id INT NOT NULL,
        stars TINYINT NOT NULL,
        tags JSON DEFAULT NULL,
        body TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        challenged TINYINT(1) DEFAULT 0,
        challenge_resolved_at DATETIME DEFAULT NULL,
        removed TINYINT(1) DEFAULT 0,
        removed_by INT DEFAULT NULL,
        remove_reason TEXT,
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS review_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        seller_id INT NOT NULL,
        evidence_path VARCHAR(255) DEFAULT NULL,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        deadline_at DATETIME NOT NULL,
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
    )");

    // 6. New Table: Seller Reputation (Feature 11)
    $pdo->exec("CREATE TABLE IF NOT EXISTS seller_reputation (
        user_id INT PRIMARY KEY,
        badge_tier ENUM('new', 'verified', 'active', 'trusted', 'business') DEFAULT 'new',
        transaction_count INT DEFAULT 0,
        avg_rating DECIMAL(3, 2) DEFAULT 0,
        dispute_count INT DEFAULT 0,
        last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 7. New Table: Seller Analytics (Feature 07)
    $pdo->exec("CREATE TABLE IF NOT EXISTS seller_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT NOT NULL,
        viewer_id INT DEFAULT NULL,
        source VARCHAR(50),
        ip_address VARCHAR(45),
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )");

    // 8. New Table: Hyper-Local SEO (Feature 09)
    $pdo->exec("CREATE TABLE IF NOT EXISTS hyperlocal_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        city VARCHAR(100) NOT NULL,
        neighbourhood VARCHAR(100) NOT NULL,
        lat DECIMAL(10, 8),
        lng DECIMAL(11, 8),
        population INT DEFAULT 0,
        UNIQUE KEY (city, neighbourhood)
    )");

    // 9. Categories updates (is_top, sort_order)
    if (!$column_exists('categories', 'is_top')) { $pdo->exec("ALTER TABLE categories ADD COLUMN is_top TINYINT(1) DEFAULT 0"); }
    if (!$column_exists('categories', 'sort_order')) { $pdo->exec("ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0"); }

    // 10. Indexes
    $indexes = [
        ['table' => 'ads', 'name' => 'cat_id', 'col' => 'cat_id'],
        ['table' => 'ads', 'name' => 'state_id', 'col' => 'state_id'],
        ['table' => 'ads', 'name' => 'status', 'col' => 'status'],
        ['table' => 'ads', 'name' => 'is_featured', 'col' => 'is_featured'],
        ['table' => 'lgas', 'name' => 'state_id', 'col' => 'state_id'],
    ];
    foreach ($indexes as $idx) {
        try {
            $pdo->exec("ALTER TABLE `{$idx['table']}` ADD INDEX `{$idx['name']}` (`{$idx['col']}`)");
        } catch (Exception $e) {}
    }

} catch (PDOException $e) {
    error_log("Tiki Migration Error: " . $e->getMessage());
}
