<?php
// Simulasikan data pengguna yang akan digunakan di Navbar
// Pastikan ini dimuat di setiap halaman sebelum memanggil template/header.php
$user_data = [
    'nama' => 'Henry Klein',
    'role' => 'Admin'
];

// Asumsi $page_title dan $page sudah di-set di file utama (e.g., index.php)
// Jika belum, gunakan nilai default
$page_title = $page_title ?? 'Dashboard'; 
$page = $page ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?> | Instansi Pembina JF</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <style>
        /* Gaya Seragam untuk Brand */
        .brand-link { background-color: #111827; }
        .brand-link .logo-pupr i { color: #0f62fe; }
        .logo-pupr { background: #fff; padding: 4px; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 50%;}
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
             <li class="nav-item d-none d-sm-inline-block">
                <div style="padding-top: 8px; text-align: right;">
                    Hi, selamat datang kembali!<br>
                    <span class="text-muted" style="font-size:13px">Sistem informasi jabatan fungsional</span>
                </div>
            </li>
            
            <li class="nav-item dropdown user-menu ml-3">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <img src="https://i.pravatar.cc/36" class="user-image img-circle elevation-2" alt="User Image">
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($user_data['nama']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <li class="user-header bg-primary">
                        <img src="https://i.pravatar.cc/90" class="img-circle elevation-2" alt="User Image">
                        <p>
                            <?php echo htmlspecialchars($user_data['nama']); ?> - <?php echo htmlspecialchars($user_data['role']); ?>
                            <small>Instansi Pembina</small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <a href="pengaturan.php" class="btn btn-default btn-flat">
                            <i class="fas fa-cog"></i> Pengaturan
                        </a>
                        <a href="#" class="btn btn-default btn-flat float-right">
                            <i class="fas fa-sign-out-alt"></i> Sign out
                        </a>
                    </li>
                </ul>
            </li>
            
        </ul>
    </nav>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="index.php" class="brand-link">
            <div class="logo-pupr brand-image img-circle elevation-3" style="opacity: .8;"><i class="fas fa-building fa-lg"></i></div>
            <span class="brand-text font-weight-light">Instansi Pembina JF</span>
        </a>

        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="database.php" class="nav-link <?php echo ($page == 'database') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-database"></i><p>Database</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="ujikom.php" class="nav-link <?php echo ($page == 'ujikom') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-clipboard-list"></i><p>Uji Kompetensi</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="rekomendasi.php" class="nav-link <?php echo ($page == 'rekomendasi') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-line"></i><p>Rekomendasi Formasi</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="peraturan.php" class="nav-link <?php echo ($page == 'peraturan') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-book"></i><p>Peraturan</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="pengaturan.php" class="nav-link <?php echo ($page == 'pengaturan') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-cog"></i><p>Pengaturan</p>
                        </a>
                    </li>
                </ul>
            </nav>
            </div>
        </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="nav-icon <?php 
                            // Tambahkan ikon berdasarkan nilai $page (jika ada)
                            $icon_map = [
                                'dashboard' => 'fas fa-tachometer-alt',
                                'database' => 'fas fa-database',
                                'ujikom' => 'fas fa-clipboard-list',
                                'rekomendasi' => 'fas fa-chart-line',
                                'peraturan' => 'fas fa-book',
                                'pengaturan' => 'fas fa-cog',
                            ];
                            echo $icon_map[$page] ?? '';
                        ?>"></i> <?php echo $page_title; ?></h1>
                    </div>
                    </div>
            </div>
        </div>
        <div class="content">
            <div class="container-fluid">