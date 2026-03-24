<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/security.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $hashed_password]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $full_name;
            header('Location: index.php');
            exit;
        }
    }
}

include __DIR__ . '/templates/header.php';
?>

<div class="container mx-auto px-4 py-20 flex justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-green-600 text-center uppercase tracking-wider">Create Account</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm font-bold text-center"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4 text-sm font-bold text-gray-700">
                <label class="block mb-2">Full Name</label>
                <input type="text" name="full_name" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-green-500 outline-none transition" placeholder="John Doe" required autofocus>
            </div>
            <div class="mb-4 text-sm font-bold text-gray-700">
                <label class="block mb-2">Email Address</label>
                <input type="email" name="email" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-green-500 outline-none transition" placeholder="example@mail.com" required>
            </div>
            <div class="mb-4 text-sm font-bold text-gray-700">
                <label class="block mb-2">Phone Number</label>
                <input type="text" name="phone" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-green-500 outline-none transition" placeholder="08012345678" required>
            </div>
            <div class="mb-4 text-sm font-bold text-gray-700">
                <label class="block mb-2">Password</label>
                <input type="password" name="password" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-green-500 outline-none transition" placeholder="********" required>
            </div>
            <div class="mb-6 text-sm font-bold text-gray-700">
                <label class="block mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" class="w-full p-3 border-2 border-gray-100 rounded-lg focus:border-green-500 outline-none transition" placeholder="********" required>
            </div>

            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition shadow-lg uppercase">Sign Up</button>
        </form>

        <div class="mt-6 text-center text-gray-600 font-bold text-sm">
            Already have an account? <a href="login.php" class="text-green-600 hover:underline">Sign In</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
