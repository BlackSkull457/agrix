<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';
require_once '../market_sync.php';

header('Content-Type: application/json');

try {
    // Perform sync
    $syncResult = syncExternalMarketData($conn);
    
    if (is_array($syncResult)) {
        echo json_encode([
            'success' => $syncResult['success'],
            'message' => $syncResult['message'],
            'inserted' => $syncResult['inserted'] ?? 0,
            'updated' => $syncResult['updated'] ?? 0,
            'timestamp' => $syncResult['timestamp'] ?? date('d/m/Y H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Sinkronisasi gagal',
            'inserted' => 0,
            'updated' => 0,
            'timestamp' => date('d/m/Y H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'timestamp' => date('d/m/Y H:i:s')
    ]);
}
?>
