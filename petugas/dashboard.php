<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isPetugas() && !isAdmin()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$totalAset = $conn->query("SELECT COUNT(*) FROM aset")->fetchColumn();
$asetBaik = $conn->query("SELECT COUNT(*) FROM aset WHERE kondisi = 'Baik'")->fetchColumn();
$asetRusak = $conn->query("SELECT COUNT(*) FROM aset WHERE kondisi LIKE 'Rusak%'")->fetchColumn();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Dashboard Petugas</h2>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h6>Total Aset</h6>
                    <h2><?= $totalAset ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h6>Aset Baik</h6>
                    <h2><?= $asetBaik ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h6>Aset Rusak</h6>
                    <h2><?= $asetRusak ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Aksi Cepat</div>
                <div class="card-body">
                    <a href="scan.php" class="btn btn-primary">Scan QR Code</a>
                    <a href="../admin/aset.php" class="btn btn-success">Lihat Aset</a>
                    <a href="cetak_qr.php" class="btn btn-info">Cetak QR</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>