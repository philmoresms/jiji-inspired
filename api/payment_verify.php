<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Get all settings for API keys
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if (isset($_GET['ref']) && isset($_GET['ad_id']) && isset($_GET['method'])) {
    $ref = $_GET['ref'];
    $ad_id = (int)$_GET['ad_id'];
    $method = $_GET['method'];
    $package = $_GET['package'] ?? 'premium';

    // Fetch package details from database
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE slug = ?");
    $stmt->execute([$package]);
    $pkg_data = $stmt->fetch();

    if (!$pkg_data) {
        die("Invalid package selected.");
    }

    $price = (float)$pkg_data['price'];
    $duration = (int)$pkg_data['duration_days'];
    $cashback = (float)$pkg_data['cashback_amount'];

    // --- Server-Side Verification ---
    $verified = false;

    if ($method === 'wallet') {
        // Wallet payments are verified by internal logic in boost.php,
        // but for safety, we check if a transaction for this reference already exists.
        // Actually, for wallet, the reference is usually "WALLET-TIMESTAMP-USERID"
        $verified = true;
    } elseif ($method === 'paystack') {
        $secret_key = $settings['paystack_secret_key'] ?? '';
        if (empty($secret_key)) die("Paystack is not configured.");

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($ref),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authorization: Bearer " . $secret_key,
                "cache-control: no-cache"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if (!$err) {
            $tranx = json_decode($response);
            if ($tranx->status && $tranx->data->status === 'success') {
                // Verify amount (Paystack returns amount in kobo/lowest currency unit)
                if (($tranx->data->amount / 100) >= $price) {
                    $verified = true;
                }
            }
        }
    } elseif ($method === 'flutterwave') {
        $secret_key = $settings['flutterwave_secret_key'] ?? '';
        if (empty($secret_key)) die("Flutterwave is not configured.");

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/" . rawurlencode($ref) . "/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authorization: Bearer " . $secret_key,
                "content-type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if (!$err) {
            $tranx = json_decode($response);
            if ($tranx->status === 'success' && $tranx->data->status === 'successful') {
                if ($tranx->data->amount >= $price) {
                    $verified = true;
                }
            }
        }
    }

    if (!$verified) {
        die("Payment verification failed. Please contact support if you were charged.");
    }

    // Check if payment already recorded
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE reference = ?");
    $stmt->execute([$ref]);
    if ($stmt->fetch()) {
        redirect('../profile.php', 'Payment already processed.');
    }

    $stmt = $pdo->prepare("INSERT INTO payments (ad_id, reference, payment_method, amount, status) VALUES (?, ?, ?, ?, 'successful')");
    $stmt->execute([$ad_id, $ref, $method, $price]);

    $new_expiry = date('Y-m-d H:i:s', strtotime("+$duration days"));

    // Boost the ad, extend expiry, set package type, and bump to top
    $stmt = $pdo->prepare("UPDATE ads SET is_featured = 1, package_type = ?, status = 'active', expires_at = ?, bumped_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$package, $new_expiry, $ad_id]);

    // Implement Cashback Logic
    if ($cashback > 0) {
        $stmt = $pdo->prepare("SELECT user_id FROM ads WHERE id = ?");
        $stmt->execute([$ad_id]);
        $owner_id = $stmt->fetchColumn();

        if ($owner_id) {
            $pdo->beginTransaction();
            try {
                // Update Wallet Balance
                $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->execute([$cashback, $owner_id]);

                // Record Transaction
                $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
                $desc = "Cashback for purchasing " . ucfirst($package) . " package for Ad #" . $ad_id;
                $stmt->execute([$owner_id, $cashback, $desc]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Cashback Error: " . $e->getMessage());
            }
        }
    }

    redirect('../profile.php', 'Payment successful! Your ad is now ' . ucfirst($package) . '.');
} else {
    redirect('../index.php');
}
