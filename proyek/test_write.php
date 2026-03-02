<?php
$qr_path = __DIR__ . '/qrcodes/test.txt';
$upload_path = __DIR__ . '/assets/uploads/aset/test.txt';

file_put_contents($qr_path, 'test');
file_put_contents($upload_path, 'test');

if (file_exists($qr_path) && file_exists($upload_path)) {
    echo "✅ Folder permission OK!";
    unlink($qr_path);
    unlink($upload_path);
} else {
    echo "❌ Folder permission ERROR!";
}
?>