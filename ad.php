<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT a.*, u.full_name as seller_name, u.phone as seller_phone, u.is_verified, u.verification_tier, u.created_at as seller_created_at, c.name as cat_name, s.name as state_name, l.name as lga_name
                     FROM ads a
                     JOIN users u ON a.user_id = u.id
                     JOIN categories c ON a.cat_id = c.id
                     JOIN states s ON a.state_id = s.id
                     JOIN lgas l ON a.lga_id = l.id
                     WHERE a.id = ? AND u.is_suspended = 0");
$stmt->execute([$id]);
$ad = $stmt->fetch();

if (!$ad || ($ad['status'] !== 'active' && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $ad['user_id']) && !isset($_SESSION['admin_id']))) {
    redirect('index.php', 'Ad not found or pending moderation.');
}

// Log view for analytics (Feature 07)
$viewer_id = $_SESSION["user_id"] ?? null;
$pdo->prepare("INSERT INTO seller_analytics (ad_id, viewer_id, source, ip_address) VALUES (?, ?, ?, ?)")->execute([$id, $viewer_id, $_GET["source"] ?? "direct", get_client_ip()]);

// Increment views
$pdo->prepare("UPDATE ads SET views = views + 1 WHERE id = ?")->execute([$id]);

// Get images
$stmt = $pdo->prepare("SELECT image_path, is_main FROM ad_images WHERE ad_id = ? ORDER BY is_main DESC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// Calculate Deal Safety Score (Feature 08)
$safety_score = calculate_safety_score(['verification_tier' => $ad['verification_tier'], 'is_verified' => $ad['is_verified'], 'created_at' => $ad['seller_created_at']], $ad);

// SEO Meta Data
$meta = generate_meta_tags($ad['title'], $ad['description'], $ad['cat_name'] . " " . $ad['state_name']);
$page_title = $meta['title'] . " - " . ($settings['site_name'] ?? 'Classifieds');
$page_desc = $meta['description'];
$page_keywords = $meta['keywords'];

// Get similar ads
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
                <p class="font-bold">Ad Preview Mode (Status: <?php echo h($ad['status']); ?>)</p>
                <p class="text-sm">Only you and admins can see this page.</p>
            </div>
            <?php if ($ad['status'] === 'declined' && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $ad['user_id']): ?>
                <a href="edit-ad.php?id=<?php echo $ad['id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition shadow-md">Edit & Re-submit</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content (Left) -->
        <div class="lg:w-2/3">
            <div class="bg-white rounded-3xl shadow-sm overflow-hidden mb-8 border border-gray-100">
                <!-- Gallery -->
                <div class="relative h-[500px] bg-gray-900 flex items-center justify-center group">
                    <img id="mainImage" src="<?php echo isset($images[0]) ? '/uploads/ads/'.$images[0]['image_path'] : 'https://placehold.co/800x600?text=No+Image'; ?>" class="max-h-full max-w-full object-contain">

                    <?php if (count($images) > 1): ?>
                        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 overflow-x-auto p-3 bg-black/40 rounded-2xl backdrop-blur-md max-w-[90%] scrollbar-hide">
                            <?php foreach ($images as $index => $img): ?>
                                <img src="/uploads/ads/<?php echo $img['image_path']; ?>" class="w-14 h-14 rounded-xl object-cover cursor-pointer border-2 border-transparent hover:border-green-500 transition-all shadow-lg" onclick="document.getElementById('mainImage').src=this.src">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-8 md:p-12">
                    <?php if (!$ad["is_verified"]): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-5 mb-8 rounded-r-2xl shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <p class="text-xs font-bold text-yellow-800">This seller has not completed identity verification. For safer transactions, we recommend only dealing with <span class="text-green-600">Verified Sellers</span>.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (strpos(strtoupper($ad["cat_name"]), "PROPERTY") !== false): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-5 mb-8 rounded-r-2xl shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center text-red-600">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <p class="text-xs font-black text-red-800 uppercase tracking-tight leading-relaxed">NEVER pay a deposit or rent in advance without first: (1) visiting the property in person, (2) confirming the seller's identity, and (3) signing a written agreement.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex flex-col md:flex-row justify-between items-start mb-8 gap-6">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-black text-gray-800 leading-tight mb-4"><?php echo h($ad['title']); ?></h1>
                            <div class="flex flex-wrap gap-3">
                                <span class="bg-gray-100 text-gray-500 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2"><i class="fas fa-map-marker-alt text-green-500"></i> <?php echo h($ad['state_name']); ?>, <?php echo h($ad['lga_name']); ?></span>
                                <span class="bg-gray-100 text-gray-500 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2"><i class="fas fa-clock text-blue-500"></i> <?php echo date('d M, Y', strtotime($ad['created_at'])); ?></span>

                                <?php
                                $color = $safety_score >= 80 ? "green" : ($safety_score >= 50 ? "yellow" : "red");
                                $icon = $safety_score >= 80 ? "fa-shield-alt" : "fa-exclamation-circle";
                                ?>
                                <div class="flex items-center gap-2 bg-<?php echo $color; ?>-50 px-4 py-2 rounded-xl border border-<?php echo $color; ?>-100 shadow-sm">
                                    <i class="fas <?php echo $icon; ?> text-<?php echo $color; ?>-500 text-xs"></i>
                                    <span class="text-[10px] font-black text-<?php echo $color; ?>-700 uppercase tracking-widest">Deal Safety: <?php echo $safety_score; ?>/100</span>
                                </div>

                                <?php
                                $stmt_prop = $pdo->prepare("SELECT * FROM property_declarations WHERE ad_id = ?");
                                $stmt_prop->execute([$ad["id"]]);
                                $prop = $stmt_prop->fetch();
                                if ($prop):
                                    $p_role = $prop["declared_role"];
                                    $p_verified = $prop["verified"];
                                    $p_color = $p_verified ? "green" : "amber";
                                    $p_icon = $p_role == "owner" ? "fa-home" : "fa-briefcase";
                                ?>
                                <div class="flex items-center gap-2 bg-<?php echo $p_color; ?>-50 px-4 py-2 rounded-xl border border-<?php echo $p_color; ?>-100 shadow-sm">
                                    <i class="fas <?php echo $p_icon; ?> text-<?php echo $p_color; ?>-500 text-xs"></i>
                                    <span class="text-[10px] font-black text-<?php echo $p_color; ?>-700 uppercase tracking-widest">
                                        <?php echo $p_verified ? h($settings['site_name'] ?? 'Classifieds') . " Verified" : "Self-Declared"; ?> <?php echo ucfirst($p_role); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-left md:text-right w-full md:w-auto">
                            <?php if ($ad['listing_type'] != 'for_swap'): ?>
                                <p class="text-4xl font-black text-green-600">₦<?php echo number_format($ad['price']); ?></p>
                            <?php endif; ?>
                            <?php if ($ad['listing_type'] != 'for_sale'): ?>
                                <div class="inline-flex items-center gap-2 bg-blue-600 text-white text-[10px] font-black px-4 py-2 rounded-xl uppercase tracking-widest mt-2 shadow-lg shadow-blue-100">
                                    <i class="fas fa-sync-alt"></i> Available for Swap
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="border-t border-gray-50 pt-10">
                        <?php if ($ad['listing_type'] != 'for_sale'): ?>
                            <div class="bg-blue-50/50 p-8 rounded-3xl border border-blue-100 mb-10">
                                <h3 class="text-blue-800 font-black text-xs uppercase tracking-[3px] mb-6 flex items-center gap-3">
                                    <div class="w-2 h-2 bg-blue-600 rounded-full"></div> Swap Terms
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="bg-white p-4 rounded-2xl shadow-sm">
                                        <p class="text-[10px] text-blue-400 font-black uppercase mb-1">Estimated Value</p>
                                        <p class="text-xl font-black text-blue-900">₦<?php echo number_format($ad['estimated_value']); ?></p>
                                    </div>
                                    <div class="bg-white p-4 rounded-2xl shadow-sm">
                                        <p class="text-[10px] text-blue-400 font-black uppercase mb-1">Desired Item</p>
                                        <p class="text-sm font-bold text-blue-900"><?php echo $ad['swap_preference'] ?: 'Open to all offers'; ?></p>
                                    </div>
                                </div>
                                <?php if ($ad['allow_cash_topup']): ?>
                                    <div class="mt-6 flex items-center gap-2 text-[11px] font-black text-green-600 uppercase tracking-widest">
                                        <i class="fas fa-check-circle"></i> Item + Cash top-up accepted
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        $extra_data = !empty($ad['ad_data']) ? json_decode($ad['ad_data'], true) : null;
                        if ($extra_data):
                        ?>
                            <div class="mb-10">
                                <h3 class="text-xs font-black text-gray-400 uppercase tracking-[3px] mb-6">Specifications</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <?php foreach ($extra_data as $key => $value): if(empty($value)) continue; ?>
                                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 hover:bg-white transition-colors">
                                            <p class="text-[9px] text-gray-400 font-black uppercase mb-1"><?php echo h(str_replace('_', ' ', $key)); ?></p>
                                            <p class="text-xs font-black text-gray-700"><?php echo h(is_array($value) ? implode(', ', $value) : $value); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-10">
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-[3px] mb-6">Description</h3>
                            <div class="text-gray-600 leading-relaxed font-medium space-y-4">
                                <?php echo nl2br(h($ad['description'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar (Right) -->
        <div class="lg:w-1/3">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 sticky top-24">
                <div class="flex items-center gap-5 mb-8">
                    <div class="w-20 h-20 bg-gradient-to-br from-green-50 to-green-100 rounded-3xl flex items-center justify-center text-green-600 text-3xl font-black uppercase shadow-inner">
                        <?php echo substr($ad['seller_name'], 0, 1); ?>
                    </div>
                    <div>
                        <h4 class="font-black text-xl text-gray-800 tracking-tight"><?php echo h($ad['seller_name']); ?></h4>
                        <div class="flex flex-col gap-2 mt-2">
                            <?php if ($ad['is_verified']): ?>
                                <div class="inline-flex items-center gap-2 bg-green-50 text-green-600 px-3 py-1.5 rounded-xl text-[10px] font-black border border-green-100 uppercase tracking-widest shadow-sm">
                            <?php
                            $stmt_rep = $pdo->prepare("SELECT badge_tier FROM seller_reputation WHERE user_id = ?");
                            $stmt_rep->execute([$ad["user_id"]]);
                            $rep = $stmt_rep->fetch();
                            if ($rep && $rep["badge_tier"] == "trusted"): ?>
                                <div class="inline-flex items-center gap-2 bg-yellow-50 text-yellow-700 px-3 py-1.5 rounded-xl text-[10px] font-black border border-yellow-100 uppercase tracking-widest shadow-sm">
                                    <i class="fas fa-crown"></i> <?php echo strtoupper(h($settings['site_name'] ?? 'Classifieds')); ?> TRUSTED
                                </div>
                            <?php endif; ?>
                                    <i class="fas fa-check-circle"></i> <?php echo ($ad["verification_tier"] == "business_verified" ? strtoupper(h($settings['site_name'] ?? 'Classifieds')) . " BUSINESS" : "NIN VERIFIED"); ?>
                                </div>
                            <?php else: ?>
                                <div class="inline-flex items-center gap-2 bg-gray-50 text-gray-400 px-3 py-1.5 rounded-xl text-[10px] font-black border border-gray-100 uppercase tracking-widest">
                                    PHONE VERIFIED
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="space-y-3">
                        <button onclick="showPhoneModal()" class="w-full bg-white border-2 border-green-600 text-green-600 py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-green-50 transition flex items-center justify-center gap-3 active:scale-95 shadow-sm">
                            <i class="fas fa-phone-alt"></i>
                            <span id="blurredPhone"><?php echo substr($ad["seller_phone"], 0, 7); ?>XXXX</span>
                        </button>
                        <p class="text-[9px] text-gray-400 font-black text-center uppercase tracking-widest">Click to show full number</p>
                    </div>

                    <?php if (is_user_logged_in() && $_SESSION['user_id'] != $ad['user_id']): ?>
                        <a href="chat.php?ad_id=<?php echo $ad['id']; ?>" class="w-full bg-green-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-green-700 transition shadow-xl shadow-green-100 flex items-center justify-center gap-3 active:scale-95">
                            <i class="fas fa-comment-dots"></i> START CHAT
                        </a>
                        <?php if ($ad['listing_type'] != 'for_sale'): ?>
                            <a href="swap_propose.php?ad_id=<?php echo $ad['id']; ?>" class="w-full bg-blue-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-100 flex items-center justify-center gap-3 active:scale-95">
                                <i class="fas fa-exchange-alt"></i> PROPOSE A SWAP
                            </a>
                        <?php endif; ?>
                    <?php elseif (!is_user_logged_in()): ?>
                        <a href="/login" class="w-full bg-gray-800 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-black transition shadow-xl flex items-center justify-center gap-3">
                             LOGIN TO CHAT
                        </a>
                    <?php endif; ?>
                </div>

                <div class="mt-10 border-t border-gray-50 pt-8">
                    <p class="text-[10px] font-black text-gray-400 uppercase mb-6 tracking-[3px] text-center">Share this ad</p>
                    <div class="flex justify-center gap-4">
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($ad["title"] . " - ₦" . number_format($ad["price"]) . ". View on " . ($settings['site_name'] ?? 'Classifieds') . ": ") . (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" target="_blank" class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center hover:bg-green-600 hover:text-white transition-all shadow-sm">
                            <i class="fab fa-whatsapp text-xl"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm"><i class="fab fa-facebook-f text-lg"></i></a>
                    </div>
                </div>

                <div class="mt-10 p-6 bg-yellow-50 rounded-3xl border border-yellow-100">
                    <h5 class="text-[10px] font-black text-yellow-800 uppercase mb-4 tracking-widest flex items-center gap-2"><i class="fas fa-lightbulb"></i> Safety Tips</h5>
                    <ul class="text-[11px] text-yellow-700 font-bold space-y-3">
                        <li class="flex gap-2"><span>•</span> <span>Never pay in advance, even for delivery.</span></li>
                        <li class="flex gap-2"><span>•</span> <span>Meet in a safe, public location.</span></li>
                        <li class="flex gap-2"><span>•</span> <span>Check the item before you buy it.</span></li>
                    </ul>
                </div>
            </div>

            <?php if ($ad["listing_type"] !== "for_sale"): ?>
            <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-gray-100 mt-8">
                <h3 class="text-xs font-black text-gray-800 uppercase tracking-[3px] mb-8 flex items-center gap-3">
                    <i class="fas fa-sync-alt text-blue-600"></i> Smart Matches
                </h3>
                <div id="swapMatches" class="space-y-6">
                    <div class="flex items-center justify-center py-10"><i class="fas fa-spinner fa-spin text-blue-500"></i></div>
                </div>
            </div>
            <script>
            fetch("/api/swaps_matches.php?id=<?php echo $ad["id"]; ?>")
                .then(res => res.json())
                .then(matches => {
                    const container = document.getElementById("swapMatches");
                    if (matches.length === 0) {
                        container.innerHTML = "<p class='text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center'>No exact matches yet.</p>";
                        return;
                    }
                    container.innerHTML = matches.map(m => `
                        <a href="/ad.php?id=${m.id}" class="flex items-center gap-4 p-2 rounded-2xl hover:bg-blue-50 transition group">
                            <img src="/uploads/ads/${m.image || "placeholder.jpg"}" class="w-16 h-16 object-cover rounded-xl shadow-sm">
                            <div class="flex-1">
                                <h4 class="text-xs font-black text-gray-800 line-clamp-1 group-hover:text-blue-600">${m.title}</h4>
                                <p class="text-[10px] font-bold text-gray-400 mt-1">₦${new Intl.NumberFormat().format(m.price)}</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-200 group-hover:text-blue-600 text-[10px]"></i>
                        </a>
                    `).join("");
                });
            </script>
            <?php endif; ?>
        </div>
    </div>

    <!-- Similar Ads Section -->
    <?php if ($similar_ads): ?>
    <div class="mt-24 border-t border-gray-50 pt-16">
        <div class="flex items-center gap-4 mb-12">
            <div class="w-2 h-10 bg-green-600 rounded-full"></div>
            <h2 class="text-3xl font-black text-gray-800 uppercase tracking-tighter italic">Recommended Deals</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <?php foreach ($similar_ads as $s_ad): ?>
            <a href="<?php echo generate_ad_url($s_ad); ?>" class="bg-white rounded-[2rem] shadow-sm overflow-hidden hover:shadow-2xl transition-all duration-500 group border border-gray-50">
                <div class="relative h-48 overflow-hidden">
                    <img src="<?php echo $s_ad['image'] ? '/uploads/ads/'.$s_ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                    <?php if ($s_ad['is_featured']): ?>
                        <span class="absolute top-4 left-4 bg-yellow-400 text-yellow-900 text-[8px] font-black px-3 py-1 rounded-full uppercase shadow-xl">Premium</span>
                    <?php endif; ?>
                </div>
                <div class="p-6">
                    <h4 class="text-xs font-black text-gray-800 line-clamp-2 h-8 mb-4 group-hover:text-green-600 transition uppercase tracking-tight"><?php echo h($s_ad['title']); ?></h4>
                    <p class="text-green-600 font-black text-xl">₦<?php echo number_format($s_ad['price']); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Phone Safety Modal (Feature 04) -->
<div id="phoneModal" class="fixed inset-0 bg-black/60 z-[100] hidden items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 animate-slide-up shadow-2xl border border-gray-100">
        <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-shield-alt text-3xl text-green-600"></i>
        </div>
        <h3 class="text-xl font-black text-gray-800 uppercase tracking-tighter text-center mb-2">Deal Safely on <?php echo h($settings['site_name'] ?? 'Classifieds'); ?></h3>
        <p class="text-sm text-gray-500 font-bold text-center mb-8">Buyers who chat on <?php echo h($settings['site_name'] ?? 'Classifieds'); ?> before paying have full dispute support. Use our message feature to keep a record of your deal.</p>

        <div class="space-y-4">
            <a href="/chat.php?ad_id=<?php echo $ad["id"]; ?>" class="block w-full bg-green-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest text-center hover:bg-green-700 transition shadow-xl">Message on <?php echo h($settings['site_name'] ?? 'Classifieds'); ?></a>
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

<?php include __DIR__ . '/templates/footer.php'; ?>
