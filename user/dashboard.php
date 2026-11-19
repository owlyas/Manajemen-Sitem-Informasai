<?php
/**
 * Bloom&Books - User / Member Dashboard
 * Halaman personal pembeli: Ringkasan Aktivitas, Edit Profil, Alamat Pengiriman, dan Keamanan.
 */

require_once __DIR__ . '/../config/database.php';

// Proteksi Halaman: Wajib Login
if (!isLoggedIn()) {
    setFlashMessage('error', 'Silakan masuk ke akun Anda terlebih dahulu.');
    header('Location: ../login.php');
    exit;
}

$currentUserSession = currentUser();
$userId = (int)$currentUserSession['id'];

// Selalu ambil data pengguna terbaru langsung dari database
$user = $currentUserSession;
if ($pdo !== null) {
    try {
        $uStmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $uStmt->execute([':id' => $userId]);
        $fetched = $uStmt->fetch();
        if ($fetched) {
            $user = $fetched;
            $_SESSION['user'] = $user;
        }
    } catch (PDOException $e) {}
}

// =========================================================
// 1. ACTION: UPDATE PROFIL
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nama = trim($_POST['nama'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $fotoProfil = trim($_POST['foto_profil'] ?? '');

    if (empty($nama)) {
        setFlashMessage('error', 'Nama lengkap wajib diisi.');
    } else {
        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nama = :nama, telepon = :telepon, foto_profil = :foto 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nama' => $nama,
                    ':telepon' => $telepon,
                    ':foto' => !empty($fotoProfil) ? $fotoProfil : $user['foto_profil'],
                    ':id' => $userId
                ]);

                // Update session
                $_SESSION['user']['nama'] = $nama;
                $_SESSION['user']['telepon'] = $telepon;
                if (!empty($fotoProfil)) {
                    $_SESSION['user']['foto_profil'] = $fotoProfil;
                }

                setFlashMessage('success', 'Profil Anda berhasil diperbarui!');
            } catch (PDOException $e) {
                setFlashMessage('error', 'Gagal memperbarui profil: ' . $e->getMessage());
            }
        } else {
            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['telepon'] = $telepon;
            setFlashMessage('success', 'Profil berhasil diperbarui (Mode Demonstrasi).');
        }
    }
    header('Location: dashboard.php?tab=profil');
    exit;
}

// =========================================================
// 2. ACTION: UPDATE ALAMAT PENGIRIMAN
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_address') {
    $alamat = trim($_POST['alamat'] ?? '');
    $kota = trim($_POST['kota'] ?? '');
    $kodePos = trim($_POST['kode_pos'] ?? '');

    if (empty($alamat)) {
        setFlashMessage('error', 'Alamat lengkap wajib diisi untuk tujuan pengiriman.');
    } else {
        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET alamat = :alamat, kota = :kota, kode_pos = :kode_pos 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':alamat' => $alamat,
                    ':kota' => $kota,
                    ':kode_pos' => $kodePos,
                    ':id' => $userId
                ]);

                // Update session
                $_SESSION['user']['alamat'] = $alamat;
                $_SESSION['user']['kota'] = $kota;
                $_SESSION['user']['kode_pos'] = $kodePos;

                setFlashMessage('success', 'Alamat pengiriman berhasil disimpan! Alamat ini akan otomatis digunakan saat checkout.');
            } catch (PDOException $e) {
                setFlashMessage('error', 'Gagal menyimpan alamat: ' . $e->getMessage());
            }
        } else {
            $_SESSION['user']['alamat'] = $alamat;
            $_SESSION['user']['kota'] = $kota;
            $_SESSION['user']['kode_pos'] = $kodePos;
            setFlashMessage('success', 'Alamat berhasil disimpan (Mode Demonstrasi).');
        }
    }
    header('Location: dashboard.php?tab=alamat');
    exit;
}

// =========================================================
// 3. ACTION: GANTI KATA SANDI
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass)) {
        setFlashMessage('error', 'Mohon lengkapi kata sandi saat ini dan kata sandi baru.');
    } elseif ($newPass !== $confirmPass) {
        setFlashMessage('error', 'Konfirmasi kata sandi baru tidak cocok.');
    } elseif (strlen($newPass) < 6) {
        setFlashMessage('error', 'Kata sandi baru minimal harus 6 karakter.');
    } else {
        if ($pdo !== null) {
            try {
                $pStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
                $pStmt->execute([':id' => $userId]);
                $hash = $pStmt->fetchColumn();

                if ($hash && password_verify($currentPass, $hash)) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    $upStmt = $pdo->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
                    $upStmt->execute([':h' => $newHash, ':id' => $userId]);
                    setFlashMessage('success', 'Kata sandi akun Anda berhasil diperbarui!');
                } else {
                    setFlashMessage('error', 'Kata sandi saat ini salah.');
                }
            } catch (PDOException $e) {
                setFlashMessage('error', 'Gagal mengubah kata sandi: ' . $e->getMessage());
            }
        } else {
            setFlashMessage('success', 'Kata sandi berhasil diubah (Mode Demonstrasi).');
        }
    }
    header('Location: dashboard.php?tab=keamanan');
    exit;
}

// Ambil data keranjang atau pesanan pengguna
$cartCount = 0;
$userOrders = [];

if ($pdo !== null) {
    try {
        $cartStmt = $pdo->prepare("SELECT SUM(qty) FROM cart WHERE user_id = :uid");
        $cartStmt->execute([':uid' => $userId]);
        $cartCount = (int)$cartStmt->fetchColumn();

        $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY order_date DESC LIMIT 10");
        $orderStmt->execute([':uid' => $userId]);
        $userOrders = $orderStmt->fetchAll();
    } catch (PDOException $e) {}
}

// Cek tab aktif dari URL param (?tab=profil / ?tab=alamat / ?tab=keamanan / default: ringkasan)
$activeTab = $_GET['tab'] ?? 'ringkasan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna - Bloom&Books</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600;1,700&family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#3A0CA3',
                            hover: '#2E0983',
                            light: '#4E12D4',
                            50: '#F4F0FD',
                        },
                        secondary: {
                            DEFAULT: '#F72585',
                            hover: '#D91A72',
                            light: '#FDE8F3',
                        },
                        surface: '#FAFAFE',
                    },
                    fontFamily: {
                        sans: ['"Inter"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        serif: ['"Inter"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        logo: ['"Cormorant Garamond"', '"Cinzel"', 'serif'],
                    },
                    boxShadow: {
                        'glass': '0 8px 32px 0 rgba(58, 12, 163, 0.08)',
                        'glass-lg': '0 16px 48px 0 rgba(58, 12, 163, 0.14)',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-surface text-slate-800 antialiased min-h-screen flex flex-col selection:bg-secondary selection:text-white">

    <!-- Ambient Glowing Orbs -->
    <div class="glow-blob-purple top-10 right-20"></div>
    <div class="glow-blob-magenta bottom-20 left-20"></div>

    <!-- Navigation Bar -->
    <header class="glass-nav sticky top-0 z-50">
        <div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto">
            <div class="flex items-center justify-between h-20">
                
                <a href="../index.php" class="flex items-center gap-3 group">
                    <div class="relative w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#3A0CA3] via-[#7209B7] to-[#F72585] p-[1.5px] shadow-sm group-hover:scale-105 transition-all">
                        <div class="w-full h-full rounded-[14px] bg-gradient-to-br from-[#1E085A] to-[#3A0CA3] flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 3.5C12 3.5 13.8 6.5 13.8 8.2C13.8 9.2 13 10 12 10C11 10 10.2 9.2 10.2 8.2C10.2 6.5 12 3.5 12 3.5Z" fill="#F72585"/>
                                <path d="M12 9C13.6 7 16.8 6.5 17.8 8C18.5 9.1 17.2 11.2 14.5 11.8C13.5 12 12.5 11.6 12 11" stroke="#FDE8F3" stroke-width="1.2" stroke-linecap="round"/>
                                <path d="M12 9C10.4 7 7.2 6.5 6.2 8C5.5 9.1 6.8 11.2 9.5 11.8C10.5 12 11.5 11.6 12 11" stroke="#FDE8F3" stroke-width="1.2" stroke-linecap="round"/>
                                <path d="M12 14.2V20" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
                                <path d="M12 14.5C9.8 13.2 6.5 13.2 3.6 14.7C3.3 14.9 3 15.2 3 15.6V19.2C3 19.7 3.5 20.1 4 19.9C6.8 18.7 9.8 18.8 12 20C14.2 18.8 17.2 18.7 20 19.9C20.5 20.1 21 19.7 21 19.2V15.6C21 15.2 20.7 14.9 20.4 14.7C17.5 13.2 14.2 13.2 12 14.5Z" stroke="white" stroke-width="1.3" stroke-linejoin="round"/>
                                <circle cx="12" cy="3.5" r="0.9" fill="#FFD166"/>
                            </svg>
                        </div>
                    </div>
                    <span class="font-logo text-xl font-bold tracking-tight text-slate-900 flex items-center">
                        <span>Bloom</span>
                        <span class="fancy-ampersand text-xl text-secondary mx-0.5 select-none font-normal italic">&amp;</span>
                        <span class="text-primary">Books</span>
                    </span>
                </a>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-6 text-xs font-semibold text-slate-600">
                    <a href="../index.php" class="hover:text-primary transition-colors">Beranda</a>
                    <a href="../index.php#katalog" class="hover:text-primary transition-colors">Katalog Buku</a>
                    <a href="../leaderboard.php" class="hover:text-primary transition-colors flex items-center gap-1">
                        <span>🏆 Leaderboard</span>
                    </a>
                    <a href="dashboard.php" class="text-primary font-bold">Dashboard Saya</a>
                    <a href="orders.php" class="hover:text-primary transition-colors">Riwayat Pesanan</a>
                </nav>

                <!-- Right Actions: Cart & Profile -->
                <div class="flex items-center gap-3">
                    <a href="../cart.php" class="relative p-2.5 rounded-full glass-card hover:border-primary/40 text-slate-700 hover:text-primary transition-all flex items-center justify-center shadow-sm" title="Keranjang Belanja">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        <?php if ($cartCount > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-secondary text-white text-[10px] font-extrabold flex items-center justify-center shadow-md animate-pulse">
                                <?= $cartCount ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <a href="../logout.php" class="px-3.5 py-1.5 rounded-full text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
                        Keluar
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto py-8 sm:py-10 flex-grow relative z-10 max-w-6xl">
        
        <!-- Flash Alert Messages -->
        <?php $flash = getFlashMessage(); if ($flash): ?>
            <div class="mb-6 p-4 rounded-2xl flex items-center gap-3 shadow-sm border <?= $flash['type'] === 'success' ? 'bg-emerald-50/90 border-emerald-200 text-emerald-800' : ($flash['type'] === 'error' ? 'bg-rose-50/90 border-rose-200 text-rose-800' : 'bg-primary/5 border-primary/20 text-primary') ?>">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 <?= $flash['type'] === 'success' ? 'bg-emerald-500 text-white' : ($flash['type'] === 'error' ? 'bg-rose-500 text-white' : 'bg-primary text-white') ?>">
                    <?= $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '✕' : 'ℹ') ?>
                </div>
                <p class="text-xs sm:text-sm font-semibold"><?= htmlspecialchars($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <!-- User Welcome Card -->
        <div class="glass-card rounded-3xl p-6 sm:p-8 mb-8 border border-white flex flex-col sm:flex-row items-center gap-6 shadow-glass relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-44 h-44 rounded-full bg-primary/5 blur-2xl pointer-events-none"></div>

            <!-- Avatar -->
            <div class="relative group shrink-0">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden bg-primary/10 flex items-center justify-center text-primary font-bold text-2xl sm:text-3xl ring-4 ring-white shadow-md">
                    <?php if (!empty($user['foto_profil'])): ?>
                        <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="switchTab('profil')" class="absolute bottom-0 right-0 p-1.5 rounded-full bg-primary text-white shadow-md hover:bg-primary-hover transition-colors" title="Ubah Foto">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </button>
            </div>

            <!-- User Info -->
            <div class="flex-grow text-center sm:text-left">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-1.5">
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900"><?= htmlspecialchars($user['nama']) ?></h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-secondary/10 text-secondary uppercase tracking-wider">
                        Member Pembaca
                    </span>
                    <?php if (!empty($user['telepon'])): ?>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 flex items-center gap-1">
                            <span>📞</span> <?= htmlspecialchars($user['telepon']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <?php if (!empty($user['alamat'])): ?>
                    <p class="text-xs text-slate-600 flex items-center justify-center sm:justify-start gap-1">
                        <span class="text-primary font-bold">📍</span>
                        <span class="truncate max-w-md"><?= htmlspecialchars($user['alamat'] . (!empty($user['kota']) ? ', ' . $user['kota'] : '') . (!empty($user['kode_pos']) ? ' ' . $user['kode_pos'] : '')) ?></span>
                    </p>
                <?php else: ?>
                    <p class="text-xs text-amber-600 flex items-center justify-center sm:justify-start gap-1 font-medium">
                        <span>⚠️</span> Alamat belum diisi. <button type="button" onclick="switchTab('alamat')" class="underline font-bold text-primary ml-1">Lengkapi sekarang</button>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Right CTA Action -->
            <div class="flex items-center gap-2">
                <a href="../index.php#katalog" class="px-5 py-2.5 rounded-full bg-gradient-to-r from-primary to-secondary text-white text-xs font-bold shadow-md hover:opacity-95 transition-all block text-center">
                    Eksplorasi Buku
                </a>
            </div>
        </div>

        <!-- Dashboard Navigation Tabs -->
        <div class="flex items-center gap-2 mb-8 overflow-x-auto pb-2 border-b border-slate-200/80">
            <button 
                type="button" 
                onclick="switchTab('ringkasan')" 
                id="tabBtn-ringkasan"
                class="tab-btn px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 <?= $activeTab === 'ringkasan' ? 'bg-primary text-white shadow-md shadow-primary/25' : 'glass-card text-slate-600 hover:text-primary hover:bg-white' ?>"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Ringkasan Aktivitas</span>
            </button>

            <button 
                type="button" 
                onclick="switchTab('profil')" 
                id="tabBtn-profil"
                class="tab-btn px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 <?= $activeTab === 'profil' ? 'bg-primary text-white shadow-md shadow-primary/25' : 'glass-card text-slate-600 hover:text-primary hover:bg-white' ?>"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Edit Profil</span>
            </button>

            <button 
                type="button" 
                onclick="switchTab('alamat')" 
                id="tabBtn-alamat"
                class="tab-btn px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 <?= $activeTab === 'alamat' ? 'bg-primary text-white shadow-md shadow-primary/25' : 'glass-card text-slate-600 hover:text-primary hover:bg-white' ?>"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Buku Alamat Pengiriman</span>
            </button>

            <button 
                type="button" 
                onclick="switchTab('keamanan')" 
                id="tabBtn-keamanan"
                class="tab-btn px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 <?= $activeTab === 'keamanan' ? 'bg-primary text-white shadow-md shadow-primary/25' : 'glass-card text-slate-600 hover:text-primary hover:bg-white' ?>"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Keamanan &amp; Sandi</span>
            </button>
        </div>

        <!-- =========================================================
             TAB 1: RINGKASAN AKTIVITAS
             ========================================================= -->
        <div id="tabContent-ringkasan" class="tab-pane <?= $activeTab === 'ringkasan' ? '' : 'hidden' ?> space-y-8">
            <!-- Quick Activity Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="../cart.php" class="glass-card rounded-2xl p-6 border border-white hover:border-primary/40 transition-all block group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider group-hover:text-primary transition-colors">Keranjang Belanja</span>
                        <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full">Aktif</span>
                    </div>
                    <p class="font-serif text-3xl font-bold text-slate-900"><?= $cartCount ?> Buku</p>
                    <p class="text-xs text-slate-500 mt-2">Buka keranjang untuk menyelesaikan pesanan &rarr;</p>
                </a>

                <a href="orders.php" class="glass-card rounded-2xl p-6 border border-white hover:border-secondary/40 transition-all block group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider group-hover:text-secondary transition-colors">Total Pesanan</span>
                        <span class="text-xs font-bold text-secondary bg-secondary/10 px-2 py-0.5 rounded-full">Riwayat</span>
                    </div>
                    <p class="font-serif text-3xl font-bold text-slate-900"><?= count($userOrders) ?> Pesanan</p>
                    <p class="text-xs text-slate-500 mt-2">Lihat status &amp; rincian paket buku Anda &rarr;</p>
                </a>

                <div class="glass-card rounded-2xl p-6 border border-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kupon Promo</span>
                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Tersedia</span>
                    </div>
                    <p class="font-serif text-3xl font-bold text-emerald-600">Diskon 15%</p>
                    <p class="text-xs text-slate-500 mt-2">Gunakan kode <strong class="text-primary font-mono">BLOOMFIRST</strong></p>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="glass-card rounded-3xl p-6 sm:p-8 border border-white shadow-glass">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="font-serif text-xl font-bold text-slate-900">Riwayat Pesanan Terkini</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Status verifikasi pembayaran otomatis via Xendit dan pengiriman buku langsung ke alamat Anda.</p>
                    </div>
                    <a href="orders.php" class="text-xs font-bold text-primary hover:text-primary-hover flex items-center gap-1">
                        <span>Lihat Semua Pesanan</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>

                <?php if (!empty($userOrders)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 uppercase text-[10px] tracking-wider text-slate-400">
                                <tr>
                                    <th class="p-3">ID Pesanan</th>
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Total Tagihan</th>
                                    <th class="p-3">Alamat Tujuan</th>
                                    <th class="p-3">Status Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($userOrders as $ord): 
                                    $st = strtolower($ord['status_pembayaran'] ?? 'pending');
                                    $badgeColor = ($st === 'paid' || $st === 'completed') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (($st === 'cancelled' || $st === 'expired') ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200');
                                ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="p-3 font-mono font-bold text-primary">#ORD-<?= $ord['id'] ?></td>
                                        <td class="p-3"><?= htmlspecialchars($ord['order_date']) ?></td>
                                        <td class="p-3 font-semibold text-slate-800"><?= formatRupiah($ord['total_harga']) ?></td>
                                        <td class="p-3 text-slate-500 truncate max-w-xs" title="<?= htmlspecialchars($ord['alamat_pengiriman']) ?>"><?= htmlspecialchars($ord['alamat_pengiriman']) ?></td>
                                        <td class="p-3">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $badgeColor ?>">
                                                <?= ucfirst($st) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center bg-slate-50/50 rounded-2xl border border-slate-100">
                        <p class="text-xs text-slate-500 mb-4">Anda belum memiliki riwayat pesanan. Mulai jelajahi ribuan buku favorit Anda!</p>
                        <a href="../index.php#katalog" class="px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-semibold hover:bg-primary-hover transition-colors inline-block shadow-sm">
                            Lihat Katalog Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- =========================================================
             TAB 2: EDIT PROFIL
             ========================================================= -->
        <div id="tabContent-profil" class="tab-pane <?= $activeTab === 'profil' ? '' : 'hidden' ?>">
            <div class="glass-card rounded-3xl p-6 sm:p-8 border border-white shadow-glass max-w-2xl mx-auto">
                <div class="mb-6 pb-4 border-b border-slate-100">
                    <h2 class="font-serif text-xl font-bold text-slate-900">Perbarui Profil Anda</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola informasi nama lengkap, kontak nomor telepon/WhatsApp, dan foto profil akun.</p>
                </div>

                <form action="dashboard.php" method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="update_profile">

                    <!-- Nama Lengkap -->
                    <div>
                        <label for="nama" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Nama Lengkap <span class="text-secondary">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="nama" 
                            name="nama" 
                            required 
                            value="<?= htmlspecialchars($user['nama']) ?>" 
                            class="glass-input w-full p-3 rounded-xl text-xs sm:text-sm text-slate-800 outline-none"
                            placeholder="Tuliskan nama lengkap Anda..."
                        >
                    </div>

                    <!-- Email (Readonly) -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Alamat Email (Akun Utama)
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            readonly 
                            value="<?= htmlspecialchars($user['email']) ?>" 
                            class="w-full p-3 rounded-xl text-xs sm:text-sm text-slate-500 bg-slate-100 border border-slate-200 outline-none cursor-not-allowed"
                            title="Alamat email tidak dapat diubah"
                        >
                        <span class="text-[10px] text-slate-400 mt-1 block">Email digunakan sebagai identitas akun dan konfirmasi pesanan Xendit.</span>
                    </div>

                    <!-- Nomor Telepon / WhatsApp -->
                    <div>
                        <label for="telepon" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Nomor WhatsApp / Telepon
                        </label>
                        <input 
                            type="text" 
                            id="telepon" 
                            name="telepon" 
                            value="<?= htmlspecialchars($user['telepon'] ?? '') ?>" 
                            class="glass-input w-full p-3 rounded-xl text-xs sm:text-sm text-slate-800 outline-none"
                            placeholder="Contoh: 081234567890 (Digunakan kurir pengiriman)"
                        >
                    </div>

                    <!-- Foto Profil URL -->
                    <div>
                        <label for="foto_profil" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Tautan URL Foto Profil
                        </label>
                        <input 
                            type="url" 
                            id="foto_profil" 
                            name="foto_profil" 
                            value="<?= htmlspecialchars($user['foto_profil'] ?? '') ?>" 
                            class="glass-input w-full p-3 rounded-xl text-xs sm:text-sm text-slate-800 outline-none"
                            placeholder="https://images.unsplash.com/photo-..."
                        >
                        <p class="text-[10px] text-slate-400 mt-1">Masukkan tautan gambar profil Anda atau pilih dari avatar cepat di bawah.</p>
                        
                        <!-- Pilihan Avatar Cepat -->
                        <div class="mt-3 flex items-center gap-3">
                            <span class="text-[11px] font-bold text-slate-500">Avatar Cepat:</span>
                            <?php 
                            $presetAvatars = [
                                'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
                                'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
                                'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
                                'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80'
                            ];
                            foreach ($presetAvatars as $pAv):
                            ?>
                                <img 
                                    src="<?= $pAv ?>" 
                                    alt="Preset" 
                                    class="w-8 h-8 rounded-full object-cover border-2 border-white hover:border-primary cursor-pointer hover:scale-110 transition-transform shadow-sm"
                                    onclick="document.getElementById('foto_profil').value='<?= $pAv ?>';"
                                    title="Pilih avatar ini"
                                >
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            onclick="switchTab('ringkasan')"
                            class="px-5 py-2.5 rounded-xl glass-card text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-md shadow-primary/20 transition-all"
                        >
                            Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =========================================================
             TAB 3: BUKU ALAMAT PENGIRIMAN
             ========================================================= -->
        <div id="tabContent-alamat" class="tab-pane <?= $activeTab === 'alamat' ? '' : 'hidden' ?>">
            <div class="glass-card rounded-3xl p-6 sm:p-8 border border-white shadow-glass max-w-2xl mx-auto">
                <div class="mb-6 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h2 class="font-serif text-xl font-bold text-slate-900">Alamat Pengiriman Utama</h2>
                    </div>
                    <p class="text-xs text-slate-500">Alamat ini akan otomatis digunakan saat Anda menyelesaikan pesanan di keranjang belanja.</p>
                </div>

                <form action="dashboard.php" method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="update_address">

                    <!-- Alamat Lengkap -->
                    <div>
                        <label for="alamat" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Alamat Lengkap (Nama Jalan, Nomor Rumah, RT/RW, Kelurahan, Kecamatan) <span class="text-secondary">*</span>
                        </label>
                        <textarea 
                            id="alamat" 
                            name="alamat" 
                            required 
                            rows="3" 
                            class="glass-input w-full p-3 rounded-xl text-xs sm:text-sm text-slate-800 outline-none leading-relaxed"
                            placeholder="Contoh: Jl. Menteng Raya No. 45, RT 02 / RW 04, Kel. Kebon Sirih, Kec. Gambir..."
                        ><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                    </div>

                    <!-- Kota & Kode Pos Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="kota" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Kota / Kabupaten
                            </label>
                            <input 
                                type="text" 
                                id="kota" 
                                name="kota" 
                                value="<?= htmlspecialchars($user['kota'] ?? '') ?>" 
                                class="glass-input w-full p-3 rounded-xl text-xs sm:text-sm text-slate-800 outline-none"
                                placeholder="Contoh: Jakarta Pusat"
                            >
                        </div>

                        <div>
                            <label for="kode_pos" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Kode Pos
                            </label>
                            <input 
                                type="text" 
                                id="kode_pos" 
                                name="kode_pos" 
                                value="<?= htmlspecialchars($user['kode_pos'] ?? '') ?>" 
                                class="glass-input w-full p-3 rounded-xl text-xs sm:text-sm text-slate-800 outline-none"
                                placeholder="Contoh: 10110"
                            >
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="p-4 rounded-2xl bg-primary/5 border border-primary/20 flex items-start gap-3">
                        <div class="text-primary text-lg mt-0.5">📦</div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Pengiriman Aman &amp; Terlacak</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Pastikan nomor telepon/WhatsApp Anda sudah benar agar kurir ekspedisi dapat menghubungi Anda dengan lancar saat paket tiba.</p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            onclick="switchTab('ringkasan')"
                            class="px-5 py-2.5 rounded-xl glass-card text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-md shadow-primary/20 transition-all"
                        >
                            Simpan Alamat Pengiriman
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =========================================================
             TAB 4: KEAMANAN & SANDI
             ========================================================= -->
        <div id="tabContent-keamanan" class="tab-pane <?= $activeTab === 'keamanan' ? '' : 'hidden' ?>">
            <div class="glass-card rounded-3xl p-6 sm:p-8 border border-white shadow-glass max-w-xl mx-auto">
                <div class="mb-6 pb-4 border-b border-slate-100">
                    <h2 class="font-serif text-xl font-bold text-slate-900">Ubah Kata Sandi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Amankan akun Anda secara berkala dengan kata sandi yang kuat.</p>
                </div>

                <form action="dashboard.php" method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Kata Sandi Saat Ini <span class="text-secondary">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            required 
                            class="glass-input w-full p-3 rounded-xl text-xs sm:text-sm text-slate-800 outline-none"
                            placeholder="Masukkan kata sandi saat ini..."
                        >
                    </div>

                    <div>
                        <label for="new_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Kata Sandi Baru <span class="text-secondary">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            required 
                            minlength="6"
                            class="glass-input w-full p-3 rounded-xl text-xs sm:text-sm text-slate-800 outline-none"
                            placeholder="Minimal 6 karakter kombinasi..."
                        >
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Konfirmasi Kata Sandi Baru <span class="text-secondary">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                            minlength="6"
                            class="glass-input w-full p-3 rounded-xl text-xs sm:text-sm text-slate-800 outline-none"
                            placeholder="Ulangi kata sandi baru..."
                        >
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            onclick="switchTab('ringkasan')"
                            class="px-5 py-2.5 rounded-xl glass-card text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-bold shadow-md shadow-primary/20 transition-all"
                        >
                            Ubah Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <footer class="py-6 border-t border-slate-200/60 text-center text-xs text-slate-400">
        &copy; <?= date('Y') ?> Bloom&amp;Books. Teman Literasi Anda.
    </footer>

    <!-- Floating Chatbot Assistant (BloomBot) -->
    <?php require_once __DIR__ . '/../includes/chatbot.php'; ?>

    <!-- Tab Switching Script -->
    <script>
        function switchTab(tabName) {
            // Update pane visibility
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.add('hidden');
            });
            const targetPane = document.getElementById('tabContent-' + tabName);
            if (targetPane) {
                targetPane.classList.remove('hidden');
            }

            // Update button styles
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = 'tab-btn px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 glass-card text-slate-600 hover:text-primary hover:bg-white';
            });
            const activeBtn = document.getElementById('tabBtn-' + tabName);
            if (activeBtn) {
                activeBtn.className = 'tab-btn px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 bg-primary text-white shadow-md shadow-primary/25';
            }

            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        }

        // Handle direct load with hash/query
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab && ['ringkasan', 'profil', 'alamat', 'keamanan'].includes(tab)) {
                switchTab(tab);
            }
        });
    </script>
</body>
</html>
