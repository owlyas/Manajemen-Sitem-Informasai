<?php
/**
 * Bloom&Books - Management Data Buku (CRUD Buku Lengkap)
 * Fitur: Create (Tambah), Read (Tabel & Cari), Update (Edit), Delete (Hapus).
 */

$pageTitle = 'Manajemen Data Buku';
$activePage = 'books';

require_once __DIR__ . '/auth_check.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Buat direktori upload jika belum ada
$uploadDir = __DIR__ . '/../uploads/books/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Ambil daftar kategori untuk dropdown
$categories = [];
if ($pdo !== null) {
    try {
        $categories = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();
    } catch (PDOException $e) {}
}

// =========================================================
// 1. PROSES DELETE (HAPUS BUKU)
// =========================================================
if ($action === 'delete') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0 && $pdo !== null) {
        try {
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlashMessage('success', "Buku berhasil dihapus dari katalog.");
        } catch (PDOException $e) {
            setFlashMessage('error', "Gagal menghapus buku: " . $e->getMessage());
        }
    }
    header('Location: books.php');
    exit;
}

// =========================================================
// 2. PROSES CREATE & UPDATE (SIMPAN BUKU)
// =========================================================
$errors = [];
$editBook = null;

if ($action === 'edit') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0 && $pdo !== null) {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $editBook = $stmt->fetch();
        if (!$editBook) {
            setFlashMessage('error', 'Buku yang dicari tidak ditemukan.');
            header('Location: books.php');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $penulis = trim($_POST['penulis'] ?? '');
    $penerbit = trim($_POST['penerbit'] ?? '');
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $cover_image_url = trim($_POST['cover_image_url'] ?? '');
    $existing_cover = $_POST['existing_cover'] ?? '';

    // Validasi input
    if (empty($judul)) $errors[] = 'Judul buku wajib diisi.';
    if ($category_id <= 0) $errors[] = 'Kategori wajib dipilih.';
    if (empty($penulis)) $errors[] = 'Nama penulis wajib diisi.';
    if ($harga <= 0) $errors[] = 'Harga buku harus lebih besar dari 0.';
    if ($stok < 0) $errors[] = 'Stok tidak boleh bernilai negatif.';

    // Penanganan upload cover image
    $finalCoverImage = $existing_cover;

    // A. Jika ada file upload
    if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['cover_file']['tmp_name'];
        $fileName = $_FILES['cover_file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExt, $allowedExts)) {
            $newFileName = 'book_' . time() . '_' . rand(100, 999) . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmp, $destination)) {
                $publicDestination = __DIR__ . '/../public/uploads/books/' . $newFileName;
                @copy($destination, $publicDestination);
                $finalCoverImage = 'uploads/books/' . $newFileName;
            } else {
                $errors[] = 'Gagal menyimpan file cover yang diunggah.';
            }
        } else {
            $errors[] = 'Format file cover tidak valid. Gunakan format JPG, PNG, atau WEBP.';
        }
    } 
    // B. Jika menggunakan URL gambar
    elseif (!empty($cover_image_url)) {
        $finalCoverImage = $cover_image_url;
    }

    if (empty($finalCoverImage)) {
        $finalCoverImage = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600&auto=format&fit=crop&q=80';
    }

    // Jika tidak ada error validasi, simpan ke database
    if (empty($errors) && $pdo !== null) {
        try {
            $bookId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($bookId > 0) {
                // UPDATE
                $stmt = $pdo->prepare("
                    UPDATE books SET 
                        category_id = :category_id,
                        judul = :judul,
                        penulis = :penulis,
                        penerbit = :penerbit,
                        harga = :harga,
                        stok = :stok,
                        deskripsi = :deskripsi,
                        cover_image = :cover_image
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':category_id' => $category_id,
                    ':judul' => $judul,
                    ':penulis' => $penulis,
                    ':penerbit' => $penerbit,
                    ':harga' => $harga,
                    ':stok' => $stok,
                    ':deskripsi' => $deskripsi,
                    ':cover_image' => $finalCoverImage,
                    ':id' => $bookId
                ]);
                setFlashMessage('success', "Buku \"{$judul}\" berhasil diperbarui.");
            } else {
                // CREATE (INSERT)
                $stmt = $pdo->prepare("
                    INSERT INTO books (category_id, judul, penulis, penerbit, harga, stok, deskripsi, cover_image) 
                    VALUES (:category_id, :judul, :penulis, :penerbit, :harga, :stok, :deskripsi, :cover_image)
                ");
                $stmt->execute([
                    ':category_id' => $category_id,
                    ':judul' => $judul,
                    ':penulis' => $penulis,
                    ':penerbit' => $penerbit,
                    ':harga' => $harga,
                    ':stok' => $stok,
                    ':deskripsi' => $deskripsi,
                    ':cover_image' => $finalCoverImage
                ]);
                setFlashMessage('success', "Buku baru \"{$judul}\" berhasil ditambahkan.");
            }

            header('Location: books.php');
            exit;

        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// =========================================================
// 3. READ (AMBIL DAFTAR BUKU)
// =========================================================
$books = [];
if ($action === 'list' && $pdo !== null) {
    try {
        $sql = "SELECT b.*, c.nama_kategori 
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (b.judul LIKE :search OR b.penulis LIKE :search OR b.penerbit LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if ($categoryFilter > 0) {
            $sql .= " AND b.category_id = :cat";
            $params[':cat'] = $categoryFilter;
        }

        $sql .= " ORDER BY b.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $books = $stmt->fetchAll();
    } catch (PDOException $e) {}
}

require_once __DIR__ . '/layout/header.php';
?>

<!-- =========================================================
     TAMPILAN 1: FORM TAMBAH / EDIT BUKU
     ========================================================= -->
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="max-w-4xl mx-auto">
        
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="books.php" class="text-xs font-semibold text-slate-500 hover:text-primary transition-colors flex items-center gap-1 mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Kembali ke Daftar Buku</span>
                </a>
                <h2 class="font-serif text-2xl font-bold text-slate-900">
                    <?= $action === 'edit' ? 'Edit Data Buku' : 'Tambah Buku Baru' ?>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Isi seluruh informasi detail buku untuk dimasukkan ke katalog online.</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                <p class="font-bold mb-1">Terdapat beberapa kesalahan pengisian:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm">
            <form action="books.php?action=<?= $action ?><?= $editBook ? '&id=' . $editBook['id'] : '' ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <?php if ($editBook): ?>
                    <input type="hidden" name="id" value="<?= $editBook['id'] ?>">
                    <input type="hidden" name="existing_cover" value="<?= htmlspecialchars($editBook['cover_image']) ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Judul Buku -->
                    <div class="md:col-span-2">
                        <label for="judul" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Judul Buku <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="judul" 
                            name="judul" 
                            required
                            value="<?= htmlspecialchars($_POST['judul'] ?? $editBook['judul'] ?? '') ?>"
                            placeholder="Contoh: Laut Bercerita"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/10 text-xs sm:text-sm text-slate-800 outline-none transition-all"
                        >
                    </div>

                    <!-- Kategori -->
                    <div>
                        <label for="category_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Kategori <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            id="category_id" 
                            name="category_id" 
                            required
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/10 text-xs sm:text-sm text-slate-800 outline-none bg-white transition-all"
                        >
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php 
                                $selected = (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) || 
                                            (isset($editBook['category_id']) && $editBook['category_id'] == $cat['id']);
                                ?>
                                <option value="<?= $cat['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Penulis -->
                    <div>
                        <label for="penulis" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Nama Penulis / Pengarang <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="penulis" 
                            name="penulis" 
                            required
                            value="<?= htmlspecialchars($_POST['penulis'] ?? $editBook['penulis'] ?? '') ?>"
                            placeholder="Contoh: Leila S. Chudori"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/10 text-xs sm:text-sm text-slate-800 outline-none transition-all"
                        >
                    </div>

                    <!-- Penerbit -->
                    <div>
                        <label for="penerbit" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Nama Penerbit
                        </label>
                        <input 
                            type="text" 
                            id="penerbit" 
                            name="penerbit" 
                            value="<?= htmlspecialchars($_POST['penerbit'] ?? $editBook['penerbit'] ?? '') ?>"
                            placeholder="Contoh: Kepustakaan Populer Gramedia"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/10 text-xs sm:text-sm text-slate-800 outline-none transition-all"
                        >
                    </div>

                    <!-- Harga -->
                    <div>
                        <label for="harga" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Harga Satuan (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="harga" 
                            name="harga" 
                            required
                            min="0"
                            step="1000"
                            value="<?= htmlspecialchars($_POST['harga'] ?? $editBook['harga'] ?? '95000') ?>"
                            placeholder="95000"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/10 text-xs sm:text-sm text-slate-800 outline-none transition-all"
                        >
                    </div>

                    <!-- Stok -->
                    <div>
                        <label for="stok" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Jumlah Stok Tersedia <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="stok" 
                            name="stok" 
                            required
                            min="0"
                            value="<?= htmlspecialchars($_POST['stok'] ?? $editBook['stok'] ?? '20') ?>"
                            placeholder="20"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/10 text-xs sm:text-sm text-slate-800 outline-none transition-all"
                        >
                    </div>

                    <!-- Upload Cover Image (Opsi File & URL) -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Upload File Sampul Buku (JPG/PNG)
                        </label>
                        <input 
                            type="file" 
                            name="cover_file" 
                            accept="image/*"
                            class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer"
                        >
                        <p class="text-[10px] text-slate-400 mt-1">Atau gunakan URL gambar di bawah jika tidak mengunggah file.</p>
                    </div>

                    <!-- URL Cover Image -->
                    <div class="md:col-span-2">
                        <label for="cover_image_url" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Atau Link URL Gambar Cover (Online)
                        </label>
                        <input 
                            type="url" 
                            id="cover_image_url" 
                            name="cover_image_url" 
                            value="<?= htmlspecialchars($_POST['cover_image_url'] ?? $editBook['cover_image'] ?? '') ?>"
                            placeholder="https://images.unsplash.com/..."
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/10 text-xs sm:text-sm text-slate-800 outline-none transition-all"
                        >
                        <?php if ($editBook && !empty($editBook['cover_image'])): ?>
                            <div class="mt-3 flex items-center gap-3">
                                <span class="text-xs text-slate-400">Sampul Saat Ini:</span>
                                <img src="<?= htmlspecialchars($editBook['cover_image']) ?>" alt="Preview" class="w-12 h-16 object-cover rounded shadow-sm border border-slate-200">
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Deskripsi Buku -->
                    <div class="md:col-span-2">
                        <label for="deskripsi" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Sinopsis / Deskripsi Buku
                        </label>
                        <textarea 
                            id="deskripsi" 
                            name="deskripsi" 
                            rows="4" 
                            placeholder="Tuliskan sinopsis singkat, poin penting buku, atau latar belakang kisah..."
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/10 text-xs sm:text-sm text-slate-800 outline-none transition-all"
                        ><?= htmlspecialchars($_POST['deskripsi'] ?? $editBook['deskripsi'] ?? '') ?></textarea>
                    </div>

                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="books.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-xs shadow-md hover:opacity-95 transition-all">
                        <?= $action === 'edit' ? 'Simpan Perubahan' : 'Tambahkan Buku' ?>
                    </button>
                </div>

            </form>
        </div>

    </div>

<!-- =========================================================
     TAMPILAN 2: DAFTAR BUKU (TABLE LIST & FILTER)
     ========================================================= -->
<?php else: ?>

    <!-- Action Bar & Filter -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="font-serif text-2xl font-bold text-slate-900">Katalog & Inventaris Buku</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kelola informasi karya, penetapan harga, dan perputaran stok buku.</p>
        </div>

        <a href="books.php?action=create" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-xs shadow-md hover:opacity-95 transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Tambah Buku Baru</span>
        </a>
    </div>

    <!-- Search & Category Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm mb-6 flex flex-col sm:flex-row items-center justify-between gap-3">
        
        <form action="books.php" method="GET" class="w-full sm:w-auto flex-1 flex flex-col sm:flex-row items-center gap-3">
            <div class="relative w-full sm:max-w-xs">
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Cari judul, penulis, penerbit..." 
                    class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
            </div>

            <select 
                name="category_id" 
                onchange="this.form.submit()" 
                class="w-full sm:w-auto px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 outline-none bg-white"
            >
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-primary hover:text-white font-semibold text-xs transition-colors">
                Terapkan
            </button>

            <?php if (!empty($search) || $categoryFilter > 0): ?>
                <a href="books.php" class="text-xs font-semibold text-rose-500 hover:underline">
                    Reset
                </a>
            <?php endif; ?>
        </form>

        <span class="text-xs text-slate-400 whitespace-nowrap">
            Total: <strong class="text-slate-800"><?= count($books) ?></strong> buku
        </span>

    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 uppercase text-[10px] font-bold tracking-wider text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Buku & Cover</th>
                        <th class="py-3.5 px-6">Kategori</th>
                        <th class="py-3.5 px-6">Penerbit</th>
                        <th class="py-3.5 px-6">Harga</th>
                        <th class="py-3.5 px-6">Stok</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $b): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                
                                <!-- Cover & Title -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-14 rounded-lg overflow-hidden bg-slate-100 shrink-0 shadow-sm border border-slate-200/60">
                                            <img 
                                                src="<?= htmlspecialchars($b['cover_image']) ?>" 
                                                alt="cover" 
                                                class="w-full h-full object-cover"
                                                onerror="this.src='https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600&auto=format&fit=crop&q=80'"
                                            >
                                        </div>
                                        <div class="truncate max-w-[220px]">
                                            <p class="font-bold text-slate-900 truncate" title="<?= htmlspecialchars($b['judul']) ?>">
                                                <?= htmlspecialchars($b['judul']) ?>
                                            </p>
                                            <p class="text-[11px] text-slate-400 truncate"><?= htmlspecialchars($b['penulis']) ?></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-primary/10 text-primary">
                                        <?= htmlspecialchars($b['nama_kategori'] ?? 'Kategori') ?>
                                    </span>
                                </td>

                                <!-- Publisher -->
                                <td class="py-4 px-6 text-slate-500 truncate max-w-[150px]">
                                    <?= htmlspecialchars($b['penerbit']) ?>
                                </td>

                                <!-- Price -->
                                <td class="py-4 px-6 font-bold text-slate-900">
                                    <?= formatRupiah($b['harga']) ?>
                                </td>

                                <!-- Stock -->
                                <td class="py-4 px-6">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $b['stok'] > 10 ? 'bg-emerald-50 text-emerald-700' : ($b['stok'] > 0 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') ?>">
                                        <?= (int)$b['stok'] ?> eks
                                    </span>
                                </td>

                                <!-- Action Buttons -->
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a 
                                            href="books.php?action=edit&id=<?= $b['id'] ?>" 
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-primary hover:bg-primary/5 transition-colors"
                                            title="Edit Buku"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>

                                        <a 
                                            href="books.php?action=delete&id=<?= $b['id'] ?>" 
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus buku \'<?= addslashes($b['judul']) ?>\'?')"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Hapus Buku"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </a>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <p class="text-sm">Tidak ada data buku yang ditemukan.</p>
                                <a href="books.php?action=create" class="mt-2 inline-block text-xs font-bold text-primary hover:underline">
                                    + Tambah Buku Pertama Sekarang
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
