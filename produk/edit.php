<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int) $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM produk WHERE produk_id='$id'");
$data = mysqli_fetch_assoc($query);

if (!$data || ($_SESSION['role'] != 'admin' && $data['user_id'] != $_SESSION['user_id'])) {
    echo 'Akses ditolak!';
    exit;
}

if (isset($_POST['update'])) {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $kualitas = mysqli_real_escape_string($conn, $_POST['kualitas']);
    $stok     = (int) $_POST['stok'];
    $harga    = (int) $_POST['harga_jual'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    mysqli_query($conn, "UPDATE produk SET nama_produk='$nama', kategori='$kategori', kualitas='$kualitas', stok='$stok', harga_jual='$harga', deskripsi='$deskripsi' WHERE produk_id='$id'");
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Agri-X</title>
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
                <h1 class="page-title">Edit Produk</h1>
            </div>
            <div class="navbar-right">
                <a href="index.php" class="btn-secondary">Kembali</a>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="form-card">
                <h2>Form Edit Produk</h2>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="nama_produk">Nama Produk</label>
                            <input type="text" id="nama_produk" name="nama_produk" value="<?= htmlspecialchars($data['nama_produk']) ?>" placeholder="Contoh: Beras Premium" required>
                        </div>
                        <div class="input-group">
                            <label for="kategori">Kategori Produk</label>
                            <select id="kategori" name="kategori" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Padi & Biji-bijian" <?= $data['kategori'] == 'Padi & Biji-bijian' ? 'selected' : '' ?>>Padi & Biji-bijian</option>
                                <option value="Daging" <?= $data['kategori'] == 'Daging' ? 'selected' : '' ?>>Daging</option>
                                <option value="Telur & Susu" <?= $data['kategori'] == 'Telur & Susu' ? 'selected' : '' ?>>Telur & Susu</option>
                                <option value="Sayuran" <?= $data['kategori'] == 'Sayuran' ? 'selected' : '' ?>>Sayuran</option>
                                <option value="Buah-buahan" <?= $data['kategori'] == 'Buah-buahan' ? 'selected' : '' ?>>Buah-buahan</option>
                                <option value="Ikan & Seafood" <?= $data['kategori'] == 'Ikan & Seafood' ? 'selected' : '' ?>>Ikan & Seafood</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="kualitas">Kualitas Produk</label>
                            <select id="kualitas" name="kualitas" required>
                                <option value="Baik" <?= $data['kualitas'] == 'Baik' ? 'selected' : '' ?>>Baik</option>
                                <option value="Sedang" <?= $data['kualitas'] == 'Sedang' ? 'selected' : '' ?>>Sedang</option>
                                <option value="Buruk" <?= $data['kualitas'] == 'Buruk' ? 'selected' : '' ?>>Buruk</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="stok">Stok (kg)</label>
                            <input type="number" id="stok" name="stok" value="<?= htmlspecialchars($data['stok']) ?>" placeholder="Contoh: 100" required>
                        </div>
                        <div class="input-group">
                            <label for="harga_jual">Harga Jual (Rp/kg)</label>
                            <input type="number" id="harga_jual" name="harga_jual" value="<?= htmlspecialchars($data['harga_jual']) ?>" placeholder="Contoh: 15000" required>
                        </div>
                        <div class="input-group">
                            <label for="foto">Foto Produk</label>
                            <input type="file" id="foto" name="foto" accept="image/*">
                            <?php if (!empty($data['foto'])): ?>
                                <small>Foto saat ini: <?= htmlspecialchars($data['foto']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="input-group" style="grid-column: span 2;">
                        <label for="deskripsi">Deskripsi Produk</label>
                        <textarea id="deskripsi" name="deskripsi" placeholder="Deskripsikan produk Anda..." rows="4"><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>
                    </div>
                    <div class="page-actions" style="margin-top:16px; justify-content:flex-start;">
                        <button type="submit" name="update" class="btn-primary">Simpan Perubahan</button>
                        <a href="index.php" class="btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
