<?php
/**
 * Halaman registrasi publik telah dinonaktifkan.
 * Pembuatan akun dilakukan oleh Administrator melalui panel admin.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, arahkan ke dashboard
if (!empty($_SESSION["id_user"])) {
    header("Location: index.php");
    exit;
}

// Redirect ke login dengan pesan
header("Location: login.php?info=register_disabled");
exit;
