<?php
/**
 * Bloom&Books - Chatbot API Endpoint
 * Memberikan respons cerdas untuk rekomendasi buku, pengecekan stok real-time,
 * status pesanan pengguna, pencarian katalog, dan FAQ toko.
 */

header('Content-Type: application/json; charset=utf-8');

// Include konfigurasi database & session
require_once __DIR__ . '/config/database.php';

// Pastikan koneksi DB tersedia
$pdo = getDBConnection();

// Ambil input dari JSON payload atau POST biasa
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = !empty($_POST) ? $_POST : $_GET;
}

$action = $data['action'] ?? ($_GET['action'] ?? '');
$userMessage = trim($data['message'] ?? ($_GET['message'] ?? ''));

$isLoggedIn = isLoggedIn();
$currentUser = currentUser();
$userName = $isLoggedIn ? ($currentUser['nama'] ?? 'Pembaca') : 'Sobat Pembaca';

// Helper response JSON
function sendResponse($reply, $books = [], $suggestions = []) {
    echo json_encode([
        'status' => 'success',
        'reply' => $reply,
        'books' => $books,
        'suggestions' => $suggestions
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// -------------------------------------------------------------
// 1. ACTION: Inisialisasi Chatbot (Welcome greeting & prompts)
// -------------------------------------------------------------
if ($action === 'init') {
    $greeting = "Halo, **{$userName}**! 👋 Selamat datang di **Bloom&Books**.\n\nSaya **BloomBot**, asisten belanja virtual Anda. Saya bisa membantu Kakak menemukan rekomendasi buku terbaik, cek ketersediaan stok buku secara akurat, info harga, hingga melacak pesanan.\n\nAda yang bisa saya bantu hari ini?";
    $suggestions = [
        "📚 Rekomendasi Terlaris",
        "📦 Cek Stok Buku",
        "✨ Novel Fiksi & Sastra",
        "💡 Pengembangan Diri",
        "💰 Buku di Bawah Rp 100rb",
        "🚚 Lacak Pesanan Saya",
        "🛒 Cara Pemesanan"
    ];
    sendResponse($greeting, [], $suggestions);
}

// Jika pesan kosong
if (empty($userMessage)) {
    sendResponse(
        "Halo Kak! Silakan ketik pertanyaan seputar buku, stok, atau rekomendasi yang Kakak inginkan. 😊",
        [],
        ["📚 Rekomendasi Terlaris", "📦 Cek Stok Buku", "✨ Novel Fiksi", "🛒 Cara Belanja"]
    );
}

// Normalisasi pesan pengguna
$cleanMsg = strtolower($userMessage);
$normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanMsg);
$normalized = preg_replace('/\s+/', ' ', trim($normalized));

// Helper fetch buku dari database dengan positional parameters aman
function fetchBooks($pdo, $whereClause = "1=1", $params = [], $limit = 4, $orderBy = "b.id DESC") {
    if (!$pdo) return [];
    try {
        $sql = "SELECT b.id, b.category_id, b.judul, b.penulis, b.penerbit, b.harga, b.stok, b.deskripsi, b.cover_image, c.nama_kategori 
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE {$whereClause} 
                ORDER BY {$orderBy} 
                LIMIT {$limit}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($books as &$b) {
            $b['harga_formatted'] = formatRupiah($b['harga']);
            $b['stok'] = (int)$b['stok'];
            $b['id'] = (int)$b['id'];
        }
        unset($b);
        return $books;
    } catch (PDOException $e) {
        return [];
    }
}

// Helper pencarian buku dengan LIKE multi-kolom aman
function searchBooksByKeyword($pdo, $keyword, $limit = 4, $orderBy = "b.id DESC") {
    if (!$pdo || empty(trim($keyword))) return [];
    try {
        $like = '%' . trim($keyword) . '%';
        $sql = "SELECT b.id, b.category_id, b.judul, b.penulis, b.penerbit, b.harga, b.stok, b.deskripsi, b.cover_image, c.nama_kategori 
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE (b.judul LIKE ? OR b.penulis LIKE ? OR b.penerbit LIKE ? OR b.deskripsi LIKE ?)
                ORDER BY {$orderBy} 
                LIMIT {$limit}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like, $like, $like, $like]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($books as &$b) {
            $b['harga_formatted'] = formatRupiah($b['harga']);
            $b['stok'] = (int)$b['stok'];
            $b['id'] = (int)$b['id'];
        }
        unset($b);
        return $books;
    } catch (PDOException $e) {
        return [];
    }
}

// -------------------------------------------------------------
// 2. DETEKSI INTENT: SAPAAN / GREETING
// -------------------------------------------------------------
$greetingWords = ['halo', 'hai', 'hi', 'hei', 'hello', 'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam', 'assalamualaikum', 'siapa kamu', 'siapakah kamu', 'kamu siapa', 'menu', 'bantuan', 'help'];
$isGreetingOnly = false;
foreach ($greetingWords as $w) {
    if ($normalized === $w || strpos($normalized, $w . ' ') === 0 || strpos($normalized, ' ' . $w) !== false) {
        if (strlen($normalized) <= strlen($w) + 8) {
            $isGreetingOnly = true;
            break;
        }
    }
}

if ($isGreetingOnly) {
    $reply = "Halo Kak **{$userName}**! Senang bisa menyapa Anda. 😊\n\nSaya siap membantu Kakak mencari buku favorit, mengecek ketersediaan stok fisik di rak kami, info promo hemat, hingga panduan belanja. Coba tanyakan sesuatu atau klik pilihan cepat berikut:";
    sendResponse($reply, [], [
        "📚 Rekomendasi Terlaris",
        "📦 Cek Stok Buku",
        "✨ Novel Fiksi Populer",
        "💡 Buku Bisnis & Diri",
        "💰 Buku di Bawah 100rb"
    ]);
}

// -------------------------------------------------------------
// 3. DETEKSI INTENT: CEK STOK BUKU (STOCK CHECK)
// -------------------------------------------------------------
$stockTriggers = ['stok', 'stock', 'ready', 'tersedia', 'ada gak', 'ada ngga', 'ada tidak', 'masih ada', 'sisa', 'habis'];
$hasStockIntent = false;
foreach ($stockTriggers as $st) {
    if (strpos($normalized, $st) !== false) {
        $hasStockIntent = true;
        break;
    }
}

if ($hasStockIntent) {
    // Ekstrak nama buku dari pertanyaan stok
    $bookKeyword = $normalized;
    $removeWords = ['apakah', 'ada', 'gak', 'ngga', 'tidak', 'stok', 'stock', 'ready', 'buku', 'novel', 'untuk', 'masih', 'tersedia', 'cek', 'info', 'berapa', 'sisa', 'di', 'toko', 'ini', 'kak', 'min', 'halo', 'tolong'];
    foreach ($removeWords as $rw) {
        $bookKeyword = preg_replace('/\b' . preg_quote($rw, '/') . '\b/u', '', $bookKeyword);
    }
    $bookKeyword = trim(preg_replace('/\s+/', ' ', $bookKeyword));

    if (strlen($bookKeyword) >= 2) {
        $matchedBooks = searchBooksByKeyword($pdo, $bookKeyword, 4, "b.stok DESC");

        if (!empty($matchedBooks)) {
            $reply = "📦 **Hasil Pengecekan Stok Real-time:**\n\n";
            foreach ($matchedBooks as $mb) {
                if ($mb['stok'] > 0) {
                    $reply .= "• **{$mb['judul']}** ({$mb['penulis']})\n";
                    $reply .= "  Status:  **TERSEDIA ({$mb['stok']} eksemplar)** | {$mb['harga_formatted']}\n\n";
                } else {
                    $reply .= "• **{$mb['judul']}** ({$mb['penulis']})\n";
                    $reply .= "  Status: ❌ **STOK HABIS** (Sedang restock dari penerbit)\n\n";
                }
            }
            $reply .= "Buku yang ready siap langsung dipacking rapi dan dikirim ke alamat Kakak! Silakan klik kartu buku di bawah untuk melihat detail atau membeli:";

            sendResponse($reply, $matchedBooks, [
                "📚 Rekomendasi Terlaris",
                "🚚 Info Pengiriman & Ongkir",
                "🛒 Cara Pemesanan"
            ]);
        }
    }

    // Jika tidak menyebutkan nama buku spesifik atau buku belum terdaftar
    $readyBooks = fetchBooks($pdo, "b.stok > 0", [], 4, "b.stok DESC");
    $reply = "📦 **Cek Ketersediaan Stok Buku:**\n\nKakak ingin mengecek stok buku yang mana? Ketik judul atau penulisnya (contoh: *'stok Laut Bercerita'* atau *'apakah Atomic Habits ready?'*).\n\nBerikut beberapa buku populer dengan stok melimpah yang siap kirim hari ini:";
    sendResponse($reply, $readyBooks, [
        "📦 Stok Laut Bercerita",
        "📦 Stok Atomic Habits",
        "📦 Stok Filosofi Teras",
        "📦 Stok Clean Code"
    ]);
}

// -------------------------------------------------------------
// 4. DETEKSI INTENT: STATUS PESANAN (ORDER TRACKING)
// -------------------------------------------------------------
$orderTriggers = ['pesanan', 'order', 'status pesanan', 'lacak', 'kiriman', 'riwayat belanja', 'resi', 'cek order'];
$hasOrderIntent = false;
foreach ($orderTriggers as $ot) {
    if (strpos($normalized, $ot) !== false) {
        $hasOrderIntent = true;
        break;
    }
}

if ($hasOrderIntent) {
    if (!$isLoggedIn) {
        $reply = "🔒 **Status Pesanan Pelanggan**\n\nUntuk melihat riwayat transaksi dan melacak status pesanan Anda, silakan **[Masuk / Login ke Akun Anda](login.php)** terlebih dahulu ya Kak.\n\nSetelah login, Kakak bisa langsung memantau status pembayaran dan pengiriman setiap pesanan.";
        sendResponse($reply, [], ["🔐 Masuk ke Akun", "📝 Daftar Akun Baru", "📚 Rekomendasi Buku"]);
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 3");
            $stmt->execute([$currentUser['id']]);
            $userOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($userOrders)) {
                $reply = "Halo Kak **{$userName}**, saat ini Kakak belum memiliki riwayat pesanan di Bloom&Books. 😊\n\nYuk mulai jelajahi katalog buku pilihan kami dan masukkan buku favorit ke keranjang!";
                $featuredBooks = fetchBooks($pdo, "1=1", [], 3, "b.id DESC");
                sendResponse($reply, $featuredBooks, ["📚 Rekomendasi Terlaris", "✨ Novel Fiksi", "🛒 Buka Keranjang"]);
            } else {
                $reply = "📋 **Riwayat Pesanan Terkini Anda (Kak {$userName}):**\n\n";
                foreach ($userOrders as $ord) {
                    $statusLabel = [
                        'pending'   => '⏳ Menunggu Pembayaran',
                        'paid'      => '✅ Pembayaran Berhasil / Diproses',
                        'completed' => '🎉 Selesai / Terkirim',
                        'cancelled' => '❌ Dibatalkan'
                    ][$ord['status_pembayaran']] ?? ucfirst($ord['status_pembayaran']);

                    $tgl = date('d M Y, H:i', strtotime($ord['order_date']));
                    $total = formatRupiah($ord['total_harga']);

                    $reply .= "• **Pesanan #ORD-{$ord['id']}**\n";
                    $reply .= "  Tanggal: {$tgl}\n";
                    $reply .= "  Total: **{$total}**\n";
                    $reply .= "  Status: **{$statusLabel}**\n\n";
                }
                $reply .= "Kakak dapat melihat rincian item lengkap di halaman **[Riwayat Pesanan Saya](user/orders.php)**.";
                sendResponse($reply, [], ["📋 Buka Halaman Pesanan", "📦 Cek Stok Buku", "📚 Rekomendasi Buku"]);
            }
        } catch (PDOException $e) {
            sendResponse("Terjadi kendala saat mengambil data pesanan. Silakan periksa langsung di halaman riwayat pesanan Kakak.", [], ["📋 Buka Halaman Pesanan"]);
        }
    }
}

// -------------------------------------------------------------
// 5. DETEKSI INTENT: REKOMENDASI BUKU (RECOMMENDATIONS)
// -------------------------------------------------------------
$recomTriggers = ['rekomendasi', 'recomended', 'rekomendasikan', 'sarankan', 'saran', 'buku bagus', 'best seller', 'bestseller', 'terlaris', 'populer', 'favorit', 'top', 'pilihan'];
$hasRecomIntent = false;
foreach ($recomTriggers as $rt) {
    if (strpos($normalized, $rt) !== false) {
        $hasRecomIntent = true;
        break;
    }
}

// Deteksi kategori spesifik
$isFiction = (strpos($normalized, 'fiksi') !== false || strpos($normalized, 'novel') !== false || strpos($normalized, 'sastra') !== false || strpos($normalized, 'cerita') !== false || strpos($normalized, 'romantis') !== false);
$isSelfDev = (strpos($normalized, 'pengembangan diri') !== false || strpos($normalized, 'self dev') !== false || strpos($normalized, 'self improvement') !== false || strpos($normalized, 'kebiasaan') !== false || strpos($normalized, 'habit') !== false || strpos($normalized, 'bisnis') !== false || strpos($normalized, 'uang') !== false || strpos($normalized, 'finansial') !== false || strpos($normalized, 'psikologi') !== false || strpos($normalized, 'filosofi') !== false || strpos($normalized, 'stoik') !== false || strpos($normalized, 'motivasi') !== false);
$isTech = (strpos($normalized, 'teknologi') !== false || strpos($normalized, 'tech') !== false || strpos($normalized, 'sains') !== false || strpos($normalized, 'coding') !== false || strpos($normalized, 'pemrograman') !== false || strpos($normalized, 'software') !== false || strpos($normalized, 'code') !== false || strpos($normalized, 'komputer') !== false || strpos($normalized, 'programmer') !== false || strpos($normalized, 'developer') !== false);
$isArt = (strpos($normalized, 'seni') !== false || strpos($normalized, 'desain') !== false || strpos($normalized, 'design') !== false || strpos($normalized, 'kreatif') !== false || strpos($normalized, 'kreativitas') !== false || strpos($normalized, 'art') !== false || strpos($normalized, 'ux') !== false || strpos($normalized, 'ui') !== false);
$isBudget = (strpos($normalized, 'murah') !== false || strpos($normalized, 'terjangkau') !== false || strpos($normalized, 'budget') !== false || strpos($normalized, 'hemat') !== false || strpos($normalized, '100rb') !== false || strpos($normalized, '100 rb') !== false || strpos($normalized, '100 ribu') !== false || strpos($normalized, '100.000') !== false || strpos($normalized, 'di bawah 100') !== false || strpos($normalized, 'kurang dari 100') !== false);

// Rekomendasi Fiksi & Sastra
if ($isFiction) {
    $books = fetchBooks($pdo, "b.category_id = ?", [1], 4, "b.stok DESC, b.id DESC");
    $reply = "✨ **Rekomendasi Novel Fiksi & Sastra Terpopuler:**\n\nKategori Fiksi & Sastra Bloom&Books menyajikan karya-karya sastra memikat dengan narasi mendalam yang paling digemari pembaca:";
    sendResponse($reply, $books, ["💡 Buku Pengembangan Diri", "💻 Buku Teknologi & Coding", "💰 Buku di Bawah 100rb"]);
}

// Rekomendasi Pengembangan Diri & Bisnis
if ($isSelfDev) {
    $books = fetchBooks($pdo, "b.category_id = ?", [2], 4, "b.stok DESC, b.id DESC");
    $reply = "💡 **Rekomendasi Buku Pengembangan Diri & Finansial:**\n\nBuku-buku ini sangat direkomendasikan untuk meningkatkan fokus, produktivitas harian, dan kecerdasan finansial jangka panjang:";
    sendResponse($reply, $books, ["✨ Novel Fiksi Terlaris", "💻 Buku Teknologi & Coding", "📦 Cek Stok Buku"]);
}

// Rekomendasi Teknologi & Coding
if ($isTech) {
    $books = fetchBooks($pdo, "b.category_id = ?", [3], 4, "b.stok DESC, b.id DESC");
    $reply = "💻 **Rekomendasi Buku Teknologi & Sains Pilihan:**\n\nBagi Kakak yang berkecimpung di bidang software development dan sains peradaban, buku-buku wajib ini sarat wawasan esensial:";
    sendResponse($reply, $books, ["🎨 Buku Seni & Desain", "💡 Buku Self-Improvement", "📦 Cek Stok Buku"]);
}

// Rekomendasi Desain & Seni
if ($isArt) {
    $books = fetchBooks($pdo, "b.category_id = ?", [4], 4, "b.stok DESC, b.id DESC");
    $reply = "🎨 **Rekomendasi Buku Seni & Desain Kreatif:**\n\nBuka sudut pandang baru seputar estetika, psikologi desain, dan kreativitas aplikatif dengan karya terkurasi berikut:";
    sendResponse($reply, $books, ["✨ Novel Fiksi Pilihan", "💡 Buku Bisnis & Diri", "🛒 Cara Belanja"]);
}

// Rekomendasi Buku Murah / Budget di bawah 100rb
if ($isBudget) {
    $books = fetchBooks($pdo, "b.harga <= ?", [100000], 4, "b.harga ASC");
    $reply = "💰 **Rekomendasi Buku Berkualitas di Bawah Rp 100.000:**\n\nBacaan bermutu tinggi ramah kantong! Berikut pilihan buku terfavorit dengan harga di bawah seratus ribu rupiah:";
    sendResponse($reply, $books, ["✨ Novel Fiksi", "💡 Buku Pengembangan Diri", "🚚 Info Pengiriman"]);
}

// Rekomendasi Umum / Terlaris
if ($hasRecomIntent) {
    $books = fetchBooks($pdo, "1=1", [], 4, "b.stok DESC, b.id ASC");
    $reply = "🌟 **Kurasi Buku Terlaris & Paling Direkomendasikan:**\n\nBerikut karya-karya terfavorit pembaca Bloom&Books dari berbagai kategori yang wajib masuk *reading list* Kakak:";
    sendResponse($reply, $books, [
        "✨ Novel Fiksi & Sastra",
        "💡 Pengembangan Diri",
        "💻 Teknologi & Coding",
        "💰 Buku di Bawah 100rb"
    ]);
}

// -------------------------------------------------------------
// 6. DETEKSI INTENT: CARA PEMESANAN / CHECKOUT / BELANJA
// -------------------------------------------------------------
$howToOrderTriggers = ['cara pesan', 'cara beli', 'cara belanja', 'cara order', 'checkout', 'langkah belanja', 'gimana belinya', 'bagaimana memesan', 'tata cara'];
foreach ($howToOrderTriggers as $ho) {
    if (strpos($normalized, $ho) !== false) {
        $reply = "🛒 **Panduan Mudah Belanja di Bloom&Books:**\n\n" .
                 "1️⃣ **Pilih Buku**: Telusuri buku pilihan di katalog kami, lalu klik buku untuk melihat sinopsis.\n" .
                 "2️⃣ **Masukkan ke Keranjang**: Tentukan jumlah eksemplar dan klik tombol **+ Masukkan ke Keranjang**.\n" .
                 "3️⃣ **Periksa Keranjang & Checkout**: Buka menu Keranjang (ikon tas di atas), periksa pesanan Anda, lalu isi alamat pengiriman.\n" .
                 "4️⃣ **Pembayaran Instan**: Pilih metode pembayaran simulasi (QRIS / VA Bank) dan klik Bayar Sekarang.\n" .
                 "5️⃣ **Selesai**: Pesanan Anda akan tercatat secara instan dan dapat dipantau di menu *Riwayat Pesanan*! 🎉";
        sendResponse($reply, [], ["📚 Lihat Katalog Buku", "💳 Metode Pembayaran", "🚚 Info Pengiriman"]);
    }
}

// -------------------------------------------------------------
// 7. DETEKSI INTENT: METODE PEMBAYARAN
// -------------------------------------------------------------
$paymentTriggers = ['bayar', 'pembayaran', 'qris', 'transfer', 'bank', 'rekening', 'bca', 'mandiri', 'bri', 'bni', 'gopay', 'ovo', 'dana', 'shopeepay', 'cod'];
foreach ($paymentTriggers as $pt) {
    if (strpos($normalized, $pt) !== false) {
        $reply = "💳 **Metode Pembayaran yang Didukung di Bloom&Books:**\n\n" .
                 "Kami mendukung beragam metode pembayaran otomatis & cepat:\n" .
                 "• **QRIS (Semua Pembayaran)**: Scan mudah via GoPay, OVO, Dana, ShopeePay, LinkAja, dan BCA Mobile.\n" .
                 "• **Virtual Account Bank**: Bank BCA, Bank Mandiri, Bank BRI, dan Bank BNI.\n" .
                 "• **Simulasi Checkout Instan**: Toko kami dilengkapi fitur *Dummy Payment Gateway* sehingga Kakak dapat langsung menyelesaikan transaksi demo tanpa hambatan.";
        sendResponse($reply, [], ["🛒 Cara Belanja", "🚚 Info Pengiriman", "📚 Rekomendasi Buku"]);
    }
}

// -------------------------------------------------------------
// 8. DETEKSI INTENT: PENGIRIMAN & ONGKIR
// -------------------------------------------------------------
$shippingTriggers = ['ongkir', 'ongkos kirim', 'kirim', 'pengiriman', 'ekspedisi', 'kurir', 'jne', 'jnt', 'sicepat', 'pos', 'estimasi', 'berapa hari', 'sampai kapan'];
foreach ($shippingTriggers as $st) {
    if (strpos($normalized, $st) !== false) {
        $reply = "🚚 **Informasi Pengiriman & Ekspedisi Bloom&Books:**\n\n" .
                 "• **Mitra Ekspedisi**: Bekerja sama dengan **JNE, SiCepat Express, dan J&T Express**.\n" .
                 "• **Estimasi Waktu Sampai**:\n" .
                 "  - Jabodetabek & Pulau Jawa: 1 - 3 hari kerja.\n" .
                 "  - Luar Pulau Jawa: 3 - 5 hari kerja.\n" .
                 "• **Packaging Aman**: Setiap buku dilapisi *bubble wrap* tebal dan kardus pelindung sudut secara gratis agar buku sampai tanpa lecet!";
        sendResponse($reply, [], ["📦 Cek Stok Buku", "💳 Metode Pembayaran", "📚 Rekomendasi Buku"]);
    }
}

// -------------------------------------------------------------
// 9. DETEKSI INTENT: KEASLIAN BUKU & GARANSI RETUR
// -------------------------------------------------------------
$originalTriggers = ['asli', 'original', 'ori', 'bajakan', 'retur', 'kembali', 'garansi', 'rusak', 'cacat'];
foreach ($originalTriggers as $ot) {
    if (strpos($normalized, $ot) !== false) {
        $reply = "🛡️ **Garansi 100% Buku Asli & Kebijakan Retur:**\n\n" .
                 "• **100% Original**: Bloom&Books berkomitmen hanya menjual buku resmi dan asli langsung dari penerbit. Kami menolak keras buku bajakan!\n" .
                 "• **Garansi Retur 7 Hari**: Jika buku yang Kakak terima cacat cetak (halaman kosong/hilang) atau rusak dalam pengiriman, kami ganti buku baru tanpa biaya tambahan.";
        sendResponse($reply, [], ["📚 Rekomendasi Buku", "📞 Hubungi Bantuan CS", "🛒 Cara Belanja"]);
    }
}

// -------------------------------------------------------------
// 10. DETEKSI INTENT: KONTAK / CUSTOMER SERVICE
// -------------------------------------------------------------
$contactTriggers = ['kontak', 'hubungi', 'admin', 'whatsapp', 'wa', 'nomor', 'email', 'cs', 'telepon', 'bantuan manusia', 'call'];
foreach ($contactTriggers as $ct) {
    if (strpos($normalized, $ct) !== false) {
        $reply = "📞 **Pusat Layanan Pelanggan Bloom&Books:**\n\n" .
                 "Tim Customer Service kami siap melayani Kakak dengan senang hati:\n" .
                 "• **WhatsApp**: [+62 812-3456-7890](https://wa.me/6281234567890) *(Respon cepat)*\n" .
                 "• **Email**: hello@bloombooks.com\n" .
                 "• **Jam Layanan**: Setiap hari, 08.00 - 21.00 WIB";
        sendResponse($reply, [], ["📚 Rekomendasi Buku", "📦 Cek Stok Buku", "🛒 Cara Belanja"]);
    }
}

// -------------------------------------------------------------
// 11. DETEKSI SPESIFIK NAMA PENULIS ATAU JUDUL (SMART SEARCH)
// -------------------------------------------------------------
$searchTerms = $normalized;
$stopWords = ['apakah', 'ada', 'buku', 'novel', 'karya', 'tulisan', 'penulis', 'dari', 'tentang', 'judul', 'mencari', 'cari', 'tolong', 'info', 'berapa', 'harga', 'harganya', 'sinopsis', 'cerita', 'kak', 'min', 'dong'];
foreach ($stopWords as $sw) {
    $searchTerms = preg_replace('/\b' . preg_quote($sw, '/') . '\b/u', '', $searchTerms);
}
$searchTerms = trim(preg_replace('/\s+/', ' ', $searchTerms));

if (strlen($searchTerms) >= 2) {
    $searchResults = searchBooksByKeyword($pdo, $searchTerms, 4);

    if (!empty($searchResults)) {
        $reply = "🔍 **Hasil Pencarian untuk '{$searchTerms}':**\n\nBerikut buku yang cocok di katalog Bloom&Books. Klik kartu di bawah untuk info detail dan pembelian:";
        sendResponse($reply, $searchResults, [
            "📚 Rekomendasi Lainnya",
            "📦 Cek Stok Buku",
            "🛒 Panduan Belanja"
        ]);
    }
}

// -------------------------------------------------------------
// 12. FALLBACK DEFAULT JIKA TIDAK ADA PENCOCOKAN
// -------------------------------------------------------------
$popularBooks = fetchBooks($pdo, "1=1", [], 3, "b.stok DESC, b.id DESC");

$fallbackReply = "Maaf Kak **{$userName}**, BloomBot belum menemukan kecocokan untuk pertanyaan tersebut. 🤔\n\nKakak bisa mencoba menanyakan:\n" .
                 "• *'Rekomendasi novel fiksi terbaik'*\n" .
                 "• *'Cek stok buku Atomic Habits'*\n" .
                 "• *'Buku teknologi dan coding'*\n" .
                 "• *'Berapa harga Filosofi Teras?'*\n" .
                 "• *'Lacak pesanan saya'*\n\n" .
                 "Atau pilih menu bantuan cepat di bawah ini:";

sendResponse($fallbackReply, $popularBooks, [
    "📚 Rekomendasi Terlaris",
    "📦 Cek Stok Buku",
    "✨ Novel Fiksi",
    "💡 Pengembangan Diri",
    "💰 Buku di Bawah 100rb",
    "📞 Kontak Bantuan CS"
]);
