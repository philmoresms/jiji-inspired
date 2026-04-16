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
                     WHERE a.id = ? AND a.status = 'active' AND u.is_suspended = 0");
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

// Prepare media array for JS
$media_list = [];
foreach ($images as $img) {
    $media_list[] = ['type' => 'image', 'url' => '/uploads/ads/' . $img['image_path']];
}
if ($ad['video_url']) {
    $vurl = $ad['video_url'];
    $vid = '';
    if (strpos($vurl, 'youtube.com') !== false || strpos($vurl, 'youtu.be') !== false) {
        parse_str(parse_url($vurl, PHP_URL_QUERY), $vparams);
        $vid = $vparams['v'] ?? basename(parse_url($vurl, PHP_URL_PATH));
        $media_list[] = ['type' => 'video', 'url' => 'https://www.youtube.com/embed/' . $vid];
    }
}

// SEO Meta Data
$meta = generate_meta_tags($ad['title'], $ad['description'], $ad['cat_name'] . " " . $ad['state_name']);
$page_title = $meta['title'] . " - " . ($settings['site_name'] ?? 'Classifieds');
$page_desc = $meta['description'];
$page_keywords = $meta['keywords'];

// Get similar ads (same category, active, not current)
$stmt = $pdo->prepare("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     JOIN categories c ON a.cat_id = c.id
                     JOIN users u ON a.user_id = u.id
                     WHERE a.cat_id = ? AND a.status = 'active' AND a.id != ? AND u.is_suspended = 0
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
                <div class="relative h-96 bg-black flex items-center justify-center group cursor-zoom-in" onclick="openLightbox(0)">
                    <img id="mainImage" src="<?php echo isset($images[0]) ? '/uploads/ads/'.$images[0]['image_path'] : 'https://placehold.co/800x600?text=No+Image'; ?>" class="max-h-full max-w-full object-contain">
                    <div class="absolute inset-0 flex items-center justify-between px-4 opacity-0 group-hover:opacity-100 transition">
                        <button onclick="event.stopPropagation(); changeMainImage(-1)" class="bg-black/50 text-white w-10 h-10 rounded-full flex items-center justify-center hover:bg-green-600 transition"><i class="fas fa-chevron-left"></i></button>
                        <button onclick="event.stopPropagation(); changeMainImage(1)" class="bg-black/50 text-white w-10 h-10 rounded-full flex items-center justify-center hover:bg-green-600 transition"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <?php if (count($images) > 1 || $ad['video_url']): ?>
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 overflow-x-auto p-2 bg-black/40 rounded-lg backdrop-blur-sm max-w-[90%]" onclick="event.stopPropagation()">
                            <?php foreach ($images as $index => $img): ?>
                                <img src="/uploads/ads/<?php echo $img['image_path']; ?>" class="w-12 h-12 rounded object-cover cursor-pointer border-2 border-transparent hover:border-green-500 transition-all" onclick="setMainImage(<?php echo $index; ?>)">
                            <?php endforeach; ?>
                            <?php if ($ad['video_url']): ?>
                                <div class="w-12 h-12 rounded bg-red-600 flex items-center justify-center cursor-pointer hover:bg-red-700 transition" onclick="openLightbox(<?php echo count($images); ?>)">
                                    <i class="fas fa-play text-white text-xs"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <h1 class="text-3xl font-bold text-gray-800"><?php echo h($ad['title']); ?></h1>
                        <div class="text-right">
                            <?php if ($ad['listing_type'] != 'for_swap'): ?>
                                <p class="text-3xl font-bold text-green-600">₦<?php echo number_format($ad['price']); ?></p>
                            <?php endif; ?>
                            <?php if ($ad['listing_type'] != 'for_sale'): ?>
                                <span class="inline-block bg-blue-100 text-blue-700 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest mt-2 border border-blue-200">Available for Swap</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mb-8 text-sm text-gray-500 font-bold">
                        <span class="bg-gray-100 px-3 py-1 rounded-full"><i class="fas fa-map-marker-alt text-green-600 mr-1"></i> <?php echo h($ad['state_name']); ?>, <?php echo h($ad['lga_name']); ?></span>
                        <span class="bg-gray-100 px-3 py-1 rounded-full"><i class="fas fa-clock text-blue-600 mr-1"></i> <?php echo date('d M, Y', strtotime($ad['created_at'])); ?></span>
                        <span class="bg-gray-100 px-3 py-1 rounded-full"><i class="fas fa-eye text-purple-600 mr-1"></i> <?php echo $ad['views']; ?> views</span>
                    </div>

                    <div class="prose max-w-none text-gray-600 leading-relaxed border-t pt-8">
                        <?php if ($ad['listing_type'] != 'for_sale'): ?>
                            <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100 mb-8">
                                <h3 class="text-blue-800 font-black text-xs uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <i class="fas fa-exchange-alt"></i> Swap Details
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <p class="text-[10px] text-blue-400 font-bold uppercase mb-1">Estimated Value</p>
                                        <p class="text-lg font-black text-blue-900">₦<?php echo number_format($ad['estimated_value']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-blue-400 font-bold uppercase mb-1">Wants in Exchange</p>
                                        <p class="text-sm font-bold text-blue-900"><?php echo $ad['swap_preference'] ?: 'Open to all offers'; ?></p>
                                    </div>
                                </div>
                                <?php if ($ad['allow_cash_topup']): ?>
                                    <p class="mt-4 text-[10px] font-black text-green-600 uppercase tracking-widest"><i class="fas fa-check-circle mr-1"></i> Seller accepts Item + Cash top-up</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        $extra_data = !empty($ad['ad_data']) ? json_decode($ad['ad_data'], true) : null;
                        if ($extra_data):
                        ?>
                            <div class="mb-8">
                                <h3 class="text-xl font-bold text-gray-800 mb-4">Specifications</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <?php foreach ($extra_data as $key => $value): if(empty($value)) continue; ?>
                                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                            <p class="text-[10px] text-gray-400 font-bold uppercase mb-1"><?php echo h(str_replace('_', ' ', $key)); ?></p>
                                            <p class="text-sm font-bold text-gray-700"><?php echo h($value); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

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
                                <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-200 w-fit uppercase"><i class="fas fa-check-circle mr-1"></i> <?php echo ($ad["verification_tier"] == "business_verified" ? "TIKI BUSINESS" : "NIN VERIFIED"); ?></span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Regular Seller</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <?php
                    $phone = $ad['seller_phone'];
                    $tel_phone = $phone;
                    if (strpos($phone, '0') === 0) {
                        $tel_phone = '234' . substr($phone, 1);
                    }
                    ?>
                    <button id="showContactBtn" onclick="this.innerHTML='<a href=\'tel:<?php echo h($tel_phone); ?>\' class=\'text-white w-full flex items-center justify-center\'><i class=\'fas fa-phone mr-2\'></i> <?php echo h($phone); ?></a>'; this.onclick=null;" class="w-full bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700 transition shadow-lg text-lg flex items-center justify-center">
                        <i class="fas fa-phone-alt mr-2"></i> SHOW CONTACT
                    </button>

                    <?php if (is_user_logged_in() && $_SESSION['user_id'] != $ad['user_id']): ?>
                        <?php if ($ad['listing_type'] != 'for_sale'): ?>
                            <a href="swap_propose.php?ad_id=<?php echo $ad['id']; ?>" class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg text-lg flex items-center justify-center gap-2">
                                <i class="fas fa-exchange-alt"></i> PROPOSE A SWAP
                            </a>
                        <?php endif; ?>

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
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($ad["title"] . " - ₦" . number_format($ad["price"]) . ". View on Tiki: ") . (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" target="_blank" class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center hover:bg-green-600 hover:text-white transition-all shadow-sm">
                            <i class="fab fa-whatsapp text-xl"></i>
                        </a>
                        <?php
                        $share_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . generate_ad_url($ad));
                        $site_name_plain = $settings['site_name'] ?? 'Classifieds';
                        $share_text = urlencode("Check out this " . $ad['title'] . " on " . $site_name_plain . "!");
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
                    <img src="<?php echo $s_ad['image'] ? '/uploads/ads/'.$s_ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
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
    <button class="absolute top-6 right-6 text-white text-4xl hover:text-green-500 transition z-[110]">&times;</button>

    <button onclick="event.stopPropagation(); navigateMedia(-1)" class="absolute left-6 top-1/2 -translate-y-1/2 text-white text-5xl hover:text-green-500 transition hidden md:block z-[110]"><i class="fas fa-chevron-left"></i></button>
    <button onclick="event.stopPropagation(); navigateMedia(1)" class="absolute right-6 top-1/2 -translate-y-1/2 text-white text-5xl hover:text-green-500 transition hidden md:block z-[110]"><i class="fas fa-chevron-right"></i></button>

    <div class="w-full max-w-5xl h-full flex items-center justify-center" onclick="event.stopPropagation()">
        <img id="lightboxImg" src="" class="max-h-full max-w-full object-contain shadow-2xl hidden transition-opacity duration-300">
        <div id="lightboxVideo" class="w-full aspect-video hidden">
            <iframe id="lightboxIframe" class="w-full h-full" src="" frameborder="0" allowfullscreen></iframe>
        </div>
    </div>

    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white font-bold bg-black/50 px-4 py-2 rounded-full text-sm">
        <span id="mediaCounter">1 / 1</span>
    </div>
</div>

<script>
const media = <?php echo json_encode($media_list); ?>;
let currentIndex = 0;

function setMainImage(index) {
    currentIndex = index;
    const mainImg = document.getElementById('mainImage');
    mainImg.src = media[index].url;
}

function changeMainImage(dir) {
    currentIndex = (currentIndex + dir + media.length) % media.length;
    if (media[currentIndex].type === 'image') {
        document.getElementById('mainImage').src = media[currentIndex].url;
    } else {
        openLightbox(currentIndex);
    }
}

function openLightbox(index = currentIndex) {
    currentIndex = index;
    const lightbox = document.getElementById('lightbox');
    updateLightboxContent();
    lightbox.classList.remove('hidden');
    lightbox.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    document.getElementById('lightboxIframe').src = ''; // Stop video
    lightbox.classList.add('hidden');
    lightbox.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

function navigateMedia(dir) {
    currentIndex = (currentIndex + dir + media.length) % media.length;
    updateLightboxContent();
}

function updateLightboxContent() {
    const img = document.getElementById('lightboxImg');
    const video = document.getElementById('lightboxVideo');
    const iframe = document.getElementById('lightboxIframe');
    const counter = document.getElementById('mediaCounter');

    img.classList.add('hidden');
    video.classList.add('hidden');
    iframe.src = '';

    const current = media[currentIndex];
    if (current.type === 'image') {
        img.src = current.url;
        img.classList.remove('hidden');
    } else {
        iframe.src = current.url;
        video.classList.remove('hidden');
    }

    counter.textContent = `${currentIndex + 1} / ${media.length}`;
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (document.getElementById('lightbox').classList.contains('flex')) {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') navigateMedia(-1);
        if (e.key === 'ArrowRight') navigateMedia(1);
    }
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>

<!-- Phone Safety Modal (Feature 04) -->
<div id="phoneModal" class="fixed inset-0 bg-black/60 z-[100] hidden items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 animate-slide-up shadow-2xl border border-gray-100">
        <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-shield-alt text-3xl text-green-600"></i>
        </div>
        <h3 class="text-xl font-black text-gray-800 uppercase tracking-tighter text-center mb-2">Deal Safely on Tiki</h3>
        <p class="text-sm text-gray-500 font-bold text-center mb-8">Buyers who chat on Tiki before paying have full dispute support. Use our message feature to keep a record of your deal.</p>

        <div class="space-y-4">
            <a href="/chat.php?ad_id=<?php echo $ad["id"]; ?>" class="block w-full bg-green-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest text-center hover:bg-green-700 transition shadow-xl">Message on Tiki</a>
            <button onclick="revealNumber()" class="block w-full bg-gray-50 text-gray-400 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest text-center hover:bg-gray-100 transition">Show Number Anyway</button>
            <button onclick="closePhoneModal()" class="block w-full text-gray-300 font-black text-[9px] uppercase tracking-widest mt-4">Maybe Later</button>
        </div>
    </div>
</div>

<script>
function showPhoneModal() {
    document.getElementById("phoneModal").classList.remove("hidden");
    document.getElementById("phoneModal").classList.add("flex");
}
function closePhoneModal() {
    document.getElementById("phoneModal").classList.add("hidden");
    document.getElementById("phoneModal").classList.remove("flex");
}
function revealNumber() {
    const fullPhone = "<?php echo h($ad["seller_phone"]); ?>";
    const telLink = "tel:" + fullPhone.replace(/^0/, "234");
    document.getElementById("blurredPhone").textContent = fullPhone;
    window.location.href = telLink;
    closePhoneModal();
}
</script>
