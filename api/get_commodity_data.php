<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$commodity = isset($_GET['commodity']) ? trim($_GET['commodity']) : '';

if (empty($commodity)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Parameter commodity diperlukan']));
}

header('Content-Type: application/json');

try {
    $commodity = mysqli_real_escape_string($conn, $commodity);
    
    // Get all price history for the commodity
    $historyQuery = "SELECT harga, waktu_update FROM harga_pasar 
                     WHERE nama_komoditas = '$commodity' 
                     ORDER BY waktu_update ASC";
    
    $result = mysqli_query($conn, $historyQuery);
    
    if (!$result) {
        throw new Exception('Query failed: ' . mysqli_error($conn));
    }
    
    $priceHistory = [];
    $prices = [];
    $dates = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $prices[] = (float)$row['harga'];
        $dates[] = $row['waktu_update'];
        $priceHistory[] = [
            'harga' => (float)$row['harga'],
            'waktu_update' => $row['waktu_update'],
            'tanggal' => date('d/m', strtotime($row['waktu_update']))
        ];
    }
    
    if (empty($prices)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tidak ada data untuk komoditas ini'
        ]);
        exit;
    }
    
    // Calculate statistics
    $averagePrice = array_sum($prices) / count($prices);
    $maxPrice = max($prices);
    $minPrice = min($prices);
    
    // Calculate price change
    $priceChange = 0;
    $priceChangePercent = 0;
    if ($prices[0] > 0) {
        $priceChange = $prices[count($prices) - 1] - $prices[0];
        $priceChangePercent = ($priceChange / $prices[0]) * 100;
    }
    
    // Format dates for chart
    $chartDates = [];
    $chartPrices = [];
    
    if (count($priceHistory) > 12) {
        $step = ceil(count($priceHistory) / 12);
    } else {
        $step = 1;
    }
    
    for ($i = 0; $i < count($priceHistory); $i += $step) {
        $chartDates[] = date('d M', strtotime($priceHistory[$i]['waktu_update']));
        $chartPrices[] = $priceHistory[$i]['harga'];
    }
    
    // Ensure last point is included
    if (count($priceHistory) > 0) {
        $lastData = end($priceHistory);
        $lastChartDate = end($chartDates);
        if ($lastChartDate !== date('d M', strtotime($lastData['waktu_update']))) {
            $chartDates[] = date('d M', strtotime($lastData['waktu_update']));
            $chartPrices[] = $lastData['harga'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'commodity' => htmlspecialchars($commodity),
        'averagePrice' => round($averagePrice, 2),
        'maxPrice' => $maxPrice,
        'minPrice' => $minPrice,
        'priceChange' => $priceChange,
        'priceChangePercent' => round($priceChangePercent, 2),
        'currentPrice' => end($prices),
        'previousPrice' => count($prices) > 1 ? $prices[count($prices) - 2] : $prices[0],
        'chartDates' => $chartDates,
        'chartPrices' => $chartPrices,
        'totalDataPoints' => count($prices)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
