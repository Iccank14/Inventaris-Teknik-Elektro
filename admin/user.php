<?php
require_once '../config/config.php';
redirectIfNotLogin();

// Hanya admin yang bisa akses halaman ini
if (!isAdmin()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle Tambah User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Gunakan password_hash di production
    $nama_lengkap = $_POST['nama_lengkap'];
    $nip = $_POST['nip'];
    $role = $_POST['role'];
    $kontak = $_POST['kontak'];
    
    // Validasi username tidak boleh kosong
    if (empty($username) || empty($password) || empty($nama_lengkap)) {
        header("Location: user.php?msg=empty_fields");
        exit();
    }
    
    // Cek username sudah ada atau belum
    $cek = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $cek->execute([$username]);
    if ($cek->fetchColumn() > 0) {
        header("Location: user.php?msg=username_exists");
        exit();
    }
    
    $query = "INSERT INTO users (username, password, nama_lengkap, nip, role, kontak) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$username, $password, $nama_lengkap, $nip, $role, $kontak]);
    
    header("Location: user.php?msg=added");
    exit();
}

// Handle Edit User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = $_POST['id_user'];
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $nip = $_POST['nip'];
    $role = $_POST['role'];
    $kontak = $_POST['kontak'];
    
    // Validasi
    if (empty($username) || empty($nama_lengkap)) {
        header("Location: user.php?msg=empty_fields");
        exit();
    }
    
    // Cek username sudah digunakan user lain
    $cek = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id_user != ?");
    $cek->execute([$username, $id]);
    if ($cek->fetchColumn() > 0) {
        header("Location: user.php?msg=username_exists");
        exit();
    }
    
    // Update password jika diisi
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $query = "UPDATE users SET username = ?, password = ?, nama_lengkap = ?, nip = ?, role = ?, kontak = ? WHERE id_user = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$username, $password, $nama_lengkap, $nip, $role, $kontak, $id]);
    } else {
        $query = "UPDATE users SET username = ?, nama_lengkap = ?, nip = ?, role = ?, kontak = ? WHERE id_user = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$username, $nama_lengkap, $nip, $role, $kontak, $id]);
    }
    
    header("Location: user.php?msg=updated");
    exit();
}

// Handle Hapus User
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek apakah user yang dihapus bukan admin terakhir
    if ($id == $_SESSION['user_id']) {
        header("Location: user.php?msg=cannot_delete_self");
        exit();
    }
    
    // Cek jumlah admin
    $cekAdmin = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $userCek = $conn->prepare("SELECT role FROM users WHERE id_user = ?");
    $userCek->execute([$id]);
    $user = $userCek->fetch();
    
    if ($user && $user['role'] == 'admin' && $cekAdmin <= 1) {
        header("Location: user.php?msg=last_admin");
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id_user = ?");
    $stmt->execute([$id]);
    
    header("Location: user.php?msg=deleted");
    exit();
}

// Handle Reset Password
if (isset($_GET['reset']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $defaultPassword = md5('123456'); // Reset ke password default
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
    $stmt->execute([$defaultPassword, $id]);
    
    header("Location: user.php?msg=password_reset");
    exit();
}

// Handle Aktivasi/Nonaktifkan User
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Jangan nonaktifkan diri sendiri
    if ($id == $_SESSION['user_id']) {
        header("Location: user.php?msg=cannot_disable_self");
        exit();
    }
    
    // Ambil status saat ini (kita tambahkan kolom is_active jika belum ada)
    // Untuk sementara kita tidak menggunakan fitur ini dulu
    header("Location: user.php?msg=not_implemented");
    exit();
}

// Ambil data users
$query = "SELECT * FROM users ORDER BY 
          CASE 
              WHEN role = 'admin' THEN 1
              WHEN role = 'petugas' THEN 2
              ELSE 3
          END, nama_lengkap";
$userList = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> Manajemen Pengguna</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-user-plus"></i> Tambah Pengguna
        </button>
    </div>
    
    <!-- Notifikasi Messages -->
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] == 'added'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Pengguna berhasil ditambahkan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Data pengguna berhasil diperbarui!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Pengguna berhasil dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'password_reset'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Password berhasil direset ke 123456!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'username_exists'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> Username sudah digunakan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'empty_fields'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> Harap isi semua field yang wajib!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'cannot_delete_self'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> Anda tidak dapat menghapus akun sendiri!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'last_admin'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> Tidak dapat menghapus admin terakhir!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Statistik User -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Pengguna</h6>
                            <h2 class="mb-0"><?= count($userList) ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Admin</h6>
                            <h2 class="mb-0">
                                <?= count(array_filter($userList, fn($u) => $u['role'] == 'admin')) ?>
                            </h2>
                        </div>
                        <i class="fas fa-user-shield fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Petugas</h6>
                            <h2 class="mb-0">
                                <?= count(array_filter($userList, fn($u) => $u['role'] == 'petugas')) ?>
                            </h2>
                        </div>
                        <i class="fas fa-user-cog fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Pimpinan</h6>
                            <h2 class="mb-0">
                                <?= count(array_filter($userList, fn($u) => $u['role'] == 'pimpinan')) ?>
                            </h2>
                        </div>
                        <i class="fas fa-user-tie fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabel Users -->
    <div class="card">
        <div class="card-header">
            <h5>Daftar Pengguna</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="tabelUser">
                    <thead class="table-primary">
                        <tr>
                            <th width="50">No</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>NIP</th>
                            <th>Role</th>
                            <th>Kontak</th>
                            <th>Terdaftar</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach($userList as $user): 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                <?php if($user['id_user'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-success">Anda</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($user['nip'] ?? '-') ?></td>
                            <td>
                                <?php 
                                $roleClass = [
                                    'admin' => 'bg-danger',
                                    'petugas' => 'bg-info',
                                    'pimpinan' => 'bg-warning'
                                ];
                                $roleName = [
                                    'admin' => 'Administrator',
                                    'petugas' => 'Petugas',
                                    'pimpinan' => 'Pimpinan'
                                ];
                                ?>
                                <span class="badge <?= $roleClass[$user['role']] ?? 'bg-secondary' ?>">
                                    <i class="fas fa-<?= $user['role'] == 'admin' ? 'shield-alt' : ($user['role'] == 'petugas' ? 'cog' : 'tie') ?>"></i>
                                    <?= $roleName[$user['role']] ?? ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['kontak'] ?? '-') ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($user['created_at'] ?? date('Y-m-d'))) ?>
                                <br>
                                <small class="text-muted">
                                    <?= date('H:i', strtotime($user['created_at'] ?? date('Y-m-d'))) ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-warning" 
                                            onclick="editUser(
                                                <?= $user['id_user'] ?>,
                                                '<?= htmlspecialchars($user['username']) ?>',
                                                '<?= htmlspecialchars($user['nama_lengkap']) ?>',
                                                '<?= htmlspecialchars($user['nip'] ?? '') ?>',
                                                '<?= $user['role'] ?>',
                                                '<?= htmlspecialchars($user['kontak'] ?? '') ?>'
                                            )"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <button class="btn btn-sm btn-info" 
                                            onclick="resetPassword(<?= $user['id_user'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>')"
                                            title="Reset Password">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    
                                    <?php if($user['id_user'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="hapusUser(<?= $user['id_user'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>')"
                                                title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambah" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Tambah Pengguna Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formTambah" onsubmit="return validasiFormTambah()">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="tambah_username" class="form-control" required>
                            <small class="text-muted">Username unik, digunakan untuk login</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="tambah_password" class="form-control" required>
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIP</label>
                            <input type="text" name="nip" class="form-control" placeholder="196501011990031001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-control" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="admin">Administrator (Akses Penuh)</option>
                                <option value="petugas">Petugas (Manajemen Aset)</option>
                                <option value="pimpinan">Pimpinan (Lihat Laporan)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kontak (HP/Email)</label>
                            <input type="text" name="kontak" class="form-control" placeholder="08123456789">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Password akan dienkripsi secara otomatis.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="modalEdit" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit"></i> Edit Pengguna
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEdit" onsubmit="return validasiFormEdit()">
                <input type="hidden" name="id_user" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" id="edit_password" class="form-control">
                            <small class="text-muted">Kosongkan jika tidak ingin mengganti password</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIP</label>
                            <input type="text" name="nip" id="edit_nip" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" id="edit_role" class="form-control" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="admin">Administrator</option>
                                <option value="petugas">Petugas</option>
                                <option value="pimpinan">Pimpinan</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kontak</label>
                            <input type="text" name="kontak" id="edit_kontak" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelUser').DataTable({
        pageLength: 10,
        order: [[4, 'asc'], [2, 'asc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
        }
    });
    
    // Validasi password minimal 6 karakter di form tambah
    $('#tambah_password').on('keyup', function() {
        if ($(this).val().length < 6 && $(this).val().length > 0) {
            $(this).addClass('is-invalid');
            if (!$('#password-error').length) {
                $(this).after('<small id="password-error" class="text-danger">Password minimal 6 karakter</small>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $('#password-error').remove();
        }
    });
});

function editUser(id, username, nama, nip, role, kontak) {
    $('#edit_id').val(id);
    $('#edit_username').val(username);
    $('#edit_nama').val(nama);
    $('#edit_nip').val(nip);
    $('#edit_role').val(role);
    $('#edit_kontak').val(kontak);
    $('#edit_password').val(''); // Kosongkan password
    $('#modalEdit').modal('show');
}

function validasiFormTambah() {
    var username = $('#tambah_username').val().trim();
    var password = $('#tambah_password').val();
    var nama = $('input[name="nama_lengkap"]').val().trim();
    var role = $('select[name="role"]').val();
    
    if (username === '') {
        Swal.fire('Error', 'Username tidak boleh kosong', 'error');
        return false;
    }
    
    if (password.length < 6) {
        Swal.fire('Error', 'Password minimal 6 karakter', 'error');
        return false;
    }
    
    if (nama === '') {
        Swal.fire('Error', 'Nama lengkap tidak boleh kosong', 'error');
        return false;
    }
    
    if (role === '') {
        Swal.fire('Error', 'Role harus dipilih', 'error');
        return false;
    }
    
    return true;
}

function validasiFormEdit() {
    var username = $('#edit_username').val().trim();
    var password = $('#edit_password').val();
    var nama = $('#edit_nama').val().trim();
    var role = $('#edit_role').val();
    
    if (username === '') {
        Swal.fire('Error', 'Username tidak boleh kosong', 'error');
        return false;
    }
    
    if (password.length > 0 && password.length < 6) {
        Swal.fire('Error', 'Password minimal 6 karakter', 'error');
        return false;
    }
    
    if (nama === '') {
        Swal.fire('Error', 'Nama lengkap tidak boleh kosong', 'error');
        return false;
    }
    
    if (role === '') {
        Swal.fire('Error', 'Role harus dipilih', 'error');
        return false;
    }
    
    return true;
}

function hapusUser(id, nama) {
    Swal.fire({
        title: 'Hapus Pengguna?',
        html: `Apakah Anda yakin ingin menghapus pengguna:<br><strong>${nama}</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'user.php?hapus=' + id;
        }
    });
}

function resetPassword(id, nama) {
    Swal.fire({
        title: 'Reset Password?',
        html: `Reset password untuk <strong>${nama}</strong> ke <strong>123456</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Reset!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'user.php?reset=1&id=' + id;
        }
    });
}
</script>

<style>
.btn-group .btn {
    margin-right: 2px;
}
.badge {
    font-size: 11px;
    padding: 5px 8px;
}
.card .opacity-50 {
    opacity: 0.3;
}
.is-invalid {
    border-color: #dc3545;
}
</style>

<?php include '../includes/footer.php'; ?>