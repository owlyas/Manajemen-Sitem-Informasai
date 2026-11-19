<?php
/**
 * Bloom&Books - Management Kategori Buku
 * Fitur: Tambah kategori, edit nama & slug kategori, hapus kategori, dan hitung buku per kategori.
 */

$pageTitle = 'Manajemen Kategori';
$activePage = 'categories';

require_once __DIR__ . '/auth_check.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$editCategory = null;

// =========================================================
// 1. DELETE KATEGORI
// =========================================================
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0 && $pdo !== null) {
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlashMessage('success', 'Kategori berhasil dihapus.');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Gagal menghapus kategori: ' . $e->getMessage());
        }
    }
    header('Location: categories.php');
    exit;
}

// =========================================================
// 2. FORM EDIT KATEGORI
// =========================================================
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0 && $pdo !== null) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $editCategory = $stmt->fetch();
    }
}

// =========================================================
// 3. PROSES SIMPAN (CREATE & UPDATE)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $catId = (int)($_POST['id'] ?? 0);

    if (empty($slug) && !empty($nama_kategori)) {
        // Auto-generate slug jika kosong
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama_kategori)));
    }

    if (empty($nama_kategori)) {
        setFlashMessage('error', 'Nama kategori tidak boleh kosong.');
    } else {
        if ($pdo !== null) {
            try {
                if ($catId > 0) {
                    $stmt = $pdo->prepare("UPDATE categories SET nama_kategori = :nama, slug = :slug WHERE id = :id");
                    $stmt->execute([':nama' => $nama_kategori, ':slug' => $slug, ':id' => $catId]);
                    setFlashMessage('success', "Kategori \"{$nama_kategori}\" berhasil diperbarui.");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (nama_kategori, slug) VALUES (:nama, :slug)");
                    $stmt->execute([':nama' => $nama_kategori, ':slug' => $slug]);
                    setFlashMessage('success', "Kategori baru \"{$nama_kategori}\" berhasil ditambahkan.");
                }
                header('Location: categories.php');
                exit;
            } catch (PDOException $e) {
                setFlashMessage('error', "Gagal menyimpan kategori: " . $e->getMessage());
            }
        }
    }
}

// =========================================================
// 4. AMBIL DAFTAR KATEGORI
// =========================================================
$categories = [];
if ($pdo !== null) {
    try {
        $stmt = $pdo->query("
            SELECT c.*, COUNT(b.id) as total_books 
            FROM categories c 
            LEFT JOIN books b ON c.id = b.category_id 
            GROUP BY c.id 
            ORDER BY c.nama_kategori ASC
        ");
        $categories = $stmt->fetchAll();
    } catch (PDOException $e) {}
}

require_once __DIR__ . '/layout/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    
    <!-- Kolom Kiri: Form Tambah / Edit Kategori -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm sticky top-28">
            <h3 class="font-serif font-bold text-lg text-slate-900 mb-1">
                <?= $editCategory ? 'Edit Kategori' : 'Tambah Kategori Baru' ?>
            </h3>
            <p class="text-xs text-slate-500 mb-6">Kelompokkan koleksi buku ke dalam klasifikasi yang rapi.</p>

            <form action="categories.php" method="POST" class="space-y-4">
                <?php if ($editCategory): ?>
                    <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                <?php endif; ?>

                <div>
                    <label for="nama_kategori" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Kategori
                    </label>
                    <input 
                        type="text" 
                        id="nama_kategori" 
                        name="nama_kategori" 
                        required
                        value="<?= htmlspecialchars($editCategory['nama_kategori'] ?? '') ?>"
                        placeholder="Contoh: Fiksi & Sastra"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm outline-none focus:border-primary"
                    >
                </div>

                <div>
                    <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Slug URL (Opsional)
                    </label>
                    <input 
                        type="text" 
                        id="slug" 
                        name="slug" 
                        value="<?= htmlspecialchars($editCategory['slug'] ?? '') ?>"
                        placeholder="fiksi-sastra"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm outline-none focus:border-primary font-mono text-slate-600"
                    >
                    <p class="text-[10px] text-slate-400 mt-1">Otomatis dibuat jika dikosongkan.</p>
                </div>

                <div class="pt-2 flex items-center gap-2">
                    <?php if ($editCategory): ?>
                        <a href="categories.php" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Batal
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="flex-1 py-2.5 px-4 rounded-xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-xs shadow-md hover:opacity-95 transition-all text-center">
                        <?= $editCategory ? 'Perbarui Kategori' : 'Tambahkan Kategori' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Tabel Kategori -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-serif font-bold text-lg text-slate-900">Daftar Kategori Tersedia</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Seluruh kategori yang aktif ditampilkan di navbar dan filter katalog.</p>
                </div>
                <span class="text-xs text-slate-400">Total: <strong class="text-slate-800"><?= count($categories) ?></strong></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 uppercase text-[10px] font-bold tracking-wider text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="py-3.5 px-6">ID</th>
                            <th class="py-3.5 px-6">Nama Kategori</th>
                            <th class="py-3.5 px-6">Slug URL</th>
                            <th class="py-3.5 px-6">Jumlah Buku</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 px-6 font-mono font-bold text-slate-400">#<?= $cat['id'] ?></td>
                                    <td class="py-4 px-6 font-bold text-slate-900"><?= htmlspecialchars($cat['nama_kategori']) ?></td>
                                    <td class="py-4 px-6 font-mono text-[11px] text-slate-500"><?= htmlspecialchars($cat['slug']) ?></td>
                                    <td class="py-4 px-6">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary">
                                            <?= (int)$cat['total_books'] ?> Buku
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="categories.php?action=edit&id=<?= $cat['id'] ?>" class="p-1.5 rounded-lg text-slate-400 hover:text-primary hover:bg-primary/5 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </a>
                                            <a href="categories.php?action=delete&id=<?= $cat['id'] ?>" onclick="return confirm('Hapus kategori ini? Buku yang terhubung mungkin akan terhapus atau kehilangan kategori.')" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">Belum ada kategori yang terdaftar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
