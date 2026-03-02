<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isPetugas() && !isAdmin()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Ambil data aset yang belum punya QR atau ingin dicetak ulang
$asetList = $conn->query("
    SELECT a.*, k.nama_kategori 
    FROM aset a 
    LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
    ORDER BY a.created_at DESC
")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Cetak Label QR Code</h2>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Pilih aset untuk mencetak label QR Code. Label dapat dicetak pada kertas stiker.
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="cetak_qr_proses.php" target="_blank">
                <div class="mb-3">
                    <label class="form-label">Ukuran Label</label>
                    <select name="ukuran" class="form-control" style="width: 200px;">
                        <option value="small">Kecil (2x2 cm)</option>
                        <option value="medium" selected>Sedang (3x3 cm)</option>
                        <option value="large">Besar (4x4 cm)</option>
                    </select>
                </div>
                
                <table class="table table-bordered table-striped" id="tabelAset">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>Kode Aset</th>
                            <th>Nama Aset</th>
                            <th>Kategori</th>
                            <th>Preview QR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($asetList as $aset): ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="aset_ids[]" value="<?= $aset['id_aset'] ?>" 
                                       class="aset-check">
                            </td>
                            <td><?= $aset['kode_aset'] ?></td>
                            <td><?= $aset['nama_aset'] ?></td>
                            <td><?= $aset['nama_kategori'] ?? '-' ?></td>
                            <td class="text-center">
                                <?php if(file_exists('../qrcodes/' . $aset['qr_code_string'] . '.png')): ?>
                                    <img src="<?= BASE_URL ?>/qrcodes/<?= $aset['qr_code_string'] ?>.png" 
                                         width="50" height="50" alt="QR">
                                <?php else: ?>
                                    <span class="badge bg-danger">File QR tidak ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-print"></i> Cetak Label QR Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelAset').DataTable();
    
    $('#checkAll').click(function() {
        $('.aset-check').prop('checked', this.checked);
    });
});
</script>

<?php include '../includes/footer.php'; ?>