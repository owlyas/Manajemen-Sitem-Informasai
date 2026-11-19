<?php
// Pastikan konfigurasi database dan sesi sudah dimuat
require_once __DIR__ . '/../config/database.php';

$currentUser = currentUser();
$flash = getFlashMessage();

// Variabel default title jika belum didefinisikan di halaman pemanggil
if (!isset($pageTitle)) {
    $pageTitle = 'Bloom&Books - Toko Buku Online Modern & Elegan';
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bloom&Books - Toko buku online terkurasi dengan pengalaman membaca terbaik. Temukan ribuan buku berkualitas, fiksi, non-fiksi, dan teknologi.">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Google Fonts: Inter (Minimalist Content), Cormorant Garamond & Cinzel (Fancy Logo), Playfair Display -->
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
                            100: '#E9E2FC',
                        },
                        secondary: {
                            DEFAULT: '#F72585',
                            hover: '#D91A72',
                            light: '#FDE8F3',
                            50: '#FFF0F7',
                        },
                        surface: '#FAFAFE',
                        dark: '#1E1B4B',
                    },
                    fontFamily: {
                        sans: ['"Inter"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        serif: ['"Inter"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        logo: ['"Cormorant Garamond"', '"Cinzel"', 'serif'],
                        display: ['"Inter"', 'sans-serif'],
                    },
                    boxShadow: {
                        'glass': '0 8px 32px 0 rgba(58, 12, 163, 0.08)',
                        'glass-sm': '0 4px 16px 0 rgba(58, 12, 163, 0.05)',
                        'glass-lg': '0 12px 40px 0 rgba(58, 12, 163, 0.12)',
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Glassmorphic Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-surface text-slate-800 antialiased min-h-screen flex flex-col selection:bg-secondary selection:text-white">

    <!-- Floating Glassmorphic Sticky Header / Navbar -->
    <div class="sticky top-0 sm:top-3 z-50 px-3 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 w-full mx-auto transition-all duration-300 pt-2 sm:pt-0">
        <header class="glass-nav rounded-2xl sm:rounded-3xl border border-white/90 bg-white/85 backdrop-blur-2xl shadow-glass-lg px-4 sm:px-6 lg:px-8 transition-all duration-300">
            <div class="flex items-center justify-between h-18 sm:h-20 gap-2 sm:gap-4 lg:gap-6">
                
                <!-- Fancy Curated Logo & Brand Identity -->
                <a href="index.php" class="flex items-center gap-2.5 sm:gap-3.5 group focus:outline-none shrink-0">
                    <!-- Fancy Jewel Emblem Logo -->
                    <div class="relative w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-tr from-[#3A0CA3] via-[#7209B7] to-[#F72585] p-[1.5px] shadow-md shadow-primary/25 group-hover:shadow-lg group-hover:shadow-secondary/30 transition-all duration-300 group-hover:scale-105">
                        <div class="w-full h-full rounded-[14px] bg-gradient-to-br from-[#1E085A] to-[#3A0CA3] flex items-center justify-center relative overflow-hidden backdrop-blur-sm">
                            <div class="absolute inset-0 bg-gradient-to-tr from-white/15 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <!-- Bespoke Botanical Bloom & Luxury Book Emblem SVG -->
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 3.5C12 3.5 13.8 6.5 13.8 8.2C13.8 9.2 13 10 12 10C11 10 10.2 9.2 10.2 8.2C10.2 6.5 12 3.5 12 3.5Z" fill="#F72585"/>
                                <path d="M12 9C13.6 7 16.8 6.5 17.8 8C18.5 9.1 17.2 11.2 14.5 11.8C13.5 12 12.5 11.6 12 11" stroke="#FDE8F3" stroke-width="1.2" stroke-linecap="round"/>
                                <path d="M12 9C10.4 7 7.2 6.5 6.2 8C5.5 9.1 6.8 11.2 9.5 11.8C10.5 12 11.5 11.6 12 11" stroke="#FDE8F3" stroke-width="1.2" stroke-linecap="round"/>
                                <path d="M12 14.2V20" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
                                <path d="M12 14.5C9.8 13.2 6.5 13.2 3.6 14.7C3.3 14.9 3 15.2 3 15.6V19.2C3 19.7 3.5 20.1 4 19.9C6.8 18.7 9.8 18.8 12 20C14.2 18.8 17.2 18.7 20 19.9C20.5 20.1 21 19.7 21 19.2V15.6C21 15.2 20.7 14.9 20.4 14.7C17.5 13.2 14.2 13.2 12 14.5Z" stroke="white" stroke-width="1.3" stroke-linejoin="round"/>
                                <circle cx="12" cy="3.5" r="0.9" fill="#FFD166"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Fancy Typography Wordmark -->
                    <div class="flex flex-col">
                        <span class="font-logo text-xl sm:text-2xl font-bold tracking-tight text-slate-900 group-hover:text-primary transition-colors flex items-center leading-none">
                            <span>Bloom</span>
                            <span class="fancy-ampersand text-xl sm:text-2xl text-secondary mx-0.5 select-none font-normal italic">&amp;</span>
                            <span class="text-primary">Books</span>
                        </span>
                        <span class="text-[8px] sm:text-[9px] uppercase tracking-[0.22em] text-slate-400 font-semibold font-sans mt-0.5 hidden xs:inline-block">Curated Bookstore</span>
                    </div>
                </a>

                <!-- Prominent Search Bar (In Navbar for Searching Books, Authors & Publishers) -->
                <div class="hidden md:flex flex-1 max-w-md lg:max-w-xl xl:max-w-2xl mx-3 lg:mx-6">
                    <form action="index.php" method="GET" class="w-full relative flex items-center group">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                            placeholder="Cari judul buku, penulis, kategori..." 
                            class="glass-input w-full pl-10 pr-20 py-2.5 rounded-full text-xs sm:text-sm placeholder-slate-400 focus:ring-2 focus:ring-primary/25 text-slate-700 bg-white/95 border border-slate-200/90 shadow-sm transition-all group-hover:border-primary/40 focus:border-primary"
                            id="headerSearchInput"
                            autocomplete="off"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8" stroke-width="2.2"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2.2" stroke-linecap="round"></line>
                            </svg>
                        </div>
                        <button type="submit" class="absolute right-1 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-primary to-secondary text-white text-xs font-bold shadow-sm hover:opacity-95 active:scale-95 transition-all flex items-center gap-1">
                            <span>Cari</span>
                        </button>
                    </form>
                </div>

                <!-- Navigation Links -->
                <nav class="hidden lg:flex items-center gap-5 xl:gap-7 text-xs xl:text-sm font-semibold text-slate-600 shrink-0">
                    <a href="index.php" class="hover:text-primary transition-colors py-1">Beranda</a>
                    <a href="index.php#katalog" class="hover:text-primary transition-colors py-1">Katalog</a>
                    <a href="index.php#kategori" class="hover:text-primary transition-colors py-1">Kategori</a>
                    <a href="leaderboard.php" class="hover:text-secondary transition-colors py-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-secondary" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z'/></svg>
                        Leaderboard
                    </a>
                    <a href="index.php#keunggulan" class="hover:text-primary transition-colors py-1">Tentang Kami</a>
                </nav>

                <!-- Auth Buttons / User Profile Actions -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    
                    <!-- Mobile Search Toggle Button (Shows on mobile) -->
                    <button 
                        id="mobileSearchToggleBtn" 
                        type="button" 
                        class="md:hidden p-2 rounded-full glass-card hover:border-primary/40 text-slate-700 hover:text-primary transition-all flex items-center justify-center shadow-sm"
                        aria-label="Cari Buku"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" stroke-width="2.2"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2.2" stroke-linecap="round"></line>
                        </svg>
                    </button>

                    <!-- Keranjang Belanja Button (Cart Icon & Counter Badge) -->
                    <a href="cart.php" class="relative p-2 sm:p-2.5 rounded-full glass-card hover:border-primary/40 text-slate-700 hover:text-primary transition-all flex items-center justify-center shadow-sm" title="Keranjang Belanja" id="navbarCartBtn">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <?php $cartCount = getCartCount(); ?>
                        <?php if ($cartCount > 0): ?>
                            <span class="absolute -top-1 -right-1 w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-secondary text-white text-[9px] sm:text-[10px] font-extrabold flex items-center justify-center shadow-md animate-pulse">
                                <?= $cartCount ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <?php if ($currentUser): ?>
                        <!-- User Is Logged In -->
                        <div class="relative">
                            <button 
                                id="userDropdownBtn" 
                                type="button" 
                                class="flex items-center gap-2 p-1 pl-2.5 pr-1.5 sm:p-1.5 sm:pl-3 sm:pr-2 rounded-full glass-card hover:border-primary/30 transition-all focus:outline-none"
                            >
                                <span class="text-xs font-semibold text-slate-700 max-w-[90px] sm:max-w-[120px] truncate hidden xs:inline-block">
                                    <?= htmlspecialchars($currentUser['nama']) ?>
                                </span>
                                <span class="text-[9px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full <?= $currentUser['role'] === 'admin' ? 'bg-primary text-white' : 'bg-secondary/10 text-secondary' ?>">
                                    <?= ucfirst($currentUser['role']) ?>
                                </span>
                                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full overflow-hidden bg-primary/10 flex items-center justify-center text-primary font-bold text-xs ring-2 ring-white">
                                    <?php if (!empty($currentUser['foto_profil'])): ?>
                                        <img src="<?= htmlspecialchars($currentUser['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <?= strtoupper(substr($currentUser['nama'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-56 glass-card rounded-2xl shadow-glass-lg py-2 border border-white/80 z-50">
                                <div class="px-4 py-2 border-b border-slate-100">
                                    <p class="text-xs text-slate-400">Masuk sebagai</p>
                                    <p class="text-xs font-semibold text-slate-800 truncate"><?= htmlspecialchars($currentUser['email']) ?></p>
                                </div>
                                <?php if ($currentUser['role'] === 'admin'): ?>
                                    <a href="admin/dashboard.php" class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-primary/5 hover:text-primary transition-colors">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                        Admin Back-Office
                                    </a>
                                <?php else: ?>
                                    <a href="user/dashboard.php" class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-primary/5 hover:text-primary transition-colors">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        Dashboard Saya
                                    </a>
                                    <a href="user/orders.php" class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-primary/5 hover:text-primary transition-colors">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        Riwayat Pesanan
                                    </a>
                                    <a href="cart.php" class="flex items-center justify-between px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-primary/5 hover:text-primary transition-colors">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                            <span>Keranjang Belanja</span>
                                        </div>
                                        <?php if ($cartCount > 0): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary text-white"><?= $cartCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                                <div class="border-t border-slate-100 my-1"></div>
                                <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    Keluar (Logout)
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Guest Mode: Login & Register Buttons -->
                        <a 
                            href="login.php" 
                            class="text-xs sm:text-sm font-semibold text-primary hover:text-primary-hover px-3 sm:px-4 py-1.5 sm:py-2 rounded-full hover:bg-primary/5 transition-all"
                            id="navLoginBtn"
                        >
                            Masuk
                        </a>
                        <a 
                            href="register.php" 
                            class="text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-primary via-indigo-600 to-secondary hover:opacity-95 px-3.5 sm:px-5 py-1.5 sm:py-2.5 rounded-full shadow-md shadow-primary/25 hover:shadow-lg hover:shadow-primary/30 transition-all hidden xs:inline-block"
                            id="navRegisterBtn"
                        >
                            Daftar
                        </a>
                    <?php endif; ?>

                    <!-- Mobile Menu Button -->
                    <button 
                        id="mobileMenuBtn" 
                        type="button" 
                        class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-primary hover:bg-white/80 focus:outline-none"
                        aria-label="Menu"
                    >
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Quick Search Form (Collapsible) -->
            <div id="mobileSearchBar" class="hidden md:hidden pb-3 pt-1 border-t border-slate-100/80">
                <form action="index.php" method="GET" class="w-full relative flex items-center">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                        placeholder="Cari judul buku, penulis..." 
                        class="glass-input w-full pl-9 pr-16 py-2 rounded-xl text-xs placeholder-slate-400 bg-white"
                        id="mobileSearchInput"
                    >
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </div>
                    <button type="submit" class="absolute right-1 px-3 py-1 rounded-lg bg-primary text-white text-[11px] font-bold">
                        Cari
                    </button>
                </form>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div id="mobileMenu" class="hidden lg:hidden py-4 border-t border-slate-100 flex flex-col gap-2.5">
                <a href="index.php" class="px-2 py-1.5 text-xs sm:text-sm font-semibold text-slate-700 hover:text-primary rounded-lg hover:bg-slate-50 transition-colors">Beranda</a>
                <a href="index.php#katalog" class="px-2 py-1.5 text-xs sm:text-sm font-semibold text-slate-700 hover:text-primary rounded-lg hover:bg-slate-50 transition-colors">Katalog Buku</a>
                <a href="index.php#kategori" class="px-2 py-1.5 text-xs sm:text-sm font-semibold text-slate-700 hover:text-primary rounded-lg hover:bg-slate-50 transition-colors">Kategori</a>
                <a href="index.php#keunggulan" class="px-2 py-1.5 text-xs sm:text-sm font-semibold text-slate-700 hover:text-primary rounded-lg hover:bg-slate-50 transition-colors">Tentang Kami</a>
                <?php if (!$currentUser): ?>
                    <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
                        <a href="login.php" class="flex-1 py-2 text-center text-xs font-semibold text-primary rounded-xl border border-primary/20">Masuk</a>
                        <a href="register.php" class="flex-1 py-2 text-center text-xs font-semibold text-white bg-primary rounded-xl">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </header>
    </div>

    <!-- Global Flash Message Notification (Glassmorphic) -->
    <?php if ($flash): ?>
        <div id="flashAlert" class="max-w-4xl mx-auto px-4 mt-4 w-full z-40 transition-all duration-300">
            <div class="p-4 rounded-2xl border flex items-center justify-between shadow-glass <?= $flash['type'] === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-800' : 'bg-rose-500/10 border-rose-500/30 text-rose-800' ?>">
                <div class="flex items-center gap-3">
                    <?php if ($flash['type'] === 'success'): ?>
                        <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-rose-500 text-white flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                    <?php endif; ?>
                    <p class="text-xs sm:text-sm font-medium"><?= htmlspecialchars($flash['message']) ?></p>
                </div>
                <button type="button" onclick="this.closest('#flashAlert').remove()" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Container Wrapper -->
    <main class="flex-grow relative">
