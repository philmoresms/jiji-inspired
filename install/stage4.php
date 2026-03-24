<?php
/**
 * Jiji-Inspired-1.0 Installer
 * Stage 4: Congratulations & Instructions
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['install_stage']) || $_SESSION['install_stage'] < 4) {
    header('Location: stage3.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jiji-Inspired-1.0 Installer - Success!</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-10 rounded-lg shadow-md w-full max-w-2xl text-center">
        <div class="mb-6 flex justify-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>
        <h1 class="text-3xl font-bold mb-4 text-green-600">Congratulations!</h1>
        <p class="text-xl text-gray-600 mb-8">Jiji-Inspired-1.0 has been successfully installed on your server.</p>

        <div class="bg-blue-50 p-6 rounded-lg text-left mb-8">
            <h2 class="font-bold text-blue-800 mb-3">Important Next Steps:</h2>
            <ul class="space-y-3 text-blue-900">
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Login to Admin Panel:</strong> Use the credentials you just created at <a href="../admin/login.php" class="underline font-bold">/admin/login.php</a></span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Configure Security:</strong> Setup your Anti-Brute Force and IP blocking rules in the security settings.</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Setup Payments:</strong> Enter your Paystack or Flutterwave API keys in the settings section.</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span><strong>Security Tip:</strong> For extra security, consider deleting the <code>/install</code> directory.</span>
                </li>
            </ul>
        </div>

        <a href="../index.php" class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-green-700 transition">Go to Homepage</a>
    </div>
</body>
</html>
