<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

require_once '../vendor/autoload.php'; // Untuk phpqrcode

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$db = new Database();
$conn = $db->getConnection();

// Ambil data kategori dan lokasi untuk dropdown
$kategoriList = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$lokasiList = $conn->query("SELECT * FROM lokasi ORDER BY nama_lokasi")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate kode aset unik
    $tahun = date('Y');
    $bulan = date('m');
    $query = $conn->query("SELECT COUNT(*) as total FROM aset WHERE YEAR(created_at) = $tahun");
    $total = $query->fetch()['total'] + 1;
    $kodeAset = "AST-$tahun$bulan-" . str_pad($total, 4, '0', STR_PAD_LEFT);
    
    // Generate string untuk QR Code (unik)
    $qrString = "TEKNIK-ELEKTRO-" . uniqid() . "-" . rand(1000, 9999);
    
    // Upload foto
    $fotoPath = '';
    if ($_FILES['foto']['name']) {
        $targetDir = "../assets/uploads/aset/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['foto']['name']);
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFilePath)) {
            $fotoPath = 'assets/uploads/aset/' . $fileName;
        }
    }
    
    // Insert ke database
    $query = "INSERT INTO aset (kode_aset, qr_code_string, nama_aset, merk, spesifikasi, 
              id_kategori, id_lokasi, kondisi, harga_perolehan, tgl_perolehan, sumber_dana, foto, keterangan) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $kodeAset,
        $qrString,
        $_POST['nama_aset'],
        $_POST['merk'],
        $_POST['spesifikasi'],
        $_POST['id_kategori'] ?: null,
        $_POST['id_lokasi'] ?: null,
        $_POST['kondisi'],
        $_POST['harga_perolehan'] ?: 0,
        $_POST['tgl_perolehan'] ?: null,
        $_POST['sumber_dana'],
        $fotoPath,
        $_POST['keterangan']
    ]);
    
    $idAset = $conn->lastInsertId();
    
    // Generate QR Code
    $qrCode = new QrCode(BASE_URL . '/public/scan.php?kode=' . $qrString);
    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    
    // Simpan QR Code sebagai file
    $qrDir = "../qrcodes/";
    if (!file_exists($qrDir)) {
        mkdir($qrDir, 0777, true);
    }
    $result->saveToFile($qrDir . $qrString . '.png');
    
    // Catat riwayat
    $riwayat = $conn->prepare("INSERT INTO riwayat_aset (id_aset, id_user, jenis_kejadian, deskripsi) VALUES (?, ?, 'Tambah', ?)");
    $riwayat->execute([$idAset, $_SESSION['user_id'], 'Aset baru ditambahkan dengan kode: ' . $kodeAset]);
    
    header("Location: aset.php?msg=added");
    exit();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Tambah Aset Baru</h2>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Aset <span class="text-danger">*</span></label>
                        <input type="text" name="nama_aset" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Merk</label>
                        <input type="text" name="merk" class="form-control">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="id_kategori" class="form-control">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategoriList as $k): ?>
                            <option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lokasi</label>
                        <select name="id_lokasi" class="form-control">
                            <option value="">-- Pilih Lokasi --</option>
                            <?php foreach($lokasiList as $l): ?>
                            <option value="<?= $l['id_lokasi'] ?>"><?= $l['nama_lokasi'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Spesifikasi</label>
                        <textarea name="spesifikasi" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Kondisi</label>
                        <select name="kondisi" class="form-control">
                            <option value="Baik">Baik</option>
                            <option value="Rusak Ringan">Rusak Ringan</option>
                            <option value="Rusak Berat">Rusak Berat</option>
                            <option value="Hilang">Hilang</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Harga Perolehan</label>
                        <input type="number" name="harga_perolehan" class="form-control">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal Perolehan</label>
                        <input type="date" name="tgl_perolehan" class="form-control">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sumber Dana</label>
                        <input type="text" name="sumber_dana" class="form-control">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Foto Aset</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                
                <hr>
                <div class="text-end">
                    <a href="aset.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Aset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>