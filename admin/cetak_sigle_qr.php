<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$id = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM aset WHERE id_aset = ?");
$stmt->execute([$id]);
$aset = $stmt->fetch();

if (!$aset) {
    header("Location: aset.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak QR Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f0f0f0;
        }
        .qr-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
        }
        .qr-image {
            width: 250px;
            height: 250px;
            margin-bottom: 20px;
        }
        .qr-image img {
            width: 100%;
            height: 100%;
        }
        .qr-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .qr-code {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .qr-location {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        .qr-footer {
            margin-top: 20px;
            font-size: 12px;
            color: #999;
            border-top: 1px dashed #ccc;
            padding-top: 20px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .qr-container {
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .print-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <button class="print-btn no-print" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak QR Code
        </button>
        
        <div class="qr-image">
            <?php if(file_exists('../qrcodes/' . $aset['qr_code_string'] . '.png')): ?>
                <img src="<?= BASE_URL ?>/qrcodes/<?= $aset['qr_code_string'] ?>.png" alt="QR Code">
            <?php else: ?>
                <p>QR Code tidak ditemukan</p>
            <?php endif; ?>
        </div>
        
        <div class="qr-title"><?= htmlspecialchars($aset['nama_aset']) ?></div>
        <div class="qr-code"><?= $aset['kode_aset'] ?></div>
        
        <div class="qr-footer">
            <div>Departemen Teknik Elektro</div>
            <div>Universitas Hasanuddin</div>
        </div>
        
        <div class="no-print" style="margin-top: 20px;">
            <button class="print-btn" onclick="window.close()">Tutup</button>
        </div>
    </div>
    
    <script>
        // Auto print jika diinginkan
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>