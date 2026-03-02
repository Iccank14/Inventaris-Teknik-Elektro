CREATE DATABASE IF NOT EXISTS inventaris_teknik_elektro;
USE inventaris_teknik_elektro;

-- Tabel User
CREATE TABLE users (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    nip VARCHAR(20),
    role ENUM('admin', 'petugas', 'pimpinan') DEFAULT 'petugas',
    kontak VARCHAR(20),
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Kategori
CREATE TABLE kategori (
    id_kategori INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Lokasi
CREATE TABLE lokasi (
    id_lokasi INT PRIMARY KEY AUTO_INCREMENT,
    nama_lokasi VARCHAR(100) NOT NULL,
    gedung VARCHAR(100),
    penanggung_jawab VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Aset
CREATE TABLE aset (
    id_aset INT PRIMARY KEY AUTO_INCREMENT,
    kode_aset VARCHAR(50) UNIQUE NOT NULL,
    qr_code_string VARCHAR(100) UNIQUE NOT NULL,
    nama_aset VARCHAR(200) NOT NULL,
    merk VARCHAR(100),
    spesifikasi TEXT,
    id_kategori INT,
    id_lokasi INT,
    kondisi ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') DEFAULT 'Baik',
    harga_perolehan DECIMAL(15,2),
    tgl_perolehan DATE,
    sumber_dana VARCHAR(100),
    foto VARCHAR(255),
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE SET NULL,
    FOREIGN KEY (id_lokasi) REFERENCES lokasi(id_lokasi) ON DELETE SET NULL
);

-- Tabel Riwayat Aset
CREATE TABLE riwayat_aset (
    id_riwayat INT PRIMARY KEY AUTO_INCREMENT,
    id_aset INT NOT NULL,
    id_user INT,
    tgl_kejadian DATETIME DEFAULT CURRENT_TIMESTAMP,
    jenis_kejadian ENUM('Tambah', 'Update', 'Perbaikan', 'Mutasi', 'Verifikasi') NOT NULL,
    deskripsi TEXT,
    FOREIGN KEY (id_aset) REFERENCES aset(id_aset) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL
);

-- Tabel Verifikasi Opname
CREATE TABLE verifikasi_opname (
    id_verifikasi INT PRIMARY KEY AUTO_INCREMENT,
    id_aset INT NOT NULL,
    id_user INT NOT NULL,
    tgl_verifikasi DATE NOT NULL,
    jam_verifikasi TIME NOT NULL,
    status_verifikasi ENUM('Ditemukan', 'Tidak Ditemukan') DEFAULT 'Ditemukan',
    catatan TEXT,
    FOREIGN KEY (id_aset) REFERENCES aset(id_aset) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

-- Insert data default
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', MD5('admin123'), 'Administrator', 'admin'),
('petugas', MD5('petugas123'), 'Petugas Inventaris', 'petugas'),
('kadep', MD5('kadep123'), 'Kepala Departemen', 'pimpinan');

INSERT INTO kategori (nama_kategori, deskripsi) VALUES
('Komputer', 'Desktop, Laptop, Server'),
('Alat Ukur', 'Multimeter, Osiloskop, dll'),
('Peralatan Lab', 'Toolkit, Solder, dll'),
('Furniture', 'Meja, Kursi, Lemari');

INSERT INTO lokasi (nama_lokasi, gedung, penanggung_jawab) VALUES
('Lab. Komputer Dasar', 'Gedung Teknik A', 'Dr. Andi'),
('Lab. Elektronika', 'Gedung Teknik B', 'Ir. Budi'),
('Lab. Telekomunikasi', 'Gedung Teknik B', 'Dr. Cici'),
('Ruang Dosen', 'Gedung Teknik A', 'Sekretariat');