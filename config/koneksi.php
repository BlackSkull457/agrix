<?php

$databaseUrl = getenv('MYSQL_URL');
$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$database = getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'agrix';
$port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306;

if ($databaseUrl) {
    $parsedUrl = parse_url($databaseUrl);
    if ($parsedUrl !== false) {
        $host = $parsedUrl['host'] ?? $host;
        $user = $parsedUrl['user'] ?? $user;
        $password = $parsedUrl['pass'] ?? $password;
        $database = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : $database;
        $port = $parsedUrl['port'] ?? $port;
    }
}

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}
?>