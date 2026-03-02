<?php
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
    // Redirect berdasarkan role
    switch($_SESSION['user_role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'petugas':
            header("Location: petugas/dashboard.php");
            break;
        case 'pimpinan':
            header("Location: admin/laporan.php");
            break;
        default:
            header("Location: login.php");
    }
} else {
    header("Location: login.php");
}
exit();
?>