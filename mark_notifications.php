<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}
include 'config/koneksi.php';

$userId = (int) $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "UPDATE notifikasi SET status = 1 WHERE status = 0 AND (penjual_id IS NULL OR penjual_id = ?)");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);

echo json_encode(['success' => true]);
?>