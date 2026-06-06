<?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isPetugas() && !isAdmin()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Scan QR Code Aset</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div id="reader" style="width: 100%;"></div>
                    
                    <div class="mt-3">
                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Arahkan kamera ke QR Code aset untuk memindai
                        </p>
                    </div>
                    
                    <div id="scanResult" class="mt-3"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Hasil Scan Terakhir</h5>
                </div>
                <div class="card-body" id="lastScan">
                    <p class="text-muted text-center">Belum ada scan</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Library untuk QR Scanner -->
<script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
<script>
const html5QrCode = new Html5Qrcode("reader");

const qrCodeSuccessCallback = (decodedText, decodedResult) => {
    // Hentikan scanning setelah berhasil
    html5QrCode.stop();
    
    // Tampilkan hasil scan
    document.getElementById('scanResult').innerHTML = `
        <div class="alert alert-success">
            <h5>QR Code Terdeteksi!</h5>
            <p>Kode: ${decodedText}</p>
            <button class="btn btn-primary" onclick="prosesKode('${decodedText}')">
                <i class="fas fa-search"></i> Cari Data Aset
            </button>
            <button class="btn btn-secondary" onclick="mulaiScan()">
                <i class="fas fa-camera"></i> Scan Lagi
            </button>
        </div>
    `;
};

const config = { fps: 10, qrbox: { width: 250, height: 250 } };

function mulaiScan() {
    html5QrCode.start(
        { facingMode: "environment" }, 
        config, 
        qrCodeSuccessCallback,
        (errorMessage) => { /* ignore */ }
    );
}

function prosesKode(kode) {
    // Ekstrak kode dari URL jika perlu
    let qrCode = kode;
    if (kode.includes('kode=')) {
        const urlParams = new URLSearchParams(kode.split('?')[1]);
        qrCode = urlParams.get('kode');
    }
    
    // Redirect ke halaman verifikasi
    window.location.href = `verifikasi.php?kode=${qrCode}`;
}

// Mulai scan otomatis saat halaman dimuat
window.onload = function() {
    if (html5QrCode) {
        mulaiScan();
    }
};

// Bersihkan saat pindah halaman
window.onbeforeunload = function() {
    if (html5QrCode) {
        html5QrCode.stop();
    }
};
</script>

<?php include '../includes/footer.php'; ?><?php
require_once '../config/config.php';
redirectIfNotLogin();

if (!isPetugas() && !isAdmin()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$aset = null;
$kode = $_GET['kode'] ?? '';

if ($kode) {
    // Cari aset berdasarkan qr_code_string
    $query = "SELECT a.*, k.nama_kategori, l.nama_lokasi, l.gedung 
              FROM aset a 
              LEFT JOIN kategori k ON a.id_kategori = k.id_kategori 
              LEFT JOIN lokasi l ON a.id_lokasi = l.id_lokasi 
              WHERE a.qr_code_string = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$kode]);
    $aset = $stmt->fetch();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="col-md-9 col-lg-10 p-4">
    <h2 class="mb-4">Scan QR Code Aset</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div id="reader" style="width: 100%;"></div>
                    
                    <div class="mt-3">
                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Arahkan kamera ke QR Code aset untuk memindai
                        </p>
                    </div>
                    
                    <div id="scanResult" class="mt-3"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Detail Aset</h5>
                </div>
                <div class="card-body" id="detailAset">
                    <?php if ($aset): ?>
                        <table class="table table-sm table-bordered">
                            <tr><th width="35%">Kode Aset</th><td><?= htmlspecialchars($aset['kode_aset']) ?></td></tr>
                            <tr><th>Nama Aset</th><td><?= htmlspecialchars($aset['nama_aset']) ?></td></tr>
                            <tr><th>Merk</th><td><?= htmlspecialchars($aset['merk'] ?? '-') ?></td></tr>
                            <tr><th>Kategori</th><td><?= htmlspecialchars($aset['nama_kategori'] ?? '-') ?></td></tr>
                            <tr><th>Lokasi</th><td><?= htmlspecialchars($aset['nama_lokasi'] ?? '-') ?> <?= $aset['gedung'] ? '(' . htmlspecialchars($aset['gedung']) . ')' : '' ?></td></tr>
                            <tr><th>Kondisi</th><td><span class="badge bg-<?= $aset['kondisi'] == 'Baik' ? 'success' : ($aset['kondisi'] == 'Rusak Ringan' ? 'warning' : 'danger') ?>"><?= $aset['kondisi'] ?></span></td></tr>
                            <tr><th>Jumlah Unit</th><td><?= (int)($aset['jumlah_unit'] ?? 1) ?></td></tr>
                            <tr><th>No. SPK</th><td><?= htmlspecialchars($aset['no_spk'] ?? '-') ?></td></tr>
                            <tr><th>Harga</th><td>Rp <?= number_format($aset['harga_perolehan'] ?? 0, 2, ',', '.') ?></td></tr>
                            <tr><th>Tgl Perolehan</th><td><?= $aset['tgl_perolehan'] ? date('d-m-Y', strtotime($aset['tgl_perolehan'])) : '-' ?></td></tr>
                            <tr><th>Sumber Dana</th><td><?= htmlspecialchars($aset['sumber_dana'] ?? '-') ?></td></tr>
                            <tr><th>Spesifikasi</th><td><?= nl2br(htmlspecialchars($aset['spesifikasi'] ?? '-')) ?></td></tr>
                            <?php if ($aset['keterangan']): ?>
                            <tr><th>Keterangan</th><td><?= nl2br(htmlspecialchars($aset['keterangan'])) ?></td></tr>
                            <?php endif; ?>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center">Belum ada data. Silakan scan QR code.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Library untuk QR Scanner -->
<script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
<script>
const html5QrCode = new Html5Qrcode("reader");

const qrCodeSuccessCallback = (decodedText, decodedResult) => {
    // Hentikan scanning setelah berhasil
    html5QrCode.stop();
    
    // Ekstrak kode dari URL jika perlu
    let qrCode = decodedText;
    if (decodedText.includes('kode=')) {
        const urlParams = new URLSearchParams(decodedText.split('?')[1]);
        qrCode = urlParams.get('kode');
    }
    
    // Redirect ke halaman yang sama dengan parameter kode
    window.location.href = `scan.php?kode=${qrCode}`;
};

const config = { fps: 10, qrbox: { width: 250, height: 250 } };

function mulaiScan() {
    html5QrCode.start(
        { facingMode: "environment" }, 
        config, 
        qrCodeSuccessCallback,
        (errorMessage) => { /* ignore */ }
    );
}

function scanLagi() {
    // Kosongkan parameter kode di URL
    window.location.href = 'scan.php';
}

// Mulai scan otomatis saat halaman dimuat (jika tidak ada parameter kode)
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('kode')) {
        if (html5QrCode) {
            mulaiScan();
        }
    }
};

// Bersihkan saat pindah halaman
window.onbeforeunload = function() {
    if (html5QrCode) {
        html5QrCode.stop();
    }
};
</script>

<?php include '../includes/footer.php'; ?>