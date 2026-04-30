<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/update_schema.php';
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

// Fetch user wallet balance
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$wallet_balance = (float)$stmt->fetchColumn();

// Fetch packages from DB for logic
$db_packages = $pdo->query("SELECT * FROM packages WHERE price > 0 ORDER BY price ASC")->fetchAll();
$packages = [];
foreach ($db_packages as $dp) {
    $packages[$dp['slug']] = [
        'name' => $dp['name'],
        'price' => (float)$dp['price'],
        'duration' => $dp['duration_days'],
        'cashback' => (float)$dp['cashback_amount'],
        'features' => array_filter(explode("\n", $dp['features']))
    ];
}

// Handle POST actions BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pay_wallet'])) {
        $pkg_key = $_POST['package'];
        if (!isset($packages[$pkg_key])) die("Invalid package.");
        $pkg = $packages[$pkg_key];

        if ($wallet_balance >= $pkg['price']) {
            $pdo->beginTransaction();
            try {
                // Debit Wallet
                $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
                $stmt->execute([$pkg['price'], $user_id]);

                // Record Debit Transaction
                $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
                $desc = "Payment for " . ucfirst($pkg_key) . " package for Ad #" . $ad_id;
                $stmt->execute([$user_id, $pkg['price'], $desc]);

                // Extend Ad Expiry
                $duration = $pkg['duration'];
                $new_expiry = date('Y-m-d H:i:s', strtotime("+$duration days"));
                $stmt = $pdo->prepare("UPDATE ads SET is_featured = 1, package_type = ?, status = 'active', expires_at = ?, bumped_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$pkg_key, $new_expiry, $ad_id]);

                // Implement Cashback Logic (if applicable)
                if ($pkg['cashback'] > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                    $stmt->execute([$pkg['cashback'], $user_id]);

                    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
                    $desc = "Cashback for purchasing " . ucfirst($pkg_key) . " package for Ad #" . $ad_id;
                    $stmt->execute([$user_id, $pkg['cashback'], $desc]);
                }

                $pdo->commit();
                redirect('profile.php', 'Package activated successfully via Wallet!', 'success');
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Wallet Payment Error: " . $e->getMessage();
            }
        } else {
            $error = "Insufficient wallet balance.";
        }
    }

    if (isset($_POST['bank_transfer'])) {
        $pkg_key = $_POST['package'];
        if (!isset($packages[$pkg_key])) die("Invalid package.");
        $pkg = $packages[$pkg_key];
        $filename = process_image_upload($_FILES['proof']['tmp_name'], __DIR__ . '/uploads/proofs', 800);
        if ($filename) {
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, ad_id, amount, payment_method, reference, status, proof_image) VALUES (?, ?, ?, 'bank_transfer', ?, 'pending', ?)");
            $reference = 'BT-' . strtoupper($pkg_key) . '-' . time();
            $stmt->execute([$user_id, $ad_id, $pkg['price'], $reference, $filename]);

            redirect('profile.php', 'Proof submitted! Ad will be boosted after verification.', 'success');
        }
    }
}

// Get settings for UI
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('paystack_public_key', 'flutterwave_public_key', 'bank_name', 'account_number', 'account_name')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$colors = ['premium' => 'green', 'vip' => 'yellow', 'diamond' => 'blue'];
$icons = ['premium' => '✨', 'vip' => '👑', 'diamond' => '💎'];
foreach ($packages as $slug => &$pkg) {
    $pkg['color'] = $colors[$slug] ?? 'primary';
    $pkg['icon'] = $icons[$slug] ?? '🚀';
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-black text-gray-900 mb-2 uppercase tracking-tighter">Promote Your Ad</h1>
            <p class="text-gray-500 font-bold">Choose a package for <span class="text-primary-600">"<?php echo h($ad['title']); ?>"</span></p>
            <?php if (isset($error)): ?>
                <p class="mt-4 text-red-600 font-bold"><?php echo h($error); ?></p>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <?php foreach ($packages as $key => $pkg): ?>
            <div class="bg-white rounded-2xl shadow-xl border-2 border-<?php echo $pkg['color']; ?>-100 overflow-hidden flex flex-col hover:scale-105 transition transform duration-300">
                <div class="bg-<?php echo $pkg['color']; ?>-50 p-6 border-b border-<?php echo $pkg['color']; ?>-100">
                    <h2 class="text-xl font-black text-<?php echo $pkg['color']; ?>-700 flex items-center justify-between">
                        <?php echo $pkg['name']; ?> <?php echo $pkg['icon']; ?>
                    </h2>
                    <p class="text-2xl font-black mt-2 text-gray-900">₦<?php echo number_format($pkg['price']); ?></p>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1"><?php echo $pkg['duration']; ?> Days</p>
                </div>
                <div class="p-6 flex-1">
                    <ul class="space-y-4">
                        <?php foreach ($pkg['features'] as $feature): ?>
                        <li class="flex items-start text-sm text-gray-600 font-medium">
                            <i class="fas fa-check-circle text-<?php echo $pkg['color']; ?>-500 mr-2 mt-1"></i>
                            <?php echo trim($feature); ?>
                        </li>
                        <?php endforeach; ?>

                        <?php if ($pkg['cashback'] > 0): ?>
                        <li class="flex items-start text-sm text-green-600 font-black italic">
                            <i class="fas fa-coins mr-2 mt-1"></i>
                            ₦<?php echo number_format($pkg['cashback']); ?> Cashback!
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="p-6 pt-0">
                    <button onclick="selectPackage('<?php echo $key; ?>')" class="w-full bg-<?php echo $pkg['color']; ?>-500 text-white py-3 rounded-xl font-bold hover:bg-<?php echo $pkg['color']; ?>-600 transition shadow-lg uppercase text-sm">
                        Select <?php echo $pkg['name']; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Payment Modal (Hidden by default) -->
        <div id="paymentModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden animate-bounce-in">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 id="modalTitle" class="text-xl font-black text-gray-800">Payment for Premium</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="p-8">
                    <div class="mb-8 text-center">
                        <p class="text-gray-500 font-bold mb-1">Amount to pay</p>
                        <p id="modalAmount" class="text-4xl font-black text-primary-600">₦0.00</p>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 mb-2">
                            <div class="flex justify-between items-center text-xs font-bold text-gray-500 uppercase tracking-widest">
                                <span>Your Wallet Balance</span>
                                <span class="text-gray-900">₦<?php echo number_format($wallet_balance, 2); ?></span>
                            </div>
                        </div>

                        <button onclick="payWithWallet()" id="walletBtn" class="w-full bg-green-600 text-white py-4 rounded-2xl font-black hover:bg-green-700 transition shadow-lg flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-wallet mr-3"></i> PAY WITH WALLET
                        </button>

                        <button onclick="payWithPaystack()" class="w-full bg-yellow-500 text-white py-4 rounded-2xl font-black hover:bg-yellow-600 transition shadow-lg flex items-center justify-center">
                            <i class="fas fa-credit-card mr-3"></i> PAYSTACK
                        </button>
                        <button onclick="payWithFlutterwave()" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black hover:bg-blue-700 transition shadow-lg flex items-center justify-center">
                            <i class="fas fa-money-bill-wave mr-3"></i> FLUTTERWAVE
                        </button>
                    </div>

                    <div class="mt-8 pt-8 border-t">
                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 text-center">Bank Transfer Option</p>
                        <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 text-sm font-mono text-blue-900 mb-4">
                            <p>Bank: <?php echo h($settings['bank_name'] ?? 'N/A'); ?></p>
                            <p>Account: <?php echo h($settings['account_number'] ?? 'N/A'); ?></p>
                            <p>Name: <?php echo h($settings['account_name'] ?? 'N/A'); ?></p>
                        </div>
                        <form id="bankForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="bank_transfer" value="1">
                            <input type="hidden" name="package" id="bankPackage">
                            <div class="relative">
                                <input type="file" name="proof" id="proofInput" accept="image/*" class="hidden" required onchange="updateFileName(this)">
                                <label for="proofInput" class="w-full flex items-center justify-center py-3 border-2 border-dashed border-blue-200 rounded-xl cursor-pointer hover:bg-blue-50 transition text-blue-600 font-bold text-xs">
                                    <i class="fas fa-upload mr-2"></i> <span id="fileName">Upload Proof (Screenshot)</span>
                                </label>
                            </div>
                            <button type="submit" class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold hover:bg-black transition uppercase text-xs">Submit Proof</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.flutterwave.com/v3.js"></script>
<script>
let selectedPkg = null;
const packages = <?php echo json_encode($packages); ?>;

function selectPackage(key) {
    selectedPkg = key;
    const pkg = packages[key];
    document.getElementById('modalTitle').textContent = 'Payment for ' + pkg.name;
    document.getElementById('modalAmount').textContent = '₦' + pkg.price.toLocaleString();
    document.getElementById('bankPackage').value = key;

    // Check wallet balance
    const walletBalance = <?php echo $wallet_balance; ?>;
    const walletBtn = document.getElementById('walletBtn');
    if (walletBalance < pkg.price) {
        walletBtn.disabled = true;
        walletBtn.title = "Insufficient wallet balance";
    } else {
        walletBtn.disabled = false;
        walletBtn.title = "";
    }

    document.getElementById('paymentModal').classList.remove('hidden');
}

function payWithWallet() {
    if (confirm("Are you sure you want to pay using your wallet balance?")) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="pay_wallet" value="1"><input type="hidden" name="package" value="${selectedPkg}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function closeModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function updateFileName(input) {
    const name = input.files[0]?.name || 'Upload Proof (Screenshot)';
    document.getElementById('fileName').textContent = name;
}

function payWithPaystack() {
    const pkg = packages[selectedPkg];
    const handler = PaystackPop.setup({
        key: '<?php echo $settings['paystack_public_key'] ?? ''; ?>',
        email: '<?php
            $stmt_u = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt_u->execute([$_SESSION['user_id']]);
            echo h($stmt_u->fetchColumn());
        ?>',
        amount: pkg.price * 100,
        currency: 'NGN',
        callback: function(response) {
            window.location.href = 'api/payment_verify.php?method=paystack&ref=' + response.reference + '&ad_id=<?php echo $ad_id; ?>&package=' + selectedPkg;
        }
    });
    handler.openIframe();
}

function payWithFlutterwave() {
    const pkg = packages[selectedPkg];
    FlutterwaveCheckout({
        public_key: '<?php echo $settings['flutterwave_public_key'] ?? ''; ?>',
        tx_ref: 'FLW-' + Date.now(),
        amount: pkg.price,
        currency: 'NGN',
        payment_options: 'card, banktransfer, ussd',
        callback: function (data) {
            window.location.href = 'api/payment_verify.php?method=flutterwave&ref=' + data.transaction_id + '&ad_id=<?php echo $ad_id; ?>&package=' + selectedPkg;
        }
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
