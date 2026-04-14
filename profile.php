<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$user_id = $_SESSION['user_id'];

// Get user profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user ads
$stmt = $pdo->prepare("SELECT a.*, (SELECT image_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image, s.name as state_name, c.name as cat_name,
                     (SELECT status FROM payments WHERE ad_id = a.id ORDER BY created_at DESC LIMIT 1) as last_payment_status,
                     (SELECT reject_reason FROM payments WHERE ad_id = a.id ORDER BY created_at DESC LIMIT 1) as last_payment_reject_reason
                     FROM ads a
                     JOIN states s ON a.state_id = s.id
                     JOIN categories c ON a.cat_id = c.id
                     WHERE a.user_id = ?
                     ORDER BY a.created_at DESC");
$stmt->execute([$user_id]);
$user_ads = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-8 flex flex-col md:flex-row gap-8">
    <!-- Profile Sidebar -->
    <div class="md:w-1/4">
        <div class="bg-white p-8 rounded-2xl shadow-sm border-2 border-green-50 mb-8">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-3xl font-bold uppercase mb-6 mx-auto shadow-sm">
                <?php echo substr($user['full_name'], 0, 1); ?>
            </div>
            <div class="text-center">
                <h3 class="font-bold text-xl text-gray-800"><?php echo h($user['full_name']); ?></h3>
                <p class="text-sm text-gray-400 font-bold mb-4 uppercase tracking-tighter"><?php echo h($user['email']); ?></p>
                <?php if ($user['is_verified']): ?>
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200 uppercase tracking-widest"><i class="fas fa-check-circle"></i> VERIFIED</span>
                <?php else: ?>
                    <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs font-bold border border-gray-200 uppercase tracking-widest">REGULAR USER</span>
                <?php endif; ?>
            </div>

            <div class="mt-10 pt-8 border-t space-y-4 font-bold text-sm text-gray-600">
                <a href="/profile" class="block p-3 rounded-xl bg-green-50 text-green-700 transition flex items-center"><i class="fas fa-th-large mr-3"></i> MY ADS</a>
                <a href="/support" class="block p-3 rounded-xl hover:bg-green-50 transition flex items-center"><i class="fas fa-headset mr-3"></i> SUPPORT CHAT</a>
                <a href="/logout" class="block p-3 rounded-xl hover:bg-red-50 text-red-400 transition flex items-center mt-6"><i class="fas fa-sign-out-alt mr-3"></i> LOGOUT</a>
            </div>
        </div>
    </div>

    <!-- Main Profile Content -->
    <div class="md:w-3/4">
        <!-- Seller Hub Quick Access -->
        <div class="mb-10">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[3px] mb-6">Seller Hub</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="/post-ad" class="bg-gradient-to-br from-green-600 to-green-700 p-4 rounded-3xl shadow-lg border border-green-500 flex flex-col items-center text-white hover:scale-105 transition transform">
                    <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center mb-2">
                        <i class="fas fa-plus text-lg"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest">New Ad</span>
                </a>
                <a href="/support" class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center hover:border-blue-400 transition group">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center mb-2 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                        <i class="fas fa-comments text-lg"></i>
                    </div>
                    <span class="text-[10px] font-black text-gray-800 uppercase tracking-widest">Messages</span>
                </a>
                <a href="/profile_edit" class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center hover:border-yellow-400 transition group">
                    <div class="w-10 h-10 rounded-2xl bg-yellow-50 flex items-center justify-center mb-2 text-yellow-600 group-hover:bg-yellow-400 group-hover:text-white transition">
                        <i class="fas fa-user-edit text-lg"></i>
                    </div>
                    <span class="text-[10px] font-black text-gray-800 uppercase tracking-widest">Edit Profile</span>
                </a>
                <a href="/logout" class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center hover:border-red-400 transition group">
                    <div class="w-10 h-10 rounded-2xl bg-red-50 flex items-center justify-center mb-2 text-red-600 group-hover:bg-red-600 group-hover:text-white transition">
                        <i class="fas fa-power-off text-lg"></i>
                    </div>
                    <span class="text-[10px] font-black text-gray-800 uppercase tracking-widest">Logout</span>
                </a>
            </div>
        </div>

        <div class="flex justify-between items-center mb-10">
            <h1 class="text-3xl font-bold text-gray-800 uppercase border-l-8 border-green-600 pl-4">My Marketplace</h1>
            <a href="/post-ad" class="bg-yellow-500 text-white px-8 py-3 rounded-xl font-bold hover:bg-yellow-600 transition shadow-lg flex items-center"><i class="fas fa-plus mr-2"></i> SELL SOMETHING</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($user_ads as $ad): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 group">
                <div class="relative h-48">
                    <img src="<?php echo $ad['image'] ? 'uploads/ads/'.$ad['image'] : 'https://placehold.co/400x300?text=No+Image'; ?>" class="w-full h-full object-cover">
                    <span class="absolute top-4 left-4 text-[10px] font-bold px-3 py-1 rounded-full uppercase shadow-md <?php
                        echo $ad['status'] == 'active' ? 'bg-green-500 text-white' : ($ad['status'] == 'pending' ? 'bg-yellow-400 text-white' : ($ad['status'] == 'expired' ? 'bg-gray-700 text-white' : 'bg-red-500 text-white'));
                    ?>">
                        <?php echo $ad['status']; ?>
                    </span>
                    <?php if ($ad['is_featured']): ?>
                        <span class="absolute top-4 right-4 bg-yellow-400 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase shadow-md"><i class="fas fa-rocket"></i> BOOSTED</span>
                    <?php endif; ?>
                </div>
                <div class="p-5">
                    <h4 class="font-bold text-gray-800 mb-3 truncate group-hover:text-green-600 transition"><?php echo h($ad['title']); ?></h4>
                    <p class="text-green-600 font-extrabold text-lg mb-4">₦<?php echo number_format($ad['price']); ?></p>

                    <div class="flex flex-col gap-2">
                        <?php if ($ad['last_payment_status'] == 'failed'): ?>
                            <div class="bg-red-50 p-2 rounded border border-red-100 mb-1">
                                <p class="text-[9px] text-red-600 font-bold uppercase">Boost Payment Rejected</p>
                                <p class="text-[8px] text-red-500 italic"><?php echo h($ad['last_payment_reject_reason']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="flex gap-2">
                        <?php if (!$ad['is_featured'] && $ad['status'] == 'active'): ?>
                            <a href="boost.php?ad_id=<?php echo $ad['id']; ?>" class="flex-1 text-center bg-green-600 text-white py-2 rounded-lg text-xs font-bold hover:bg-green-700 transition uppercase shadow-md tracking-wider">
                                <?php echo ($ad['last_payment_status'] == 'failed') ? 'Retry Boost' : 'Boost Ad'; ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($ad['status'] != 'sold' && $ad['status'] != 'expired'): ?>
                            <a href="edit-ad.php?id=<?php echo $ad['id']; ?>" class="flex-1 text-center bg-yellow-500 text-white py-2 rounded-lg text-xs font-bold hover:bg-yellow-600 transition uppercase shadow-md tracking-wider">Edit</a>
                        <?php else: ?>
                            <a href="api/republish.php?id=<?php echo $ad['id']; ?>" class="flex-1 text-center bg-blue-600 text-white py-2 rounded-lg text-xs font-bold hover:bg-blue-700 transition uppercase shadow-md tracking-wider">Republish</a>
                        <?php endif; ?>
                        <a href="<?php echo generate_ad_url($ad); ?>" class="flex-1 text-center bg-gray-100 text-gray-600 py-2 rounded-lg text-xs font-bold hover:bg-gray-200 transition uppercase tracking-wider border border-gray-200">View</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($user_ads)): ?>
            <div class="col-span-full bg-white p-20 rounded-2xl text-center border-2 border-dashed border-gray-100">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-ad text-gray-200 text-3xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-400 mb-2">You haven't posted any ads yet</h2>
                <p class="text-gray-300 font-bold mb-8">Start selling today and reach millions of buyers!</p>
                <a href="/post-ad" class="bg-green-600 text-white px-10 py-4 rounded-xl font-bold hover:bg-green-700 transition uppercase shadow-lg tracking-widest inline-block">POST YOUR FIRST AD</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
