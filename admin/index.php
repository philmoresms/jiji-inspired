<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

include __DIR__ . '/../templates/admin_header.php';

// Fetch stats
$ad_count = $pdo->query("SELECT COUNT(*) FROM ads")->fetchColumn();
$pending_ad_count = $pdo->query("SELECT COUNT(*) FROM ads WHERE status = 'pending'")->fetchColumn();
$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'successful'")->fetchColumn() ?: 0;
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
        <h2 class="text-gray-500 font-bold uppercase text-xs mb-1">Total Ads</h2>
        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($ad_count); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-yellow-500">
        <h2 class="text-gray-500 font-bold uppercase text-xs mb-1">Pending Moderation</h2>
        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($pending_ad_count); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
        <h2 class="text-gray-500 font-bold uppercase text-xs mb-1">Total Users</h2>
        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($user_count); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-purple-500">
        <h2 class="text-gray-500 font-bold uppercase text-xs mb-1">Total Revenue</h2>
        <p class="text-3xl font-bold text-gray-800">₦<?php echo number_format($total_revenue, 2); ?></p>
    </div>
</div>

<!-- Security Monitoring Section -->
<div class="bg-white rounded-lg shadow-sm mb-8 overflow-hidden">
    <div class="bg-gray-50 p-4 border-b flex justify-between items-center">
        <h2 class="font-bold text-gray-800"><i class="fas fa-shield-alt mr-2 text-red-500"></i> Security Monitor & Protection Logs</h2>
        <a href="security.php" class="text-sm text-blue-600 hover:underline">Manage Security</a>
    </div>
    <div class="p-6 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold">
                <tr>
                    <th class="p-3">IP Address</th>
                    <th class="p-3">User/Admin</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Time</th>
                    <th class="p-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $logs = $pdo->query("SELECT * FROM login_logs ORDER BY attempted_at DESC LIMIT 5")->fetchAll();
                foreach ($logs as $log):
                    $is_whitelisted = $pdo->prepare("SELECT status FROM ip_security WHERE ip_address = ?");
                    $is_whitelisted->execute([$log['ip_address']]);
                    $ip_status = $is_whitelisted->fetchColumn();
                ?>
                <tr>
                    <td class="p-3">
                        <?php echo h($log['ip_address']); ?>
                        <?php if ($ip_status === 'whitelisted'): ?>
                            <i class="fas fa-crown text-green-500 ml-1" title="Whitelisted"></i>
                        <?php endif; ?>
                    </td>
                    <td class="p-3"><?php echo h($log['username'] ?: 'Unknown'); ?> (<?php echo $log['is_admin'] ? 'Admin' : 'User'; ?>)</td>
                    <td class="p-3">
                        <?php if ($log['is_success']): ?>
                            <span class="text-green-600 font-bold">Success</span>
                        <?php else: ?>
                            <span class="text-red-600 font-bold">Failed</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-3"><?php echo $log['attempted_at']; ?></td>
                    <td class="p-3">
                        <a href="security_action.php?ip=<?php echo urlencode($log['ip_address']); ?>&action=blacklist" class="text-red-600 hover:underline">Blacklist</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
