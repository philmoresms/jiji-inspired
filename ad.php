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
    // Check if it's a preview by the owner
    $stmt = $pdo->prepare("SELECT a.*, u.full_name as seller_name, u.phone as seller_phone, u.is_verified, c.name as cat_name, s.name as state_name, l.name as lga_name
                         FROM ads a
                         JOIN users u ON a.user_id = u.id
                         JOIN categories c ON a.cat_id = c.id
                         JOIN states s ON a.state_id = s.id
                         JOIN lgas l ON a.lga_id = l.id
                         WHERE a.id = ?");
    $stmt->execute([$id]);
    $ad = $stmt->fetch();

    if (!$ad || ($ad['status'] !== 'active' && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $ad['user_id']) && !isset($_SESSION['admin_id']))) {
        redirect('index.php', 'Ad not found or pending moderation.');
    }
}

// Increment views
$pdo->prepare("UPDATE ads SET views = views + 1 WHERE id = ?")->execute([$id]);

// Get images
$stmt = $pdo->prepare("SELECT image_path, is_main FROM ad_images WHERE ad_id = ? ORDER BY is_main DESC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// Get similar ads (same category, active, not current)
$stmt = $pdo->prepare("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     WHERE a.cat_id = ? AND a.status = 'active' AND a.id != ?
                     ORDER BY a.created_at DESC LIMIT 4");
$stmt->execute([$ad['cat_id'], $id]);
$similar_ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <?php if ($ad['status'] !== 'active'): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded shadow-sm flex items-center justify-between" role="alert">
            <div>
                <p class="font-bold">Ad Preview Mode</p>
                <p class="text-sm">This ad is currently <strong><?php echo h($ad['status']); ?></strong>. Only you (the owner) and admins can see this page.</p>
                <?php if ($ad['status'] === 'declined'): ?>
                    <p class="mt-2 text-sm"><strong>Reason for rejection:</strong> <?php echo h($ad['decline_reason']); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($ad['status'] === 'declined' && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $ad['user_id']): ?>
                <a href="edit-ad.php?id=<?php echo $ad['id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition shadow-md">Edit Ad & Re-submit</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content (Left) -->
        <div class="lg:w-2/3">
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
                <!-- Gallery -->
                <div class="relative h-96 bg-black flex items-center justify-center group cursor-zoom-in" onclick="openLightbox()">
                    <img id="mainImage" src="uploads/ads/<?php echo $images[0]['image_path'] ?? 'default.jpg'; ?>" class="max-h-full max-w-full object-contain">
                    <?php if (count($images) > 1): ?>
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 overflow-x-auto p-2 bg-black/40 rounded-lg backdrop-blur-sm max-w-[90%]" onclick="event.stopPropagation()">
                            <?php foreach ($images as $img): ?>
                                <img src="uploads/ads/<?php echo $img['image_path']; ?>" class="w-12 h-12 rounded object-cover cursor-pointer border-2 border-transparent hover:border-green-500 transition-all" onclick="document.getElementById('mainImage').src = this.src">
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
                        <div class="flex flex-col gap-1 mt-1">
                            <?php if ($ad['is_featured']): ?>
                                <span class="text-[10px] font-bold text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded-full border border-yellow-200 w-fit uppercase"><i class="fas fa-crown mr-1"></i> PREMIUM AD</span>
                            <?php endif; ?>
                            <?php if ($ad['is_verified']): ?>
                                <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-200 w-fit uppercase"><i class="fas fa-check-circle mr-1"></i> VERIFIED SELLER</span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Regular Seller</span>
                            <?php endif; ?>
                        </div>
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
                        <a href="/login" class="w-full bg-white text-green-600 border-2 border-green-600 py-4 rounded-xl font-bold hover:bg-green-600 hover:text-white transition flex items-center justify-center">
                             LOGIN TO CHAT
                        </a>
                    <?php endif; ?>
                </div>

                <div class="mt-8 border-t pt-8">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest text-center">Share this ad</p>
                    <div class="flex justify-center gap-4">
                        <?php
                        $share_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . generate_ad_url($ad));
                        $share_text = urlencode("Check out this " . $ad['title'] . " on Jiji Clone!");
                        ?>
                        <a href="https://wa.me/?text=<?php echo $share_text . '%20' . $share_url; ?>" target="_blank" class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center hover:bg-green-600 transition shadow-sm"><i class="fab fa-whatsapp text-xl"></i></a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition shadow-sm"><i class="fab fa-facebook-f text-lg"></i></a>
                        <a href="https://twitter.com/intent/tweet?text=<?php echo $share_text; ?>&url=<?php echo $share_url; ?>" target="_blank" class="w-10 h-10 bg-black text-white rounded-full flex items-center justify-center hover:bg-gray-800 transition shadow-sm"><i class="fab fa-x-twitter text-lg"></i></a>
                        <a href="https://t.me/share/url?url=<?php echo $share_url; ?>&text=<?php echo $share_text; ?>" target="_blank" class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition shadow-sm"><i class="fab fa-telegram-plane text-lg"></i></a>
                    </div>
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

    <!-- Similar Ads Section -->
    <?php if ($similar_ads): ?>
    <div class="mt-20 border-t pt-16">
        <h2 class="text-2xl font-bold text-gray-800 mb-10 uppercase tracking-widest border-l-8 border-green-600 pl-6">Similar Ads You May Like</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach ($similar_ads as $s_ad): ?>
            <a href="<?php echo generate_ad_url($s_ad); ?>" class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition-all group border border-gray-100">
                <div class="relative h-48 overflow-hidden">
                    <img src="<?php echo $s_ad['image'] ? 'uploads/ads/'.$s_ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <?php if ($s_ad['is_featured']): ?>
                        <span class="absolute top-4 left-4 bg-yellow-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase shadow-md"><i class="fas fa-rocket"></i> BOOSTED</span>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <h4 class="text-sm font-bold text-gray-800 line-clamp-2 h-10 mb-3 group-hover:text-green-600 transition"><?php echo h($s_ad['title']); ?></h4>
                    <p class="text-green-600 font-extrabold text-lg mb-4">₦<?php echo number_format($s_ad['price']); ?></p>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"><i class="fas fa-map-marker-alt mr-1 text-green-500"></i> <?php echo h($s_ad['state_name']); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="fixed inset-0 bg-black/95 hidden items-center justify-center z-[100] p-4 group" onclick="closeLightbox()">
    <button class="absolute top-6 right-6 text-white text-4xl hover:text-green-500 transition">&times;</button>
    <img id="lightboxImg" src="" class="max-h-full max-w-full object-contain shadow-2xl transition-transform duration-300" onclick="event.stopPropagation()">
</div>

<script>
function openLightbox() {
    const mainImg = document.getElementById('mainImage');
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');

    lightboxImg.src = mainImg.src;
    lightbox.classList.remove('hidden');
    lightbox.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    lightbox.classList.add('hidden');
    lightbox.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

// Close on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
