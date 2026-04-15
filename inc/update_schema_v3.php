<?php
/**
 * Jiji-Inspired-1.0 Schema Update v3
 * Handles Missing Tables: saved_ads and search_history
 */

require_once __DIR__ . '/../config/config.php';

try {
    // 1. Create saved_ads table
    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ad_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        UNIQUE KEY (user_id, ad_id)
    )");

    // 2. Create search_history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS search_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        keyword VARCHAR(255),
        cat_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Migration successful
} catch (PDOException $e) {
    error_log("Schema update notice: " . $e->getMessage());
}
