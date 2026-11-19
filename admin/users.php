<?php
/**
 * Bloom&Books - Management Data Pengguna (Users Management)
 * Fitur: Melihat seluruh user terdaftar (Admin & User), filter role, pencarian, dan pembuatan admin baru.
 */

$pageTitle = 'Manajemen Pengguna';
$activePage = 'users';

require_once __DIR__ . '/auth_check.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// =========================================================
// 1. UBAH ROLE PENGGUNA (TOGGLE ROLE)
// =========================================================
if ($action === 'toggle_role') {
    $targetId = (int)($_GET['id'] ?? 0);
    $newRole = trim($_GET['new_role'] ?? 'user');

    if ($targetId === (int)$admin['id']) {
        setFlashMessage('error', 'Anda tidak dapat mengubah role akun Anda sendiri yang sedang aktif.');
    } elseif ($targetId > 0 && in_array($newRole, ['admin', 'user']) && $pdo !== null) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
            $stmt->execute([':role' => $newRole, ':id' => $targetId]);
            setFlashMessage('success', "Role pengguna berhasil diubah menjadi: " . ucfirst($newRole));
        } catch (PDOException $e) {
            setFlashMessage('error', "Gagal mengubah role: " . $e->getMessage());
        }
    }
    header('Location: users.php');
    exit;
}

// =========================================================
// 2. HAPUS PENGGUNA
// =========================================================
if ($action === 'delete') {
    $targetId = (int)($_GET['id'] ?? 0);
    if ($targetId === (int)$admin['id']) {
        setFlashMessage('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
    } elseif ($targetId > 0 && $pdo !== null) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $targetId]);
            setFlashMessage('success', "Akun pengguna berhasil dihapus dari sistem.");
        } catch (PDOException $e) {
            setFlashMessage('error', "Gagal menghapus user: " . $e->getMessage());
        }
    }
    header('Location: users.php');
    exit;
}

// =========================================================
// 3. TAMBAH AKUN BARU OLEH ADMIN
// =========================================================
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : 'user';

    if (empty($nama) || empty($email) || empty($password)) {
        $errors[] = 'Nama, Email, dan Kata Sandi wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Kata sandi minimal 6 karakter.';
    } else {
        if ($pdo !== null) {
            try {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $check->execute([':email' => $email]);
                if ($check->fetch()) {
                    $errors[] = 'Alamat email ini sudah terdaftar.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $avatar = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80';
                    $ins = $pdo->prepare("
                        INSERT INTO users (nama, email, password_hash, role, foto_profil)
                        VALUES (:nama, :email, :hash, :role, :avatar)
                    ");
                    $ins->execute([
                        ':nama' => $nama,
                        ':email' => $email,
                        ':hash' => $hash,
                        ':role' => $role,
                        ':avatar' => $avatar
                    ]);
                    setFlashMessage('success', "Akun baru untuk {$nama} ({$role}) berhasil dibuat.");
                    header('Location: users.php');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// =========================================================
// 4. AMBIL DAFTAR PENGGUNA
// =========================================================
$users = [];
if ($pdo !== null) {
    try {
        $sql = "
            SELECT u.*, 
                   (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count
            FROM users u 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (u.nama LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($roleFilter)) {
            $sql .= " AND u.role = :role";
            $params[':role'] = $roleFilter;
        }

        $sql .= " ORDER BY u.id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
    } catch (PDOException $e) {}
}

require_once __DIR__ . '/layout/header.php';
?>

<!-- Header Section & Tombol Tambah -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="font-serif text-2xl font-bold text-slate-900">Manajemen Data Pengguna</h2>
        <p class="text-xs text-slate-500 mt-0.5">Kelola hak akses role administrator dan data pelanggan toko.</p>
    </div>

    <button type="button" onclick="document.getElementById('modalAddUser').classList.remove('hidden')" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-xs shadow-md hover:opacity-95 transition-all flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
        <span>Tambah Pengguna / Admin</span>
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
        <p class="font-bold mb-1">Gagal menyimpan data pengguna:</p>
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Filter & Search Bar -->
<div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm mb-6 flex flex-col sm:flex-row items-center justify-between gap-3">
    
    <form action="users.php" method="GET" class="w-full sm:w-auto flex-1 flex flex-col sm:flex-row items-center gap-3">
        <div class="relative w-full sm:max-w-xs">
            <input 
                type="text" 
                name="search" 
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Cari nama atau email..." 
                class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-primary focus:ring-1 focus:ring-primary outline-none"
            >
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
        </div>

        <select 
            name="role" 
            onchange="this.form.submit()" 
            class="w-full sm:w-auto px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 outline-none bg-white"
        >
            <option value="">Semua Role</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Administrator</option>
            <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>Customer / Member</option>
        </select>

        <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-primary hover:text-white font-semibold text-xs transition-colors">
            Filter
        </button>

        <?php if (!empty($search) || !empty($roleFilter)): ?>
            <a href="users.php" class="text-xs font-semibold text-rose-500 hover:underline">
                Reset
            </a>
        <?php endif; ?>
    </form>

    <span class="text-xs text-slate-400 whitespace-nowrap">
        Total: <strong class="text-slate-800"><?= count($users) ?></strong> akun terdaftar
    </span>

</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 uppercase text-[10px] font-bold tracking-wider text-slate-400 border-b border-slate-100">
                <tr>
                    <th class="py-3.5 px-6">ID & Profil</th>
                    <th class="py-3.5 px-6">Email Akun</th>
                    <th class="py-3.5 px-6">Hak Akses (Role)</th>
                    <th class="py-3.5 px-6">Total Belanja</th>
                    <th class="py-3.5 px-6">Tanggal Bergabung</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $u): ?>
                        <?php $isSelf = ($u['id'] == $admin['id']); ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            
                            <!-- Profile & Name -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 overflow-hidden ring-2 ring-slate-100 shrink-0 flex items-center justify-center font-bold text-slate-600">
                                        <?php if (!empty($u['foto_profil'])): ?>
                                            <img src="<?= htmlspecialchars($u['foto_profil']) ?>" alt="avatar" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 flex items-center gap-1.5">
                                            <?= htmlspecialchars($u['nama']) ?>
                                            <?php if ($isSelf): ?>
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-primary text-white">Akun Anda</span>
                                            <?php endif; ?>
                                        </p>
                                        <span class="text-[10px] text-slate-400 font-mono">UID: #<?= str_pad($u['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="py-4 px-6 font-medium text-slate-700">
                                <?= htmlspecialchars($u['email']) ?>
                            </td>

                            <!-- Role -->
                            <td class="py-4 px-6">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-primary text-white">
                                        Administrator
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        Customer / Member
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Order Count -->
                            <td class="py-4 px-6 font-bold text-slate-700">
                                <?= (int)$u['order_count'] ?> transaksi
                            </td>

                            <!-- Created At -->
                            <td class="py-4 px-6 text-slate-400 whitespace-nowrap">
                                <?= date('d M Y', strtotime($u['created_at'])) ?>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <?php if (!$isSelf): ?>
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($u['role'] === 'user'): ?>
                                            <a 
                                                href="users.php?action=toggle_role&id=<?= $u['id'] ?>&new_role=admin" 
                                                onclick="return confirm('Promosikan <?= addslashes($u['nama']) ?> menjadi Administrator?')"
                                                class="px-2.5 py-1 rounded-lg bg-primary/10 hover:bg-primary text-primary hover:text-white font-bold text-[10px] transition-colors"
                                                title="Jadikan Admin"
                                            >
                                                Jadikan Admin
                                            </a>
                                        <?php else: ?>
                                            <a 
                                                href="users.php?action=toggle_role&id=<?= $u['id'] ?>&new_role=user" 
                                                onclick="return confirm('Ubah role <?= addslashes($u['nama']) ?> menjadi Customer biasa?')"
                                                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-[10px] transition-colors"
                                                title="Turunkan ke User"
                                            >
                                                Ubah ke User
                                            </a>
                                        <?php endif; ?>

                                        <a 
                                            href="users.php?action=delete&id=<?= $u['id'] ?>" 
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus akun \'<?= addslashes($u['nama']) ?>\'?')"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Hapus Akun"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-[10px] text-slate-400 italic">Sedang Login</span>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400">
                            Tidak ditemukan data pengguna yang cocok.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- =========================================================
     MODAL TAMBAH PENGGUNA BARU
     ========================================================= -->
<div id="modalAddUser" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl border border-slate-100 relative">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
            <div>
                <h3 class="font-serif font-bold text-xl text-slate-900">Tambah Akun Baru</h3>
                <p class="text-xs text-slate-400">Buat akun staf Admin atau Member baru.</p>
            </div>
            <button type="button" onclick="document.getElementById('modalAddUser').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg p-1">
                ✕
            </button>
        </div>

        <form action="users.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_user">

            <div>
                <label for="new_nama" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Nama Lengkap
                </label>
                <input type="text" id="new_nama" name="nama" required placeholder="Contoh: Rian Anggara" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-primary">
            </div>

            <div>
                <label for="new_email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Alamat Email
                </label>
                <input type="email" id="new_email" name="email" required placeholder="nama@bloombooks.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-primary">
            </div>

            <div>
                <label for="new_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Kata Sandi (Min. 6 Karakter)
                </label>
                <input type="password" id="new_password" name="password" required minlength="6" placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-primary">
            </div>

            <div>
                <label for="new_role" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Role / Hak Akses
                </label>
                <select id="new_role" name="role" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs outline-none bg-white font-bold">
                    <option value="admin">Administrator (Akses Back-Office)</option>
                    <option value="user" selected>User / Pelanggan Umum</option>
                </select>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalAddUser').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-xs shadow-md hover:opacity-95">
                    Buat Akun
                </button>
            </div>
        </form>

    </div>
</div>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
