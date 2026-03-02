<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle Tambah Lokasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nama = $_POST['nama_lokasi'];
    $gedung = $_POST['gedung'];
    $penanggung_jawab = $_POST['penanggung_jawab'];
    
    $query = "INSERT INTO lokasi (nama_lokasi, gedung, penanggung_jawab) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$nama, $gedung, $penanggung_jawab]);
    
    header("Location: lokasi.php?msg=added");
    exit();
}

// Handle Edit Lokasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = $_POST['id_lokasi'];
    $nama = $_POST['nama_lokasi'];
    $gedung = $_POST['gedung'];
    $penanggung_jawab = $_POST['penanggung_jawab'];
    
    $query = "UPDATE lokasi SET nama_lokasi = ?, gedung = ?, penanggung_jawab = ? WHERE id_lokasi = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$nama, $gedung, $penanggung_jawab, $id]);
    
    header("Location: lokasi.php?msg=updated");
    exit();
}

// Handle Hapus Lokasi
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek apakah lokasi masih digunakan
    $cek = $conn->prepare("SELECT COUNT(*) FROM aset WHERE id_lokasi = ?");
    $cek->execute([$id]);
    $jumlah = $cek->fetchColumn();
    
    if ($jumlah > 0) {
        header("Location: lokasi.php?msg=used");
    } else {
        $query = "DELETE FROM lokasi WHERE id_lokasi = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);
        header("Location: lokasi.php?msg=deleted");
    }
    exit();
}

// Ambil data lokasi
$lokasiList = $conn->query("SELECT * FROM lokasi ORDER BY nama_lokasi")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manajemen Lokasi</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-plus"></i> Tambah Lokasi
        </button>
    </div>
    
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] == 'added'): ?>
            <div class="alert alert-success">Lokasi berhasil ditambahkan!</div>
        <?php elseif($_GET['msg'] == 'updated'): ?>
            <div class="alert alert-success">Lokasi berhasil diupdate!</div>
        <?php elseif($_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success">Lokasi berhasil dihapus!</div>
        <?php elseif($_GET['msg'] == 'used'): ?>
            <div class="alert alert-danger">Lokasi tidak dapat dihapus karena masih digunakan oleh aset!</div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tabelLokasi">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Nama Lokasi</th>
                            <th>Gedung</th>
                            <th>Penanggung Jawab</th>
                            <th>Jumlah Aset</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach($lokasiList as $lokasi):
                            // Hitung jumlah aset per lokasi
                            $hitung = $conn->prepare("SELECT COUNT(*) FROM aset WHERE id_lokasi = ?");
                            $hitung->execute([$lokasi['id_lokasi']]);
                            $jumlahAset = $hitung->fetchColumn();
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($lokasi['nama_lokasi']) ?></td>
                            <td><?= htmlspecialchars($lokasi['gedung'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($lokasi['penanggung_jawab'] ?? '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-info"><?= $jumlahAset ?> Aset</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="editLokasi(<?= $lokasi['id_lokasi'] ?>, '<?= htmlspecialchars($lokasi['nama_lokasi']) ?>', '<?= htmlspecialchars($lokasi['gedung'] ?? '') ?>', '<?= htmlspecialchars($lokasi['penanggung_jawab'] ?? '') ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?hapus=<?= $lokasi['id_lokasi'] ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Yakin ingin menghapus lokasi ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Lokasi -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Lokasi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lokasi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gedung</label>
                        <input type="text" name="gedung" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penanggung Jawab</label>
                        <input type="text" name="penanggung_jawab" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Lokasi -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Lokasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id_lokasi" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lokasi" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gedung</label>
                        <input type="text" name="gedung" id="edit_gedung" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penanggung Jawab</label>
                        <input type="text" name="penanggung_jawab" id="edit_pj" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelLokasi').DataTable();
});

function editLokasi(id, nama, gedung, pj) {
    $('#edit_id').val(id);
    $('#edit_nama').val(nama);
    $('#edit_gedung').val(gedung);
    $('#edit_pj').val(pj);
    $('#modalEdit').modal('show');
}
</script>

<?php include '../includes/footer.php'; ?>