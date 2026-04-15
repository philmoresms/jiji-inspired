<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    redirect('index.php', 'Category not found.');
}

$cat_id = $category['id'];
$type = $_GET['type'] ?? 'all';

// Get subcategories
$stmt = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM ads a JOIN users u ON a.user_id = u.id WHERE a.cat_id = c.id AND a.status = 'active' AND u.is_suspended = 0) as ad_count FROM categories c WHERE parent_id = ? ORDER BY name ASC");
$stmt->execute([$cat_id]);
$subcategories = $stmt->fetchAll();

// SEO Meta Data
$page_title = $category['name'] . " - " . ($settings['site_name'] ?? 'Jiji Inspired');
$page_desc = "Browse the best deals in " . $category['name'] . " on " . ($settings['site_name'] ?? 'Jiji Clone');
$page_keywords = extract_keywords($category['name'], "buy sell nigeria");

// Get ads in this category
$query = "SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
         FROM ads a
         JOIN states s ON a.state_id = s.id
         JOIN categories c ON a.cat_id = c.id
         JOIN users u ON a.user_id = u.id
         WHERE a.cat_id = ? AND a.status = 'active' AND u.is_suspended = 0";

if ($type === 'sale') {
    $query .= " AND (a.listing_type = 'for_sale' OR a.listing_type = 'for_sale_or_swap')";
} elseif ($type === 'swap') {
    $query .= " AND (a.listing_type = 'for_swap' OR a.listing_type = 'for_sale_or_swap')";
}

$query .= " ORDER BY a.is_featured DESC, a.bumped_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$cat_id]);
$ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb / Back Navigation -->
    <div class="mb-6">
        <a href="/" class="text-sm font-bold text-gray-400 hover:text-green-600 transition"><i class="fas fa-arrow-left mr-1"></i> Back to Home</a>
    </div>

    <div class="bg-green-600 rounded-2xl p-10 text-white mb-12 flex flex-col md:flex-row items-center justify-between shadow-xl">
        <div class="mb-6 md:mb-0">
            <h1 class="text-4xl font-bold mb-4 uppercase tracking-widest border-l-8 border-yellow-400 pl-6"><?php echo h($category['name']); ?></h1>
            <p class="text-green-100 font-bold text-lg opacity-80">Find the best deals in <?php echo h($category['name']); ?> across Nigeria.</p>
        </div>
        <a href="/post-ad?cat_id=<?php echo $cat_id; ?>" class="bg-yellow-500 text-white px-10 py-4 rounded-full font-bold hover:bg-yellow-600 transition shadow-lg text-lg uppercase tracking-widest">SELL IN <?php echo h($category['name']); ?></a>
    </div>

    <div class="flex flex-wrap gap-2 mb-8">
        <a href="?type=all" class="px-6 py-2 rounded-full font-black text-xs uppercase tracking-widest transition <?php echo $type == 'all' ? 'bg-green-600 text-white shadow-lg' : 'bg-white text-gray-500 hover:bg-green-50'; ?> border border-gray-100">All Items</a>
        <a href="?type=sale" class="px-6 py-2 rounded-full font-black text-xs uppercase tracking-widest transition <?php echo $type == 'sale' ? 'bg-green-600 text-white shadow-lg' : 'bg-white text-gray-500 hover:bg-green-50'; ?> border border-gray-100">For Sale</a>
        <a href="?type=swap" class="px-6 py-2 rounded-full font-black text-xs uppercase tracking-widest transition <?php echo $type == 'swap' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-500 hover:bg-blue-50'; ?> border border-gray-100">For Swap</a>
    </div>

    <!-- Subcategories Scroller (Mobile) / Grid (Desktop) -->
    <?php if ($subcategories): ?>
    <div class="mb-10">
        <h3 class="font-bold text-gray-800 mb-4 uppercase text-xs tracking-widest">Browse Subcategories</h3>
        <div class="flex gap-3 overflow-x-auto pb-4 scrollbar-hide">
            <?php foreach ($subcategories as $sub): ?>
            <a href="/category/<?php echo $sub['slug']; ?>" class="bg-white px-6 py-3 rounded-full shadow-sm border border-gray-100 whitespace-nowrap hover:border-green-500 hover:text-green-600 transition text-sm font-bold text-gray-600">
                <?php echo h($sub['name']); ?> <span class="ml-1 text-[10px] opacity-50"><?php echo $sub['ad_count']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
        <?php foreach ($ads as $ad): ?>
        <a href="<?php echo generate_ad_url($ad); ?>" class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition-all border border-gray-100 group">
            <div class="relative h-48 overflow-hidden">
                <img src="<?php echo $ad['image'] ? 'uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                <?php if ($ad['is_featured']): ?>
                    <span class="absolute top-4 left-4 bg-yellow-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase shadow-md"><i class="fas fa-rocket"></i> BOOSTED</span>
                <?php endif; ?>
            </div>
            <div class="p-4">
                <h4 class="text-sm font-bold text-gray-800 line-clamp-2 h-10 mb-3 group-hover:text-green-600 transition"><?php echo h($ad['title']); ?></h4>
                <p class="text-green-600 font-extrabold text-lg mb-4">₦<?php echo number_format($ad['price']); ?></p>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"><i class="fas fa-map-marker-alt mr-1 text-green-500"></i> <?php echo h($ad['state_name']); ?></p>
            </div>
        </a>
        <?php endforeach; ?>

        <?php if (empty($ads)): ?>
            <div class="col-span-full bg-white p-20 rounded-2xl text-center border-2 border-dashed border-gray-100">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-box-open text-gray-200 text-3xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-400 mb-2">No items listed in this category yet</h2>
                <p class="text-gray-300 font-bold">Be the first to post an ad here!</p>
                <a href="post-ad.php?cat_id=<?php echo $cat_id; ?>" class="bg-green-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-green-700 transition uppercase shadow-lg inline-block mt-8">POST AD NOW</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
