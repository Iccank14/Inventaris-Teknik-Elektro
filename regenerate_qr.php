<?php
require_once 'config/config.php';
require_once 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$db = new Database();
$conn = $db->getConnection();

// Ambil semua aset yang memiliki qr_code_string
$query = "SELECT id_aset, qr_code_string, nama_aset FROM aset WHERE qr_code_string IS NOT NULL";
$asetList = $conn->query($query)->fetchAll();

if (empty($asetList)) {
    die("Tidak ada aset ditemukan.");
}

$writer = new PngWriter();
$qrDir = __DIR__ . '/qrcodes/';

// Pastikan folder qrcodes ada
if (!file_exists($qrDir)) {
    mkdir($qrDir, 0777, true);
}

echo "<h2>Memperbarui QR Code Aset</h2>";
echo "<ul>";
foreach ($asetList as $aset) {
    $qrString = $aset['qr_code_string'];
    $url = BASE_URL . '/public/scan.php?kode=' . urlencode($qrString);
    
    $qrCode = new QrCode($url);
    $result = $writer->write($qrCode);
    
    $filePath = $qrDir . $qrString . '.png';
    $result->saveToFile($filePath);
    
    echo "<li>Aset: {$aset['nama_aset']} (ID: {$aset['id_aset']}) -> QR diperbarui</li>";
}
echo "</ul>";
echo "<p><strong>Selesai! Semua QR Code telah diperbarui dengan BASE_URL = " . BASE_URL . "</strong></p>";