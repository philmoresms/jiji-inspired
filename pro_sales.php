<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/user_auth.php';
require_user();

$user_id = $_SESSION['user_id'];

// Check for active Pro package
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ads WHERE user_id = ? AND package_type IN ('premium', 'vip', 'diamond') AND status = 'active' AND expires_at > CURRENT_TIMESTAMP");
$stmt->execute([$user_id]);
$has_pro_access = $stmt->fetchColumn() > 0;

if (!$has_pro_access) {
    include __DIR__ . '/templates/header.php';
    ?>
    <div class="container mx-auto px-4 py-20 text-center">
        <div class="max-w-md mx-auto bg-white p-10 rounded-3xl shadow-xl border-2 border-primary-50">
            <div class="w-20 h-20 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-lock text-3xl text-primary-500"></i>
            </div>
            <h1 class="text-2xl font-black text-gray-900 mb-4">Pro Sales Insights</h1>
            <p class="text-gray-500 font-bold mb-8 text-sm">This feature is exclusive to sellers with an active Premium, VIP, or Diamond package.</p>
            <a href="profile.php" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-primary-700 transition shadow-lg">Upgrade to Pro</a>
        </div>
    </div>
    <?php
    include __DIR__ . '/templates/footer.php';
    exit;
}

// Fetch basic analytics
$stmt = $pdo->prepare("SELECT a.id, a.title, a.views, a.package_type,
                       (SELECT COUNT(*) FROM seller_analytics WHERE ad_id = a.id AND interaction_type IN ('click', 'call', 'chat')) as real_interactions
                       FROM ads a
                       WHERE a.user_id = ?
                       ORDER BY a.views DESC");
$stmt->execute([$user_id]);
$stats = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-10">
    <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tighter">Pro Sales Dashboard</h1>
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest flex items-center">
            <i class="fas fa-check-circle mr-2"></i> Active Pro Access
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Total Impressions</p>
            <p class="text-4xl font-black text-primary-600"><?php echo number_format(array_sum(array_column($stats, 'views'))); ?></p>
        </div>
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Real Interactions</p>
            <p class="text-4xl font-black text-green-600"><?php echo number_format(array_sum(array_column($stats, 'real_interactions'))); ?></p>
        </div>
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Engagement Rate</p>
            <?php
            $views = array_sum(array_column($stats, 'views')) ?: 1;
            $clicks = array_sum(array_column($stats, 'real_interactions'));
            $rate = ($clicks / $views) * 100;
            ?>
            <p class="text-4xl font-black text-orange-500"><?php echo number_format($rate, 1); ?>%</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
            <h2 class="font-black text-gray-800 uppercase tracking-tighter">Ad Performance Table</h2>
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Real-time data</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Listing Title</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Package</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Views</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Interactions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($stats as $s): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-gray-700"><?php echo h($s['title']); ?></td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter
                                <?php echo $s['package_type'] === 'diamond' ? 'bg-blue-100 text-blue-700' : ($s['package_type'] === 'vip' ? 'bg-yellow-100 text-yellow-800' : ($s['package_type'] === 'premium' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')); ?>">
                                <?php echo h($s['package_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-gray-900"><?php echo number_format($s['views']); ?></td>
                        <td class="px-6 py-4 text-center font-bold text-primary-600"><?php echo number_format($s['real_interactions']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
