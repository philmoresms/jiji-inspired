<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT a.*, u.full_name as seller_name, u.phone as seller_phone, u.is_verified, c.name as cat_name, s.name as state_name, l.name as lga_name
                     FROM ads a
                     JOIN users u ON a.user_id = u.id
                     JOIN categories c ON a.cat_id = c.id
                     JOIN states s ON a.state_id = s.id
                     JOIN lgas l ON a.lga_id = l.id
                     WHERE a.id = ? AND a.status = 'active'");
$stmt->execute([$id]);
$ad = $stmt->fetch();

if (!$ad) {
    redirect('index.php', 'Ad not found or pending moderation.');
}

// Increment views
$pdo->prepare("UPDATE ads SET views = views + 1 WHERE id = ?")->execute([$id]);

// Get images
$stmt = $pdo->prepare("SELECT image_path, is_main FROM ad_images WHERE ad_id = ? ORDER BY is_main DESC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content (Left) -->
        <div class="lg:w-2/3">
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
                <!-- Gallery -->
                <div class="relative h-96 bg-black flex items-center justify-center group">
                    <img id="mainImage" src="/uploads/ads/<?php echo $images[0]['image_path'] ?? 'default.jpg'; ?>" class="max-h-full max-w-full object-contain">
                    <?php if (count($images) > 1): ?>
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 overflow-x-auto p-2 bg-black/40 rounded-lg backdrop-blur-sm max-w-[90%]">
                            <?php foreach ($images as $img): ?>
                                <img src="/uploads/ads/<?php echo $img['image_path']; ?>" class="w-12 h-12 rounded object-cover cursor-pointer border-2 border-transparent hover:border-green-500 transition-all" onclick="document.getElementById('mainImage').src = this.src">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <h1 class="text-3xl font-bold text-gray-800"><?php echo h($ad['title']); ?></h1>
                        <p class="text-3xl font-bold text-green-600">₦<?php echo number_format($ad['price']); ?></p>
                    </div>

                    <div class="flex items-center gap-4 mb-8 text-sm text-gray-500 font-bold">
                        <span class="bg-gray-100 px-3 py-1 rounded-full"><i class="fas fa-map-marker-alt text-green-600 mr-1"></i> <?php echo h($ad['state_name']); ?>, <?php echo h($ad['lga_name']); ?></span>
                        <span class="bg-gray-100 px-3 py-1 rounded-full"><i class="fas fa-clock text-blue-600 mr-1"></i> <?php echo date('d M, Y', strtotime($ad['created_at'])); ?></span>
                        <span class="bg-gray-100 px-3 py-1 rounded-full"><i class="fas fa-eye text-purple-600 mr-1"></i> <?php echo $ad['views']; ?> views</span>
                    </div>

                    <div class="prose max-w-none text-gray-600 leading-relaxed border-t pt-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Description</h3>
                        <p class="mb-8"><?php echo nl2br(h($ad['description'])); ?></p>

                        <?php if ($ad['video_url']): ?>
                            <div class="mt-8">
                                <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fab fa-youtube text-red-600 mr-2"></i> Video Tour</h3>
                                <div class="relative pb-[56.25%] h-0 rounded-xl overflow-hidden shadow-lg border-4 border-gray-100">
                                    <?php
                                    $vurl = $ad['video_url'];
                                    if (strpos($vurl, 'youtube.com') !== false || strpos($vurl, 'youtu.be') !== false) {
                                        parse_str(parse_url($vurl, PHP_URL_QUERY), $vparams);
                                        $vid = $vparams['v'] ?? basename(parse_url($vurl, PHP_URL_PATH));
                                        echo '<iframe class="absolute top-0 left-0 w-full h-full" src="https://www.youtube.com/embed/'.$vid.'" frameborder="0" allowfullscreen></iframe>';
                                    } else {
                                        echo '<a href="'.h($vurl).'" target="_blank" class="bg-blue-50 text-blue-600 p-4 rounded-lg block font-bold text-center hover:bg-blue-100 transition">View External Video Link</a>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar (Right) -->
        <div class="lg:w-1/3">
            <div class="bg-white p-8 rounded-2xl shadow-sm border-2 border-green-50 sticky top-24">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-2xl font-bold uppercase">
                        <?php echo substr($ad['seller_name'], 0, 1); ?>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg text-gray-800"><?php echo h($ad['seller_name']); ?></h4>
                        <?php if ($ad['is_verified']): ?>
                            <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-200"><i class="fas fa-check-circle"></i> VERIFIED SELLER</span>
                        <?php else: ?>
                            <span class="text-xs font-bold text-gray-400">Regular Seller</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="space-y-4">
                    <button onclick="this.innerHTML='<i class=\'fas fa-phone mr-2\'></i> <?php echo h($ad['seller_phone']); ?>'" class="w-full bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700 transition shadow-lg text-lg flex items-center justify-center">
                        <i class="fas fa-phone-alt mr-2"></i> SHOW CONTACT
                    </button>

                    <?php if (is_user_logged_in() && $_SESSION['user_id'] != $ad['user_id']): ?>
                        <a href="chat.php?ad_id=<?php echo $ad['id']; ?>" class="w-full bg-white text-green-600 border-2 border-green-600 py-4 rounded-xl font-bold hover:bg-green-600 hover:text-white transition flex items-center justify-center">
                            <i class="fas fa-comment-dots mr-2"></i> START CHAT
                        </a>
                    <?php elseif (!is_user_logged_in()): ?>
                        <a href="login.php" class="w-full bg-white text-green-600 border-2 border-green-600 py-4 rounded-xl font-bold hover:bg-green-600 hover:text-white transition flex items-center justify-center">
                             LOGIN TO CHAT
                        </a>
                    <?php endif; ?>
                </div>

                <div class="mt-8 p-4 bg-yellow-50 rounded-xl border border-yellow-100 text-center">
                    <p class="text-xs font-bold text-yellow-800 uppercase mb-2">Safety Tips</p>
                    <ul class="text-[10px] text-yellow-700 text-left space-y-1">
                        <li>• Never pay in advance, even for delivery.</li>
                        <li>• Meet in a safe, public location.</li>
                        <li>• Check the item before you buy it.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
