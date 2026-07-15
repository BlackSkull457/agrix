<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: ../login.php');
    exit;
}
include '../config/koneksi.php';

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM produk"))['total'] ?: 0;
$total_harga_updates = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM harga_pasar"))['total'] ?: 0;
$total_notifikasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifikasi"))['total'] ?: 0;
$avg_harga_pasar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(harga) AS avg FROM harga_pasar"))['avg'] ?: 0;
$total_value_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(harga_jual * stok) AS total FROM produk"))['total'] ?: 0;

$monthly_produk = mysqli_query($conn, "SELECT MONTH(tanggal_update) AS bulan, COUNT(*) AS jumlah FROM produk WHERE YEAR(tanggal_update) = YEAR(CURDATE()) GROUP BY MONTH(tanggal_update)");
$monthly_harga = mysqli_query($conn, "SELECT MONTH(waktu_update) AS bulan, AVG(harga) AS rata_harga FROM harga_pasar WHERE YEAR(waktu_update) = YEAR(CURDATE()) GROUP BY MONTH(waktu_update)");

$produk_data = array_fill(1, 12, 0);
$harga_data = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($monthly_produk)) {
    $produk_data[$row['bulan']] = (int) $row['jumlah'];
}
while ($row = mysqli_fetch_assoc($monthly_harga)) {
    $harga_data[$row['bulan']] = round($row['rata_harga'], 0);
}

$recent_activities = mysqli_query($conn, "SELECT jenis, pesan, waktu, status FROM notifikasi ORDER BY waktu DESC LIMIT 8");
$top_products = mysqli_query($conn, "SELECT nama_produk, harga_jual, stok, (harga_jual * stok) AS total_value FROM produk ORDER BY total_value DESC LIMIT 5");
$low_stock = mysqli_query($conn, "SELECT nama_produk, stok FROM produk WHERE stok < 10 ORDER BY stok ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Ringkasan - Agri-X</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="seller-container">
    <aside class="seller-sidebar">
        <div class="sidebar-header">
            <div class="brand">Agri-X</div>
            <small>Admin Panel</small>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="update_harga_pasar.php" class="nav-item">
                <span class="nav-icon">💰</span>
                <span>Kelola Harga Pasar</span>
            </a>
            <a href="pengaturan_notifikasi.php" class="nav-item">
                <span class="nav-icon">⚙️</span>
                <span>Kelola Pengaturan Notifikasi</span>
            </a>
            <a href="laporan_ringkasan.php" class="nav-item active">
                <span class="nav-icon">📈</span>
                <span>Laporan Ringkasan</span>
            </a>
            <a href="../logout.php" class="nav-item logout">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <div class="seller-main">
        <header class="seller-navbar">
            <div class="navbar-left">
                <h1 class="page-title">Laporan Ringkasan</h1>
            </div>
            <div class="navbar-right">
                <button class="btn-primary" onclick="window.print()">Cetak Laporan</button>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?= $total_produk ?></h3>
                        <p>Total Produk</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3><?= $total_harga_updates ?></h3>
                        <p>Total Update Harga</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔔</div>
                    <div class="stat-info">
                        <h3><?= $total_notifikasi ?></h3>
                        <p>Notifikasi</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💹</div>
                    <div class="stat-info">
                        <h3><?= rupiah($avg_harga_pasar) ?></h3>
                        <p>Rata-rata Harga Pasar</p>
                    </div>
                </div>
            </div>

            <div class="summary-section">
                <div class="summary-card">
                    <h3>Ringkasan Data Produk</h3>
                    <div class="financial-grid">
                        <div class="financial-item">
                            <span class="label">Nilai Total Produk</span>
                            <span class="value"><?= rupiah($total_value_produk) ?></span>
                        </div>
                        <div class="financial-item">
                            <span class="label">Rata-rata Harga Pasar</span>
                            <span class="value"><?= rupiah($avg_harga_pasar) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="charts-section">
                <div class="chart-card">
                    <h3>Statistik Harga Pasar per Bulan</h3>
                    <canvas id="marketReportChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Perubahan Produk per Bulan</h3>
                    <canvas id="productReportChart"></canvas>
                </div>
            </div>

            <div class="tables-section">
                <div class="table-card">
                    <h3>Produk dengan Nilai Tertinggi</h3>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th>Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = mysqli_fetch_assoc($top_products)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['nama_produk']) ?></td>
                                    <td><?= rupiah($product['harga_jual']) ?></td>
                                    <td><?= number_format($product['stok']) ?></td>
                                    <td><?= rupiah($product['total_value']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-card">
                    <h3>Aktivitas Sistem</h3>
                    <?php while ($activity = mysqli_fetch_assoc($recent_activities)): ?>
                        <div class="activity-item">
                            <div class="activity-icon">📝</div>
                            <div class="activity-content">
                                <strong><?= htmlspecialchars($activity['jenis']) ?></strong>
                                <p><?= htmlspecialchars($activity['pesan']) ?></p>
                                <small><?= date('d/m/Y H:i', strtotime($activity['waktu'])) ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="table-card">
                <h3>Stok Rendah dan Peringatan</h3>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th>Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($stock = mysqli_fetch_assoc($low_stock)): ?>
                            <tr>
                                <td><?= htmlspecialchars($stock['nama_produk']) ?></td>
                                <td><?= number_format($stock['stok']) ?></td>
                                <td><span class="status-warning">Stok Rendah</span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
const marketReportCtx = document.getElementById('marketReportChart').getContext('2d');
new Chart(marketReportCtx, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Rata-rata Harga Pasar',
            data: <?= json_encode(array_values($harga_data)) ?>,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.15)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});

const productReportCtx = document.getElementById('productReportChart').getContext('2d');
new Chart(productReportCtx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Produk Ditambahkan',
            data: <?= json_encode(array_values($produk_data)) ?>,
            backgroundColor: '#22c55e'
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
