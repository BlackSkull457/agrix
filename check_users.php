<?php
include 'config/koneksi.php';
$result = mysqli_query($conn, 'SHOW COLUMNS FROM users');
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
echo '\nSample rows:\n';
$result = mysqli_query($conn, 'SELECT user_id, username, password, role FROM users LIMIT 5');
while ($row = mysqli_fetch_assoc($result)) {
    echo implode(' | ', $row) . PHP_EOL;
}
?>