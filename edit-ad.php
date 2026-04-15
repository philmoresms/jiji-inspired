<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

if (!isset($_GET['id'])) {
    redirect('profile.php');
}

$ad_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ? AND user_id = ?");
$stmt->execute([$ad_id, $user_id]);
$ad = $stmt->fetch();

if (!$ad) {
    redirect('profile.php', 'Ad not found or access denied.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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

        // Handle category-specific data
        $ad_data = null;
        if (isset($_POST['extra'])) {
            $ad_data = json_encode($_POST['extra']);
        }

        // Update ad and reset status to pending
        $stmt = $pdo->prepare("UPDATE ads SET cat_id = ?, state_id = ?, lga_id = ?, title = ?, price = ?, listing_type = ?, estimated_value = ?, swap_preference = ?, allow_cash_topup = ?, description = ?, ad_data = ?, video_url = ?, status = 'pending', decline_reason = NULL WHERE id = ?");
        $stmt->execute([$cat_id, $state_id, $lga_id, $title, $price, $listing_type, $estimated_value, $swap_preference, $allow_cash_topup, $description, $ad_data, $video_url, $ad_id]);

    // Handle new images if any
    if (!empty($_FILES['images']['name'][0])) {
        // Option: Delete old images or just add new ones.
        // For the marketplace, we add new ones up to limit or clear existing ones.
        // Let's clear existing ones for a clean re-submission if new ones are provided.
        $pdo->prepare("DELETE FROM ad_images WHERE ad_id = ?")->execute([$ad_id]);

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $filename = process_image_upload($tmp_name, __DIR__ . '/uploads/ads', 800);
            if ($filename) {
                $is_main = ($key === 0) ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO ad_images (ad_id, image_path, is_main) VALUES (?, ?, ?)");
                $stmt->execute([$ad_id, $filename, $is_main]);
            }
        }
    }

        redirect('profile.php', 'Ad updated and re-submitted for moderation.');
    } catch (PDOException $e) {
        error_log("Edit Ad Error: " . $e->getMessage());
        $error = "An error occurred while updating your ad.";
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$states = $pdo->query("SELECT * FROM states ORDER BY name ASC")->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10 flex justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-2xl border-t-8 border-yellow-500">
        <h1 class="text-2xl font-bold mb-4 text-gray-800"><i class="fas fa-edit mr-2"></i> Edit & Re-submit Ad</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 font-bold text-sm"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if ($ad['status'] == 'declined'): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded">
                <p class="text-red-700 font-bold mb-1">Rejection Reason:</p>
                <p class="text-red-600 text-sm italic"><?php echo h($ad['decline_reason']); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">Category</label>
                    <select name="cat_id" id="cat_id" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" required onchange="loadFilters(this.value)">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $ad['cat_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo h($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">Title</label>
                    <input type="text" name="title" value="<?php echo h($ad['title']); ?>" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">State</label>
                    <select name="state_id" id="state_id" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" required onchange="loadLGAs(this.value)">
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>" <?php echo $ad['state_id'] == $state['id'] ? 'selected' : ''; ?>><?php echo h($state['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2 text-sm">LGA (City)</label>
                    <select name="lga_id" id="lga_id" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" required>
                        <!-- Will be populated by JS -->
                    </select>
                </div>
            </div>

            <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 mb-6">
                <label class="block text-gray-700 font-black mb-4 text-xs uppercase tracking-widest">Listing Options</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-green-200 has-[:checked]:border-green-600 has-[:checked]:bg-green-50 transition">
                        <input type="radio" name="listing_type" value="for_sale" <?php echo $ad['listing_type'] == 'for_sale' ? 'checked' : ''; ?> class="absolute opacity-0" onchange="toggleSwapFields()">
                        <span class="text-xs font-bold text-gray-800">For Sale</span>
                        <span class="text-[9px] text-gray-400 mt-1">Direct monetary trade</span>
                    </label>
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-green-200 has-[:checked]:border-green-600 has-[:checked]:bg-green-50 transition">
                        <input type="radio" name="listing_type" value="for_swap" <?php echo $ad['listing_type'] == 'for_swap' ? 'checked' : ''; ?> class="absolute opacity-0" onchange="toggleSwapFields()">
                        <span class="text-xs font-bold text-gray-800">For Swap</span>
                        <span class="text-[9px] text-gray-400 mt-1">Item-for-item exchange</span>
                    </label>
                    <label class="relative flex flex-col p-4 bg-white rounded-xl border-2 border-transparent cursor-pointer hover:border-green-200 has-[:checked]:border-green-600 has-[:checked]:bg-green-50 transition">
                        <input type="radio" name="listing_type" value="for_sale_or_swap" <?php echo $ad['listing_type'] == 'for_sale_or_swap' ? 'checked' : ''; ?> class="absolute opacity-0" onchange="toggleSwapFields()">
                        <span class="text-xs font-bold text-gray-800">Sale or Swap</span>
                        <span class="text-[9px] text-gray-400 mt-1">Accept cash or items</span>
                    </label>
                </div>

                <div id="price_field" class="<?php echo $ad['listing_type'] == 'for_swap' ? 'hidden' : ''; ?>">
                    <label class="block text-gray-700 font-bold mb-2 text-sm">Price (₦)</label>
                    <input type="number" name="price" step="0.01" value="<?php echo (float)$ad['price']; ?>" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none">
                </div>

                <div id="swap_fields" class="<?php echo $ad['listing_type'] == 'for_sale' ? 'hidden' : ''; ?> space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-sm">Estimated Market Value (₦)</label>
                        <input type="number" name="estimated_value" step="0.01" value="<?php echo (float)$ad['estimated_value']; ?>" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="e.g. 50000">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2 text-sm">Swap Preference (What do you want in exchange?)</label>
                        <input type="text" name="swap_preference" value="<?php echo h($ad['swap_preference']); ?>" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="e.g. iPhone 13 or equivalent laptop">
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="allow_cash_topup" value="1" <?php echo $ad['allow_cash_topup'] ? 'checked' : ''; ?> class="w-5 h-5 accent-green-600">
                        <span class="text-sm font-bold text-gray-700">Allow item + cash top-up</span>
                    </label>
                </div>
            </div>

            <div id="dynamic_filters" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <!-- Filters injected here -->
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2 text-sm">Description</label>
                <textarea name="description" rows="5" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" required><?php echo h($ad['description']); ?></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2 text-sm">Video Link (YouTube/TikTok)</label>
                <input type="url" name="video_url" value="<?php echo h($ad['video_url']); ?>" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none">
            </div>

            <div id="dropZone" class="mb-4 p-8 border-4 border-dashed border-gray-200 rounded-2xl bg-gray-50 hover:bg-white transition cursor-pointer relative group">
                <label class="block text-gray-700 font-bold mb-4 text-sm text-center">Update Photos (Optional - replacing all current photos)</label>
                <input type="file" name="images[]" id="fileInput" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-camera-retro text-3xl text-green-500"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-600 mb-1">Drag or click to replace photos</p>
                </div>
            </div>

            <div id="imagePreviewContainer" class="grid grid-cols-5 gap-4 mb-6 hidden"></div>

            <div class="pt-6">
                <button type="submit" class="w-full bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700 transition shadow-lg text-lg uppercase">Update Ad</button>
            </div>
        </form>
    </div>
</div>

<script>
function loadLGAs(stateId, selectedLgaId = null) {
    const lgaSelect = document.getElementById('lga_id');
    lgaSelect.innerHTML = '<option value="">Loading...</option>';

    fetch('api/lgas.php?state_id=' + stateId)
        .then(response => response.json())
        .then(data => {
            lgaSelect.innerHTML = '<option value="">Select LGA</option>';
            data.forEach(lga => {
                const option = document.createElement('option');
                option.value = lga.id;
                option.textContent = lga.name;
                if(selectedLgaId && lga.id == selectedLgaId) option.selected = true;
                lgaSelect.appendChild(option);
            });
        });
}

// Initial LGA load
loadLGAs(<?php echo $ad['state_id']; ?>, <?php echo $ad['lga_id']; ?>);

function loadFilters(catId) {
    const filterContainer = document.getElementById('dynamic_filters');
    if (!catId) {
        filterContainer.innerHTML = '';
        return;
    }

    fetch('api/filters.php?cat_id=' + catId)
        .then(response => response.json())
        .then(filters => {
            let html = '';
            const currentExtra = <?php echo $ad['ad_data'] ?: '{}'; ?>;
            for (let key in filters) {
                const f = filters[key];
                if (f.search_only) continue;
                html += '<div>';
                html += `<label class="block text-gray-700 font-bold mb-2 text-sm">${f.label}</label>`;

                const val = currentExtra[key] || '';

                if (f.type === 'select') {
                    html += `<select name="extra[${key}]" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none">`;
                    html += '<option value="">Select option</option>';
                    f.options.forEach(opt => {
                        const sel = (val == opt) ? 'selected' : '';
                        html += `<option value="${opt}" ${sel}>${opt}</option>`;
                    });
                    html += '</select>';
                } else if (f.type === 'number') {
                    html += `<input type="number" name="extra[${key}]" value="${val}" class="w-full p-3 border rounded-lg focus:border-green-500 outline-none" placeholder="Enter value">`;
                }

                html += '</div>';
            }
            filterContainer.innerHTML = html;
        });
}

// Initial Filters load
loadFilters(<?php echo $ad['cat_id']; ?>);

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

// Image logic (re-used from post-ad)
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const previewContainer = document.getElementById('imagePreviewContainer');
let allFiles = new DataTransfer();

['dragover', 'dragleave', 'drop'].forEach(ev => {
    dropZone.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); });
});

dropZone.addEventListener('dragover', () => dropZone.classList.add('bg-green-50', 'border-green-400'));
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('bg-green-50', 'border-green-400'));

dropZone.addEventListener('drop', (e) => {
    dropZone.classList.remove('bg-green-50', 'border-green-400');
    const files = e.dataTransfer.files;
    if (files.length > 0) addFiles(files);
});

fileInput.addEventListener('change', () => addFiles(fileInput.files));

function addFiles(files) {
    for (let i = 0; i < files.length; i++) {
        if (allFiles.items.length < 5) allFiles.items.add(files[i]);
    }
    fileInput.files = allFiles.files;
    renderPreviews();
}

function removeFile(index) {
    const newDT = new DataTransfer();
    for (let i = 0; i < allFiles.files.length; i++) {
        if (i !== index) newDT.items.add(allFiles.files[i]);
    }
    allFiles = newDT;
    fileInput.files = allFiles.files;
    renderPreviews();
}

function renderPreviews() {
    previewContainer.innerHTML = '';
    if (allFiles.files.length === 0) { previewContainer.classList.add('hidden'); return; }
    previewContainer.classList.remove('hidden');
    Array.from(allFiles.files).forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'relative group aspect-square rounded-xl overflow-hidden border-2 border-gray-100 shadow-sm';
            div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                    <button type="button" onclick="removeFile(${i})" class="bg-red-500 text-white w-6 h-6 rounded-full flex items-center justify-center hover:bg-red-600"><i class="fas fa-trash-alt text-[10px]"></i></button>
                </div>`;
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
