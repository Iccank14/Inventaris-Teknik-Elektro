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

<?php include '../includes/footer.php'; ?>