<?php
require_once 'config/session.php';
startAgrixSession('login.php');
include 'config/koneksi.php';

$userId = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "UPDATE notifikasi SET status = 1 WHERE status = 0 AND (penjual_id IS NULL OR penjual_id = ?)");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);

$notifQuery = mysqli_query($conn, "SELECT * FROM notifikasi WHERE (penjual_id IS NULL OR penjual_id = $userId) ORDER BY waktu DESC");
$unreadCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS unread FROM notifikasi WHERE status = 0 AND (penjual_id IS NULL OR penjual_id = $userId)"))['unread'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Agri-X</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                <h1 class="page-title">Notifikasi</h1>
            </div>
            <div class="navbar-right">
                <div class="notification-icon">
                    <span class="icon">🔔</span>
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge-count"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="card large">
                <h2>Semua Notifikasi</h2>
                <?php if (mysqli_num_rows($notifQuery) > 0): ?>
                    <div class="notifications-list">
                        <?php while ($notif = mysqli_fetch_assoc($notifQuery)): ?>
                            <div class="notification-item <?= $notif['status'] == 0 ? 'unread' : '' ?>">
                                <div class="notification-content">
                                    <div class="notification-header">
                                        <span class="notification-type <?= $notif['jenis'] ?>">
                                            <?php
                                            $icon = '';
                                            switch($notif['jenis']) {
                                                case 'stok_menipis': $icon = '⚠️'; break;
                                                case 'harga_pasar': $icon = '💰'; break;
                                                case 'produk_update': $icon = '📦'; break;
                                                default: $icon = '📢';
                                            }
                                            echo $icon;
                                            ?>
                                        </span>
                                        <span class="notification-time">
                                            <?= date('d/m/Y H:i', strtotime($notif['waktu'])) ?>
                                        </span>
                                    </div>
                                    <div class="notification-message">
                                        <?= htmlspecialchars($notif['pesan']) ?>
                                    </div>
                                    <?php if ($notif['penjual_id'] === null): ?>
                                        <small style="color:#16a34a;">Notifikasi global</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>Tidak ada notifikasi</h3>
                        <p>Anda belum memiliki notifikasi apapun.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
</body>
</html>