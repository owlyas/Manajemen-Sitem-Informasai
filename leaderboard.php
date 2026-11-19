<?php
/**
 * Bloom&Books - Leaderboard Buku Terpopuler
 * Menampilkan ranking buku berdasarkan: total terjual (order_items), jumlah ulasan, dan rating rata-rata.
 */

$pageTitle = 'Leaderboard Buku Terpopuler - Bloom&Books';
require_once __DIR__ . '/config/database.php';

// =========================================================
// AMBIL DATA LEADERBOARD DARI DATABASE
// =========================================================

$topBooks     = [];
$topRated     = [];
$topReviewed  = [];
$periodFilter = $_GET['period'] ?? 'all'; // all | month | week
$categoryFilter = (int)($_GET['kategori'] ?? 0);

$dateCondition = '';
if ($periodFilter === 'month') {
    $dateCondition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($periodFilter === 'week') {
    $dateCondition = "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

$catCondition = $categoryFilter > 0 ? "AND b.category_id = {$categoryFilter}" : '';

if ($pdo !== null) {
    try {
        // ── Top 10: Paling Banyak Terjual ──────────────────────────────
        $topBooks = $pdo->query("
            SELECT 
                b.id, b.judul, b.penulis, b.harga, b.cover_image,
                c.nama_kategori,
                COALESCE(SUM(oi.qty), 0) AS total_terjual,
                COALESCE(SUM(oi.qty * oi.harga_satuan), 0) AS total_pendapatan,
                COALESCE(AVG(r.rating), 0) AS avg_rating,
                COUNT(DISTINCT r.id) AS jumlah_ulasan
            FROM books b
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN order_items oi ON b.id = oi.book_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status_pembayaran = 'paid' {$dateCondition}
            LEFT JOIN reviews r ON b.id = r.book_id
            WHERE 1=1 {$catCondition}
            GROUP BY b.id, b.judul, b.penulis, b.harga, b.cover_image, c.nama_kategori
            ORDER BY total_terjual DESC, avg_rating DESC
            LIMIT 10
        ")->fetchAll();

        // ── Top 5: Rating Tertinggi ─────────────────────────────────────
        $topRated = $pdo->query("
            SELECT 
                b.id, b.judul, b.penulis, b.cover_image, c.nama_kategori,
                COALESCE(AVG(r.rating), 0) AS avg_rating,
                COUNT(r.id) AS jumlah_ulasan,
                COALESCE(SUM(oi.qty), 0) AS total_terjual
            FROM books b
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN reviews r ON b.id = r.book_id
            LEFT JOIN order_items oi ON b.id = oi.book_id
            WHERE 1=1 {$catCondition}
            GROUP BY b.id, b.judul, b.penulis, b.cover_image, c.nama_kategori
            HAVING jumlah_ulasan > 0
            ORDER BY avg_rating DESC, jumlah_ulasan DESC
            LIMIT 5
        ")->fetchAll();

        // ── Statistik Global ────────────────────────────────────────────
        $stats = $pdo->query("
            SELECT 
                COUNT(DISTINCT b.id) AS total_books,
                COALESCE(SUM(oi.qty), 0) AS total_sold,
                COUNT(DISTINCT o.id) AS total_orders,
                COUNT(DISTINCT r.id) AS total_reviews
            FROM books b
            LEFT JOIN order_items oi ON b.id = oi.book_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status_pembayaran = 'paid'
            LEFT JOIN reviews r ON b.id = r.book_id
        ")->fetch();

        // ── Kategori untuk filter ───────────────────────────────────────
        $categories = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();

    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

// Helper: render bintang
function renderStars(float $rating): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $fill = $i <= round($rating) ? '#F72585' : '#E2E8F0';
        $html .= "<svg class='w-3 h-3' fill='{$fill}' viewBox='0 0 20 20'><path d='M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z'/></svg>";
    }
    return $html;
}

// Badge warna per rank
function rankBadge(int $rank): string {
    return match($rank) {
        1 => 'bg-gradient-to-br from-yellow-400 to-amber-500 text-white shadow-lg shadow-yellow-400/40',
        2 => 'bg-gradient-to-br from-slate-300 to-slate-400 text-white shadow-md shadow-slate-300/40',
        3 => 'bg-gradient-to-br from-amber-600 to-orange-700 text-white shadow-md shadow-orange-400/30',
        default => 'bg-slate-100 text-slate-500',
    };
}

function rankEmoji(int $rank): string {
    return match($rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => "#{$rank}" };
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Ambient Blobs -->
<div class="glow-blob-purple -top-10 -left-10"></div>
<div class="glow-blob-magenta top-80 -right-10"></div>

<div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto py-10 relative z-10">

    <!-- ═══ HERO HEADER ═══════════════════════════════════════════════ -->
    <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-secondary/10 text-secondary text-xs font-bold uppercase tracking-widest mb-4">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z'/></svg>
            Leaderboard
        </div>
        <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 mb-3">
            Buku <span class="bg-gradient-to-r from-primary via-indigo-600 to-secondary bg-clip-text text-transparent">Terpopuler</span>
        </h1>
        <p class="text-sm text-slate-500 max-w-xl mx-auto">
            Ranking real-time berdasarkan total penjualan, rating pembaca, dan ulasan terpercaya dari komunitas Bloom&Books.
        </p>
    </div>

    <!-- ═══ STATISTIK GLOBAL ══════════════════════════════════════════ -->
    <?php if (!empty($stats)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10">
        <?php
        $statItems = [
            ['label' => 'Total Buku',     'value' => number_format($stats['total_books']),   'icon' => '📚', 'color' => 'primary'],
            ['label' => 'Total Terjual',  'value' => number_format($stats['total_sold']),    'icon' => '🛒', 'color' => 'secondary'],
            ['label' => 'Total Order',    'value' => number_format($stats['total_orders']),  'icon' => '📦', 'color' => 'primary'],
            ['label' => 'Total Ulasan',   'value' => number_format($stats['total_reviews']), 'icon' => '⭐', 'color' => 'secondary'],
        ];
        foreach ($statItems as $s):
        ?>
        <div class="glass-card rounded-2xl p-4 sm:p-5 border border-white/90 shadow-glass-sm text-center">
            <div class="text-2xl mb-1"><?= $s['icon'] ?></div>
            <div class="text-xl sm:text-2xl font-bold text-<?= $s['color'] ?>"><?= $s['value'] ?></div>
            <div class="text-[11px] text-slate-400 font-medium mt-0.5"><?= $s['label'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ═══ FILTER BAR ════════════════════════════════════════════════ -->
    <form method="GET" class="glass-card rounded-2xl border border-white/90 shadow-glass-sm p-4 mb-8 flex flex-wrap gap-3 items-center">
        <!-- Period Filter -->
        <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
            <?php foreach (['all' => 'Semua Waktu', 'month' => '30 Hari', 'week' => '7 Hari'] as $key => $label): ?>
            <button type="submit" name="period" value="<?= $key ?>"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all <?= $periodFilter === $key ? 'bg-white text-primary shadow-sm' : 'text-slate-500 hover:text-primary' ?>">
                <?= $label ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Category Filter -->
        <select name="kategori" onchange="this.form.submit()"
            class="text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none cursor-pointer">
            <option value="0">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['nama_kategori']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <?php if ($periodFilter !== 'all' || $categoryFilter): ?>
        <a href="leaderboard.php" class="text-xs text-rose-500 font-semibold hover:underline">Reset Filter</a>
        <?php endif; ?>

        <span class="ml-auto text-[11px] text-slate-400">
            Diperbarui: <?= date('d M Y, H:i') ?> WIB
        </span>
    </form>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">

        <!-- ═══════════════════════════════════════════════════════════
             KOLOM KIRI: LEADERBOARD UTAMA (TOP 10)
             ═══════════════════════════════════════════════════════════ -->
        <div class="xl:col-span-8">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="font-serif text-xl font-bold text-slate-900">🏆 Top 10 Paling Banyak Terjual</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Berdasarkan total qty terjual dari order yang lunas</p>
                </div>
            </div>

            <?php if (empty($topBooks)): ?>
            <div class="glass-card rounded-2xl p-10 text-center border border-white">
                <div class="text-4xl mb-3">📊</div>
                <p class="text-sm text-slate-500">Belum ada data penjualan.</p>
                <a href="index.php#katalog" class="mt-4 inline-block text-xs font-bold text-primary hover:underline">Jelajahi Katalog →</a>
            </div>
            <?php else: ?>

            <!-- TOP 3 PODIUM ─────────────────────────────────────────── -->
            <div class="grid grid-cols-3 gap-3 mb-6">
                <?php
                $podiumOrder = [1, 0, 2]; // Tampil: #2, #1, #3 → tapi kita urut #1 di tengah via CSS
                $podiumBooks = array_slice($topBooks, 0, 3);
                // Susun: index 1 = rank2 (kiri), index 0 = rank1 (tengah), index 2 = rank3 (kanan)
                $ordered = [$podiumBooks[1] ?? null, $podiumBooks[0] ?? null, $podiumBooks[2] ?? null];
                $podiumRanks = [2, 1, 3];
                foreach ($ordered as $pi => $pb):
                    if (!$pb) continue;
                    $prank = $podiumRanks[$pi];
                    $isFirst = $prank === 1;
                ?>
                <div class="glass-card rounded-2xl border border-white/90 shadow-glass p-4 text-center flex flex-col items-center gap-2 <?= $isFirst ? 'ring-2 ring-yellow-400/60 shadow-yellow-400/20 shadow-lg' : '' ?> relative overflow-hidden">
                    <?php if ($isFirst): ?>
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-yellow-400 via-amber-400 to-orange-400 rounded-t-2xl"></div>
                    <?php endif; ?>

                    <!-- Rank Badge -->
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black <?= rankBadge($prank) ?>">
                        <?= $prank === 1 ? '👑' : ($prank === 2 ? '🥈' : '🥉') ?>
                    </div>

                    <!-- Cover -->
                    <div class="w-16 h-24 rounded-xl overflow-hidden bg-slate-100 shadow-sm border border-slate-200/60 flex items-center justify-center">
                        <img src="<?= htmlspecialchars($pb['cover_image'] ?? '') ?>" alt="cover"
                             class="max-h-full max-w-full object-contain"
                             onerror="this.src='https://via.placeholder.com/80x112/3A0CA3/ffffff?text=📚'">
                    </div>

                    <!-- Info -->
                    <div class="w-full">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary"><?= htmlspecialchars($pb['nama_kategori']) ?></p>
                        <h3 class="font-serif font-bold text-xs text-slate-800 line-clamp-2 mt-0.5 leading-snug"><?= htmlspecialchars($pb['judul']) ?></h3>
                        <p class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars($pb['penulis']) ?></p>
                    </div>

                    <!-- Stats -->
                    <div class="w-full border-t border-slate-100 pt-2 mt-1">
                        <div class="text-lg font-black <?= $isFirst ? 'text-yellow-500' : 'text-primary' ?>"><?= number_format($pb['total_terjual']) ?></div>
                        <div class="text-[10px] text-slate-400 font-medium">eksemplar terjual</div>
                        <div class="flex items-center justify-center gap-0.5 mt-1.5">
                            <?= renderStars((float)$pb['avg_rating']) ?>
                            <span class="text-[10px] text-slate-400 ml-1"><?= number_format((float)$pb['avg_rating'], 1) ?></span>
                        </div>
                    </div>

                    <a href="index.php?view_book=<?= $pb['id'] ?>#katalog"
                       class="w-full mt-1 text-[10px] font-bold text-center py-1.5 rounded-lg bg-primary/5 text-primary hover:bg-primary hover:text-white transition-all">
                        Lihat Detail
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- RANK 4-10: LIST ──────────────────────────────────────── -->
            <div class="space-y-3">
                <?php foreach (array_slice($topBooks, 3) as $idx => $book):
                    $rank = $idx + 4;
                ?>
                <div class="glass-card rounded-xl border border-white/90 shadow-glass-sm p-3 sm:p-4 flex items-center gap-3 sm:gap-4 hover:shadow-glass transition-all group">
                    
                    <!-- Rank Number -->
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-black shrink-0 <?= rankBadge($rank) ?>">
                        <?= $rank ?>
                    </div>

                    <!-- Cover -->
                    <div class="w-10 h-14 sm:w-12 sm:h-16 rounded-lg overflow-hidden bg-slate-100 border border-slate-200/60 flex items-center justify-center shrink-0">
                        <img src="<?= htmlspecialchars($book['cover_image'] ?? '') ?>" alt="cover"
                             class="max-h-full max-w-full object-contain"
                             onerror="this.src='https://via.placeholder.com/50x70/3A0CA3/ffffff?text=📚'">
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-secondary"><?= htmlspecialchars($book['nama_kategori']) ?></p>
                        <h3 class="font-serif font-bold text-sm text-slate-800 line-clamp-1 mt-0.5"><?= htmlspecialchars($book['judul']) ?></h3>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($book['penulis']) ?></p>
                        <div class="flex items-center gap-0.5 mt-1">
                            <?= renderStars((float)$book['avg_rating']) ?>
                            <span class="text-[10px] text-slate-400 ml-1">(<?= $book['jumlah_ulasan'] ?> ulasan)</span>
                        </div>
                    </div>

                    <!-- Penjualan -->
                    <div class="text-right shrink-0">
                        <div class="text-base sm:text-lg font-black text-primary"><?= number_format($book['total_terjual']) ?></div>
                        <div class="text-[10px] text-slate-400">terjual</div>
                        <div class="text-[10px] font-bold text-emerald-600 mt-0.5"><?= formatRupiah($book['harga']) ?></div>
                    </div>

                    <!-- CTA -->
                    <a href="index.php?view_book=<?= $book['id'] ?>#katalog"
                       class="hidden sm:flex items-center justify-center w-8 h-8 rounded-lg bg-primary/5 text-primary hover:bg-primary hover:text-white transition-all shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endif; // end topBooks ?>
        </div>

        <!-- ═══════════════════════════════════════════════════════════
             KOLOM KANAN: SIDEBAR WIDGETS
             ═══════════════════════════════════════════════════════════ -->
        <div class="xl:col-span-4 space-y-6">

            <!-- ── Top Rated ──────────────────────────────────────── -->
            <div class="glass-card rounded-2xl border border-white/90 shadow-glass p-5">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-secondary/20 to-primary/20 flex items-center justify-center text-base">⭐</div>
                    <div>
                        <h3 class="font-serif font-bold text-sm text-slate-800">Rating Tertinggi</h3>
                        <p class="text-[10px] text-slate-400">Paling disukai pembaca</p>
                    </div>
                </div>

                <?php if (empty($topRated)): ?>
                <p class="text-xs text-slate-400 text-center py-4">Belum ada ulasan.</p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($topRated as $ri => $rb): ?>
                    <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors group">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 <?= rankBadge($ri + 1) ?>">
                            <?= rankEmoji($ri + 1) ?>
                        </div>
                        <div class="w-9 h-12 rounded-lg overflow-hidden bg-slate-100 border border-slate-200/50 flex items-center justify-center shrink-0">
                            <img src="<?= htmlspecialchars($rb['cover_image'] ?? '') ?>" alt="cover"
                                 class="max-h-full max-w-full object-contain"
                                 onerror="this.src='https://via.placeholder.com/40x56/3A0CA3/ffffff?text=📚'">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-bold text-slate-800 line-clamp-1"><?= htmlspecialchars($rb['judul']) ?></h4>
                            <p class="text-[10px] text-slate-400"><?= htmlspecialchars($rb['penulis']) ?></p>
                            <div class="flex items-center gap-0.5 mt-0.5">
                                <?= renderStars((float)$rb['avg_rating']) ?>
                                <span class="text-[10px] font-bold text-secondary ml-1"><?= number_format((float)$rb['avg_rating'], 1) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Info Card ──────────────────────────────────────── -->
            <div class="rounded-2xl border border-primary/20 bg-gradient-to-br from-primary/5 via-indigo-50 to-secondary/5 p-5">
                <h3 class="font-serif font-bold text-sm text-primary mb-2">ℹ️ Cara Menghitung Popularitas</h3>
                <ul class="space-y-2 text-xs text-slate-600">
                    <li class="flex items-start gap-2">
                        <span class="text-secondary font-bold mt-0.5">🛒</span>
                        <span><strong>Total Terjual:</strong> Jumlah eksemplar yang berhasil dibeli (status order: lunas).</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-secondary font-bold mt-0.5">⭐</span>
                        <span><strong>Rating:</strong> Rata-rata bintang dari semua ulasan pembaca terverifikasi.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-secondary font-bold mt-0.5">📊</span>
                        <span><strong>Filter Waktu:</strong> Sempurnakan analisis dengan filter 7 atau 30 hari terakhir.</span>
                    </li>
                </ul>
            </div>

            <!-- ── CTA: Belanja ───────────────────────────────────── -->
            <div class="rounded-2xl border border-secondary/20 bg-gradient-to-br from-secondary/5 to-primary/5 p-5 text-center">
                <div class="text-3xl mb-2">🌟</div>
                <h3 class="font-serif font-bold text-sm text-slate-800 mb-1">Temukan Buku Favoritmu</h3>
                <p class="text-[11px] text-slate-500 mb-4">Jelajahi ribuan judul dari para penulis terbaik dunia & Indonesia.</p>
                <a href="index.php#katalog"
                   class="inline-block px-5 py-2.5 rounded-full bg-gradient-to-r from-primary via-indigo-600 to-secondary text-white text-xs font-bold shadow-md hover:opacity-95 transition-all">
                    Jelajahi Katalog →
                </a>
            </div>
        </div>

    </div><!-- end grid -->
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
