<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
include '../config/koneksi.php';

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

$role = strtolower($_SESSION['role'] ?? 'user');
$userId = $_SESSION['user_id'];

$filterCondition = $role === 'admin' ? '' : "WHERE user_id='$userId'";
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$whereClause = $filterCondition;
if ($search) {
    $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . "nama_produk LIKE '%$search%'";
}
if ($categoryFilter) {
    $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . "kategori='$categoryFilter'";
}
if ($statusFilter === 'menipis') {
    $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . "stok < 10";
} elseif ($statusFilter === 'tersedia') {
    $whereClause .= ($whereClause ? ' AND ' : 'WHERE ') . "stok >= 10";
}

$produkQuery = mysqli_query($conn, "SELECT * FROM produk $whereClause ORDER BY produk_id DESC");
$categoryQuery = mysqli_query($conn, "SELECT DISTINCT kategori FROM produk $filterCondition");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Agri-X</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="seller-container">
    <aside class="seller-sidebar">
        <div class="sidebar-header">
            <div class="brand">Agri-X</div>
            <small>Penjual</small>
        </div>
        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="index.php" class="nav-item active">
                <span class="nav-icon">📦</span>
                <span>Kelola Produk</span>
            </a>
            <a href="../update_stok.php" class="nav-item">
                <span class="nav-icon">🔄</span>
                <span>Update Stok & Kualitas</span>
            </a>
            <a href="../informasi_pasar.php" class="nav-item">
                <span class="nav-icon">💰</span>
                <span>Informasi Harga Pasar</span>
            </a>
            <a href="../rekomendasi_harga.php" class="nav-item">
                <span class="nav-icon">📈</span>
                <span>Rekomendasi Harga</span>
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
                <h1 class="page-title">Kelola Produk</h1>
            </div>
            <div class="navbar-right">
                <a href="tambah.php" class="btn-primary">+ Tambah Produk</a>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="form-card">
                <h2>Cari dan Filter Produk</h2>
                <form method="GET" class="search-filter">
                    <input type="text" name="search" placeholder="Cari nama produk..." value="<?= htmlspecialchars($search) ?>">
                    <select name="category">
                        <option value="">Semua Kategori</option>
                        <?php mysqli_data_seek($categoryQuery, 0); while ($cat = mysqli_fetch_assoc($categoryQuery)): ?>
                            <option value="<?= htmlspecialchars($cat['kategori']) ?>" <?= $categoryFilter == $cat['kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="tersedia" <?= $statusFilter == 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="menipis" <?= $statusFilter == 'menipis' ? 'selected' : '' ?>>Stok Menipis</option>
                    </select>
                    <button type="submit" class="btn-primary">Cari</button>
                </form>
            </div>

            <div class="card large">
                <h2>Daftar Produk</h2>
                <div class="table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Kualitas</th>
                                <th>Stok</th>
                                <th>Harga Jual</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; if (mysqli_num_rows($produkQuery) > 0): mysqli_data_seek($produkQuery, 0); while ($row = mysqli_fetch_assoc($produkQuery)):
                                $status = $row['stok'] < 10 ? 'Stok Menipis' : 'Tersedia';
                                $statusClass = $row['stok'] < 10 ? 'warning' : 'success';
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['nama_produk']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['kategori']) ?></td>
                                    <td><?= htmlspecialchars($row['kualitas']) ?></td>
                                    <td><?= number_format($row['stok']) ?> kg</td>
                                    <td><?= rupiah($row['harga_jual']) ?></td>
                                    <td><small><?= htmlspecialchars(substr($row['deskripsi'] ?? '', 0, 50)) ?><?= strlen($row['deskripsi'] ?? '') > 50 ? '...' : '' ?></small></td>
                                    <td><span class="badge <?= $statusClass ?>"><?= $status ?></span></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="edit.php?id=<?= $row['produk_id'] ?>">Edit</a>
                                            <a href="hapus.php?id=<?= $row['produk_id'] ?>" class="delete" onclick="return confirm('Yakin ingin menghapus produk?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="9">Tidak ada produk ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
