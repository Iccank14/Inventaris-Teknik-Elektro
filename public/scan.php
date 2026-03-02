<?php
require_once '../config/config.php';

$db = new Database();
$conn = $db->getConnection();

$kode = $_GET['kode'] ?? '';

if (empty($kode)) {
    header("Location: " . BASE_URL);
    exit();
}

// Cari aset berdasarkan QR code string
$query = "
    SELECT a.*, k.nama_kategori, l.nama_lokasi, l.gedung 
    FROM aset a 
    LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
    LEFT JOIN lokasi l ON a.id_lokasi = l.id_lokasi 
    WHERE a.qr_code_string = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$kode]);
$aset = $stmt->fetch();

if (!$aset) {
    $error = "Aset tidak ditemukan!";
}

// Dapatkan URL sebelumnya (referrer)
$previousUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : BASE_URL;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Aset - Inventaris Teknik Elektro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .aset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }
        .aset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
        }
        .aset-body {
            padding: 30px;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            min-width: 150px;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
        .univ-badge {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-kembali {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-kembali:hover {
            background: #5a6268;
            color: white;
            transform: translateX(-5px);
        }
        .btn-kembali i {
            font-size: 14px;
        }
        .btn-scan-lagi {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-scan-lagi:hover {
            background: #218838;
            color: white;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .alert-danger {
            max-width: 500px;
            margin: 0 auto;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <h4><?= $error ?></h4>
                <div class="action-buttons">
                    <a href="javascript:history.back()" class="btn-kembali">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <a href="<?= BASE_URL ?>" class="btn-scan-lagi">
                        <i class="fas fa-home"></i> Ke Halaman Utama
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="univ-badge">
                <img src="<?= BASE_URL ?>/assets/img/logo-unhas.png" alt="Logo Unhas" height="50" onerror="this.style.display='none'">
                <h5 class="mt-2">Universitas Hasanuddin</h5>
                <p>Departemen Teknik Elektro</p>
            </div>
            
            <div class="aset-card">
                <div class="aset-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><?= htmlspecialchars($aset['nama_aset']) ?></h3>
                        <span class="badge bg-light text-dark"><?= $aset['kode_aset'] ?></span>
                    </div>
                    <p class="mb-0"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($aset['nama_lokasi'] ?? 'Lokasi tidak ditentukan') ?></p>
                </div>
                
                <div class="aset-body">
                    <div class="row">
                        <?php if($aset['foto']): ?>
                        <div class="col-md-5 text-center mb-3">
                            <img src="<?= BASE_URL ?>/<?= $aset['foto'] ?>" 
                                 alt="Foto Aset" class="img-fluid rounded" style="max-height: 250px; border: 1px solid #ddd;">
                        </div>
                        <div class="col-md-7">
                        <?php else: ?>
                        <div class="col-md-12">
                        <?php endif; ?>
                        
                            <table class="table table-borderless">
                                <tr>
                                    <td class="info-label"><i class="fas fa-tag"></i> Merk</td>
                                    <td>: <?= htmlspecialchars($aset['merk'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="info-label"><i class="fas fa-folder"></i> Kategori</td>
                                    <td>: <?= htmlspecialchars($aset['nama_kategori'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="info-label"><i class="fas fa-map-pin"></i> Lokasi Detail</td>
                                    <td>: <?= htmlspecialchars($aset['nama_lokasi'] ?? '-') ?> 
                                        <?= $aset['gedung'] ? '(' . htmlspecialchars($aset['gedung']) . ')' : '' ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="info-label"><i class="fas fa-heartbeat"></i> Kondisi</td>
                                    <td>: 
                                        <span class="badge bg-<?= 
                                            $aset['kondisi'] == 'Baik' ? 'success' : 
                                            ($aset['kondisi'] == 'Rusak Ringan' ? 'warning' : 
                                            ($aset['kondisi'] == 'Rusak Berat' ? 'danger' : 'secondary')) 
                                        ?>" style="font-size: 14px; padding: 8px 12px;">
                                            <?= $aset['kondisi'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="info-label"><i class="fas fa-calendar"></i> Tahun Perolehan</td>
                                    <td>: <?= $aset['tgl_perolehan'] ? date('d-m-Y', strtotime($aset['tgl_perolehan'])) : '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="info-label"><i class="fas fa-money-bill"></i> Sumber Dana</td>
                                    <td>: <?= htmlspecialchars($aset['sumber_dana'] ?? '-') ?></td>
                                </tr>
                                <?php if($aset['harga_perolehan'] > 0): ?>
                                <tr>
                                    <td class="info-label"><i class="fas fa-coins"></i> Harga</td>
                                    <td>: Rp <?= number_format($aset['harga_perolehan'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                            
                            <hr>
                            <h6><i class="fas fa-info-circle"></i> Spesifikasi:</h6>
                            <p style="background: #f8f9fa; padding: 10px; border-radius: 5px;"><?= nl2br(htmlspecialchars($aset['spesifikasi'] ?? '-')) ?></p>
                            
                            <?php if($aset['keterangan']): ?>
                            <hr>
                            <h6><i class="fas fa-sticky-note"></i> Keterangan:</h6>
                            <p style="background: #f8f9fa; padding: 10px; border-radius: 5px;"><?= nl2br(htmlspecialchars($aset['keterangan'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    <div class="text-center text-muted small">
                        <p>Data ini diambil dari Sistem Informasi Inventaris Digital Teknik Elektro Unhas<br>
                        Terakhir diupdate: <?= date('d-m-Y H:i', strtotime($aset['updated_at'])) ?></p>
                    </div>
                    
                    <!-- ACTION BUTTONS FIX -->
                    <div class="action-buttons no-print">
                        <!-- TOMBOL KEMBALI YANG SUDAH DIPERBAIKI -->
                        <button onclick="goBack()" class="btn-kembali">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </button>
                        
                        <button onclick="window.print()" class="btn-scan-lagi" style="background: #17a2b8;">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                        
                        <a href="<?= BASE_URL ?>" class="btn-scan-lagi" style="background: #28a745;">
                            <i class="fas fa-home"></i> Beranda
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    // Fungsi untuk tombol kembali yang lebih reliable
    function goBack() {
        // Cek apakah ada referrer (halaman sebelumnya)
        if (document.referrer) {
            // Kembali ke halaman sebelumnya
            window.location.href = document.referrer;
        } else {
            // Jika tidak ada referrer, kembali ke halaman utama inventaris
            window.location.href = '<?= BASE_URL ?>';
        }
    }
    
    // Alternative: menggunakan history.back() dengan fallback
    function goBackAlt() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '<?= BASE_URL ?>';
        }
    }
    
    // Tambahkan event listener untuk tombol kembali jika menggunakan class
    document.addEventListener('DOMContentLoaded', function() {
        // Jika ada tombol dengan class 'btn-kembali' yang bukan button dengan onclick
        var backButtons = document.querySelectorAll('.btn-kembali:not([onclick])');
        backButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                goBack();
            });
        });
    });
    </script>
    
    <!-- TAMBAHKAN FALLBACK DENGAN JQUERY (JIKA ADA) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Fallback untuk semua link dengan href="javascript:history.back()"
        $('a[href="javascript:history.back()"]').click(function(e) {
            e.preventDefault();
            goBack();
        });
    });
    </script>
</body>
</html>