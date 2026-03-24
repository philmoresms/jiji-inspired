<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = $_POST['title'];
    $cat_id = (int)$_POST['cat_id'];
    $state_id = (int)$_POST['state_id'];
    $lga_id = (int)$_POST['lga_id'];
    $price = (float)$_POST['price'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO ads (user_id, cat_id, state_id, lga_id, title, price, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $cat_id, $state_id, $lga_id, $title, $price, $description]);
    $ad_id = $pdo->lastInsertId();

    // Process Images
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $filename = process_image_upload($tmp_name, __DIR__ . '/../uploads/ads', 800);
            if ($filename) {
                $is_main = ($key === 0) ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO ad_images (ad_id, image_path, is_main) VALUES (?, ?, ?)");
                $stmt->execute([$ad_id, $filename, $is_main]);
            }
        }
    }

    redirect('profile.php', 'Ad posted successfully! It will be live after moderation.');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-2xl">
        <h1 class="text-2xl font-bold mb-8 text-green-600 border-b pb-4"><i class="fas fa-plus-circle mr-2"></i> Post Your Ad</h1>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">Category</label>
                    <select name="cat_id" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo h($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">Title</label>
                    <input type="text" name="title" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="What are you selling?" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">State</label>
                    <select name="state_id" id="state_id" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" required onchange="loadLGAs(this.value)">
                        <option value="">Select State</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>"><?php echo h($state['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">LGA (City)</label>
                    <select name="lga_id" id="lga_id" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" required>
                        <option value="">Select LGA</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2 text-sm">Price (₦)</label>
                <input type="number" name="price" step="0.01" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="10000" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2 text-sm">Description</label>
                <textarea name="description" rows="5" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="Provide details about the item..." required></textarea>
            </div>

            <div class="mb-4 p-6 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 hover:bg-white transition cursor-pointer relative group">
                <label class="block text-gray-700 font-bold mb-2 text-sm text-center">Add Images (Maximum 5)</label>
                <input type="file" name="images[]" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                <div class="text-center">
                    <i class="fas fa-camera text-3xl text-gray-300 group-hover:text-green-500 mb-2"></i>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Drag & drop or click to upload</p>
                </div>
            </div>

            <div class="pt-6">
                <button type="submit" class="w-full bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700 transition shadow-lg text-lg uppercase">Post Ad</button>
            </div>
        </form>
    </div>
</div>

<script>
function loadLGAs(stateId) {
    const lgaSelect = document.getElementById('lga_id');
    lgaSelect.innerHTML = '<option value="">Loading...</option>';

    if (!stateId) {
        lgaSelect.innerHTML = '<option value="">Select LGA</option>';
        return;
    }

    fetch('api/lgas.php?state_id=' + stateId)
        .then(response => response.json())
        .then(data => {
            lgaSelect.innerHTML = '<option value="">Select LGA</option>';
            data.forEach(lga => {
                const option = document.createElement('option');
                option.value = lga.id;
                option.textContent = lga.name;
                lgaSelect.appendChild(option);
            });
        });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
