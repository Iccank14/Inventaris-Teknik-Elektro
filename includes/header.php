<?php
// Pastikan session sudah dimulai di config.php, tapi jika belum, mulai di sini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Inventaris Teknik Elektro</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS (opsional) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    
    <!-- jQuery (diperlukan untuk DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS Bundle (termasuk Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <style>
        /* Agar sidebar tetap di kiri dan konten di kanan */
        .sidebar {
            min-height: calc(100vh - 56px); /* 56px adalah tinggi navbar */
            transition: transform 0.3s ease;
        }
        @media (max-width: 767px) {
            .sidebar {
                position: fixed;
                top: 56px;
                left: 0;
                width: 250px;
                height: calc(100vh - 56px);
                z-index: 1000;
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .content {
                margin-left: 0 !important;
            }
        }
        .content {
            transition: margin-left 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navbar Utama -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <!-- Tombol toggle untuk sidebar di mobile -->
            <button class="btn btn-outline-light me-2 d-md-none" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
           <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/index.php">
    <img src="<?= BASE_URL ?>/assets/img/logounhas.png" alt="Logo Unhas" class="me-2" style="height: 40px;">
    <span>Inventaris Teknik Elektro</span>
</a>
            
            <!-- Toggler untuk dropdown user (tetap ada) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_nama']) ?> (<?= $_SESSION['user_role'] ?>)
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar akan dimasukkan di sini oleh file yang meng-include header -->
            <!-- Jangan lupa untuk menutup div row dan container setelah sidebar dan konten -->