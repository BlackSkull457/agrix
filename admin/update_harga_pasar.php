<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}
include '../config/koneksi.php';

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

if (isset($_POST['tambah'])) {
    $nama_komoditas = mysqli_real_escape_string($conn, $_POST['nama_komoditas']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = (float) $_POST['harga'];
    $sumber = mysqli_real_escape_string($conn, $_POST['sumber']);

    mysqli_query($conn, "INSERT INTO harga_pasar (nama_komoditas, kategori, harga, sumber, waktu_update) VALUES ('$nama_komoditas', '$kategori', '$harga', '$sumber', NOW())");
    header('Location: update_harga_pasar.php');
    exit;
}

if (isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $nama_komoditas = mysqli_real_escape_string($conn, $_POST['nama_komoditas']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = (float) $_POST['harga'];
    $sumber = mysqli_real_escape_string($conn, $_POST['sumber']);

    mysqli_query($conn, "UPDATE harga_pasar SET nama_komoditas='$nama_komoditas', kategori='$kategori', harga='$harga', sumber='$sumber', waktu_update=NOW() WHERE harga_pasar_id='$id'");
    header('Location: update_harga_pasar.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM harga_pasar WHERE harga_pasar_id='$id'");
    header('Location: update_harga_pasar.php');
    exit;
}

$mode = isset($_GET['edit']) ? 'edit' : 'add';
$editData = null;
if ($mode == 'edit') {
    $id = (int) $_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM harga_pasar WHERE harga_pasar_id='$id'");
    $editData = mysqli_fetch_assoc($result);
}

$hargaPasarList = mysqli_query($conn, "SELECT * FROM harga_pasar ORDER BY waktu_update DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Harga Pasar - Agri-X</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
            <a href="update_harga_pasar.php" class="nav-item active">
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
                <h1 class="page-title">Kelola Harga Pasar</h1>
            </div>
            <div class="navbar-right">
                <div class="profile-section">
                    <img src="../assets/images/admin-avatar.png" alt="Admin" class="profile-avatar">
                    <span class="profile-name">Admin</span>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="form-card">
                <h2><?= $mode === 'add' ? 'Tambah Data Harga Pasar' : 'Perbarui Harga Pasar' ?></h2>
                <form method="post">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="nama_komoditas">Nama Komoditas</label>
                            <input type="text" id="nama_komoditas" name="nama_komoditas" placeholder="Contoh: Beras Premium" value="<?= $editData ? htmlspecialchars($editData['nama_komoditas']) : '' ?>" required>
                        </div>
                        <div class="input-group">
                            <label for="kategori">Kategori</label>
                            <select id="kategori" name="kategori" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Padi & Biji-bijian" <?= $editData && $editData['kategori'] == 'Padi & Biji-bijian' ? 'selected' : '' ?>>Padi & Biji-bijian</option>
                                <option value="Daging" <?= $editData && $editData['kategori'] == 'Daging' ? 'selected' : '' ?>>Daging</option>
                                <option value="Telur & Susu" <?= $editData && $editData['kategori'] == 'Telur & Susu' ? 'selected' : '' ?>>Telur & Susu</option>
                                <option value="Sayuran" <?= $editData && $editData['kategori'] == 'Sayuran' ? 'selected' : '' ?>>Sayuran</option>
                                <option value="Buah-buahan" <?= $editData && $editData['kategori'] == 'Buah-buahan' ? 'selected' : '' ?>>Buah-buahan</option>
                                <option value="Ikan & Seafood" <?= $editData && $editData['kategori'] == 'Ikan & Seafood' ? 'selected' : '' ?>>Ikan & Seafood</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="harga">Harga (Rp/kg)</label>
                            <input type="number" id="harga" name="harga" placeholder="Contoh: 15000" value="<?= $editData ? htmlspecialchars($editData['harga']) : '' ?>" required>
                        </div>
                        <div class="input-group">
                            <label for="sumber">Sumber Data Harga</label>
                            <input type="text" id="sumber" name="sumber" placeholder="Contoh: Data Langsung Admin" value="<?= $editData ? htmlspecialchars($editData['sumber']) : 'Admin' ?>" required>
                        </div>
                    </div>
                    <?php if ($mode === 'edit'): ?>
                        <input type="hidden" name="id" value="<?= $editData['harga_pasar_id'] ?>">
                    <?php endif; ?>
                    <div class="page-actions" style="margin-top:16px; justify-content:flex-start;">
                        <button type="submit" name="<?= $mode === 'add' ? 'tambah' : 'update' ?>" class="btn-primary"><?= $mode === 'add' ? 'Tambah Data' : 'Perbarui Data' ?></button>
                        <?php if ($mode === 'edit'): ?>
                            <a href="update_harga_pasar.php" class="btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="recent-updates">
                <h3>Riwayat Perubahan Harga</h3>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Komoditas</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Sumber</th>
                            <th>Tanggal Update</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($hargaPasarList) > 0): $no = 1; mysqli_data_seek($hargaPasarList, 0); while ($row = mysqli_fetch_assoc($hargaPasarList)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama_komoditas']) ?></td>
                                <td><?= htmlspecialchars($row['kategori']) ?></td>
                                <td><?= rupiah($row['harga']) ?></td>
                                <td><?= htmlspecialchars($row['sumber']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['waktu_update'])) ?></td>
                                <td>
                                    <div class="action-links">
                                        <a href="update_harga_pasar.php?edit=<?= $row['harga_pasar_id'] ?>">Edit</a>
                                        <a href="update_harga_pasar.php?delete=<?= $row['harga_pasar_id'] ?>" class="delete" onclick="return confirm('Yakin ingin menghapus data?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="7">Belum ada data harga pasar. Tambahkan data untuk memulai.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>
