<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isAdmin() && !isPetugas()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->getConnection();

try {
    // Ambil data aset untuk hapus file
    $stmt = $conn->prepare("SELECT foto, qr_code_string FROM aset WHERE id_aset = ?");
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
    }
    
    // Hapus dari database
    $stmt = $conn->prepare("DELETE FROM aset WHERE id_aset = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>