<?php
/**
 * Jiji-Inspired-1.0 Installer
 * Stage 3: Admin Account Creation & Seeding
 */

session_start();
require_once '../inc/functions.php';
require_once 'seeder.php';

if (!isset($_SESSION['install_stage']) || $_SESSION['install_stage'] < 3) {
    header('Location: stage2.php');
    exit;
}

$error = null;

if (isset($_POST['complete'])) {
    $admin_user = $_POST['admin_user'];
    $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
    $admin_email = $_POST['admin_email'];
    $admin_name = $_POST['admin_name'];

    try {
        require_once '../config/config.php'; // Load PDO from newly created config

        // 1. Create Admin User
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$admin_user, $admin_pass, $admin_email, $admin_name]);

        // 2. Seed Data (States, LGAs, Categories)
        seed_database($pdo);

        // 3. Set Default Settings
        $default_settings = [
            'site_name' => 'Jiji Inspired',
            'brute_force_period' => '15',
            'max_failures_account' => '5',
            'max_failures_ip' => '10',
            'ip_block_duration' => 'one_day',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_encryption' => 'tls',
            'paystack_public_key' => '',
            'paystack_secret_key' => '',
            'flutterwave_public_key' => '',
            'flutterwave_secret_key' => ''
        ];

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($default_settings as $key => $val) {
            $stmt->execute([$key, $val]);
        }

        $_SESSION['install_stage'] = 4;
        header('Location: stage4.php');
        exit;

    } catch (PDOException $e) {
        $error = "Installation Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jiji-Inspired-1.0 Installer - Stage 3</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-green-600">Admin Account Setup</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Full Name</label>
                <input type="text" name="admin_name" value="Administrator" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Admin Username</label>
                <input type="text" name="admin_user" value="admin" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Admin Email</label>
                <input type="email" name="admin_email" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 font-bold mb-2">Admin Password</label>
                <input type="password" name="admin_pass" class="w-full p-2 border rounded" required>
            </div>

            <button type="submit" name="complete" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition">Complete Installation</button>
        </form>
    </div>
</body>
</html>
