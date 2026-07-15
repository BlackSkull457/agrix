<?php
session_start();
include 'config/koneksi.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query($conn,
        "SELECT * FROM users WHERE username='$username' AND password='$password'"
    );

    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        $_SESSION['user_id'] = $data['user_id'];
        $_SESSION['role'] = $data['role'];

        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Login gagal, periksa username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agri-X</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1>Login Agri-X</h1>
        <p>Masuk untuk mengelola produk dan stok.</p>
        <?php if (!empty($error)): ?>
            <div style="color:#b91c1c; margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" name="login">Masuk</button>
        </form>
        <div class="login-footer">Gunakan akun administrator atau user untuk masuk.</div>
    </div>
</body>
</html>
