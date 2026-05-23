<?php
// pengaturan.php - Halaman Pengaturan (Profil & Sistem)

// --- AUTH GUARD DITAMBAHKAN DI SINI ---
// File ini akan memulai sesi (jika belum) dan memastikan pengguna telah login.
require_once 'auth_guard.php'; 

// Memuat koneksi database. Walaupun tidak ada query update, 
// ini menjaga konsistensi dan koneksi tetap dibuka jika diperlukan.
require_once 'koneksi.php'; 

$message = ''; // Variabel untuk menyimpan pesan sukses/error
$is_error = false;

// --- PENGGUNAAN DATA SESSION DARI auth_guard.php ---
// Mengganti dummy data dengan variabel global yang disiapkan oleh auth_guard.php
$user_data = [
    'nama' => $user_nama_sesi ?? 'Pengguna JF', 
    'role' => $user_role_sesi ?? 'User', 
    'email' => $user_email_sesi ?? 'user@instansi.go.id', 
    // Asumsi auth_guard.php juga menyiapkan data lain di sesi
    'nip' => $_SESSION['nip'] ?? 'NIP Tidak Diketahui', 
    'unit_kerja' => $_SESSION['unit_kerja'] ?? 'Unit Kerja Tidak Diketahui',
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023' // join_date mungkin harus disiapkan di login.php
];


// --- LOGIKA UPDATE DATA (Saat ini dikosongkan untuk menyederhanakan) ---
// Note: Perlu implementasi database yang sesungguhnya di sini
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['update_profil'])) {
        // Placeholder: Logika untuk memproses update profil
        $message = "✅ Perubahan profil berhasil disimpan! (Placeholder)";
        $is_error = false;
        // JANGAN KOSONGKAN $_POST agar data form tetap terisi saat sukses
    } elseif (isset($_POST['update_sistem'])) {
        // Placeholder: Logika untuk memproses update pengaturan sistem
        $message = "✅ Pengaturan sistem berhasil diperbarui! (Placeholder)";
        $is_error = false;
        // JANGAN KOSONGKAN $_POST agar data form tetap terisi saat sukses
    }
    
    // Simulasikan pesan error jika ada validasi yang gagal
    // if (empty($input_penting)) { $is_error = true; $message = "Data tidak lengkap."; }
}

// --- LOGIKA AMBIL DATA (DATA DUMMY LANJUTAN) ---
// Data yang akan ditampilkan di form pengaturan sistem (simulasi)
$current_nama_aplikasi = "SI JF Penata Kelola Perumahan";
$current_versiapp = "1.0.3";
$current_status_pemeliharaan = "Nonaktif"; // Bisa 'Aktif' atau 'Nonaktif'

// Jika form disubmit, ambil nilai dari POST untuk ditampilkan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['nama_aplikasi'])) $current_nama_aplikasi = htmlspecialchars($_POST['nama_aplikasi']);
    if (isset($_POST['versiapp'])) $current_versiapp = htmlspecialchars($_POST['versiapp']);
    if (isset($_POST['status_pemeliharaan'])) $current_status_pemeliharaan = htmlspecialchars($_POST['status_pemeliharaan']);
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan | Instansi Pembina JF</title>

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
                    <li class="nav-item"><a href="peraturan.php" class="nav-link"><i class="nav-icon fas fa-book"></i><p>Peraturan</p></a></li>
                    <li class="nav-item"><a href="pengaturan.php" class="nav-link active"><i class="nav-icon fas fa-cog"></i><p>Pengaturan</p></a></li>
                </ul>
            </nav>
            </div>
        </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-cog"></i> Pengaturan Aplikasi & Profil</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right" style="background-color: transparent;">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Pengaturan</li>
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

                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-circle"></i> Profil Pengguna</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <form method="POST" action="pengaturan.php">
                                <input type="hidden" name="update_profil" value="1">
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <img src="https://i.pravatar.cc/120?u=<?php echo urlencode($user_data['email']); ?>" class="img-circle elevation-2" alt="User Image">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="nama_user">Nama Lengkap</label>
                                        <input type="text" id="nama_user" name="nama_user" class="form-control" value="<?php echo htmlspecialchars($user_data['nama']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email_user">Email (Tidak dapat diubah)</label>
                                        <input type="email" id="email_user" name="email_user" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="nip_user">NIP</label>
                                        <input type="text" id="nip_user" name="nip_user" class="form-control" value="<?php echo htmlspecialchars($user_data['nip']); ?>" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="unit_kerja_user">Unit Kerja</label>
                                        <input type="text" id="unit_kerja_user" name="unit_kerja_user" class="form-control" value="<?php echo htmlspecialchars($user_data['unit_kerja']); ?>" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label for="role_user">Peran (Role)</label>
                                        <input type="text" id="role_user" name="role_user" class="form-control" value="<?php echo htmlspecialchars($user_data['role']); ?>" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="password_baru">Password Baru</label>
                                        <input type="password" id="password_baru" name="password_baru" class="form-control" placeholder="Isi hanya jika ingin mengubah password">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-tools"></i> Pengaturan Sistem Aplikasi</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <form method="POST" action="pengaturan.php">
                                <input type="hidden" name="update_sistem" value="1">
                                <div class="card-body">
                                    
                                    <div class="form-group">
                                        <label for="nama_aplikasi">Nama Aplikasi</label>
                                        <input type="text" id="nama_aplikasi" name="nama_aplikasi" class="form-control" value="<?php echo htmlspecialchars($current_nama_aplikasi); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="versiapp">Versi Aplikasi</label>
                                        <input type="text" id="versiapp" name="versiapp" class="form-control" value="<?php echo htmlspecialchars($current_versiapp); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="status_pemeliharaan">Status Pemeliharaan (Maintenance Mode)</label>
                                        <select id="status_pemeliharaan" name="status_pemeliharaan" class="form-control">
                                            <option value="Nonaktif" <?php echo ($current_status_pemeliharaan == 'Nonaktif' ? 'selected' : ''); ?>>Nonaktif</option>
                                            <option value="Aktif" <?php echo ($current_status_pemeliharaan == 'Aktif' ? 'selected' : ''); ?>>Aktif</option>
                                        </select>
                                    </div>
                                    <p class="text-muted small">Aktifkan untuk memblokir akses pengguna non-admin ke aplikasi.</p>

                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-warning"><i class="fas fa-sync-alt"></i> Update Sistem</button>
                                </div>
                            </form>
                        </div>
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