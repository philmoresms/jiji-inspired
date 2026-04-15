<?php
/**
 * Jiji-Inspired-1.0 Schema Update v2
 * Handles Swap Feature fields and tables
 */

require_once __DIR__ . '/../config/config.php';

try {
    // 1. Update ads table with swap fields
    $pdo->exec("ALTER TABLE ads ADD COLUMN listing_type ENUM('for_sale', 'for_swap', 'for_sale_or_swap') DEFAULT 'for_sale' AFTER price");
    $pdo->exec("ALTER TABLE ads ADD COLUMN estimated_value DECIMAL(15, 2) DEFAULT NULL AFTER listing_type");
    $pdo->exec("ALTER TABLE ads ADD COLUMN swap_preference TEXT DEFAULT NULL AFTER estimated_value");
    $pdo->exec("ALTER TABLE ads ADD COLUMN allow_cash_topup TINYINT(1) DEFAULT 0 AFTER swap_preference");

    // 2. Update status enum in ads table (Note: ALTER ENUM is tricky in some SQL flavors, using MODIFY)
    // For MySQL:
    $pdo->exec("ALTER TABLE ads MODIFY COLUMN status ENUM('pending', 'active', 'declined', 'sold', 'swapped', 'expired') DEFAULT 'pending'");

    // 3. Create swap_proposals table
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

    echo "Schema updated to v2 successfully.";
} catch (PDOException $e) {
    // Column already exists?
    echo "Schema update notice: " . $e->getMessage();
}
