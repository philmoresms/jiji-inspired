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

$tables = [
    'ads' => [
        'ad_data' => "JSON DEFAULT NULL AFTER description",
        'listing_type' => "ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale' AFTER price",
        'estimated_value' => "DECIMAL(15, 2) DEFAULT NULL AFTER listing_type",
        'swap_preference' => "TEXT DEFAULT NULL AFTER estimated_value",
        'allow_cash_topup' => "TINYINT(1) DEFAULT 0 AFTER swap_preference",
        'safety_score' => "INT DEFAULT 50 AFTER status",
        'video_url' => "VARCHAR(255) DEFAULT NULL",
        'expires_at' => "TIMESTAMP NULL DEFAULT NULL",
        'bumped_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ],
    'users' => [
        'business_name' => "VARCHAR(200) DEFAULT NULL AFTER full_name",
        'verification_tier' => "ENUM('phone_verified', 'nin_verified', 'business_verified') DEFAULT 'phone_verified' AFTER is_verified",
        'nin_number' => "VARCHAR(11) DEFAULT NULL AFTER verification_tier",
        'kyc_reference' => "VARCHAR(100) DEFAULT NULL AFTER nin_number",
        'last_verified_at' => "DATETIME DEFAULT NULL AFTER kyc_reference",
        'verification_fails' => "INT DEFAULT 0 AFTER last_verified_at",
        'locked_until' => "DATETIME DEFAULT NULL AFTER verification_fails"
    ],
    'categories' => [
        'is_top' => "TINYINT(1) DEFAULT 0",
        'sort_order' => "INT DEFAULT 0"
    ],
    'payments' => [
        'reject_reason' => "TEXT DEFAULT NULL AFTER proof_image"
    ],
    'reviews' => [
        'reply_text' => "TEXT DEFAULT NULL AFTER body",
        'replied_at' => "DATETIME DEFAULT NULL AFTER reply_text"
    ]
];

foreach ($tables as $table => $cols) {
    foreach ($cols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        } catch (Exception $e) {
            // Likely already exists. If JSON failed, try fallback for ad_data.
            if ($col === 'ad_data' && strpos($e->getMessage(), 'JSON') !== false) {
                try { $pdo->exec("ALTER TABLE ads ADD COLUMN ad_data LONGTEXT DEFAULT NULL AFTER description"); } catch (Exception $e2) {}
            }
        }
    }
}

// Update status ENUM
try {
    $pdo->exec("ALTER TABLE ads MODIFY COLUMN status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired', 'moderation') DEFAULT 'pending'");
} catch (Exception $e) {}

// Missing Tables - Use CREATE TABLE IF NOT EXISTS
$missing_tables = [
    "CREATE TABLE IF NOT EXISTS image_hashes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT,
        user_id INT,
        phash VARCHAR(64) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (phash),
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS property_declarations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT NOT NULL,
        user_id INT NOT NULL,
        declared_role ENUM('owner', 'agent') NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        verification_method VARCHAR(50) DEFAULT NULL,
        verified_at DATETIME DEFAULT NULL,
        verified_by_admin_id INT DEFAULT NULL,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS property_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        declaration_id INT NOT NULL,
        document_type VARCHAR(50),
        document_reference VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        reviewed_at DATETIME DEFAULT NULL,
        reviewer_id INT DEFAULT NULL,
        rejection_reason TEXT,
        FOREIGN KEY (declaration_id) REFERENCES property_declarations(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS reviews (
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
    )",
    "CREATE TABLE IF NOT EXISTS review_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        seller_id INT NOT NULL,
        evidence_path VARCHAR(255) DEFAULT NULL,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        deadline_at DATETIME NOT NULL,
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
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
        ip_address VARCHAR(45),
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS hyperlocal_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        city VARCHAR(100) NOT NULL,
        neighbourhood VARCHAR(100) NOT NULL,
        lat DECIMAL(10, 8),
        lng DECIMAL(11, 8),
        population INT DEFAULT 0,
        UNIQUE KEY (city, neighbourhood)
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
    "CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        content LONGTEXT,
        meta_desc TEXT,
        meta_keys TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(150) UNIQUE NOT NULL,
        summary TEXT,
        content LONGTEXT,
        image VARCHAR(255) DEFAULT NULL,
        meta_desc TEXT,
        meta_keywords TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($missing_tables as $sql) {
    try { $pdo->exec($sql); } catch (Exception $e) {}
}

// Ensure Indexes
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

$_SESSION['schema_verified'] = true;
