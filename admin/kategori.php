<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nama = $_POST['nama_kategori'];
    $deskripsi = $_POST['deskripsi'];
    
    $query = "INSERT INTO kategori (nama_kategori, deskripsi) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$nama, $deskripsi]);
    
    header("Location: kategori.php?msg=added");
    exit();
}

// Handle Edit Kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = $_POST['id_kategori'];
    $nama = $_POST['nama_kategori'];
    $deskripsi = $_POST['deskripsi'];
    
    $query = "UPDATE kategori SET nama_kategori = ?, deskripsi = ? WHERE id_kategori = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$nama, $deskripsi, $id]);
    
    header("Location: kategori.php?msg=updated");
    exit();
}

// Handle Hapus Kategori
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek apakah kategori masih digunakan
    $cek = $conn->prepare("SELECT COUNT(*) FROM aset WHERE id_kategori = ?");
    $cek->execute([$id]);
    $jumlah = $cek->fetchColumn();
    
    if ($jumlah > 0) {
        header("Location: kategori.php?msg=used");
    } else {
        $query = "DELETE FROM kategori WHERE id_kategori = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);
        header("Location: kategori.php?msg=deleted");
    }
    exit();
}

// Ambil data kategori
$kategoriList = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manajemen Kategori Aset</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-plus"></i> Tambah Kategori
        </button>
    </div>
    
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] == 'added'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Kategori berhasil ditambahkan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Kategori berhasil diupdate!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Kategori berhasil dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'used'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Kategori tidak dapat dihapus karena masih digunakan oleh aset!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tabelKategori">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Jumlah Aset</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach($kategoriList as $kategori):
                            // Hitung jumlah aset per kategori
                            $hitung = $conn->prepare("SELECT COUNT(*) FROM aset WHERE id_kategori = ?");
                            $hitung->execute([$kategori['id_kategori']]);
                            $jumlahAset = $hitung->fetchColumn();
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($kategori['nama_kategori']) ?></td>
                            <td><?= htmlspecialchars($kategori['deskripsi'] ?? '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-info"><?= $jumlahAset ?> Aset</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="editKategori(<?= $kategori['id_kategori'] ?>, '<?= htmlspecialchars($kategori['nama_kategori']) ?>', '<?= htmlspecialchars($kategori['deskripsi'] ?? '') ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?hapus=<?= $kategori['id_kategori'] ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Yakin ingin menghapus kategori ini?')">
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

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"></textarea>
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

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id_kategori" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
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
    $('#tabelKategori').DataTable();
});

function editKategori(id, nama, deskripsi) {
    $('#edit_id').val(id);
    $('#edit_nama').val(nama);
    $('#edit_deskripsi').val(deskripsi);
    $('#modalEdit').modal('show');
}
</script>

<?php include '../includes/footer.php'; ?>