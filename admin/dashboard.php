<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: ../login.php');
    exit;
}
include '../config/koneksi.php';

function rupiah($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT nama_komoditas) AS total FROM harga_pasar"))['total'] ?: 0;
$total_harga_updates = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM harga_pasar"))['total'] ?: 0;
$total_notifikasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifikasi"))['total'] ?: 0;
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'] ?: 0;
$avg_harga_pasar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(harga) AS avg FROM harga_pasar"))['avg'] ?: 0;
$latest_updates = mysqli_query($conn, "SELECT * FROM harga_pasar ORDER BY waktu_update DESC LIMIT 5");
$recent_activities = mysqli_query($conn, "SELECT jenis, pesan, waktu, status FROM notifikasi ORDER BY waktu DESC LIMIT 5");

$price_history = mysqli_query($conn, "
    SELECT DATE(waktu_update) AS tanggal,
           DATE_FORMAT(waktu_update, '%d %b') AS label,
           AVG(harga) AS avg_harga
    FROM harga_pasar
    GROUP BY DATE(waktu_update)
    ORDER BY DATE(waktu_update) ASC
");
$price_comparison = mysqli_query($conn, "
    SELECT current.nama_komoditas,
           current.harga AS harga_terkini,
           COALESCE(previous.harga, current.harga) AS harga_sebelumnya
    FROM harga_pasar current
    LEFT JOIN harga_pasar previous
        ON previous.nama_komoditas = current.nama_komoditas
       AND previous.waktu_update = (
            SELECT MAX(p.waktu_update)
            FROM harga_pasar p
            WHERE p.nama_komoditas = current.nama_komoditas
              AND p.waktu_update < current.waktu_update
       )
    WHERE current.waktu_update = (
        SELECT MAX(latest.waktu_update)
        FROM harga_pasar latest
        WHERE latest.nama_komoditas = current.nama_komoditas
    )
    ORDER BY current.harga DESC
    LIMIT 6
");

$trend_labels = [];
$trend_values = [];
while ($row = mysqli_fetch_assoc($price_history)) {
    $trend_labels[] = $row['label'];
    $trend_values[] = round((float) $row['avg_harga'], 0);
}
if (empty($trend_labels)) {
    $trend_labels = ['-'];
    $trend_values = [0];
}

$compare_labels = [];
$compare_current = [];
$compare_previous = [];
while ($row = mysqli_fetch_assoc($price_comparison)) {
    $compare_labels[] = $row['nama_komoditas'];
    $compare_current[] = round((float) $row['harga_terkini'], 0);
    $compare_previous[] = round((float) $row['harga_sebelumnya'], 0);
}
if (empty($compare_labels)) {
    $compare_labels = ['-'];
    $compare_current = [0];
    $compare_previous = [0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Agri-X</title>
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
            <a href="dashboard.php" class="nav-item active">
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
            <a href="laporan_ringkasan.php" class="nav-item">
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
                <h1 class="page-title">Dashboard Admin</h1>
            </div>
            <div class="navbar-right">
                <button class="notification-btn">
                    <span class="notif-icon">🔔</span>
                </button>
                <div class="profile-section">
                    <img src="../assets/images/admin-avatar.png" alt="Admin" class="profile-avatar">
                    <span class="profile-name">Admin</span>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?= $total_produk ?></h3>
                        <p>Total Komoditas</p>
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
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?= $total_users ?></h3>
                        <p>Total Pengguna</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔔</div>
                    <div class="stat-info">
                        <h3><?= $total_notifikasi ?></h3>
                        <p>Notifikasi Sistem</p>
                    </div>
                </div>
            </div>

            <div class="charts-section">
                <div class="chart-card">
                    <h3>Grafik Perubahan Harga Pasar</h3>
                    <canvas id="marketTrendChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Grafik Perbandingan Harga Produk</h3>
                    <canvas id="priceComparisonChart"></canvas>
                </div>
            </div>

            <div class="activity-summary">
                <h3>Ringkasan Aktivitas Sistem</h3>
                <div class="activity-list">
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

            <div class="recent-updates">
                <h3>Tabel Update Harga Terbaru</h3>
                <div class="table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Komoditas</th>
                                <th>Kategori</th>
                                <th>Harga (Rp/kg)</th>
                                <th>Sumber</th>
                                <th>Tanggal Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($latest_updates)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nama_komoditas']) ?></td>
                                    <td><?= htmlspecialchars($row['kategori']) ?></td>
                                    <td><?= rupiah($row['harga']) ?></td>
                                    <td><?= htmlspecialchars($row['sumber']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['waktu_update'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
const marketTrendCtx = document.getElementById('marketTrendChart').getContext('2d');
new Chart(marketTrendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_labels) ?>,
        datasets: [{
            label: 'Harga Pasar Rata-rata',
            data: <?= json_encode($trend_values) ?>,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#22c55e'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

const priceComparisonCtx = document.getElementById('priceComparisonChart').getContext('2d');
new Chart(priceComparisonCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($compare_labels) ?>,
        datasets: [
            {
                label: 'Harga Terkini',
                data: <?= json_encode($compare_current) ?>,
                backgroundColor: '#22c55e'
            },
            {
                label: 'Harga Sebelumnya',
                data: <?= json_encode($compare_previous) ?>,
                backgroundColor: '#94a3b8'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
