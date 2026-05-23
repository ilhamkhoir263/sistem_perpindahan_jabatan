<?php
// FILE: peraturan.php - Halaman Daftar Peraturan (AdminLTE)

// --- PENTING: DEKLARASI VARIABEL UNTUK HIGHLIGHT MENU SIDEBAR ---
$page = 'peraturan'; // Mengaktifkan highlight menu "Peraturan"
$sub_page = '';      
// -------------------------------------------------------------

// --- ASUMSI FILE PENDUKUNG ---
require_once 'auth_guard.php'; 
require_once 'koneksi.php'; 

// --- DEKLARASI VARIABEL & ASUMSI ---
$message = ''; 
$is_error = false;
// BARIS YANG DIPERBAIKI: Mengganti nama tabel menjadi yang benar
$NAMA_TABEL_PERATURAN = "peraturan_jf_pkp"; 

// Menggunakan variabel global yang disiapkan oleh auth_guard.php
$user_data = [
    'nama' => $user_nama_sesi ?? 'Pengguna JF', 
    'role' => $user_role_sesi ?? 'User', 
    'email' => $user_email_sesi ?? 'user@instansi.go.id', 
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023'
];

// --- LOGIKA FORM UPLOAD PERATURAN (ASUMSI HANYA ADMIN YANG BISA) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_peraturan'])) {
    
    // 1. Ambil dan Bersihkan Data dari Form
    $judul_peraturan = mysqli_real_escape_string($conn, $_POST['judul_peraturan']);
    $nomor_peraturan = mysqli_real_escape_string($conn, $_POST['nomor_peraturan']);
    $tahun = mysqli_real_escape_string($conn, $_POST['tahun']);
    $link_dokumen = mysqli_real_escape_string($conn, $_POST['link_dokumen']);
    $tanggal_input = date('Y-m-d H:i:s'); 

    // Lakukan validasi dasar
    if (empty($judul_peraturan) || empty($nomor_peraturan) || empty($link_dokumen)) {
        $is_error = true;
        $message = "Semua kolom wajib harus diisi.";
    } else {
        // 2. Query untuk Menyimpan Data menggunakan Prepared Statement
        $sql_insert = "INSERT INTO {$NAMA_TABEL_PERATURAN} (
                             judul, nomor_peraturan, tahun, link_dokumen, tanggal_input
                           ) VALUES (?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql_insert);
        
        if ($stmt) {
            // ASUMSI: Nama kolom di database adalah 'judul', 'nomor_peraturan', 'tahun', 'link_dokumen', 'tanggal_input'
            mysqli_stmt_bind_param($stmt, "sssss", 
                $judul_peraturan, $nomor_peraturan, $tahun, 
                $link_dokumen, $tanggal_input);
            
            if (mysqli_stmt_execute($stmt)) {
                $is_error = false;
                $message = "✅ Peraturan **{$nomor_peraturan}** berhasil ditambahkan.";
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

// --- LOGIKA AMBIL DATA PERATURAN (Baris 72 diperbaiki secara otomatis oleh perubahan di atas) ---
$sql_select = "SELECT id, judul, nomor_peraturan, tahun, link_dokumen FROM {$NAMA_TABEL_PERATURAN} ORDER BY tahun DESC, nomor_peraturan DESC";
$res_data_peraturan = @mysqli_query($conn, $sql_select);
$data_peraturan_gagal = !$res_data_peraturan; 

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
    
    <?php include 'template/sidebar.php'; ?> 

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-book"></i> Peraturan Jabatan Fungsional</h1>
                    </div>
                    <div class="col-sm-6 text-right">
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

                <?php 
                // Asumsi hanya user dengan role 'Admin' atau 'Pimpinan' yang bisa upload peraturan
                if (($user_data['role'] ?? '') == 'Admin' || ($user_data['role'] ?? '') == 'Pimpinan'): 
                ?>
                <div class="card card-info collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-upload"></i> Tambah Peraturan Baru</h3>
                        <div class="card-tools">
                             <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                 <i class="fas fa-plus"></i>
                             </button>
                        </div>
                    </div>
                    <form method="POST" action="peraturan.php">
                        <div class="card-body">
                            
                            <div class="form-group">
                                <label for="judul_peraturan">Judul Peraturan</label>
                                <input type="text" id="judul_peraturan" name="judul_peraturan" class="form-control" required value="<?php echo htmlspecialchars($_POST['judul_peraturan'] ?? ''); ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="nomor_peraturan">Nomor Peraturan (Contoh: Permenpan RB No. 7 Tahun 2023)</label>
                                        <input type="text" id="nomor_peraturan" name="nomor_peraturan" class="form-control" required value="<?php echo htmlspecialchars($_POST['nomor_peraturan'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                     <div class="form-group">
                                         <label for="tahun">Tahun</label>
                                         <input type="number" id="tahun" name="tahun" class="form-control" required value="<?php echo htmlspecialchars($_POST['tahun'] ?? date('Y')); ?>">
                                     </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="link_dokumen">Link Dokumen (URL Resmi/Google Drive)</label>
                                <input type="url" id="link_dokumen" name="link_dokumen" class="form-control" required placeholder="Contoh: https://jdih.go.id/peraturan-resmi/link-dokumen" value="<?php echo htmlspecialchars($_POST['link_dokumen'] ?? ''); ?>">
                            </div>
                            

                        </div>
                        <div class="card-footer">
                            <button type="submit" name="submit_peraturan" class="btn btn-info"><i class="fas fa-save"></i> Simpan Peraturan</button>
                        </div>
                    </form>
                </div>
                <?php endif; // Akhir dari form upload (hanya untuk Admin/Pimpinan) ?>


                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list-alt"></i> Daftar Peraturan yang Tersedia</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 5%">No</th>
                                    <th style="width: 20%">Nomor & Tahun</th>
                                    <th style="width: 65%">Judul Peraturan</th>
                                    <th style="width: 10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($data_peraturan_gagal) {
                                    // Pesan error di sini akan lebih spesifik jika ada masalah lain setelah nama tabel benar
                                    $error_msg = isset($conn) ? mysqli_error($conn) : 'Koneksi database belum diinisiasi.';
                                    echo "<tr><td colspan='4' class='text-center text-danger font-weight-bold'>Gagal memuat daftar peraturan. Error: " . $error_msg . "</td></tr>";
                                } elseif (@mysqli_num_rows($res_data_peraturan) > 0) {
                                    $no = 1;
                                    while($row = @mysqli_fetch_assoc($res_data_peraturan)) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nomor_peraturan']) . " Tahun " . htmlspecialchars($row['tahun']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['judul']) . "</td>";
                                        echo "<td><a href='" . htmlspecialchars($row['link_dokumen']) . "' target='_blank' class='btn btn-sm btn-outline-info' title='Lihat Dokumen'><i class='fas fa-external-link-alt'></i> Lihat</a></td>";
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
            
        </div>
        <strong>© <?php echo date('Y'); ?> Instansi Pembina Jabatan Fungsional Penata Kelola Perumahan</strong> — Semua Hak Dilindungi.
    </footer>
</div>

    <?php
    // --- PANGGIL FOOTER (Ini akan menutup section.content, content-wrapper, dan tag body/html) ---
    include 'template/footer.php';
    ?>

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