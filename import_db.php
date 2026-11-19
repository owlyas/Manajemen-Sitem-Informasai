<?php
/**
 * Bloom&Books - Database Auto-Installer / Importer Script
 * Mengimpor schema.sql secara otomatis ke MySQL tanpa perlu Apache / phpMyAdmin.
 */

$host = 'localhost';
$port = '3306';
$user = 'root';
$pass = '';
$sqlFile = __DIR__ . '/schema.sql';

$status = 'idle';
$message = '';
$tablesCreated = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    if (!file_exists($sqlFile)) {
        $status = 'error';
        $message = "File schema.sql tidak ditemukan di: " . htmlspecialchars($sqlFile);
    } else {
        try {
            // 1. Koneksi ke server MySQL tanpa memilih database terlebih dahulu
            $pdoRaw = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // 2. Baca isi schema.sql
            $sqlContent = file_get_contents($sqlFile);

            // 3. Eksekusi skrip SQL (Multi-query execution)
            $pdoRaw->exec($sqlContent);

            // 4. Periksa tabel yang berhasil dibuat di db_ecommerce
            $pdoDB = new PDO("mysql:host={$host};port={$port};dbname=db_ecommerce;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $stmt = $pdoDB->query("SHOW TABLES");
            $tablesCreated = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Hitung total buku
            $bookCount = $pdoDB->query("SELECT COUNT(*) FROM books")->fetchColumn();

            $status = 'success';
            $message = "Database <strong>db_ecommerce</strong> berhasil dibuat dan di-import! Ditemukan " . count($tablesCreated) . " tabel dan {$bookCount} buku dummy siap digunakan.";

        } catch (PDOException $e) {
            $status = 'error';
            $message = "Gagal menghubungkan ke MySQL: " . $e->getMessage() . "<br><br><small class='text-slate-500'>Pastikan tombol <strong>MySQL</strong> di XAMPP Control Panel sudah dalam posisi <strong>Start</strong> (berwarna hijau).</small>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Import Database - Bloom&Books</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3A0CA3',
                        secondary: '#F72585',
                        surface: '#FAFAFE',
                    },
                    fontFamily: {
                        sans: ['"Inter"', 'sans-serif'],
                        serif: ['"Cormorant Garamond"', 'serif'],
                        logo: ['"Cormorant Garamond"', '"Cinzel"', 'serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-surface min-h-screen flex items-center justify-center p-4 font-sans text-slate-800">
    
    <div class="max-w-lg w-full bg-white/90 backdrop-blur-md rounded-3xl p-8 shadow-2xl border border-slate-200">
        
        <!-- Header -->
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-primary to-secondary mx-auto flex items-center justify-center text-white text-xl font-bold mb-3 shadow-md">
                ⚡
            </div>
            <h1 class="text-2xl font-bold text-slate-900 font-serif">Auto Import Database</h1>
            <p class="text-xs text-slate-500 mt-1">Impor skrip <code class="text-primary font-mono font-bold">schema.sql</code> langsung ke MySQL tanpa perlu Apache</p>
        </div>

        <!-- Alert Hasil -->
        <?php if ($status === 'success'): ?>
            <div class="mb-6 p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs leading-relaxed">
                <div class="flex items-center gap-2 font-bold text-emerald-900 mb-1 text-sm">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Import Berhasil 100%!
                </div>
                <p><?= $message ?></p>

                <?php if (!empty($tablesCreated)): ?>
                    <div class="mt-3 pt-3 border-t border-emerald-200/60 flex flex-wrap gap-1.5">
                        <?php foreach ($tablesCreated as $tbl): ?>
                            <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-900 font-mono text-[10px] font-semibold">
                                <?= htmlspecialchars($tbl) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-5">
                    <a href="index.php" class="w-full block py-3 rounded-xl bg-gradient-to-r from-primary to-secondary text-white font-bold text-center text-xs shadow-md hover:opacity-95 transition-all">
                        🎉 Buka Website Bloom&Books Sekarang
                    </a>
                </div>
            </div>
        <?php elseif ($status === 'error'): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs leading-relaxed">
                <div class="flex items-center gap-2 font-bold text-rose-900 mb-1 text-sm">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    Proses Import Gagal
                </div>
                <p><?= $message ?></p>
            </div>
        <?php endif; ?>

        <!-- Form Eksekusi -->
        <?php if ($status !== 'success'): ?>
            <div class="space-y-4">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-2">
                    <p class="font-bold text-slate-800">Prasyarat Sangat Sederhana:</p>
                    <ol class="list-decimal list-inside space-y-1 text-[11px] text-slate-600">
                        <li>Buka <strong>XAMPP Control Panel</strong>.</li>
                        <li>Cukup klik <strong>Start pada MySQL saja</strong> (lampu indikator hijau).</li>
                        <li><strong>Apache TIDAK PERLU dinyalakan</strong> jika mengalami kendala port.</li>
                    </ol>
                </div>

                <form method="POST">
                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-primary via-indigo-600 to-secondary text-white font-bold text-xs sm:text-sm shadow-lg hover:opacity-95 transition-all flex items-center justify-center gap-2">
                        <span>Mulai Import Otomatis schema.sql</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>

                <div class="text-center">
                    <a href="index.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors">
                        &larr; Kembali ke Beranda
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
