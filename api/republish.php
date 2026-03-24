<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $ad_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Verify ownership and status
    $stmt = $pdo->prepare("SELECT id FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$ad_id, $user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE ads SET status = 'pending', created_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$ad_id]);
        redirect('../profile.php', 'Ad republished! It will be live after moderation.');
    }
}
redirect('../profile.php');
