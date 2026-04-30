<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_id = (int)$_POST['ad_id'];
    $type = $_POST['type'] ?? 'click'; // click, call, chat
    $viewer_id = $_SESSION['user_id'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO seller_analytics (ad_id, viewer_id, interaction_type, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$ad_id, $viewer_id, $type, get_client_ip()]);

    echo json_encode(['success' => true]);
}
