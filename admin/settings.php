<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($_POST['s'] as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        redirect('settings.php', 'Global settings updated successfully.');
    }
}

// Get current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-sm">
    <h2 class="text-2xl font-bold mb-8 text-gray-800 border-b pb-4">Global System Settings</h2>

    <form method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <!-- General Settings -->
            <div>
                <h3 class="font-bold text-lg mb-4 text-green-700">General Information</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Site Name</label>
                        <input type="text" name="s[site_name]" value="<?php echo h($settings['site_name'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>
                </div>

                <h3 class="font-bold text-lg mt-8 mb-4 text-green-700">Email (SMTP) Configuration</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">SMTP Host</label>
                        <input type="text" name="s[smtp_host]" value="<?php echo h($settings['smtp_host'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">SMTP Port</label>
                            <input type="text" name="s[smtp_port]" value="<?php echo h($settings['smtp_port'] ?? ''); ?>" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Encryption</label>
                            <select name="s[smtp_encryption]" class="w-full p-2 border rounded">
                                <option value="tls" <?php echo ($settings['smtp_encryption'] ?? '') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">SMTP Username</label>
                        <input type="text" name="s[smtp_user]" value="<?php echo h($settings['smtp_user'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">SMTP Password</label>
                        <input type="password" name="s[smtp_pass]" value="<?php echo h($settings['smtp_pass'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>
                </div>
            </div>

            <!-- Payment Settings -->
            <div>
                <h3 class="font-bold text-lg mb-4 text-green-700">Payment Gateway (API Keys)</h3>
                <div class="space-y-4">
                    <div class="p-4 bg-yellow-50 rounded border border-yellow-200 mb-4">
                        <p class="text-sm text-yellow-800 font-bold">Paystack Configuration</p>
                        <div class="mt-2 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Public Key</label>
                                <input type="text" name="s[paystack_public_key]" value="<?php echo h($settings['paystack_public_key'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Secret Key</label>
                                <input type="password" name="s[paystack_secret_key]" value="<?php echo h($settings['paystack_secret_key'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-blue-50 rounded border border-blue-200 mb-4">
                        <p class="text-sm text-blue-800 font-bold">Flutterwave Configuration</p>
                        <div class="mt-2 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Public Key</label>
                                <input type="text" name="s[flutterwave_public_key]" value="<?php echo h($settings['flutterwave_public_key'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600">Secret Key</label>
                                <input type="password" name="s[flutterwave_secret_key]" value="<?php echo h($settings['flutterwave_secret_key'] ?? ''); ?>" class="w-full p-2 border rounded text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="font-bold text-lg mt-8 mb-4 text-green-700">Social Login (OAuth)</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Google Client ID</label>
                        <input type="text" name="s[google_client_id]" value="<?php echo h($settings['google_client_id'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Facebook App ID</label>
                        <input type="text" name="s[facebook_app_id]" value="<?php echo h($settings['facebook_app_id'] ?? ''); ?>" class="w-full p-2 border rounded">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-10 border-t pt-6 flex justify-end">
            <button type="submit" name="save_settings" class="bg-green-600 text-white px-10 py-3 rounded-lg font-bold hover:bg-green-700 transition shadow-lg">Save All Configurations</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
