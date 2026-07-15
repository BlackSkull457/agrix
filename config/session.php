<?php
function startAgrixSession($loginPath) {
    $lifetime = 28800;

    ini_set('session.gc_maxlifetime', $lifetime);
    session_set_cookie_params($lifetime, '/');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: $loginPath");
        exit;
    }

    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $lifetime) {
        session_unset();
        session_destroy();
        header("Location: $loginPath");
        exit;
    }

    $_SESSION['last_activity'] = time();
}
