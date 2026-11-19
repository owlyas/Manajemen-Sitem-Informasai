<?php
/**
 * Xendit Webhook Handler
 * Menerima notifikasi status pembayaran dari Xendit dan update status order di DB.
 */

require_once __DIR__ . '/config/database.php';

// Hanya terima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Baca payload dari Xendit
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON payload');
}

// Log webhook untuk debugging
error_log('[Xendit Webhook] Received: ' . $rawPayload);

// Ambil reference_id yang kita set waktu membuat session (format: order_123_timestamp)
$referenceId = $data['reference_id'] ?? '';
// Ambil order ID dari reference_id
if (preg_match('/^order_(\d+)/', $referenceId, $m)) {
    $orderId = (int)$m[1];
} else {
    // Coba ambil dari external_id (format lama)
    $extId = $data['external_id'] ?? '';
    if (preg_match('/^order_(\d+)/', $extId, $m)) {
        $orderId = (int)$m[1];
    } else {
        error_log('[Xendit Webhook] Cannot determine order ID from: ' . $referenceId . ' / ' . $extId);
        http_response_code(400);
        exit('Cannot determine order ID');
    }
}

// Status dari Xendit
$xenditStatus = strtoupper($data['status'] ?? '');

// Mapping status Xendit -> status order kita
$statusMap = [
    'PAID'      => 'paid',
    'ACTIVE'    => 'pending',
    'EXPIRED'   => 'expired',
    'CANCELLED' => 'cancelled',
    'PENDING'   => 'pending',
    'SUCCEEDED' => 'paid',
    'FAILED'    => 'failed',
];

$newStatus = $statusMap[$xenditStatus] ?? 'pending';

// Update order di database
if ($pdo !== null && $orderId > 0) {
    try {
        $stmt = $pdo->prepare('UPDATE orders SET status_pembayaran = :status WHERE id = :id');
        $stmt->execute([':status' => $newStatus, ':id' => $orderId]);
        error_log('[Xendit Webhook] Order #' . $orderId . ' updated to: ' . $newStatus);
        http_response_code(200);
        echo 'OK';
    } catch (PDOException $e) {
        error_log('[Xendit Webhook] DB error: ' . $e->getMessage());
        http_response_code(500);
        echo 'DB Error';
    }
} else {
    http_response_code(400);
    echo 'Invalid order';
}
