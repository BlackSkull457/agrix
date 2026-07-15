<?php
require_once 'config/session.php';
startAgrixSession('login.php');
include 'config/koneksi.php';

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

$role = strtolower($_SESSION['role'] ?? 'user');
$userId = $_SESSION['user_id'];
$displayName = $_SESSION['username'] ?? 'Penjual';

if ($role === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}

// For seller (user)
$filterCondition = "WHERE user_id='$userId'";
$totalProduk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM produk $filterCondition"))['total'] ?: 0;
$totalStok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok) AS total FROM produk $filterCondition"))['total'] ?: 0;
$lowStock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM produk $filterCondition AND stok < 10"))['total'] ?: 0;

$marketHistoryQuery = mysqli_query($conn, "SELECT nama_komoditas, harga, waktu_update FROM harga_pasar ORDER BY waktu_update DESC LIMIT 8");
$recentActivities = mysqli_query($conn, "SELECT * FROM notifikasi WHERE (penjual_id IS NULL OR penjual_id = $userId) ORDER BY waktu DESC LIMIT 5");
$unreadCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS unread FROM notifikasi WHERE status = 0 AND (penjual_id IS NULL OR penjual_id = $userId)"))['unread'] ?: 0;

$marketLabels = [];
$marketValues = [];
while ($row = mysqli_fetch_assoc($marketHistoryQuery)) {
    $marketLabels[] = date('d M', strtotime($row['waktu_update']));
    $marketValues[] = (float) $row['harga'];
}
if (empty($marketLabels)) {
    $marketLabels = ['-'];
    $marketValues = [0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penjual - Agri-X</title>
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
            <a href="dashboard.php" class="nav-item active">
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
            <a href="informasi_pasar.php" class="nav-item">
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
                <h1 class="page-title">Dashboard Penjual</h1>
            </div>
            <div class="navbar-right">
                <a href="notifikasi.php" class="notification-btn">
                    <span class="notif-icon">🔔</span>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notif-badge"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
                <div class="profile-section">
                    <img src="assets/images/avatar.png" alt="Profile" class="profile-avatar">
                    <span class="profile-name"><?= htmlspecialchars($displayName) ?></span>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?= $totalProduk ?></h3>
                        <p>Total Produk</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3><?= $totalStok ?></h3>
                        <p>Total Stok Produk</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <h3><?= $lowStock ?></h3>
                        <p>Produk Stok Menipis</p>
                    </div>
                </div>
            </div>

            <div class="charts-section">
                <div class="chart-card">
                    <h3>Grafik Perubahan Harga Pasar</h3>
                    <canvas id="marketChart"></canvas>
                </div>
            </div>

            <div class="activity-summary">
                <h3>Ringkasan Aktivitas Produk</h3>
                <div class="activity-list">
                    <?php mysqli_data_seek($recentActivities, 0); while ($activity = mysqli_fetch_assoc($recentActivities)): ?>
                        <div class="activity-item">
                            <div class="activity-icon">📝</div>
                            <div class="activity-content">
                                <strong><?= htmlspecialchars($activity['jenis'] ?? 'Aktivitas') ?></strong>
                                <p><?= htmlspecialchars($activity['pesan']) ?></p>
                                <small><?= date('d/m/Y H:i', strtotime($activity['waktu'])) ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="recent-notifications">
                <h3>Notifikasi Terbaru</h3>
                <div class="notification-list">
                    <?php mysqli_data_seek($recentActivities, 0); while ($notif = mysqli_fetch_assoc($recentActivities)): ?>
                        <div class="notification-item">
                            <strong><?= htmlspecialchars($notif['jenis']) ?></strong>
                            <p><?= htmlspecialchars($notif['pesan']) ?></p>
                            <small><?= date('d/m/Y H:i', strtotime($notif['waktu'])) ?></small>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
const marketLabels = <?= json_encode($marketLabels) ?>;
const marketValues = <?= json_encode($marketValues) ?>;

const marketCtx = document.getElementById('marketChart').getContext('2d');
new Chart(marketCtx, {
    type: 'line',
    data: {
        labels: marketLabels.reverse(),
        datasets: [{
            label: 'Harga Pasar',
            data: marketValues.reverse(),
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.12)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { callback: value => 'Rp ' + value.toLocaleString('id-ID') } }
        }
    }
});

</script>
</body>
</html>
