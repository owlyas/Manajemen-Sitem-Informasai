<?php
/**
 * Alat Cek Koneksi & Validasi Key Midtrans
 * Akses melalui browser: http://localhost/ecommerce/test_midtrans_web.php
 */

require_once __DIR__ . '/vendor/autoload.php';
$cfg = require __DIR__ . '/config/midtrans.php';

$serverKey = trim($_POST['server_key'] ?? $cfg['server_key']);
$clientKey = trim($_POST['client_key'] ?? $cfg['client_key']);
$isProd = isset($_POST['is_production']) ? (bool)$_POST['is_production'] : (bool)$cfg['is_production'];

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan ke config jika user memilih
    if (isset($_POST['save_to_config'])) {
        $content = "<?php\nreturn [\n"
            . "    'server_key' => " . var_export($serverKey, true) . ",\n"
            . "    'client_key' => " . var_export($clientKey, true) . ",\n"
            . "    'is_production' => " . ($isProd ? 'true' : 'false') . ",\n"
            . "    'is_3ds' => true,\n"
            . "];\n";
        file_put_contents(__DIR__ . '/config/midtrans.php', $content);
    }

    \Midtrans\Config::$serverKey = $serverKey;
    \Midtrans\Config::$isProduction = $isProd;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    $params = [
        'transaction_details' => [
            'order_id' => 'TEST-' . time() . '-' . mt_rand(100, 999),
            'gross_amount' => 10000,
        ],
        'customer_details' => [
            'first_name' => 'Tester',
            'email' => 'tester@bloomandbooks.com',
        ],
    ];

    try {
        $snapToken = \Midtrans\Snap::getSnapToken($params);
        $baseUrl = $isProd ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        $redirectUrl = "{$baseUrl}/snap/v2/vtweb/{$snapToken}";
        $result = [
            'success' => true,
            'token' => $snapToken,
            'redirect' => $redirectUrl,
        ];
    } catch (\Exception $e) {
        $result = [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Koneksi Midtrans - Bloom&Books</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen py-10 px-4">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl p-8 border border-slate-200">
        <div class="flex items-center gap-3 pb-6 border-b border-slate-200">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-lg">M</div>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Uji Langsung Kredensial Midtrans</h1>
                <p class="text-xs text-slate-500">Cek apakah Server Key Anda diterima oleh server Midtrans Sandbox</p>
            </div>
        </div>

        <?php if ($result): ?>
            <?php if ($result['success']): ?>
                <div class="mt-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                    <div class="flex items-center gap-2 font-bold text-sm">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>KONEKSI BERHASIL! Server Key Valid & Diterima.</span>
                    </div>
                    <p class="text-xs mt-2 text-emerald-700">Token Snap: <code class="font-mono bg-white px-2 py-0.5 rounded"><?= htmlspecialchars($result['token']) ?></code></p>
                    <div class="mt-4">
                        <a href="<?= htmlspecialchars($result['redirect']) ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition">
                            <span>Buka Halaman Pembayaran Midtrans &rarr;</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="mt-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800">
                    <div class="flex items-center gap-2 font-bold text-sm">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span>MIDTRANS MENOLAK (HTTP 401 UNAUTHORIZED)</span>
                    </div>
                    <p class="text-xs mt-2 text-rose-700 font-mono"><?= htmlspecialchars($result['error']) ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Server Key (Wajib)</label>
                <input type="text" name="server_key" value="<?= htmlspecialchars($serverKey) ?>" required class="w-full p-3 border border-slate-300 rounded-xl font-mono text-xs focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="SB-Mid-server-xxxx">
                <span class="text-[11px] text-slate-400 mt-1 block">Salin dari dashboard.sandbox.midtrans.com &rarr; Settings &rarr; Access Keys</span>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Client Key</label>
                <input type="text" name="client_key" value="<?= htmlspecialchars($clientKey) ?>" class="w-full p-3 border border-slate-300 rounded-xl font-mono text-xs focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="SB-Mid-client-xxxx">
            </div>

            <div class="flex items-center gap-4 pt-2">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_production" value="1" <?= $isProd ? 'checked' : '' ?> class="rounded text-indigo-600">
                    <span>Mode Production (Kosongkan jika Sandbox / Testing)</span>
                </label>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="save_to_config" value="1" checked class="rounded text-indigo-600">
                    <span>Langsung simpan key ini ke <code class="text-indigo-600 font-mono">config/midtrans.php</code> jika berhasil</span>
                </label>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow transition">
                    Test Kredensial Sekarang
                </button>
                <a href="cart.php" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                    Kembali ke Keranjang
                </a>
            </div>
        </form>
    </div>
</body>
</html>

