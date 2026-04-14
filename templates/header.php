<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/user_auth.php';

// Fetch global settings for site name, etc.
if (isset($pdo)) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Global IP Blacklist/Country Check
if (isset($pdo)) {
    require_once __DIR__ . '/../inc/security.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($page_title ?? ($settings['site_name'] ?? 'Jiji Inspired')); ?></title>
    <meta name="description" content="<?php echo h($page_desc ?? ($settings['meta_description'] ?? '')); ?>">
    <meta name="keywords" content="<?php echo h($page_keywords ?? ($settings['meta_keywords'] ?? '')); ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="manifest" href="/manifest.json">
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-green-600"><?php echo h($settings['site_name'] ?? 'Jiji Clone'); ?></a>

            <div class="flex-1 max-w-2xl mx-8 hidden md:block">
                <form action="/search" method="GET" class="flex items-center bg-gray-100 rounded-xl overflow-hidden border-2 border-transparent focus-within:border-green-500 focus-within:bg-white transition-all shadow-sm">
                    <div class="flex items-center px-4 border-r border-gray-200 gap-2">
                        <i class="fas fa-map-marker-alt text-green-600 text-sm"></i>
                        <select name="state_id" class="bg-transparent text-xs font-bold text-gray-600 outline-none py-3 cursor-pointer">
                            <option value="">All Nigeria</option>
                            <?php
                            $h_states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll();
                            foreach ($h_states as $hs) echo "<option value='{$hs['id']}'>".h($hs['name'])."</option>";
                            ?>
                        </select>
                    </div>
                    <input type="text" name="q" placeholder="Search for anything..." class="flex-1 bg-transparent p-3 text-sm font-bold text-gray-700 outline-none" required>
                    <button type="submit" class="bg-green-600 text-white px-6 py-3 hover:bg-green-700 transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <div class="flex items-center space-x-4">
                <?php if (is_user_logged_in()): ?>
                    <a href="/profile" class="text-gray-600 hover:text-green-600 font-bold"><i class="fas fa-user mr-1"></i> Profile</a>
                    <a href="/post-ad" class="bg-yellow-500 text-white px-4 py-2 rounded-lg font-bold hover:bg-yellow-600 transition">SELL</a>
                <?php else: ?>
                    <a href="/login" class="text-gray-600 hover:text-green-600 font-bold">Sign In</a>
                    <a href="/register" class="text-green-600 font-bold border-2 border-green-600 px-4 py-1 rounded-lg hover:bg-green-600 hover:text-white transition">Registration</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
