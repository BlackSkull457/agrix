<?php
session_start();
include "../config/koneksi.php";

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM produk WHERE produk_id='$id'");

header("Location: index.php");
exit;