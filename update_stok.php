<?php
require_once 'config/session.php';
startAgrixSession('login.php');
if (strtolower($_SESSION['role'] ?? 'user') !== 'user') {
    header('Location: login.php');
    exit;
}
include 'config/koneksi.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_stok'])) {
        $produk_id = (int)$_POST['produk_id'];
        $stok_baru = (int)$_POST['stok_baru'];
        $kualitas = mysqli_real_escape_string($conn, $_POST['kualitas']);

        // Update produk
        mysqli_query($conn, "UPDATE produk SET stok='$stok_baru', kualitas='$kualitas' WHERE produk_id='$produk_id' AND user_id='$userId'");

        // Insert riwayat
        mysqli_query($conn, "INSERT INTO riwayat_stok (produk_id, user_id, stok_lama, stok_baru, kualitas, tanggal_update) 
                            SELECT produk_id, user_id, stok, '$stok_baru', '$kualitas', NOW() FROM produk WHERE produk_id='$produk_id'");
        
        header('Location: update_stok.php');
        exit;
    }
}

$produkList = mysqli_query($conn, "SELECT * FROM produk WHERE user_id='$userId' ORDER BY nama_produk");
$riwayatList = mysqli_query($conn, "SELECT r.*, p.nama_produk FROM riwayat_stok r JOIN produk p ON r.produk_id = p.produk_id WHERE r.user_id='$userId' ORDER BY r.tanggal_update DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Stok & Kualitas - Agri-X</title>
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
            <a href="update_stok.php" class="nav-item active">
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
                <h1 class="page-title">Update Stok & Kualitas</h1>
            </div>
            <div class="navbar-right">
                <div class="profile-section">
                    <img src="assets/images/avatar.png" alt="Profile" class="profile-avatar">
                    <span class="profile-name">Penjual</span>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="form-card">
                <h2>Update Stok dan Kualitas Produk</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="produk_id">Pilih Produk</label>
                            <select id="produk_id" name="produk_id" required>
                                <option value="">Pilih Produk</option>
                                <?php while ($produk = mysqli_fetch_assoc($produkList)): ?>
                                    <option value="<?= $produk['produk_id'] ?>" data-stok="<?= $produk['stok'] ?>" data-kualitas="<?= htmlspecialchars($produk['kualitas']) ?>">
                                        <?= htmlspecialchars($produk['nama_produk']) ?> (Stok: <?= $produk['stok'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="stok_baru">Jumlah Stok Baru</label>
                            <input type="number" id="stok_baru" name="stok_baru" placeholder="Contoh: 50" required>
                        </div>
                        <div class="input-group">
                            <label for="kualitas">Status Kualitas Produk</label>
                            <select id="kualitas" name="kualitas" required>
                                <option value="Baik">Baik</option>
                                <option value="Sedang">Sedang</option>
                                <option value="Buruk">Buruk</option>
                            </select>
                        </div>
                    </div>
                    <div class="page-actions">
                        <button type="submit" name="update_stok" class="button button-primary">Update Stok & Kualitas</button>
                    </div>
                </form>
            </div>

            <div class="card large">
                <h2>Riwayat Perubahan Stok</h2>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Stok Lama</th>
                            <th>Stok Baru</th>
                            <th>Kualitas</th>
                            <th>Tanggal Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($riwayat = mysqli_fetch_assoc($riwayatList)): ?>
                            <tr>
                                <td><?= htmlspecialchars($riwayat['nama_produk']) ?></td>
                                <td><?= $riwayat['stok_lama'] ?></td>
                                <td><?= $riwayat['stok_baru'] ?></td>
                                <td><?= htmlspecialchars($riwayat['kualitas']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($riwayat['tanggal_update'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
document.getElementById('produk_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const stok = selected.getAttribute('data-stok');
    const kualitas = selected.getAttribute('data-kualitas');
    document.getElementById('stok_baru').value = stok;
    document.getElementById('kualitas').value = kualitas;
});
</script>
</body>
</html>