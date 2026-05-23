<?php
// peraturan.php - Halaman Daftar Peraturan

// --- AUTH GUARD DITAMBAHKAN DI SINI ---
// File ini akan memulai sesi (jika belum) dan memastikan pengguna telah login.
require_once 'auth_guard.php'; 

require_once 'koneksi.php'; // Memuat $conn dan variabel nama tabel

// --- KOREKSI NAMA TABEL UNTUK DATABASE ANDA ---
$NAMA_TABEL_PERATURAN = "peraturan_jf_pkp"; 

$message = ''; // Variabel untuk menyimpan pesan sukses/error
$is_error = false;

// --- PENGGUNAAN DATA SESSION DARI auth_guard.php ---
// Mengganti dummy data dengan variabel global yang disiapkan oleh auth_guard.php
$user_data = [
    'nama' => $user_nama_sesi ?? 'Pengguna JF', 
    'role' => $user_role_sesi ?? 'User', 
    'email' => $user_email_sesi ?? 'user@instansi.go.id', 
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023' // join_date mungkin harus disiapkan di login.php
];

// --- LOGIKA PENYIMPANAN DATA (HANDLE POST REQUEST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_peraturan'])) {
    
    // 1. Ambil dan Bersihkan Data dari Form
    $nomor_peraturan = mysqli_real_escape_string($conn, $_POST['nomor_peraturan']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $link_dokumen = mysqli_real_escape_string($conn, $_POST['link_dokumen']);

    // Lakukan validasi dasar
    if (empty($nomor_peraturan) || empty($judul) || empty($link_dokumen)) {
        $is_error = true;
        $message = "Semua kolom wajib harus diisi.";
    } else {
        // 2. Query untuk Menyimpan Data menggunakan Prepared Statement
        $sql_insert = "INSERT INTO {$NAMA_TABEL_PERATURAN} (
                             nomor_peraturan, judul, link_dokumen
                           ) VALUES (?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql_insert);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", 
                $nomor_peraturan, $judul, $link_dokumen);
            
            if (mysqli_stmt_execute($stmt)) {
                $is_error = false;
                $message = "✅ Data peraturan **{$nomor_peraturan}** berhasil disimpan.";
                // Kosongkan $_POST
                $_POST = [];
            } else {
                $is_error = true;
                $message = "❌ Gagal menyimpan data: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $is_error = true;
            $message = "❌ Gagal menyiapkan query: " . mysqli_error($conn);
        }
    }
}

// --- LOGIKA AMBIL DATA UNTUK TABEL DI BAWAH FORM ---

$sql_select = "SELECT id, nomor_peraturan, judul, link_dokumen FROM {$NAMA_TABEL_PERATURAN} ORDER BY id DESC";
$res_data_peraturan = @mysqli_query($conn, $sql_select);
$data_peraturan_gagal = !$res_data_peraturan; // True jika query gagal

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peraturan | Instansi Pembina JF</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        .hold-transition.sidebar-mini { margin: 0; } 
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
                    <img src="https://i.pravatar.cc/36?u=<?php echo urlencode($user_data['email']); ?>" class="user-image img-circle elevation-2" alt="User Image">
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($user_data['nama']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <li class="user-header bg-primary">
                        <img src="https://i.pravatar.cc/90?u=<?php echo urlencode($user_data['email']); ?>" class="img-circle elevation-2" alt="User Image">
                        <p>
                            <?php echo htmlspecialchars($user_data['nama']); ?> - <?php echo htmlspecialchars($user_data['role']); ?>
                            <small>Member since <?php echo htmlspecialchars($user_data['join_date']); ?></small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <a href="pengaturan.php" class="btn btn-default btn-flat">
                            <i class="fas fa-cog"></i> Pengaturan
                        </a>
                        <a href="logout.php" class="btn btn-default btn-flat float-right">
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
                    <li class="nav-item"><a href="index.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="database.php" class="nav-link"><i class="nav-icon fas fa-database"></i><p>Database</p></a></li>
                    <li class="nav-item"><a href="ujikom.php" class="nav-link"><i class="nav-icon fas fa-clipboard-list"></i><p>Uji Kompetensi</p></a></li>
                    <li class="nav-item"><a href="rekomendasi.php" class="nav-link"><i class="nav-icon fas fa-chart-line"></i><p>Rekomendasi Formasi</p></a></li>
                    <li class="nav-item"><a href="peraturan.php" class="nav-link active"><i class="nav-icon fas fa-book"></i><p>Peraturan</p></a></li>
                    <li class="nav-item"><a href="pengaturan.php" class="nav-link"><i class="nav-icon fas fa-cog"></i><p>Pengaturan</p></a></li>
                </ul>
            </nav>
            </div>
        </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-book"></i> Manajemen Peraturan</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right" style="background-color: transparent;">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Peraturan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">

                <?php if ($message): ?>
                    <div class="alert <?php echo $is_error ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Tambah Data Peraturan Baru</h3>
                    </div>
                    <form method="POST" action="peraturan.php">
                        <div class="card-body">
                            
                            <div class="form-group">
                                <label for="nomor_peraturan">Nomor Peraturan *</label>
                                <input type="text" id="nomor_peraturan" name="nomor_peraturan" class="form-control" required placeholder="Contoh: Permen No. 4 Tahun 2024" value="<?php echo htmlspecialchars($_POST['nomor_peraturan'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="judul">Judul Peraturan *</label>
                                <input type="text" id="judul" name="judul" class="form-control" required placeholder="Judul Lengkap Peraturan" value="<?php echo htmlspecialchars($_POST['judul'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="link_dokumen">Link Dokumen (URL) *</label>
                                <input type="url" id="link_dokumen" name="link_dokumen" class="form-control" required placeholder="Contoh: https://jdih.pu.go.id/doc/permen-4-2024.pdf" value="<?php echo htmlspecialchars($_POST['link_dokumen'] ?? ''); ?>">
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" name="submit_peraturan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Peraturan</button>
                        </div>
                    </form>
                </div>

                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list-ul"></i> Daftar Peraturan Jabatan Fungsional</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 5%">No</th>
                                    <th style="width: 25%">Nomor Peraturan</th>
                                    <th style="width: 60%">Judul</th>
                                    <th style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($data_peraturan_gagal) {
                                    echo "<tr><td colspan='4' class='text-center text-danger font-weight-bold'>❌ Gagal memuat daftar peraturan. Pastikan nama tabel di kode sudah benar. Error: " . (isset($conn) ? mysqli_error($conn) : 'Koneksi Gagal') . "</td></tr>";
                                } elseif (@mysqli_num_rows($res_data_peraturan) > 0) {
                                    $no = 1;
                                    while($row = mysqli_fetch_assoc($res_data_peraturan)) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nomor_peraturan']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['judul']) . "</td>";
                                        // Aksi: Link untuk mengunduh/melihat dokumen
                                        echo "<td><a href='" . htmlspecialchars($row['link_dokumen']) . "' target='_blank' class='btn btn-sm btn-info' title='Lihat Dokumen'><i class='fas fa-download'></i></a></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center text-muted'>Belum ada peraturan yang terdaftar.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
            </div>
        </section>
    </div>
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            Version 3.2
        </div>
        <strong>© <?php echo date('Y'); ?> Instansi Pembina Jabatan Fungsional Penata Kelola Perumahan</strong> — Semua Hak Dilindungi.
    </footer>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>
</body>
</html>