<?php
$pageTitle = 'Bloom&Books - Toko Buku Online Modern & Elegan';
require_once __DIR__ . '/config/database.php';

// =========================================================
// ACTION: SUBMIT ULASAN & RATING BUKU
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Silakan masuk ke akun Anda terlebih dahulu untuk memberikan ulasan.');
        header('Location: login.php');
        exit;
    }
    
    $user = currentUser();
    $bookId = (int)($_POST['book_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;
    $komentar = trim($_POST['komentar'] ?? '');

    if ($bookId <= 0 || empty($komentar)) {
        setFlashMessage('error', 'Mohon pilih rating bintang dan tuliskan komentar ulasan Anda.');
    } else {
        if ($pdo !== null) {
            try {
                // Pastikan tabel reviews ada
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `reviews` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `book_id` INT NOT NULL,
                        `user_id` INT NOT NULL,
                        `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
                        `komentar` TEXT NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                $stmt = $pdo->prepare("
                    INSERT INTO reviews (book_id, user_id, rating, komentar) 
                    VALUES (:bid, :uid, :rating, :komentar)
                ");
                $stmt->execute([
                    ':bid' => $bookId,
                    ':uid' => $user['id'],
                    ':rating' => $rating,
                    ':komentar' => $komentar
                ]);
                setFlashMessage('success', 'Terima kasih! Ulasan dan rating bintang Anda berhasil dipublikasikan.');
            } catch (PDOException $e) {
                setFlashMessage('error', 'Gagal mempublikasikan ulasan: ' . $e->getMessage());
            }
        } else {
            setFlashMessage('success', 'Ulasan berhasil disimpan (Mode Demonstrasi).');
        }
    }
    header("Location: index.php?view_book={$bookId}#katalog");
    exit;
}

// Ambil parameter filter & pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedCategory = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

$categories = [];
$books = [];
$dbConnected = false;
$reviewsByBook = [];

// Coba ambil data dari database jika koneksi aktif
if ($pdo !== null) {
    try {
        // Ambil daftar kategori
        $catStmt = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
        $categories = $catStmt->fetchAll();

        // Bangun query pencarian dan filter buku
        $sql = "SELECT b.*, c.nama_kategori 
                FROM books b 
                JOIN categories c ON b.category_id = c.id 
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (b.judul LIKE :search OR b.penulis LIKE :search OR b.penerbit LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($selectedCategory > 0) {
            $sql .= " AND b.category_id = :category_id";
            $params[':category_id'] = $selectedCategory;
        }

        $sql .= " ORDER BY b.id ASC";

        $bookStmt = $pdo->prepare($sql);
        $bookStmt->execute($params);
        $books = $bookStmt->fetchAll();
        $dbConnected = true;

        // Ambil ulasan dari tabel reviews jika ada
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `reviews` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `book_id` INT NOT NULL,
                    `user_id` INT NOT NULL,
                    `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
                    `komentar` TEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Cek jika kosong, lakukan seeding ulasan awal
            $revCheck = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
            if ($revCheck == 0) {
                $pdo->exec("
                    INSERT INTO `reviews` (`book_id`, `user_id`, `rating`, `komentar`, `created_at`) VALUES
                    (1, 2, 5, 'Buku yang sangat mengharukan dan membuka mata tentang sejarah perjuangan mahasiswa. Gaya penulisan Leila S. Chudori sangat puitis dan menusuk relung hati.', '2026-10-10 10:15:00'),
                    (4, 2, 5, 'Filosofi Teras benar-benar mengubah cara saya memandang masalah hidup. Sangat praktis untuk meredakan overthinking dan kecemasan sehari-hari!', '2026-10-12 14:30:00'),
                    (5, 2, 5, 'Buku wajib untuk siapa saja yang ingin membangun kebiasaan produktif. Konsep perbaikan 1% setiap hari benar-benar terbukti membawa dampak besar.', '2026-10-14 09:20:00'),
                    (7, 2, 4, 'Buku pegangan wajib bagi software engineer. Memberi wawasan mendalam tentang bagaimana menulis kode yang bersih, mudah dirawat, dan terstruktur rapi.', '2026-10-15 16:45:00')
                ");
            }

            $revStmt = $pdo->query("
                SELECT r.*, u.nama as user_nama, u.foto_profil as user_foto 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                ORDER BY r.id DESC
            ");
            $allReviewsData = $revStmt->fetchAll();
            foreach ($allReviewsData as $rd) {
                $reviewsByBook[$rd['book_id']][] = $rd;
            }
        } catch (PDOException $e) {}

    } catch (PDOException $e) {
        // Database ada tapi tabel mungkin belum di-import
        $dbConnected = false;
    }
}

// Fallback data representatif jika database XAMPP belum di-import
if (!$dbConnected) {
    $categories = [
        ['id' => 1, 'nama_kategori' => 'Fiksi & Sastra', 'slug' => 'fiksi-sastra'],
        ['id' => 2, 'nama_kategori' => 'Pengembangan Diri & Bisnis', 'slug' => 'pengembangan-diri-bisnis'],
        ['id' => 3, 'nama_kategori' => 'Teknologi & Sains', 'slug' => 'teknologi-sains'],
        ['id' => 4, 'nama_kategori' => 'Seni & Desain Kreatif', 'slug' => 'seni-desain-kreatif'],
    ];

    $fallbackBooks = [
        [
            'id' => 1,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Laut Bercerita',
            'penulis' => 'Leila S. Chudori',
            'penerbit' => 'KPG (Kepustakaan Populer Gramedia)',
            'harga' => 115000,
            'stok' => 24,
            'deskripsi' => 'Kisah para aktivis mahasiswa 1998 yang diculik dan disiksa, serta keluarga yang terus setia menanti kepastian.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/10648285-L.jpg'
        ],
        [
            'id' => 2,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Bumi Manusia',
            'penulis' => 'Pramoedya Ananta Toer',
            'penerbit' => 'Lentera Dipantara',
            'harga' => 135000,
            'stok' => 18,
            'deskripsi' => 'Mahakarya Tetralogi Buru mengisahkan pergulatan Minke melawan ketidakadilan feodal dan kolonial Hindia Belanda.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9789799731234-L.jpg'
        ],
        [
            'id' => 3,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Cantik Itu Luka',
            'penulis' => 'Eka Kurniawan',
            'penerbit' => 'Gramedia Pustaka Utama',
            'harga' => 125000,
            'stok' => 22,
            'deskripsi' => 'Realisme magis Indonesia yang merajut tragedi Dewi Ayu dan keturunannya dengan sejarah kelam bangsa.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9786020312583-L.jpg'
        ],
        [
            'id' => 4,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Hujan',
            'penulis' => 'Tere Liye',
            'penerbit' => 'Gramedia Pustaka Utama',
            'harga' => 95000,
            'stok' => 30,
            'deskripsi' => 'Kisah fiksi ilmiah tentang persahabatan, cinta, dan melupakan kenangan di masa depan yang sarat teknologi.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/10872657-L.jpg'
        ],
        [
            'id' => 5,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Laskar Pelangi',
            'penulis' => 'Andrea Hirata',
            'penerbit' => 'Bentang Pustaka',
            'harga' => 89000,
            'stok' => 45,
            'deskripsi' => 'Perjuangan sepuluh anak Belitung mengejar impian pendidikan dengan keterbatasan fasilitas yang mengharukan.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/7079796-L.jpg'
        ],
        [
            'id' => 6,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Ronggeng Dukuh Paruk',
            'penulis' => 'Ahmad Tohari',
            'penerbit' => 'Gramedia Pustaka Utama',
            'harga' => 110000,
            'stok' => 15,
            'deskripsi' => 'Trilogi legendaris tentang Srintil, seorang penari ronggeng di Dukuh Paruk, dan pusaran pergolakan politik 1965.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/4317176-L.jpg'
        ],
        [
            'id' => 7,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Perahu Kertas',
            'penulis' => 'Dee Lestari',
            'penerbit' => 'Bentang Pustaka',
            'harga' => 98000,
            'stok' => 28,
            'deskripsi' => 'Kisah Kugy dan Keenan, dua anak muda yang terperangkap antara realitas karier dan panggilan hati sebagai seniman.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/8705088-L.jpg'
        ],
        [
            'id' => 8,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Supernova: Ksatria, Puteri, dan Bintang Jatuh',
            'penulis' => 'Dee Lestari',
            'penerbit' => 'Bentang Pustaka',
            'harga' => 112000,
            'stok' => 20,
            'deskripsi' => 'Fiksi spekulatif Indonesia yang menggabungkan sains kuantum, filsafat cinta, dan misteri eksistensi manusia.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/10000969-L.jpg'
        ],
        [
            'id' => 9,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Tenggelamnya Kapal Van Der Wijck',
            'penulis' => 'Buya Hamka',
            'penerbit' => 'Bulan Bintang',
            'harga' => 85000,
            'stok' => 35,
            'deskripsi' => 'Kisah cinta tragis Zainuddin dan Hayati yang terhalang benteng adat istiadat Minangkabau.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/10574100-L.jpg'
        ],
        [
            'id' => 10,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'The Great Gatsby',
            'penulis' => 'F. Scott Fitzgerald',
            'penerbit' => 'Scribner',
            'harga' => 145000,
            'stok' => 25,
            'deskripsi' => 'Potret dekadensi dan impian Amerika di era Jazz Age melalui sosok misterius Jay Gatsby.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780743273565-L.jpg'
        ],
        [
            'id' => 11,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => '1984',
            'penulis' => 'George Orwell',
            'penerbit' => 'Signet Classic',
            'harga' => 135000,
            'stok' => 30,
            'deskripsi' => 'Distopia legendaris tentang tirani Big Brother, manipulasi fakta sejarah, dan perlawanan nurani Winston Smith.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780451524935-L.jpg'
        ],
        [
            'id' => 12,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'To Kill a Mockingbird',
            'penulis' => 'Harper Lee',
            'penerbit' => 'Harper Perennial',
            'harga' => 150000,
            'stok' => 20,
            'deskripsi' => 'Kisah kepolosan masa kanak-kanak dan integritas moral Atticus Finch membela keadilan rasial di Alabama.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780060935467-L.jpg'
        ],
        [
            'id' => 13,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'The Alchemist',
            'penulis' => 'Paulo Coelho',
            'penerbit' => 'HarperOne',
            'harga' => 120000,
            'stok' => 40,
            'deskripsi' => 'Kembara magis gembala Andalusia Santiago mengikuti pertanda demi menemukan Harta Karun Takdir hidupnya.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780062315007-L.jpg'
        ],
        [
            'id' => 14,
            'category_id' => 1,
            'nama_kategori' => 'Fiksi & Sastra',
            'judul' => 'Norwegian Wood',
            'penulis' => 'Haruki Murakami',
            'penerbit' => 'Vintage',
            'harga' => 165000,
            'stok' => 16,
            'deskripsi' => 'Nostalgia patah hati, duka kehilangan, dan dinamika asmara anak muda Tokyo tahun 1960-an.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/8750865-L.jpg'
        ],
        [
            'id' => 15,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Filosofi Teras',
            'penulis' => 'Henry Manampiring',
            'penerbit' => 'Penerbit Buku Kompas',
            'harga' => 98000,
            'stok' => 42,
            'deskripsi' => 'Penerapan praktis filsafat Stoa kuno untuk memadamkan emosi negatif dan overthinking generasi masa kini.',
            'cover_image' => 'https://cdn.gramedia.com/uploads/picture_meta/2023/11/27/kjf6cgigkomf6sy9o5qauu.jpg'
        ],
        [
            'id' => 16,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Atomic Habits',
            'penulis' => 'James Clear',
            'penerbit' => 'Gramedia Pustaka Utama',
            'harga' => 128000,
            'stok' => 50,
            'deskripsi' => 'Perubahan kecil 1% setiap hari dengan 4 kaidah hukum pembentukan kebiasaan yang terbukti ilmiah.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'
        ],
        [
            'id' => 17,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'The Psychology of Money',
            'penulis' => 'Morgan Housel',
            'penerbit' => 'BACA',
            'harga' => 105000,
            'stok' => 35,
            'deskripsi' => 'Pelajaran abadi mengenai perilaku manusia memperlakukan uang, ego finansial, dan keserakahan.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780857197689-L.jpg'
        ],
        [
            'id' => 18,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Rich Dad Poor Dad',
            'penulis' => 'Robert T. Kiyosaki',
            'penerbit' => 'Plata Publishing',
            'harga' => 118000,
            'stok' => 38,
            'deskripsi' => 'Perbedaan mendasar cara berpikir orang kaya vs kelas pekerja dalam mengelola aset dan liabilitas.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/12547191-L.jpg'
        ],
        [
            'id' => 19,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Deep Work',
            'penulis' => 'Cal Newport',
            'penerbit' => 'Grand Central Publishing',
            'harga' => 140000,
            'stok' => 25,
            'deskripsi' => 'Aturan sukses untuk fokus tanpa distraksi di era digital demi menghasilkan karya luar biasa.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781455586691-L.jpg'
        ],
        [
            'id' => 20,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Start with Why',
            'penulis' => 'Simon Sinek',
            'penerbit' => 'Portfolio Penguin',
            'harga' => 135000,
            'stok' => 28,
            'deskripsi' => 'Bagaimana pemimpin besar seperti Steve Jobs menginspirasi jutaan orang dengan memulai dari alasan mendasar (Why).',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781591846444-L.jpg'
        ],
        [
            'id' => 21,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Think and Grow Rich',
            'penulis' => 'Napoleon Hill',
            'penerbit' => 'TarcherPerigee',
            'harga' => 100000,
            'stok' => 30,
            'deskripsi' => '13 prinsip kesuksesan finansial hasil riset 500 tokoh terkaya di dunia selama 20 tahun.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781585424337-L.jpg'
        ],
        [
            'id' => 22,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'The 7 Habits of Highly Effective People',
            'penulis' => 'Stephen R. Covey',
            'penerbit' => 'Free Press',
            'harga' => 155000,
            'stok' => 32,
            'deskripsi' => 'Prinsip efektivitas personal dan interpersonal yang mengubah kepribadian menuju kedewasaan sejati.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780743269513-L.jpg'
        ],
        [
            'id' => 23,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Ikigai: The Japanese Secret to a Long and Happy Life',
            'penulis' => 'Héctor García & Francesc Miralles',
            'penerbit' => 'Penguin Life',
            'harga' => 125000,
            'stok' => 45,
            'deskripsi' => 'Rahasia umur panjang dan kepuasan hidup warga desa Okinawa melalui titik temu passion, bakat, dan kontribusi.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/12692257-L.jpg'
        ],
        [
            'id' => 24,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Grit: The Power of Passion and Perseverance',
            'penulis' => 'Angela Duckworth',
            'penerbit' => 'Scribner',
            'harga' => 145000,
            'stok' => 20,
            'deskripsi' => 'Buktian riset psikologi bahwa kegigihan (grit) jauh lebih menentukan pencapaian ketimbang bakat alami.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781501111105-L.jpg'
        ],
        [
            'id' => 25,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Zero to One',
            'penulis' => 'Peter Thiel',
            'penerbit' => 'Crown Business',
            'harga' => 150000,
            'stok' => 26,
            'deskripsi' => 'Catatan penting membangun startup yang menciptakan monopoli kreatif dari nol menjadi pionir.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780804139298-L.jpg'
        ],
        [
            'id' => 26,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'The Lean Startup',
            'penulis' => 'Eric Ries',
            'penerbit' => 'Crown Business',
            'harga' => 160000,
            'stok' => 22,
            'deskripsi' => 'Metodologi Build-Measure-Learn untuk memvalidasi ide bisnis dengan cepat tanpa membuang modal.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780307887894-L.jpg'
        ],
        [
            'id' => 27,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Essentialism: The Disciplined Pursuit of Less',
            'penulis' => 'Greg McKeown',
            'penerbit' => 'Crown Currency',
            'harga' => 138000,
            'stok' => 19,
            'deskripsi' => 'Disiplin mengeliminasi hal yang tidak penting agar dapat mencurahkan energi maksimal pada hal esensial.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780804137386-L.jpg'
        ],
        [
            'id' => 28,
            'category_id' => 2,
            'nama_kategori' => 'Pengembangan Diri & Bisnis',
            'judul' => 'Show Your Work!',
            'penulis' => 'Austin Kleon',
            'penerbit' => 'Workman Publishing',
            'harga' => 92000,
            'stok' => 34,
            'deskripsi' => '10 cara membagikan proses berkaryamu secara konsisten agar ditemukan oleh orang yang tepat.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780761178972-L.jpg'
        ],
        [
            'id' => 29,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
            'penulis' => 'Robert C. Martin',
            'penerbit' => 'Prentice Hall',
            'harga' => 345000,
            'stok' => 15,
            'deskripsi' => 'Pedoman wajib developer merancang kode bersih, elegan, teruji, dan mudah dipelihara.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg'
        ],
        [
            'id' => 30,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'The Pragmatic Programmer: 20th Anniversary Edition',
            'penulis' => 'David Thomas & Andrew Hunt',
            'penerbit' => 'Addison-Wesley',
            'harga' => 380000,
            'stok' => 12,
            'deskripsi' => 'Filosofi profesional perangkat lunak: arsitektur fleksibel, etika engineering, dan otomatisasi.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780135957059-L.jpg'
        ],
        [
            'id' => 31,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Sapiens: Riwayat Singkat Umat Manusia',
            'penulis' => 'Yuval Noah Harari',
            'penerbit' => 'KPG',
            'harga' => 145000,
            'stok' => 28,
            'deskripsi' => 'Sejarah perjalanan Homo sapiens dari makhluk primata biasa hingga mendominasi planet Bumi.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/8741369-L.jpg'
        ],
        [
            'id' => 32,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Homo Deus: A Brief History of Tomorrow',
            'penulis' => 'Yuval Noah Harari',
            'penerbit' => 'Harper',
            'harga' => 165000,
            'stok' => 18,
            'deskripsi' => 'Eksplorasi masa depan manusia di era bio-rekayasa, kecerdasan buatan, dan pencarian keabadian.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780062464316-L.jpg'
        ],
        [
            'id' => 33,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Introduction to Algorithms (CLRS)',
            'penulis' => 'Thomas H. Cormen et al.',
            'penerbit' => 'MIT Press',
            'harga' => 450000,
            'stok' => 10,
            'deskripsi' => 'Kitab suci algoritma dan struktur data dunia komputer dari sorting, graph, hingga pemrograman dinamis.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780262033848-L.jpg'
        ],
        [
            'id' => 34,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Refactoring: Improving the Design of Existing Code',
            'penulis' => 'Martin Fowler',
            'penerbit' => 'Addison-Wesley',
            'harga' => 395000,
            'stok' => 14,
            'deskripsi' => 'Katalog perbaikan struktur kode tanpa mengubah perilaku eksternal agar software tetap awet.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780201485677-L.jpg'
        ],
        [
            'id' => 35,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
            'penulis' => 'Erich Gamma et al. (Gang of Four)',
            'penerbit' => 'Addison-Wesley',
            'harga' => 370000,
            'stok' => 16,
            'deskripsi' => '23 pola desain klasik OOP yang menjadi fondasi arsitektur perangkat lunak modern.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780201633610-L.jpg'
        ],
        [
            'id' => 36,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Code Complete',
            'penulis' => 'Steve McConnell',
            'penerbit' => 'Microsoft Press',
            'harga' => 390000,
            'stok' => 11,
            'deskripsi' => 'Ensiklopedia konstruksi perangkat lunak praktis dan teknik penulisan kode tingkat industri.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780735619678-L.jpg'
        ],
        [
            'id' => 37,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Designing Data-Intensive Applications',
            'penulis' => 'Martin Kleppmann',
            'penerbit' => 'O\'Reilly Media',
            'harga' => 420000,
            'stok' => 15,
            'deskripsi' => 'Buku pegangan wajib backend engineer tentang reliabilitas, skalabilitas, dan data stream terdistribusi.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781449373320-L.jpg'
        ],
        [
            'id' => 38,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'A Brief History of Time',
            'penulis' => 'Stephen Hawking',
            'penerbit' => 'Bantam Books',
            'harga' => 135000,
            'stok' => 35,
            'deskripsi' => 'Penjelasan kosmologi menakjubkan tentang Big Bang, Lubang Hitam, dan misteri ruang waktu.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780553380163-L.jpg'
        ],
        [
            'id' => 39,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Cosmos',
            'penulis' => 'Carl Sagan',
            'penerbit' => 'Ballantine Books',
            'harga' => 155000,
            'stok' => 24,
            'deskripsi' => 'Refleksi puitis Carl Sagan tentang miliaran galaksi dan posisi manusia dalam panggung semesta.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780345539434-L.jpg'
        ],
        [
            'id' => 40,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'Astrophysics for People in a Hurry',
            'penulis' => 'Neil deGrasse Tyson',
            'penerbit' => 'W. W. Norton & Company',
            'harga' => 125000,
            'stok' => 30,
            'deskripsi' => 'Ringkasan astrofisika yang renyah dan cepat dicerna mengenai gravitasi kuantum dan materi gelap.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780393609394-L.jpg'
        ],
        [
            'id' => 41,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'The Selfish Gene',
            'penulis' => 'Richard Dawkins',
            'penerbit' => 'Oxford University Press',
            'harga' => 160000,
            'stok' => 20,
            'deskripsi' => 'Sudut pandang revolusioner evolusi biologi yang menempatkan gen sebagai pusat dorongan seleksi alam.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780199291151-L.jpg'
        ],
        [
            'id' => 42,
            'category_id' => 3,
            'nama_kategori' => 'Teknologi & Sains',
            'judul' => 'You Don\'t Know JS: Scope & Closures',
            'penulis' => 'Kyle Simpson',
            'penerbit' => 'O\'Reilly Media',
            'harga' => 180000,
            'stok' => 25,
            'deskripsi' => 'Bedah tuntas mekanisme lexical scope, closures, dan hoisting dalam mesin JavaScript.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781449335588-L.jpg'
        ],
        [
            'id' => 43,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'The Design of Everyday Things',
            'penulis' => 'Don Norman',
            'penerbit' => 'Basic Books',
            'harga' => 220000,
            'stok' => 20,
            'deskripsi' => 'Prinsip fundamental UX, affordance, dan signifier agar produk dapat dipakai tanpa membingungkan manusia.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780465050659-L.jpg'
        ],
        [
            'id' => 44,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Steal Like an Artist',
            'penulis' => 'Austin Kleon',
            'penerbit' => 'Workman Publishing',
            'harga' => 88000,
            'stok' => 32,
            'deskripsi' => '10 petuah inspiratif bahwa tidak ada ide murni orisinal; pelajari, remiks, dan ciptakan gayamu.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780761169253-L.jpg'
        ],
        [
            'id' => 45,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Don\'t Make Me Think: Revisited',
            'penulis' => 'Steve Krug',
            'penerbit' => 'New Riders',
            'harga' => 210000,
            'stok' => 18,
            'deskripsi' => 'Prinsip kesederhanaan navigasi dan usabilitas website yang intuitif bagi siapa saja.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/7984916-L.jpg'
        ],
        [
            'id' => 46,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Universal Principles of Design',
            'penulis' => 'William Lidwell',
            'penerbit' => 'Rockport Publishers',
            'harga' => 240000,
            'stok' => 15,
            'deskripsi' => '125 prinsip desain lintas disiplin dari Golden Ratio hingga Hukum Fitts.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781592535873-L.jpg'
        ],
        [
            'id' => 47,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Sprint: How to Solve Big Problems in 5 Days',
            'penulis' => 'Jake Knapp',
            'penerbit' => 'Simon & Schuster',
            'harga' => 195000,
            'stok' => 22,
            'deskripsi' => 'Proses 5 hari Google Ventures untuk merancang ide, prototyping cepat, dan menguji langsung ke pengguna.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781501121746-L.jpg'
        ],
        [
            'id' => 48,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Creative Confidence',
            'penulis' => 'Tom Kelley & David Kelley',
            'penerbit' => 'Crown Business',
            'harga' => 185000,
            'stok' => 24,
            'deskripsi' => 'Membangun kembali keberanian berkreasi dan memecahkan masalah kompleks dari pendiri IDEO.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780385349369-L.jpg'
        ],
        [
            'id' => 49,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Grid Systems in Graphic Design',
            'penulis' => 'Josef Müller-Brockmann',
            'penerbit' => 'Niggli',
            'harga' => 310000,
            'stok' => 12,
            'deskripsi' => 'Pedoman legendaris sistem grid Swiss style untuk desainer grafis dan perancang layout tipografi.',
            'cover_image' => 'https://covers.openlibrary.org/b/id/8276709-L.jpg'
        ],
        [
            'id' => 50,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Interaction of Color',
            'penulis' => 'Josef Albers',
            'penerbit' => 'Yale University Press',
            'harga' => 230000,
            'stok' => 14,
            'deskripsi' => 'Eksperimen Bauhaus tentang bagaimana warna berinteraksi dan menipu persepsi mata manusia.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780300179354-L.jpg'
        ],
        [
            'id' => 51,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Hooked: How to Build Habit-Forming Products',
            'penulis' => 'Nir Eyal',
            'penerbit' => 'Portfolio Penguin',
            'harga' => 175000,
            'stok' => 26,
            'deskripsi' => 'Model 4 langkah Hook (Trigger, Action, Variable Reward, Investment) dalam menciptakan produk adiktif.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781591847786-L.jpg'
        ],
        [
            'id' => 52,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'The Non-Designer\'s Design Book',
            'penulis' => 'Robin Williams',
            'penerbit' => 'Peachpit Press',
            'harga' => 165000,
            'stok' => 28,
            'deskripsi' => '4 pilar sakti desain visual: Contrast, Repetition, Alignment, Proximity (CRAP) untuk pemula.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9780321534040-L.jpg'
        ],
        [
            'id' => 53,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Thinking with Type',
            'penulis' => 'Ellen Lupton',
            'penerbit' => 'Princeton Architectural Press',
            'harga' => 225000,
            'stok' => 16,
            'deskripsi' => 'Panduan komprehensif seni tipografi: anatomi huruf, hierarki teks, tracking, dan kerning.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781568989693-L.jpg'
        ],
        [
            'id' => 54,
            'category_id' => 4,
            'nama_kategori' => 'Seni & Desain Kreatif',
            'judul' => 'Layout Essentials: 100 Design Principles',
            'penulis' => 'Beth Tondreau',
            'penerbit' => 'Rockport Publishers',
            'harga' => 195000,
            'stok' => 19,
            'deskripsi' => '100 prinsip esensial tata letak editorial, majalah, poster, dan antarmuka web modern.',
            'cover_image' => 'https://covers.openlibrary.org/b/isbn/9781592537075-L.jpg'
        ],
    ];

    // Filter fallback data jika ada query pencarian atau kategori
    $books = array_filter($fallbackBooks, function($b) use ($search, $selectedCategory) {
        $matchSearch = true;
        if (!empty($search)) {
            $s = strtolower($search);
            $matchSearch = (strpos(strtolower($b['judul']), $s) !== false) ||
                           (strpos(strtolower($b['penulis']), $s) !== false) ||
                           (strpos(strtolower($b['penerbit']), $s) !== false);
        }
        $matchCategory = true;
        if ($selectedCategory > 0) {
            $matchCategory = ($b['category_id'] === $selectedCategory);
        }
        return $matchSearch && $matchCategory;
    });
}

// Fallback ulasan representatif untuk demonstrasi
$fallbackReviews = [
    1 => [
        ['id' => 1, 'book_id' => 1, 'user_id' => 2, 'user_nama' => 'Nasya Reader', 'user_foto' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80', 'rating' => 5, 'komentar' => 'Buku yang sangat mengharukan dan membuka mata tentang sejarah perjuangan mahasiswa. Gaya penulisan Leila S. Chudori sangat puitis dan menusuk relung hati.', 'created_at' => '2026-10-10 10:15:00']
    ],
    4 => [
        ['id' => 2, 'book_id' => 4, 'user_id' => 2, 'user_nama' => 'Nasya Reader', 'user_foto' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80', 'rating' => 5, 'komentar' => 'Filosofi Teras benar-benar mengubah cara saya memandang masalah hidup. Sangat praktis untuk meredakan overthinking dan kecemasan sehari-hari!', 'created_at' => '2026-10-12 14:30:00']
    ],
    5 => [
        ['id' => 3, 'book_id' => 5, 'user_id' => 2, 'user_nama' => 'Nasya Reader', 'user_foto' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80', 'rating' => 5, 'komentar' => 'Buku wajib untuk siapa saja yang ingin membangun kebiasaan produktif. Konsep perbaikan 1% setiap hari benar-benar terbukti membawa dampak besar.', 'created_at' => '2026-10-14 09:20:00']
    ],
    7 => [
        ['id' => 4, 'book_id' => 7, 'user_id' => 2, 'user_nama' => 'Nasya Reader', 'user_foto' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80', 'rating' => 4, 'komentar' => 'Buku pegangan wajib bagi software engineer. Memberi wawasan mendalam tentang bagaimana menulis kode yang bersih, mudah dirawat, dan terstruktur rapi.', 'created_at' => '2026-10-15 16:45:00']
    ]
];

// Pasangkan ulasan & kalkulasi rating bintang rata-rata untuk setiap buku
foreach ($books as &$b) {
    $b['reviews'] = $reviewsByBook[$b['id']] ?? ($fallbackReviews[$b['id']] ?? []);
    $b['review_count'] = count($b['reviews']);
    if ($b['review_count'] > 0) {
        $sum = array_sum(array_column($b['reviews'], 'rating'));
        $b['avg_rating'] = round($sum / $b['review_count'], 1);
    } else {
        $b['avg_rating'] = 5.0; // nilai rating default
    }
}
unset($b);

// =========================================================
// PAGINATION: TAMPILKAN 3 BARIS KATALOG (18 BUKU PER HALAMAN)
// =========================================================
$allBooks = array_values($books);
$totalBooksCount = count($allBooks);

// =========================================================
// LEADERBOARD 5 BUKU TERPOPULER MINGGU INI
// =========================================================
$top5Books = [];
if ($pdo !== null) {
    try {
        $top5Stmt = $pdo->query("
            SELECT b.*, c.nama_kategori,
                   COALESCE(SUM(oi.qty), 0) AS total_terjual,
                   COALESCE(AVG(r.rating), 4.9) AS avg_rating,
                   COUNT(DISTINCT r.id) AS review_count
            FROM books b
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN order_items oi ON b.id = oi.book_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status_pembayaran = 'paid' AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            LEFT JOIN reviews r ON b.id = r.book_id
            GROUP BY b.id
            ORDER BY total_terjual DESC, avg_rating DESC, b.id ASC
            LIMIT 5
        ");
        $top5Books = $top5Stmt->fetchAll();
    } catch (PDOException $e) {}
}

if (empty($top5Books)) {
    // Kurasi 5 buku paling terpopuler untuk showcase leaderboard
    $top5Ids = [16, 1, 15, 29, 17]; // Atomic Habits, Laut Bercerita, Filosofi Teras, Clean Code, Psychology of Money
    $byId = [];
    foreach ($allBooks as $bk) {
        $byId[$bk['id']] = $bk;
    }
    foreach ($top5Ids as $tid) {
        if (isset($byId[$tid])) {
            $tb = $byId[$tid];
            $tb['total_terjual'] = $tb['total_terjual'] ?? rand(38, 92);
            $top5Books[] = $tb;
        }
    }
}

// 18 buku per halaman = tepat 3 baris penuh pada grid 6 kolom desktop
$perPage = 18;
$totalPages = max(1, (int)ceil($totalBooksCount / $perPage));
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
if ($currentPage > $totalPages) $currentPage = $totalPages;

$offset = ($currentPage - 1) * $perPage;
$pagedBooks = array_slice($allBooks, $offset, $perPage);

// Mulai render halaman dengan header
require_once __DIR__ . '/includes/header.php';
?>

<!-- Ambient Background Glow Elements -->
<div class="glow-blob-purple -top-20 -left-20"></div>
<div class="glow-blob-magenta top-60 -right-20"></div>

<?php if (!$dbConnected): ?>
    <!-- Notice Bantuan Database XAMPP (Clean Glassmorphic Banner) -->
    <div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto pt-4 relative z-20">
        <div class="p-4 rounded-2xl glass-card border border-amber-200/80 bg-amber-50/70 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-glass-sm">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs sm:text-sm font-semibold">Database Belum Terhubung ke MySQL</p>
                    <p class="text-xs text-amber-700">Aplikasi sedang menampilkan <strong>data demonstrasi lokal</strong>. Nyalakan MySQL di XAMPP lalu klik tombol Auto Import untuk mengaktifkan database live.</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="import_db.php" class="px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1.5">
                    <span>⚡ Auto Import Database</span>
                </a>
                <span class="text-[11px] font-semibold text-amber-800 bg-amber-200/60 px-3 py-1 rounded-full whitespace-nowrap hidden sm:inline-block">Mode Pratinjau Aman</span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- =========================================================
     HERO BANNER CAROUSEL SECTION
     ========================================================= -->
<section class="relative pt-4 pb-8 lg:pt-6 lg:pb-10 overflow-hidden z-10">
    <div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto">
        
        <!-- Carousel Frame Container -->
        <div id="heroCarouselWrapper" class="relative rounded-3xl overflow-hidden shadow-2xl border border-white/80 bg-slate-950 group select-none">
            
            <!-- Slides Container -->
            <div id="carouselSlidesContainer" class="relative w-full h-[540px] sm:h-[480px] lg:h-[490px] overflow-hidden">
                
                <!-- SLIDE 1: PESTA SASTRA & MAHAIKARYA FIKSI -->
                <div class="carousel-slide active absolute inset-0 w-full h-full flex items-center transition-all duration-700 ease-in-out opacity-100 scale-100 bg-gradient-to-r from-[#170535] via-[#2A0860] to-[#480CA8] text-white p-6 sm:p-10 lg:p-14" data-slide-index="0">
                    <!-- Background ambient glow -->
                    <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-secondary/30 blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-20 -bottom-20 w-72 h-72 rounded-full bg-primary/40 blur-3xl pointer-events-none"></div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center w-full relative z-10">
                        <div class="lg:col-span-7 flex flex-col items-start">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-pink-300 text-[11px] font-extrabold uppercase tracking-wider mb-4">
                                <span class="w-2 h-2 rounded-full bg-secondary animate-ping"></span>
                                <span>Pesta Sastra &amp; Fiksi • Diskon s/d 25%</span>
                            </div>
                            <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight mb-4">
                                Jelajahi Kisah Epik &amp; <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-300 via-purple-200 to-amber-200">Sastra Penggugah Jiwa</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-purple-100/90 leading-relaxed max-w-xl mb-6">
                                Temukan karya-karya abadi dari Leila S. Chudori, Pramoedya Ananta Toer, Eka Kurniawan, dan Tere Liye. Kisah mendalam tentang cinta, sejarah, dan harapan.
                            </p>
                            <div class="flex flex-wrap items-center gap-3 mb-6">
                                <span class="px-3 py-1.5 rounded-xl bg-secondary/30 border border-secondary/50 text-white font-mono font-bold text-xs">
                                    Kode: BLOOM25
                                </span>
                                <span class="text-[11px] text-purple-200 font-medium">Potongan langsung saat checkout</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="index.php?kategori=1#katalog" class="px-6 py-3 rounded-full bg-gradient-to-r from-secondary to-pink-600 hover:opacity-95 text-white font-bold text-xs sm:text-sm shadow-lg shadow-secondary/30 transition-all flex items-center gap-2">
                                    <span>Jelajahi Novel Fiksi</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                                <a href="#katalog" class="px-5 py-3 rounded-full bg-white/10 hover:bg-white/20 text-white font-semibold text-xs sm:text-sm backdrop-blur-md border border-white/20 transition-all">
                                    Semua Koleksi
                                </a>
                            </div>
                        </div>

                        <!-- Right Visual Floating Books (Real Covers with Seamless Aspect Ratio & 3D Lighting) -->
                        <div class="lg:col-span-5 hidden lg:flex justify-center items-center relative">
                            <div class="relative w-80 h-80 flex items-center justify-center">
                                <!-- Book 1: Laut Bercerita -->
                                <div class="absolute -left-3 transform -rotate-6 hover:rotate-0 transition-transform duration-500 z-10">
                                    <div class="w-36 h-52 sm:w-38 sm:h-56 rounded-xl overflow-hidden shadow-2xl border-2 border-white/25 bg-slate-900 group/bk">
                                        <img src="https://covers.openlibrary.org/b/id/10648285-L.jpg" alt="Laut Bercerita" class="w-full h-full object-cover group-hover/bk:scale-105 transition-transform duration-300" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                                    </div>
                                </div>
                                <!-- Book 2 (Center): Cantik Itu Luka -->
                                <div class="transform scale-105 z-20 hover:scale-110 transition-transform duration-500 shadow-2xl">
                                    <div class="w-40 h-60 rounded-2xl overflow-hidden shadow-2xl border-2 border-white/50 bg-slate-900 relative group/bk">
                                        <img src="https://covers.openlibrary.org/b/isbn/9786020312583-L.jpg" alt="Cantik Itu Luka" class="w-full h-full object-cover group-hover/bk:scale-105 transition-transform duration-300" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                                        <div class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full bg-secondary text-white text-[9px] font-extrabold uppercase shadow-md tracking-wider">Bestseller</div>
                                    </div>
                                </div>
                                <!-- Book 3: Bumi Manusia -->
                                <div class="absolute -right-3 transform rotate-6 hover:rotate-0 transition-transform duration-500 z-10">
                                    <div class="w-36 h-52 sm:w-38 sm:h-56 rounded-xl overflow-hidden shadow-2xl border-2 border-white/25 bg-slate-900 group/bk">
                                        <img src="https://covers.openlibrary.org/b/isbn/9789799731234-L.jpg" alt="Bumi Manusia" class="w-full h-full object-cover group-hover/bk:scale-105 transition-transform duration-300" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SLIDE 2: PENGEMBANGAN DIRI & FINANSIAL -->
                <div class="carousel-slide inactive absolute inset-0 w-full h-full flex items-center transition-all duration-700 ease-in-out opacity-0 scale-95 pointer-events-none bg-gradient-to-r from-[#071E19] via-[#0E3D35] to-[#1E085A] text-white p-6 sm:p-10 lg:p-14" data-slide-index="1">
                    <div class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full bg-emerald-500/20 blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-20 -top-20 w-72 h-72 rounded-full bg-indigo-500/30 blur-3xl pointer-events-none"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center w-full relative z-10">
                        <div class="lg:col-span-7 flex flex-col items-start">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/20 backdrop-blur-md border border-emerald-400/30 text-emerald-300 text-[11px] font-extrabold uppercase tracking-wider mb-4">
                                <span>💡 Upgrade Pola Pikir &amp; Finansial</span>
                            </div>
                            <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight mb-4">
                                Ubah Kebiasaan Kecil Jadi <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-amber-200">Lompatan Hidup Besar</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed max-w-xl mb-6">
                                Kuasai konsistensi 1% tiap hari dan kemerdekaan finansial dengan buku pegangan nomor wahid: Atomic Habits, Filosofi Teras, dan The Psychology of Money.
                            </p>
                            <div class="flex items-center gap-4 mb-6">
                                <div class="flex items-center gap-1.5 text-amber-300 text-xs font-bold">
                                    <span>★★★★★</span>
                                    <span class="text-white text-[11px]">4.9 / 5 Rating Pembaca</span>
                                </div>
                                <span class="text-slate-400">&bull;</span>
                                <span class="text-[11px] text-emerald-200 font-medium">2.500+ Eksemplar Terjual</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="index.php?kategori=2#katalog" class="px-6 py-3 rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:opacity-95 text-white font-bold text-xs sm:text-sm shadow-lg shadow-emerald-900/40 transition-all flex items-center gap-2">
                                    <span>Buku Pengembangan Diri</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                                <button type="button" onclick="if(window.toggleChatbot) window.toggleChatbot(true); else document.getElementById('chatbotToggleBtn').click();" class="px-5 py-3 rounded-full bg-white/10 hover:bg-white/20 text-white font-semibold text-xs sm:text-sm backdrop-blur-md border border-white/20 transition-all flex items-center gap-2">
                                    <span>🤖 Tanya BloomBot AI</span>
                                </button>
                            </div>
                        </div>

                        <!-- Right Visual Floating Books (Real Covers) -->
                        <div class="lg:col-span-5 hidden lg:flex justify-center items-center relative">
                            <div class="relative w-80 h-80 flex items-center justify-center">
                                <!-- Book 1: Filosofi Teras -->
                                <div class="absolute -left-3 transform -rotate-6 hover:rotate-0 transition-transform duration-500 z-10">
                                    <div class="w-36 h-52 sm:w-38 sm:h-56 rounded-xl overflow-hidden shadow-2xl border-2 border-white/25 bg-slate-900 group/bk">
                                        <img src="https://cdn.gramedia.com/uploads/picture_meta/2023/11/27/kjf6cgigkomf6sy9o5qauu.jpg" alt="Filosofi Teras" class="w-full h-full object-cover group-hover/bk:scale-105 transition-transform duration-300" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                                    </div>
                                </div>
                                <!-- Book 2 (Center): Atomic Habits -->
                                <div class="transform scale-105 z-20 hover:scale-110 transition-transform duration-500 shadow-2xl">
                                    <div class="w-40 h-60 rounded-2xl overflow-hidden shadow-2xl border-2 border-white/50 bg-slate-900 relative group/bk">
                                        <img src="https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg" alt="Atomic Habits" class="w-full h-full object-cover group-hover/bk:scale-105 transition-transform duration-300" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                                        <div class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full bg-emerald-500 text-white text-[9px] font-extrabold uppercase shadow-md tracking-wider">Mega Best</div>
                                    </div>
                                </div>
                                <!-- Book 3: Psychology of Money -->
                                <div class="absolute -right-3 transform rotate-6 hover:rotate-0 transition-transform duration-500 z-10">
                                    <div class="w-36 h-52 sm:w-38 sm:h-56 rounded-xl overflow-hidden shadow-2xl border-2 border-white/25 bg-slate-900 group/bk">
                                        <img src="https://covers.openlibrary.org/b/isbn/9780857197689-L.jpg" alt="Psychology of Money" class="w-full h-full object-cover group-hover/bk:scale-105 transition-transform duration-300" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SLIDE 3: TEKNOLOGI & SAINS MASA DEPAN -->
                <div class="carousel-slide inactive absolute inset-0 w-full h-full flex items-center transition-all duration-700 ease-in-out opacity-0 scale-95 pointer-events-none bg-gradient-to-r from-[#080E24] via-[#0D1B44] to-[#3A0CA3] text-white p-6 sm:p-10 lg:p-14" data-slide-index="2">
                    <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-20 -bottom-20 w-72 h-72 rounded-full bg-primary/40 blur-3xl pointer-events-none"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center w-full relative z-10">
                        <div class="lg:col-span-7 flex flex-col items-start">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-500/20 backdrop-blur-md border border-cyan-400/30 text-cyan-300 text-[11px] font-extrabold uppercase tracking-wider mb-4">
                                <span>💻 Coding, Teknologi &amp; Sains</span>
                            </div>
                            <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight mb-4">
                                Tulis Kode Bersih, Kuasai <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 via-sky-200 to-indigo-200">Sains &amp; Software Craft</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-cyan-100/90 leading-relaxed max-w-xl mb-6">
                                Bekal wajib software engineer dan pengembang modern: Clean Code, The Pragmatic Programmer, serta penjelajahan sejarah peradaban manusia Sapiens.
                            </p>
                            <div class="flex flex-wrap items-center gap-3 mb-6">
                                <span class="px-3 py-1 rounded-xl bg-white/10 text-cyan-300 text-[11px] font-semibold border border-cyan-500/30">Pedoman Tech Lead &amp; Engineer</span>
                                <span class="px-3 py-1 rounded-xl bg-white/10 text-purple-300 text-[11px] font-semibold border border-purple-500/30">100% Edisi Asli</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="index.php?kategori=3#katalog" class="px-6 py-3 rounded-full bg-gradient-to-r from-cyan-500 to-blue-600 hover:opacity-95 text-white font-bold text-xs sm:text-sm shadow-lg shadow-cyan-900/40 transition-all flex items-center gap-2">
                                    <span>Koleksi Teknologi &amp; Sains</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                                <a href="#katalog" class="px-5 py-3 rounded-full bg-white/10 hover:bg-white/20 text-white font-semibold text-xs sm:text-sm backdrop-blur-md border border-white/20 transition-all">
                                    Katalog Lengkap
                                </a>
                            </div>
                        </div>

                        <!-- Right Visual Floating Books (Real Covers) -->
                        <div class="lg:col-span-5 hidden lg:flex justify-center items-center relative">
                            <div class="relative w-80 h-80 flex items-center justify-center">
                                <!-- Book 1: Clean Code -->
                                <div class="absolute -left-3 transform -rotate-6 hover:rotate-0 transition-transform duration-500 z-10">
                                    <div class="w-36 h-52 sm:w-38 sm:h-56 rounded-xl overflow-hidden shadow-2xl border-2 border-white/25 bg-slate-900 group/bk">
                                        <img src="https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg" alt="Clean Code" class="w-full h-full object-cover group-hover/bk:scale-105 transition-transform duration-300" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                                    </div>
                                </div>
                                <!-- Book 2 (Center): Pragmatic Programmer -->
                                <div class="transform scale-105 z-20 hover:scale-110 transition-transform duration-500 shadow-2xl">
                                    <div class="w-40 h-60 rounded-2xl overflow-hidden shadow-2xl border-2 border-white/50 bg-slate-900 relative group/bk">
                                        <img src="https://covers.openlibrary.org/b/isbn/9780135957059-L.jpg" alt="The Pragmatic Programmer" class="w-full h-full object-cover group-hover/bk:scale-105 transition-transform duration-300" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                                        <div class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full bg-blue-600 text-white text-[9px] font-extrabold uppercase shadow-md tracking-wider">Wajib Baca</div>
                                    </div>
                                </div>
                                <!-- Book 3: Sapiens -->
                                <div class="absolute -right-3 transform rotate-6 hover:rotate-0 transition-transform duration-500 z-10">
                                    <div class="w-36 h-52 sm:w-38 sm:h-56 rounded-xl overflow-hidden shadow-2xl border-2 border-white/25 bg-slate-900 group/bk">
                                        <img src="https://covers.openlibrary.org/b/id/8741369-L.jpg" alt="Sapiens" class="w-full h-full object-cover group-hover/bk:scale-105 transition-transform duration-300" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SLIDE 4: JAMINAN 100% ASLI & PENGIRIMAN AMAN (INFO KEUNGGULAN TERINTEGRASI) -->
                <div class="carousel-slide inactive absolute inset-0 w-full h-full flex items-center transition-all duration-700 ease-in-out opacity-0 scale-95 pointer-events-none bg-gradient-to-r from-[#42042E] via-[#65077E] to-[#F72585] text-white p-6 sm:p-10 lg:p-14" data-slide-index="3">
                    <div class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full bg-amber-400/20 blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-20 -top-20 w-72 h-72 rounded-full bg-primary/40 blur-3xl pointer-events-none"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center w-full relative z-10">
                        <div class="lg:col-span-6 flex flex-col items-start">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-amber-300 text-[11px] font-extrabold uppercase tracking-wider mb-4">
                                <span>🛡️ Jaminan 100% Original &amp; Layanan Prioritas</span>
                            </div>
                            <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight mb-4">
                                Belanja Buku Tenang, <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-pink-200 to-white">Pasti Asli &amp; Bergaransi</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-pink-100/90 leading-relaxed max-w-xl mb-6">
                                Katakan tidak pada buku bajakan. Setiap pengiriman buku di Bloom&amp;Books dirawat dengan 3 lapisan proteksi bubble wrap, kardus pelindung gratis, dan garansi retur tukar baru 7 hari jika terdapat cacat cetak.
                            </p>
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="#katalog" class="px-6 py-3 rounded-full bg-gradient-to-r from-amber-400 to-amber-500 hover:opacity-95 text-slate-900 font-extrabold text-xs sm:text-sm shadow-lg shadow-amber-950/30 transition-all flex items-center gap-2">
                                    <span>Mulai Belanja Sekarang</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                                <a href="#keunggulan" class="px-5 py-3 rounded-full bg-white/10 hover:bg-white/20 text-white font-semibold text-xs sm:text-sm backdrop-blur-md border border-white/20 transition-all">
                                    Standar Mutu Buku
                                </a>
                            </div>
                        </div>

                        <!-- Right Visual: 4 Key Value Highlights Cards Seamlessly Integrated into Carousel -->
                        <div class="lg:col-span-6 hidden lg:flex justify-center items-center">
                            <div class="grid grid-cols-2 gap-3.5 w-full max-w-md">
                                <!-- Highlight 1: 100% Original -->
                                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-xl border border-white/25 hover:bg-white/20 transition-all shadow-xl group">
                                    <div class="w-10 h-10 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center mb-2.5 group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-white text-sm leading-snug">100% Original</h4>
                                    <p class="text-[11px] text-pink-100/80 mt-1 leading-relaxed">Buku asli langsung dari penerbit resmi tanpa kompromi.</p>
                                </div>

                                <!-- Highlight 2: Packaging Aman -->
                                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-xl border border-white/25 hover:bg-white/20 transition-all shadow-xl group">
                                    <div class="w-10 h-10 rounded-xl bg-pink-400/20 text-pink-200 flex items-center justify-center mb-2.5 group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-white text-sm leading-snug">Packaging Aman</h4>
                                    <p class="text-[11px] text-pink-100/80 mt-1 leading-relaxed">Ekstra tebal bubble wrap + box karton pelindung sudut.</p>
                                </div>

                                <!-- Highlight 3: Garansi 7 Hari -->
                                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-xl border border-white/25 hover:bg-white/20 transition-all shadow-xl group">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-400/20 text-emerald-300 flex items-center justify-center mb-2.5 group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-white text-sm leading-snug">Garansi 7 Hari</h4>
                                    <p class="text-[11px] text-pink-100/80 mt-1 leading-relaxed">Jaminan tukar buku baru gratis jika terdapat cacat cetak.</p>
                                </div>

                                <!-- Highlight 4: BloomBot AI 24/7 -->
                                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-xl border border-white/25 hover:bg-white/20 transition-all shadow-xl group">
                                    <div class="w-10 h-10 rounded-xl bg-purple-400/20 text-purple-200 flex items-center justify-center mb-2.5 group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-white text-sm leading-snug">BloomBot AI 24/7</h4>
                                    <p class="text-[11px] text-pink-100/80 mt-1 leading-relaxed">Asisten cerdas siap bantu cek stok dan beri rekomendasi.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Controls: Prev & Next Navigation Buttons -->
            <button id="carouselPrevBtn" type="button" aria-label="Slide Sebelumnya" 
                class="absolute left-3 sm:left-5 top-1/2 -translate-y-1/2 w-11 h-11 rounded-2xl bg-white/15 hover:bg-white/35 text-white backdrop-blur-md border border-white/25 flex items-center justify-center transition-all opacity-85 hover:opacity-100 hover:scale-105 active:scale-95 shadow-xl z-20 focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <button id="carouselNextBtn" type="button" aria-label="Slide Selanjutnya" 
                class="absolute right-3 sm:right-5 top-1/2 -translate-y-1/2 w-11 h-11 rounded-2xl bg-white/15 hover:bg-white/35 text-white backdrop-blur-md border border-white/25 flex items-center justify-center transition-all opacity-85 hover:opacity-100 hover:scale-105 active:scale-95 shadow-xl z-20 focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
            </button>

            <!-- Slide Indicators (Dots / Active Bars) -->
            <div id="carouselIndicators" class="absolute bottom-5 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
                <button type="button" class="indicator-dot w-8 h-2 rounded-full bg-white transition-all duration-300" data-slide-to="0" aria-label="Slide 1"></button>
                <button type="button" class="indicator-dot w-2.5 h-2 rounded-full bg-white/40 hover:bg-white/70 transition-all duration-300" data-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" class="indicator-dot w-2.5 h-2 rounded-full bg-white/40 hover:bg-white/70 transition-all duration-300" data-slide-to="2" aria-label="Slide 3"></button>
                <button type="button" class="indicator-dot w-2.5 h-2 rounded-full bg-white/40 hover:bg-white/70 transition-all duration-300" data-slide-to="3" aria-label="Slide 4"></button>
            </div>

            <!-- Slide Counter Pill (Top-Right) -->
            <div class="absolute top-5 right-5 hidden sm:flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/40 backdrop-blur-md border border-white/20 text-white text-[11px] font-bold z-20">
                <span id="currentSlideNum" class="text-pink-300">01</span>
                <span class="opacity-40">/</span>
                <span class="opacity-75">04</span>
            </div>

        </div>

    </div>
</section>

<!-- =========================================================
     TOP 5 BUKU TERPOPULER MINGGU INI (LEADERBOARD SHOWCASE)
     ========================================================= -->
<?php if (!empty($top5Books) && empty($search)): ?>
<section class="py-6 relative z-10">
    <div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto">
        
        <!-- Header Leaderboard -->
        <div class="flex items-center justify-between mb-5">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-xs font-extrabold uppercase tracking-widest text-secondary">Trending Pekan Ini</span>
                </div>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900 mt-0.5">Top 5 Buku Terpopuler</h2>
            </div>
            <a href="leaderboard.php" class="group inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl glass-card border border-white hover:border-primary/40 text-primary font-bold text-xs shadow-sm transition-all hover:scale-105">
                <span>Peringkat Lengkap</span>
                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <!-- Horizontal Scrollable Cards (Matching Design Attachment) -->
        <div class="relative">
            <div class="flex items-center gap-4 sm:gap-5 overflow-x-auto pb-4 pt-1 px-1 scrollbar-thin scrollbar-thumb-purple-200" style="-webkit-overflow-scrolling: touch;">
                <?php 
                $cardGradients = [
                    0 => 'bg-gradient-to-br from-[#38bdf8] via-[#6366f1] to-[#818cf8]', // Blue Sky - Indigo
                    1 => 'bg-gradient-to-br from-[#818cf8] via-[#a855f7] to-[#c084fc]', // Purple Lavender
                    2 => 'bg-gradient-to-br from-[#f43f5e] via-[#fb7185] to-[#fda4af]', // Coral Rose
                    3 => 'bg-gradient-to-br from-[#10b981] via-[#14b8a6] to-[#06b6d4]', // Emerald Teal
                    4 => 'bg-gradient-to-br from-[#f59e0b] via-[#fb923c] to-[#f43f5e]', // Sunset Amber
                ];
                $readerAvatars = [
                    0 => ['https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=60&auto=format&fit=crop&q=80', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=60&auto=format&fit=crop&q=80'],
                    1 => ['https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60&auto=format&fit=crop&q=80', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=60&auto=format&fit=crop&q=80'],
                    2 => ['https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=60&auto=format&fit=crop&q=80', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=60&auto=format&fit=crop&q=80'],
                    3 => ['https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=60&auto=format&fit=crop&q=80', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=60&auto=format&fit=crop&q=80'],
                    4 => ['https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=60&auto=format&fit=crop&q=80', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=60&auto=format&fit=crop&q=80'],
                ];

                foreach ($top5Books as $rankIdx => $tb):
                    $grad = $cardGradients[$rankIdx % 5];
                    $avs = $readerAvatars[$rankIdx % 5];
                    $terjual = max(18, (int)($tb['total_terjual'] ?? 24));
                ?>
                    <div class="flex-shrink-0 w-[310px] sm:w-[360px] rounded-3xl p-4 sm:p-5 <?= $grad ?> shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1.5 cursor-pointer flex items-center gap-4 text-white relative overflow-hidden group select-none border border-white/20"
                         onclick="openBookDetail(<?= htmlspecialchars(json_encode($tb), ENT_QUOTES, 'UTF-8') ?>)">
                        
                        <!-- Ambient soft blur in background -->
                        <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/20 blur-2xl pointer-events-none"></div>

                        <!-- Left: Book Cover with realistic 3D shadow & Rank Badge -->
                        <div class="relative shrink-0">
                            <div class="w-24 h-36 sm:w-28 sm:h-40 rounded-xl overflow-hidden shadow-2xl border-2 border-white/40 bg-slate-900 group-hover:scale-105 transition-transform duration-300">
                                <img src="<?= htmlspecialchars($tb['cover_image']) ?>" alt="<?= htmlspecialchars($tb['judul']) ?>" class="w-full h-full object-cover" onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'">
                            </div>
                            <!-- Rank Badge Pill -->
                            <div class="absolute -top-2.5 -left-2.5 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white text-slate-900 font-extrabold text-xs flex items-center justify-center shadow-lg border-2 border-amber-300">
                                <span>#<?= $rankIdx + 1 ?></span>
                            </div>
                        </div>

                        <!-- Right: Info Details -->
                        <div class="flex-grow min-w-0 flex flex-col justify-between h-36 sm:h-40 py-0.5">
                            <div>
                                <span class="inline-block px-2 py-0.5 rounded-full bg-white/20 backdrop-blur-md text-[9px] sm:text-[10px] font-bold uppercase tracking-wider mb-1">
                                    <?= htmlspecialchars($tb['nama_kategori'] ?? 'Favorit') ?>
                                </span>
                                <h3 class="font-serif font-bold text-sm sm:text-base leading-snug line-clamp-2 text-white drop-shadow-sm group-hover:underline decoration-white/70" title="<?= htmlspecialchars($tb['judul']) ?>">
                                    <?= htmlspecialchars($tb['judul']) ?>
                                </h3>
                                <p class="text-xs text-white/80 truncate mt-0.5 font-medium">
                                    Oleh <?= htmlspecialchars($tb['penulis']) ?>
                                </p>
                            </div>

                            <div>
                                <!-- Rating Stars -->
                                <div class="flex items-center gap-1 mb-2">
                                    <div class="flex text-amber-200 text-xs">
                                        ★★★★★
                                    </div>
                                    <span class="text-[11px] font-bold text-white/95 ml-1"><?= number_format($tb['avg_rating'] ?? 4.9, 1) ?></span>
                                </div>

                                <!-- Avatars + Reader Count -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center -space-x-1.5">
                                        <img class="w-6 h-6 rounded-full border-2 border-white object-cover" src="<?= $avs[0] ?>" alt="Reader">
                                        <img class="w-6 h-6 rounded-full border-2 border-white object-cover" src="<?= $avs[1] ?>" alt="Reader">
                                        <span class="w-6 h-6 rounded-full bg-white text-primary text-[9px] font-extrabold flex items-center justify-center border-2 border-white shadow-sm">
                                            +<?= $terjual ?>
                                        </span>
                                    </div>

                                    <span class="px-2.5 py-1 rounded-xl bg-white/20 backdrop-blur-md text-[10px] font-bold text-white shadow-sm flex items-center gap-1 group-hover:bg-white group-hover:text-slate-900 transition-colors">
                                        <span>Beli</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>
<?php endif; ?>

<?php if (!empty($search)): ?>
    <!-- Notice Hasil Pencarian dari Navbar -->
    <div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto mb-4">
        <div class="p-4 rounded-2xl glass-card border border-primary/20 bg-primary/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-primary text-white flex items-center justify-center font-bold text-sm shrink-0">
                    🔍
                </div>
                <div>
                    <p class="text-xs sm:text-sm text-slate-800">
                        Menampilkan hasil pencarian untuk: <strong class="text-primary font-bold">"<?= htmlspecialchars($search) ?>"</strong>
                    </p>
                    <p class="text-[11px] text-slate-500">Ditemukan <strong class="text-primary font-semibold"><?= count($books) ?></strong> buku yang sesuai di katalog.</p>
                </div>
            </div>
            <a href="index.php#katalog" class="px-3.5 py-1.5 rounded-xl bg-white text-rose-600 hover:bg-rose-50 text-xs font-bold border border-rose-200 transition-colors shadow-sm flex items-center gap-1.5 shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>Reset Pencarian</span>
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- =========================================================
     KATALOG BUKU SECTION (DENGAN FILTER KATEGORI TERINTEGRASI)
     ========================================================= -->
<section id="katalog" class="pt-4 pb-12 relative z-10">
    <div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto">
        
        <!-- Section Header & Integrated Category Filter Bar -->
        <div class="glass-card rounded-3xl p-5 sm:p-6 mb-8 border border-white/80 shadow-glass-sm flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-secondary block mb-1">Kurasi Bloom&amp;Books</span>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900">Katalog Pilihan</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Menampilkan <span class="font-semibold text-primary"><?= count($books) ?></span> judul buku berkualitas siap memperluas wawasanmu.
                </p>
            </div>

            <!-- Integrated Category Filter Pills -->
            <div class="flex flex-wrap items-center gap-2">
                <a 
                    href="index.php#katalog" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($selectedCategory == 0) ? 'bg-primary text-white shadow-md shadow-primary/25 scale-[1.02]' : 'bg-white/80 text-slate-600 hover:text-primary hover:bg-white border border-slate-200/60' ?>"
                >
                    Semua Koleksi
                </a>

                <?php foreach ($categories as $cat): ?>
                    <a 
                        href="index.php?kategori=<?= $cat['id'] ?>#katalog" 
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= ($selectedCategory == $cat['id']) ? 'bg-primary text-white shadow-md shadow-primary/25 scale-[1.02]' : 'bg-white/80 text-slate-600 hover:text-primary hover:bg-white border border-slate-200/60' ?>"
                    >
                        <?= htmlspecialchars($cat['nama_kategori']) ?>
                    </a>
                <?php endforeach; ?>

                <?php if (!empty($search) || $selectedCategory > 0): ?>
                    <a 
                        href="index.php#katalog" 
                        class="text-xs text-rose-500 hover:text-rose-700 font-bold px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 transition-colors flex items-center gap-1 border border-rose-200"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span>Reset</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        // Category color map for badge styling
        $catColors = [
            'Fiksi & Sastra'               => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'dot' => 'bg-purple-500'],
            'Pengembangan Diri & Bisnis'    => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
            'Teknologi & Sains'             => ['bg' => 'bg-sky-100', 'text' => 'text-sky-700', 'dot' => 'bg-sky-500'],
            'Seni & Desain Kreatif'         => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500'],
        ];
        $badges = [
            1 => 'Bestseller',
            2 => 'Mahakarya',
            4 => 'Terlaris',
            5 => 'Mega Hit',
            7 => 'Klasik',
            10 => 'Top Favorit',
            15 => 'Staff Pick',
            16 => 'Mega Hit',
            17 => 'Bestseller',
            18 => 'Terlaris',
            23 => 'Favorit',
            29 => 'Wajib Baca',
            30 => 'Pilihan Editor',
            31 => 'Bestseller',
            33 => 'Legendaris',
            43 => 'Top Design',
            44 => 'Inspiratif',
            45 => 'UX Favorit',
        ];
        ?>
        <?php if (empty($books)): ?>
            <!-- Empty State -->
            <div class="glass-card rounded-3xl p-12 text-center max-w-lg mx-auto border border-white">
                <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"></circle><line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2" stroke-linecap="round"></line></svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-slate-800 mb-2">Buku Tidak Ditemukan</h3>
                <p class="text-xs text-slate-500 mb-6">Kami tidak menemukan buku yang cocok dengan kata kunci atau kategori yang Anda pilih.</p>
                <a href="index.php#katalog" class="px-5 py-2.5 rounded-full bg-primary text-white text-xs font-semibold hover:bg-primary-hover transition-colors">
                    Kembali ke Seluruh Buku
                </a>
            </div>
        <?php else: ?>
            <!-- ======= BOOKS GRID (3 BARIS PERSIS: 6 KOLOM X 3 BARIS) ======= -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
                <?php foreach ($pagedBooks as $idx => $book):
                    $cat = $book['nama_kategori'] ?? '';
                    $color = $catColors[$cat] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'dot' => 'bg-slate-400'];
                    $badge = $badges[$book['id']] ?? null;
                    $isLowStock = ($book['stok'] > 0 && $book['stok'] <= 5);
                    $isOutOfStock = ($book['stok'] <= 0);
                ?>
                    <div class="group relative bg-white rounded-2xl overflow-hidden border border-slate-100/90 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer flex flex-col"
                         onclick="openBookDetail(<?= htmlspecialchars(json_encode($book), ENT_QUOTES, 'UTF-8') ?>)">

                        <!-- Cover Image Container — proporsi lebih ramping & panjang kebawah (height 220px) dengan latar putih & efek bayangan fisik buku -->
                        <div class="relative bg-white flex items-center justify-center overflow-hidden p-2.5" style="height: 220px;">
                            <img
                                src="<?= htmlspecialchars($book['cover_image']) ?>"
                                alt="<?= htmlspecialchars($book['judul']) ?>"
                                class="h-full max-h-[195px] w-auto max-w-[88%] object-contain rounded-sm shadow-md group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                                onerror="this.src='https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'"
                            >

                            <!-- Category Badge top-left -->
                            <div class="absolute top-2 left-2 pointer-events-none">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold <?= $color['bg'] ?> <?= $color['text'] ?> shadow-sm">
                                    <span class="w-1 h-1 rounded-full <?= $color['dot'] ?>"></span>
                                    <?= htmlspecialchars(mb_strimwidth($cat, 0, 11, '..')) ?>
                                </span>
                            </div>

                            <!-- Special badge top-right -->
                            <?php if ($badge): ?>
                            <div class="absolute top-2 right-2 pointer-events-none">
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-extrabold bg-secondary text-white shadow uppercase tracking-wider">
                                    <?= $badge ?>
                                </span>
                            </div>
                            <?php endif; ?>

                            <!-- Stock warning -->
                            <?php if ($isOutOfStock): ?>
                            <div class="absolute bottom-2 left-2 pointer-events-none">
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-600 text-white shadow-sm">Habis</span>
                            </div>
                            <?php elseif ($isLowStock): ?>
                            <div class="absolute bottom-2 left-2 pointer-events-none">
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-500 text-white shadow-sm">Terbatas</span>
                            </div>
                            <?php endif; ?>

                            <!-- Quick view overlay on hover -->
                            <div class="absolute inset-0 bg-slate-900/10 backdrop-blur-[1px] transition-colors duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <div class="bg-white/95 rounded-full px-3 py-1.5 flex items-center gap-1.5 shadow-lg text-[10px] font-bold text-slate-800">
                                    <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Detail</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body (Lebih ramping & elegan) -->
                        <div class="p-3 flex flex-col flex-1 border-t border-slate-50">
                            <p class="text-[9px] sm:text-[10px] font-semibold text-slate-400 uppercase tracking-wider truncate mb-1">
                                <?= htmlspecialchars($book['penulis']) ?>
                            </p>
                            <h3 class="font-serif font-bold text-xs sm:text-sm text-slate-900 group-hover:text-primary transition-colors line-clamp-2 leading-snug mb-1.5 min-h-[2rem]" title="<?= htmlspecialchars($book['judul']) ?>">
                                <?= htmlspecialchars($book['judul']) ?>
                            </h3>

                            <!-- Stars Rating -->
                            <div class="flex items-center gap-0.5 mb-2.5">
                                <?php $r = round($book['avg_rating'] ?? 5); for ($s = 1; $s <= 5; $s++): ?>
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 <?= $s <= $r ? 'text-amber-400 fill-amber-400' : 'text-slate-200 fill-slate-200' ?>" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <?php endfor; ?>
                                <span class="text-[9px] sm:text-[10px] text-slate-400 ml-1 font-medium">(<?= $book['review_count'] ?? 0 ?>)</span>
                            </div>

                            <!-- Price + Action Button -->
                            <div class="mt-auto flex items-center justify-between gap-1.5 pt-2 border-t border-slate-100/70" onclick="event.stopPropagation()">
                                <div>
                                    <span class="text-[9px] text-slate-400 uppercase font-bold block leading-none mb-0.5">Harga</span>
                                    <span class="text-xs sm:text-sm font-extrabold text-primary">
                                        <?= formatRupiah($book['harga']) ?>
                                    </span>
                                </div>
                                <?php if ($isOutOfStock): ?>
                                    <button disabled class="px-2 py-1 rounded-xl bg-slate-100 text-slate-400 text-[9px] font-semibold cursor-not-allowed">Habis</button>
                                <?php else: ?>
                                    <div class="flex items-center gap-1">
                                        <!-- Tombol Cepat Masukkan ke Keranjang -->
                                        <?php if (isLoggedIn()): ?>
                                            <form action="cart.php" method="POST" class="inline m-0">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                                                <input type="hidden" name="qty" value="1">
                                                <input type="hidden" name="redirect" value="index.php#katalog">
                                                <button
                                                    type="submit"
                                                    title="Masukkan ke Keranjang"
                                                    class="w-7 h-7 rounded-lg border border-primary/30 text-primary hover:bg-primary hover:text-white flex items-center justify-center transition-colors"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Tombol Beli / Lihat Detail -->
                                        <button
                                            type="button"
                                            onclick="openBookDetail(<?= htmlspecialchars(json_encode($book), ENT_QUOTES, 'UTF-8') ?>)"
                                            class="px-2.5 py-1.5 rounded-lg bg-primary hover:bg-primary-hover text-white text-[10px] font-bold transition-all flex items-center gap-1 shrink-0 shadow-sm"
                                            title="Lihat Detail & Beli Sekarang"
                                        >
                                            <span>Beli</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- =========================================================
                 KONTROL NAVIGASI PAGINATION DINAMIS (3 BARIS PER HALAMAN)
                 ========================================================= -->
            <?php if ($totalPages >= 2): ?>
                <?php
                $queryParams = [];
                if (!empty($search)) $queryParams['search'] = $search;
                if ($selectedCategory > 0) $queryParams['kategori'] = $selectedCategory;
                $buildPageUrl = function($pageNum) use ($queryParams) {
                    $params = array_merge($queryParams, ['page' => $pageNum]);
                    return 'index.php?' . http_build_query($params) . '#katalog';
                };
                ?>
                <div class="mt-12 flex flex-col sm:flex-row items-center justify-between gap-4 pt-8 border-t border-slate-200/70">
                    <p class="text-xs text-slate-500 font-medium">
                        Menampilkan Halaman <strong class="text-primary font-bold"><?= $currentPage ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $totalBooksCount ?> judul buku, 18 buku / 3 baris per halaman)
                    </p>

                    <div class="flex items-center gap-2 flex-wrap justify-center">
                        <!-- Tombol Sebelumnya -->
                        <a 
                            href="<?= $buildPageUrl(max(1, $currentPage - 1)) ?>" 
                            class="px-3.5 py-2 rounded-xl glass-card text-xs font-semibold text-slate-700 <?= ($currentPage <= 1) ? 'opacity-40 pointer-events-none' : 'hover:border-primary/40 hover:text-primary' ?> transition-all flex items-center gap-1 shadow-sm"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            <span>Sebelumnya</span>
                        </a>

                        <!-- Loop Nomor Halaman Dinamis -->
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a 
                                href="<?= $buildPageUrl($p) ?>" 
                                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl text-xs font-bold flex items-center justify-center transition-all <?= ($currentPage === $p) ? 'bg-primary text-white shadow-md shadow-primary/25 scale-105' : 'glass-card text-slate-700 hover:text-primary' ?>"
                            >
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Tombol Selanjutnya -->
                        <a 
                            href="<?= $buildPageUrl(min($totalPages, $currentPage + 1)) ?>" 
                            class="px-3.5 py-2 rounded-xl glass-card text-xs font-semibold text-slate-700 <?= ($currentPage >= $totalPages) ? 'opacity-40 pointer-events-none' : 'hover:border-primary/40 hover:text-primary' ?> transition-all flex items-center gap-1 shadow-sm"
                        >
                            <span>Selanjutnya</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<!-- =========================================================
     KEUNGGULAN / VALUE PROPOSITION SECTION
     ========================================================= -->
<section id="keunggulan" class="py-16 relative z-10">
    <div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto">
        
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-widest text-secondary block mb-1">Mengapa Bloom&amp;Books?</span>
            <h2 class="font-serif text-3xl font-bold text-slate-900">Standar Kenyamanan Membaca Anda</h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-2">Setiap pesanan dirawat dengan penuh perhatian untuk memastikan pengalaman literasi yang tak tertandingi.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
            
            <!-- Benefit 1 -->
            <div class="glass-card rounded-3xl p-7 border border-white hover:border-secondary/40 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <h3 class="font-serif text-lg font-bold text-slate-900 mb-2">100% Buku Asli &amp; Terkurasi</h3>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                    Kami bermitra resmi langsung dengan penerbit terkemuka di tanah air maupun internasional untuk menjamin keaslian setiap lembar karya.
                </p>
            </div>

            <!-- Benefit 2 -->
            <div class="glass-card rounded-3xl p-7 border border-white hover:border-secondary/40 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <h3 class="font-serif text-lg font-bold text-slate-900 mb-2">Kemasan Estetis &amp; Protektif</h3>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                    Setiap paket dikemas dengan double bubble wrap tebal dan kotak ramah lingkungan agar buku tiba di tangan Anda dalam kondisi sempurna tanpa lekukan.
                </p>
            </div>

            <!-- Benefit 3 -->
            <div class="glass-card rounded-3xl p-7 border border-white hover:border-secondary/40 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-serif text-lg font-bold text-slate-900 mb-2">Pengiriman Kilat Seluruh Nusantara</h3>
                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                    Dukungan ekspedisi terpercaya dengan pelacakan nomor resi real-time untuk pengantaran cepat ke seluruh pelosok Indonesia.
                </p>
            </div>

        </div>

    </div>
</section>

<!-- =========================================================
     NEWSLETTER / COMMUNITY BANNER
     ========================================================= -->
<section class="py-10 relative z-10">
    <div class="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 mx-auto">
        <div class="rounded-3xl p-8 sm:p-12 bg-gradient-to-r from-primary via-indigo-900 to-[#2A0876] text-white shadow-glass-lg relative overflow-hidden">
            
            <div class="glow-blob-magenta -bottom-10 -right-10 opacity-60"></div>

            <div class="relative z-10 max-w-2xl">
                <span class="text-xs font-bold uppercase tracking-widest text-secondary block mb-2">Bergabung dengan Pembaca Lainnya</span>
                <h2 class="font-serif text-2xl sm:text-4xl font-bold mb-4 leading-snug">Dapatkan Diskon 15% untuk Pembelian Pertamamu</h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed mb-6">
                    Daftarkan akun Bloom&Books sekarang juga dan nikmati rekomendasi buku mingguan yang dipersonalisasi sesuai minat bacamu.
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <a 
                        href="register.php" 
                        class="px-6 py-3 rounded-full bg-secondary hover:bg-secondary-hover text-white text-xs sm:text-sm font-semibold shadow-md transition-all"
                    >
                        Daftar Akun Baru Sekarang
                    </a>
                    <a 
                        href="login.php" 
                        class="px-5 py-3 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs sm:text-sm font-semibold backdrop-blur-sm transition-all"
                    >
                        Sudah Punya Akun? Masuk
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- =========================================================
     MODAL POP-UP DETAIL BUKU & TAMBAH KE KERANJANG
     ========================================================= -->
<div id="bookDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md hidden transition-opacity" onclick="closeBookDetail()">
    <div class="glass-card bg-white/95 rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-white relative overflow-hidden max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        
        <!-- Close Button -->
        <button type="button" onclick="closeBookDetail()" class="absolute top-5 right-5 p-2 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors z-10" aria-label="Tutup">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

        <!-- Top Grid: Book Overview & Purchase -->
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-start">
            
            <!-- Book Cover Column -->
            <div class="sm:col-span-5 flex flex-col items-center">
                <div class="w-48 h-64 rounded-2xl overflow-hidden shadow-lg border border-slate-100 bg-white flex items-center justify-center p-3 relative">
                    <img id="modalCover" src="" alt="Book Cover" class="max-h-full max-w-full object-contain drop-shadow-md rounded">
                </div>
                <div class="mt-3 text-center">
                    <span id="modalStockBadge" class="inline-block px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Stok Tersedia
                    </span>
                </div>
            </div>

            <!-- Book Info & Action Column -->
            <div class="sm:col-span-7 flex flex-col justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span id="modalCategory" class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-primary/10 text-primary">
                            Kategori
                        </span>
                        
                        <!-- Rating Pill -->
                        <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-600 border border-amber-200/80">
                            <svg class="w-3.5 h-3.5 fill-current text-amber-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <span id="modalRatingScore">5.0</span>
                            <span class="text-slate-400 font-normal text-[11px]">(<span id="modalReviewCount">0</span> ulasan)</span>
                        </div>
                    </div>

                    <h3 id="modalTitle" class="font-sans font-bold text-2xl text-slate-900 leading-snug">
                        Judul Buku
                    </h3>
                    <p id="modalAuthor" class="text-xs text-slate-500 mt-1 font-medium">
                        Penulis: -
                    </p>
                    <p id="modalPublisher" class="text-[11px] text-slate-400 mt-0.5">
                        Penerbit: -
                    </p>

                    <div class="my-3 py-2 border-y border-slate-100">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Harga Resmi</span>
                        <span id="modalPrice" class="text-2xl font-extrabold text-primary">
                            Rp 0
                        </span>
                    </div>

                    <div class="mb-5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block mb-1">Sinopsis & Deskripsi</span>
                        <p id="modalDescription" class="text-xs text-slate-600 leading-relaxed max-h-32 overflow-y-auto pr-1">
                            Deskripsi buku...
                        </p>
                    </div>
                </div>

                <!-- Form Tambah ke Keranjang & Beli Sekarang -->
                <form id="modalPurchaseForm" action="cart.php" method="POST" class="pt-2">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="book_id" id="modalBookId" value="">
                    <input type="hidden" name="redirect" id="modalRedirectInput" value="cart.php">
                    
                    <div class="flex items-center gap-3 mb-4">
                        <label for="modalQty" class="text-xs font-bold text-slate-700">Jumlah:</label>
                        <div class="flex items-center rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                            <button type="button" onclick="adjustModalQty(-1)" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 font-bold text-sm transition-colors">-</button>
                            <input type="number" id="modalQty" name="qty" value="1" min="1" max="50" oninput="updateModalTotal()" class="w-12 text-center text-xs font-bold outline-none border-x border-slate-200 py-1.5">
                            <button type="button" onclick="adjustModalQty(1)" class="px-3 py-1.5 text-slate-600 hover:bg-slate-100 font-bold text-sm transition-colors">+</button>
                        </div>
                        <span class="text-xs text-slate-500">Subtotal: <strong id="modalTotalPrice" class="text-primary font-extrabold">Rp 0</strong></span>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-2.5">
                        <?php if (isLoggedIn()): ?>
                            <!-- Button 1: Masukkan ke Keranjang -->
                            <button 
                                type="submit" 
                                id="modalAddToCartBtn" 
                                onclick="document.getElementById('modalRedirectInput').value = 'index.php?view_book=' + document.getElementById('modalBookId').value + '#katalog'"
                                class="w-full sm:flex-1 py-3 px-3.5 rounded-xl border-2 border-primary/80 bg-primary/5 hover:bg-primary text-primary hover:text-white font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 shadow-sm"
                                title="Tambahkan buku ini ke keranjang belanja"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                <span>Masukkan Keranjang</span>
                            </button>

                            <!-- Button 2: Beli Sekarang -->
                            <button 
                                type="submit" 
                                id="modalBuyNowBtn" 
                                onclick="document.getElementById('modalRedirectInput').value = 'cart.php'"
                                class="w-full sm:flex-1 py-3 px-3.5 rounded-xl bg-gradient-to-r from-primary via-indigo-600 to-secondary text-white font-bold text-xs sm:text-sm shadow-md hover:opacity-95 hover:shadow-lg transition-all flex items-center justify-center gap-2"
                                title="Langsung beli dan lanjut ke pembayaran"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span>Beli Sekarang</span>
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-xs sm:text-sm text-center shadow-md hover:opacity-95 transition-all">
                                Masuk untuk Membeli Buku
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

            </div>

        </div>

        <!-- =========================================================
             BAGIAN RATING & ULASAN PEMBACA
             ========================================================= -->
        <div class="mt-8 pt-6 border-t border-slate-200/80">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-5">
                <div>
                    <h4 class="font-bold text-base text-slate-900 flex items-center gap-2">
                        <span>Rating & Ulasan Pembaca</span>
                        <span id="modalReviewCountBadge" class="text-[11px] px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-bold">0 ulasan</span>
                    </h4>
                    <p class="text-xs text-slate-500 mt-0.5">Penilaian langsung dari komunitas pembaca Bloom&Books.</p>
                </div>

                <!-- Tombol Tulis Ulasan -->
                <?php if (isLoggedIn()): ?>
                    <button type="button" onclick="toggleReviewForm()" class="px-3.5 py-2 rounded-xl bg-primary/10 hover:bg-primary text-primary hover:text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>+ Tulis Ulasan Anda</span>
                    </button>
                <?php else: ?>
                    <a href="login.php" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-primary/10 text-slate-600 hover:text-primary text-xs font-bold transition-all flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        <span>Masuk untuk Beri Ulasan</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Form Tambah Ulasan Interaktif -->
            <?php if (isLoggedIn()): ?>
                <div id="reviewFormContainer" class="hidden mb-6 p-5 rounded-2xl bg-slate-50 border border-slate-200 shadow-sm animate-scale-up">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">Tuliskan Penilaian & Ulasan Anda</h5>
                    <form action="index.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="submit_review">
                        <input type="hidden" name="book_id" id="reviewFormBookId" value="">
                        
                        <!-- Rating Bintang Interaktif -->
                        <div>
                            <span class="block text-xs text-slate-600 font-semibold mb-1.5">Pilih Rating Bintang:</span>
                            <input type="hidden" name="rating" id="selectedRatingInput" value="5">
                            <div class="flex items-center gap-1" id="starRatingSelector">
                                <button type="button" onclick="setRating(1)" class="star-btn text-2xl text-amber-400 hover:scale-125 transition-transform">★</button>
                                <button type="button" onclick="setRating(2)" class="star-btn text-2xl text-amber-400 hover:scale-125 transition-transform">★</button>
                                <button type="button" onclick="setRating(3)" class="star-btn text-2xl text-amber-400 hover:scale-125 transition-transform">★</button>
                                <button type="button" onclick="setRating(4)" class="star-btn text-2xl text-amber-400 hover:scale-125 transition-transform">★</button>
                                <button type="button" onclick="setRating(5)" class="star-btn text-2xl text-amber-400 hover:scale-125 transition-transform">★</button>
                                <span id="ratingLabel" class="text-xs font-bold text-amber-600 ml-2">5 / 5 (Luar Biasa)</span>
                            </div>
                        </div>

                        <!-- Komentar Textarea -->
                        <div>
                            <label for="reviewKomentarInput" class="block text-xs text-slate-600 font-semibold mb-1">Ulasan Anda:</label>
                            <textarea 
                                id="reviewKomentarInput"
                                name="komentar" 
                                required
                                rows="3" 
                                placeholder="Bagikan kesan Anda tentang gaya penulisan, ide gagasan, atau manfaat buku ini bagi Anda..." 
                                class="w-full p-3 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder-slate-400 outline-none focus:ring-2 focus:ring-primary/20 leading-relaxed"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-1">
                            <button type="button" onclick="toggleReviewForm()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-200 transition-colors">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-primary via-indigo-600 to-secondary text-white font-bold text-xs shadow-md hover:opacity-95 transition-all">
                                Publikasikan Ulasan
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Daftar Ulasan Pembaca -->
            <div id="modalReviewsContainer" class="space-y-3 max-h-60 overflow-y-auto pr-1">
                <!-- Diisi secara dinamis oleh JavaScript -->
            </div>
        </div>

    </div>
</div>

<!-- Script Interaktif Modal Detail Buku, Rating & Ulasan -->
<script>
    function formatRupiahJS(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
    }

    function setRating(rating) {
        const input = document.getElementById('selectedRatingInput');
        if (input) input.value = rating;

        const stars = document.querySelectorAll('#starRatingSelector .star-btn');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('text-amber-400');
                star.classList.remove('text-slate-300');
            } else {
                star.classList.remove('text-amber-400');
                star.classList.add('text-slate-300');
            }
        });

        const labels = {
            1: '1 / 5 (Kurang Puas)',
            2: '2 / 5 (Cukup)',
            3: '3 / 5 (Bagus)',
            4: '4 / 5 (Sangat Bagus)',
            5: '5 / 5 (Luar Biasa)'
        };
        const labelEl = document.getElementById('ratingLabel');
        if (labelEl) labelEl.textContent = labels[rating] || (rating + ' / 5');
    }

    function toggleReviewForm() {
        const container = document.getElementById('reviewFormContainer');
        if (container) {
            container.classList.toggle('hidden');
        }
    }

    let currentModalBookPrice = 0;

    function updateModalTotal() {
        const input = document.getElementById('modalQty');
        const totalEl = document.getElementById('modalTotalPrice');
        if (input && totalEl) {
            const qty = Math.max(1, parseInt(input.value) || 1);
            totalEl.textContent = formatRupiahJS(currentModalBookPrice * qty);
        }
    }

    function openBookDetail(book) {
        if (!book) return;

        document.getElementById('modalCover').src = book.cover_image || 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600&auto=format&fit=crop&q=80';
        document.getElementById('modalTitle').textContent = book.judul || 'Judul Buku';
        document.getElementById('modalAuthor').textContent = 'Penulis: ' + (book.penulis || '-');
        document.getElementById('modalPublisher').textContent = 'Penerbit: ' + (book.penerbit || '-');
        document.getElementById('modalCategory').textContent = book.nama_kategori || 'Kategori';
        document.getElementById('modalPrice').textContent = formatRupiahJS(book.harga || 0);
        document.getElementById('modalDescription').textContent = book.deskripsi || 'Belum ada deskripsi yang tersedia untuk buku ini.';
        document.getElementById('modalBookId').value = book.id || '';
        document.getElementById('modalQty').value = 1;

        currentModalBookPrice = parseFloat(book.harga || 0);
        updateModalTotal();

        // Rating & Reviews Stats
        const avgScore = (book.avg_rating && book.avg_rating > 0) ? parseFloat(book.avg_rating).toFixed(1) : '5.0';
        const reviews = book.reviews || [];
        const totalReviews = reviews.length;

        const ratingScoreEl = document.getElementById('modalRatingScore');
        if (ratingScoreEl) ratingScoreEl.textContent = avgScore;

        const revCountEl = document.getElementById('modalReviewCount');
        if (revCountEl) revCountEl.textContent = totalReviews;

        const revBadgeEl = document.getElementById('modalReviewCountBadge');
        if (revBadgeEl) revBadgeEl.textContent = totalReviews + ' ulasan';

        // Set book_id for review form
        const reviewBookIdInput = document.getElementById('reviewFormBookId');
        if (reviewBookIdInput) reviewBookIdInput.value = book.id || '';

        // Reset review form state
        setRating(5);
        const reviewFormCont = document.getElementById('reviewFormContainer');
        if (reviewFormCont) reviewFormCont.classList.add('hidden');

        // Populate Reviews List
        const reviewsContainer = document.getElementById('modalReviewsContainer');
        if (reviewsContainer) {
            reviewsContainer.innerHTML = '';

            if (reviews.length === 0) {
                reviewsContainer.innerHTML = `
                    <div class="p-6 text-center rounded-2xl bg-slate-50/70 border border-slate-100">
                        <span class="text-2xl block mb-1">📖</span>
                        <p class="text-xs font-semibold text-slate-700">Belum ada ulasan untuk buku ini</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Jadilah pembaca pertama yang membagikan ulasan dan penilaianmu!</p>
                    </div>
                `;
            } else {
                reviews.forEach(r => {
                    const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
                    const userInitial = (r.user_nama) ? r.user_nama.charAt(0).toUpperCase() : 'U';
                    const dateFormatted = r.created_at ? new Date(r.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Baru saja';

                    const item = document.createElement('div');
                    item.className = 'p-3.5 rounded-2xl bg-slate-50/80 border border-slate-200/80 text-xs space-y-1.5 transition-all hover:bg-slate-50';
                    item.innerHTML = `
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-xs ring-2 ring-white overflow-hidden shrink-0">
                                    ${r.user_foto ? `<img src="${r.user_foto}" alt="Avatar" class="w-full h-full object-cover">` : userInitial}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 block leading-tight">${r.user_nama || 'Pembaca Bloom'}</span>
                                    <span class="text-[10px] text-emerald-600 font-semibold flex items-center gap-0.5">
                                        <svg class="w-3 h-3 fill-current inline" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                        Pembaca Terverifikasi
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-amber-400 font-bold tracking-wider text-xs">${stars}</span>
                                <span class="text-[10px] text-slate-400 block">${dateFormatted}</span>
                            </div>
                        </div>
                        <p class="text-slate-600 leading-relaxed text-[11px] pt-1 pl-9">
                            ${r.komentar}
                        </p>
                    `;
                    reviewsContainer.appendChild(item);
                });
            }
        }

        // Stock status & Action buttons (Keranjang & Beli Sekarang)
        const stockBadge = document.getElementById('modalStockBadge');
        const cartBtn = document.getElementById('modalAddToCartBtn');
        const buyNowBtn = document.getElementById('modalBuyNowBtn');
        const stock = parseInt(book.stok || 0);

        if (stock > 0) {
            stockBadge.className = 'inline-block px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
            stockBadge.textContent = 'Stok Tersedia (' + stock + ' eks)';
            if (cartBtn) cartBtn.disabled = false;
            if (buyNowBtn) buyNowBtn.disabled = false;
        } else {
            stockBadge.className = 'inline-block px-3 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200';
            stockBadge.textContent = 'Stok Habis';
            if (cartBtn) cartBtn.disabled = true;
            if (buyNowBtn) buyNowBtn.disabled = true;
        }

        const modal = document.getElementById('bookDetailModal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeBookDetail() {
        const modal = document.getElementById('bookDetailModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function adjustModalQty(delta) {
        const input = document.getElementById('modalQty');
        let current = parseInt(input.value) || 1;
        current += delta;
        if (current < 1) current = 1;
        if (current > 50) current = 50;
        input.value = current;
        updateModalTotal();
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeBookDetail();
        }
    });

    // Auto buka modal buku jika ada parameter view_book di URL (setelah kirim ulasan)
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const viewBookId = parseInt(urlParams.get('view_book') || 0);
        if (viewBookId > 0) {
            const allBooksData = <?= json_encode($allBooks) ?>;
            const targetBook = allBooksData.find(b => parseInt(b.id) === viewBookId);
            if (targetBook) {
                openBookDetail(targetBook);
            }
        }

        // =========================================================
        // BANNER CAROUSEL CONTROLLER
        // =========================================================
        const carouselWrapper = document.getElementById('heroCarouselWrapper');
        const carouselSlides = document.querySelectorAll('.carousel-slide');
        const carouselDots = document.querySelectorAll('.indicator-dot');
        const currentSlideNum = document.getElementById('currentSlideNum');
        const prevBtn = document.getElementById('carouselPrevBtn');
        const nextBtn = document.getElementById('carouselNextBtn');
        
        let currentSlide = 0;
        const totalSlides = carouselSlides.length;
        let carouselInterval = null;

        function showSlide(index) {
            if (totalSlides === 0) return;
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;
            currentSlide = index;

            carouselSlides.forEach((slide, i) => {
                if (i === index) {
                    slide.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
                    slide.classList.add('opacity-100', 'scale-100');
                } else {
                    slide.classList.remove('opacity-100', 'scale-100');
                    slide.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
                }
            });

            carouselDots.forEach((dot, i) => {
                if (i === index) {
                    dot.classList.add('w-8', 'bg-white');
                    dot.classList.remove('w-2.5', 'bg-white/40');
                } else {
                    dot.classList.remove('w-8', 'bg-white');
                    dot.classList.add('w-2.5', 'bg-white/40');
                }
            });

            if (currentSlideNum) {
                currentSlideNum.textContent = String(index + 1).padStart(2, '0');
            }
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        function startAutoplay() {
            stopAutoplay();
            carouselInterval = setInterval(nextSlide, 5000);
        }

        function stopAutoplay() {
            if (carouselInterval) {
                clearInterval(carouselInterval);
                carouselInterval = null;
            }
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                prevSlide();
                startAutoplay();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                nextSlide();
                startAutoplay();
            });
        }

        carouselDots.forEach((dot, i) => {
            dot.addEventListener('click', () => {
                showSlide(i);
                startAutoplay();
            });
        });

        if (carouselWrapper) {
            carouselWrapper.addEventListener('mouseenter', stopAutoplay);
            carouselWrapper.addEventListener('mouseleave', startAutoplay);

            // Touch Swipe support untuk perangkat mobile
            let touchStartX = 0;
            let touchEndX = 0;

            carouselWrapper.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            carouselWrapper.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                if (touchStartX - touchEndX > 50) {
                    nextSlide();
                    startAutoplay();
                } else if (touchEndX - touchStartX > 50) {
                    prevSlide();
                    startAutoplay();
                }
            }, { passive: true });
        }

        // Start autoplay on load
        startAutoplay();
    });
</script>

<?php
// Footer template
require_once __DIR__ . '/includes/footer.php';
?>
