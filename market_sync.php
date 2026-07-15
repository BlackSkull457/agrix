<?php
if (!function_exists('normalizeCommodityName')) {
    function normalizeCommodityName($nama) {
        $nama = trim($nama);
        $nama = preg_replace('/\s*\/\s*.*$/i', '', $nama);
        $nama = preg_replace('/\s*\(.*?\)/i', '', $nama);
        $nama = preg_replace('/\s+/', ' ', $nama);
        return trim($nama);
    }
}

if (!function_exists('mapCommodityCategory')) {
    function mapCommodityCategory($nama) {
    $nama = strtolower($nama);

    if (preg_match('/beras|jagung|kedelai|tepung|terigu|gula|minyak|garam|pupuk|sagu|kentang|ketela/i', $nama)) {
        return 'Padi & Biji-bijian';
    }

    if (preg_match('/daging|sapi|ayam/i', $nama)) {
        return 'Daging';
    }

    if (preg_match('/telur|susu/i', $nama)) {
        return 'Telur & Susu';
    }

    if (preg_match('/cabe|cabai|bawang|tomat|wortel|kubis|kol|buncis|kacang|sayur/i', $nama)) {
        return 'Sayuran';
    }

    if (preg_match('/ikan|bandeng|tuna|tongkol|cakalang|seafood|udang|kerang/i', $nama)) {
        return 'Ikan & Seafood';
    }

        if (preg_match('/buah|mangga|apel|jeruk|pisang|alpukat|pepaya/i', $nama)) {
            return 'Buah-buahan';
        }

        return 'Lainnya';
    }
}

if (!function_exists('parseExternalPrice')) {
    function parseExternalPrice($value) {
        $value = trim((string) $value);
        if ($value === '' || $value === '0') {
            return 0;
        }

        $value = preg_replace('/[^0-9,.-]/', '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }
}

if (!function_exists('syncExternalMarketData')) {
    function syncExternalMarketData($conn) {
    $url = 'https://siskaperbapo.jatimprov.go.id/';
    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'ignore_errors' => true,
            'header' => "User-Agent: Mozilla/5.0\r\nAccept-Language: id-ID,id;q=0.9,en;q=0.8\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $fetchResult = fetchUrl($url, $context);
    if ($fetchResult['success'] === false) {
        return [
            'success' => false,
            'message' => 'Gagal mengambil konten dari SISKAPERBAPO. ' . $fetchResult['error'],
            'inserted' => 0,
            'updated' => 0,
            'timestamp' => date('d/m/Y H:i:s')
        ];
    }

    $html = $fetchResult['content'];
    if ($html === '' || stripos($html, 'NAMA BAHAN POKOK') === false) {
        return [
            'success' => false,
            'message' => 'Tabel data harga tidak ditemukan atau konten tidak sesuai.',
            'inserted' => 0,
            'updated' => 0,
            'timestamp' => date('d/m/Y H:i:s')
        ];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $targetTable = null;
    $tables = $xpath->query('//table');
    foreach ($tables as $table) {
        $text = trim($table->textContent);
        if (stripos($text, 'NAMA BAHAN POKOK') !== false) {
            $targetTable = $table;
            break;
        }
    }

    if ($targetTable === null) {
        return [
            'success' => false,
            'message' => 'Tabel data harga tidak ditemukan.',
            'inserted' => 0,
            'updated' => 0,
            'timestamp' => date('d/m/Y H:i:s')
        ];
    }

    $rows = $xpath->query('.//tr', $targetTable);
    $inserted = 0;
    $updated = 0;
    $waktuUpdate = date('Y-m-d H:i:s');
    $sumber = 'SISKAPERBAPO';

    foreach ($rows as $row) {
        $cells = [];
        foreach ($xpath->query('.//td|.//th', $row) as $cell) {
            $cells[] = trim($cell->textContent);
        }

        if (count($cells) < 4) {
            continue;
        }

        $firstCell = trim($cells[0]);
        if (!preg_match('/^\d+$/', $firstCell)) {
            continue;
        }

        $namaKomoditas = normalizeCommodityName($cells[1] ?? '');
        $harga = parseExternalPrice($cells[3] ?? '');
        if ($namaKomoditas === '' || $harga <= 0) {
            continue;
        }

        $kategori = mapCommodityCategory($namaKomoditas);
        $namaKomoditasEsc = mysqli_real_escape_string($conn, $namaKomoditas);
        $kategoriEsc = mysqli_real_escape_string($conn, $kategori);
        $sumberEsc = mysqli_real_escape_string($conn, $sumber);

        $lastRowQuery = mysqli_query($conn, "SELECT harga, waktu_update FROM harga_pasar WHERE nama_komoditas = '$namaKomoditasEsc' AND sumber = '$sumberEsc' ORDER BY waktu_update DESC LIMIT 1");
        $lastRow = mysqli_fetch_assoc($lastRowQuery);
        if ($lastRow && (float) $lastRow['harga'] === $harga) {
            continue;
        }

        mysqli_query($conn, "INSERT INTO harga_pasar (nama_komoditas, kategori, harga, sumber, waktu_update) VALUES ('$namaKomoditasEsc', '$kategoriEsc', $harga, '$sumberEsc', '$waktuUpdate')");
        $inserted++;
    }

        return [
            'success' => true,
            'message' => 'Sinkronisasi selesai.',
            'inserted' => $inserted,
            'updated' => $updated,
            'timestamp' => date('d/m/Y H:i:s')
        ];
    }
}

if (!function_exists('fetchUrl')) {
    function fetchUrl($url, $context = null) {
        if (function_exists('curl_version')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept-Language: id-ID,id;q=0.9,en;q=0.8'
            ]);

            $result = curl_exec($ch);
            if ($result === false) {
                $errorMessage = curl_error($ch);
                curl_close($ch);
                return ['success' => false, 'content' => '', 'error' => $errorMessage];
            }

            curl_close($ch);
            return ['success' => true, 'content' => $result, 'error' => ''];
        }

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            $error = error_get_last();
            return ['success' => false, 'content' => '', 'error' => $error['message'] ?? 'Unknown fetch error'];
        }

        return ['success' => true, 'content' => $content, 'error' => ''];
    }
}
?>
