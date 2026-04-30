<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

if (isset($_GET['ad_id'])) {
    $ad_id = (int)$_GET['ad_id'];
    $user_id = $_SESSION['user_id'];

    // Check if ad belongs to user
    $stmt = $pdo->prepare("SELECT title FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$ad_id, $user_id]);
    $ad = $stmt->fetch();

    if (!$ad) {
        redirect('profile.php', 'Ad not found or not yours.');
    }
} else {
    redirect('profile.php');
}

// Get settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('paystack_public_key', 'flutterwave_public_key', 'boost_price', 'premium_ad_duration', 'lite_boost_price', 'lite_boost_duration', 'bank_name', 'account_number', 'account_name')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$tiers = [
    'standard' => [
        'name' => 'Standard Premium',
        'price' => (float)($settings['boost_price'] ?? 2000),
        'duration' => (int)($settings['premium_ad_duration'] ?? 30)
    ],
    'lite' => [
        'name' => 'Lite Boost',
        'price' => (float)($settings['lite_boost_price'] ?? 1000),
        'duration' => (int)($settings['lite_boost_duration'] ?? 7)
    ]
];

$selected_tier = $_POST['tier'] ?? 'standard';
if (!isset($tiers[$selected_tier])) $selected_tier = 'standard';
$current_price = $tiers[$selected_tier]['price'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bank_transfer'])) {
    $filename = process_image_upload($_FILES['proof']['tmp_name'], __DIR__ . '/uploads/proofs', 800);
    if ($filename) {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, ad_id, amount, method, reference, status, proof_image) VALUES (?, ?, ?, 'bank_transfer', ?, 'pending', ?)");
        $reference = 'BT-'.time().'-'.rand(100, 999);
        $stmt->execute([$user_id, $ad_id, $current_price, $reference, $filename]);
        redirect('profile.php', 'Payment proof submitted for ' . $tiers[$selected_tier]['name'] . '! Your ad will be boosted after manual verification.');
    }
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-xl">
        <h1 class="text-2xl font-bold mb-8 text-primary-600 border-b pb-4"><i class="fas fa-rocket mr-2"></i> Boost Your Ad</h1>
        <p class="mb-8 font-bold text-gray-700">Get 10x more views for <span class="text-primary-600">"<?php echo h($ad['title']); ?>"</span> by upgrading to a Premium Boost.</p>

        <form method="POST" id="boostForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                <?php foreach ($tiers as $key => $tier): ?>
                <label class="relative border-2 rounded-2xl p-6 cursor-pointer transition-all <?php echo $selected_tier == $key ? 'border-primary-600 bg-primary-50' : 'border-gray-100 hover:border-primary-200'; ?>">
                    <input type="radio" name="tier" value="<?php echo $key; ?>" class="hidden" <?php echo $selected_tier == $key ? 'checked' : ''; ?> onchange="this.form.submit()">
                    <div class="flex flex-col items-center text-center">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1"><?php echo $tier['name']; ?></span>
                        <span class="text-2xl font-black text-gray-800">₦<?php echo number_format($tier['price']); ?></span>
                        <span class="text-xs font-bold text-primary-600 mt-2"><?php echo $tier['duration']; ?> Days Visibility</span>
                    </div>
                    <?php if ($selected_tier == $key): ?>
                        <div class="absolute -top-3 -right-3 w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-check text-xs"></i>
                        </div>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
        </form>

        <div class="space-y-6">
            <!-- Online Payment -->
            <div class="bg-gray-50 p-6 rounded-xl border-2 border-primary-100">
                <h2 class="font-bold text-lg mb-4 text-gray-800"><i class="fas fa-credit-card mr-2 text-primary-600"></i> Pay Online</h2>
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="payWithPaystack()" class="bg-yellow-500 text-white py-3 rounded-lg font-bold hover:bg-yellow-600 transition shadow-md uppercase text-sm">Paystack</button>
                    <button onclick="payWithFlutterwave()" class="bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-md uppercase text-sm">Flutterwave</button>
                </div>
                <p class="text-center text-xs text-gray-400 mt-4 font-bold uppercase tracking-widest">Instant Activation</p>
            </div>

            <!-- Manual Bank Transfer -->
            <div class="bg-gray-50 p-6 rounded-xl border-2 border-blue-100">
                <h2 class="font-bold text-lg mb-4 text-gray-800"><i class="fas fa-university mr-2 text-blue-600"></i> Bank Transfer</h2>
                <div class="bg-white p-4 rounded-lg mb-4 text-sm font-mono text-blue-900 border border-blue-100">
                    <p>Bank: <?php echo h($settings['bank_name'] ?? 'N/A'); ?></p>
                    <p>Account: <?php echo h($settings['account_number'] ?? 'N/A'); ?></p>
                    <p>Name: <?php echo h($settings['account_name'] ?? 'N/A'); ?></p>
                    <p class="mt-2 font-bold italic">Selected: <?php echo $tiers[$selected_tier]['name']; ?></p>
                    <p class="font-bold text-lg text-primary-600">Amount: ₦<?php echo number_format($current_price, 2); ?></p>
                </div>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="bank_transfer" value="1">
                    <input type="hidden" name="tier" value="<?php echo $selected_tier; ?>">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Upload Proof (Screenshot)</label>
                        <input type="file" name="proof" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-md uppercase text-sm">Submit Proof</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Payment Gateway Scripts -->
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
function payWithPaystack() {
    const handler = PaystackPop.setup({
        key: '<?php echo $settings['paystack_public_key'] ?? ''; ?>',
        email: 'user@example.com',
        amount: <?php echo ($current_price * 100); ?>, // In kobo
        currency: 'NGN',
        callback: function(response) {
            window.location.href = 'api/payment_verify.php?method=paystack&ref=' + response.reference + '&ad_id=<?php echo $ad_id; ?>&tier=<?php echo $selected_tier; ?>';
        }
    });
    handler.openIframe();
}

function payWithFlutterwave() {
    FlutterwaveCheckout({
        public_key: '<?php echo $settings['flutterwave_public_key'] ?? ''; ?>',
        tx_ref: 'FLW-' + Date.now(),
        amount: <?php echo $current_price; ?>,
        currency: 'NGN',
        payment_options: 'card, banktransfer, ussd',
        callback: function (data) {
            window.location.href = 'api/payment_verify.php?method=flutterwave&ref=' + data.transaction_id + '&ad_id=<?php echo $ad_id; ?>&tier=<?php echo $selected_tier; ?>';
        }
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
