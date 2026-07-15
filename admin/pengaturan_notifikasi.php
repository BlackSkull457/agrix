<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: ../login.php');
    exit;
}
include '../config/koneksi.php';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL
)");

function get_setting($conn, $key, $default = '') {
    $result = mysqli_query($conn, "SELECT setting_value FROM admin_settings WHERE setting_key='$key'");
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    mysqli_query($conn, "INSERT INTO admin_settings (setting_key, setting_value) VALUES ('$key', '$default')");
    return $default;
}

function set_setting($conn, $key, $value) {
    mysqli_query($conn, "INSERT INTO admin_settings (setting_key, setting_value) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE setting_value='$value'");
}

$batas_stok = get_setting($conn, 'batas_stok_minimum', '10');
$ambang_harga = get_setting($conn, 'ambang_perubahan_harga', '5');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $batas_stok = (int) $_POST['batas_stok_minimum'];
        $ambang_harga = (float) $_POST['ambang_perubahan_harga'];
        set_setting($conn, 'batas_stok_minimum', $batas_stok);
        set_setting($conn, 'ambang_perubahan_harga', $ambang_harga);
        header('Location: pengaturan_notifikasi.php');
        exit;
    }

    if (isset($_POST['add_notif'])) {
        $jenis = $_POST['jenis'];
        $pesan = $_POST['pesan'];
        $status = (int) $_POST['status'];
        $targetType = $_POST['target_type'] ?? 'global';
        $targetUserId = isset($_POST['penjual_id']) ? (int) $_POST['penjual_id'] : 0;

        if ($targetType === 'user' && $targetUserId > 0) {
            $stmt = mysqli_prepare($conn, "INSERT INTO notifikasi (jenis, penjual_id, pesan, waktu, status) VALUES (?, ?, ?, NOW(), ?)");
            mysqli_stmt_bind_param($stmt, 'sisi', $jenis, $targetUserId, $pesan, $status);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO notifikasi (jenis, penjual_id, pesan, waktu, status) VALUES (?, NULL, ?, NOW(), ?)");
            mysqli_stmt_bind_param($stmt, 'ssi', $jenis, $pesan, $status);
        }

        mysqli_stmt_execute($stmt);
        header('Location: pengaturan_notifikasi.php');
        exit;
    }

    if (isset($_POST['edit_notif'])) {
        $id = (int) $_POST['id'];
        $jenis = $_POST['jenis'];
        $pesan = $_POST['pesan'];
        $status = (int) $_POST['status'];
        $stmt = mysqli_prepare($conn, "UPDATE notifikasi SET jenis = ?, pesan = ?, status = ? WHERE notifikasi_id = ?");
        mysqli_stmt_bind_param($stmt, 'ssii', $jenis, $pesan, $status, $id);
        mysqli_stmt_execute($stmt);
        header('Location: pengaturan_notifikasi.php');
        exit;
    }

    if (isset($_POST['delete_notif'])) {
        $id = (int) $_POST['id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM notifikasi WHERE notifikasi_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        header('Location: pengaturan_notifikasi.php');
        exit;
    }
}

$users = mysqli_query($conn, "SELECT user_id, username FROM users WHERE role = 'user' ORDER BY username");
$notifikasi = mysqli_query($conn, "SELECT * FROM notifikasi ORDER BY waktu DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Notifikasi - Agri-X</title>
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
            <a href="update_harga_pasar.php" class="nav-item">
                <span class="nav-icon">💰</span>
                <span>Kelola Harga Pasar</span>
            </a>
            <a href="pengaturan_notifikasi.php" class="nav-item active">
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
                <h1 class="page-title">Pengaturan Notifikasi</h1>
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
                <h2>Atur Ambang Notifikasi</h2>
                <form method="post">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="batas_stok_minimum">Batas Stok Minimum (kg)</label>
                            <input type="number" id="batas_stok_minimum" name="batas_stok_minimum" value="<?= htmlspecialchars($batas_stok) ?>" required>
                        </div>
                        <div class="input-group">
                            <label for="ambang_perubahan_harga">Ambang Perubahan Harga (%)</label>
                            <input type="number" step="0.1" id="ambang_perubahan_harga" name="ambang_perubahan_harga" value="<?= htmlspecialchars($ambang_harga) ?>" required>
                        </div>
                    </div>
                    <div class="page-actions" style="justify-content:flex-start; margin-top:16px;">
                        <button type="submit" name="update_settings" class="btn-primary">Simpan Pengaturan</button>
                    </div>
                </form>
            </div>

            <div class="form-card">
                <h2>Kelola Notifikasi Sistem</h2>
                <form method="post">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="jenis">Jenis Notifikasi</label>
                            <select id="jenis" name="jenis" required>
                                <option value="Info">Info</option>
                                <option value="Warning">Warning</option>
                                <option value="Error">Error</option>
                                <option value="Success">Success</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="0">Unread</option>
                                <option value="1">Read</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="target_type">Tujuan Notifikasi</label>
                            <select id="target_type" name="target_type">
                                <option value="global">Global (semua user)</option>
                                <option value="user">Khusus user tertentu</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="penjual_id">Pilih User</label>
                            <select id="penjual_id" name="penjual_id">
                                <option value="">-- Pilih user --</option>
                                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                    <option value="<?= (int) $user['user_id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="input-group" style="grid-column: span 2;">
                        <label for="pesan">Pesan Notifikasi</label>
                        <textarea id="pesan" name="pesan" rows="4" required></textarea>
                    </div>
                    <div class="page-actions" style="justify-content:flex-start; margin-top:16px;">
                        <button type="submit" name="add_notif" class="btn-primary">Tambah Notifikasi</button>
                    </div>
                </form>
            </div>

            <div class="table-section">
                <h3>Daftar Notifikasi Sistem</h3>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Target</th>
                            <th>Pesan</th>
                            <th>Status</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($notif = mysqli_fetch_assoc($notifikasi)): ?>
                            <tr>
                                <td><?= htmlspecialchars($notif['jenis']) ?></td>
                                <td><?= $notif['penjual_id'] === null ? 'Global' : 'User #' . (int) $notif['penjual_id'] ?></td>
                                <td><?= htmlspecialchars($notif['pesan']) ?></td>
                                <td><?= $notif['status'] == 0 ? '<span class="status-unread">Unread</span>' : '<span class="status-read">Read</span>' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($notif['waktu'])) ?></td>
                                <td>
                                    <form method="post" style="display:inline-block; margin-right:8px;">
                                        <input type="hidden" name="id" value="<?= $notif['notifikasi_id'] ?>">
                                        <input type="hidden" name="jenis" value="<?= htmlspecialchars($notif['jenis']) ?>">
                                        <input type="hidden" name="pesan" value="<?= htmlspecialchars($notif['pesan']) ?>">
                                        <input type="hidden" name="status" value="<?= $notif['status'] ?>">
                                        <button type="submit" name="edit_notif" class="btn-edit">Edit</button>
                                    </form>
                                    <form method="post" style="display:inline-block;">
                                        <input type="hidden" name="id" value="<?= $notif['notifikasi_id'] ?>">
                                        <button type="submit" name="delete_notif" class="btn-delete" onclick="return confirm('Hapus notifikasi ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>
