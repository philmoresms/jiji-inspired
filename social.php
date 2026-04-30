<?php
/**
 * Jiji-Inspired-1.0 Social Login Handler (OAuth)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';

$provider = $_GET['provider'] ?? '';
if (!in_array($provider, ['google', 'facebook'])) {
    die("Invalid provider selected.");
}

// Get settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE ?");
$stmt->execute([$provider . '_%']);
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$status = $settings[$provider . '_auth_status'] ?? 'disabled';
if ($status !== 'active') {
    die("Social login for " . ucfirst($provider) . " is currently disabled.");
}

$client_id = $settings[$provider . '_client_id'] ?? ($settings[$provider . '_app_id'] ?? '');
$client_secret = $settings[$provider . '_client_secret'] ?? ($settings[$provider . '_app_secret'] ?? '');

if (empty($client_id) || empty($client_secret)) {
    die("Social login for " . ucfirst($provider) . " is not configured correctly in the admin panel.");
}

// PRODUCTION OAUTH LOGIC (Skeleton)
// In a real environment, you would use a library here.
// For this task, we remove the simulator and provide the structure for live calls.

if ($provider === 'google') {
    $auth_url = "https://accounts.google.com/o/oauth2/auth?" . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/social.php?provider=google",
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online'
    ]);
} else {
    $auth_url = "https://www.facebook.com/v12.0/dialog/oauth?" . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/social.php?provider=facebook",
        'scope' => 'email,public_profile'
    ]);
}

if (!isset($_GET['code'])) {
    header("Location: " . $auth_url);
    exit;
} else {
    // Handle Callback and Exchange code for token
    // This is where production API calls to Google/Facebook would happen.
    // Example: curl to https://oauth2.googleapis.com/token

    die("OAuth callback received. Production API exchange would happen here with code: " . h($_GET['code']));
}
