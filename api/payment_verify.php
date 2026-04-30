<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';

if (isset($_GET['ref']) && isset($_GET['ad_id']) && isset($_GET['method'])) {
    $ref = $_GET['ref'];
    $ad_id = (int)$_GET['ad_id'];
    $method = $_GET['method'];
    $tier = $_GET['tier'] ?? 'standard';

    // Fetch settings for pricing and duration
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('boost_price', 'premium_ad_duration', 'lite_boost_price', 'lite_boost_duration')");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    if ($tier == 'lite') {
        $amount = (float)($settings['lite_boost_price'] ?? 1000);
        $duration = (int)($settings['lite_boost_duration'] ?? 7);
    } else {
        $amount = (float)($settings['boost_price'] ?? 2000);
        $duration = (int)($settings['premium_ad_duration'] ?? 30);
    }

    // For this clone, we simulate successful verification
    $stmt = $pdo->prepare("INSERT INTO payments (ad_id, reference, method, amount, status) VALUES (?, ?, ?, ?, 'successful')");
    $stmt->execute([$ad_id, $ref, $method, $amount]);

    $new_expiry = date('Y-m-d H:i:s', strtotime("+$duration days"));

    // Boost the ad, extend expiry, and bump to top
    $stmt = $pdo->prepare("UPDATE ads SET is_featured = 1, status = 'active', expires_at = ?, bumped_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$new_expiry, $ad_id]);

    redirect('../profile.php', 'Payment successful! Your ad is now featured for ' . $duration . ' days.');
} else {
    redirect('../index.php');
}
