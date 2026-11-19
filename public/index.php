<?php
/**
 * Bloom&Books - Public Entry Point & Development Server Bridge
 * File ini menangani request saat aplikasi dijalankan melalui:
 * 1. 'php artisan serve'
 * 2. PHP Built-in Server pada folder public/
 * 3. XAMPP VirtualHost yang mengarah ke public/
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$cleanUri = trim($uri, '/');

// Routing sederhana untuk memetakan request ke file PHP Native Bloom&Books
switch ($cleanUri) {
    case '':
    case 'index.php':
        require __DIR__ . '/../index.php';
        exit;

    case 'login':
    case 'login.php':
        require __DIR__ . '/../login.php';
        exit;

    case 'register':
    case 'register.php':
        require __DIR__ . '/../register.php';
        exit;

    case 'logout':
    case 'logout.php':
        require __DIR__ . '/../logout.php';
        exit;

    case 'cart':
    case 'cart.php':
        require __DIR__ . '/../cart.php';
        exit;

    case 'admin':
    case 'admin/':
    case 'admin/dashboard':
    case 'admin/dashboard.php':
        require __DIR__ . '/../admin/dashboard.php';
        exit;

    case 'admin/books':
    case 'admin/books.php':
        require __DIR__ . '/../admin/books.php';
        exit;

    case 'admin/categories':
    case 'admin/categories.php':
        require __DIR__ . '/../admin/categories.php';
        exit;

    case 'admin/orders':
    case 'admin/orders.php':
        require __DIR__ . '/../admin/orders.php';
        exit;

    case 'admin/users':
    case 'admin/users.php':
        require __DIR__ . '/../admin/users.php';
        exit;

    case 'admin/settings':
    case 'admin/settings.php':
        require __DIR__ . '/../admin/settings.php';
        exit;

    case 'user':
    case 'user/':
    case 'user/dashboard':
    case 'user/dashboard.php':
        require __DIR__ . '/../user/dashboard.php';
        exit;

    case 'user/orders':
    case 'user/orders.php':
        require __DIR__ . '/../user/orders.php';
        exit;

    case 'import_db':
    case 'import_db.php':
        require __DIR__ . '/../import_db.php';
        exit;
}

// Cek apakah ada file fisik di root project (misalnya file asset atau skrip tambahan)
$projectRoot = dirname(__DIR__);
$targetFile = realpath($projectRoot . '/' . $cleanUri);

if ($targetFile && str_starts_with($targetFile, $projectRoot) && is_file($targetFile)) {
    $extension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    if ($extension === 'php') {
        require $targetFile;
        exit;
    }

    // Penanganan MIME type untuk aset statis
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
    ];

    $mime = $mimeTypes[$extension] ?? 'application/octet-stream';
    header("Content-Type: " . $mime);
    readfile($targetFile);
    exit;
}

// Default fallback jika tidak ada yang cocok
require __DIR__ . '/../index.php';
