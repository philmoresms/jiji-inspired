<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/security.php';
require_admin();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE payments SET status = 'successful' WHERE id = ?");
        $stmt->execute([$id]);

        // Boost the associated ad
        $stmt = $pdo->prepare("SELECT ad_id FROM payments WHERE id = ?");
        $stmt->execute([$id]);
        $ad_id = $stmt->fetchColumn();
        $pdo->prepare("UPDATE ads SET is_featured = 1 WHERE id = ?")->execute([$ad_id]);

        redirect('payments.php', 'Payment approved and ad boosted.');
    } elseif ($action == 'decline') {
        $pdo->prepare("UPDATE payments SET status = 'failed' WHERE id = ?")->execute([$id]);
        redirect('payments.php', 'Payment declined.');
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>

<div class="bg-white p-6 rounded-lg shadow-sm">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Revenue & Payment Moderation</h2>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs">
                <tr>
                    <th class="p-4">Reference</th>
                    <th class="p-4">User</th>
                    <th class="p-4">Method</th>
                    <th class="p-4">Amount</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Proof</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $stmt = $pdo->query("SELECT p.*, u.full_name as user_name FROM payments p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
                while ($payment = $stmt->fetch()):
                ?>
                <tr>
                    <td class="p-4 font-mono font-bold"><?php echo h($payment['reference']); ?></td>
                    <td class="p-4"><?php echo h($payment['user_name']); ?></td>
                    <td class="p-4 uppercase font-bold text-xs"><?php echo h($payment['method']); ?></td>
                    <td class="p-4 font-bold">₦<?php echo number_format($payment['amount'], 2); ?></td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?php
                            echo $payment['status'] == 'successful' ? 'bg-green-100 text-green-700' : ($payment['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                        ?>">
                            <?php echo $payment['status']; ?>
                        </span>
                    </td>
                    <td class="p-4">
                        <?php if ($payment['proof_image']): ?>
                            <a href="../uploads/proofs/<?php echo $payment['proof_image']; ?>" target="_blank" class="text-blue-500 hover:underline"><i class="fas fa-image mr-1"></i> View Proof</a>
                        <?php else: ?>
                            <span class="text-gray-300">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 space-x-2">
                        <?php if ($payment['status'] == 'pending' && $payment['method'] == 'bank_transfer'): ?>
                            <a href="payments.php?action=approve&id=<?php echo $payment['id']; ?>" class="bg-green-500 text-white px-2 py-1 rounded text-xs hover:bg-green-600 transition shadow-sm font-bold">Approve</a>
                            <a href="payments.php?action=decline&id=<?php echo $payment['id']; ?>" class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600 transition shadow-sm font-bold" onclick="return confirm('Decline this payment?')">Decline</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>
