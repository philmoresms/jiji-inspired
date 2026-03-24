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
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon_class) VALUES (?, ?, ?)");
        $stmt->execute([$name, $slug, $icon]);
        redirect('categories.php', 'Category added.');
    }
    if (isset($_POST['delete_cat'])) {
        $id = (int)$_POST['cat_id'];
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        redirect('categories.php', 'Category deleted.');
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-sm">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Manage Product Categories</h2>

    <form method="POST" class="mb-8 flex gap-4 bg-gray-50 p-4 rounded-lg border">
        <input type="text" name="name" placeholder="Category Name" class="flex-1 p-2 border rounded" required>
        <input type="text" name="icon" placeholder="FontAwesome Icon (e.g. fa-car)" class="flex-1 p-2 border rounded" required>
        <button type="submit" name="add_cat" class="bg-green-600 text-white px-6 py-2 rounded font-bold hover:bg-green-700 transition">Add Category</button>
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
            <form method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                <input type="hidden" name="cat_id" value="<?php echo $cat['id']; ?>">
                <button type="submit" name="delete_cat" class="text-red-400 hover:text-red-600 transition"><i class="fas fa-trash"></i></button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
