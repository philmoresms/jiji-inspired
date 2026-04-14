<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Jiji-Inspired-1.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-green-800 text-white min-h-screen">
            <div class="p-6 font-bold text-2xl border-b border-green-700">Jiji Admin</div>
            <nav class="p-4">
                <ul class="space-y-2">
                    <li><a href="/" class="block p-2 hover:bg-green-700 rounded transition"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a></li>
                    <li><a href="ads.php" class="block p-2 hover:bg-green-700 rounded transition"><i class="fas fa-ad mr-2"></i> Ad Moderation</a></li>
                    <li><a href="users.php" class="block p-2 hover:bg-green-700 rounded transition"><i class="fas fa-users mr-2"></i> User Management</a></li>
                    <li><a href="categories.php" class="block p-2 hover:bg-green-700 rounded transition"><i class="fas fa-list mr-2"></i> Categories</a></li>
                    <li><a href="locations.php" class="block p-2 hover:bg-green-700 rounded transition"><i class="fas fa-map-marker-alt mr-2"></i> Locations</a></li>
                    <li><a href="security.php" class="block p-2 hover:bg-green-700 rounded transition"><i class="fas fa-shield-alt mr-2"></i> Security & Firewall</a></li>
                    <li><a href="payments.php" class="block p-2 hover:bg-green-700 rounded transition"><i class="fas fa-receipt mr-2"></i> Revenue & Payments</a></li>
                    <li><a href="settings.php" class="block p-2 hover:bg-green-700 rounded transition"><i class="fas fa-cog mr-2"></i> Global Settings</a></li>
                    <li><a href="/logout" class="block p-2 hover:bg-red-700 rounded transition text-red-200 mt-8"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <header class="flex justify-between items-center mb-8 border-b pb-4">
                <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <strong><?php echo h($_SESSION['admin_user']); ?></strong></span>
                    <?php
                    $my_ip = get_client_ip();
                    $stmt_ip = $pdo->prepare("SELECT status FROM ip_security WHERE ip_address = ?");
                    $stmt_ip->execute([$my_ip]);
                    if ($stmt_ip->fetchColumn() === 'whitelisted'):
                    ?>
                        <div class="flex items-center gap-1 bg-green-50 px-3 py-1 rounded-full border border-green-200">
                            <i class="fas fa-crown text-green-500 text-xs"></i>
                            <span class="text-[9px] font-black text-green-600 uppercase tracking-tighter">Trusted IP</span>
                        </div>
                    <?php endif; ?>
                </div>
            </header>
