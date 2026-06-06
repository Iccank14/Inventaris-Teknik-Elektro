<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    die("ID aset tidak ditemukan.");
}

$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT * FROM aset WHERE id_aset = ?");
$stmt->execute([$id]);
$aset = $stmt->fetch();

if (!$aset) {
    die("Aset tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code - <?= htmlspecialchars($aset['nama_aset']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 30px;
            background: #f0f0f0;
        }
        .qr-container {
            background: white;
            max-width: 400px;
            margin: 0 auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        img {
            max-width: 250px;
            height: auto;
            margin-bottom: 20px;
        }
        .nama-aset {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .kode-aset {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .qr-string {
            font-size: 12px;
            color: #999;
            word-break: break-all;
        }
        .no-print {
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-print {
            background: #28a745;
            color: white;
        }
        .btn-close {
            background: #6c757d;
            color: white;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: white;
                padding: 0;
            }
            .qr-container {
                box-shadow: none;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <div class="nama-aset"><?= htmlspecialchars($aset['nama_aset']) ?></div>
        <div class="kode-aset"><?= $aset['kode_aset'] ?></div>
        <img src="<?= BASE_URL ?>/qrcodes/<?= $aset['qr_code_string'] ?>.png" 
             alt="QR Code" 
             onerror="this.onerror=null; this.src='<?= BASE_URL ?>/assets/img/no-qr.png'; this.alt='QR tidak ditemukan'">
        <div class="qr-string"><?= $aset['qr_code_string'] ?></div>
        
        <div class="no-print">
            <button class="btn btn-print" onclick="window.print()">Cetak</button>
            <button class="btn btn-close" onclick="window.close()">Tutup</button>
        </div>
    </div>
</body>
</html>