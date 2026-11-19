<?php
/**
 * Bloom&Books - Keranjang Belanja & Alur Checkout Dummy
 * Fitur: Kelola kuantitas buku, kalkulasi subtotal, hapus item, dan simulasi pembayaran instan (Dummy Payment Flow).
 */

require_once __DIR__ . '/config/database.php';

// Proteksi Halaman: Wajib Login
if (!isLoggedIn()) {
    setFlashMessage('info', 'Silakan masuk ke akun Anda terlebih dahulu untuk mengakses keranjang belanja.');
    header('Location: login.php');
    exit;
}

$user = currentUser();
$userId = (int)$user['id'];

// Ambil data profil terbaru pengguna (alamat & telepon)
if ($pdo !== null) {
    try {
        $uStmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $uStmt->execute([':id' => $userId]);
        $freshUser = $uStmt->fetch();
        if ($freshUser) {
            $user = $freshUser;
            $_SESSION['user'] = $user;
        }
    } catch (PDOException $e) {}
}

$dummyPaymentSuccess = null;

// =========================================================
// 1. ACTION: TAMBAH KE KERANJANG (ADD TO CART)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $bookId = (int)($_POST['book_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    if ($bookId > 0 && $pdo !== null) {
        try {
            // Cek apakah buku sudah ada di keranjang
            $checkStmt = $pdo->prepare("SELECT id, qty FROM cart WHERE user_id = :uid AND book_id = :bid LIMIT 1");
            $checkStmt->execute([':uid' => $userId, ':bid' => $bookId]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                $newQty = $existing['qty'] + $qty;
                $upStmt = $pdo->prepare("UPDATE cart SET qty = :qty WHERE id = :id");
                $upStmt->execute([':qty' => $newQty, ':id' => $existing['id']]);
            } else {
                $insStmt = $pdo->prepare("INSERT INTO cart (user_id, book_id, qty) VALUES (:uid, :bid, :qty)");
                $insStmt->execute([':uid' => $userId, ':bid' => $bookId, ':qty' => $qty]);
            }

            setFlashMessage('success', "Buku berhasil dimasukkan ke keranjang belanja Anda!");
        } catch (PDOException $e) {
            setFlashMessage('error', "Gagal menambahkan ke keranjang: " . $e->getMessage());
        }
    }
    
    // Redirect ke keranjang atau kembali
    $redirectUrl = isset($_POST['redirect']) ? $_POST['redirect'] : 'cart.php';
    header("Location: {$redirectUrl}");
    exit;
}

// =========================================================
// 2. ACTION: UBAH JUMLAH / QUANTITY (UPDATE QTY)
// =========================================================
if (isset($_GET['action']) && $_GET['action'] === 'qty') {
    $cartId = (int)($_GET['id'] ?? 0);
    $delta = (int)($_GET['delta'] ?? 0);

    if ($cartId > 0 && $delta !== 0 && $pdo !== null) {
        try {
            $stmt = $pdo->prepare("SELECT id, qty FROM cart WHERE id = :id AND user_id = :uid LIMIT 1");
            $stmt->execute([':id' => $cartId, ':uid' => $userId]);
            $item = $stmt->fetch();

            if ($item) {
                $newQty = $item['qty'] + $delta;
                if ($newQty <= 0) {
                    $del = $pdo->prepare("DELETE FROM cart WHERE id = :id");
                    $del->execute([':id' => $cartId]);
                } else {
                    $up = $pdo->prepare("UPDATE cart SET qty = :qty WHERE id = :id");
                    $up->execute([':qty' => $newQty, ':id' => $cartId]);
                }
            }
        } catch (PDOException $e) {}
    }
    header('Location: cart.php');
    exit;
}

// =========================================================
// 3. ACTION: HAPUS DARI KERANJANG (REMOVE ITEM)
// =========================================================
if (isset($_GET['action']) && $_GET['action'] === 'remove') {
    $cartId = (int)($_GET['id'] ?? 0);
    if ($cartId > 0 && $pdo !== null) {
        try {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $cartId, ':uid' => $userId]);
            setFlashMessage('success', "Item telah dihapus dari keranjang belanja.");
        } catch (PDOException $e) {}
    }
    header('Location: cart.php');
    exit;
}

// =========================================================
// 4. ACTION: KOSONGKAN KERANJANG (CLEAR CART)
// =========================================================
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            setFlashMessage('info', "Keranjang belanja telah dikosongkan.");
        } catch (PDOException $e) {}
    }
    header('Location: cart.php');
    exit;
}

// =========================================================
// 5. READ: AMBIL ITEM KERANJANG PENGGUNA
// =========================================================
$cartItems = [];
$subtotal = 0;

if ($pdo !== null) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.id as cart_id, c.qty, b.id as book_id, b.judul, b.penulis, b.harga, b.stok, b.cover_image, cat.nama_kategori
            FROM cart c 
            JOIN books b ON c.book_id = b.id 
            LEFT JOIN categories cat ON b.category_id = cat.id 
            WHERE c.user_id = :uid 
            ORDER BY c.id DESC
        ");
        $stmt->execute([':uid' => $userId]);
        $cartItems = $stmt->fetchAll();

        foreach ($cartItems as $ci) {
            $subtotal += ($ci['harga'] * $ci['qty']);
        }
    } catch (PDOException $e) {}
}

// =========================================================
// 6. ACTION: CHECKOUT - MIDTRANS + FALLBACK DUMMY PAYMENT
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $alamat = trim($_POST['alamat_pengiriman'] ?? '');

    if (empty($alamat)) {
        setFlashMessage('error', 'Mohon lengkapi alamat pengiriman buku sebelum melakukan pembayaran.');
    } elseif (empty($cartItems)) {
        setFlashMessage('error', 'Keranjang belanja Anda masih kosong.');
    } else {
        if ($pdo !== null) {
            try {
                $pdo->beginTransaction();

                // Insert order dengan status pending
                $orderStmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_harga, status_pembayaran, alamat_pengiriman) 
                    VALUES (:uid, :total, 'pending', :alamat)
                ");
                $orderStmt->execute([
                    ':uid'    => $userId,
                    ':total'  => $subtotal,
                    ':alamat' => $alamat
                ]);
                $newOrderId = (int)$pdo->lastInsertId();

                // Insert detail item pesanan
                $itemStmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, book_id, qty, harga_satuan) 
                    VALUES (:oid, :bid, :qty, :harga)
                ");
                foreach ($cartItems as $item) {
                    $itemStmt->execute([
                        ':oid'   => $newOrderId,
                        ':bid'   => $item['book_id'],
                        ':qty'   => $item['qty'],
                        ':harga' => $item['harga']
                    ]);
                }

                // --- Panggil Midtrans Snap API ---
                $snapToken   = null;
                // Xendit integration start
        $xenditApiKey = getenv('XENDIT_API_KEY');
        if (!$xenditApiKey) {
            throw new Exception('XENDIT_API_KEY not set in environment');
        }

        // Prepare Xendit Payment Session payload (POST /sessions)
        $payload = [
            'reference_id'       => 'order_' . $newOrderId . '_' . time(),
            'session_type'       => 'PAY',
            'mode'               => 'PAYMENT_LINK',
            'amount'             => (int)$subtotal,
            'currency'           => 'IDR',
            'country'            => 'ID',
            'customer'           => [
                'reference_id'      => 'user_' . $userId . '_' . time(),
                'type'              => 'INDIVIDUAL',
                'email'             => $user['email'] ?? 'pembeli@bloomandbooks.com',
                'individual_detail' => [
                    'given_names' => $user['nama'] ?? 'Pembeli',
                ],
            ],
            'success_return_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/user/orders.php?status=success',
            'cancel_return_url'  => 'https://' . $_SERVER['HTTP_HOST'] . '/cart.php',
        ];

        $ch = curl_init('https://api.xendit.co/sessions');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($xenditApiKey . ':'),
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new Exception('Xendit cURL error: ' . $err);
        }
        $session = json_decode($response, true);
        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new Exception('Xendit error (' . $httpCode . '): ' . ($session['message'] ?? $response));
        }
        if (empty($session['payment_link_url'])) {
            throw new Exception('Xendit tidak mengembalikan payment_link_url: ' . $response);
        }

        // Simpan xendit session id ke order untuk referensi webhook
        $pdo->prepare("UPDATE orders SET xendit_session_id = :sid WHERE id = :id")
            ->execute([':sid' => $session['payment_session_id'] ?? '', ':id' => $newOrderId]);

        // Kosongkan keranjang dan commit transaksi DB
        $pdo->prepare("DELETE FROM cart WHERE user_id = :uid")->execute([':uid' => $userId]);
        $pdo->commit();

        // Redirect ke halaman pembayaran Xendit
        header("Location: {$session['payment_link_url']}");
        exit;
        // === END XENDIT ===

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                setFlashMessage('error', "Gagal memproses pesanan (DB): " . $e->getMessage());
            } catch (\Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                setFlashMessage('error', $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Keranjang Belanja - Bloom&Books';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Ambient Glow Blobs -->
<div class="glow-blob-purple -top-10 -left-10"></div>
<div class="glow-blob-magenta top-96 -right-10"></div>

<div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto py-10 relative z-10">
    
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-6 border-b border-slate-200/80 gap-4 mb-8">
        <div>
            <span class="text-xs font-bold uppercase tracking-widest text-secondary block mb-1">Rincian Pembelian</span>
            <h1 class="font-serif text-3xl font-bold text-slate-900">Keranjang Belanja Anda</h1>
            <p class="text-xs text-slate-500 mt-1">Periksa kembali judul buku dan jumlah pesanan sebelum melanjutkan ke pembayaran.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="index.php#katalog" class="px-4 py-2 rounded-xl glass-card text-xs font-semibold text-slate-600 hover:text-primary transition-all flex items-center gap-1.5 shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Lanjut Pilih Buku</span>
            </a>
            <?php if (!empty($cartItems)): ?>
                <a href="cart.php?action=clear" onclick="return confirm('Kosongkan semua buku dari keranjang belanja?')" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-500 hover:bg-rose-50 transition-colors">
                    Kosongkan Keranjang
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($cartItems) && !$dummyPaymentSuccess): ?>
        <!-- =========================================================
             EMPTY STATE KERANJANG KOSONG
             ========================================================= -->
        <div class="glass-card rounded-3xl p-12 text-center max-w-lg mx-auto border border-white my-12 shadow-glass">
            <div class="w-20 h-20 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-5 shadow-sm">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            </div>
            <h2 class="font-serif text-2xl font-bold text-slate-900 mb-2">Keranjang Belanja Masih Kosong</h2>
            <p class="text-xs text-slate-500 leading-relaxed max-w-sm mx-auto mb-6">
                Belum ada buku yang Anda pilih. Buka katalog Bloom&Books untuk menemukan karya inspiratif terbaik hari ini.
            </p>
            <a href="index.php#katalog" class="px-6 py-3 rounded-full bg-gradient-to-r from-primary via-indigo-600 to-secondary text-white font-bold text-xs shadow-md hover:opacity-95 transition-all inline-flex items-center gap-2">
                <span>Jelajahi Katalog Buku</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>

    <?php else: ?>

        <!-- =========================================================
             TAMPILAN KERANJANG DENGAN ITEM
             ========================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Kolom Kiri: Daftar Item Keranjang -->
            <div class="lg:col-span-8 space-y-4">
                
                <?php foreach ($cartItems as $item): ?>
                    <div class="glass-card rounded-2xl p-4 sm:p-5 border border-white/90 shadow-glass-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        
                        <!-- Gambar & Info Buku -->
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-24 rounded-xl overflow-hidden bg-white shadow-sm shrink-0 border border-slate-200/60 flex items-center justify-center p-1">
                                <img src="<?= htmlspecialchars($item['cover_image']) ?>" alt="cover" class="max-h-full max-w-full object-contain">
                            </div>
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">
                                    <?= htmlspecialchars($item['nama_kategori'] ?? 'Buku') ?>
                                </span>
                                <h3 class="font-serif font-bold text-base text-slate-900 line-clamp-1 mt-0.5">
                                    <?= htmlspecialchars($item['judul']) ?>
                                </h3>
                                <p class="text-xs text-slate-400">Penulis: <?= htmlspecialchars($item['penulis']) ?></p>
                                <p class="text-xs font-bold text-primary mt-1"><?= formatRupiah($item['harga']) ?></p>
                            </div>
                        </div>

                        <!-- Controller Qty & Subtotal -->
                        <div class="flex items-center justify-between w-full sm:w-auto sm:justify-end gap-6 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                            
                            <!-- +/- Controller -->
                            <div class="flex items-center rounded-xl border border-slate-200 bg-white/80 overflow-hidden shadow-sm">
                                <a href="cart.php?action=qty&id=<?= $item['cart_id'] ?>&delta=-1" class="px-3 py-1 text-slate-500 hover:bg-slate-100 font-bold text-sm transition-colors">-</a>
                                <span class="px-3 py-1 text-xs font-bold text-slate-800 border-x border-slate-200 bg-slate-50 min-w-[32px] text-center"><?= $item['qty'] ?></span>
                                <a href="cart.php?action=qty&id=<?= $item['cart_id'] ?>&delta=1" class="px-3 py-1 text-slate-500 hover:bg-slate-100 font-bold text-sm transition-colors">+</a>
                            </div>

                            <!-- Subtotal Per Item -->
                            <div class="text-right min-w-[100px]">
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">Subtotal</span>
                                <span class="text-sm font-extrabold text-slate-900">
                                    <?= formatRupiah($item['harga'] * $item['qty']) ?>
                                </span>
                            </div>

                            <!-- Tombol Hapus -->
                            <a href="cart.php?action=remove&id=<?= $item['cart_id'] ?>" onclick="return confirm('Hapus buku ini dari keranjang?')" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </a>

                        </div>

                    </div>
                <?php endforeach; ?>

            </div>

            <!-- Kolom Kanan: Ringkasan Belanja & Form Checkout Dummy -->
            <div class="lg:col-span-4 sticky top-28">
                <div class="glass-card rounded-3xl p-6 sm:p-7 border border-white shadow-glass-lg">
                    
                    <h3 class="font-serif font-bold text-lg text-slate-900 pb-3 border-b border-slate-100">Ringkasan Belanja</h3>

                    <form action="cart.php" method="POST" class="mt-5 space-y-4">
                        <input type="hidden" name="action" value="checkout">

                        <!-- Alamat Pengiriman -->
                        <div>
                            <label for="alamat_pengiriman" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Alamat Pengiriman Buku <span class="text-secondary">*</span>
                            </label>
                            <textarea 
                                id="alamat_pengiriman" 
                                name="alamat_pengiriman" 
                                required
                                rows="3" 
                                placeholder="Tuliskan nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota, dan kode pos pengiriman..." 
                                class="glass-input w-full p-3 rounded-xl text-xs text-slate-800 outline-none leading-relaxed"
                            ><?= htmlspecialchars(!empty($user['alamat']) ? ($user['alamat'] . (!empty($user['kota']) ? ', ' . $user['kota'] : '') . (!empty($user['kode_pos']) ? ' ' . $user['kode_pos'] : '')) : 'Jl. Kebon Sirih No. 12, Gambir, Jakarta Pusat, DKI Jakarta 10110') ?></textarea>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Subtotal Buku (<?= count($cartItems) ?> judul)</span>
                                <span class="font-semibold text-slate-800"><?= formatRupiah($subtotal) ?></span>
                            </div>

                            <div class="flex items-center justify-between text-slate-500">
                                <span>Ongkos Kirim Nusantara</span>
                                <span class="font-bold text-emerald-600">GRATIS (Promo)</span>
                            </div>

                            <div class="flex items-center justify-between text-slate-500">
                                <span>Packaging Eksklusif</span>
                                <span class="font-semibold text-slate-800">Gratis Bubble Wrap</span>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span class="font-serif font-bold text-sm text-slate-900">Total Pembayaran:</span>
                                <span class="text-xl font-extrabold text-primary"><?= formatRupiah($subtotal) ?></span>
                            </div>
                        </div>


                        <!-- Tombol Checkout / Bayar -->
                        <button 
                            type="submit" 
                            class="w-full py-4 rounded-xl bg-gradient-to-r from-primary via-indigo-600 to-secondary text-white font-bold text-sm shadow-glass hover:opacity-95 transition-all text-center flex items-center justify-center gap-2"
                            id="btnCheckoutMidtrans"
                        >
                            <span>Bayar dengan Midtrans</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                        <script>
                        document.getElementById('btnCheckoutMidtrans').addEventListener('click', function(){
                            this.closest('form').submit();
                        });
                        </script>

                    </form>

                </div>
            </div>

        </div>

    <?php endif; ?>

</div>

<!-- =========================================================
     CUSTOM MODAL POP-UP: DUMMY PAYMENT SUCCESS
     ========================================================= -->
<?php if ($dummyPaymentSuccess): ?>
    <div id="dummySuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-md transition-opacity">
        <div class="glass-card bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl border border-white text-center relative overflow-hidden animate-scale-up">
            
            <!-- Confetti & Celebration Glow Blob -->
            <div class="w-40 h-40 rounded-full bg-emerald-400/20 blur-2xl absolute -top-10 -right-10 pointer-events-none"></div>

            <!-- Checkmark Animation Icon -->
            <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 text-white flex items-center justify-center mx-auto mb-5 shadow-lg shadow-emerald-500/30">
                <svg class="w-10 h-10 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-emerald-100 text-emerald-800 inline-block mb-2">
                PEMBAYARAN BERHASIL (DUMMY)
            </span>

            <h2 class="font-serif text-2xl font-bold text-slate-900 mb-1">Terima Kasih, <?= htmlspecialchars($user['nama']) ?>!</h2>
            <p class="text-xs text-slate-500 mb-6">Pesanan buku Anda telah berhasil diverifikasi dan masuk ke antrean pengemasan toko.</p>

            <!-- Transaksi Rincian Card -->
            <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 text-left text-xs space-y-2 mb-6">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Nomor Transaksi:</span>
                    <span class="font-mono font-bold text-primary">#ORD-<?= str_pad($dummyPaymentSuccess['order_id'], 4, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Total Pembayaran:</span>
                    <span class="font-bold text-slate-900"><?= formatRupiah($dummyPaymentSuccess['total_harga']) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Metode Pembayaran:</span>
                    <span class="font-semibold text-emerald-700">Simulasi Instan (Lunas)</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Waktu Transaksi:</span>
                    <span class="text-slate-700"><?= $dummyPaymentSuccess['date'] ?></span>
                </div>
                <div class="pt-2 border-t border-slate-200/60 text-[11px] text-slate-500">
                    <span class="font-semibold text-slate-700 block">Tujuan Pengiriman:</span>
                    <span class="truncate block"><?= htmlspecialchars($dummyPaymentSuccess['alamat']) ?></span>
                </div>
            </div>

            <!-- Tombol Aksi Menuju Riwayat Pesanan -->
            <div class="space-y-2">
                <a href="user/orders.php" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-primary via-indigo-600 to-secondary text-white font-bold text-xs sm:text-sm shadow-md hover:opacity-95 transition-all block text-center">
                    Lihat Riwayat Pesanan Saya
                </a>
                <a href="index.php" class="w-full py-2.5 px-4 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors block text-center">
                    Kembali ke Beranda Utama
                </a>
            </div>

        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
