<?php
/**
 * Bloom&Books - Management Data Transaksi & Laporan Pesanan
 * Fitur: List pesanan, filter status, detail item pesanan, dan pembaruan status transaksi.
 */

$pageTitle = 'Manajemen Transaksi & Pesanan';
$activePage = 'orders';

require_once __DIR__ . '/auth_check.php';

$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$viewOrderId = isset($_GET['view']) ? (int)$_GET['view'] : 0;

// =========================================================
// 1. UPDATE STATUS PESANAN
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status_pembayaran'] ?? '');
    $validStatuses = ['pending', 'paid', 'cancelled', 'completed'];

    if ($orderId > 0 && in_array($newStatus, $validStatuses) && $pdo !== null) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status_pembayaran = :status WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $orderId]);
            setFlashMessage('success', "Status pesanan #ORD-" . str_pad($orderId, 4, '0', STR_PAD_LEFT) . " berhasil diubah menjadi: " . ucfirst($newStatus));
        } catch (PDOException $e) {
            setFlashMessage('error', "Gagal memperbarui status: " . $e->getMessage());
        }
    }
    header('Location: orders.php' . ($viewOrderId > 0 ? '?view=' . $viewOrderId : ''));
    exit;
}

// =========================================================
// 2. GENERATE TRANSAKSI DUMMY JIKA DATABASE KOSONG
// =========================================================
if (isset($_GET['action']) && $_GET['action'] === 'seed_dummy_order' && $pdo !== null) {
    try {
        // Ambil user dan buku
        $userId = $pdo->query("SELECT id FROM users WHERE role = 'user' LIMIT 1")->fetchColumn() ?: 2;
        $book = $pdo->query("SELECT id, harga FROM books LIMIT 1")->fetch();

        if ($book) {
            $total = (float)$book['harga'] * 2;
            $insOrder = $pdo->prepare("
                INSERT INTO orders (user_id, total_harga, status_pembayaran, alamat_pengiriman)
                VALUES (:uid, :total, 'pending', 'Jl. Malioboro No. 45, Danurejan, Kota Yogyakarta')
            ");
            $insOrder->execute([':uid' => $userId, ':total' => $total]);
            $newOrdId = $pdo->lastInsertId();

            $insItem = $pdo->prepare("
                INSERT INTO order_items (order_id, book_id, qty, harga_satuan)
                VALUES (:oid, :bid, 2, :harga)
            ");
            $insItem->execute([':oid' => $newOrdId, ':bid' => $book['id'], ':harga' => $book['harga']]);

            setFlashMessage('success', "Pesanan uji coba berhasil dibuat! ID: #ORD-" . str_pad($newOrdId, 4, '0', STR_PAD_LEFT));
        }
    } catch (PDOException $e) {
        setFlashMessage('error', "Gagal membuat pesanan dummy: " . $e->getMessage());
    }
    header('Location: orders.php');
    exit;
}

// =========================================================
// 3. AMBIL DETAIL PESANAN SPESIFIK (JIKA PARAMETER ?view)
// =========================================================
$selectedOrder = null;
$orderItems = [];

if ($viewOrderId > 0 && $pdo !== null) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.nama as customer_name, u.email as customer_email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $viewOrderId]);
        $selectedOrder = $stmt->fetch();

        if ($selectedOrder) {
            $itemStmt = $pdo->prepare("
                SELECT oi.*, b.judul, b.penulis, b.cover_image 
                FROM order_items oi 
                JOIN books b ON oi.book_id = b.id 
                WHERE oi.order_id = :oid
            ");
            $itemStmt->execute([':oid' => $viewOrderId]);
            $orderItems = $itemStmt->fetchAll();
        }
    } catch (PDOException $e) {}
}

// =========================================================
// 4. AMBIL SELURUH DAFTAR PESANAN
// =========================================================
$orders = [];
if ($pdo !== null) {
    try {
        $sql = "
            SELECT o.*, u.nama as customer_name, u.email as customer_email,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as total_items
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filterStatus)) {
            $sql .= " AND o.status_pembayaran = :status";
            $params[':status'] = $filterStatus;
        }

        $sql .= " ORDER BY o.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
    } catch (PDOException $e) {}
}

require_once __DIR__ . '/layout/header.php';
?>

<!-- =========================================================
     DETAIL MODAL / VIEW KARTU PESANAN SPESIFIK
     ========================================================= -->
<?php if ($selectedOrder): ?>
    <div class="mb-8 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-md">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
            <div>
                <a href="orders.php" class="text-xs font-semibold text-slate-400 hover:text-primary transition-colors flex items-center gap-1 mb-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Tutup Detail Pesanan</span>
                </a>
                <div class="flex items-center gap-3">
                    <h2 class="font-serif text-2xl font-bold text-slate-900">
                        Pesanan #ORD-<?= str_pad($selectedOrder['id'], 4, '0', STR_PAD_LEFT) ?>
                    </h2>
                    <?php
                    $status = $selectedOrder['status_pembayaran'];
                    $badgeClass = match($status) {
                        'paid', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                        default => 'bg-slate-50 text-slate-700 border-slate-200'
                    };
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $badgeClass ?>">
                        Status: <?= ucfirst($status) ?>
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Tanggal Transaksi: <?= date('d F Y, H:i:s', strtotime($selectedOrder['order_date'])) ?></p>
            </div>

            <!-- Form Ubah Status Cepat -->
            <form action="orders.php?view=<?= $selectedOrder['id'] ?>" method="POST" class="flex items-center gap-2">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="<?= $selectedOrder['id'] ?>">
                
                <label for="statusSelect" class="text-xs font-bold text-slate-600">Ubah Status:</label>
                <select id="statusSelect" name="status_pembayaran" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-white outline-none">
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending (Menunggu)</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid (Lunas)</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed (Selesai)</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled (Batal)</option>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-hover transition-colors shadow-sm">
                    Simpan
                </button>
            </form>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-6 p-5 rounded-2xl bg-slate-50 border border-slate-100 text-xs">
            <div>
                <p class="font-bold text-slate-400 uppercase tracking-wider text-[10px] mb-1">Informasi Pembeli</p>
                <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($selectedOrder['customer_name'] ?? 'Pelanggan Umum') ?></p>
                <p class="text-slate-500"><?= htmlspecialchars($selectedOrder['customer_email'] ?? '-') ?></p>
            </div>
            <div>
                <p class="font-bold text-slate-400 uppercase tracking-wider text-[10px] mb-1">Alamat Pengiriman Paket</p>
                <p class="text-slate-700 leading-relaxed font-medium"><?= nl2br(htmlspecialchars($selectedOrder['alamat_pengiriman'])) ?></p>
            </div>
        </div>

        <!-- Rincian Buku yang Dipesan -->
        <h4 class="font-serif font-bold text-base text-slate-900 mb-3">Item Buku yang Dipesan</h4>
        <div class="border border-slate-100 rounded-2xl overflow-hidden mb-6">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 uppercase text-[10px] font-bold tracking-wider text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="p-3">Buku</th>
                        <th class="p-3 text-center">Jumlah (Qty)</th>
                        <th class="p-3 text-right">Harga Satuan</th>
                        <th class="p-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($orderItems)): ?>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td class="p-3">
                                    <div class="flex items-center gap-3">
                                        <img src="<?= htmlspecialchars($item['cover_image']) ?>" class="w-8 h-12 object-cover rounded shadow-sm">
                                        <div>
                                            <p class="font-bold text-slate-800"><?= htmlspecialchars($item['judul']) ?></p>
                                            <p class="text-[10px] text-slate-400"><?= htmlspecialchars($item['penulis']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3 text-center font-bold"><?= $item['qty'] ?></td>
                                <td class="p-3 text-right"><?= formatRupiah($item['harga_satuan']) ?></td>
                                <td class="p-3 text-right font-bold text-slate-900"><?= formatRupiah($item['qty'] * $item['harga_satuan']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-4 text-center text-slate-400">Tidak ada item tercatat untuk pesanan ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-slate-900">
                    <tr>
                        <td colspan="3" class="p-4 text-right">Total Tagihan Pesanan:</td>
                        <td class="p-4 text-right text-base text-primary font-extrabold"><?= formatRupiah($selectedOrder['total_harga']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
<?php endif; ?>

<!-- =========================================================
     DAFTAR LAPORAN TRANSAKSI (TABLE LIST)
     ========================================================= -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="font-serif text-2xl font-bold text-slate-900">Daftar Transaksi Pesanan</h2>
        <p class="text-xs text-slate-500 mt-0.5">Kelola verifikasi pembayaran dan pengiriman paket buku pelanggan.</p>
    </div>

    <div class="flex items-center gap-2">
        <a href="orders.php?action=seed_dummy_order" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-colors flex items-center gap-1.5" title="Buat transaksi contoh untuk pengujian">
            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            <span>+ Buat Contoh Transaksi</span>
        </a>
    </div>
</div>

<!-- Filter Pills -->
<div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
        <a href="orders.php" class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all <?= empty($filterStatus) ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            Semua Pesanan
        </a>
        <a href="orders.php?status=pending" class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all <?= $filterStatus === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            Pending
        </a>
        <a href="orders.php?status=paid" class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all <?= $filterStatus === 'paid' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            Paid (Lunas)
        </a>
        <a href="orders.php?status=completed" class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all <?= $filterStatus === 'completed' ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            Completed
        </a>
        <a href="orders.php?status=cancelled" class="px-4 py-1.5 rounded-full text-xs font-semibold transition-all <?= $filterStatus === 'cancelled' ? 'bg-rose-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            Cancelled
        </a>
    </div>

    <span class="text-xs text-slate-400">
        Menampilkan <strong class="text-slate-800"><?= count($orders) ?></strong> transaksi
    </span>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 uppercase text-[10px] font-bold tracking-wider text-slate-400 border-b border-slate-100">
                <tr>
                    <th class="py-3.5 px-6">ID Pesanan</th>
                    <th class="py-3.5 px-6">Nama Pelanggan</th>
                    <th class="py-3.5 px-6">Tanggal Order</th>
                    <th class="py-3.5 px-6">Alamat Kirim</th>
                    <th class="py-3.5 px-6">Total Harga</th>
                    <th class="py-3.5 px-6">Status</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $o): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6 font-mono font-bold text-primary">
                                #ORD-<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td class="py-4 px-6">
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($o['customer_name'] ?? 'Pelanggan Umum') ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($o['customer_email'] ?? '-') ?></p>
                            </td>
                            <td class="py-4 px-6 text-slate-500 whitespace-nowrap">
                                <?= date('d M Y, H:i', strtotime($o['order_date'])) ?>
                            </td>
                            <td class="py-4 px-6 text-slate-600 truncate max-w-[200px]" title="<?= htmlspecialchars($o['alamat_pengiriman']) ?>">
                                <?= htmlspecialchars($o['alamat_pengiriman']) ?>
                            </td>
                            <td class="py-4 px-6 font-extrabold text-slate-900 whitespace-nowrap">
                                <?= formatRupiah($o['total_harga']) ?>
                            </td>
                            <td class="py-4 px-6">
                                <?php
                                $st = $o['status_pembayaran'];
                                $badgeClass = match($st) {
                                    'paid', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                                ?>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $badgeClass ?>">
                                    <?= ucfirst($st) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <a href="orders.php?view=<?= $o['id'] ?>" class="px-3 py-1.5 rounded-xl bg-primary/10 hover:bg-primary text-primary hover:text-white font-bold text-xs transition-colors">
                                    Detail & Status
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <p class="text-sm">Belum ada data transaksi yang tercatat.</p>
                            <a href="orders.php?action=seed_dummy_order" class="mt-2 inline-block text-xs font-bold text-primary hover:underline">
                                + Buat Contoh Transaksi Sekarang
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
