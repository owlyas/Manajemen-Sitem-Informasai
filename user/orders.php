<?php
/**
 * Bloom&Books - Halaman Riwayat Pesanan Customer (Member Area)
 * Fitur: Menampilkan daftar transaksi yang telah dibuat pengguna, detail item buku yang dibeli, dan status pembayaran.
 */

require_once __DIR__ . '/../config/database.php';

// Proteksi Halaman: Wajib Login
if (!isLoggedIn()) {
    setFlashMessage('error', 'Silakan masuk ke akun Anda terlebih dahulu.');
    header('Location: ../login.php');
    exit;
}

$user = currentUser();
$userId = (int)$user['id'];

// Ambil seluruh pesanan milik pengguna yang sedang login
$orders = [];
if ($pdo !== null) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.* 
            FROM orders o 
            WHERE o.user_id = :uid 
            ORDER BY o.id DESC
        ");
        $stmt->execute([':uid' => $userId]);
        $orders = $stmt->fetchAll();

        // Ambil item untuk setiap order
        foreach ($orders as &$ord) {
            $itemStmt = $pdo->prepare("
                SELECT oi.*, b.judul, b.penulis, b.cover_image, cat.nama_kategori 
                FROM order_items oi 
                JOIN books b ON oi.book_id = b.id 
                LEFT JOIN categories cat ON b.category_id = cat.id 
                WHERE oi.order_id = :oid
            ");
            $itemStmt->execute([':oid' => $ord['id']]);
            $ord['items'] = $itemStmt->fetchAll();
        }
        unset($ord);
    } catch (PDOException $e) {}
}

$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan Saya - Bloom&Books</title>
    
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

    <!-- Ambient Glowing Blobs -->
    <div class="glow-blob-purple top-10 right-20"></div>
    <div class="glow-blob-magenta bottom-20 left-20"></div>

    <!-- Top User Navigation Header -->
    <header class="glass-nav sticky top-0 z-50">
        <div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto">
            <div class="flex items-center justify-between h-20">
                
                <!-- Logo -->
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
                    <a href="dashboard.php" class="hover:text-primary transition-colors">Dashboard Saya</a>
                    <a href="orders.php" class="text-primary font-bold">Riwayat Pesanan</a>
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

                    <!-- User Pill -->
                    <div class="flex items-center gap-2 p-1.5 pl-3 pr-2 rounded-full glass-card">
                        <span class="text-xs font-semibold text-slate-700 max-w-[100px] truncate">
                            <?= htmlspecialchars($user['nama']) ?>
                        </span>
                        <div class="w-7 h-7 rounded-full overflow-hidden bg-primary/10 flex items-center justify-center text-primary font-bold text-xs ring-2 ring-white">
                            <?php if (!empty($user['foto_profil'])): ?>
                                <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="../logout.php" class="p-2 text-slate-400 hover:text-rose-600 transition-colors" title="Keluar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto py-10 flex-grow relative z-10">
        
        <!-- Page Title & Overview Banner -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-6 border-b border-slate-200/80 gap-4 mb-8">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-secondary block mb-1">Aktivitas Belanja</span>
                <h1 class="font-serif text-3xl font-bold text-slate-900">Riwayat Pesanan Anda</h1>
                <p class="text-xs text-slate-500 mt-1">Lacak seluruh paket buku yang pernah Anda pesan di Bloom&Books.</p>
            </div>

            <a href="../index.php#katalog" class="px-5 py-2.5 rounded-full bg-gradient-to-r from-primary to-secondary text-white font-bold text-xs shadow-md hover:opacity-95 transition-all flex items-center gap-2">
                <span>+ Belanja Buku Baru</span>
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="glass-card rounded-3xl p-12 text-center max-w-md mx-auto my-12 border border-white shadow-glass">
                <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-slate-900 mb-2">Belum Ada Riwayat Pesanan</h3>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">Anda belum pernah menyelesaikan pesanan buku. Mulai temukan bacaan favorit Anda sekarang!</p>
                <a href="../index.php#katalog" class="px-6 py-2.5 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-hover transition-colors shadow-sm inline-block">
                    Jelajahi Katalog Buku
                </a>
            </div>
        <?php else: ?>
            <!-- List Orders -->
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="glass-card rounded-3xl p-6 sm:p-7 border border-white/90 shadow-glass-sm transition-all hover:border-primary/20">
                        
                        <!-- Order Card Header -->
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-5 border-b border-slate-100 gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="font-mono font-bold text-primary text-sm sm:text-base">
                                    #ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>
                                </span>
                                <span class="text-xs text-slate-400">&bull;</span>
                                <span class="text-xs text-slate-500">
                                    <?= date('d F Y, H:i', strtotime($order['order_date'])) ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <?php
                                $st = $order['status_pembayaran'];
                                $badgeClass = match($st) {
                                    'paid', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $badgeClass ?>">
                                    <?= ucfirst($st) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Ordered Items List -->
                        <div class="py-5 space-y-4">
                            <?php if (!empty($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-16 rounded-xl overflow-hidden bg-white shadow-sm shrink-0 border border-slate-200/60 flex items-center justify-center p-0.5">
                                                <img src="<?= htmlspecialchars($item['cover_image']) ?>" alt="cover" class="max-h-full max-w-full object-contain">
                                            </div>
                                            <div>
                                                <span class="text-[10px] font-extrabold uppercase text-secondary">
                                                    <?= htmlspecialchars($item['nama_kategori'] ?? 'Koleksi') ?>
                                                </span>
                                                <h4 class="font-bold text-xs sm:text-sm text-slate-800 line-clamp-1">
                                                    <?= htmlspecialchars($item['judul']) ?>
                                                </h4>
                                                <p class="text-[11px] text-slate-400">
                                                    <?= (int)$item['qty'] ?> x <?= formatRupiah($item['harga_satuan']) ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="text-right whitespace-nowrap">
                                            <span class="font-extrabold text-xs sm:text-sm text-slate-900">
                                                <?= formatRupiah($item['qty'] * $item['harga_satuan']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Card Footer -->
                        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                            <div class="text-slate-500 max-w-md truncate">
                                <span class="font-bold text-slate-700">Tujuan:</span>
                                <span class="truncate" title="<?= htmlspecialchars($order['alamat_pengiriman']) ?>"><?= htmlspecialchars($order['alamat_pengiriman']) ?></span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-slate-400 uppercase font-bold text-[10px]">Total Tagihan:</span>
                                <span class="text-base font-extrabold text-primary">
                                    <?= formatRupiah($order['total_harga']) ?>
                                </span>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="py-6 border-t border-slate-200/60 text-center text-xs text-slate-400 relative z-10">
        &copy; <?= date('Y') ?> Bloom&Books. Teman Literasi Terpercaya Anda.
    </footer>

    <!-- Floating Chatbot Assistant (BloomBot) -->
    <?php require_once __DIR__ . '/../includes/chatbot.php'; ?>

</body>
</html>
