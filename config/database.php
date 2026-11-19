<?php
/**
 * Bloom&Books - Database Connection & Global Configuration
 * Menggunakan PHP PDO (PHP Data Objects) untuk koneksi aman & terstruktur.
 */

// ─── Load .env variables (plain PHP parser, no dependency required) ───────────
if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $path): void {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            // Strip surrounding quotes
            if (strlen($value) >= 2 && (
                ($value[0] === '"'  && $value[-1] === '"') ||
                ($value[0] === "'"  && $value[-1] === "'")
            )) {
                $value = substr($value, 1, -1);
            }
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}
loadEnvFile(__DIR__ . '/../.env');
// ──────────────────────────────────────────────────────────────────────────────

// Mulai sesi aplikasi jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Kredensial Database (Default XAMPP / MySQL)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'db_ecommerce');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// URL Base helper (opsional jika dibutuhkan path relatif dinamis)
define('BASE_URL', '/');

/**
 * Mendapatkan instance koneksi PDO
 * @return PDO|null
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Simpan log error secara internal
        error_log("Database Connection Error: " . $e->getMessage());
        return null;
    }
}

// Inisialisasi variabel $pdo global untuk kemudahan akses
$pdo = getDBConnection();

/**
 * Format angka ke format Rupiah standar Indonesia
 * @param float|int $amount
 * @return string
 */
function formatRupiah($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Cek status login pengguna
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Cek apakah pengguna adalah admin
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Dapatkan data sesi pengguna saat ini
 * @return array|null
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'          => $_SESSION['user_id'],
        'nama'        => $_SESSION['nama'] ?? 'Pengguna',
        'email'       => $_SESSION['email'] ?? '',
        'role'        => $_SESSION['role'] ?? 'user',
        'foto_profil' => $_SESSION['foto_profil'] ?? null
    ];
}

/**
 * Sanitasi string input untuk keamanan XSS
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message ke sesi
 * @param string $type 'success' | 'error' | 'info' | 'warning'
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Ambil dan hapus flash message dari sesi
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Hitung total kuantitas barang dalam keranjang pengguna saat ini
 * @return int
 */
function getCartCount() {
    if (!isLoggedIn()) {
        return 0;
    }
    $pdo = getDBConnection();
    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("SELECT SUM(qty) FROM cart WHERE user_id = :uid");
            $stmt->execute([':uid' => $_SESSION['user_id']]);
            return (int)($stmt->fetchColumn() ?: 0);
        } catch (PDOException $e) {
            return 0;
        }
    }
    return (int)($_SESSION['cart_count'] ?? 0);
}

// Return konfigurasi database standar agar kompatibel jika framework Laravel/artisan dijalankan
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => DB_HOST,
            'port' => DB_PORT,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS,
            'charset' => DB_CHARSET,
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
];
