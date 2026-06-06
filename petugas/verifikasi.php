<?php
require_once '../config/config.php';
redirectIfNotLogin();

// Hanya admin yang boleh mengakses halaman verifikasi
if (!isAdmin()) {
    header("Location: " . BASE_URL . "/petugas/dashboard.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$kode = $_GET['kode'] ?? '';
$aset = null;

if ($kode) {
    // Cari aset berdasarkan qr_code_string
    $query = "SELECT a.*, l.nama_lokasi FROM aset a 
              LEFT JOIN lokasi l ON a.id_lokasi = l.id_lokasi 
              WHERE a.qr_code_string = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$kode]);
    $aset = $stmt->fetch();
}

// Proses verifikasi via scan
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
                                VALUES (?, ?, 'Verifikasi', 'Aset diverifikasi pada stock opname')");
    $riwayat->execute([$idAset, $_SESSION['user_id']]);
    
    header("Location: verifikasi.php?success=1");
    exit();
}

// Proses verifikasi manual dari daftar (tanpa scan)
if (isset($_GET['verifikasi_manual']) && isset($_GET['id'])) {
    $idAset = $_GET['id'];
    
    // Cek apakah sudah diverifikasi hari ini
    $cek = $conn->prepare("SELECT COUNT(*) FROM verifikasi_opname WHERE id_aset = ? AND tgl_verifikasi = CURDATE()");
    $cek->execute([$idAset]);
    if ($cek->fetchColumn() == 0) {
        // Simpan verifikasi
        $query = "INSERT INTO verifikasi_opname (id_aset, id_user, tgl_verifikasi, jam_verifikasi, catatan) 
                  VALUES (?, ?, CURDATE(), CURTIME(), 'Verifikasi manual dari daftar')";
        $stmt = $conn->prepare($query);
        $stmt->execute([$idAset, $_SESSION['user_id']]);
        
        // Catat riwayat
        $riwayat = $conn->prepare("INSERT INTO riwayat_aset (id_aset, id_user, jenis_kejadian, deskripsi) 
                                    VALUES (?, ?, 'Verifikasi', 'Aset diverifikasi manual')");
        $riwayat->execute([$idAset, $_SESSION['user_id']]);
    }
    
    header("Location: verifikasi.php?success=1");
    exit();
}

// Ambil daftar aset yang BELUM diverifikasi hari ini
$queryBelum = "
    SELECT a.id_aset, a.kode_aset, a.nama_aset, a.kondisi, l.nama_lokasi
    FROM aset a
    LEFT JOIN lokasi l ON a.id_lokasi = l.id_lokasi
    WHERE a.id_aset NOT IN (
        SELECT id_aset FROM verifikasi_opname WHERE tgl_verifikasi = CURDATE()
    )
    ORDER BY a.kode_aset
";
$belumVerif = $conn->query($queryBelum)->fetchAll(PDO::FETCH_ASSOC);

// Ambil riwayat verifikasi hari ini
$verifToday = $conn->prepare("
    SELECT v.*, a.nama_aset, a.kode_aset, u.nama_lengkap as petugas
    FROM verifikasi_opname v
    JOIN aset a ON v.id_aset = a.id_aset
    JOIN users u ON v.id_user = u.id_user
    WHERE v.tgl_verifikasi = CURDATE()
    ORDER BY v.jam_verifikasi DESC
");
$verifToday->execute();
$verifList = $verifToday->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Verifikasi Aset (Stock Opname) - Khusus Admin</h2>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Aset berhasil diverifikasi!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Kolom Kiri: Scan QR -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Scan QR Code</h5>
                </div>
                <div class="card-body text-center">
                    <div id="reader" style="width: 100%;"></div>
                    <div class="mt-3">
                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Arahkan kamera ke QR Code aset untuk memindai
                        </p>
                    </div>
                    <div id="scanResult" class="mt-3"></div>
                </div>
            </div>
            
            <?php if($aset): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5>Hasil Scan</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Kode Aset</th><td><?= $aset['kode_aset'] ?></td></tr>
                        <tr><th>Nama Aset</th><td><?= $aset['nama_aset'] ?></td></tr>
                        <tr><th>Lokasi</th><td><?= $aset['nama_lokasi'] ?? '-' ?></td></tr>
                        <tr><th>Kondisi</th><td><?= $aset['kondisi'] ?></td></tr>
                    </table>
                    
                    <?php
                    $cekVerif = $conn->prepare("SELECT * FROM verifikasi_opname WHERE id_aset = ? AND tgl_verifikasi = CURDATE()");
                    $cekVerif->execute([$aset['id_aset']]);
                    $sudahVerif = $cekVerif->fetch();
                    ?>
                    
                    <?php if($sudahVerif): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-check-circle"></i> Aset ini sudah diverifikasi hari ini.
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="id_aset" value="<?= $aset['id_aset'] ?>">
                            <div class="mb-3">
                                <label>Catatan (opsional)</label>
                                <textarea name="catatan" class="form-control" placeholder="Contoh: kondisi baik"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Verifikasi Aset
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Kolom Kanan: Daftar Aset Belum Diverifikasi -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5>Aset Belum Diverifikasi Hari Ini (<?= count($belumVerif) ?>)</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if(empty($belumVerif)): ?>
                        <p class="text-success">Semua aset sudah diverifikasi hari ini!</p>
                    <?php else: ?>
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Aset</th>
                                    <th>Lokasi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($belumVerif as $aset): ?>
                                <tr>
                                    <td><?= $aset['kode_aset'] ?></td>
                                    <td><?= $aset['nama_aset'] ?></td>
                                    <td><?= $aset['nama_lokasi'] ?? '-' ?></td>
                                    <td>
                                        <a href="?verifikasi_manual=1&id=<?= $aset['id_aset'] ?>" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Verifikasi aset <?= $aset['nama_aset'] ?>?')">
                                            <i class="fas fa-check"></i> Verifikasi
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Riwayat Verifikasi Hari Ini -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5>Riwayat Verifikasi Hari Ini (<?= count($verifList) ?>)</h5>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php if(empty($verifList)): ?>
                        <p class="text-muted">Belum ada verifikasi hari ini.</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Jam</th>
                                    <th>Aset</th>
                                    <th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($verifList as $v): ?>
                                <tr>
                                    <td><?= $v['jam_verifikasi'] ?></td>
                                    <td><?= $v['nama_aset'] ?></td>
                                    <td><?= $v['petugas'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Library QR Scanner -->
<script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
<script>
const html5QrCode = new Html5Qrcode("reader");

const qrCodeSuccessCallback = (decodedText, decodedResult) => {
    html5QrCode.stop();
    
    // Ekstrak kode dari URL jika perlu
    let kode = decodedText;
    if (decodedText.includes('kode=')) {
        const urlParams = new URLSearchParams(decodedText.split('?')[1]);
        kode = urlParams.get('kode');
    }
    
    window.location.href = `verifikasi.php?kode=${kode}`;
};

const config = { fps: 10, qrbox: { width: 250, height: 250 } };

function mulaiScan() {
    html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback, (error) => {});
}

window.onload = function() {
    if (html5QrCode) {
        mulaiScan();
    }
};

window.onbeforeunload = function() {
    if (html5QrCode) {
        html5QrCode.stop();
    }
};
</script>

<?php include '../includes/footer.php'; ?>