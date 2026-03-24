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
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-green-600"><?php echo h($settings['site_name'] ?? 'Jiji Clone'); ?></a>

            <div class="flex-1 max-w-xl mx-8 hidden md:block">
                <form action="search.php" method="GET" class="relative">
                    <input type="text" name="q" placeholder="I am looking for..." class="w-full p-2 pl-4 pr-10 border-2 border-green-500 rounded-lg focus:outline-none">
                    <button type="submit" class="absolute right-2 top-2 text-green-600"><i class="fas fa-search"></i></button>
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
