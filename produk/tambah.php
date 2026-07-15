<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
include '../config/koneksi.php';

if (isset($_POST['simpan'])) {
    $user_id  = $_SESSION['user_id'];
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $kualitas = mysqli_real_escape_string($conn, $_POST['kualitas']);
    $stok     = (int) $_POST['stok'];
    $harga    = (int) $_POST['harga'];
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $foto     = '';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/produk/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($_FILES['foto']['name']);
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
        $newName = time() . '_' . $safeName . '.' . $fileExt;
        $targetPath = $uploadDir . $newName;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) {
            $foto = 'uploads/produk/' . $newName;
        }
    }

    mysqli_query($conn, "INSERT INTO produk (user_id, nama_produk, kategori, kualitas, stok, harga_jual, deskripsi, foto) VALUES ('$user_id', '$nama', '$kategori', '$kualitas', '$stok', '$harga', '$deskripsi', '$foto')");
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Agri-X</title>
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
                <h1 class="page-title">Tambah Produk Baru</h1>
            </div>
            <div class="navbar-right">
                <a href="index.php" class="btn-secondary">Kembali</a>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="form-card">
                <h2>Form Tambah Produk</h2>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="nama">Nama Produk</label>
                            <input type="text" id="nama" name="nama" placeholder="Contoh: Beras Premium" required>
                        </div>
                        <div class="input-group">
                            <label for="kategori">Kategori Produk</label>
                            <select id="kategori" name="kategori" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Padi & Biji-bijian">Padi & Biji-bijian</option>
                                <option value="Daging">Daging</option>
                                <option value="Telur & Susu">Telur & Susu</option>
                                <option value="Sayuran">Sayuran</option>
                                <option value="Buah-buahan">Buah-buahan</option>
                                <option value="Ikan & Seafood">Ikan & Seafood</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="kualitas">Kualitas Produk</label>
                            <select id="kualitas" name="kualitas" required>
                                <option value="Baik">Baik</option>
                                <option value="Sedang">Sedang</option>
                                <option value="Buruk">Buruk</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="stok">Stok (kg)</label>
                            <input type="number" id="stok" name="stok" placeholder="Contoh: 100" required>
                        </div>
                        <div class="input-group">
                            <label for="harga">Harga Jual (Rp/kg)</label>
                            <input type="number" id="harga" name="harga" placeholder="Contoh: 15000" required>
                        </div>
                        <div class="input-group">
                            <label for="foto">Foto Produk</label>
                            <input type="file" id="foto" name="foto" accept="image/*">
                        </div>
                    </div>
                    <div class="input-group" style="grid-column: span 2;">
                        <label for="deskripsi">Deskripsi Produk</label>
                        <textarea id="deskripsi" name="deskripsi" placeholder="Deskripsikan produk Anda..." rows="4"></textarea>
                    </div>
                    <div class="page-actions" style="margin-top:16px; justify-content:flex-start;">
                        <button type="submit" name="simpan" class="btn-primary">Simpan Produk</button>
                        <a href="index.php" class="btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
