<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle Hapus Aset (via GET untuk kompatibilitas)
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Ambil data aset untuk hapus file
    $stmt = $conn->prepare("SELECT foto, qr_code_string, nama_aset FROM aset WHERE id_aset = ?");
    $stmt->execute([$id]);
    $aset = $stmt->fetch();
    
    if ($aset) {
        // Hapus file foto
        if ($aset['foto'] && file_exists('../' . $aset['foto'])) {
            unlink('../' . $aset['foto']);
        }
        // Hapus file QR
        if ($aset['qr_code_string'] && file_exists('../qrcodes/' . $aset['qr_code_string'] . '.png')) {
            unlink('../qrcodes/' . $aset['qr_code_string'] . '.png');
        }
        
        // Catat riwayat sebelum hapus
        $riwayat = $conn->prepare("INSERT INTO riwayat_aset (id_aset, id_user, jenis_kejadian, deskripsi) VALUES (?, ?, 'Hapus', ?)");
        $riwayat->execute([$id, $_SESSION['user_id'], 'Aset dihapus dari sistem: ' . $aset['nama_aset']]);
        
        // Hapus dari database
        $stmt = $conn->prepare("DELETE FROM aset WHERE id_aset = ?");
        $stmt->execute([$id]);
    }
    
    header("Location: aset.php?msg=deleted");
    exit();
}

// Handle Hapus Massal
if (isset($_POST['hapus_massal']) && isset($_POST['selected_ids'])) {
    $ids = explode(',', $_POST['selected_ids']);
    
    foreach ($ids as $id) {
        // Ambil data aset
        $stmt = $conn->prepare("SELECT foto, qr_code_string, nama_aset FROM aset WHERE id_aset = ?");
        $stmt->execute([$id]);
        $aset = $stmt->fetch();
        
        if ($aset) {
            // Hapus file
            if ($aset['foto'] && file_exists('../' . $aset['foto'])) {
                unlink('../' . $aset['foto']);
            }
            if ($aset['qr_code_string'] && file_exists('../qrcodes/' . $aset['qr_code_string'] . '.png')) {
                unlink('../qrcodes/' . $aset['qr_code_string'] . '.png');
            }
        }
    }
    
    // Hapus dari database
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("DELETE FROM aset WHERE id_aset IN ($placeholders)");
    $stmt->execute($ids);
    
    header("Location: aset.php?msg=mass_deleted");
    exit();
}

// Handle Export Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Data_Aset_" . date('Y-m-d') . ".xls");
    
    $query = "
        SELECT a.kode_aset, a.nama_aset, a.merk, k.nama_kategori, 
               l.nama_lokasi, a.kondisi, a.harga_perolehan, a.tgl_perolehan
        FROM aset a 
        LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
        LEFT JOIN lokasi l ON a.id_lokasi = l.id_lokasi 
        ORDER BY a.kode_aset
    ";
    $asetList = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "KODE ASET\tNAMA ASET\tMERK\tKATEGORI\tLOKASI\tKONDISI\tHARGA\tTANGGAL PEROLEHAN\n";
    foreach ($asetList as $aset) {
        echo $aset['kode_aset'] . "\t";
        echo $aset['nama_aset'] . "\t";
        echo ($aset['merk'] ?? '-') . "\t";
        echo ($aset['nama_kategori'] ?? '-') . "\t";
        echo ($aset['nama_lokasi'] ?? '-') . "\t";
        echo $aset['kondisi'] . "\t";
        echo $aset['harga_perolehan'] . "\t";
        echo ($aset['tgl_perolehan'] ?? '-') . "\n";
    }
    exit();
}

// Filter dan Pencarian
$where = [];
$params = [];

// Filter berdasarkan kategori
if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
    $where[] = "a.id_kategori = ?";
    $params[] = $_GET['kategori'];
}

// Filter berdasarkan lokasi
if (isset($_GET['lokasi']) && !empty($_GET['lokasi'])) {
    $where[] = "a.id_lokasi = ?";
    $params[] = $_GET['lokasi'];
}

// Filter berdasarkan kondisi
if (isset($_GET['kondisi']) && !empty($_GET['kondisi'])) {
    $where[] = "a.kondisi = ?";
    $params[] = $_GET['kondisi'];
}

// Pencarian
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where[] = "(a.kode_aset LIKE ? OR a.nama_aset LIKE ? OR a.merk LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

// Bangun query
$query = "
    SELECT a.*, k.nama_kategori, l.nama_lokasi,
           (SELECT COUNT(*) FROM verifikasi_opname WHERE id_aset = a.id_aset AND tgl_verifikasi = CURDATE()) as sudah_diverifikasi
    FROM aset a 
    LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
    LEFT JOIN lokasi l ON a.id_lokasi = l.id_lokasi 
";

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY a.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$asetList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data untuk filter
$kategoriList = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$lokasiList = $conn->query("SELECT * FROM lokasi ORDER BY nama_lokasi")->fetchAll();

// Hitung total nilai aset dengan benar (pastikan numeric)
$totalNilai = 0;
foreach ($asetList as $aset) {
    $totalNilai += (float)($aset['harga_perolehan'] ?? 0);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Data Aset Inventaris</h2>
        <div>
            <a href="aset_tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Aset
            </a>
            <a href="?export=excel" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <button class="btn btn-info" onclick="printTable()">
                <i class="fas fa-print"></i> Cetak
            </button>
        </div>
    </div>
    
    <!-- Form Filter -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filter Data</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <label>Kategori</label>
                    <select name="kategori" class="form-control">
                        <option value="">Semua Kategori</option>
                        <?php foreach($kategoriList as $k): ?>
                        <option value="<?= $k['id_kategori'] ?>" <?= isset($_GET['kategori']) && $_GET['kategori'] == $k['id_kategori'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Lokasi</label>
                    <select name="lokasi" class="form-control">
                        <option value="">Semua Lokasi</option>
                        <?php foreach($lokasiList as $l): ?>
                        <option value="<?= $l['id_lokasi'] ?>" <?= isset($_GET['lokasi']) && $_GET['lokasi'] == $l['id_lokasi'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['nama_lokasi']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Kondisi</label>
                    <select name="kondisi" class="form-control">
                        <option value="">Semua Kondisi</option>
                        <option value="Baik" <?= isset($_GET['kondisi']) && $_GET['kondisi'] == 'Baik' ? 'selected' : '' ?>>Baik</option>
                        <option value="Rusak Ringan" <?= isset($_GET['kondisi']) && $_GET['kondisi'] == 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                        <option value="Rusak Berat" <?= isset($_GET['kondisi']) && $_GET['kondisi'] == 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
                        <option value="Hilang" <?= isset($_GET['kondisi']) && $_GET['kondisi'] == 'Hilang' ? 'selected' : '' ?>>Hilang</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Pencarian</label>
                    <input type="text" name="search" class="form-control" placeholder="Kode/Nama/Merk..." value="<?= $_GET['search'] ?? '' ?>">
                </div>
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Terapkan Filter
                    </button>
                    <a href="aset.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] == 'added'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Aset berhasil ditambahkan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Aset berhasil diupdate!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Aset berhasil dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'mass_deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Aset terpilih berhasil dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h5>Daftar Aset (<?= count($asetList) ?> ditemukan)</h5>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-sm btn-danger" id="btnHapusMassal" style="display: none;" onclick="hapusMassal()">
                        <i class="fas fa-trash"></i> Hapus Terpilih
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="tabelAset">
                    <thead class="table-primary">
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th width="50">No</th>
                            <th width="80">QR Code</th>
                            <th>Kode Aset</th>
                            <th>Nama Aset</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Kondisi</th>
                            <th>Verifikasi</th>
                            <th>Harga</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($asetList)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada data aset</p>
                                <a href="aset_tambah.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Aset Pertama
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php $no=1; foreach($asetList as $aset): ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="selected" value="<?= $aset['id_aset'] ?>" class="aset-check">
                            </td>
                            <td><?= $no++ ?></td>
                            <td class="text-center">
                                <?php if(file_exists('../qrcodes/' . $aset['qr_code_string'] . '.png')): ?>
                                    <img src="<?= BASE_URL ?>/qrcodes/<?= $aset['qr_code_string'] ?>.png" 
                                         width="50" height="50" alt="QR" class="img-thumbnail"
                                         onclick="showQR('<?= $aset['qr_code_string'] ?>', '<?= htmlspecialchars($aset['nama_aset']) ?>')"
                                         style="cursor: pointer;">
                                <?php else: ?>
                                    <span class="badge bg-danger">QR tidak ditemukan</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= $aset['kode_aset'] ?></strong></td>
                            <td>
                                <?= htmlspecialchars($aset['nama_aset']) ?>
                                <?php if($aset['merk']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($aset['merk']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($aset['nama_kategori']): ?>
                                    <span class="badge bg-info"><?= htmlspecialchars($aset['nama_kategori']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($aset['nama_lokasi']): ?>
                                    <span class="badge bg-success"><?= htmlspecialchars($aset['nama_lokasi']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $aset['kondisi'] == 'Baik' ? 'success' : 
                                    ($aset['kondisi'] == 'Rusak Ringan' ? 'warning' : 
                                    ($aset['kondisi'] == 'Rusak Berat' ? 'danger' : 'dark')) 
                                ?>">
                                    <?= $aset['kondisi'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if($aset['sudah_diverifikasi'] > 0): ?>
                                    <span class="badge bg-success" title="Sudah diverifikasi hari ini">
                                        <i class="fas fa-check-circle"></i> Hari ini
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary" title="Belum diverifikasi hari ini">
                                        <i class="fas fa-clock"></i> Belum
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php 
                                $harga = (float)($aset['harga_perolehan'] ?? 0);
                                if($harga > 0): 
                                    // Format harga dengan pemisah ribuan dan 2 desimal
                                    echo 'Rp ' . number_format($harga, 2, ',', '.');
                                else: 
                                ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="aset_edit.php?id=<?= $aset['id_aset'] ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/public/scan.php?kode=<?= $aset['qr_code_string'] ?>" 
                                       target="_blank" class="btn btn-sm btn-info" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="konfirmasiHapus(<?= $aset['id_aset'] ?>, '<?= htmlspecialchars($aset['nama_aset']) ?>')"
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-success"
                                            onclick="cetakQR(<?= $aset['id_aset'] ?>)"
                                            title="Cetak QR">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="9" class="text-end">Total Nilai Aset:</th>
                            <th class="text-end">
                                <?php 
                                // Format total nilai dengan pemisah ribuan dan 2 desimal
                                echo 'Rp ' . number_format($totalNilai, 2, ',', '.');
                                ?>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal QR Code -->
<div class="modal fade" id="modalQR" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR Code Aset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="qrImage" class="img-fluid mb-3" style="max-width: 200px;">
                <p id="qrNamaAset"></p>
                <p id="qrKodeAset" class="text-muted small"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="printQR()">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Massal -->
<div class="modal fade" id="modalHapusMassal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus Massal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus <span id="jumlahTerpilih"></span> aset yang dipilih?</p>
                <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan!</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" id="formHapusMassal">
                    <input type="hidden" name="selected_ids" id="selectedIds">
                    <button type="submit" name="hapus_massal" class="btn btn-danger">Ya, Hapus!</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    var table = $('#tabelAset').DataTable({
        pageLength: 25,
        order: [[3, 'asc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
        },
        columnDefs: [
            { type: 'num', targets: 9 } // Kolom harga sebagai numeric
        ]
    });
    
    // Check all checkbox
    $('#checkAll').click(function() {
        $('.aset-check').prop('checked', this.checked);
        toggleHapusMassal();
    });
    
    // Individual checkbox
    $('.aset-check').change(function() {
        if ($('.aset-check:checked').length == $('.aset-check').length) {
            $('#checkAll').prop('checked', true);
        } else {
            $('#checkAll').prop('checked', false);
        }
        toggleHapusMassal();
    });
});

function toggleHapusMassal() {
    var checked = $('.aset-check:checked').length;
    if (checked > 0) {
        $('#btnHapusMassal').show();
        $('#btnHapusMassal').text('Hapus ' + checked + ' Terpilih');
    } else {
        $('#btnHapusMassal').hide();
    }
}

function hapusMassal() {
    var selected = [];
    $('.aset-check:checked').each(function() {
        selected.push($(this).val());
    });
    
    if (selected.length > 0) {
        $('#jumlahTerpilih').text(selected.length);
        $('#selectedIds').val(selected.join(','));
        $('#modalHapusMassal').modal('show');
    }
}

function konfirmasiHapus(id, nama) {
    Swal.fire({
        title: 'Hapus Aset?',
        html: `Apakah Anda yakin ingin menghapus aset:<br><strong>${nama}</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'aset.php?hapus=' + id;
        }
    });
}

function showQR(qrString, namaAset) {
    $('#qrImage').attr('src', '<?= BASE_URL ?>/qrcodes/' + qrString + '.png');
    $('#qrNamaAset').text(namaAset);
    $('#qrKodeAset').text('Kode: ' + qrString);
    $('#modalQR').modal('show');
}

function printQR() {
    var qrSrc = $('#qrImage').attr('src');
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Cetak QR Code</title>
            <style>
                body { text-align: center; padding: 50px; }
                img { max-width: 300px; }
                p { font-family: Arial; }
            </style>
        </head>
        <body>
            <img src="${qrSrc}" style="max-width: 300px;">
            <p>${$('#qrNamaAset').text()}</p>
            <p><small>${$('#qrKodeAset').text()}</small></p>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

function cetakQR(id) {
    window.open('cetak_single_qr.php?id=' + id, '_blank');
}

function printTable() {
    var printContent = document.getElementById('tabelAset').cloneNode(true);
    $(printContent).find('.btn-group, input[type="checkbox"]').remove();
    
    var totalNilai = '<?= 'Rp ' . number_format($totalNilai, 2, ',', '.') ?>';
    
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Data Aset Inventaris</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; font-family: Arial, sans-serif; }
                table { font-size: 12px; width: 100%; border-collapse: collapse; }
                th { background-color: #007bff; color: white; padding: 8px; }
                td { padding: 6px; border: 1px solid #ddd; }
                .text-end { text-align: right; }
                .text-center { text-align: center; }
                .badge { 
                    display: inline-block; 
                    padding: 4px 8px; 
                    border-radius: 4px; 
                    color: white;
                    font-size: 11px;
                }
                .bg-success { background-color: #28a745; }
                .bg-warning { background-color: #ffc107; color: black; }
                .bg-danger { background-color: #dc3545; }
                .bg-info { background-color: #17a2b8; }
                .bg-secondary { background-color: #6c757d; }
                @media print {
                    .no-print { display: none; }
                    body { padding: 0; }
                }
                .header-title { 
                    text-align: center; 
                    margin-bottom: 20px; 
                    border-bottom: 2px solid #007bff;
                    padding-bottom: 10px;
                }
            </style>
        </head>
        <body>
            <div class="header-title">
                <h2>Data Aset Inventaris</h2>
                <h4>Departemen Teknik Elektro Universitas Hasanuddin</h4>
                <p>Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
            </div>
            ${printContent.outerHTML}
            <div style="margin-top: 20px; text-align: right;">
                <strong>Total Nilai Aset: ${totalNilai}</strong>
            </div>
            <p class="text-muted text-center no-print" style="margin-top: 20px;">
                <small>Dokumen ini dicetak dari Sistem Informasi Inventaris Digital</small>
            </p>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}
</script>

<style>
.table th {
    white-space: nowrap;
}
.table td {
    vertical-align: middle;
}
.btn-group .btn {
    margin-right: 2px;
}
.badge {
    font-size: 11px;
    padding: 5px 8px;
}
.img-thumbnail {
    transition: transform 0.2s;
}
.img-thumbnail:hover {
    transform: scale(1.5);
    z-index: 1000;
    position: relative;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
}
.text-end {
    text-align: right !important;
}
tfoot th {
    background-color: #e9ecef !important;
    font-weight: bold;
}
</style>

<?php include '../includes/footer.php'; ?>