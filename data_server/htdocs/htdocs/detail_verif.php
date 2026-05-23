<?php
// FILE: detail_verif.php - Halaman Penilaian Persyaratan Uji Kompetensi (AdminLTE)

// --- AUTH GUARD DITAMBAHKAN DI SINI ---
// File ini akan memulai sesi (jika belum) dan memastikan pengguna telah login.
require_once 'auth_guard.php'; 

// Memuat koneksi database.
require_once 'koneksi.php'; 

// --- PENGGUNAAN DATA SESSION DARI auth_guard.php ---
// Menggunakan variabel global yang disiapkan oleh auth_guard.php
// Jika variabel sesi belum didefinisikan, gunakan nilai default yang aman ('Pengguna', 'User', dsb.)
$user_data = [
    'nama' => $user_nama_sesi ?? 'Pengguna JF', 
    'role' => $user_role_sesi ?? 'User', 
    'email' => $user_email_sesi ?? 'user@instansi.go.id', 
    // join_date biasanya disiapkan di login.php atau auth_guard.php
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023' 
];

// --- PENGATURAN ID KOLOM UTAMA & DUMMY DATA (WAJIB DISESUAIKAN) ---
// PENTING: GANTI 'id' DENGAN NAMA KOLOM PRIMARY KEY YANG BENAR DI TABEL detailpegawai ANDA!
$NAMA_KOLOM_ID_PEGAWAI = "id"; 
$NAMA_TABEL_PEGAWAI = "detailpegawai"; 

// --- VARIABEL HALAMAN ---
$page = 'ujikom'; // Untuk mengaktifkan menu Uji Kompetensi
$page_title = 'Verifikasi Peserta Uji Kompetensi';

$pegawai_id = $_GET['id'] ?? null;
$pegawai_data = null;
$error_message = '';
$message = '';
$nama_pegawai = 'N/A';
$nip_pegawai = 'N/A';
$verifikasi_data = []; // Untuk menyimpan status verifikasi yang sudah ada/default

// Pastikan koneksi tersedia
if (!isset($conn)) {
    $error_message = "❌ Gagal terhubung ke database. Cek file koneksi.php.";
    $pegawai_id = null; // Memaksa agar tidak menjalankan query
}

if (!$pegawai_id) {
    $error_message = $error_message ?: "❌ ID Peserta tidak ditemukan di URL. Mohon pastikan tautan sudah benar.";
} else {
    // 1. AMBIL DATA PESERTA DARI TABEL PEGAWAI
    $safe_id = mysqli_real_escape_string($conn, $pegawai_id);
    
    $sql_pegawai = "SELECT * FROM {$NAMA_TABEL_PEGAWAI} WHERE {$NAMA_KOLOM_ID_PEGAWAI} = '{$safe_id}'";
    $result_pegawai = mysqli_query($conn, $sql_pegawai);

    if ($result_pegawai && mysqli_num_rows($result_pegawai) > 0) {
        $pegawai_data = mysqli_fetch_assoc($result_pegawai);
        
        $nama_pegawai = htmlspecialchars($pegawai_data['nama_lengkap'] ?? 'N/A');
        $nip_pegawai = htmlspecialchars($pegawai_data['nip'] ?? 'N/A');
        
        // Placeholder data verifikasi default (Ganti dengan data dari DB Anda)
        $verifikasi_data = [
            'p1_pns' => 'Ya', 'p2_pendidikan' => 'Ya', 'p3_bidang' => 'Ya', 
            'p4_pengalaman' => 'Ya', 'p5_usia' => 'Ya', 
            'd1_surat_usulan' => 'Ya', 'd2_rekomendasi_formasi' => 'Ya', 
            'd3_usulan_ujikom' => 'Ya', 'd4_portofolio' => 'Ya', 'd5_sk_cpns' => 'Ya',
            'd6_sk_pns' => 'Ya', 'd7_sk_pangkat' => 'Ya', 'd8_sk_jabatan' => 'Ya', 
            'd9_nilai_skp' => 'Ya', 'd10_ijazah' => 'Ya', 'd11_transkrip' => 'Ya', 
            'd12_integritas' => 'Ya', 'd13_bersedia' => 'Ya', 'd14_pengalaman_2th' => 'Ya', 
            'd15_rencana_penempatan' => 'Ya',
            'catatan_evaluasi' => 'Dokumen lengkap dan memenuhi persyaratan usia.'
        ];

    } else {
        $error_message = "❌ Data peserta dengan ID: **{$pegawai_id}** tidak ditemukan di tabel `{$NAMA_TABEL_PEGAWAI}`. Cek kembali ID dan nama kolom Primary Key di baris 33.";
    }
}

// --- LOGIKA UPDATE/SIMPAN VERIFIKASI (Handle POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_verifikasi']) && $pegawai_data) {
    
    // Ambil data dari form
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan_evaluasi'] ?? '');
    $p1_pns = mysqli_real_escape_string($conn, $_POST['p1_pns'] ?? 'Tidak');
    $p2_pendidikan = mysqli_real_escape_string($conn, $_POST['p2_pendidikan'] ?? 'Tidak');
    // Anda perlu mengambil semua input verifikasi lainnya di sini jika ingin menyimpannya ke DB

    // Simulasi penyimpanan
    $message = "✅ Verifikasi untuk NIP: {$nip_pegawai} berhasil disimulasikan disimpan! (Perlu implementasi DB)";
}

// Data daftar persyaratan (digunakan untuk loop)
$persyaratan_umum = [
    'p1_pns' => 'Pegawai berstatus Pegawai Negeri Sipil (PNS)',
    'p2_pendidikan' => 'Pendidikan terakhir pegawai minimal S-1/D4',
    'p3_bidang' => 'Pendidikan sesuai dengan bidang yang dibutuhkan',
    'p4_pengalaman' => 'Pengalaman kerja bidang Perumahan minimal 2 tahun',
    'p5_usia' => 'Usia pegawai memenuhi batas maksimal<br>a. ≤53 tahun (Ahli Pertama/Muda)<br>b. ≤55 tahun (Ahli Madya)<br>c. ≤60 tahun (Ahli Utama)',
];

$dokumen_persyaratan = [
    'd1_surat_usulan' => 'Surat Usulan Perpindahan Jabatan ditandatangani pejabat tinggi pratama',
    'd2_rekomendasi_formasi' => 'Salinan Surat Penetapan atau Rekomendasi Formasi resmi',
    'd3_usulan_ujikom' => 'Surat Usulan Uji Kompetensi ditandatangani pejabat berwenang',
    'd4_portofolio' => 'Daftar Riwayat Hidup/Portofolio terbaru memuat pengalaman kerja relevan',
    'd5_sk_cpns' => 'Salinan SK CPNS',
    'd6_sk_pns' => 'Salinan SK PNS',
    'd7_sk_pangkat' => 'SK Pangkat/Golongan terakhir',
    'd8_sk_jabatan' => 'SK Jabatan terakhir',
    'd9_nilai_skp' => 'Nilai SKP dua tahun terakhir minimal “Baik”',
    'd10_ijazah' => 'Salinan Ijazah',
    'd11_transkrip' => 'Transkrip Nilai',
    'd12_integritas' => 'Surat Pernyataan Integritas & Moralitas Baik',
    'd13_bersedia' => 'Surat Pernyataan Bersedia Diangkat dalam Jafung',
    'd14_pengalaman_2th' => 'Surat Pernyataan Pengalaman Tugas minimal 2 tahun',
    'd15_rencana_penempatan' => 'Surat Keterangan Rencana Penempatan dari pejabat berwenang',
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?> | AdminLTE</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        /* Styling tambahan agar tabel detail tidak terlalu lebar */
        .detail-table td:first-child { width: 70%; }
        .detail-table td:nth-child(2) { width: 150px; text-align: center; }
        .detail-table td:nth-child(3) { width: 100px; text-align: center; }
        
        /* Tambahan styling untuk Brand/Logo */
        .brand-link { background-color: #111827; }
        .brand-link .logo-pupr i { color: #0f62fe; }
        .logo-pupr { background: #fff; padding: 4px; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 50%;}
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
             <li class="nav-item dropdown user-menu">
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
             <div class="logo-pupr brand-image img-circle elevation-3" style="opacity: .8"><i class="fas fa-building fa-lg"></i></div>
            <span class="brand-text font-weight-light">Instansi Pembina JF</span>
        </a>

        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item"><a href="index.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="database.php" class="nav-link"><i class="nav-icon fas fa-database"></i><p>Database</p></a></li>
                    <li class="nav-item"><a href="ujikom.php" class="nav-link <?php echo ($page == 'ujikom' ? 'active' : ''); ?>"><i class="nav-icon fas fa-clipboard-check"></i><p>Uji Kompetensi</p></a></li>
                    <li class="nav-item"><a href="rekomendasi.php" class="nav-link"><i class="nav-icon fas fa-file-alt"></i><p>Rekomendasi Formasi</p></a></li>
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
                        <h1 class="m-0"><?php echo $page_title; ?></h1>
                        <span class="text-muted">Penilaian Persyaratan dan Dokumen</span>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="ujikom.php">Uji Kompetensi</a></li>
                            <li class="breadcrumb-item active">Verifikasi</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                
                <?php 
                // Tampilkan pesan error atau sukses
                if ($error_message) {
                    echo "<div class='alert alert-danger alert-dismissible'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button><i class='icon fas fa-ban'></i> " . $error_message . "</div>";
                }
                if ($message) {
                    echo "<div class='alert alert-success alert-dismissible'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button><i class='icon fas fa-check'></i> " . $message . "</div>";
                }
                
                // Tampilkan formulir hanya jika data peserta ditemukan
                if ($pegawai_data):
                ?>

                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-tag"></i> Data Peserta</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="detail_verif.php?id=<?php echo htmlspecialchars($pegawai_id); ?>">
                            <div class="form-group row">
                                <label for="nama_pegawai" class="col-sm-2 col-form-label">Nama</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" value="<?php echo $nama_pegawai; ?>" readonly>
                                </div>
                                <label for="nip_pegawai" class="col-sm-2 col-form-label">NIP</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" value="<?php echo $nip_pegawai; ?>" readonly>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <h4><i class="fas fa-check-circle text-primary"></i> A. Persyaratan Umum</h4>
                                    <div class="float-right mb-2">
                                        <button type="submit" name="submit_verifikasi" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Verifikasi</button>
                                    </div>
                                    <table class="table table-bordered detail-table">
                                        <thead>
                                            <tr class="bg-light">
                                                <th>Persyaratan</th>
                                                <th class="text-center">Dokumen</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($persyaratan_umum as $key => $label): ?>
                                            <tr>
                                                <td><?php echo $no++; ?>. <?php echo $label; ?></td>
                                                <td><a href="#" target="_blank" class="btn btn-sm btn-success"><i class="fas fa-file-alt"></i> Buka Dokumen</a></td>
                                                <td>
                                                    <select name="<?php echo $key; ?>" class="form-control form-control-sm">
                                                        <option value="Ya" <?php echo (($verifikasi_data[$key] ?? '') == 'Ya' ? 'selected' : ''); ?>>Ya</option>
                                                        <option value="Tidak" <?php echo (($verifikasi_data[$key] ?? '') == 'Tidak' ? 'selected' : ''); ?>>Tidak</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <h4><i class="fas fa-file-upload text-primary"></i> B. Dokumen Persyaratan</h4>
                                    <div class="float-right mb-2">
                                        <button type="submit" name="submit_verifikasi" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Verifikasi</button>
                                    </div>
                                    <table class="table table-bordered detail-table">
                                        <thead>
                                            <tr class="bg-light">
                                                <th>Dokumen</th>
                                                <th class="text-center">File</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($dokumen_persyaratan as $key => $label): ?>
                                            <tr>
                                                <td><?php echo $no++; ?>. <?php echo htmlspecialchars($label); ?></td>
                                                <td><a href="#" target="_blank" class="btn btn-sm btn-success"><i class="fas fa-file-alt"></i> Buka Dokumen</a></td>
                                                <td>
                                                    <select name="<?php echo $key; ?>" class="form-control form-control-sm">
                                                        <option value="Ya" <?php echo (($verifikasi_data[$key] ?? '') == 'Ya' ? 'selected' : ''); ?>>Ya</option>
                                                        <option value="Tidak" <?php echo (($verifikasi_data[$key] ?? '') == 'Tidak' ? 'selected' : ''); ?>>Tidak</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <h4><i class="fas fa-comments text-primary"></i> C. Catatan Evaluasi</h4>
                                    <div class="form-group">
                                        <label for="catatan-evaluasi">Tambahkan Catatan Evaluasi</label>
                                        <textarea id="catatan-evaluasi" name="catatan_evaluasi" rows="4" class="form-control" placeholder="Masukkan catatan evaluasi di sini..."><?php echo htmlspecialchars($verifikasi_data['catatan_evaluasi'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" name="submit_verifikasi" class="btn btn-lg btn-block btn-info"><i class="fas fa-share-square"></i> Selesaikan Verifikasi dan Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <p class="lead">Data peserta tidak dapat dimuat.</p>
                            <a href="ujikom.php" class="btn btn-warning"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Uji Kompetensi</a>
                        </div>
                    </div>
                <?php endif; ?>

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

</body>
</html>
<?php 
// Tutup koneksi database
if (isset($conn)) {
    mysqli_close($conn); 
}
?>