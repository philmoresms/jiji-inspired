<?php
/**
 * Jiji-Inspired-1.0 Common Functions
 */

// Check if schema needs update (migration logic)
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/update_schema.php';
}

// Initialize project directories
$required_dirs = [
    __DIR__ . '/../config',
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/ads',
    __DIR__ . '/../uploads/proofs',
    __DIR__ . '/../uploads/blog'
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

    // Save as JPG with higher compression (70 instead of 85)
    imagejpeg($tmp, $target_file, 70);

    imagedestroy($src);
    imagedestroy($tmp);

    return $filename;
}

/**
 * Generate SEO friendly Ad URL
 */
function generate_ad_url($ad) {
    $title = $ad['title'] ?? 'ad';
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    $state_name = $ad['state_name'] ?? 'nigeria';
    $state = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $state_name)));

    $cat_name = $ad['cat_name'] ?? 'others';
    $cat = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cat_name)));

    // Ensure we don't have empty segments
    $state = $state ?: 'nigeria';
    $cat = $cat ?: 'others';

    return "/$state/$cat/$slug-{$ad['id']}";
}

/**
 * Extract SEO Keywords from text
 */
function extract_keywords($text, $additional = "") {
    $premium_words = ['buy', 'sell', 'cheap', 'best', 'nigeria', 'price', 'new', 'used', 'deals', 'marketplace', 'classifieds', 'online shop'];
    $text = strtolower($text . " " . $additional);
    $text = preg_replace('/[^a-z0-9\s]/', '', $text);
    $words = explode(' ', $text);

    // Filter out short and common words
    $common = ['the', 'and', 'with', 'for', 'this', 'that', 'your', 'from', 'have', 'more', 'about'];
    $filtered = array_filter($words, function($w) use ($common) {
        return strlen($w) > 3 && !in_array($w, $common);
    });

    // Prioritize premium words
    $keywords = array_unique(array_merge($premium_words, $filtered));
    return implode(', ', array_slice($keywords, 0, 15));
}

/**
 * Generate Auto SEO Meta tags for any page
 */
function generate_meta_tags($title, $description, $tags = "") {
    $meta_title = h($title);
    // Limit description to 160 characters for SEO
    $meta_desc = h(substr(strip_tags($description), 0, 160));
    $meta_keywords = h(extract_keywords($title, $tags));

    return [
        'title' => $meta_title,
        'description' => $meta_desc,
        'keywords' => $meta_keywords
    ];
}
