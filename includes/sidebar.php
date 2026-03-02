<div class="col-md-3 col-lg-2 px-0 bg-light sidebar">
    <div class="list-group list-group-flush">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="list-group-item list-group-item-action">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        
        <?php if(isAdmin() || isPetugas()): ?>
        <a href="<?= BASE_URL ?>/admin/aset.php" class="list-group-item list-group-item-action">
            <i class="fas fa-box"></i> Data Aset
        </a>
        <a href="<?= BASE_URL ?>/admin/kategori.php" class="list-group-item list-group-item-action">
            <i class="fas fa-tags"></i> Kategori
        </a>
        <a href="<?= BASE_URL ?>/admin/lokasi.php" class="list-group-item list-group-item-action">
            <i class="fas fa-map-marker-alt"></i> Lokasi
        </a>
        <?php endif; ?>
        
        <?php if(isPetugas()): ?>
        <a href="<?= BASE_URL ?>/petugas/scan.php" class="list-group-item list-group-item-action">
            <i class="fas fa-qrcode"></i> Scan QR Code
        </a>
        <a href="<?= BASE_URL ?>/petugas/verifikasi.php" class="list-group-item list-group-item-action">
            <i class="fas fa-check-circle"></i> Verifikasi Aset
        </a>
        <a href="<?= BASE_URL ?>/petugas/cetak_qr.php" class="list-group-item list-group-item-action">
            <i class="fas fa-print"></i> Cetak QR
        </a>
        <?php endif; ?>
        
        <?php if(isAdmin() || isPimpinan()): ?>
        <a href="<?= BASE_URL ?>/admin/laporan.php" class="list-group-item list-group-item-action">
            <i class="fas fa-chart-bar"></i> Laporan
        </a>
        <?php endif; ?>
        
        <?php if(isAdmin()): ?>
        <a href="<?= BASE_URL ?>/admin/user.php" class="list-group-item list-group-item-action">
            <i class="fas fa-users"></i> Manajemen User
        </a>
        <?php endif; ?>
    </div>
</div>