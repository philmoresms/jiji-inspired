<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_package'])) {
        $stmt = $pdo->prepare("UPDATE packages SET name = ?, price = ?, duration_days = ?, cashback_amount = ?, features = ? WHERE id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['price'],
            $_POST['duration_days'],
            $_POST['cashback_amount'],
            $_POST['features'],
            $_POST['id']
        ]);
        redirect('packages.php', 'Package updated successfully.');
    }
}

$packages = $pdo->query("SELECT * FROM packages ORDER BY price ASC")->fetchAll();

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-sm">
    <div class="flex justify-between items-center mb-8 border-b pb-4">
        <h2 class="text-2xl font-bold text-gray-800">Manage Ad Packages</h2>
        <p class="text-sm text-gray-500 font-medium">Configure pricing, durations, and cashback for tiers.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach ($packages as $pkg): ?>
        <div class="border rounded-2xl p-6 bg-gray-50 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-2 bg-primary-600 text-white text-[10px] font-bold uppercase tracking-widest rounded-bl-xl">
                <?php echo h($pkg['slug']); ?>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Display Name</label>
                        <input type="text" name="name" value="<?php echo h($pkg['name']); ?>" class="w-full p-2 border rounded font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Price (₦)</label>
                        <input type="number" name="price" value="<?php echo (float)$pkg['price']; ?>" class="w-full p-2 border rounded font-bold text-primary-600">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Duration (Days)</label>
                        <input type="number" name="duration_days" value="<?php echo $pkg['duration_days']; ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Cashback (₦)</label>
                        <input type="number" name="cashback_amount" value="<?php echo (float)$pkg['cashback_amount']; ?>" class="w-full p-2 border rounded text-green-600 font-bold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Features (One per line)</label>
                    <textarea name="features" rows="4" class="w-full p-2 border rounded text-sm"><?php echo h($pkg['features']); ?></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" name="update_package" class="w-full bg-primary-600 text-white py-2 rounded-lg font-bold hover:bg-primary-700 transition">Update <?php echo h($pkg['name']); ?></button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
