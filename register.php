<?php
/**
 * Bloom&Books - Dedicated Registration Page
 * Halaman pendaftaran mandiri pengguna baru (Role: 'user').
 */

require_once __DIR__ . '/config/database.php';

// Jika pengguna sudah login, alihkan
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit;
}

$errorMessage = '';
$successMessage = '';

// Data isian awal agar tidak hilang saat terjadi validasi gagal
$formData = [
    'nama' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    $formData['nama'] = $nama;
    $formData['email'] = $email;

    // Validasi Formulir
    if (empty($nama) || empty($email) || empty($password) || empty($confirmPassword)) {
        $errorMessage = 'Seluruh bidang formulir wajib diisi.';
    } elseif (mb_strlen($nama) < 3) {
        $errorMessage = 'Nama lengkap minimal terdiri dari 3 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Format alamat email tidak valid.';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Kata sandi minimal harus terdiri dari 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Konfirmasi kata sandi tidak cocok dengan kata sandi yang dimasukkan.';
    } else {
        // Cek ketersediaan database PDO
        if ($pdo !== null) {
            try {
                // Cek apakah email sudah terdaftar
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $checkStmt->execute([':email' => $email]);
                
                if ($checkStmt->fetch()) {
                    $errorMessage = 'Alamat email ini sudah terdaftar. Silakan gunakan email lain atau masuk.';
                } else {
                    // Buat hash kata sandi aman
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Avatar acak estetis default
                    $defaultAvatar = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80';

                    // Insert ke tabel users dengan role 'user'
                    $insertStmt = $pdo->prepare("
                        INSERT INTO users (nama, email, password_hash, role, foto_profil) 
                        VALUES (:nama, :email, :password_hash, 'user', :foto_profil)
                    ");
                    $insertStmt->execute([
                        ':nama' => $nama,
                        ':email' => $email,
                        ':password_hash' => $passwordHash,
                        ':foto_profil' => $defaultAvatar
                    ]);

                    // Redirect ke login dengan notifikasi sukses
                    header('Location: login.php?registered=success');
                    exit;
                }
            } catch (PDOException $e) {
                $errorMessage = 'Gagal mendaftarkan akun ke database: ' . $e->getMessage();
            }
        } else {
            // Jika XAMPP belum aktif, berikan instruksi ramah
            $errorMessage = 'Koneksi ke database MySQL XAMPP belum aktif. Harap jalankan XAMPP MySQL dan import schema.sql terlebih dahulu.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru - Bloom&Books</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600;1,700&family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#3A0CA3',
                            hover: '#2E0983',
                            light: '#4E12D4',
                            50: '#F4F0FD',
                        },
                        secondary: {
                            DEFAULT: '#F72585',
                            hover: '#D91A72',
                            light: '#FDE8F3',
                        },
                        surface: '#FAFAFE',
                    },
                    fontFamily: {
                        sans: ['"Inter"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        serif: ['"Inter"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        logo: ['"Cormorant Garamond"', '"Cinzel"', 'serif'],
                    },
                    boxShadow: {
                        'glass': '0 8px 32px 0 rgba(58, 12, 163, 0.08)',
                        'glass-lg': '0 16px 48px 0 rgba(58, 12, 163, 0.14)',
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Glassmorphic Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-surface text-slate-800 antialiased min-h-screen flex flex-col justify-between relative selection:bg-secondary selection:text-white">

    <!-- Ambient Glow Orbs -->
    <div class="glow-blob-purple top-0 right-10"></div>
    <div class="glow-blob-magenta bottom-10 left-10"></div>

    <!-- Top Minimal Navigation -->
    <nav class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 relative z-10 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-3 group">
            <div class="relative w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#3A0CA3] via-[#7209B7] to-[#F72585] p-[1.5px] shadow-sm group-hover:scale-105 transition-all">
                <div class="w-full h-full rounded-[14px] bg-gradient-to-br from-[#1E085A] to-[#3A0CA3] flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 3.5C12 3.5 13.8 6.5 13.8 8.2C13.8 9.2 13 10 12 10C11 10 10.2 9.2 10.2 8.2C10.2 6.5 12 3.5 12 3.5Z" fill="#F72585"/>
                        <path d="M12 9C13.6 7 16.8 6.5 17.8 8C18.5 9.1 17.2 11.2 14.5 11.8C13.5 12 12.5 11.6 12 11" stroke="#FDE8F3" stroke-width="1.2" stroke-linecap="round"/>
                        <path d="M12 9C10.4 7 7.2 6.5 6.2 8C5.5 9.1 6.8 11.2 9.5 11.8C10.5 12 11.5 11.6 12 11" stroke="#FDE8F3" stroke-width="1.2" stroke-linecap="round"/>
                        <path d="M12 14.2V20" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
                        <path d="M12 14.5C9.8 13.2 6.5 13.2 3.6 14.7C3.3 14.9 3 15.2 3 15.6V19.2C3 19.7 3.5 20.1 4 19.9C6.8 18.7 9.8 18.8 12 20C14.2 18.8 17.2 18.7 20 19.9C20.5 20.1 21 19.7 21 19.2V15.6C21 15.2 20.7 14.9 20.4 14.7C17.5 13.2 14.2 13.2 12 14.5Z" stroke="white" stroke-width="1.3" stroke-linejoin="round"/>
                        <circle cx="12" cy="3.5" r="0.9" fill="#FFD166"/>
                    </svg>
                </div>
            </div>
            <span class="font-logo text-xl font-bold tracking-tight text-slate-900 flex items-center">
                <span>Bloom</span>
                <span class="fancy-ampersand text-xl text-secondary mx-0.5 select-none font-normal italic">&amp;</span>
                <span class="text-primary">Books</span>
            </span>
        </a>

        <a href="login.php" class="text-xs font-semibold text-slate-500 hover:text-primary transition-colors flex items-center gap-1">
            <span>Sudah punya akun? Masuk</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </a>
    </nav>

    <!-- Center Registration Glassmorphic Container -->
    <main class="flex-grow flex items-center justify-center px-4 py-8 relative z-10">
        <div class="w-full max-w-lg">
            
            <div class="glass-card rounded-3xl p-8 sm:p-10 shadow-glass-lg border border-white">
                
                <!-- Card Header -->
                <div class="text-center mb-8">
                    <span class="text-[11px] font-bold uppercase tracking-widest text-secondary block mb-1">Mulai Perjalanan Membacamu</span>
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900">Buat Akun Bloom&Books</h1>
                    <p class="text-xs text-slate-500 mt-2">Daftar sekarang dan nikmati kurasi buku terbaik untuk memperluas wawasan Anda.</p>
                </div>

                <!-- Error Alert -->
                <?php if (!empty($errorMessage)): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span><?= htmlspecialchars($errorMessage) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form action="register.php" method="POST" class="space-y-4">
                    
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="nama" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Nama Lengkap
                        </label>
                        <input 
                            type="text" 
                            id="nama" 
                            name="nama" 
                            required
                            value="<?= htmlspecialchars($formData['nama']) ?>"
                            placeholder="Contoh: Nasya Putri" 
                            class="glass-input w-full px-4 py-3 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400"
                        >
                    </div>

                    <!-- Alamat Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Alamat Email
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            value="<?= htmlspecialchars($formData['email']) ?>"
                            placeholder="nama@email.com" 
                            class="glass-input w-full px-4 py-3 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400"
                        >
                    </div>

                    <!-- Password Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Kata Sandi
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                minlength="6"
                                placeholder="Min. 6 Karakter" 
                                class="glass-input w-full px-4 py-3 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400"
                            >
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Konfirmasi Sandi
                            </label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required
                                minlength="6"
                                placeholder="Ulangi Sandi" 
                                class="glass-input w-full px-4 py-3 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400"
                            >
                        </div>
                    </div>

                    <!-- Terms agreement note -->
                    <p class="text-[11px] text-slate-500 pt-2 leading-relaxed">
                        Dengan mendaftar, Anda menyetujui Ketentuan Layanan dan Kebijakan Privasi toko online Bloom&Books.
                    </p>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full mt-4 py-3.5 px-4 rounded-xl bg-gradient-to-r from-primary via-indigo-600 to-secondary text-white font-semibold text-xs sm:text-sm shadow-md hover:opacity-95 transition-all text-center"
                        id="submitRegisterBtn"
                    >
                        Daftar Sebagai Pembaca
                    </button>

                </form>

                <!-- Footer link to Login -->
                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-500">
                        Sudah memiliki akun? 
                        <a href="login.php" class="text-primary font-bold hover:text-secondary transition-colors ml-1">
                            Masuk di Sini
                        </a>
                    </p>
                </div>

            </div>

        </div>
    </main>

    <!-- Bottom Minimal Footer -->
    <footer class="py-6 text-center text-xs text-slate-400 relative z-10">
        &copy; <?= date('Y') ?> Bloom&Books. Seluruh Hak Cipta Dilindungi.
    </footer>

</body>
</html>
