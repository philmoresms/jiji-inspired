<?php
require_once 'config/config.php';
try {
    $stmt = $pdo->query("DESCRIBE ads");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in 'ads' table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . " - " . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
