<?php
// FILE: index.php - Halaman Dashboard

// CATATAN: session_start() sudah dilakukan di auth_guard.php, tapi akan aman
// jika session_start() tetap dipanggil sebelum auth_guard.php jika auth_guard.php
// tidak menangani session_start() sendiri. Namun, karena auth_guard.php Anda
// sudah memanggilnya dengan cek session_status(), kita cukup require_once.

// --- AUTH GUARD DITAMBAHKAN DI SINI ---
require_once 'auth_guard.php'; 

// CATATAN: Logika pengecekan sesi lama (if (!isset($_SESSION['user_id'])) { ... })
// telah dihapus karena sudah ditangani sepenuhnya di auth_guard.php.

// Memuat koneksi database dan semua variabel nama tabel
require_once 'koneksi.php'; 

// --- LOGIKA POPUP DARI SESSION (Login Sukses / Logout Sukses) ---
$popup_data = [];
if (isset($_SESSION['popup_message']) && isset($_SESSION['popup_type'])) {
    $popup_data = [
        'type' => $_SESSION['popup_type'],
        'message' => $_SESSION['popup_message'],
    ];
    // Hapus data session agar pop-up tidak muncul lagi saat di-refresh
    unset($_SESSION['popup_message']);
    unset($_SESSION['popup_type']);
}
// --------------------------------------------------------------

// --- PENGGUNAAN DATA SESSION ---
// Menggunakan variabel sesi yang sudah disiapkan di auth_guard.php
$user_data = [
    // Menggunakan variabel global yang disiapkan di auth_guard.php
    'nama' => $user_nama_sesi ?? 'Pengguna JF', 
    'role' => $user_role_sesi ?? 'User', 
    'email' => $user_email_sesi ?? 'user@instansi.go.id', 
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023' // join_date mungkin harus disiapkan di login.php
];

// --- FUNGSI HELPER ---
function get_status_class_adminlte($status) {
    $status_lower = strtolower(trim($status));
    if (strpos($status_lower, 'sesuai') !== false || strpos( $status_lower, 'valid') !== false) {
        return 'success'; // Hijau
    } elseif (strpos($status_lower, 'perbaikan') !== false || strpos($status_lower, 'tolak') !== false) {
        return 'warning'; // Kuning/Oranye
    } else {
        return 'info'; // Biru (Dalam Penilaian/Default)
    }
}

// --- LOGIKA AMBIL DATA REKOMENDASI FORMASI ---
// Menggunakan variabel nama tabel dari koneksi.php (misal: tabel_pengajuan_rekomendasi)
$NAMA_TABEL_FORMASI = $NAMA_TABEL_FORMASI ?? 'rekomendasiformasi'; // Fallback
$sql_formasi = "
    SELECT 
        id, pengusul, instansi, status 
    FROM 
        {$NAMA_TABEL_FORMASI} 
    ORDER BY 
        id DESC 
    LIMIT 5
";
$res_formasi = isset($conn) ? @mysqli_query($conn, $sql_formasi) : false;
$data_formasi_gagal = !$res_formasi; 

// --- LOGIKA AMBIL DATA PEGAWAI (UJI KOMPETENSI) ---
// Menggunakan variabel nama tabel dari koneksi.php (misal: detailpegawai)
$NAMA_TABEL_PEGAWAI = $NAMA_TABEL_PEGAWAI ?? 'detailpegawai'; // Fallback
$sql_pegawai = "
    SELECT 
        nip, nama_lengkap_gelar, instansi, unit_organisasi, unit_kerja 
    FROM 
        {$NAMA_TABEL_PEGAWAI} 
    ORDER BY 
        id DESC 
    LIMIT 7
";
$res_pegawai = isset($conn) ? @mysqli_query($conn, $sql_pegawai) : false;
$data_pegawai_gagal = !$res_pegawai;

$page = 'dashboard';
$page_title = 'Dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Instansi Pembina JF PKP</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <style>
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
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link active">Dashboard</span>
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
                    <li class="nav-item"><a href="index.php" class="nav-link active"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="database.php" class="nav-link"><i class="nav-icon fas fa-database"></i><p>Database</p></a></li>
                    <li class="nav-item"><a href="ujikom.php" class="nav-link"><i class="nav-icon fas fa-clipboard-list"></i><p>Uji Kompetensi</p></a></li>
                    <li class="nav-item"><a href="rekomendasi.php" class="nav-link"><i class="nav-icon fas fa-chart-line"></i><p>Rekomendasi Formasi</p></a></li>
                    <li class="nav-item"><a href="peraturan.php" class="nav-link"><i class="nav-icon fas fa-book"></i><p>Peraturan</p></a></li>
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
                        <h1 class="m-0"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                        <p class="text-muted">Dashboard sistem informasi jabatan fungsional</p>
                    </div>
                    <div class="col-sm-6 text-right">
                        <button class="btn btn-primary"><i class="fas fa-user-plus"></i> Tambah User</button>
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                
                <div class="row">
                    </div>
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-chart-line"></i> Status Pengajuan Rekomendasi Formasi Terakhir</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped table-valign-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%">No</th>
                                            <th style="width: 25%">Pengusul</th>
                                            <th style="width: 40%">Instansi</th>
                                            <th style="width: 20%">Status</th>
                                            <th style="width: 10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($data_formasi_gagal): ?>
                                            <tr><td colspan='5' class="text-center text-danger font-weight-bold">Gagal memuat data formasi. Error: <?php echo isset($conn) ? mysqli_error($conn) : 'Koneksi database tidak tersedia.'; ?></td></tr>
                                        <?php elseif (@mysqli_num_rows($res_formasi) > 0): ?>
                                            <?php $no = 1; while($row = mysqli_fetch_assoc($res_formasi)): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($row['pengusul']); ?></td>
                                                <td><?php echo htmlspecialchars($row['instansi']); ?></td>
                                                <td><span class="badge badge-<?php echo get_status_class_adminlte($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                                <td><a href="rekomendasi.php?detail_id=<?php echo htmlspecialchars($row['id']); ?>" class="text-muted"><i class="fas fa-eye"></i> detail</a></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan='5' class="text-center text-muted">Belum ada pengajuan rekomendasi formasi.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Pengajuan Usulan Peserta Uji Kompetensi Terakhir</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-head-fixed text-nowrap table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%">No</th>
                                            <th>Nama Lengkap dan Gelar</th>
                                            <th>NIP</th>
                                            <th>Instansi</th>
                                            <th>Nama Unit Organisasi</th>
                                            <th>Nama Unit Kerja</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($data_pegawai_gagal): ?>
                                            <tr><td colspan='6' class="text-center text-danger font-weight-bold">Gagal memuat data pegawai. Error: <?php echo isset($conn) ? mysqli_error($conn) : 'Koneksi database tidak tersedia.'; ?></td></tr>
                                        <?php elseif (@mysqli_num_rows($res_pegawai) > 0): ?>
                                            <?php $no = 1; while($row = mysqli_fetch_assoc($res_pegawai)): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_lengkap_gelar']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nip']); ?></td>
                                                <td><?php echo htmlspecialchars($row['instansi']); ?></td>
                                                <td><?php echo htmlspecialchars($row['unit_organisasi']); ?></td>
                                                <td><?php echo htmlspecialchars($row['unit_kerja']); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan='6' class="text-center text-muted">Belum ada data peserta uji kompetensi.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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


<?php if (!empty($popup_data)): ?>
<div class="modal fade" id="sessionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-<?php echo ($popup_data['type'] == 'success' ? 'success' : ($popup_data['type'] == 'info' ? 'info' : 'warning')); ?>">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> Notifikasi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <p><?php echo $popup_data['message']; ?></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if ($('#sessionModal').length) {
            $('#sessionModal').modal('show');
        }
    });
</script>
<?php endif; ?>
<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>
</body>
</html>