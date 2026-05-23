<?php
// 1. Inisialisasi Session - Wajib paling atas
session_start();

// 2. Load Koneksi Database
require_once 'koneksi.php';

/** 
 * PERBAIKAN LOGIKA KONEKSI:
 * Memastikan $koneksi mengambil variabel yang tepat dari koneksi.php
 */
if (isset($conn)) {
    $koneksi = $conn;
} elseif (isset($db)) {
    $koneksi = $db;
} else {
    // Fallback jika variabel tidak ditemukan namun file koneksi.php ada
    $koneksi = mysqli_connect("localhost", "root", "", "db_nama_anda"); 
}

// 3. Logika Redirect Aman (Dijalankan sebelum konten render)
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role_sesi'] ?? '';
    $dashboard_map = [
        'user_pengusul'    => 'index_pengusul.php',
        'user_verifikator' => 'index_verifikator.php',
        'user_ppsdm'       => 'index_ppsdm.php',
        'user_kasubdit'    => 'index_kasubdit.php',
        'user_direktur'    => 'index_direktur.php',
        'user_evaluator'   => 'index_evaluator.php'
    ];

    if (array_key_exists($role, $dashboard_map) && file_exists($dashboard_map[$role])) {
        header("Location: " . $dashboard_map[$role]);
        exit;
    }
}

// 4. Ambil Data Utama dari Database
$query_portal = mysqli_query($koneksi, "SELECT * FROM tb_admin_update WHERE id = 1 LIMIT 1");
$data_utama = mysqli_fetch_assoc($query_portal);

// Default data jika database kosong
if (!$data_utama) {
    $data_utama = [
        'judul_pengumuman' => 'Pendaftaran Belum Dibuka',
        'file_pengumuman'  => '',
        'kj_tgl_daftar'    => '-',
        'kj_tgl_ujian'     => '-',
        'pj_tgl_daftar'    => '-',
        'pj_tgl_ujian'     => '-',
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Uji Kompetensi - JFPKP</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --primary-color: #0f4c75;
            --secondary-color: #3282b8;
            --dark-text: #1b262c;
            --light-bg: #ffffff;
            --gray-bg: #f8f9fa;
            --border-color: #e9ecef;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            margin: 0;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            z-index: 1000;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            color: var(--dark-text) !important;
            text-decoration: none;
        }

        /* Buttons */
        .btn-modern-primary {
            background: var(--primary-color);
            color: white !important;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(15, 76, 117, 0.2);
            text-decoration: none;
            display: inline-block;
        }

        .btn-modern-primary:hover {
            transform: translateY(-3px);
            background: var(--secondary-color);
            box-shadow: 0 6px 20px rgba(15, 76, 117, 0.3);
        }

        .btn-modern-outline {
            background: transparent;
            color: var(--primary-color) !important;
            border: 2px solid var(--primary-color);
            padding: 10px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-modern-outline:hover {
            background: var(--primary-color);
            color: white !important;
            transform: translateY(-3px);
        }

        .btn-auth-nav {
            background-color: var(--primary-color);
            color: white !important;
            padding: 8px 25px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
        }

        .btn-regis-nav {
            border: 2px solid var(--primary-color);
            color: var(--primary-color) !important;
            padding: 6px 25px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
        }

        /* Hero Section */
        .hero {
            min-height: 95vh;
            background: white;
            display: flex;
            align-items: center;
            padding-top: 100px;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 40%; height: 100%;
            background: rgba(50, 130, 184, 0.03);
            z-index: 0;
            clip-path: polygon(25% 0%, 100% 0%, 100% 100%, 0% 100%);
        }

        .hero-schedule-container {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 35px;
            position: relative;
            z-index: 1;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }

        .hero-schedule-item {
            background: var(--gray-bg);
            border-left: 4px solid var(--secondary-color);
            border-radius: 12px;
            padding: 20px;
            height: 100%;
        }

        .pembukaan-ujikom {
            background: #fff9e6;
            border: 1px solid #ffeeba;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }

        .btn-download-surat {
            background-color: #ffc107;
            color: #212529;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            transition: 0.3s;
        }

        .footer { 
            background: white; 
            color: #6c757d; 
            padding: 40px 0; 
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .text-info-custom { color: var(--secondary-color) !important; }
        .badge-custom { background: var(--secondary-color); color: white; padding: 5px 15px; border-radius: 50px; }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-city me-2 text-info-custom"></i>JFPKP
            </a>
            <div class="d-flex gap-2">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="btn-auth-nav">Login</a>
                    <a href="register.php" class="btn-regis-nav">Daftar</a>
                <?php else: ?>
                    <a href="logout.php" class="btn-auth-nav bg-danger">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <!-- Teks Utama -->
                <div class="col-lg-6 animate__animated animate__fadeIn">
                    <h6 class="text-info-custom fw-bold mb-3">PORTAL RESMI JFPKP</h6>
                    <h1 class="display-5 fw-bold mb-4" style="color: var(--dark-text);">Sistem Verifikasi <br><span class="text-info-custom">Uji Kompetensi</span></h1>
                    <p class="lead mb-5 text-muted">Platform digital terpadu untuk manajemen pengajuan dan verifikasi berkas uji kompetensi secara transparan dan akuntabel.</p>
                    
                    <div class="d-flex flex-wrap gap-3">
                        <a href="register.php" class="btn-modern-primary">
                            <i class="fas fa-user-plus me-2"></i>Mulai Pendaftaran
                        </a>
                        <a href="panduan.php" class="btn-modern-outline">
                            <i class="fas fa-book-open me-2"></i>Panduan Sistem
                        </a>
                    </div>
                </div>

                <!-- Card Jadwal -->
                <div class="col-lg-6 mt-5 mt-lg-0 animate__animated animate__fadeInRight">
                    <div class="hero-schedule-container">
                        <div class="pembukaan-ujikom">
                            <h5 class="fw-bold text-dark mb-2">
                                <i class="fas fa-bullhorn me-2 text-warning"></i><?= htmlspecialchars($data_utama['judul_pengumuman']) ?>
                            </h5>
                            <p class="small text-muted mb-3">Akses pendaftaran portofolio tersedia bagi seluruh peserta JFPKP.</p>
                            
                            <?php if(!empty($data_utama['file_pengumuman'])): ?>
                                <a href="uploads/pengumuman/<?= htmlspecialchars(basename($data_utama['file_pengumuman'])) ?>" 
                                   class="btn-download-surat shadow-sm" download>
                                     <i class="fas fa-file-download me-2"></i>Unduh Surat Pengumuman
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">File pengumuman belum diunggah</span>
                            <?php endif; ?>
                        </div>

                        <h5 class="mb-4 text-center fw-bold"><i class="far fa-calendar-alt me-2 text-info-custom"></i>Jadwal Uji Kompetensi 2026</h5>
                        
                        <div class="row g-3">
                            <!-- Kenaikan Jenjang -->
                            <div class="col-md-6">
                                <div class="hero-schedule-item">
                                    <span class="badge badge-custom mb-3">KENAIKAN JENJANG</span>
                                    <p class="mb-2 small"><strong>Pendaftaran:</strong><br><span class="text-muted"><?= htmlspecialchars($data_utama['kj_tgl_daftar']) ?></span></p>
                                    <p class="mb-0 small"><strong>Selesai:</strong><br><span class="text-muted"><?= htmlspecialchars($data_utama['kj_tgl_ujian']) ?></span></p>
                                </div>
                            </div>
                            <!-- Perpindahan Jabatan -->
                            <div class="col-md-6">
                                <div class="hero-schedule-item">
                                    <span class="badge bg-dark mb-3">PERPINDAHAN JABATAN</span>
                                    <p class="mb-2 small"><strong>Pendaftaran:</strong><br><span class="text-muted"><?= htmlspecialchars($data_utama['pj_tgl_daftar']) ?></span></p>
                                    <p class="mb-0 small"><strong>Selesai:</strong><br><span class="text-muted"><?= htmlspecialchars($data_utama['pj_tgl_ujian']) ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="mb-2 fw-bold text-dark">JFPKP</p>
            <p class="mb-0 small">&copy; 2026 - Kementerian Perumahan dan Kawasan Permukiman. Seluruh Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>