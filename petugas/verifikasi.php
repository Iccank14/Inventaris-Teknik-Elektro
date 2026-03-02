<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isPetugas() && !isAdmin()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$kode = $_GET['kode'] ?? '';
$aset = null;

if ($kode) {
    // Cari aset
    $query = "SELECT a.*, l.nama_lokasi FROM aset a 
              LEFT JOIN lokasi l ON a.id_lokasi = l.id_lokasi 
              WHERE a.qr_code_string = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$kode]);
    $aset = $stmt->fetch();
}

// Proses verifikasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_aset'])) {
    $idAset = $_POST['id_aset'];
    $catatan = $_POST['catatan'] ?? '';
    
    // Simpan verifikasi
    $query = "INSERT INTO verifikasi_opname (id_aset, id_user, tgl_verifikasi, jam_verifikasi, catatan) 
              VALUES (?, ?, CURDATE(), CURTIME(), ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$idAset, $_SESSION['user_id'], $catatan]);
    
    // Catat riwayat
    $riwayat = $conn->prepare("INSERT INTO riwayat_aset (id_aset, id_user, jenis_kejadian, deskripsi) 
                                VALUES (?, ?, 'Verifikasi', ?)");
    $riwayat->execute([$idAset, $_SESSION['user_id'], 'Aset diverifikasi pada stock opname']);
    
    header("Location: verifikasi.php?success=1");
    exit();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Verifikasi Aset (Stock Opname)</h2>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Aset berhasil diverifikasi! 
            <a href="scan.php" class="alert-link">Scan aset berikutnya</a>
        </div>
    <?php endif; ?>
    
    <?php if($aset): ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5>Data Aset Ditemukan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th width="150">Kode Aset</th>
                                <td>: <strong><?= $aset['kode_aset'] ?></strong></td>
                            </tr>
                            <tr>
                                <th>Nama Aset</th>
                                <td>: <?= $aset['nama_aset'] ?></td>
                            </tr>
                            <tr>
                                <th>Merk</th>
                                <td>: <?= $aset['merk'] ?? '-' ?></td>
                            </tr>
                            <tr>
                                <th>Lokasi</th>
                                <td>: <?= $aset['nama_lokasi'] ?? '-' ?></td>
                            </tr>
                            <tr>
                                <th>Kondisi</th>
                                <td>: 
                                    <span class="badge bg-<?= $aset['kondisi'] == 'Baik' ? 'success' : 'warning' ?>">
                                        <?= $aset['kondisi'] ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6 text-center">
                        <?php if($aset['foto']): ?>
                            <img src="<?= BASE_URL ?>/<?= $aset['foto'] ?>" 
                                 alt="Foto" class="img-fluid rounded" style="max-height: 200px;">
                        <?php endif; ?>
                        
                        <!-- Cek apakah sudah diverifikasi hari ini -->
                        <?php
                        $cekVerif = $conn->prepare("
                            SELECT * FROM verifikasi_opname 
                            WHERE id_aset = ? AND tgl_verifikasi = CURDATE()
                        ");
                        $cekVerif->execute([$aset['id_aset']]);
                        $sudahVerif = $cekVerif->fetch();
                        ?>
                        
                        <?php if($sudahVerif): ?>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-check-circle"></i>
                                Aset ini sudah diverifikasi hari ini oleh <?= $_SESSION['user_nama'] ?>
                            </div>
                            <a href="scan.php" class="btn btn-primary">
                                <i class="fas fa-camera"></i> Scan Aset Lain
                            </a>
                        <?php else: ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="id_aset" value="<?= $aset['id_aset'] ?>">
                                <div class="mb-3">
                                    <label>Catatan (opsional)</label>
                                    <textarea name="catatan" class="form-control" 
                                              placeholder="Contoh: kondisi baik, perlu perawatan, dll"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-check"></i> Verifikasi Aset
                                </button>
                                <a href="scan.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            Silakan scan QR Code aset terlebih dahulu di menu <a href="scan.php">Scan QR Code</a>
        </div>
        
        <!-- Tampilkan daftar verifikasi hari ini -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Riwayat Verifikasi Hari Ini</h5>
            </div>
            <div class="card-body">
                <?php
                $verifToday = $conn->prepare("
                    SELECT v.*, a.nama_aset, a.kode_aset, u.nama_lengkap 
                    FROM verifikasi_opname v
                    JOIN aset a ON v.id_aset = a.id_aset
                    JOIN users u ON v.id_user = u.id_user
                    WHERE v.tgl_verifikasi = CURDATE()
                    ORDER BY v.jam_verifikasi DESC
                ");
                $verifToday->execute();
                $verifList = $verifToday->fetchAll();
                ?>
                
                <?php if(count($verifList) > 0): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Kode Aset</th>
                                <th>Nama Aset</th>
                                <th>Petugas</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($verifList as $v): ?>
                            <tr>
                                <td><?= $v['jam_verifikasi'] ?></td>
                                <td><?= $v['kode_aset'] ?></td>
                                <td><?= $v['nama_aset'] ?></td>
                                <td><?= $v['nama_lengkap'] ?></td>
                                <td><?= $v['catatan'] ?: '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="mt-2">
                        <strong>Total terverifikasi hari ini: <?= count($verifList) ?> aset</strong>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Belum ada verifikasi hari ini</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>