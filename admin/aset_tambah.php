<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

require_once '../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$db = new Database();
$conn = $db->getConnection();

// Ambil data kategori dan lokasi untuk dropdown
$kategoriList = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$lokasiList = $conn->query("SELECT * FROM lokasi ORDER BY nama_lokasi")->fetchAll();

// Debug: Cek apakah data lokasi ada
if (empty($lokasiList)) {
    // Jika tidak ada data lokasi, buat data default
    $conn->query("INSERT INTO lokasi (nama_lokasi, gedung, penanggung_jawab) VALUES 
                  ('Lab. Komputer Dasar', 'Gedung Teknik A', 'Dr. Andi'),
                  ('Lab. Elektronika', 'Gedung Teknik B', 'Ir. Budi'),
                  ('Lab. Telekomunikasi', 'Gedung Teknik B', 'Dr. Cici'),
                  ('Ruang Dosen', 'Gedung Teknik A', 'Sekretariat')");
    
    // Ambil ulang data lokasi
    $lokasiList = $conn->query("SELECT * FROM lokasi ORDER BY nama_lokasi")->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate kode aset unik
    $tahun = date('Y');
    $bulan = date('m');
    $query = $conn->query("SELECT COUNT(*) as total FROM aset WHERE YEAR(created_at) = $tahun");
    $result = $query->fetch();
    $total = ($result['total'] ?? 0) + 1;
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
    
    // Ambil nilai No SPK dan Jumlah Unit
    $no_spk = $_POST['no_spk'] ?? '';
    $jumlah_unit = isset($_POST['jumlah_unit']) ? (int)$_POST['jumlah_unit'] : 1;
    if ($jumlah_unit < 1) $jumlah_unit = 1;
    
    // Insert ke database (termasuk no_spk dan jumlah_unit)
    $query = "INSERT INTO aset (kode_aset, qr_code_string, nama_aset, merk, spesifikasi, 
              id_kategori, id_lokasi, kondisi, harga_perolehan, tgl_perolehan, sumber_dana, 
              foto, keterangan, no_spk, jumlah_unit) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
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
        $_POST['keterangan'],
        $no_spk,
        $jumlah_unit
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
                            <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                        <select name="id_lokasi" class="form-control" required>
                            <option value="">-- Pilih Lokasi --</option>
                            <?php 
                            $jumlahLokasi = count($lokasiList);
                            ?>
                            <?php if($jumlahLokasi > 0): ?>
                                <?php foreach($lokasiList as $l): ?>
                                <option value="<?= $l['id_lokasi'] ?>">
                                    <?= htmlspecialchars($l['nama_lokasi']) ?> 
                                    <?= $l['gedung'] ? '(' . htmlspecialchars($l['gedung']) . ')' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak ada data lokasi</option>
                            <?php endif; ?>
                        </select>
                        <?php if($jumlahLokasi == 0): ?>
                            <small class="text-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Data lokasi tidak ditemukan. 
                                <a href="lokasi.php" class="text-danger">Tambah lokasi sekarang</a>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Spesifikasi</label>
                        <textarea name="spesifikasi" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kondisi</label>
                            <select name="kondisi" class="form-control">
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                                <option value="Hilang">Hilang</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Harga Perolehan (Rp)</label>
                            <input type="number" name="harga_perolehan" class="form-control" 
                                   step="0.01" min="0" placeholder="0.00">
                            <small class="text-muted">Gunakan titik untuk desimal</small>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal Perolehan</label>
                            <input type="date" name="tgl_perolehan" class="form-control">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Jumlah Unit</label>
                            <input type="number" name="jumlah_unit" class="form-control" 
                                   min="1" value="1" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">No. SPK</label>
                        <input type="text" name="no_spk" class="form-control" placeholder="Contoh: SPK-001/2025">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sumber Dana</label>
                        <input type="text" name="sumber_dana" class="form-control">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Foto Aset</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <small class="text-muted">Format: JPG, PNG. Maks: 2MB</small>
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

<script>
$(document).ready(function() {
    console.log('Jumlah opsi lokasi: ' + $('select[name="id_lokasi"] option').length);
    if ($('select[name="id_lokasi"] option').length <= 1) {
        Swal.fire({
            icon: 'warning',
            title: 'Data Lokasi Kosong',
            text: 'Belum ada data lokasi. Silakan tambah lokasi terlebih dahulu.',
            showConfirmButton: true,
            confirmButtonText: 'Tambah Lokasi',
            showCancelButton: true,
            cancelButtonText: 'Tutup'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'lokasi.php';
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>