<?php
/**
 * Jiji-Inspired-1.0 Social Login Handler (OAuth 2.0)
 * Production-ready implementation using cURL
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';

$provider = $_GET['provider'] ?? '';
if (!in_array($provider, ['google', 'facebook'])) {
    die("Invalid provider selected.");
}

// Get settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE ? OR setting_key = ?");
$stmt->execute([$provider . '_%', $provider . '_auth_active']);
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if (($settings[$provider . '_auth_active'] ?? '0') !== '1') {
    die(ucfirst($provider) . " login is currently disabled.");
}

$client_id = $settings[$provider . '_client_id'] ?? ($settings[$provider . '_app_id'] ?? '');
$client_secret = $settings[$provider . '_client_secret'] ?? ($settings[$provider . '_app_secret'] ?? '');
$redirect_uri = SITE_URL . "/social.php?provider=$provider";

if (empty($client_id) || empty($client_secret)) {
    die("Social login for " . ucfirst($provider) . " is not fully configured.");
}

if (!isset($_GET['code'])) {
    // Generate and store state for CSRF protection
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    // Stage 1: Redirect to Provider
    if ($provider === 'google') {
        $auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online',
            'state' => $state
        ]);
    } else {
        $auth_url = "https://www.facebook.com/v12.0/dialog/oauth?" . http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => 'email,public_profile',
            'state' => $state
        ]);
    }
    header("Location: $auth_url");
    exit;
} else {
    // Stage 2: Handle Callback

    // Verify state to prevent CSRF
    if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        die("Invalid OAuth state. Potential CSRF attack detected.");
    }
    unset($_SESSION['oauth_state']);

    $code = $_GET['code'];

    if ($provider === 'google') {
        $token_url = "https://oauth2.googleapis.com/token";
        $params = [
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        ];
    } else {
        $token_url = "https://graph.facebook.com/v12.0/oauth/access_token";
        $params = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $code
        ];
    }

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (!isset($data['access_token'])) {
        die("Failed to obtain access token: " . ($data['error_description'] ?? $data['error'] ?? 'Unknown error'));
    }

    $access_token = $data['access_token'];

    // Get User Info
    if ($provider === 'google') {
        $info_url = "https://www.googleapis.com/oauth2/v3/userinfo?access_token=" . $access_token;
    } else {
        $info_url = "https://graph.facebook.com/me?fields=id,name,email&access_token=" . $access_token;
    }

    $ch = curl_init($info_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $user_data = json_decode($response, true);
    curl_close($ch);

    $email = $user_data['email'] ?? '';
    $full_name = $user_data['name'] ?? ($user_data['given_name'] . ' ' . $user_data['family_name'] ?? 'Social User');

    if (empty($email)) {
        die("Could not retrieve email from social provider.");
    }

    // Check if user exists by email
    $stmt = $pdo->prepare("SELECT id, full_name, is_suspended FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_suspended']) {
            header('Location: login.php?error=' . urlencode("Account suspended."));
            exit;
        }
        $user_id = $user['id'];
        $full_name = $user['full_name'];
    } else {
        // Create new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, is_verified) VALUES (?, ?, ?, 1)");
        $stmt->execute([$full_name, $email, password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT)]);
        $user_id = $pdo->lastInsertId();
    }

    // Log them in
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $full_name;

    header('Location: index.php');
    exit;
}
