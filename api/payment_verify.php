<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../inc/functions.php';

if (isset($_GET['ref']) && isset($_GET['ad_id']) && isset($_GET['method'])) {
    $ref = $_GET['ref'];
    $ad_id = (int)$_GET['ad_id'];
    $method = $_GET['method'];

    // In a real application, we would call Paystack/Flutterwave API to verify the reference
    // Fetch dynamic boost price
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'boost_price'");
    $boost_price = (float)($stmt->fetchColumn() ?: 2000);

    // For this clone, we simulate successful verification

    $stmt = $pdo->prepare("INSERT INTO payments (ad_id, reference, method, amount, status) VALUES (?, ?, ?, ?, 'successful')");
    $stmt->execute([$ad_id, $ref, $method, $boost_price]);

    // Boost the ad
    $stmt = $pdo->prepare("UPDATE ads SET is_featured = 1 WHERE id = ?");
    $stmt->execute([$ad_id]);

    redirect('../profile.php', 'Payment successful! Your ad is now featured.');
} else {
    redirect('../index.php');
}
