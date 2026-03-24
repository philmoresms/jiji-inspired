<?php
/**
 * Jiji-Inspired-1.0 Common Functions
 */

// Initialize project directories
$required_dirs = [
    __DIR__ . '/../config',
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/ads',
    __DIR__ . '/../uploads/proofs'
];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

/**
 * Sanitize output for XSS prevention
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with a message
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
    header("Location: $url");
    exit;
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return trim($ip);
}

/**
 * Image Upload & Processing (GD Library)
 */
function process_image_upload($file_tmp, $target_dir, $max_width = 800) {
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $filename = md5(uniqid(rand(), true)) . '.jpg';
    $target_file = $target_dir . '/' . $filename;

    list($width, $height, $type) = getimagesize($file_tmp);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($file_tmp);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($file_tmp);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($file_tmp);
            break;
        case IMAGETYPE_WEBP:
            $src = imagecreatefromwebp($file_tmp);
            break;
        default:
            return false;
    }

    // Calculate aspect ratio
    $new_width = min($width, $max_width);
    $new_height = ($height / $width) * $new_width;

    $tmp = imagecreatetruecolor($new_width, $new_height);

    // Preserve transparency for PNGs if we were not converting to JPG
    // But since we convert to JPG, we skip that.

    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Save as JPG
    imagejpeg($tmp, $target_file, 85);

    imagedestroy($src);
    imagedestroy($tmp);

    return $filename;
}
