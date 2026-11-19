<?php
// Pastikan middleware auth sudah dijalankan
require_once __DIR__ . '/../auth_check.php';

if (!isset($pageTitle)) {
    $pageTitle = 'Admin Back-Office - Bloom&Books';
}
if (!isset($activePage)) {
    $activePage = 'dashboard';
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Bloom&Books Back-Office</title>
    
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
                            hover: '#2A0876',
                            light: '#4E12D4',
                            50: '#F4F0FD',
                            100: '#E9E2FC',
                        },
                        secondary: {
                            DEFAULT: '#F72585',
                            hover: '#D91A72',
                            light: '#FDE8F3',
                        },
                        dark: '#0F172A',
                    },
                    fontFamily: {
                        sans: ['"Inter"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        serif: ['"Inter"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        logo: ['"Cormorant Garamond"', '"Cinzel"', 'serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased bg-[#F8F9FD]">

    <!-- Admin Wrapper -->
    <div class="flex h-screen overflow-hidden">
        
        <!-- =========================================================
             SIDEBAR NAVIGASI ADMIN (Back-Office Sidebar)
             ========================================================= -->
        <aside id="adminSidebar" class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 transition-all duration-300 z-40 fixed inset-y-0 left-0 lg:static -translate-x-full lg:translate-x-0 border-r border-slate-800">
            
            <!-- Sidebar Header / Logo -->
            <div class="h-20 flex items-center px-6 border-b border-slate-800/80 bg-slate-950/40 justify-between">
                <a href="dashboard.php" class="flex items-center gap-3 group">
                    <div class="relative w-9 h-9 rounded-2xl bg-gradient-to-tr from-[#3A0CA3] via-[#7209B7] to-[#F72585] p-[1.2px] shadow-md shadow-primary/30 group-hover:scale-105 transition-all">
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
                    <div class="flex flex-col">
                        <span class="font-logo text-lg font-bold text-white tracking-tight flex items-center">
                            <span>Bloom</span>
                            <span class="fancy-ampersand text-lg text-secondary mx-0.5 select-none font-normal italic">&amp;</span>
                            <span class="text-indigo-300">Books</span>
                        </span>
                        <span class="text-[9px] tracking-[0.2em] text-slate-400 font-semibold uppercase font-sans -mt-0.5">Back-Office</span>
                    </div>
                </a>

                <!-- Mobile Close Button -->
                <button type="button" onclick="toggleSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-grow py-6 px-4 space-y-1.5 overflow-y-auto">
                
                <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    Menu Utama
                </div>

                <!-- Dashboard -->
                <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= ($activePage === 'dashboard') ? 'bg-primary text-white shadow-md shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                    <svg class="w-4 h-4 shrink-0 <?= ($activePage === 'dashboard') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <!-- Data Buku -->
                <a href="books.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= ($activePage === 'books') ? 'bg-primary text-white shadow-md shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                    <svg class="w-4 h-4 shrink-0 <?= ($activePage === 'books') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span>Data Buku</span>
                </a>

                <!-- Data Kategori -->
                <a href="categories.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= ($activePage === 'categories') ? 'bg-primary text-white shadow-md shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                    <svg class="w-4 h-4 shrink-0 <?= ($activePage === 'categories') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <span>Data Kategori</span>
                </a>

                <!-- Data Transaksi / Pesanan -->
                <a href="orders.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= ($activePage === 'orders') ? 'bg-primary text-white shadow-md shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                    <svg class="w-4 h-4 shrink-0 <?= ($activePage === 'orders') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <span>Data Pesanan</span>
                </a>

                <!-- Data User -->
                <a href="users.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= ($activePage === 'users') ? 'bg-primary text-white shadow-md shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                    <svg class="w-4 h-4 shrink-0 <?= ($activePage === 'users') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Data Pengguna</span>
                </a>

                <!-- Pengaturan -->
                <a href="settings.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= ($activePage === 'settings') ? 'bg-primary text-white shadow-md shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                    <svg class="w-4 h-4 shrink-0 <?= ($activePage === 'settings') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>Pengaturan Toko</span>
                </a>

                <div class="pt-6 pb-2 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    Akses Cepat
                </div>

                <!-- Ke Toko Publik -->
                <a href="../index.php" target="_blank" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800/60 transition-all">
                    <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    <span>Lihat Toko Publik</span>
                </a>

            </div>

            <!-- Sidebar User Card & Logout -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-primary/30 flex items-center justify-center font-bold text-xs text-white ring-2 ring-primary/40 shrink-0 overflow-hidden">
                            <?php if (!empty($admin['foto_profil'])): ?>
                                <img src="<?= htmlspecialchars($admin['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= strtoupper(substr($admin['nama'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($admin['nama']) ?></p>
                            <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-semibold bg-primary/40 text-primary-light">Admin</span>
                        </div>
                    </div>
                    <a href="../logout.php" title="Keluar Akun" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>
            </div>

        </aside>

        <!-- Backdrop Overlay untuk Mobile Sidebar -->
        <div id="sidebarBackdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/60 z-30 hidden lg:hidden backdrop-blur-sm transition-opacity"></div>

        <!-- =========================================================
             MAIN ADMIN CONTENT WRAPPER
             ========================================================= -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Top Admin Header -->
            <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200/80 px-6 sm:px-8 flex items-center justify-between shrink-0 z-10 sticky top-0">
                
                <div class="flex items-center gap-3">
                    <button type="button" onclick="toggleSidebar()" class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div>
                        <h1 class="text-lg sm:text-xl font-serif font-bold text-slate-900"><?= htmlspecialchars($pageTitle) ?></h1>
                        <p class="text-[11px] text-slate-400 font-medium hidden sm:block">Panel Kontrol & Manajemen Bloom&Books</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Mode Administrasi Aktif</span>
                    </div>

                    <a href="../index.php" target="_blank" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-1.5">
                        <span>Toko Online</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                </div>

            </header>

            <!-- Main Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-6 sm:p-8">
                
                <!-- Flash Notification Banner -->
                <?php if ($flash): ?>
                    <div id="adminFlashAlert" class="mb-6 p-4 rounded-2xl border flex items-center justify-between shadow-sm <?= $flash['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs text-white <?= $flash['type'] === 'success' ? 'bg-emerald-500' : 'bg-rose-500' ?>">
                                <?= $flash['type'] === 'success' ? '✓' : '!' ?>
                            </span>
                            <span class="text-xs sm:text-sm font-medium"><?= htmlspecialchars($flash['message']) ?></span>
                        </div>
                        <button type="button" onclick="this.closest('#adminFlashAlert').remove()" class="text-slate-400 hover:text-slate-600 text-sm p-1">
                            ✕
                        </button>
                    </div>
                <?php endif; ?>
