<?php
/**
 * Main Entry Point for Jiji-Inspired-1.0
 */

// Define project root
define('ROOT_PATH', __DIR__);

// Check if installed
if (!file_exists(ROOT_PATH . '/config/config.php')) {
    header('Location: install/index.php');
    exit;
}

// Load configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/inc/functions.php';
require_once ROOT_PATH . '/inc/security.php';
require_once ROOT_PATH . '/inc/user_auth.php';

// Fetch categories for sidebar
$stmt = $pdo->query("SELECT * FROM categories WHERE parent_id = 0 ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Fetch featured ads
$stmt = $pdo->query("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     WHERE a.status = 'active' AND a.is_featured = 1
                     ORDER BY a.created_at DESC LIMIT 8");
$featured_ads = $stmt->fetchAll();

// Fetch regular ads
$stmt = $pdo->query("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     WHERE a.status = 'active' AND a.is_featured = 0
                     ORDER BY a.created_at DESC LIMIT 20");
$recent_ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar Categories -->
        <aside class="w-full md:w-64 bg-white rounded-lg shadow-sm p-4 h-fit">
            <h3 class="font-bold text-gray-800 border-b pb-2 mb-4">Categories</h3>
            <ul class="space-y-2">
                <?php foreach ($categories as $cat): ?>
                <li>
                    <a href="category.php?slug=<?php echo $cat['slug']; ?>" class="flex items-center p-2 rounded hover:bg-green-50 transition group">
                        <i class="fas <?php echo h($cat['icon_class']); ?> w-6 text-gray-500 group-hover:text-green-600"></i>
                        <span class="text-sm font-bold text-gray-700 group-hover:text-green-600"><?php echo h($cat['name']); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Hero Banner -->
            <div class="bg-green-600 rounded-2xl p-8 mb-10 text-white flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-2">Sell Faster, Buy Smarter.</h2>
                    <p class="text-green-100 font-bold">Nigeria's #1 Classifieds Platform</p>
                </div>
                <a href="post-ad.php" class="mt-4 md:mt-0 bg-yellow-500 text-white px-8 py-3 rounded-full font-bold hover:bg-yellow-600 transition shadow-lg">POST AD FOR FREE</a>
            </div>

            <!-- Featured Ads -->
            <?php if ($featured_ads): ?>
            <section class="mb-12">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800 uppercase tracking-wide border-l-4 border-yellow-500 pl-3">Premium Boosted</h3>
                    <a href="#" class="text-green-600 font-bold hover:underline">View All</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($featured_ads as $ad): ?>
                    <a href="ad.php?id=<?php echo $ad['id']; ?>" class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
                        <div class="relative h-40">
                            <img src="<?php echo $ad['image'] ? 'uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover">
                            <span class="absolute top-2 left-2 bg-yellow-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Featured</span>
                        </div>
                        <div class="p-3">
                            <h4 class="text-sm font-bold text-gray-800 line-clamp-2 h-10 mb-2"><?php echo h($ad['title']); ?></h4>
                            <p class="text-green-600 font-bold mb-2">₦<?php echo number_format($ad['price']); ?></p>
                            <p class="text-[10px] text-gray-400 font-bold"><i class="fas fa-map-marker-alt"></i> <?php echo h($ad['state_name']); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Recent Ads -->
            <section>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800 uppercase tracking-wide border-l-4 border-green-600 pl-3">Trending Now</h3>
                    <a href="#" class="text-green-600 font-bold hover:underline">View All</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php foreach ($recent_ads as $ad): ?>
                    <a href="ad.php?id=<?php echo $ad['id']; ?>" class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
                        <div class="h-40">
                            <img src="<?php echo $ad['image'] ? 'uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="p-3">
                            <h4 class="text-sm font-bold text-gray-800 line-clamp-2 h-10 mb-2"><?php echo h($ad['title']); ?></h4>
                            <p class="text-green-600 font-bold mb-2">₦<?php echo number_format($ad['price']); ?></p>
                            <p class="text-[10px] text-gray-400 font-bold"><i class="fas fa-map-marker-alt"></i> <?php echo h($ad['state_name']); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
