<?php
session_start();

// URL Website
define('BASE_URL', 'http://localhost/inventaris-teknik-elektro');

// Path folder
define('ROOT_PATH', dirname(__DIR__) . '/');
define('QR_PATH', ROOT_PATH . 'qrcodes/');
define('UPLOAD_PATH', ROOT_PATH . 'assets/uploads/aset/');

// Fungsi untuk cek login
function isLogin() {
    return isset($_SESSION['user_id']);
}

// Fungsi untuk cek role
function isAdmin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin');
}

function isPetugas() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'petugas');
}

function isPimpinan() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'pimpinan');
}

// Redirect jika tidak punya akses
function redirectIfNotLogin() {
    if (!isLogin()) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

// Include database
require_once 'database.php';
?>