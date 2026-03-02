<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Ambil ID aset dari URL
$id = $_GET['id'] ?? 0;

// Ambil data aset
$query = "SELECT * FROM aset WHERE id_aset = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$aset = $stmt->fetch();

if (!$aset) {
    header("Location: aset.php?msg=notfound");
    exit();
}

// Ambil data kategori dan lokasi
$kategoriList = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$lokasiList = $conn->query("SELECT * FROM lokasi ORDER BY nama_lokasi")->fetchAll();

// Proses update aset
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_aset = $_POST['nama_aset'];
    $merk = $_POST['merk'];
    $spesifikasi = $_POST['spesifikasi'];
    $id_kategori = $_POST['id_kategori'] ?: null;
    $id_lokasi = $_POST['id_lokasi'] ?: null;
    $kondisi = $_POST['kondisi'];
    $harga_perolehan = $_POST['harga_perolehan'] ?: 0;
    $tgl_perolehan = $_POST['tgl_perolehan'] ?: null;
    $sumber_dana = $_POST['sumber_dana'];
    $keterangan = $_POST['keterangan'];
    
    // Upload foto baru jika ada
    $fotoPath = $aset['foto'];
    if ($_FILES['foto']['name']) {
        $targetDir = "../assets/uploads/aset/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Hapus foto lama
        if ($aset['foto'] && file_exists("../" . $aset['foto'])) {
            unlink("../" . $aset['foto']);
        }
        
        $fileName = time() . '_' . basename($_FILES['foto']['name']);
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFilePath)) {
            $fotoPath = 'assets/uploads/aset/' . $fileName;
        }
    }
    
    // Update database
    $query = "UPDATE aset SET 
              nama_aset = ?, merk = ?, spesifikasi = ?, 
              id_kategori = ?, id_lokasi = ?, kondisi = ?,
              harga_perolehan = ?, tgl_perolehan = ?, sumber_dana = ?,
              foto = ?, keterangan = ? 
              WHERE id_aset = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $nama_aset, $merk, $spesifikasi,
        $id_kategori, $id_lokasi, $kondisi,
        $harga_perolehan, $tgl_perolehan, $sumber_dana,
        $fotoPath, $keterangan, $id
    ]);
    
    // Catat riwayat
    $riwayat = $conn->prepare("INSERT INTO riwayat_aset (id_aset, id_user, jenis_kejadian, deskripsi) 
                                VALUES (?, ?, 'Update', 'Data aset diperbarui')");
    $riwayat->execute([$id, $_SESSION['user_id']]);
    
    header("Location: aset.php?msg=updated");
    exit();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Edit Aset</h2>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kode Aset</label>
                        <input type="text" class="form-control" value="<?= $aset['kode_aset'] ?>" readonly>
                        <small class="text-muted">Kode aset tidak dapat diubah</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Aset <span class="text-danger">*</span></label>
                        <input type="text" name="nama_aset" class="form-control" 
                               value="<?= htmlspecialchars($aset['nama_aset']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Merk</label>
                        <input type="text" name="merk" class="form-control" 
                               value="<?= htmlspecialchars($aset['merk'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="id_kategori" class="form-control">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategoriList as $k): ?>
                            <option value="<?= $k['id_kategori'] ?>" 
                                <?= $aset['id_kategori'] == $k['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kategori']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Lokasi</label>
                        <select name="id_lokasi" class="form-control">
                            <option value="">-- Pilih Lokasi --</option>
                            <?php foreach($lokasiList as $l): ?>
                            <option value="<?= $l['id_lokasi'] ?>" 
                                <?= $aset['id_lokasi'] == $l['id_lokasi'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['nama_lokasi']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Spesifikasi</label>
                        <textarea name="spesifikasi" class="form-control" rows="3"><?= htmlspecialchars($aset['spesifikasi'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Kondisi</label>
                        <select name="kondisi" class="form-control">
                            <option value="Baik" <?= $aset['kondisi'] == 'Baik' ? 'selected' : '' ?>>Baik</option>
                            <option value="Rusak Ringan" <?= $aset['kondisi'] == 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                            <option value="Rusak Berat" <?= $aset['kondisi'] == 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
                            <option value="Hilang" <?= $aset['kondisi'] == 'Hilang' ? 'selected' : '' ?>>Hilang</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Harga Perolehan</label>
                        <input type="number" name="harga_perolehan" class="form-control" 
                               value="<?= $aset['harga_perolehan'] ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tanggal Perolehan</label>
                        <input type="date" name="tgl_perolehan" class="form-control" 
                               value="<?= $aset['tgl_perolehan'] ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sumber Dana</label>
                        <input type="text" name="sumber_dana" class="form-control" 
                               value="<?= htmlspecialchars($aset['sumber_dana'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Foto Aset</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <?php if($aset['foto']): ?>
                            <small class="text-muted">
                                Foto saat ini: <a href="<?= BASE_URL ?>/<?= $aset['foto'] ?>" target="_blank">Lihat</a>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($aset['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <hr>
                <div class="text-end">
                    <a href="aset.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Update Aset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>