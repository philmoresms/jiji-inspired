<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        $title = $_POST['title'];
        $cat_id = (int)$_POST['cat_id'] ?: null;
        $state_id = (int)$_POST['state_id'] ?: null;
        $lga_id = (int)$_POST['lga_id'] ?: null;
        $price = (float)$_POST['price'];
        $listing_type = $_POST['listing_type'] ?? 'for_sale';
        $estimated_value = !empty($_POST['estimated_value']) ? (float)$_POST['estimated_value'] : null;
        $swap_preference = $_POST['swap_preference'] ?? null;
        $allow_cash_topup = isset($_POST['allow_cash_topup']) ? 1 : 0;
        $description = $_POST['description'];
        $video_url = !empty($_POST['video_url']) ? $_POST['video_url'] : null;

        // Get free ad duration
        $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'free_ad_duration'");
        $duration = (int)($stmt->fetchColumn() ?: 15);
        $expires_at = date('Y-m-d H:i:s', strtotime("+$duration days"));

        $stmt = $pdo->prepare("INSERT INTO ads (user_id, cat_id, state_id, lga_id, title, price, listing_type, estimated_value, swap_preference, allow_cash_topup, description, status, video_url, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$user_id, $cat_id, $state_id, $lga_id, $title, $price, $listing_type, $estimated_value, $swap_preference, $allow_cash_topup, $description, $video_url, $expires_at]);
        $ad_id = $pdo->lastInsertId();

    // Process Images
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $filename = process_image_upload($tmp_name, __DIR__ . '/uploads/ads', 800);
            if ($filename) {
                $is_main = ($key === 0) ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO ad_images (ad_id, image_path, is_main) VALUES (?, ?, ?)");
                $stmt->execute([$ad_id, $filename, $is_main]);
            }
        }
    }

        redirect('profile.php', 'Ad posted successfully! It will be live after moderation.');
    } catch (PDOException $e) {
        error_log("Post Ad Error: " . $e->getMessage());
        $error = "An error occurred while posting your ad. Please ensure all fields are correct.";
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-2xl">
        <h1 class="text-2xl font-bold mb-8 text-green-600 border-b pb-4"><i class="fas fa-plus-circle mr-2"></i> Post Your Ad</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 font-bold text-sm"><?php echo h($error); ?></div>
        <?php endif; ?>

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

            <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 mb-6">
                <label class="block text-gray-700 font-black mb-4 text-xs uppercase tracking-widest">Listing Options</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-green-200 has-[:checked]:border-green-600 has-[:checked]:bg-green-50 transition">
                        <input type="radio" name="listing_type" value="for_sale" checked class="absolute opacity-0" onchange="toggleSwapFields()">
                        <span class="text-xs font-bold text-gray-800">For Sale</span>
                        <span class="text-[9px] text-gray-400 mt-1">Direct monetary trade</span>
                    </label>
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-green-200 has-[:checked]:border-green-600 has-[:checked]:bg-green-50 transition">
                        <input type="radio" name="listing_type" value="for_swap" class="absolute opacity-0" onchange="toggleSwapFields()">
                        <span class="text-xs font-bold text-gray-800">For Swap</span>
                        <span class="text-[9px] text-gray-400 mt-1">Item-for-item exchange</span>
                    </label>
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-green-200 has-[:checked]:border-green-600 has-[:checked]:bg-green-50 transition">
                        <input type="radio" name="listing_type" value="for_sale_or_swap" class="absolute opacity-0" onchange="toggleSwapFields()">
                        <span class="text-xs font-bold text-gray-800">Sale or Swap</span>
                        <span class="text-[9px] text-gray-400 mt-1">Accept cash or items</span>
                    </label>
                </div>

                <div id="price_field">
                    <label class="block text-gray-700 font-bold mb-2 text-sm">Price (₦)</label>
                    <input type="number" name="price" step="0.01" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="10000">
                </div>

                <div id="swap_fields" class="hidden space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-sm">Estimated Market Value (₦)</label>
                        <input type="number" name="estimated_value" step="0.01" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="e.g. 50000">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-sm">Swap Preference (What do you want in exchange?)</label>
                        <input type="text" name="swap_preference" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="e.g. iPhone 13 or equivalent laptop">
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="allow_cash_topup" value="1" class="w-5 h-5 accent-green-600">
                        <span class="text-sm font-bold text-gray-700">Allow item + cash top-up</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2 text-sm">Description</label>
                <textarea name="description" rows="5" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="Provide details about the item..." required></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2 text-sm">Video Link (YouTube/TikTok)</label>
                <input type="url" name="video_url" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="https://youtube.com/watch?v=...">
            </div>

            <div id="dropZone" class="mb-4 p-8 border-4 border-dashed border-gray-200 rounded-2xl bg-gray-50 hover:bg-white transition cursor-pointer relative group">
                <label class="block text-gray-700 font-bold mb-4 text-sm text-center">Upload Ad Photos (Maximum 5)</label>
                <input type="file" name="images[]" id="fileInput" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-100 transition">
                        <i class="fas fa-cloud-upload-alt text-3xl text-green-500"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-600 mb-1">Drag & drop images here</p>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">or click to browse from device</p>
                </div>
            </div>

            <div id="imagePreviewContainer" class="grid grid-cols-5 gap-4 mb-6 hidden">
                <!-- Previews will appear here -->
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

function toggleSwapFields() {
    const type = document.querySelector('input[name="listing_type"]:checked').value;
    const priceField = document.getElementById('price_field');
    const swapFields = document.getElementById('swap_fields');

    if (type === 'for_sale') {
        priceField.classList.remove('hidden');
        swapFields.classList.add('hidden');
    } else if (type === 'for_swap') {
        priceField.classList.add('hidden');
        swapFields.classList.remove('hidden');
    } else {
        priceField.classList.remove('hidden');
        swapFields.classList.remove('hidden');
    }
}

// Image Preview & Drag and Drop Logic
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const previewContainer = document.getElementById('imagePreviewContainer');
let allFiles = new DataTransfer(); // To keep track of multiple selections

['dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
    });
});

dropZone.addEventListener('dragover', () => {
    dropZone.classList.replace('border-gray-200', 'border-green-400');
    dropZone.classList.add('bg-green-50');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.replace('border-green-400', 'border-gray-200');
    dropZone.classList.remove('bg-green-50');
});

dropZone.addEventListener('drop', (e) => {
    dropZone.classList.replace('border-green-400', 'border-gray-200');
    dropZone.classList.remove('bg-green-50');

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        addFiles(files);
    }
});

fileInput.addEventListener('change', () => {
    addFiles(fileInput.files);
});

function addFiles(files) {
    for (let i = 0; i < files.length; i++) {
        if (allFiles.items.length < 5) {
            allFiles.items.add(files[i]);
        }
    }
    fileInput.files = allFiles.files; // Update the real input
    renderPreviews();
}

function removeFile(index) {
    const newDT = new DataTransfer();
    for (let i = 0; i < allFiles.files.length; i++) {
        if (i !== index) {
            newDT.items.add(allFiles.files[i]);
        }
    }
    allFiles = newDT;
    fileInput.files = allFiles.files;
    renderPreviews();
}

function renderPreviews() {
    previewContainer.innerHTML = '';

    if (allFiles.files.length === 0) {
        previewContainer.classList.add('hidden');
        return;
    }

    previewContainer.classList.remove('hidden');

    Array.from(allFiles.files).forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const previewDiv = document.createElement('div');
            previewDiv.className = 'relative group aspect-square rounded-xl overflow-hidden border-2 border-gray-100 shadow-sm transition transform hover:scale-95';
            previewDiv.innerHTML = `
                <img src="${e.target.result}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                    <button type="button" onclick="removeFile(${i})" class="bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-red-600 shadow-lg">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
                <div class="absolute bottom-1 right-1 bg-green-600 text-white text-[8px] px-1 rounded font-bold">PHOTO ${i + 1}</div>
            `;
            previewContainer.appendChild(previewDiv);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
