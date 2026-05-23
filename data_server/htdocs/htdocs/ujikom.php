<?php
// ujikom.php - Halaman Manajemen Uji Kompetensi

// --- AUTH GUARD DITAMBAHKAN DI SINI ---
// File ini akan memulai sesi (jika belum) dan memastikan pengguna telah login.
require_once 'auth_guard.php'; 

require_once 'koneksi.php'; // Memuat koneksi dan variabel nama tabel

$message = ''; 
$is_error = false;

// --- PENGGUNAAN DATA SESSION DARI auth_guard.php ---
// Menggunakan variabel global yang disiapkan oleh auth_guard.php
$user_data = [
    'nama' => $user_nama_sesi ?? 'Pengguna JF', 
    'role' => $user_role_sesi ?? 'User', 
    'email' => $user_email_sesi ?? 'user@instansi.go.id', 
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023' // join_date mungkin harus disiapkan di login.php
];

// --- FUNGSI HELPER ---
function get_hasil_badge_adminlte($hasil) {
    $hasil_lower = strtolower(trim($hasil));
    if (strpos($hasil_lower, 'lulus') !== false) {
        return 'success'; // Hijau
    } elseif (strpos($hasil_lower, 'belum') !== false || strpos($hasil_lower, 'proses') !== false || strpos($hasil_lower, 'menunggu') !== false) {
        return 'info'; // Biru/Biru Muda
    } else {
        return 'danger'; // Merah (Tidak Lulus/Gagal)
    }
}


// --- LOGIKA AMBIL DATA HASIL UJI KOMPETENSI ---
// Menggunakan JOIN untuk menggabungkan nama lengkap dari detailpegawai dan hasil dari data_ujikom.
$NAMA_TABEL_UJIKOM = "data_ujikom"; // Asumsi nama tabel uji kom
$NAMA_TABEL_PEGAWAI = "detailpegawai"; // Asumsi nama tabel pegawai

$sql_select_hasil = "
    SELECT 
        ujikom.id, 
        pegawai.nama_lengkap_gelar, 
        ujikom.nip, 
        pegawai.instansi,
        ujikom.tanggal_uji, 
        ujikom.hasil 
    FROM 
        {$NAMA_TABEL_UJIKOM} ujikom
    JOIN 
        {$NAMA_TABEL_PEGAWAI} pegawai ON ujikom.nip = pegawai.nip
    ORDER BY 
        ujikom.tanggal_uji DESC, ujikom.id DESC
";
$res_data_ujikom = @mysqli_query($conn, $sql_select_hasil);
$data_ujikom_gagal = !$res_data_ujikom; 

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uji Kompetensi | Instansi Pembina JF</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
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
                    <li class="nav-item"><a href="ujikom.php" class="nav-link active"><i class="nav-icon fas fa-clipboard-list"></i><p>Uji Kompetensi</p></a></li>
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
                        <h1 class="m-0"><i class="fas fa-clipboard-list"></i> Manajemen Uji Kompetensi</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Uji Kompetensi</li>
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
                    <div class="col-md-12">
                         <div class="card card-warning">
                              <div class="card-header">
                                   <h3 class="card-title"><i class="fas fa-plus-circle"></i> Input Hasil Uji Kompetensi / Pendaftaran Baru</h3>
                              </div>
                              <form method="POST" action="ujikom.php">
                                   <div class="card-body row">
                                        <div class="form-group col-md-4">
                                             <label for="nip">NIP Peserta</label>
                                             <input type="text" id="nip" name="nip" class="form-control" required placeholder="Cari NIP untuk mengisi data otomatis">
                                        </div>
                                        <div class="form-group col-md-4">
                                             <label for="tanggal_uji">Tanggal Uji</label>
                                             <input type="date" id="tanggal_uji" name="tanggal_uji" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-4">
                                             <label for="hasil">Hasil Uji</label>
                                             <select id="hasil" name="hasil" class="form-control" required>
                                                  <option value="">-- Pilih --</option>
                                                  <option value="Lulus">Lulus</option>
                                                  <option value="Tidak Lulus">Tidak Lulus</option>
                                                  <option value="Menunggu Nilai">Menunggu Nilai</option>
                                             </select>
                                        </div>
                                   </div>
                                   <div class="card-footer">
                                        <button type="submit" name="submit_hasil" class="btn btn-warning"><i class="fas fa-upload"></i> Simpan Hasil</button>
                                   </div>
                              </form>
                         </div>
                    </div>

                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-list-ul"></i> Daftar Hasil Uji Kompetensi</h3>
                            </div>
                            <div class="card-body table-responsive">
                                <table id="tabelUjikom" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Lengkap dan Gelar</th>
                                            <th>NIP</th>
                                            <th>Instansi</th>
                                            <th>Tanggal Uji</th>
                                            <th>Hasil</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($data_ujikom_gagal): ?>
                                             <tr><td colspan='7' class="text-center text-danger font-weight-bold">Gagal memuat data. Periksa koneksi atau pastikan tabel '<?php echo $NAMA_TABEL_UJIKOM; ?>' dan '<?php echo $NAMA_TABEL_PEGAWAI; ?>' memiliki kolom 'nip' yang terhubung. Error: <?php echo mysqli_error($conn); ?></td></tr>
                                        <?php elseif (mysqli_num_rows($res_data_ujikom) > 0): ?>
                                             <?php $no = 1; while($row = mysqli_fetch_assoc($res_data_ujikom)): ?>
                                             <tr>
                                                 <td><?php echo $no++; ?></td>
                                                 <td><?php echo htmlspecialchars($row['nama_lengkap_gelar']); ?></td>
                                                 <td><?php echo htmlspecialchars($row['nip']); ?></td>
                                                 <td><?php echo htmlspecialchars($row['instansi']); ?></td>
                                                 <td><?php echo date('d-m-Y', strtotime($row['tanggal_uji'])); ?></td>
                                                 <td><span class="badge badge-<?php echo get_hasil_badge_adminlte($row['hasil']); ?>"><?php echo htmlspecialchars($row['hasil']); ?></span></td>
                                                 <td>
                                                     <a href="edit_ujikom.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                                                 </td>
                                             </tr>
                                             <?php endwhile; ?>
                                        <?php else: ?>
                                             <tr><td colspan='7' class="text-center text-muted">Belum ada hasil uji kompetensi yang terdaftar.</td></tr>
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
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
    $(function () {
        // Inisialisasi DataTables
        $("#tabelUjikom").DataTable({
            "responsive": true, 
            "autoWidth": false,
            "pageLength": 10,
        });
    });
</script>

<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>
</body>
</html>