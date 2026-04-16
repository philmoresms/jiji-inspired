<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/user_auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    return;
}

$reviewer_id = $_SESSION['user_id'];
$seller_id = (int)($_POST['seller_id'] ?? 0);
$stars = (int)($_POST['stars'] ?? 0);
$body = $_POST['body'] ?? '';
$tags = $_POST['tags'] ?? []; // Array of strings

if ($stars < 1 || $stars > 5 || !$seller_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating.']);
    return;
}

// One review per seller
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE reviewer_id = ? AND seller_id = ?");
$stmt->execute([$reviewer_id, $seller_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this seller.']);
    return;
}

$stmt = $pdo->prepare("INSERT INTO reviews (reviewer_id, seller_id, stars, body, tags) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$reviewer_id, $seller_id, $stars, $body, json_encode($tags)]);

echo json_encode(['success' => true, 'message' => 'Review submitted. Note: False reviews face a permanent ban if challenged and unsupported by evidence.']);
