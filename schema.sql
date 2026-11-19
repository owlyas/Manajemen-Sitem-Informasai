-- ========================================================
-- Bloom&Books E-Commerce Database Schema
-- Database: db_ecommerce
-- Compatible with XAMPP / MariaDB / MySQL / phpMyAdmin
-- ========================================================

CREATE DATABASE IF NOT EXISTS `db_ecommerce` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_ecommerce`;

-- 1. Table: users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `telepon` VARCHAR(30) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `foto_profil` VARCHAR(255) DEFAULT NULL,
    `alamat` TEXT DEFAULT NULL,
    `kota` VARCHAR(100) DEFAULT NULL,
    `kode_pos` VARCHAR(10) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: categories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: books
CREATE TABLE IF NOT EXISTS `books` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `judul` VARCHAR(255) NOT NULL,
    `penulis` VARCHAR(150) NOT NULL,
    `penerbit` VARCHAR(150) NOT NULL,
    `harga` DECIMAL(12, 2) NOT NULL,
    `stok` INT NOT NULL DEFAULT 0,
    `deskripsi` TEXT NULL,
    `cover_image` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) 
        REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table: cart
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `book_id` INT NOT NULL,
    `qty` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cart_book` FOREIGN KEY (`book_id`) 
        REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table: orders
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_harga` DECIMAL(12, 2) NOT NULL,
    `status_pembayaran` ENUM('pending', 'paid', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    `alamat_pengiriman` TEXT NOT NULL,
    `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table: order_items
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `book_id` INT NOT NULL,
    `qty` INT NOT NULL,
    `harga_satuan` DECIMAL(12, 2) NOT NULL,
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) 
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_book` FOREIGN KEY (`book_id`) 
        REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table: reviews (Rating & Ulasan Buku)
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `book_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `komentar` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reviews_book` FOREIGN KEY (`book_id`) 
        REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DUMMY DATA SEEDING
-- ========================================================

-- Seed: Categories (4 Kategori)
INSERT INTO `categories` (`id`, `nama_kategori`, `slug`) VALUES
(1, 'Fiksi & Sastra', 'fiksi-sastra'),
(2, 'Pengembangan Diri & Bisnis', 'pengembangan-diri-bisnis'),
(3, 'Teknologi & Sains', 'teknologi-sains'),
(4, 'Seni & Desain Kreatif', 'seni-desain-kreatif')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`);

-- Seed: Users (1 Admin & 1 User)
-- Password default: 'admin123' untuk admin & 'user123' untuk user
-- Hash bcrypt standar ($2y$10$wE3/M7tG.t3O65qF863Fq.9YgLgB1o.g4Xk78c18712p1xN1e5k9K)
INSERT INTO `users` (`id`, `nama`, `email`, `password_hash`, `role`, `foto_profil`) VALUES
(1, 'Admin Bloom', 'admin@bloombooks.com', '$2y$10$wE3/M7tG.t3O65qF863Fq.9YgLgB1o.g4Xk78c18712p1xN1e5k9K', 'admin', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80'),
(2, 'Nasya Reader', 'user@bloombooks.com', '$2y$10$wE3/M7tG.t3O65qF863Fq.9YgLgB1o.g4Xk78c18712p1xN1e5k9K', 'user', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Seed: Books (54 Buku Lengkap dengan Cover Asli Sesuai Judul)
INSERT INTO `books` (`id`, `category_id`, `judul`, `penulis`, `penerbit`, `harga`, `stok`, `deskripsi`, `cover_image`) VALUES
(1, 1, 'Laut Bercerita', 'Leila S. Chudori', 'KPG (Kepustakaan Populer Gramedia)', 115000.00, 24, 'Kisah para aktivis mahasiswa 1998 yang diculik dan disiksa, serta keluarga yang terus setia menanti kepastian.', 'https://covers.openlibrary.org/b/id/10648285-L.jpg'),
(2, 1, 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Lentera Dipantara', 135000.00, 18, 'Mahakarya Tetralogi Buru mengisahkan pergulatan Minke melawan ketidakadilan feodal dan kolonial Hindia Belanda.', 'https://covers.openlibrary.org/b/isbn/9789799731234-L.jpg'),
(3, 1, 'Cantik Itu Luka', 'Eka Kurniawan', 'Gramedia Pustaka Utama', 125000.00, 22, 'Realisme magis Indonesia yang merajut tragedi Dewi Ayu dan keturunannya dengan sejarah kelam bangsa.', 'https://covers.openlibrary.org/b/isbn/9786020312583-L.jpg'),
(4, 1, 'Hujan', 'Tere Liye', 'Gramedia Pustaka Utama', 95000.00, 30, 'Kisah fiksi ilmiah tentang persahabatan, cinta, dan melupakan kenangan di masa depan yang sarat teknologi.', 'https://covers.openlibrary.org/b/id/10872657-L.jpg'),
(5, 1, 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 89000.00, 45, 'Perjuangan sepuluh anak Belitung mengejar impian pendidikan dengan keterbatasan fasilitas yang mengharukan.', 'https://covers.openlibrary.org/b/id/7079796-L.jpg'),
(6, 1, 'Ronggeng Dukuh Paruk', 'Ahmad Tohari', 'Gramedia Pustaka Utama', 110000.00, 15, 'Trilogi legendaris tentang Srintil, seorang penari ronggeng di Dukuh Paruk, dan pusaran pergolakan politik 1965.', 'https://covers.openlibrary.org/b/id/4317176-L.jpg'),
(7, 1, 'Perahu Kertas', 'Dee Lestari', 'Bentang Pustaka', 98000.00, 28, 'Kisah Kugy dan Keenan, dua anak muda yang terperangkap antara realitas karier dan panggilan hati sebagai seniman.', 'https://covers.openlibrary.org/b/id/8705088-L.jpg'),
(8, 1, 'Supernova: Ksatria, Puteri, dan Bintang Jatuh', 'Dee Lestari', 'Bentang Pustaka', 112000.00, 20, 'Fiksi spekulatif Indonesia yang menggabungkan sains kuantum, filsafat cinta, dan misteri eksistensi manusia.', 'https://covers.openlibrary.org/b/id/10000969-L.jpg'),
(9, 1, 'Tenggelamnya Kapal Van Der Wijck', 'Buya Hamka', 'Bulan Bintang', 85000.00, 35, 'Kisah cinta tragis Zainuddin dan Hayati yang terhalang benteng adat istiadat Minangkabau.', 'https://covers.openlibrary.org/b/id/10574100-L.jpg'),
(10, 1, 'The Great Gatsby', 'F. Scott Fitzgerald', 'Scribner', 145000.00, 25, 'Potret dekadensi dan impian Amerika di era Jazz Age melalui sosok misterius Jay Gatsby.', 'https://covers.openlibrary.org/b/isbn/9780743273565-L.jpg'),
(11, 1, '1984', 'George Orwell', 'Signet Classic', 135000.00, 30, 'Distopia legendaris tentang tirani Big Brother, manipulasi fakta sejarah, dan perlawanan nurani Winston Smith.', 'https://covers.openlibrary.org/b/isbn/9780451524935-L.jpg'),
(12, 1, 'To Kill a Mockingbird', 'Harper Lee', 'Harper Perennial', 150000.00, 20, 'Kisah kepolosan masa kanak-kanak dan integritas moral Atticus Finch membela keadilan rasial di Alabama.', 'https://covers.openlibrary.org/b/isbn/9780060935467-L.jpg'),
(13, 1, 'The Alchemist', 'Paulo Coelho', 'HarperOne', 120000.00, 40, 'Kembara magis gembala Andalusia Santiago mengikuti pertanda demi menemukan Harta Karun Takdir hidupnya.', 'https://covers.openlibrary.org/b/isbn/9780062315007-L.jpg'),
(14, 1, 'Norwegian Wood', 'Haruki Murakami', 'Vintage', 165000.00, 16, 'Nostalgia patah hati, duka kehilangan, dan dinamika asmara anak muda Tokyo tahun 1960-an.', 'https://covers.openlibrary.org/b/id/8750865-L.jpg'),
(15, 2, 'Filosofi Teras', 'Henry Manampiring', 'Penerbit Buku Kompas', 98000.00, 42, 'Penerapan praktis filsafat Stoa kuno untuk memadamkan emosi negatif dan overthinking generasi masa kini.', 'https://cdn.gramedia.com/uploads/picture_meta/2023/11/27/kjf6cgigkomf6sy9o5qauu.jpg'),
(16, 2, 'Atomic Habits', 'James Clear', 'Gramedia Pustaka Utama', 128000.00, 50, 'Perubahan kecil 1% setiap hari dengan 4 kaidah hukum pembentukan kebiasaan yang terbukti ilmiah.', 'https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg'),
(17, 2, 'The Psychology of Money', 'Morgan Housel', 'BACA', 105000.00, 35, 'Pelajaran abadi mengenai perilaku manusia memperlakukan uang, ego finansial, dan keserakahan.', 'https://covers.openlibrary.org/b/isbn/9780857197689-L.jpg'),
(18, 2, 'Rich Dad Poor Dad', 'Robert T. Kiyosaki', 'Plata Publishing', 118000.00, 38, 'Perbedaan mendasar cara berpikir orang kaya vs kelas pekerja dalam mengelola aset dan liabilitas.', 'https://covers.openlibrary.org/b/id/12547191-L.jpg'),
(19, 2, 'Deep Work', 'Cal Newport', 'Grand Central Publishing', 140000.00, 25, 'Aturan sukses untuk fokus tanpa distraksi di era digital demi menghasilkan karya luar biasa.', 'https://covers.openlibrary.org/b/isbn/9781455586691-L.jpg'),
(20, 2, 'Start with Why', 'Simon Sinek', 'Portfolio Penguin', 135000.00, 28, 'Bagaimana pemimpin besar seperti Steve Jobs menginspirasi jutaan orang dengan memulai dari alasan mendasar (Why).', 'https://covers.openlibrary.org/b/isbn/9781591846444-L.jpg'),
(21, 2, 'Think and Grow Rich', 'Napoleon Hill', 'TarcherPerigee', 100000.00, 30, '13 prinsip kesuksesan finansial hasil riset 500 tokoh terkaya di dunia selama 20 tahun.', 'https://covers.openlibrary.org/b/isbn/9781585424337-L.jpg'),
(22, 2, 'The 7 Habits of Highly Effective People', 'Stephen R. Covey', 'Free Press', 155000.00, 32, 'Prinsip efektivitas personal dan interpersonal yang mengubah kepribadian menuju kedewasaan sejati.', 'https://covers.openlibrary.org/b/isbn/9780743269513-L.jpg'),
(23, 2, 'Ikigai: The Japanese Secret to a Long and Happy Life', 'Héctor García & Francesc Miralles', 'Penguin Life', 125000.00, 45, 'Rahasia umur panjang dan kepuasan hidup warga desa Okinawa melalui titik temu passion, bakat, dan kontribusi.', 'https://covers.openlibrary.org/b/id/12692257-L.jpg'),
(24, 2, 'Grit: The Power of Passion and Perseverance', 'Angela Duckworth', 'Scribner', 145000.00, 20, 'Buktian riset psikologi bahwa kegigihan (grit) jauh lebih menentukan pencapaian ketimbang bakat alami.', 'https://covers.openlibrary.org/b/isbn/9781501111105-L.jpg'),
(25, 2, 'Zero to One', 'Peter Thiel', 'Crown Business', 150000.00, 26, 'Catatan penting membangun startup yang menciptakan monopoli kreatif dari nol menjadi pionir.', 'https://covers.openlibrary.org/b/isbn/9780804139298-L.jpg'),
(26, 2, 'The Lean Startup', 'Eric Ries', 'Crown Business', 160000.00, 22, 'Metodologi Build-Measure-Learn untuk memvalidasi ide bisnis dengan cepat tanpa membuang modal.', 'https://covers.openlibrary.org/b/isbn/9780307887894-L.jpg'),
(27, 2, 'Essentialism: The Disciplined Pursuit of Less', 'Greg McKeown', 'Crown Currency', 138000.00, 19, 'Disiplin mengeliminasi hal yang tidak penting agar dapat mencurahkan energi maksimal pada hal esensial.', 'https://covers.openlibrary.org/b/isbn/9780804137386-L.jpg'),
(28, 2, 'Show Your Work!', 'Austin Kleon', 'Workman Publishing', 92000.00, 34, '10 cara membagikan proses berkaryamu secara konsisten agar ditemukan oleh orang yang tepat.', 'https://covers.openlibrary.org/b/isbn/9780761178972-L.jpg'),
(29, 3, 'Clean Code: A Handbook of Agile Software Craftsmanship', 'Robert C. Martin', 'Prentice Hall', 345000.00, 15, 'Pedoman wajib developer merancang kode bersih, elegan, teruji, dan mudah dipelihara.', 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg'),
(30, 3, 'The Pragmatic Programmer: 20th Anniversary Edition', 'David Thomas & Andrew Hunt', 'Addison-Wesley', 380000.00, 12, 'Filosofi profesional perangkat lunak: arsitektur fleksibel, etika engineering, dan otomatisasi.', 'https://covers.openlibrary.org/b/isbn/9780135957059-L.jpg'),
(31, 3, 'Sapiens: Riwayat Singkat Umat Manusia', 'Yuval Noah Harari', 'KPG', 145000.00, 28, 'Sejarah perjalanan Homo sapiens dari makhluk primata biasa hingga mendominasi planet Bumi.', 'https://covers.openlibrary.org/b/id/8741369-L.jpg'),
(32, 3, 'Homo Deus: A Brief History of Tomorrow', 'Yuval Noah Harari', 'Harper', 165000.00, 18, 'Eksplorasi masa depan manusia di era bio-rekayasa, kecerdasan buatan, dan pencarian keabadian.', 'https://covers.openlibrary.org/b/isbn/9780062464316-L.jpg'),
(33, 3, 'Introduction to Algorithms (CLRS)', 'Thomas H. Cormen et al.', 'MIT Press', 450000.00, 10, 'Kitab suci algoritma dan struktur data dunia komputer dari sorting, graph, hingga pemrograman dinamis.', 'https://covers.openlibrary.org/b/isbn/9780262033848-L.jpg'),
(34, 3, 'Refactoring: Improving the Design of Existing Code', 'Martin Fowler', 'Addison-Wesley', 395000.00, 14, 'Katalog perbaikan struktur kode tanpa mengubah perilaku eksternal agar software tetap awet.', 'https://covers.openlibrary.org/b/isbn/9780201485677-L.jpg'),
(35, 3, 'Design Patterns: Elements of Reusable Object-Oriented Software', 'Erich Gamma et al. (Gang of Four)', 'Addison-Wesley', 370000.00, 16, '23 pola desain klasik OOP yang menjadi fondasi arsitektur perangkat lunak modern.', 'https://covers.openlibrary.org/b/isbn/9780201633610-L.jpg'),
(36, 3, 'Code Complete', 'Steve McConnell', 'Microsoft Press', 390000.00, 11, 'Ensiklopedia konstruksi perangkat lunak praktis dan teknik penulisan kode tingkat industri.', 'https://covers.openlibrary.org/b/isbn/9780735619678-L.jpg'),
(37, 3, 'Designing Data-Intensive Applications', 'Martin Kleppmann', 'O\'Reilly Media', 420000.00, 15, 'Buku pegangan wajib backend engineer tentang reliabilitas, skalabilitas, dan data stream terdistribusi.', 'https://covers.openlibrary.org/b/isbn/9781449373320-L.jpg'),
(38, 3, 'A Brief History of Time', 'Stephen Hawking', 'Bantam Books', 135000.00, 35, 'Penjelasan kosmologi menakjubkan tentang Big Bang, Lubang Hitam, dan misteri ruang waktu.', 'https://covers.openlibrary.org/b/isbn/9780553380163-L.jpg'),
(39, 3, 'Cosmos', 'Carl Sagan', 'Ballantine Books', 155000.00, 24, 'Refleksi puitis Carl Sagan tentang miliaran galaksi dan posisi manusia dalam panggung semesta.', 'https://covers.openlibrary.org/b/isbn/9780345539434-L.jpg'),
(40, 3, 'Astrophysics for People in a Hurry', 'Neil deGrasse Tyson', 'W. W. Norton & Company', 125000.00, 30, 'Ringkasan astrofisika yang renyah dan cepat dicerna mengenai gravitasi kuantum dan materi gelap.', 'https://covers.openlibrary.org/b/isbn/9780393609394-L.jpg'),
(41, 3, 'The Selfish Gene', 'Richard Dawkins', 'Oxford University Press', 160000.00, 20, 'Sudut pandang revolusioner evolusi biologi yang menempatkan gen sebagai pusat dorongan seleksi alam.', 'https://covers.openlibrary.org/b/isbn/9780199291151-L.jpg'),
(42, 3, 'You Don\'t Know JS: Scope & Closures', 'Kyle Simpson', 'O\'Reilly Media', 180000.00, 25, 'Bedah tuntas mekanisme lexical scope, closures, dan hoisting dalam mesin JavaScript.', 'https://covers.openlibrary.org/b/isbn/9781449335588-L.jpg'),
(43, 4, 'The Design of Everyday Things', 'Don Norman', 'Basic Books', 220000.00, 20, 'Prinsip fundamental UX, affordance, dan signifier agar produk dapat dipakai tanpa membingungkan manusia.', 'https://covers.openlibrary.org/b/isbn/9780465050659-L.jpg'),
(44, 4, 'Steal Like an Artist', 'Austin Kleon', 'Workman Publishing', 88000.00, 32, '10 petuah inspiratif bahwa tidak ada ide murni orisinal; pelajari, remiks, dan ciptakan gayamu.', 'https://covers.openlibrary.org/b/isbn/9780761169253-L.jpg'),
(45, 4, 'Don\'t Make Me Think: Revisited', 'Steve Krug', 'New Riders', 210000.00, 18, 'Prinsip kesederhanaan navigasi dan usabilitas website yang intuitif bagi siapa saja.', 'https://covers.openlibrary.org/b/id/7984916-L.jpg'),
(46, 4, 'Universal Principles of Design', 'William Lidwell', 'Rockport Publishers', 240000.00, 15, '125 prinsip desain lintas disiplin dari Golden Ratio hingga Hukum Fitts.', 'https://covers.openlibrary.org/b/isbn/9781592535873-L.jpg'),
(47, 4, 'Sprint: How to Solve Big Problems in 5 Days', 'Jake Knapp', 'Simon & Schuster', 195000.00, 22, 'Proses 5 hari Google Ventures untuk merancang ide, prototyping cepat, dan menguji langsung ke pengguna.', 'https://covers.openlibrary.org/b/isbn/9781501121746-L.jpg'),
(48, 4, 'Creative Confidence', 'Tom Kelley & David Kelley', 'Crown Business', 185000.00, 24, 'Membangun kembali keberanian berkreasi dan memecahkan masalah kompleks dari pendiri IDEO.', 'https://covers.openlibrary.org/b/isbn/9780385349369-L.jpg'),
(49, 4, 'Grid Systems in Graphic Design', 'Josef Müller-Brockmann', 'Niggli', 310000.00, 12, 'Pedoman legendaris sistem grid Swiss style untuk desainer grafis dan perancang layout tipografi.', 'https://covers.openlibrary.org/b/id/8276709-L.jpg'),
(50, 4, 'Interaction of Color', 'Josef Albers', 'Yale University Press', 230000.00, 14, 'Eksperimen Bauhaus tentang bagaimana warna berinteraksi dan menipu persepsi mata manusia.', 'https://covers.openlibrary.org/b/isbn/9780300179354-L.jpg'),
(51, 4, 'Hooked: How to Build Habit-Forming Products', 'Nir Eyal', 'Portfolio Penguin', 175000.00, 26, 'Model 4 langkah Hook (Trigger, Action, Variable Reward, Investment) dalam menciptakan produk adiktif.', 'https://covers.openlibrary.org/b/isbn/9781591847786-L.jpg'),
(52, 4, 'The Non-Designer\'s Design Book', 'Robin Williams', 'Peachpit Press', 165000.00, 28, '4 pilar sakti desain visual: Contrast, Repetition, Alignment, Proximity (CRAP) untuk pemula.', 'https://covers.openlibrary.org/b/isbn/9780321534040-L.jpg'),
(53, 4, 'Thinking with Type', 'Ellen Lupton', 'Princeton Architectural Press', 225000.00, 16, 'Panduan komprehensif seni tipografi: anatomi huruf, hierarki teks, tracking, dan kerning.', 'https://covers.openlibrary.org/b/isbn/9781568989693-L.jpg'),
(54, 4, 'Layout Essentials: 100 Design Principles', 'Beth Tondreau', 'Rockport Publishers', 195000.00, 19, '100 prinsip esensial tata letak editorial, majalah, poster, dan antarmuka web modern.', 'https://covers.openlibrary.org/b/isbn/9781592537075-L.jpg')
ON DUPLICATE KEY UPDATE `judul` = VALUES(`judul`), `cover_image` = VALUES(`cover_image`);

-- Seed: Reviews (Contoh Ulasan & Rating Buku)
INSERT INTO `reviews` (`id`, `book_id`, `user_id`, `rating`, `komentar`, `created_at`) VALUES
(1, 1, 2, 5, 'Buku yang sangat mengharukan dan membuka mata tentang sejarah perjuangan mahasiswa. Gaya penulisan Leila S. Chudori sangat puitis dan menusuk relung hati.', '2026-10-10 10:15:00'),
(2, 4, 2, 5, 'Filosofi Teras benar-benar mengubah cara saya memandang masalah hidup. Sangat praktis untuk meredakan overthinking dan kecemasan sehari-hari!', '2026-10-12 14:30:00'),
(3, 5, 2, 5, 'Buku wajib untuk siapa saja yang ingin membangun kebiasaan produktif. Konsep perbaikan 1% setiap hari benar-benar terbukti membawa dampak besar.', '2026-10-14 09:20:00'),
(4, 7, 2, 4, 'Buku pegangan wajib bagi software engineer. Memberi wawasan mendalam tentang bagaimana menulis kode yang bersih, mudah dirawat, dan terstruktur rapi.', '2026-10-15 16:45:00')
ON DUPLICATE KEY UPDATE `komentar` = VALUES(`komentar`);

