<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';

$q = $_GET['q'] ?? '';
$cat_id = (int)($_GET['cat_id'] ?? 0);
$state_id = (int)($_GET['state_id'] ?? 0);
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 10000000);

$query = "SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name
          FROM ads a
          JOIN states s ON a.state_id = s.id
          WHERE a.status = 'active'";

$params = [];

if ($q) {
    $query .= " AND (a.title LIKE ? OR a.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($cat_id) {
    $query .= " AND a.cat_id = ?";
    $params[] = $cat_id;
}
if ($state_id) {
    $query .= " AND a.state_id = ?";
    $params[] = $state_id;
}
if ($min_price) {
    $query .= " AND a.price >= ?";
    $params[] = $min_price;
}
if ($max_price) {
    $query .= " AND a.price <= ?";
    $params[] = $max_price;
}

$query .= " ORDER BY a.is_featured DESC, a.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Search Filters Sidebar -->
        <aside class="md:w-1/4 bg-white p-6 rounded-2xl shadow-sm h-fit sticky top-24">
            <h3 class="font-bold text-gray-800 border-b pb-4 mb-6 uppercase tracking-wider">Refine Search</h3>
            <form action="search.php" method="GET" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Keyword</label>
                    <input type="text" name="q" value="<?php echo h($q); ?>" class="w-full p-2 border rounded focus:border-green-500 outline-none font-bold text-gray-700" placeholder="Search keywords...">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Category</label>
                    <select name="cat_id" class="w-full p-2 border rounded focus:border-green-500 outline-none font-bold text-gray-700">
                        <option value="">All Categories</option>
                        <?php
                        $cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
                        foreach ($cats as $cat) {
                            $sel = ($cat_id == $cat['id']) ? 'selected' : '';
                            echo "<option value='{$cat['id']}' $sel>{$cat['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Location</label>
                    <select name="state_id" class="w-full p-2 border rounded focus:border-green-500 outline-none font-bold text-gray-700">
                        <option value="">All States</option>
                        <?php
                        $states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll();
                        foreach ($states as $state) {
                            $sel = ($state_id == $state['id']) ? 'selected' : '';
                            echo "<option value='{$state['id']}' $sel>{$state['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Price Range (₦)</label>
                    <div class="flex gap-2">
                        <input type="number" name="min_price" value="<?php echo $min_price ?: ''; ?>" placeholder="Min" class="w-1/2 p-2 border rounded focus:border-green-500 outline-none font-bold text-gray-700 text-sm">
                        <input type="number" name="max_price" value="<?php echo $max_price ?: ''; ?>" placeholder="Max" class="w-1/2 p-2 border rounded focus:border-green-500 outline-none font-bold text-gray-700 text-sm">
                    </div>
                </div>
                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition uppercase shadow-md">Apply Filters</button>
            </form>
        </aside>

        <!-- Search Results (Right) -->
        <div class="md:w-3/4">
            <h2 class="text-2xl font-bold text-gray-800 mb-8 border-l-8 border-green-600 pl-4 uppercase">
                <?php echo $q ? "Search Results for \"".h($q)."\"" : "Marketplace Browser"; ?>
                <span class="text-sm text-gray-400 ml-2 font-bold">(<?php echo count($ads); ?> found)</span>
            </h2>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($ads as $ad): ?>
                <a href="ad.php?id=<?php echo $ad['id']; ?>" class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition-all group border border-gray-100">
                    <div class="relative h-48 overflow-hidden">
                        <img src="<?php echo $ad['image'] ? 'uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                        <?php if ($ad['is_featured']): ?>
                            <span class="absolute top-4 left-4 bg-yellow-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase shadow-md"><i class="fas fa-rocket"></i> BOOSTED</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h4 class="text-sm font-bold text-gray-800 line-clamp-2 h-10 mb-3 group-hover:text-green-600 transition"><?php echo h($ad['title']); ?></h4>
                        <p class="text-green-600 font-extrabold text-lg mb-4">₦<?php echo number_format($ad['price']); ?></p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider"><i class="fas fa-map-marker-alt mr-1 text-green-500"></i> <?php echo h($ad['state_name']); ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($ads)): ?>
            <div class="bg-white p-20 rounded-2xl text-center border-2 border-dashed border-gray-100">
                <i class="fas fa-search-minus text-5xl text-gray-100 mb-6"></i>
                <h3 class="text-xl font-bold text-gray-400">No matching items found</h3>
                <p class="text-gray-300 font-bold">Try adjusting your filters or search keywords.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
