<?php
/**
 * Bloom&Books - Pengaturan Toko & Profil Administrator
 */

$pageTitle = 'Pengaturan Toko & Profil';
$activePage = 'settings';

require_once __DIR__ . '/auth_check.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($nama) || empty($email)) {
        $error = 'Nama dan email tidak boleh kosong.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        if ($pdo !== null) {
            try {
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $error = 'Kata sandi baru minimal 6 karakter.';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'Konfirmasi kata sandi tidak cocok.';
                    } else {
                        $hash = password_hash($new_password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("UPDATE users SET nama = :nama, email = :email, password_hash = :hash WHERE id = :id");
                        $stmt->execute([':nama' => $nama, ':email' => $email, ':hash' => $hash, ':id' => $admin['id']]);
                        $_SESSION['nama'] = $nama;
                        $_SESSION['email'] = $email;
                        setFlashMessage('success', 'Profil dan kata sandi Anda berhasil diperbarui!');
                        header('Location: settings.php');
                        exit;
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nama = :nama, email = :email WHERE id = :id");
                    $stmt->execute([':nama' => $nama, ':email' => $email, ':id' => $admin['id']]);
                    $_SESSION['nama'] = $nama;
                    $_SESSION['email'] = $email;
                    setFlashMessage('success', 'Profil admin berhasil diperbarui!');
                    header('Location: settings.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/layout/header.php';
?>

<div class="max-w-4xl mx-auto space-y-8">
    
    <div>
        <h2 class="font-serif text-2xl font-bold text-slate-900">Pengaturan Toko & Akun</h2>
        <p class="text-xs text-slate-500 mt-0.5">Konfigurasi preferensi toko online dan kelola kredensial login admin.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Form Profil Admin -->
    <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm">
        <h3 class="font-serif font-bold text-lg text-slate-900 mb-1">Profil Administrator</h3>
        <p class="text-xs text-slate-400 mb-6">Perbarui nama, alamat email kontak, atau ubah kata sandi login Anda.</p>

        <form action="settings.php" method="POST" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Lengkap
                    </label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($admin['nama']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Alamat Email Login
                    </label>
                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Kata Sandi Baru (Kosongkan jika tidak diubah)
                    </label>
                    <input type="password" name="new_password" placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Ulangi Kata Sandi Baru
                    </label>
                    <input type="password" name="confirm_password" placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-primary">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-xs shadow-md hover:opacity-95 transition-all">
                    Simpan Perubahan Profil
                </button>
            </div>
        </form>
    </div>

    <!-- Informasi Lingkungan Sistem & Database -->
    <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm">
        <h3 class="font-serif font-bold text-lg text-slate-900 mb-1">Status Sistem & Lingkungan Server</h3>
        <p class="text-xs text-slate-400 mb-6">Informasi teknis koneksi backend dan penyimpanan file.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Versi PHP Server</span>
                <span class="font-bold text-slate-800 text-sm"><?= phpversion() ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Nama Database MySQL</span>
                <span class="font-bold text-primary text-sm font-mono"><?= DB_NAME ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Direktori Upload Cover</span>
                <span class="font-bold text-emerald-700 text-xs font-mono">uploads/books/ (Siap)</span>
            </div>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
