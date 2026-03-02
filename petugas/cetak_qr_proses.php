<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isPetugas() && !isAdmin()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$aset_ids = $_POST['aset_ids'] ?? [];
$ukuran = $_POST['ukuran'] ?? 'medium';

if (empty($aset_ids)) {
    header("Location: cetak_qr.php?error=no_selection");
    exit();
}

// Tentukan ukuran
$size = match($ukuran) {
    'small' => 100,
    'medium' => 150,
    'large' => 200,
    default => 150
};

$db = new Database();
$conn = $db->getConnection();

// Ambil data aset
$placeholders = implode(',', array_fill(0, count($aset_ids), '?'));
$query = "SELECT * FROM aset WHERE id_aset IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->execute($aset_ids);
$asetList = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cetak Label QR Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .print-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .qr-label {
            border: 1px dashed #ccc;
            padding: 10px;
            text-align: center;
            width: <?= $size + 20 ?>px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .qr-label img {
            width: <?= $size ?>px;
            height: <?= $size ?>px;
        }
        .qr-label .kode {
            font-size: 10px;
            margin-top: 5px;
            word-break: break-word;
        }
        .qr-label .nama {
            font-size: 11px;
            font-weight: bold;
            margin: 5px 0;
        }
        @media print {
            .no-print {
                display: none;
            }
            .qr-label {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Cetak</button>
        <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
    </div>
    
    <div class="print-container">
        <?php foreach($asetList as $aset): 
            $qrPath = '../qrcodes/' . $aset['qr_code_string'] . '.png';
            if (!file_exists($qrPath)) continue;
        ?>
            <div class="qr-label">
                <img src="<?= $qrPath ?>" alt="QR Code">
                <div class="nama"><?= $aset['nama_aset'] ?></div>
                <div class="kode"><?= $aset['kode_aset'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        window.onload = function() {
            // Uncomment untuk auto print
            // window.print();
        }
    </script>
</body>
</html>