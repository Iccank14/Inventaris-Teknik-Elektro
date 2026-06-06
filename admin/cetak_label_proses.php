<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$aset_ids = $_POST['aset_ids'] ?? [];
$ukuran = $_POST['ukuran'] ?? 'medium';

if (empty($aset_ids)) {
    header("Location: cetak_label.php?error=no_selection");
    exit();
}

// Tentukan ukuran (dalam pixel)
$size = match($ukuran) {
    'small' => 100,
    'medium' => 150,
    'large' => 200,
    default => 150
};

$db = new Database();
$conn = $db->getConnection();

$placeholders = implode(',', array_fill(0, count($aset_ids), '?'));
$query = "SELECT * FROM aset WHERE id_aset IN ($placeholders) ORDER BY kode_aset";
$stmt = $conn->prepare($query);
$stmt->execute($aset_ids);
$asetList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Label QR</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
        }
        .print-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .qr-label {
            border: 1px dashed #ccc;
            padding: 10px;
            text-align: center;
            width: <?= $size + 20 ?>px;
            page-break-inside: avoid;
        }
        .qr-label img {
            width: <?= $size ?>px;
            height: <?= $size ?>px;
        }
        .qr-label .nama {
            font-size: 11px;
            font-weight: bold;
            margin: 5px 0;
        }
        .qr-label .kode {
            font-size: 10px;
            color: #555;
        }
        @media print {
            body { padding: 0; }
            .qr-label { border: none; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <?php foreach($asetList as $aset): 
            $qrPath = '../qrcodes/' . $aset['qr_code_string'] . '.png';
            if (!file_exists($qrPath)) continue;
        ?>
            <div class="qr-label">
                <img src="<?= $qrPath ?>" alt="QR">
                <div class="nama"><?= htmlspecialchars($aset['nama_aset']) ?></div>
                <div class="kode"><?= $aset['kode_aset'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        window.onload = function() {
            window.print();
            // Optional: setelah print, bisa redirect atau tutup
            // setTimeout(() => window.close(), 1000);
        }
    </script>
</body>
</html>