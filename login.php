<?php
/**
 * Bloom&Books - Dedicated Login Page
 * Halaman otentikasi mandiri beraksen Glassmorphism dengan pengalihan berbasis Role.
 */

require_once __DIR__ . '/config/database.php';

// Jika pengguna sudah dalam keadaan login, alihkan sesuai role
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

$errorMessage = '';
$successMessage = '';

// Ambil notifikasi dari query parameter jika ada (misal setelah registrasi atau logout)
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $successMessage = 'Pendaftaran akun berhasil! Silakan masuk dengan email dan kata sandi Anda.';
}
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $successMessage = 'Anda telah berhasil keluar dari akun.';
}

// Proses form login saat metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($email) || empty($password)) {
        $errorMessage = 'Harap isi alamat email dan kata sandi Anda.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Format email yang dimasukkan tidak valid.';
    } else {
        $authenticatedUser = null;

        // 1. Cek Autentikasi melalui Database PDO
        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    $isPasswordValid = password_verify($password, $user['password_hash']);
                    
                    // Fallback verifikasi untuk akun demo awal bila salt berbeda di runtime
                    if (!$isPasswordValid) {
                        if (($email === 'admin@bloombooks.com' && $password === 'admin123') ||
                            ($email === 'user@bloombooks.com' && $password === 'user123')) {
                            $isPasswordValid = true;
                            // Update hash bcrypt lokal untuk keamanan berkelanjutan
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            $up = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
                            $up->execute([':hash' => $newHash, ':id' => $user['id']]);
                        }
                    }

                    if ($isPasswordValid) {
                        $authenticatedUser = $user;
                    }
                }
            } catch (PDOException $e) {
                // Tangani kasus tabel belum ada
                $authenticatedUser = null;
            }
        }

        // 2. Fallback Autentikasi Akun Demo (Bila database XAMPP belum aktif)
        if (!$authenticatedUser && $pdo === null) {
            if ($email === 'admin@bloombooks.com' && $password === 'admin123') {
                $authenticatedUser = [
                    'id' => 1,
                    'nama' => 'Admin Bloom',
                    'email' => 'admin@bloombooks.com',
                    'role' => 'admin',
                    'foto_profil' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80'
                ];
            } elseif ($email === 'user@bloombooks.com' && $password === 'user123') {
                $authenticatedUser = [
                    'id' => 2,
                    'nama' => 'Nasya Reader',
                    'email' => 'user@bloombooks.com',
                    'role' => 'user',
                    'foto_profil' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80'
                ];
            }
        }

        // 3. Evaluasi Hasil Autentikasi
        if ($authenticatedUser) {
            // Set data sesi
            $_SESSION['user_id'] = $authenticatedUser['id'];
            $_SESSION['nama'] = $authenticatedUser['nama'];
            $_SESSION['email'] = $authenticatedUser['email'];
            $_SESSION['role'] = $authenticatedUser['role'];
            $_SESSION['foto_profil'] = $authenticatedUser['foto_profil'] ?? null;

            // Logika Pengalihan Sesuai Role
            if ($authenticatedUser['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit;
        } else {
            $errorMessage = 'Alamat email atau kata sandi yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun - Bloom&Books</title>
    
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

    <!-- Ambient Glowing Light Orbs -->
    <div class="glow-blob-purple top-0 left-10"></div>
    <div class="glow-blob-magenta bottom-10 right-10"></div>

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

        <a href="index.php" class="text-xs font-semibold text-slate-500 hover:text-primary transition-colors flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Kembali ke Beranda</span>
        </a>
    </nav>

    <!-- Center Login Glassmorphic Container -->
    <main class="flex-grow flex items-center justify-center px-4 py-8 relative z-10">
        <div class="w-full max-w-md">
            
            <!-- Glassmorphism Card -->
            <div class="glass-card rounded-3xl p-8 sm:p-10 shadow-glass-lg border border-white">
                
                <!-- Card Header -->
                <div class="text-center mb-8">
                    <span class="text-[11px] font-bold uppercase tracking-widest text-secondary block mb-1">Selamat Datang Kembali</span>
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900">Masuk ke Akun Anda</h1>
                    <p class="text-xs text-slate-500 mt-2">Akses ribuan buku dan kelola keranjang belanja Anda.</p>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($errorMessage)): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span><?= htmlspecialchars($errorMessage) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($successMessage)): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span><?= htmlspecialchars($successMessage) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form Login -->
                <form action="login.php" method="POST" class="space-y-5">
                    
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Alamat Email
                        </label>
                        <div class="relative">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                placeholder="nama@email.com" 
                                class="glass-input w-full px-4 py-3 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Kata Sandi
                            </label>
                            <span class="text-[11px] text-primary hover:underline cursor-pointer">Lupa sandi?</span>
                        </div>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                placeholder="••••••••" 
                                class="glass-input w-full px-4 py-3 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400"
                            >
                        </div>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember" 
                            class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary/30"
                        >
                        <label for="remember" class="ml-2 text-xs text-slate-600">
                            Ingat saya di perangkat ini
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-primary via-indigo-600 to-secondary text-white font-semibold text-xs sm:text-sm shadow-md hover:opacity-95 transition-all text-center"
                        id="submitLoginBtn"
                    >
                        Masuk ke Akun
                    </button>

                </form>

                <!-- Divider & Register Link -->
                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-500">
                        Belum memiliki akun Bloom&Books? 
                        <a href="register.php" class="text-primary font-bold hover:text-secondary transition-colors ml-1">
                            Daftar Sekarang
                        </a>
                    </p>
                </div>

                <!-- Quick Demo Credentials Box -->
                <div class="mt-6 p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 text-[11px] text-slate-600">
                    <p class="font-bold text-slate-700 mb-2 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Kredensial Uji Coba Cepat:
                    </p>
                    <div class="grid grid-cols-2 gap-2 text-[10px]">
                        <button type="button" onclick="fillCreds('admin@bloombooks.com', 'admin123')" class="text-left p-2 rounded-lg bg-white border border-slate-200 hover:border-primary transition-colors">
                            <span class="block font-bold text-primary">Role: Admin</span>
                            <span>admin@bloombooks.com</span>
                            <span class="text-slate-400 block">Pass: admin123</span>
                        </button>
                        <button type="button" onclick="fillCreds('user@bloombooks.com', 'user123')" class="text-left p-2 rounded-lg bg-white border border-slate-200 hover:border-secondary transition-colors">
                            <span class="block font-bold text-secondary">Role: User</span>
                            <span>user@bloombooks.com</span>
                            <span class="text-slate-400 block">Pass: user123</span>
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- Bottom Footer Minimal -->
    <footer class="py-6 text-center text-xs text-slate-400 relative z-10">
        &copy; <?= date('Y') ?> Bloom&Books. Seluruh Hak Cipta Dilindungi.
    </footer>

    <!-- Script Autofill Helper -->
    <script>
        function fillCreds(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
