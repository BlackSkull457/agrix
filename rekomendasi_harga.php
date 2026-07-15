<?php
require_once 'config/session.php';
startAgrixSession('login.php');
if (strtolower($_SESSION['role'] ?? 'user') !== 'user') {
    header('Location: login.php');
    exit;
}
include 'config/koneksi.php';
require_once 'market_sync.php';

$marketDataCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM harga_pasar WHERE sumber='SISKAPERBAPO'"))['total'] ?: 0;

$marketPriceQuery = mysqli_query($conn, "SELECT nama_komoditas, kategori, harga, sumber, waktu_update FROM harga_pasar WHERE harga > 0 ORDER BY waktu_update DESC");
$latestMarketPrices = [];
while ($marketPrice = mysqli_fetch_assoc($marketPriceQuery)) {
    $marketKey = normalizeCommodityName($marketPrice['nama_komoditas']);
    if ($marketKey === '') {
        continue;
    }

    if (!isset($latestMarketPrices[$marketKey]) || strtotime($marketPrice['waktu_update']) > strtotime($latestMarketPrices[$marketKey]['waktu_update'])) {
        $latestMarketPrices[$marketKey] = $marketPrice;
    }
}
$latestMarketPrices = array_values($latestMarketPrices);

function getMarketReferencePrice($produkName, $produkKategori, $marketPrices) {
    $targetName = normalizeCommodityName($produkName);
    $targetCategory = strtolower(trim((string) $produkKategori));
    $targetCategoryGroup = mapCommodityCategory($produkName);

    $directMatches = [];
    $categoryMatches = [];

    foreach ($marketPrices as $marketPrice) {
        $marketName = normalizeCommodityName($marketPrice['nama_komoditas']);
        $marketCategory = strtolower(trim((string) $marketPrice['kategori']));
        $marketCategoryGroup = mapCommodityCategory($marketPrice['nama_komoditas']);

        $nameMatch = false;
        if ($targetName !== '' && $marketName !== '') {
            $nameMatch = $targetName === $marketName
                || strpos($targetName, $marketName) !== false
                || strpos($marketName, $targetName) !== false;
        }

        if ($nameMatch) {
            $directMatches[] = (float) $marketPrice['harga'];
            continue;
        }

        $categoryMatch = false;
        if ($targetCategory !== '' && $marketCategory !== '') {
            $categoryMatch = stripos($targetCategory, $marketCategory) !== false || stripos($marketCategory, $targetCategory) !== false;
        }

        if (!$categoryMatch && $targetCategoryGroup !== '' && $marketCategoryGroup !== '' && $targetCategoryGroup === $marketCategoryGroup) {
            $categoryMatch = true;
        }

        if ($categoryMatch) {
            $categoryMatches[] = (float) $marketPrice['harga'];
        }
    }

    if (!empty($directMatches)) {
        return array_sum($directMatches) / count($directMatches);
    }

    if (!empty($categoryMatches)) {
        return array_sum($categoryMatches) / count($categoryMatches);
    }

    return 0.0;
}

$userId = $_SESSION['user_id'];

$produkRows = [];
$categories = [];
$produkListQuery = mysqli_query($conn, "SELECT p.* FROM produk p WHERE p.user_id='$userId' ORDER BY p.nama_produk");
while ($row = mysqli_fetch_assoc($produkListQuery)) {
    $avg = getMarketReferencePrice($row['nama_produk'], $row['kategori'], $latestMarketPrices);
    $row['avg_pasar'] = $avg;
    $row['min_pasar'] = $avg;
    $row['max_pasar'] = $avg;
    $row['sample_count'] = $avg > 0 ? 1 : 0;

    $row['selisih'] = $avg > 0 ? (($row['harga_jual'] - $avg) / $avg) * 100 : 0;

    if ($avg > 0) {
        $diff = abs($row['selisih']);
        if ($diff <= 5) {
            $row['rek_status'] = 'Kompetitif';
            $row['status_class'] = 'success';
            $row['priority'] = 'Rendah';
            $row['priority_class'] = 'success';
        } elseif ($diff <= 20) {
            $row['rek_status'] = 'Perlu Penyesuaian';
            $row['status_class'] = 'warning';
            $row['priority'] = 'Sedang';
            $row['priority_class'] = 'warning';
        } else {
            $row['rek_status'] = $row['selisih'] > 0 ? 'Harga Terlalu Tinggi' : 'Harga Terlalu Rendah';
            $row['status_class'] = 'danger';
            $row['priority'] = 'Tinggi';
            $row['priority_class'] = 'danger';
        }
    } else {
        $row['rek_status'] = 'Data Pasar Tidak Tersedia';
        $row['status_class'] = 'info';
        $row['priority'] = 'Sedang';
        $row['priority_class'] = 'info';
    }

    if ($row['kategori'] && !in_array($row['kategori'], $categories)) {
        $categories[] = $row['kategori'];
    }
    $produkRows[] = $row;
}
sort($categories);

$filterCategory = $_GET['kategori'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$searchTerm = trim($_GET['search'] ?? '');
$filterComparison = $_GET['comparison'] ?? '';

$filteredProdukRows = array_filter($produkRows, function ($row) use ($filterCategory, $filterStatus, $searchTerm, $filterComparison) {
    if ($filterCategory !== '' && $row['kategori'] !== $filterCategory) {
        return false;
    }

    if ($searchTerm !== '' && stripos($row['nama_produk'], $searchTerm) === false && stripos($row['kategori'], $searchTerm) === false) {
        return false;
    }

    if ($filterStatus === 'kompetitif' && abs($row['selisih']) > 5) {
        return false;
    }
    if ($filterStatus === 'adjust' && !($row['avg_pasar'] > 0 && abs($row['selisih']) > 5)) {
        return false;
    }
    if ($filterStatus === 'unavailable' && $row['avg_pasar'] > 0) {
        return false;
    }

    if ($filterComparison === 'high' && $row['selisih'] <= 10) {
        return false;
    }
    if ($filterComparison === 'low' && $row['selisih'] >= -10) {
        return false;
    }
    if ($filterComparison === 'balanced' && abs($row['selisih']) > 10) {
        return false;
    }

    return true;
});

$totalProduk = count($produkRows);
$filteredCount = count($filteredProdukRows);
$kompetitifCount = 0;
$adjustmentCount = 0;
foreach ($produkRows as $row) {
    if ($row['avg_pasar'] > 0 && abs($row['selisih']) <= 5) {
        $kompetitifCount++;
    }
    if ($row['avg_pasar'] > 0 && abs($row['selisih']) > 5) {
        $adjustmentCount++;
    }
}
$actionPercent = $totalProduk > 0 ? round(($adjustmentCount / $totalProduk) * 100) : 0;

$marketSource = 'Tidak tersedia';
$marketUpdated = 'Tidak tersedia';
$marketInfo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(waktu_update) AS last_update, GROUP_CONCAT(DISTINCT sumber SEPARATOR ', ') AS sources FROM harga_pasar WHERE sumber='SISKAPERBAPO'"));
if ($marketInfo) {
    if (!empty($marketInfo['sources'])) {
        $marketSource = $marketInfo['sources'];
    }
    if (!empty($marketInfo['last_update'])) {
        $marketUpdated = date('d/m/Y H:i', strtotime($marketInfo['last_update']));
    }
}

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function getRekomendasi($harga_jual, $avg_pasar) {
    if ($avg_pasar == 0) {
        return ['text' => 'Data pasar tidak tersedia', 'action' => 'Monitor harga pasar'];
    }

    $selisih = (($harga_jual - $avg_pasar) / $avg_pasar) * 100;
    $rounded = number_format(abs($selisih), 1);

    if ($selisih > 20) {
        return [
            'text' => "Harga Anda $rounded% lebih tinggi dibanding rata-rata pasar.",
            'action' => 'Disarankan menurunkan harga menjadi lebih kompetitif.'
        ];
    }
    if ($selisih > 5) {
        return [
            'text' => "Harga Anda $rounded% lebih tinggi dibanding rata-rata pasar.",
            'action' => 'Pertimbangkan penyesuaian harga agar lebih seimbang dengan pasar.'
        ];
    }
    if ($selisih >= -5) {
        return [
            'text' => "Harga Anda kompetitif, hanya $rounded% dari rata-rata pasar.",
            'action' => 'Pertahankan harga saat ini untuk menjaga daya saing.'
        ];
    }
    if ($selisih >= -20) {
        return [
            'text' => "Harga Anda $rounded% lebih rendah dibanding rata-rata pasar.",
            'action' => 'Pertimbangkan sedikit kenaikan untuk margin yang lebih sehat.'
        ];
    }
    return [
        'text' => "Harga Anda $rounded% lebih rendah dibanding rata-rata pasar.",
        'action' => 'Harga sangat rendah; pertimbangkan menaikkan harga secara bertahap.'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekomendasi Harga - Agri-X</title>
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
            <a href="rekomendasi_harga.php" class="nav-item active">
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
                <h1 class="page-title">Rekomendasi Harga</h1>
            </div>
            <div class="navbar-right">
                <div class="profile-section">
                    <img src="assets/images/avatar.png" alt="Profile" class="profile-avatar">
                    <span class="profile-name">Penjual</span>
                </div>
            </div>
        </header>

        <main class="dashboard-content rekomendasi-harga">
            <div class="card large">
                <h2>Rekomendasi Harga Jual Berdasarkan Harga Pasar</h2>
                <p class="secondary-text">Analisis harga produk Anda dibandingkan dengan rata-rata harga pasar untuk kategori yang sama.</p>

                <form method="GET" class="search-filter" style="margin-top: 22px;">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="search">Cari Produk / Komoditas</label>
                            <input type="search" id="search" name="search" placeholder="Ketik nama produk atau kategori" value="<?= htmlspecialchars($searchTerm) ?>">
                        </div>
                        <div class="input-group">
                            <label for="kategori">Pilih Komoditas</label>
                            <select id="kategori" name="kategori">
                                <option value="">Semua Komoditas</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>" <?= $filterCategory === $category ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="status">Status Rekomendasi</label>
                            <select id="status" name="status">
                                <option value="">Semua Status</option>
                                <option value="kompetitif" <?= $filterStatus === 'kompetitif' ? 'selected' : '' ?>>Kompetitif</option>
                                <option value="adjust" <?= $filterStatus === 'adjust' ? 'selected' : '' ?>>Perlu Penyesuaian</option>
                                <option value="unavailable" <?= $filterStatus === 'unavailable' ? 'selected' : '' ?>>Data Pasar Tidak Tersedia</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="comparison">Perbandingan Harga</label>
                            <select id="comparison" name="comparison">
                                <option value="">Semua Perbandingan</option>
                                <option value="high" <?= $filterComparison === 'high' ? 'selected' : '' ?>>Harga Lebih Tinggi</option>
                                <option value="balanced" <?= $filterComparison === 'balanced' ? 'selected' : '' ?>>Harga Seimbang</option>
                                <option value="low" <?= $filterComparison === 'low' ? 'selected' : '' ?>>Harga Lebih Rendah</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center; margin-top: 14px; flex-wrap: wrap;">
                        <button type="submit" class="btn-primary">Terapkan Filter</button>
                        <a href="rekomendasi_harga.php" class="btn-secondary">Reset Filter</a>
                        <span class="secondary-text">Menampilkan <?= $filteredCount ?> dari <?= $totalProduk ?> produk</span>
                    </div>
                </form>

                <div class="table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Harga Anda</th>
                                <th>Rata-rata Pasar</th>
                                <th>Perbandingan</th>
                                <th>Status</th>
                                <th>Prioritas</th>
                                <th>Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($filteredProdukRows)): ?>
                                <?php foreach ($filteredProdukRows as $produk): 
                                    $rekom = getRekomendasi($produk['harga_jual'], $produk['avg_pasar']);
                                    $selisih = $produk['avg_pasar'] > 0 ? (($produk['harga_jual'] - $produk['avg_pasar']) / $produk['avg_pasar']) * 100 : 0;
                                    $compareClass = $produk['avg_pasar'] > 0 ? ($selisih > 10 ? 'danger' : ($selisih < -10 ? 'warning' : 'success')) : 'info';
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($produk['nama_produk']) ?></strong></td>
                                        <td><?= htmlspecialchars($produk['kategori']) ?></td>
                                        <td><?= rupiah($produk['harga_jual']) ?></td>
                                        <td><?= $produk['avg_pasar'] > 0 ? rupiah($produk['avg_pasar']) : 'N/A' ?></td>
                                        <td>
                                            <?php if ($produk['avg_pasar'] > 0): ?>
                                                <span class="badge <?= $compareClass ?>"><?= number_format($selisih, 1) ?>%</span>
                                            <?php else: ?>
                                                <span class="badge info">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge <?= $produk['status_class'] ?>"><?= htmlspecialchars($produk['rek_status']) ?></span></td>
                                        <td><span class="badge <?= $produk['priority_class'] ?>"><?= htmlspecialchars($produk['priority']) ?></span></td>
                                        <td>
                                            <strong><?= htmlspecialchars($rekom['text']) ?></strong><br>
                                            <span class="secondary-text"><?= htmlspecialchars($rekom['action']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 28px 0;">Tidak ada produk yang cocok dengan filter saat ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mobile-card-list">
                    <?php if (!empty($filteredProdukRows)): ?>
                        <?php foreach ($filteredProdukRows as $produk): 
                            $rekom = getRekomendasi($produk['harga_jual'], $produk['avg_pasar']);
                            $selisih = $produk['avg_pasar'] > 0 ? (($produk['harga_jual'] - $produk['avg_pasar']) / $produk['avg_pasar']) * 100 : 0;
                        ?>
                            <article class="product-card">
                                <div class="product-card-header">
                                    <div>
                                        <h4><?= htmlspecialchars($produk['nama_produk']) ?></h4>
                                        <p class="secondary-text"><?= htmlspecialchars($produk['kategori']) ?></p>
                                    </div>
                                    <span class="badge <?= $produk['status_class'] ?>"><?= htmlspecialchars($produk['rek_status']) ?></span>
                                </div>
                                <div class="product-card-row"><strong>Harga Anda</strong><span><?= rupiah($produk['harga_jual']) ?></span></div>
                                <div class="product-card-row"><strong>Rata-rata Pasar</strong><span><?= $produk['avg_pasar'] > 0 ? rupiah($produk['avg_pasar']) : 'N/A' ?></span></div>
                                <div class="product-card-row"><strong>Perbandingan</strong><span class="badge <?= $compareClass ?>"><?= $produk['avg_pasar'] > 0 ? number_format($selisih, 1) . '%' : 'N/A' ?></span></div>
                                <div class="product-card-row"><strong>Prioritas</strong><span class="badge <?= $produk['priority_class'] ?>"><?= htmlspecialchars($produk['priority']) ?></span></div>
                                <div class="product-card-row product-card-action">
                                    <div>
                                        <strong><?= htmlspecialchars($rekom['text']) ?></strong>
                                        <div class="secondary-text"><?= htmlspecialchars($rekom['action']) ?></div>
                                    </div>
                                    <button type="button" class="btn-secondary">Tinjau Harga</button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="summary-section">
                <div class="summary-card">
                    <h3>Ringkasan Data Produk</h3>
                    <div class="financial-grid">
                        <div class="financial-item">
                            <div class="label">Total produk</div>
                            <div class="value"><?= $totalProduk ?></div>
                        </div>
                        <div class="financial-item">
                            <div class="label">Produk kompetitif</div>
                            <div class="value"><?= $kompetitifCount ?></div>
                        </div>
                        <div class="financial-item">
                            <div class="label">Perlu penyesuaian</div>
                            <div class="value"><?= $adjustmentCount ?></div>
                        </div>
                        <div class="financial-item">
                            <div class="label">Persentase perlu tindakan</div>
                            <div class="value"><?= $actionPercent ?>%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="summary-section">
                <div class="summary-card">
                    <h3>Informasi Pasar</h3>
                    <div class="financial-grid">
                        <div class="financial-item">
                            <div class="label">Sumber Data</div>
                            <div class="value"><?= htmlspecialchars($marketSource) ?></div>
                        </div>
                        <div class="financial-item">
                            <div class="label">Terakhir Terbarui</div>
                            <div class="value"><?= htmlspecialchars($marketUpdated) ?></div>
                        </div>
                        <div class="financial-item">
                            <div class="label">Komoditas Terdaftar</div>
                            <div class="value"><?= count($categories) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

    </div>
</div>
</body>
</html>