<?php
require_once 'config/config.php';

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM \`$table\` LIKE '$column'");
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

try {
    if (!columnExists($pdo, 'ads', 'ad_data')) {
        echo "Adding ad_data column...\n";
        // Using TEXT instead of JSON for better compatibility if JSON fails
        $pdo->exec("ALTER TABLE ads ADD COLUMN ad_data TEXT DEFAULT NULL AFTER description");
        echo "ad_data column added.\n";
    } else {
        echo "ad_data column already exists.\n";
    }

    if (!columnExists($pdo, 'ads', 'expires_at')) {
        echo "Adding expires_at column...\n";
        $pdo->exec("ALTER TABLE ads ADD COLUMN expires_at TIMESTAMP NULL DEFAULT NULL");
    }

    if (!columnExists($pdo, 'ads', 'bumped_at')) {
        echo "Adding bumped_at column...\n";
        $pdo->exec("ALTER TABLE ads ADD COLUMN bumped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }

    echo "Schema check complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
