<?php
$conn = mysqli_connect("localhost", "root", "", "agrix");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>