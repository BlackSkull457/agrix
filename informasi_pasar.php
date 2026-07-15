<?php
require_once 'config/session.php';
startAgrixSession('login.php');
include 'config/koneksi.php';
include 'market_sync.php';

function rupiah($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

$existingDataCount = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM harga_pasar'))['total'] ?: 0;
if ($existingDataCount == 0) {
    $sampleData = [
        ['Beras Premium', 'Padi & Biji-bijian', 14500, 'Data Simulasi Admin', '2026-04-01 08:00:00'],
        ['Beras Premium', 'Padi & Biji-bijian', 14700, 'Data Simulasi Admin', '2026-04-02 08:00:00'],
        ['Beras Premium', 'Padi & Biji-bijian', 14600, 'Data Simulasi Admin', '2026-04-03 08:00:00'],
        ['Beras Premium', 'Padi & Biji-bijian', 14800, 'Data Simulasi Admin', '2026-04-04 08:00:00'],
        ['Beras Premium', 'Padi & Biji-bijian', 14900, 'Data Simulasi Admin', '2026-04-05 08:00:00'],
        ['Beras Medium', 'Padi & Biji-bijian', 12000, 'Data Simulasi Admin', '2026-04-01 08:00:00'],
        ['Beras Medium', 'Padi & Biji-bijian', 12100, 'Data Simulasi Admin', '2026-04-02 08:00:00'],
        ['Beras Medium', 'Padi & Biji-bijian', 11900, 'Data Simulasi Admin', '2026-04-03 08:00:00'],
        ['Beras Medium', 'Padi & Biji-bijian', 12000, 'Data Simulasi Admin', '2026-04-04 08:00:00'],
        ['Beras Medium', 'Padi & Biji-bijian', 12200, 'Data Simulasi Admin', '2026-04-05 08:00:00'],
        ['Jagung', 'Padi & Biji-bijian', 5000, 'Data Simulasi Admin', '2026-04-01 08:00:00'],
        ['Jagung', 'Padi & Biji-bijian', 5200, 'Data Simulasi Admin', '2026-04-02 08:00:00'],
        ['Jagung', 'Padi & Biji-bijian', 5100, 'Data Simulasi Admin', '2026-04-03 08:00:00'],
        ['Jagung', 'Padi & Biji-bijian', 5000, 'Data Simulasi Admin', '2026-04-04 08:00:00'],
        ['Jagung', 'Padi & Biji-bijian', 5300, 'Data Simulasi Admin', '2026-04-05 08:00:00'],
        ['Cabai Merah', 'Sayuran', 36000, 'Data Simulasi Admin', '2026-04-01 08:00:00'],
        ['Cabai Merah', 'Sayuran', 35500, 'Data Simulasi Admin', '2026-04-02 08:00:00'],
        ['Cabai Merah', 'Sayuran', 35800, 'Data Simulasi Admin', '2026-04-03 08:00:00'],
        ['Cabai Merah', 'Sayuran', 36200, 'Data Simulasi Admin', '2026-04-04 08:00:00'],
        ['Cabai Merah', 'Sayuran', 36000, 'Data Simulasi Admin', '2026-04-05 08:00:00'],
        ['Cabai Rawit', 'Sayuran', 47000, 'Data Simulasi Admin', '2026-04-01 08:00:00'],
        ['Cabai Rawit', 'Sayuran', 46800, 'Data Simulasi Admin', '2026-04-02 08:00:00'],
        ['Cabai Rawit', 'Sayuran', 47200, 'Data Simulasi Admin', '2026-04-03 08:00:00'],
        ['Cabai Rawit', 'Sayuran', 47500, 'Data Simulasi Admin', '2026-04-04 08:00:00'],
        ['Cabai Rawit', 'Sayuran', 47300, 'Data Simulasi Admin', '2026-04-05 08:00:00'],
    ];

    foreach ($sampleData as $item) {
        $namaKomoditas = mysqli_real_escape_string($conn, $item[0]);
        $kategori = mysqli_real_escape_string($conn, $item[1]);
        $harga = (float) $item[2];
        $sumber = mysqli_real_escape_string($conn, $item[3]);
        $waktuUpdate = mysqli_real_escape_string($conn, $item[4]);
        mysqli_query($conn, "INSERT INTO harga_pasar (nama_komoditas, kategori, harga, sumber, waktu_update) VALUES ('$namaKomoditas', '$kategori', $harga, '$sumber', '$waktuUpdate')");
    }
}

syncExternalMarketData($conn);

if (isset($_GET['chart']) && $_GET['chart'] == '1') {
    $selectedProduct = isset($_GET['komoditas']) ? trim($_GET['komoditas']) : '';
    $whereClause = '';
    if ($selectedProduct !== '') {
        $selectedProduct = mysqli_real_escape_string($conn, $selectedProduct);
        $whereClause = "WHERE nama_komoditas = '$selectedProduct'";
    }

    $historyQuery = mysqli_query($conn, "SELECT waktu_update, harga FROM harga_pasar $whereClause ORDER BY waktu_update ASC");
    $labels = [];
    $values = [];
    while ($row = mysqli_fetch_assoc($historyQuery)) {
        $labels[] = date('d M', strtotime($row['waktu_update']));
        $values[] = (float) $row['harga'];
    }

    if (empty($labels)) {
        $labels = ['-'];
        $values = [0];
    }

    header('Content-Type: application/json');
    echo json_encode([
        'labels' => $labels,
        'values' => $values,
        'updated' => date('d/m/Y H:i')
    ]);
    exit;
}

$productOptionsQuery = mysqli_query($conn, "SELECT DISTINCT nama_komoditas FROM harga_pasar ORDER BY nama_komoditas");
$productOptions = [];
while ($row = mysqli_fetch_assoc($productOptionsQuery)) {
    $productOptions[] = $row['nama_komoditas'];
}

$selectedProduct = isset($_GET['komoditas']) ? trim($_GET['komoditas']) : '';
if ($selectedProduct === '' && !empty($productOptions)) {
    $selectedProduct = $productOptions[0];
}

$produkCountQuery = mysqli_query($conn, 'SELECT COUNT(DISTINCT nama_komoditas) AS total FROM harga_pasar');
$totalProduk = mysqli_fetch_assoc($produkCountQuery)['total'] ?: 0;

$latestPricesQuery = mysqli_query($conn, "
    SELECT h.nama_komoditas, h.kategori, h.harga, h.sumber, h.waktu_update
    FROM harga_pasar h
    JOIN (
        SELECT nama_komoditas, MAX(waktu_update) AS max_time
        FROM harga_pasar
        GROUP BY nama_komoditas
    ) latest ON h.nama_komoditas = latest.nama_komoditas AND h.waktu_update = latest.max_time
    ORDER BY h.waktu_update DESC
");

$marketRows = [];
$sumHarga = 0;
while ($row = mysqli_fetch_assoc($latestPricesQuery)) {
    $productName = $row['nama_komoditas'];
    $prevPriceQuery = mysqli_query($conn, "
        SELECT harga
        FROM harga_pasar
        WHERE nama_komoditas = '" . mysqli_real_escape_string($conn, $productName) . "'
          AND waktu_update < '" . mysqli_real_escape_string($conn, $row['waktu_update']) . "'
        ORDER BY waktu_update DESC
        LIMIT 1
    ");
    $prevPriceRow = mysqli_fetch_assoc($prevPriceQuery);
    $previousPrice = $prevPriceRow['harga'] ?? null;
    $currentPrice = (float) $row['harga'];
    $trendStatus = 'stabil';
    $trendText = 'Stabil';
    $trendClass = 'info';
    $trendIcon = '➡️';

    if ($previousPrice !== null && $previousPrice > 0) {
        $changePercent = (($currentPrice - $previousPrice) / $previousPrice) * 100;
        if ($changePercent > 0.5) {
            $trendStatus = 'naik';
            $trendText = 'Naik ' . number_format(abs($changePercent), 1) . '%';
            $trendClass = 'success';
            $trendIcon = '🔺';
        } elseif ($changePercent < -0.5) {
            $trendStatus = 'turun';
            $trendText = 'Turun ' . number_format(abs($changePercent), 1) . '%';
            $trendClass = 'danger';
            $trendIcon = '🔻';
        } else {
            $trendText = 'Stabil';
            $trendIcon = '➡️';
        }
    }

    $row['trend_icon'] = $trendIcon;

    $row['trend_status'] = $trendStatus;
    $row['trend_text'] = $trendText;
    $row['trend_class'] = $trendClass;

    $marketRows[] = $row;
    $sumHarga += $currentPrice;
}
$avgHarga = !empty($marketRows) ? $sumHarga / count($marketRows) : 0;

$historyQuery = mysqli_query($conn, "SELECT waktu_update, harga FROM harga_pasar WHERE nama_komoditas = '" . mysqli_real_escape_string($conn, $selectedProduct) . "' ORDER BY waktu_update ASC");
$labels = [];
$values = [];
while ($row = mysqli_fetch_assoc($historyQuery)) {
    $labels[] = date('d M', strtotime($row['waktu_update']));
    $values[] = (float) $row['harga'];
}
if (empty($labels)) {
    $labels = ['-'];
    $values = [0];
}

$latestPrice = end($values) !== false ? (float) end($values) : 0;
$previousPrice = count($values) > 1 ? (float) $values[count($values) - 2] : $latestPrice;
$priceTrend = $previousPrice > 0 ? (($latestPrice - $previousPrice) / $previousPrice) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Harga Pasar - Agri-X</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="seller-container">
    <aside class="seller-sidebar">
        <div class="sidebar-header">
            <div class="brand">Agri-X</div>
            <small>Penjual</small>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="produk/index.php" class="nav-item">
                <span class="nav-icon">📦</span>
                <span>Kelola Produk</span>
            </a>
            <a href="update_stok.php" class="nav-item">
                <span class="nav-icon">🔄</span>
                <span>Update Stok & Kualitas</span>
            </a>
            <a href="informasi_pasar.php" class="nav-item active">
                <span class="nav-icon">💰</span>
                <span>Informasi Harga Pasar</span>
            </a>
            <a href="rekomendasi_harga.php" class="nav-item">
                <span class="nav-icon">📈</span>
                <span>Rekomendasi Harga</span>
            </a>
            <a href="logout.php" class="nav-item logout">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <div class="seller-main">
        <header class="seller-navbar">
            <div class="navbar-left">
                <h1 class="page-title">Informasi Harga Pasar</h1>
            </div>
            <div class="navbar-right">
                <div class="profile-section">
                    <img src="assets/images/avatar.png" alt="Profile" class="profile-avatar">
                    <span class="profile-name">Penjual</span>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3><?= $totalProduk ?></h3>
                        <p>Total Komoditas</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3 id="avgPriceValue"><?= rupiah($avgHarga) ?></h3>
                        <p>Rata-rata Harga Pasar</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info">
                        <h3><span id="trendValue"><?= number_format($priceTrend, 1) ?></span>%</h3>
                        <p>Perubahan Harga <span id="trendCommodityName"><?= htmlspecialchars($selectedProduct ?: 'Terpilih') ?></span></p>
                    </div>
                </div>
            </div>

            <div class="card large">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
                    <div>
                        <h2>Pilih Produk</h2>
                        <p class="secondary-text">Pilih komoditas untuk melihat riwayat perubahan harganya secara dinamis.</p>
                    </div>
                    <div>
                        <label for="komoditasSelect" style="display:block; margin-bottom:6px; font-weight:600;">Komoditas</label>
                        <select id="komoditasSelect" style="padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; min-width:220px;">
                            <?php foreach ($productOptions as $product): ?>
                                <option value="<?= htmlspecialchars($product) ?>" <?= $selectedProduct === $product ? 'selected' : '' ?>><?= htmlspecialchars($product) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button id="syncButton" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:8px; background:#2563eb; color:#fff; border:none; cursor:pointer; font-size:14px; font-weight:600;">
                            <span id="syncButtonText">Sinkronkan Sekarang</span>
                            <span id="syncSpinner" style="display:none; width:14px; height:14px; border:2px solid rgba(255,255,255,0.3); border-top-color:#fff; border-radius:50%; animation:spin 0.8s linear infinite;"></span>
                        </button>
                        <div id="syncStatus" style="margin-top:8px; font-size:13px; font-weight:500; display:none;"></div>
                    </div>
                </div>

                <!-- Market Analysis Cards -->
                <div class="market-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <!-- Average Market Price Card -->
                    <div class="market-card" style="background: linear-gradient(135deg, #fff 0%, #f9fafb 100%); border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);">
                        <div style="margin-bottom: 16px; border-bottom: 2px solid #f0f0f0; padding-bottom: 12px;">
                            <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">💰 Rata-rata Harga Pasar</h4>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div>
                                <span style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Harga Rata-rata</span>
                                <div style="font-size: 28px; font-weight: 700; color: #2563eb;" id="dynamicAvgPrice">-</div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div style="padding: 8px; background-color: #f9fafb; border-radius: 6px;">
                                    <span style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Tertinggi</span>
                                    <div style="font-size: 14px; font-weight: 700; color: #1f2937; margin-top: 4px;" id="dynamicMaxPrice">-</div>
                                </div>
                                <div style="padding: 8px; background-color: #f9fafb; border-radius: 6px;">
                                    <span style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Terendah</span>
                                    <div style="font-size: 14px; font-weight: 700; color: #1f2937; margin-top: 4px;" id="dynamicMinPrice">-</div>
                                </div>
                                <div style="padding: 8px; background-color: #f9fafb; border-radius: 6px;">
                                    <span style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Data Tersedia</span>
                                    <div style="font-size: 14px; font-weight: 700; color: #1f2937; margin-top: 4px;" id="dynamicDataPoints">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price Change Card -->
                    <div class="market-card" style="background: linear-gradient(135deg, #fff 0%, #f9fafb 100%); border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);">
                        <div style="margin-bottom: 16px; border-bottom: 2px solid #f0f0f0; padding-bottom: 12px;">
                            <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">📈 Perubahan Harga</h4>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div id="dynamicCommodityName" style="font-size: 14px; font-weight: 600; color: #1f2937; padding: 8px 12px; background-color: #f3f4f6; border-radius: 6px;">-</div>
                            <div id="priceChangeContainer">
                                <span style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Perubahan Harga</span>
                                <div id="dynamicPriceChangePercent" style="font-size: 28px; font-weight: 700; color: #2563eb; margin-top: 4px;">-</div>
                                <div id="dynamicPriceChangeAbsolute" style="font-size: 14px; color: #6b7280; margin-top: 4px;">-</div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div style="padding: 8px; background-color: #f9fafb; border-radius: 6px;">
                                    <span style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Harga Saat Ini</span>
                                    <div style="font-size: 14px; font-weight: 700; color: #1f2937; margin-top: 4px;" id="dynamicCurrentPrice">-</div>
                                </div>
                                <div style="padding: 8px; background-color: #f9fafb; border-radius: 6px;">
                                    <span style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Harga Sebelumnya</span>
                                    <div style="font-size: 14px; font-weight: 700; color: #1f2937; margin-top: 4px;" id="dynamicPreviousPrice">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="charts-section">
                    <div class="chart-card">
                        <h3 id="chartTitle">Grafik Perubahan Harga Produk</h3>
                        <canvas id="priceChart" height="260"></canvas>
                        <p class="secondary-text" id="chartUpdated">Terakhir diperbarui: <?= date('d/m/Y H:i') ?></p>
                    </div>
                </div>
            </div>

            <div class="card large">
                <h2>Daftar Harga Pasar Saat Ini</h2>
                <p class="secondary-text">Informasi harga komoditas terkini dengan indikasi tren perubahan.</p>
                <div class="table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Komoditas</th>
                                <th>Kategori</th>
                                <th>Harga (Rp/kg)</th>
                                <th>Trend</th>
                                <th>Waktu Update</th>
                                <th>Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marketRows as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['nama_komoditas']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['kategori']) ?></td>
                                    <td><?= rupiah($row['harga']) ?></td>
                                    <td>
                                        <span class="badge <?= htmlspecialchars($row['trend_class']) ?>">
                                            <?= htmlspecialchars($row['trend_icon'] . ' ' . $row['trend_text']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['waktu_update'])) ?></td>
                                    <td><?= htmlspecialchars($row['sumber'] ?? 'Admin') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let priceChart = null;

// Format Rupiah
function formatRupiah(value) {
    return 'Rp ' + Math.round(value).toLocaleString('id-ID');
}

// Initialize chart with default data
function initializeChart() {
    const labels = <?= json_encode($labels) ?>;
    const values = <?= json_encode($values) ?>;
    
    if (priceChart) {
        priceChart.destroy();
    }
    
    const priceChartCtx = document.getElementById('priceChart').getContext('2d');
    priceChart = new Chart(priceChartCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Harga Produk',
                data: values,
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.16)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatRupiah(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 0, minRotation: 0 }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return formatRupiah(value); }
                    }
                }
            }
        }
    });
}

// Update commodity data via AJAX
async function updateCommodityData(commodity) {
    if (!commodity) {
        resetDashboard();
        return;
    }
    
    try {
        const response = await fetch(`api/get_commodity_data.php?commodity=${encodeURIComponent(commodity)}`);
        const data = await response.json();
        
        if (!data.success) {
            console.error('Error:', data.message || data.error);
            return;
        }
        
        // Update stat cards
        document.getElementById('avgPriceValue').textContent = formatRupiah(data.averagePrice);
        document.getElementById('trendCommodityName').textContent = data.commodity;
        document.getElementById('trendValue').textContent = data.priceChangePercent.toFixed(1);
        
        // Update market cards
        document.getElementById('dynamicCommodityName').textContent = data.commodity;
        document.getElementById('dynamicAvgPrice').textContent = formatRupiah(data.averagePrice);
        document.getElementById('dynamicMaxPrice').textContent = formatRupiah(data.maxPrice);
        document.getElementById('dynamicMinPrice').textContent = formatRupiah(data.minPrice);
        document.getElementById('dynamicDataPoints').textContent = data.totalDataPoints + ' titik data';
        
        // Update price change display
        const priceChangePercent = data.priceChangePercent;
        const priceChangeEl = document.getElementById('dynamicPriceChangePercent');
        const priceChangeContainer = document.getElementById('priceChangeContainer');
        
        if (priceChangePercent > 0) {
            priceChangeEl.textContent = '+' + priceChangePercent.toFixed(2) + '%';
            priceChangeEl.style.color = '#10b981';
            document.getElementById('dynamicPriceChangeAbsolute').style.color = '#10b981';
        } else if (priceChangePercent < 0) {
            priceChangeEl.textContent = priceChangePercent.toFixed(2) + '%';
            priceChangeEl.style.color = '#ef4444';
            document.getElementById('dynamicPriceChangeAbsolute').style.color = '#ef4444';
        } else {
            priceChangeEl.textContent = '0.00%';
            priceChangeEl.style.color = '#2563eb';
            document.getElementById('dynamicPriceChangeAbsolute').style.color = '#6b7280';
        }
        
        document.getElementById('dynamicPriceChangeAbsolute').textContent = formatRupiah(data.priceChange);
        document.getElementById('dynamicCurrentPrice').textContent = formatRupiah(data.currentPrice);
        document.getElementById('dynamicPreviousPrice').textContent = formatRupiah(data.previousPrice);
        
        // Update chart title
        document.getElementById('chartTitle').textContent = `Grafik Perubahan Harga ${data.commodity}`;
        
        // Update chart
        if (priceChart) {
            priceChart.destroy();
        }
        
        const priceChartCtx = document.getElementById('priceChart').getContext('2d');
        priceChart = new Chart(priceChartCtx, {
            type: 'line',
            data: {
                labels: data.chartDates,
                datasets: [{
                    label: `Harga ${data.commodity}`,
                    data: data.chartPrices,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.16)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return formatRupiah(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0, minRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return formatRupiah(value); }
                        }
                    }
                }
            }
        });
        
        document.getElementById('chartUpdated').textContent = 'Terakhir diperbarui: ' + new Date().toLocaleString('id-ID');
        
    } catch (error) {
        console.error('Gagal memuat data:', error);
    }
}

// Reset dashboard
function resetDashboard() {
    document.getElementById('dynamicCommodityName').textContent = '-';
    document.getElementById('dynamicAvgPrice').textContent = '-';
    document.getElementById('dynamicMaxPrice').textContent = '-';
    document.getElementById('dynamicMinPrice').textContent = '-';
    document.getElementById('dynamicDataPoints').textContent = '-';
    document.getElementById('dynamicPriceChangePercent').textContent = '-';
    document.getElementById('dynamicPriceChangeAbsolute').textContent = '-';
    document.getElementById('dynamicCurrentPrice').textContent = '-';
    document.getElementById('dynamicPreviousPrice').textContent = '-';
    document.getElementById('chartTitle').textContent = 'Grafik Perubahan Harga Produk';
    
    if (priceChart) {
        priceChart.destroy();
    }
    
    initializeChart();
}

// Sync market data
async function syncMarketData() {
    const syncButton = document.getElementById('syncButton');
    const syncButtonText = document.getElementById('syncButtonText');
    const syncSpinner = document.getElementById('syncSpinner');
    const syncStatus = document.getElementById('syncStatus');
    
    // Disable button and show spinner
    syncButton.disabled = true;
    syncButtonText.style.display = 'none';
    syncSpinner.style.display = 'inline-block';
    syncStatus.style.display = 'none';
    
    try {
        const response = await fetch('api/sync_market_data.php');
        const data = await response.json();
        
        // Show status message
        syncStatus.style.display = 'block';
        if (data.success) {
            syncStatus.textContent = `✅ ${data.message} (${data.inserted} ditambah, ${data.updated} diperbarui)`;
            syncStatus.style.color = '#10b981';
            
            // Reload current commodity data after sync
            setTimeout(() => {
                const currentCommodity = document.getElementById('komoditasSelect').value;
                updateCommodityData(currentCommodity);
                
                // Hide status after 3 seconds
                setTimeout(() => {
                    syncStatus.style.display = 'none';
                }, 3000);
            }, 500);
        } else {
            syncStatus.textContent = `⚠️ ${data.message}`;
            syncStatus.style.color = '#ef4444';
            
            // Hide status after 3 seconds
            setTimeout(() => {
                syncStatus.style.display = 'none';
            }, 3000);
        }
        
    } catch (error) {
        console.error('Gagal sinkronisasi:', error);
        syncStatus.style.display = 'block';
        syncStatus.textContent = '❌ Terjadi kesalahan saat sinkronisasi';
        syncStatus.style.color = '#ef4444';
        
        setTimeout(() => {
            syncStatus.style.display = 'none';
        }, 3000);
    } finally {
        // Re-enable button and hide spinner
        syncButton.disabled = false;
        syncButtonText.style.display = 'inline';
        syncSpinner.style.display = 'none';
    }
}

// Event listeners
document.getElementById('komoditasSelect').addEventListener('change', function() {
    updateCommodityData(this.value);
});

document.getElementById('syncButton').addEventListener('click', syncMarketData);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeChart();
    const defaultCommodity = document.getElementById('komoditasSelect').value;
    if (defaultCommodity) {
        updateCommodityData(defaultCommodity);
    }
});
</script>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
</body>
</html>
