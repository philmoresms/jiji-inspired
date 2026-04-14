<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_cat'])) {
        $name = $_POST['name'];
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $icon = $_POST['icon'];
        $is_top = isset($_POST['is_top']) ? 1 : 0;
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $parent_id = (int)($_POST['parent_id'] ?? 0);

        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_class, is_top, sort_order, parent_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $icon, $is_top, $sort_order, $parent_id]);
        redirect('categories.php', 'Category added.');
    }
    if (isset($_POST['delete_cat'])) {
        $id = (int)$_POST['cat_id'];
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        redirect('categories.php', 'Category deleted.');
    }
    if (isset($_GET['toggle_top'])) {
        $id = (int)$_GET['toggle_top'];
        $pdo->prepare("UPDATE categories SET is_top = 1 - is_top WHERE id = ?")->execute([$id]);
        redirect('categories.php', 'Top display status updated.');
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-sm">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Manage Product Categories</h2>

    <form method="POST" class="mb-8 space-y-4 bg-gray-50 p-6 rounded-xl border">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="name" placeholder="Category Name" class="p-2 border rounded" required>
            <input type="text" name="icon" placeholder="Icon (e.g. fa-car)" class="p-2 border rounded">
            <input type="number" name="sort_order" placeholder="Sort Order" class="p-2 border rounded">
            <select name="parent_id" class="p-2 border rounded">
                <option value="0">Main Category (No Parent)</option>
                <?php
                $main_cats = $pdo->query("SELECT id, name FROM categories WHERE parent_id = 0 ORDER BY name ASC")->fetchAll();
                foreach ($main_cats as $mc) echo "<option value='{$mc['id']}'>Sub of {$mc['name']}</option>";
                ?>
            </select>
        </div>
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm font-bold text-gray-600">
                <input type="checkbox" name="is_top" value="1"> Show in Top Grid (Mobile)
            </label>
            <button type="submit" name="add_cat" class="bg-green-600 text-white px-8 py-2 rounded-lg font-bold hover:bg-green-700 transition">Create Category</button>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php
        $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
        foreach ($cats as $cat):
        ?>
        <div class="flex justify-between items-center p-4 border rounded-lg hover:shadow-sm transition">
            <div class="flex items-center gap-3">
                <i class="fas <?php echo h($cat['icon_class']); ?> text-green-600 w-8 text-center"></i>
                <span class="font-bold text-gray-700"><?php echo h($cat['name']); ?></span>
            </div>
            <div class="flex items-center gap-2">
                <a href="categories.php?toggle_top=<?php echo $cat['id']; ?>" class="text-xs font-bold <?php echo $cat['is_top'] ? 'text-yellow-500' : 'text-gray-300'; ?> hover:text-yellow-600 transition" title="Toggle Top Grid Display">
                    <i class="fas fa-star"></i>
                </a>
                <form method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                    <input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>">
                    <button type="submit" name="delete_cat" class="text-red-400 hover:text-red-600 transition"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
