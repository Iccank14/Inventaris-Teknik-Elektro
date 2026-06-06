<?php
// File: includes/sidebar.php
?>
<!-- Navbar toggle untuk mobile -->
<nav class="navbar navbar-expand-md navbar-light bg-light d-md-none">
    <div class="container-fluid">
        <span class="navbar-brand">Menu</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<!-- Sidebar untuk layar sedang ke atas -->
<div class="col-md-3 col-lg-2 px-0 bg-light sidebar collapse d-md-block" id="sidebarMenu">
    <div class="list-group list-group-flush">
        <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="list-group-item list-group-item-action">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        <?php elseif (isPetugas()): ?>
            <a href="<?= BASE_URL ?>/petugas/dashboard.php" class="list-group-item list-group-item-action">
                <i class="fas fa-tachometer-alt"></i> Dashboard Petugas
            </a>
        <?php elseif (isPimpinan()): ?>
            <a href="<?= BASE_URL ?>/admin/laporan.php" class="list-group-item list-group-item-action">
                <i class="fas fa-chart-bar"></i> Laporan
            </a>
        <?php endif; ?>

        <?php if (isAdmin() || isPetugas()): ?>
            <a href="<?= BASE_URL ?>/admin/aset.php" class="list-group-item list-group-item-action">
                <i class="fas fa-box"></i> Data Aset
            </a>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>/admin/aset_tambah.php" class="list-group-item list-group-item-action">
                <i class="fas fa-plus-circle"></i> Tambah Aset
            </a>
            <a href="<?= BASE_URL ?>/admin/kategori.php" class="list-group-item list-group-item-action">
                <i class="fas fa-tags"></i> Kategori
            </a>
            <a href="<?= BASE_URL ?>/admin/lokasi.php" class="list-group-item list-group-item-action">
                <i class="fas fa-map-marker-alt"></i> Lokasi
            </a>
            <a href="<?= BASE_URL ?>/admin/cetak_label.php" class="list-group-item list-group-item-action">
                <i class="fas fa-print"></i> Cetak Label QR
            </a>
            <a href="<?= BASE_URL ?>/admin/laporan.php" class="list-group-item list-group-item-action">
                <i class="fas fa-chart-bar"></i> Laporan
            </a>
            <a href="<?= BASE_URL ?>/admin/user.php" class="list-group-item list-group-item-action">
                <i class="fas fa-users"></i> Manajemen User
            </a>
        <?php endif; ?>

        <?php if (isPetugas()): ?>
            <a href="<?= BASE_URL ?>/petugas/scan.php" class="list-group-item list-group-item-action">
                <i class="fas fa-qrcode"></i> Scan QR Code
            </a>
            <a href="<?= BASE_URL ?>/petugas/cetak_qr.php" class="list-group-item list-group-item-action">
                <i class="fas fa-print"></i> Cetak QR
            </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>