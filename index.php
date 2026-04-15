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
$stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM ads a JOIN users u ON a.user_id = u.id WHERE a.cat_id = c.id AND a.status = 'active' AND u.is_suspended = 0) as ad_count FROM categories c WHERE parent_id = 0 ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll();

// Fetch Top Grid categories for mobile
$stmt = $pdo->query("SELECT * FROM categories WHERE is_top = 1 ORDER BY sort_order ASC LIMIT 3");
$top_grid_categories = $stmt->fetchAll();

// Fetch featured ads
$stmt = $pdo->query("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     JOIN categories c ON a.cat_id = c.id
                     JOIN users u ON a.user_id = u.id
                     WHERE a.status = 'active' AND a.is_featured = 1 AND u.is_suspended = 0
                     ORDER BY a.created_at DESC LIMIT 8");
$featured_ads = $stmt->fetchAll();

// Fetch regular ads
$stmt = $pdo->query("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     JOIN categories c ON a.cat_id = c.id
                     JOIN users u ON a.user_id = u.id
                     WHERE a.status = 'active' AND a.is_featured = 0 AND u.is_suspended = 0
                     ORDER BY a.created_at DESC LIMIT 20");
$recent_ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Categories Top Grid (Admin Managed Mobile) -->
    <?php if ($top_grid_categories): ?>
    <div class="md:hidden grid grid-cols-3 gap-2 mb-8 px-2">
        <?php foreach ($top_grid_categories as $tcat): ?>
        <a href="/category/<?php echo $tcat['slug']; ?>" class="flex flex-col items-center bg-white p-3 rounded-2xl shadow-sm border border-gray-50">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-green-600 mb-2">
                <i class="fas <?php echo h($tcat['icon_class']); ?> text-xl"></i>
            </div>
            <span class="text-[8px] font-extrabold text-gray-800 uppercase text-center line-clamp-1"><?php echo h($tcat['name']); ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Categories Navigation (Modern Mobile Scroller) -->
    <div class="md:hidden overflow-x-auto pb-8 mb-4 scrollbar-hide snap-x snap-mandatory">
        <div class="flex gap-3 px-2">
            <?php foreach ($categories as $cat): ?>
            <a href="/category/<?php echo $cat['slug']; ?>" class="flex flex-col items-center snap-center">
                <div class="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center text-green-600 mb-2 border border-gray-50 active:scale-95 transition-transform duration-200">
                    <i class="fas <?php echo h($cat['icon_class']); ?> text-xl"></i>
                </div>
                <span class="text-[9px] font-extrabold text-gray-500 uppercase tracking-tighter text-center w-16 leading-tight"><?php echo h($cat['name']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar Categories (Desktop Only - Jiji Style) -->
        <aside class="hidden md:block w-72 bg-white rounded-2xl shadow-sm overflow-hidden h-fit sticky top-24 border border-gray-100">
            <div class="bg-gray-50 p-4 border-b border-gray-100">
                <h3 class="font-black text-gray-800 uppercase text-[10px] tracking-[2px]">Marketplace</h3>
            </div>
            <div class="p-2">
                <?php foreach ($categories as $cat): ?>
                <div class="group relative">
                    <a href="/category/<?php echo $cat['slug']; ?>" class="flex items-center justify-between p-3 rounded-xl hover:bg-green-600 hover:text-white transition-all duration-200">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-green-500/20 transition">
                                <i class="fas <?php echo h($cat['icon_class']); ?> text-sm text-gray-500 group-hover:text-white"></i>
                            </div>
                            <span class="text-sm font-bold tracking-tight"><?php echo h($cat['name']); ?></span>
                        </div>
                        <i class="fas fa-chevron-right text-[10px] opacity-30 group-hover:opacity-100"></i>
                    </a>

                    <!-- Submenu on Hover (Tiki style enhancement) -->
                    <?php
                    $stmt_sub = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? LIMIT 10");
                    $stmt_sub->execute([$cat['id']]);
                    $subs = $stmt_sub->fetchAll();
                    if ($subs):
                    ?>
                    <div class="hidden group-hover:block absolute left-full top-0 ml-2 w-64 bg-white shadow-2xl rounded-2xl border border-gray-100 p-4 z-[60]">
                        <h4 class="font-black text-[10px] text-green-600 uppercase mb-3 tracking-widest"><?php echo h($cat['name']); ?> Sub-Categories</h4>
                        <div class="grid gap-2">
                            <?php foreach ($subs as $sub): ?>
                            <a href="/category/<?php echo $sub['slug']; ?>" class="text-xs font-bold text-gray-500 hover:text-green-600 flex items-center justify-between p-2 rounded-lg hover:bg-green-50 transition">
                                <?php echo h($sub['name']); ?>
                                <i class="fas fa-plus text-[8px] opacity-0 group-hover:opacity-100"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Hero Banner (Jiji/Tiki Hybrid Style) -->
            <div class="relative bg-gradient-to-br from-green-600 to-green-700 rounded-[2rem] p-10 mb-10 text-white overflow-hidden shadow-2xl">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-yellow-500/20 rounded-full -ml-24 -mb-24 blur-3xl"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="text-center md:text-left">
                        <span class="inline-block bg-yellow-500 text-green-900 text-[10px] font-black px-3 py-1 rounded-full uppercase mb-4 tracking-widest shadow-sm">Verified Marketplace</span>
                        <h2 class="text-4xl md:text-5xl font-black mb-4 leading-tight">Everything is possible <br class="hidden md:block">with <span class="text-yellow-400"><?php echo h($settings['site_name'] ?? 'Jiji Clone'); ?></span></h2>
                        <p class="text-green-50 font-bold opacity-90 max-w-md">Nigeria's most premium classifieds platform for buying and selling anything.</p>
                    </div>
                    <div class="flex flex-col gap-4 w-full md:w-auto">
                        <a href="/post-ad" class="bg-yellow-500 text-white px-10 py-5 rounded-2xl font-black hover:bg-yellow-400 transition transform hover:-translate-y-1 shadow-2xl flex items-center justify-center gap-3">
                            <i class="fas fa-plus-circle text-xl"></i>
                            POST AN AD
                        </a>
                        <p class="text-[10px] text-center font-bold text-green-200">JOIN 5M+ VERIFIED SELLERS TODAY</p>
                    </div>
                </div>
            </div>

            <!-- Featured Ads (Enhanced Grid) -->
            <?php if ($featured_ads): ?>
            <section class="mb-16">
                <div class="flex justify-between items-center mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-8 bg-yellow-400 rounded-full"></div>
                        <h3 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Premium Boosted</h3>
                    </div>
                    <a href="/search" class="text-xs font-black text-green-600 uppercase tracking-widest hover:text-green-700 transition">View All Listings</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($featured_ads as $ad): ?>
                    <a href="<?php echo generate_ad_url($ad); ?>" class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
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

            <!-- Recent Ads (Jiji/Tiki Hybrid Feed) -->
            <section class="mt-20">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-3 h-10 bg-green-600 rounded-full shadow-[0_0_15px_rgba(22,163,74,0.5)]"></div>
                    <h3 class="text-3xl font-black text-gray-800 uppercase tracking-tighter italic">The Trending Feed</h3>
                </div>

                <div class="flex flex-col lg:flex-row gap-10">
                    <!-- Left Sidebar Category Filter (Modern List View) -->
                    <aside class="w-full lg:w-64 flex-shrink-0">
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 sticky top-24">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[3px] mb-6 px-2">Filter by Category</h4>
                            <div class="space-y-1">
                                <button onclick="filterTrending(0)" class="trending-filter-btn w-full text-left px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-wider transition-all duration-300 flex items-center justify-between group bg-green-600 text-white shadow-xl" data-cat="0">
                                    <span>All Items</span>
                                    <i class="fas fa-th-large opacity-50 group-hover:rotate-12 transition-transform"></i>
                                </button>
                                <?php foreach ($categories as $fcat): ?>
                                    <button onclick="filterTrending(<?php echo $fcat['id']; ?>)" class="trending-filter-btn w-full text-left px-4 py-3 rounded-2xl text-xs font-black uppercase tracking-wider text-gray-500 hover:bg-green-50 hover:text-green-600 transition-all duration-300 flex items-center justify-between group" data-cat="<?php echo $fcat['id']; ?>">
                                        <span class="truncate pr-2"><?php echo h($fcat['name']); ?></span>
                                        <i class="fas <?php echo h($fcat['icon_class']); ?> opacity-20 group-hover:opacity-100 transition-opacity"></i>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </aside>

                    <!-- Feed Grid -->
                    <div class="flex-1">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-8" id="trendingContainer">
                    <?php foreach ($recent_ads as $ad): ?>
                    <a href="<?php echo generate_ad_url($ad); ?>" class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition">
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
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
function filterTrending(catId) {
    // Update buttons (List View Style)
    document.querySelectorAll('.trending-filter-btn').forEach(btn => {
        if(btn.dataset.cat == catId) {
            btn.classList.add('bg-green-600', 'text-white', 'shadow-xl');
            btn.classList.remove('text-gray-500', 'hover:bg-green-50');
        } else {
            btn.classList.remove('bg-green-600', 'text-white', 'shadow-xl');
            btn.classList.add('text-gray-500', 'hover:bg-green-50');
        }
    });

    // Fetch filtered ads
    const container = document.getElementById('trendingContainer');
    container.innerHTML = '<div class="col-span-full py-20 text-center"><i class="fas fa-spinner fa-spin text-3xl text-green-500"></i></div>';

    fetch(`api/trending.php?cat_id=${catId}`)
        .then(res => res.json())
        .then(data => {
            if(data.length === 0) {
                container.innerHTML = '<div class="col-span-full py-20 text-center font-bold text-gray-400 uppercase tracking-widest text-sm">No products found in this category</div>';
                return;
            }
            container.innerHTML = data.map(ad => `
                <a href="${ad.url}" class="bg-white rounded-[2.5rem] shadow-sm overflow-hidden hover:shadow-2xl transition-all duration-500 border border-gray-100 group">
                    <div class="h-64 overflow-hidden relative">
                        <img src="${ad.image ? 'uploads/ads/'+ad.image : 'https://placehold.co/400x300?text=No+Image'}" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                        ${ad.is_featured == 1 ? '<div class="absolute top-4 left-4 bg-yellow-400 text-yellow-900 text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl border border-yellow-300">Premium</div>' : ''}
                    </div>
                    <div class="p-6">
                        <h4 class="text-sm font-black text-gray-800 line-clamp-2 h-10 mb-4 group-hover:text-green-600 transition">${ad.title}</h4>
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-green-600 font-black text-xl">₦${new Intl.NumberFormat().format(ad.price)}</p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1"><i class="fas fa-map-marker-alt text-green-500 mr-1"></i> ${ad.state_name}</p>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-red-50 group-hover:text-red-500 transition-colors duration-300">
                                <i class="far fa-heart text-sm"></i>
                            </div>
                        </div>
                    </div>
                </a>
            `).join('');
        });
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
