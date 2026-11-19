<?php
/**
 * Bloom&Books - Admin Authentication & Authorization Middleware
 * Memastikan hanya akun dengan status login dan role 'admin' yang dapat mengakses.
 */

require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Silakan masuk ke akun Administrator Anda terlebih dahulu.');
    header('Location: ../login.php');
    exit;
}

if (!isAdmin()) {
    setFlashMessage('error', 'Akses ditolak! Halaman ini hanya dapat diakses oleh Administrator.');
    header('Location: ../user/dashboard.php');
    exit;
}

$admin = currentUser();
