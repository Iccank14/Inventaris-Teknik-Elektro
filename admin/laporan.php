<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPimpinan()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Filter
$tahun = $_GET['tahun'] ?? date('Y');
$lokasi = $_GET['lokasi'] ?? '';

// Query untuk laporan
$query = "
    SELECT a.*, k.nama_kategori, l.nama_lokasi 
    FROM aset a 
    LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
    LEFT JOIN lokasi l ON a.id_lokasi = l.id_lokasi 
    WHERE 1=1
";

$params = [];
if ($lokasi) {
    $query .= " AND a.id_lokasi = ?";
    $params[] = $lokasi;
}

if ($tahun) {
    $query .= " AND YEAR(a.tgl_perolehan) = ?";
    $params[] = $tahun;
}

$query .= " ORDER BY a.kode_aset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$asetList = $stmt->fetchAll();

// Statistik
$totalAset = count($asetList);
$totalNilai = array_sum(array_column($asetList, 'harga_perolehan'));
$asetBaik = count(array_filter($asetList, fn($a) => $a['kondisi'] == 'Baik'));
$asetRusak = count(array_filter($asetList, fn($a) => strpos($a['kondisi'], 'Rusak') !== false));

// Ambil data lokasi untuk filter
$lokasiList = $conn->query("SELECT * FROM lokasi ORDER BY nama_lokasi")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Laporan Inventaris Aset</h2>
    
    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <label>Tahun Perolehan</label>
                    <select name="tahun" class="form-control">
                        <option value="">Semua Tahun</option>
                        <?php for($y = date('Y'); $y >= 2015; $y--): ?>
                        <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Lokasi</label>
                    <select name="lokasi" class="form-control">
                        <option value="">Semua Lokasi</option>
                        <?php foreach($lokasiList as $l): ?>
                        <option value="<?= $l['id_lokasi'] ?>" <?= $lokasi == $l['id_lokasi'] ? 'selected' : '' ?>>
                            <?= $l['nama_lokasi'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Tampilkan
                        </button>
                        <a href="laporan.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <label>&nbsp;</label>
                    <div>
                        <button onclick="window.print()" class="btn btn-success">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Ringkasan -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Aset</h6>
                    <h3><?= $totalAset ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Nilai Aset</h6>
                    <h5>Rp <?= number_format($totalNilai, 0, ',', '.') ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Aset Baik</h6>
                    <h3><?= $asetBaik ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Aset Rusak</h6>
                    <h3><?= $asetRusak ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabel Laporan -->
    <div class="card">
        <div class="card-header">
            <h5>Detail Aset</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="tabelLaporan">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Aset</th>
                            <th>Nama Aset</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Merk</th>
                            <th>Tgl Perolehan</th>
                            <th>Kondisi</th>
                            <th>Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach($asetList as $aset): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $aset['kode_aset'] ?></td>
                            <td><?= $aset['nama_aset'] ?></td>
                            <td><?= $aset['nama_kategori'] ?? '-' ?></td>
                            <td><?= $aset['nama_lokasi'] ?? '-' ?></td>
                            <td><?= $aset['merk'] ?? '-' ?></td>
                            <td><?= $aset['tgl_perolehan'] ? date('d/m/Y', strtotime($aset['tgl_perolehan'])) : '-' ?></td>
                            <td>
                                <span class="badge bg-<?= $aset['kondisi'] == 'Baik' ? 'success' : 'warning' ?>">
                                    <?= $aset['kondisi'] ?>
                                </span>
                            </td>
                            <td class="text-end">Rp <?= number_format($aset['harga_perolehan'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="8" class="text-end">Total:</th>
                            <th class="text-end">Rp <?= number_format($totalNilai, 0, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelLaporan').DataTable({
        pageLength: 25,
        order: [[1, 'asc']]
    });
});
</script>

<style>
@media print {
    .navbar, .sidebar, .no-print, .dataTables_filter, .dataTables_paginate, .card-header button {
        display: none !important;
    }
    .col-md-9 {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
    .table {
        font-size: 10px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>