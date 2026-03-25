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

    define('MIGRATION_DONE', true);
} catch (PDOException $e) {
    // Migration might fail if already exists or other DB issues
    error_log("Migration Error: " . $e->getMessage());
}
