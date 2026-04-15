<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_cat'])) {
        $name = $_POST['name'];
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $icon = $_POST['icon'];
        $is_top = isset($_POST['is_top']) ? 1 : 0;
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $parent_id = (int)($_POST['parent_id'] ?? 0);

        if ($is_top) {
            $top_count = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_top = 1")->fetchColumn();
            if ($top_count >= 4) {
                $error = "Maximum of 4 categories can be shown in the top grid. Please unstar another category first.";
                $is_top = 0;
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_class, is_top, sort_order, parent_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $icon, $is_top, $sort_order, $parent_id]);
            $success = "Category added.";
        }
    }

    if (isset($_POST['edit_cat'])) {
        $id = (int)$_POST['cat_id'];
        $name = $_POST['name'];
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $icon = $_POST['icon'];
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $parent_id = (int)($_POST['parent_id'] ?? 0);

        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon_class = ?, sort_order = ?, parent_id = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $icon, $sort_order, $parent_id, $id]);
        $success = "Category updated.";
    }

    if (isset($_POST['delete_cat'])) {
        $id = (int)$_POST['cat_id'];
        $pdo->prepare("DELETE FROM categories WHERE id = ? OR parent_id = ?")->execute([$id, $id]);
        $success = "Category and its subcategories deleted.";
    }
}

if (isset($_GET['toggle_top'])) {
    $id = (int)$_GET['toggle_top'];
    $current = $pdo->prepare("SELECT is_top FROM categories WHERE id = ?");
    $current->execute([$id]);
    $is_now_top = $current->fetchColumn();

    if (!$is_now_top) { // Trying to set it to top
        $top_count = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_top = 1")->fetchColumn();
        if ($top_count >= 4) {
            header("Location: categories.php?error=" . urlencode("Maximum of 4 categories can be shown in the top grid."));
            exit;
        }
    }

    $pdo->prepare("UPDATE categories SET is_top = 1 - is_top WHERE id = ?")->execute([$id]);
    header("Location: categories.php?success=" . urlencode("Top status updated."));
    exit;
}

$success = $_GET['success'] ?? $success;
$error = $_GET['error'] ?? $error;

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-10">
        <div>
            <h2 class="text-3xl font-black text-gray-800 uppercase tracking-tighter italic">Manage <span class="text-green-600">Categories</span></h2>
            <p class="text-gray-400 font-bold text-sm">Organize your marketplace hierarchy and mobile grid.</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 px-6 py-3 rounded-2xl flex items-center gap-3">
            <div class="w-10 h-10 bg-yellow-400 rounded-full flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-star"></i>
            </div>
            <div>
                <?php $top_count = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_top = 1")->fetchColumn(); ?>
                <p class="text-[10px] font-black text-yellow-700 uppercase tracking-widest">Mobile Grid Slots</p>
                <p class="text-xl font-black text-yellow-900"><?php echo $top_count; ?> / 4 used</p>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-2xl mb-8 font-bold flex items-center gap-3">
            <i class="fas fa-check-circle"></i> <?php echo h($success); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-2xl mb-8 font-bold flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i> <?php echo h($error); ?>
        </div>
    <?php endif; ?>

    <!-- Add Category Form -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 mb-12">
        <h3 class="text-xs font-black text-gray-400 uppercase tracking-[3px] mb-6">Create New Category</h3>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div class="md:col-span-1">
                <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Name</label>
                <input type="text" name="name" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-green-500 transition" placeholder="Electronics" required>
            </div>
            <div class="md:col-span-1">
                <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Icon Class</label>
                <input type="text" name="icon" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-green-500 transition" placeholder="fa-tv">
            </div>
            <div class="md:col-span-1">
                <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Parent Category</label>
                <select name="parent_id" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-green-500 transition">
                    <option value="0">Main Category</option>
                    <?php
                    $main_cats = $pdo->query("SELECT id, name FROM categories WHERE parent_id = 0 ORDER BY name ASC")->fetchAll();
                    foreach ($main_cats as $mc) echo "<option value='{$mc['id']}'>Sub of {$mc['name']}</option>";
                    ?>
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Sort Order</label>
                <input type="number" name="sort_order" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-green-500 transition" value="0">
            </div>
            <div class="md:col-span-1">
                <button type="submit" name="add_cat" class="w-full bg-green-600 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-green-700 transition shadow-xl shadow-green-100">Add Category</button>
            </div>
        </form>
    </div>

    <!-- Categories List -->
    <div class="space-y-6">
        <?php
        $main_categories = $pdo->query("SELECT * FROM categories WHERE parent_id = 0 ORDER BY sort_order ASC, name ASC")->fetchAll();
        foreach ($main_categories as $mcat):
        ?>
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 bg-gray-50 flex items-center justify-between border-b border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-green-600 shadow-sm">
                        <i class="fas <?php echo h($mcat['icon_class']); ?> text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-gray-800 uppercase tracking-tighter text-lg"><?php echo h($mcat['name']); ?></h4>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Main Category • Order: <?php echo $mcat['sort_order']; ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="categories.php?toggle_top=<?php echo $mcat['id']; ?>" class="w-10 h-10 rounded-xl flex items-center justify-center transition <?php echo $mcat['is_top'] ? 'bg-yellow-400 text-white shadow-lg shadow-yellow-100' : 'bg-gray-100 text-gray-300 hover:bg-yellow-50 hover:text-yellow-400'; ?>" title="Star for Mobile Grid">
                        <i class="fas fa-star"></i>
                    </a>
                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($mcat)); ?>)" class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" onsubmit="return confirm('Delete this category and all its subcategories?')">
                        <input type="hidden" name="cat_id" value="<?php echo $mcat['id']; ?>">
                        <button type="submit" name="delete_cat" class="w-10 h-10 bg-red-50 text-red-400 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Subcategories -->
            <div class="p-8 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php
                $stmt_sub = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name ASC");
                $stmt_sub->execute([$mcat['id']]);
                $subs = $stmt_sub->fetchAll();
                foreach ($subs as $sub):
                ?>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl group border border-transparent hover:border-green-200 transition-all">
                    <span class="font-bold text-gray-700 text-sm"><?php echo h($sub['name']); ?></span>
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($sub)); ?>)" class="text-blue-400 hover:text-blue-600"><i class="fas fa-edit text-xs"></i></button>
                        <form method="POST" onsubmit="return confirm('Delete this subcategory?')">
                            <input type="hidden" name="cat_id" value="<?php echo $sub['id']; ?>">
                            <button type="submit" name="delete_cat" class="text-red-300 hover:text-red-500"><i class="fas fa-times text-xs"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Add Sub Button (Mini) -->
                <button onclick="document.querySelector('select[name=parent_id]').value = <?php echo $mcat['id']; ?>; window.scrollTo({top: 0, behavior: 'smooth'})" class="flex items-center justify-center p-4 border-2 border-dashed border-gray-200 rounded-2xl text-gray-400 hover:border-green-400 hover:text-green-600 transition-all group">
                    <i class="fas fa-plus mr-2 text-xs"></i>
                    <span class="text-xs font-black uppercase tracking-widest">Add Sub</span>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black/60 z-[100] hidden items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-10 shadow-2xl animate-slide-up" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-2xl font-black text-gray-800 uppercase tracking-tighter italic">Edit <span class="text-blue-600">Category</span></h3>
            <button onclick="closeEditModal()" class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center text-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="cat_id" id="edit_id">
            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Name</label>
                <input type="text" name="name" id="edit_name" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 transition" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Icon Class</label>
                    <input type="text" name="icon" id="edit_icon" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Sort Order</label>
                    <input type="number" name="sort_order" id="edit_order" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-2">Parent Category</label>
                <select name="parent_id" id="edit_parent" class="w-full p-4 bg-gray-50 border-none rounded-2xl font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 transition">
                    <option value="0">Main Category</option>
                    <?php foreach ($main_cats as $mc) echo "<option value='{$mc['id']}'>Sub of {$mc['name']}</option>"; ?>
                </select>
            </div>
            <button type="submit" name="edit_cat" class="w-full bg-blue-600 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-100">Update Category</button>
        </form>
    </div>
</div>

<script>
function openEditModal(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_name').value = cat.name;
    document.getElementById('edit_icon').value = cat.icon_class;
    document.getElementById('edit_order').value = cat.sort_order;
    document.getElementById('edit_parent').value = cat.parent_id;

    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
