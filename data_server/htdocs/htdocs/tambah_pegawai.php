<?php
// tambah_pegawai.php - Halaman Form Tambah Pegawai
require_once 'auth_guard.php'; 
require_once 'koneksi.php'; 

// --- PENGATURAN HALAMAN ---
$page = 'database'; 
$page_title = 'Tambah Data Pegawai Baru'; 

// Menggunakan data sesi dari auth_guard.php
$user_data = [
    'nama' => $user_nama_sesi ?? 'Pengguna JF', 
    'role' => $user_role_sesi ?? 'User', 
    'email' => $user_email_sesi ?? 'user@instansi.go.id', 
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023'
];

// Tentukan nama tabel
$NAMA_TABEL_DETAIL_PEGAWAI = "detailpegawai"; 
$NAMA_TABEL_VERIFIKASI_UJIKOM = "verifikasi_ujikom";

$error_message = '';
$success_message = '';

// --- Logika Penanganan POST Form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil Data Dasar Pegawai
    $nip = trim($_POST['nip'] ?? '');
    $nama_lengkap_gelar = trim($_POST['nama_lengkap_gelar'] ?? '');
    $instansi = trim($_POST['instansi'] ?? ''); // Ini akan diisi dari filter Instansi (Pusat/Daerah)
    $unit_kerja = trim($_POST['unit_kerja'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $pangkat_gol = trim($_POST['pangkat_gol'] ?? '');

    // 2. Ambil Data Filter/Lokasi (untuk membantu menentukan Instansi)
    $jenis_instansi_form = trim($_POST['jenisInstansi'] ?? '');
    $nama_instansi_pusat = trim($_POST['namaInstansi'] ?? '');
    $provinsi_form = trim($_POST['provinsi'] ?? '');
    $kabupaten_form = trim($_POST['kabupaten'] ?? '');
    
    // Logika penentuan nilai kolom 'instansi'
    if ($jenis_instansi_form === 'pusat' && !empty($nama_instansi_pusat)) {
        $instansi_final = $nama_instansi_pusat;
    } elseif ($jenis_instansi_form === 'daerah') {
        if (!empty($kabupaten_form)) {
            $instansi_final = $kabupaten_form; // Asumsi Instansi Daerah = Kab/Kota
        } elseif (!empty($provinsi_form)) {
            $instansi_final = $provinsi_form; // Jika Kab/Kota kosong, pakai Provinsi
        } else {
            $instansi_final = $instansi; // Fallback ke input Instansi
        }
    } else {
        $instansi_final = $instansi; // Gunakan input Instansi manual jika filter tidak spesifik
    }

    // Validasi Sederhana
    if (empty($nip) || empty($nama_lengkap_gelar) || empty($instansi_final) || empty($unit_kerja) || empty($jabatan)) {
        $error_message = "Semua field bertanda (*) harus diisi.";
    } elseif (isset($conn)) {
        // Sanitasi input
        $nip_safe = mysqli_real_escape_string($conn, $nip);
        $nama_safe = mysqli_real_escape_string($conn, $nama_lengkap_gelar);
        $instansi_safe = mysqli_real_escape_string($conn, $instansi_final);
        $unit_kerja_safe = mysqli_real_escape_string($conn, $unit_kerja);
        $jabatan_safe = mysqli_real_escape_string($conn, $jabatan);
        $pangkat_safe = mysqli_real_escape_string($conn, $pangkat_gol);

        // --- 3. Cek Duplikasi NIP (Penting) ---
        $sql_check = "SELECT id FROM {$NAMA_TABEL_DETAIL_PEGAWAI} WHERE nip = '{$nip_safe}' LIMIT 1";
        $res_check = mysqli_query($conn, $sql_check);
        if (mysqli_num_rows($res_check) > 0) {
            $error_message = "Pegawai dengan NIP **{$nip}** sudah terdaftar.";
        } else {
             // --- 4. Mulai Transaksi (Pastikan kedua tabel terisi) ---
            mysqli_begin_transaction($conn);
            $berhasil = true;
            $pegawai_id = 0;

            // A. Insert ke detailpegawai
            $sql_insert_detail = "INSERT INTO {$NAMA_TABEL_DETAIL_PEGAWAI} (nip, nama_lengkap_gelar, instansi, unit_kerja, jabatan, pangkat_gol) VALUES ('{$nip_safe}', '{$nama_safe}', '{$instansi_safe}', '{$unit_kerja_safe}', '{$jabatan_safe}', '{$pangkat_safe}')";
            if (mysqli_query($conn, $sql_insert_detail)) {
                $pegawai_id = mysqli_insert_id($conn);
            } else {
                $berhasil = false;
                $error_message = "Gagal menyimpan data pegawai: " . mysqli_error($conn);
            }

            // B. Insert ke verifikasi_ujikom (Inisiasi dengan status default/kosong)
            // Hanya menginisiasi kolom 'pegawai_id' dan sisa kolom dibiarkan NULL (sesuai struktur tabel)
            if ($berhasil) {
                $sql_insert_verif = "INSERT INTO {$NAMA_TABEL_VERIFIKASI_UJIKOM} (pegawai_id) VALUES ({$pegawai_id})";
                if (!mysqli_query($conn, $sql_insert_verif)) {
                    $berhasil = false;
                    $error_message = "Gagal menginisiasi data verifikasi: " . mysqli_error($conn);
                }
            }

            // C. Commit/Rollback Transaksi
            if ($berhasil) {
                mysqli_commit($conn);
                $success_message = "Data Pegawai **{$nama_lengkap_gelar}** berhasil ditambahkan. Anda akan diarahkan ke halaman database.";
                // Redirect setelah 3 detik
                header("Refresh: 3; url=database.php");
            } else {
                mysqli_rollback($conn);
            }
        }
    } else {
        $error_message = "Koneksi database tidak tersedia. Mohon cek file koneksi.php.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<?php include 'layout/header_sidebar.php'; // Asumsi Anda memiliki file layout/header_sidebar.php ?>
<?php // Jika tidak punya, Anda harus mengcopy tag <head> dan sidebar dari database.php ?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?> | Instansi Pembina JF</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        .hold-transition.sidebar-mini { margin: 0; }
        .brand-link { background-color: #111827; }
        .brand-link .logo-pupr i { color: #0f62fe; }
        .logo-pupr { background: #fff; padding: 4px; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 50%;}
        .required-label:after { content:" *"; color: red; }
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
                    <li class="nav-item"><a href="index.php" class="nav-link <?php echo ($page === 'dashboard' ? 'active' : ''); ?>"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="database.php" class="nav-link <?php echo ($page === 'database' ? 'active' : ''); ?>"><i class="nav-icon fas fa-database"></i><p>Database</p></a></li>
                    <li class="nav-item"><a href="ujikom.php" class="nav-link <?php echo ($page === 'ujikom' ? 'active' : ''); ?>"><i class="nav-icon fas fa-clipboard-list"></i><p>Uji Kompetensi</p></a></li>
                    <li class="nav-item"><a href="rekomendasi.php" class="nav-link <?php echo ($page === 'rekomendasi' ? 'active' : ''); ?>"><i class="nav-icon fas fa-chart-line"></i><p>Rekomendasi Formasi</p></a></li>
                    <li class="nav-item"><a href="peraturan.php" class="nav-link <?php echo ($page === 'peraturan' ? 'active' : ''); ?>"><i class="nav-icon fas fa-book"></i><p>Peraturan</p></a></li>
                    <li class="nav-item"><a href="pengaturan.php" class="nav-link <?php echo ($page === 'pengaturan' ? 'active' : ''); ?>"><i class="nav-icon fas fa-cog"></i><p>Pengaturan</p></a></li>
                </ul>
            </nav>
            </div>
        </aside>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-user-plus"></i> <?php echo $page_title; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right" style="background-color: transparent;">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="database.php">Database</a></li>
                            <li class="breadcrumb-item active">Tambah</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <section class="content">
            <div class="container-fluid">

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Berhasil!</strong> <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-edit mr-1"></i> Form Data Pegawai Baru</h3>
                    </div>
                    <form method="POST" action="tambah_pegawai.php">
                    <div class="card-body">
                        
                        <p class="text-muted">Isi data dasar pegawai. Instansi akan diisi otomatis berdasarkan filter lokasi yang Anda pilih.</p>
                        
                        <h5 class="mt-4 mb-3 text-primary"><i class="fas fa-map-marker-alt"></i> Penentuan Instansi dan Lokasi</h5>
                        <div class="form-row border p-3 mb-4 bg-light">
                            <div class="form-group col-md-3">
                                <label for="jenisInstansi" class="required-label">Jenis Instansi</label>
                                <select id="jenisInstansi" name="jenisInstansi" class="form-control form-control-sm" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="pusat">Pusat</option>
                                    <option value="daerah">Pemerintah Daerah</option>
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label for="namaInstansi">Nama Instansi Pusat</label>
                                <select id="namaInstansi" name="namaInstansi" class="form-control form-control-sm" disabled>
                                    <option value="">Pilih Instansi Pusat</option>
                                    <?php 
                                    $list_pusat = ["Kementerian PKP", "Kementerian PU", "Kementerian PUPR", "Instansi Pusat Lain"];
                                    foreach ($list_pusat as $inst) {
                                        echo "<option value='".htmlspecialchars($inst)."'>".htmlspecialchars($inst)."</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label for="provinsi">Provinsi</label>
                                <select id="provinsi" name="provinsi" class="form-control form-control-sm" disabled>
                                    <option value="">Pilih Provinsi</option>
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label for="kabupaten">Kabupaten/Kota</label>
                                <select id="kabupaten" name="kabupaten" class="form-control form-control-sm" disabled>
                                    <option value="">Pilih Kabupaten/Kota</option>
                                </select>
                            </div>
                            <div class="col-12"><small class="text-info">Field **Instansi** di data pegawai akan diisi otomatis dari hasil pilihan di atas (Nama Instansi Pusat, atau Kab/Kota jika Daerah).</small></div>
                        </div>
                        
                        <h5 class="mt-4 mb-3 text-primary"><i class="fas fa-id-card"></i> Data Dasar Pegawai</h5>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="nip" class="required-label">NIP</label>
                                <input type="text" class="form-control" id="nip" name="nip" required placeholder="NIP 18 Digit" value="<?php echo htmlspecialchars($_POST['nip'] ?? ''); ?>">
                            </div>
                            <div class="form-group col-md-8">
                                <label for="nama_lengkap_gelar" class="required-label">Nama Lengkap (dengan Gelar)</label>
                                <input type="text" class="form-control" id="nama_lengkap_gelar" name="nama_lengkap_gelar" required placeholder="Contoh: Dr. Ir. Budi Santoso, M.T." value="<?php echo htmlspecialchars($_POST['nama_lengkap_gelar'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="jabatan" class="required-label">Jabatan Fungsional</label>
                                <input type="text" class="form-control" id="jabatan" name="jabatan" required placeholder="Contoh: Penata Kelola Perumahan Ahli Muda" value="<?php echo htmlspecialchars($_POST['jabatan'] ?? ''); ?>">
                            </div>
                             <div class="form-group col-md-6">
                                <label for="pangkat_gol">Pangkat/Golongan</label>
                                <input type="text" class="form-control" id="pangkat_gol" name="pangkat_gol" placeholder="Contoh: Penata Muda Tk. I / IIIb" value="<?php echo htmlspecialchars($_POST['pangkat_gol'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="unit_kerja" class="required-label">Unit Kerja</label>
                            <input type="text" class="form-control" id="unit_kerja" name="unit_kerja" required placeholder="Contoh: Sekretariat Direktorat Jenderal Perumahan" value="<?php echo htmlspecialchars($_POST['unit_kerja'] ?? ''); ?>">
                        </div>
                        
                        <input type="hidden" name="instansi" value=""> 

                        <h5 class="mt-4 mb-3 text-primary"><i class="fas fa-check-double"></i> Inisiasi Status Verifikasi Uji Kom.</h5>
                        <p class="text-muted">Tabel `verifikasi_ujikom` akan otomatis diinisiasi (diisi) dengan **`pegawai_id`** dari data yang baru ditambahkan. Semua status verifikasi akan bernilai **NULL**.</p>
                        
                    </div>
                    <div class="card-footer text-right">
                        <a href="database.php" class="btn btn-default"><i class="fas fa-arrow-left"></i> Batal</a>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Pegawai</button>
                    </div>
                    </form>
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

<script>
    // --- Data Provinsi/Kabupaten (Copy dari database.php) ---
    const provinsiData = {
        "Aceh": ["Kabupaten Aceh Barat", "Kota Banda Aceh", "Kota Lhokseumawe"], // Disingkat untuk contoh
        "Sumatera Utara": ["Kabupaten Asahan", "Kota Medan", "Kota Binjai"],
        // Anda harus **menambahkan semua data Provinsi/Kabupaten** yang lengkap dari script database.php
        // ke dalam objek `provinsiData` ini agar dropdown berjalan normal.
        "DKI Jakarta": ["Kabupaten Kepulauan Seribu", "Kota Jakarta Barat", "Kota Jakarta Pusat", "Kota Jakarta Selatan", "Kota Jakarta Timur", "Kota Jakarta Utara"],
        "Jawa Barat": ["Kabupaten Bandung", "Kota Bandung", "Kota Bekasi", "Kota Bogor", "Kota Depok"],
        // ... (Tambahkan semua 38 Provinsi di sini) ...
    };
    // Salin lengkap objek `provinsiData` dari file database.php Anda ke sini!
    // Karena batasan panjang, saya hanya menyertakan sebagian kecil.
    
    // Asumsi: Anda sudah menyalin lengkap objek 'provinsiData' dari database.php ke sini.

    const jenisInstansi = document.getElementById("jenisInstansi");
    const namaInstansi = document.getElementById("namaInstansi");
    const provinsiSelect = document.getElementById("provinsi");
    const kabupatenSelect = document.getElementById("kabupaten");
    
    // Fungsi untuk mengisi opsi dropdown
    function fillSelect(selectElement, options, placeholder) {
        selectElement.innerHTML = `<option value="">Pilih ${placeholder}</option>`;
        options.forEach(optValue => {
            const opt = document.createElement("option");
            opt.value = optValue;
            opt.textContent = optValue;
            selectElement.appendChild(opt);
        });
    }

    // Event listener untuk Jenis Instansi
    jenisInstansi.addEventListener("change", function() {
        // Reset dan atur disabled state
        namaInstansi.value = "";
        provinsiSelect.value = "";
        kabupatenSelect.value = "";
        
        namaInstansi.disabled = true;
        provinsiSelect.disabled = true;
        kabupatenSelect.disabled = true;

        if (this.value === 'pusat') {
            namaInstansi.disabled = false;
        } else if (this.value === 'daerah') {
            provinsiSelect.disabled = false;
            // Isi Provinsi
            const provinsiOptions = Object.keys(provinsiData);
            fillSelect(provinsiSelect, provinsiOptions, 'Provinsi');
        }
    });

    // Event listener untuk Provinsi
    provinsiSelect.addEventListener("change", function() {
        kabupatenSelect.value = "";
        kabupatenSelect.disabled = true;

        const selectedProvinsi = this.value;
        if (selectedProvinsi && provinsiData[selectedProvinsi]) {
            kabupatenSelect.disabled = false;
            fillSelect(kabupatenSelect, provinsiData[selectedProvinsi], 'Kabupaten/Kota');
        }
    });
    
    // Jalankan inisiasi saat load (misalnya jika ada error dan form terisi)
    jenisInstansi.dispatchEvent(new Event('change'));
</script>

<?php
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>
</body>
</html>