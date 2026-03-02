<?php
require_once '../config/config.php';
redirectIfNotLogin();

$db = new Database();
$conn = $db->getConnection();

// Hitung statistik
$totalAset = $conn->query("SELECT COUNT(*) FROM aset")->fetchColumn();
$totalKategori = $conn->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
$totalLokasi = $conn->query("SELECT COUNT(*) FROM lokasi")->fetchColumn();
$asetBaik = $conn->query("SELECT COUNT(*) FROM aset WHERE kondisi = 'Baik'")->fetchColumn();
$asetRusak = $conn->query("SELECT COUNT(*) FROM aset WHERE kondisi LIKE 'Rusak%'")->fetchColumn();

// Ambil data aset per kategori
$kategoriData = $conn->query("
    SELECT k.nama_kategori, COUNT(a.id_aset) as jumlah 
    FROM kategori k 
    LEFT JOIN aset a ON k.id_kategori = a.id_kategori 
    GROUP BY k.id_kategori
")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Dashboard Inventaris</h2>
    
    <!-- Statistik Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Aset</h5>
                    <h2><?= $totalAset ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Aset Baik</h5>
                    <h2><?= $asetBaik ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Aset Rusak</h5>
                    <h2><?= $asetRusak ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Kategori</h5>
                    <h2><?= $totalKategori ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Grafik Kategori -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Distribusi Aset per Kategori</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartKategori"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Aset Terbaru -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Aset Terbaru</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Aset</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $asetTerbaru = $conn->query("
                                SELECT a.*, l.nama_lokasi 
                                FROM aset a 
                                LEFT JOIN lokasi l ON a.id_lokasi = l.id_lokasi 
                                ORDER BY a.created_at DESC 
                                LIMIT 5
                            ")->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach($asetTerbaru as $aset):
                            ?>
                            <tr>
                                <td><?= $aset['kode_aset'] ?></td>
                                <td><?= $aset['nama_aset'] ?></td>
                                <td><?= $aset['nama_lokasi'] ?? '-' ?></td>
                                <td>
                                    <span class="badge bg-<?= $aset['kondisi'] == 'Baik' ? 'success' : 'warning' ?>">
                                        <?= $aset['kondisi'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart untuk distribusi kategori
var ctx = document.getElementById('chartKategori').getContext('2d');
var chart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: [<?php foreach($kategoriData as $k) echo "'" . $k['nama_kategori'] . "'," ?>],
        datasets: [{
            data: [<?php foreach($kategoriData as $k) echo $k['jumlah'] . "," ?>],
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
        }]
    }
});
</script>

<?php include '../includes/footer.php'; ?>