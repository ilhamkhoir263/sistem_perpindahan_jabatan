<?php
// FILE: form_perpindahan_jabatan.php - Formulir Pendaftaran Uji Kompetensi (Multi-Step) untuk Perpindahan Jabatan

// --- ASUMSI FILE PENDUKUNG ---
require_once 'koneksi.php';    // Koneksi database
require_once 'auth_guard.php'; // Digunakan untuk menjaga sesi dan mendapatkan data user
require_once 'role_definitions.php'; // <<< FILE DITAMBAHKAN SESUAI PERMINTAAN

// Cek koneksi untuk mencegah error fatal
if (!isset($conn) || !$conn) {
    die("Fatal Error: Koneksi database tidak tersedia. Mohon cek koneksi.php");
}
// --- LOGIKA ON/OFF AKSES FORM --- 

// Ambil status akses dari tabel admin_update (ID 1 adalah baris utama)
$cek_akses = mysqli_query($conn, "SELECT status_form_pj FROM tb_admin_update WHERE id = 1");
$status = mysqli_fetch_assoc($cek_akses);

// Jika status_form_pj bernilai 0 (Tutup) atau data tidak ditemukan
if (!$status || $status['status_form_pj'] == 0) {
    // Arahkan ke halaman index_pengusul.php dengan pesan status tertutup
    header("Location: index_pengusul.php?status=closed");
    exit;
}

// --- Kontrol Akses Logika ---
// ASUMSI KONSTANTA ROLE ADA DI role_definitions.php (jika tidak, gunakan definisi fallback)
if (!defined('ROLE_SUPER_ADMIN')) define('ROLE_SUPER_ADMIN', 'super_admin'); // <<< TAMBAHAN UNTUK SUPER_ADMIN (Baris 21)
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 'admin'); 
if (!defined('ROLE_USER_ADMIN')) define('ROLE_USER_ADMIN', 'user_admin'); 
if (!defined('ROLE_USER_PENGUSUL')) define('ROLE_USER_PENGUSUL', 'user_pengusul'); // <<< FIX: DEFINISI ROLE DITAMBAHKAN
if (!defined('ROLE_USER_BIASA')) define('ROLE_USER_BIASA', 'user_biasa'); 

// Ambil data sesi (diasumsikan diatur di auth_guard.php)
$user_role_sesi = $_SESSION['user_role'] ?? ROLE_USER_BIASA; 
$user_nip = $_SESSION['nip'] ?? ''; 
$NAMA_TABEL_UJIKOM = "pengajuan_ujikom";
$user_nama_sesi = $_SESSION['user_name'] ?? ''; 
$user_email_sesi = $_SESSION['user_email'] ?? ''; 

$nip_sudah_ada = false;
$show_rereg_message = false;
$rereg_date = null;

if (!empty($user_nip)) {

    $sql_cek = "SELECT id, status_pengajuan, tanggal_re_registrasi 
                FROM {$NAMA_TABEL_UJIKOM} 
                WHERE nip = '$user_nip' 
                AND jenis_pengajuan = 'Perpindahan Jabatan'
                ORDER BY id DESC LIMIT 1";

    $result_cek = $conn->query($sql_cek);

    if ($result_cek && $result_cek->num_rows > 0) {

        $data = $result_cek->fetch_assoc();

        $status = strtolower($data['status_pengajuan']);
        $rereg_date = $data['tanggal_re_registrasi'];

        if ($status == 'tidak lulus') {

            if (empty($rereg_date)) {
                $nip_sudah_ada = true;
            } else {

                $today = date('Y-m-d');

                if ($today < $rereg_date) {
                    $nip_sudah_ada = true;
                    $show_rereg_message = true;
                } else {
                    $nip_sudah_ada = false;
                }
            }

        } else {
            $nip_sudah_ada = true;
        }

    } else {
        $nip_sudah_ada = false;
    }
}
// Fungsi check_role
if (!function_exists('check_role')) {
    function check_role(array $allowed_roles) {
        global $user_role_sesi; 
        return in_array($user_role_sesi, $allowed_roles);
    }
}
// MODIFIKASI KRITIS: ROLE_USER_PENGUSUL dan ROLE_USER_BIASA ditambahkan agar bisa SUBMIT/isi form
$allowed_submit_roles = [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_USER_ADMIN, ROLE_USER_PENGUSUL, ROLE_USER_BIASA]; // <<< FIX UTAMA DI BARIS INI
// Variabel ini TRUE jika role saat ini TIDAK diizinkan untuk SUBMIT
$is_form_disabled = !check_role($allowed_submit_roles); 

// Siapkan atribut disabled/readonly untuk HTML umum
$disabled_attr = $is_form_disabled ? 'disabled' : '';
$readonly_attr = $is_form_disabled ? 'readonly' : '';

// --- LOGIKA KHUSUS NIP ---
// TAMBAHAN LOGIKA NIP UNTUK SUPER_ADMIN
if ($user_role_sesi == ROLE_ADMIN || $user_role_sesi == ROLE_SUPER_ADMIN) { 
    // Admin/Super Admin: NIP TIDAK dikunci (Boleh diubah)
    $nip_control_attr = ''; 
    $nip_title_attr = 'title="Anda adalah Admin/Super Admin: NIP dapat diubah untuk keperluan pengujian aplikasi."';
    $is_nip_locked = false; // <<< VARIABEL KONTROL JS
} else {
    // User Admin / User Biasa: NIP dikunci (readonly) dan diisi otomatis dari sesi
    $nip_control_attr = '';
    $nip_title_attr = 'title="NIP tidak dapat diubah karena diambil dari data akun Anda."';
    $is_nip_locked = true; // <<< VARIABEL KONTROL JS
}
// --- AKHIR LOGIKA KHUSUS NIP ---

// --- PENTING: DEKLARASI VARIABEL UNTUK HIGHLIGHT MENU SIDEBAR --
$page = 'ujikom'; 
$sub_page = 'perpindahan_jabatan';      // Variabel highlight untuk sidebar
$page_title = 'Pengajuan Uji Kompetensi - Perpindahan Jabatan'; // Judul Halaman
// -------------------------------------------------------------

// --- PENGATURAN GLOBAL ---
$TARGET_DIR = "uploads/perpindahan/"; // Folder khusus untuk perpindahan
$NAMA_TABEL_UJIKOM = "pengajuan_ujikom"; 
$MAX_FILE_SIZE = 5242880; // 5 MB dalam byte (5 * 1024 * 1024)

$success_message = '';
$error_message = '';


// ARRAY FILE MAP - MENGGUNAKAN NAMA KOLOM DATABASE
$file_map = [
    'file_usulan' => 'file_surat_usulan_perpindahan', 
    'file_kebutuhan' => 'file_dokumen_penetapan', 
    'file_usulan_ukom' => 'file_surat_usulan_uji', 
    'file_drh' => 'file_portofolio', 
    'file_cpns_pns' => 'file_sk_cpns_pns', 
    'file_pangkat' => 'file_sk_pangkat', 
    'file_jabatan' => 'file_sk_jabatan', 
    'file_skp' => 'file_skp', 
    'file_ijazah_transkrip' => 'file_ijazah_transkrip', 
    'file_integritas' => 'file_pernyataan_integritas', 
    'file_bersedia' => 'file_pernyataan_bersedia', 
    'file_pengalaman' => 'file_pernyataan_pengalaman', 
    'file_penempatan' => 'file_rencana_penempatan', 
];
// Deskripsi file untuk notifikasi
$file_descriptions = [
    'file_usulan' => 'Surat Usulan Perpindahan Jabatan',
    'file_kebutuhan' => 'Dokumen Penetapan Kebutuhan JF',
    'file_usulan_ukom' => 'Surat Usulan Uji Kompetensi',
    'file_drh' => 'Daftar Riwayat Hidup (DRH) / Portofolio',
    'file_cpns_pns' => 'Salinan SK CPNS dan SK PNS',
    'file_pangkat' => 'Salinan SK Pangkat/Golongan Terakhir',
    'file_jabatan' => 'Salinan SK Jabatan Terakhir',
    'file_skp' => 'Salinan SKP 1 Tahun Terakhir',
    'file_ijazah_transkrip' => 'Salinan Ijazah dan Transkrip Nilai',
    'file_integritas' => 'Surat Pernyataan Integritas/Moralitas',
    'file_bersedia' => 'Surat Pernyataan Bersedia Diangkat',
    'file_pengalaman' => 'Surat Pernyataan Memiliki Pengalaman Jabatan',
    'file_penempatan' => 'Rencana Penempatan PNS',
];


// >>> LOGIKA PEMROSESAN FORM SUBMISSION <<<
// Hanya proses jika form TIDAK disabled dan ada POST request
if (!$is_form_disabled && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize input data
    $nama = $_POST['nama'] ?? '';
    
    // --- LOGIKA TAMBAHAN: CEK APAKAH NIP SUDAH PERNAH MENDAFTAR ---
    $sql_check_nip = "SELECT id FROM {$NAMA_TABEL_UJIKOM} WHERE nip = ? AND jenis_pengajuan = 'Perpindahan Jabatan'";
    $stmt_check_nip = mysqli_prepare($conn, $sql_check_nip);
    mysqli_stmt_bind_param($stmt_check_nip, "s", $nip);
    mysqli_stmt_execute($stmt_check_nip);
    mysqli_stmt_store_result($stmt_check_nip);

    if ($result_check && mysqli_num_rows($result_check) > 0) {

    $data = mysqli_fetch_assoc($result_check);
    $status = strtolower($data['status_pengajuan']);
    $rereg_date = $data['tanggal_re_registrasi'];
    $today = date('Y-m-d');

    if ($status != 'tidak lulus') {
        $error_message = "❌ Anda sudah pernah mengajukan dan tidak dapat mendaftar lagi.";
        $upload_ok = false;

    } else {

        if (empty($rereg_date) || $today < $rereg_date) {
            $error_message = "❌ Anda belum bisa re-registrasi. Silakan tunggu sampai tanggal yang ditentukan.";
            $upload_ok = false;
        }
        // kalau lolos → boleh lanjut submit
    }
}
    mysqli_stmt_close($stmt_check_nip);
    // --- AKHIR LOGIKA CEK NIP ---
    // NIP diambil dari POST jika Admin (NIP tidak dikunci), atau dari Sesi jika NIP dikunci/kosong
    $nip_from_form = $_POST['nip'] ?? $user_nip;
    $nip = $nip_from_form; 
    
    $jabatan = $_POST['jabatan'] ?? '';
    $jf_pkp_tujuan = $_POST['jf_pkp_tujuan'] ?? '';
    $jabatan_saat_ini = $_POST['jabatan_saat_ini'] ?? '';
    $pangkat = $_POST['pangkat'] ?? '';
    $tmt = $_POST['tmt'] ?? '';
    $jenjang = $_POST['jenjang'] ?? '';
    $prodi = $_POST['prodi'] ?? '';
    $email = $_POST['email'] ?? $user_email_sesi; // Default dari sesi
    $hp = $_POST['hp'] ?? '';
    $instansi = $_POST['instansi'] ?? '';
    $unit_organisasi = $_POST['unit_organisasi'] ?? '';
    $instansi_daerah = $_POST['instansi_daerah'] ?? ''; 
    $unit_saat_ini = $_POST['unit_saat_ini'] ?? '';
    $unit_sebelumnya = $_POST['unit_sebelumnya'] ?? '';

    $upload_ok = true;
    
    if (empty($nip)) {
         $error_message .= "❌ NIP wajib diisi.";
         $upload_ok = false;
    }
    
    $file_paths = [];
    

    // 2. Proses Upload File
    if (!is_dir($TARGET_DIR)) {
        if (!@mkdir($TARGET_DIR, 0777, true)) { 
            $error_message .= "Gagal membuat folder upload ({$TARGET_DIR}). Periksa izin folder.<br>";
            $upload_ok = false;
        }
    }

    if ($upload_ok) {
        // Logika upload file untuk setiap file di $file_map
        foreach ($file_map as $form_field => $db_column) {
            
            // Cek apakah file wajib ada dan diupload
            if (isset($_FILES[$form_field])) {
                 
                if ($_FILES[$form_field]['error'] == 4) {
                    $error_message .= "File **{$file_descriptions[$form_field]}** wajib diunggah.<br>";
                    $upload_ok = false;
                    break;
                } else if ($_FILES[$form_field]['error'] != 0) {
                    $error_message .= "Terjadi error saat upload file **{$file_descriptions[$form_field]}** (Error Code: {$_FILES[$form_field]['error']}).<br>";
                    $upload_ok = false;
                    break;
                }
                
                // Jika file ada dan tidak error
                $file_temp = $_FILES[$form_field]['tmp_name'];
                $file_name_original = basename($_FILES[$form_field]['name']);
                $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
                
                // Buat nama file unik dan bersih
                $prefix = str_replace(['file_', '_'], ['','-'], $form_field); 
                // Ganti spasi dengan strip, buang karakter non-alphanumeric, dan potong nama
                $clean_nama = substr(preg_replace('/[^A-Za-z0-9-]/', '', str_replace(' ', '-', $nama)), 0, 15); 
                $new_file_name = "{$prefix}_{$nip}_{$clean_nama}_" . time() . ".{$file_ext}";
                
                $target_file = $TARGET_DIR . $new_file_name;

                if ($file_ext != "pdf") {
                    $error_message .= "File **{$file_descriptions[$form_field]}** harus berformat PDF.<br>";
                    $upload_ok = false;
                    break;
                }
                if ($_FILES[$form_field]['size'] > $MAX_FILE_SIZE) { 
                    $error_message .= "File **{$file_descriptions[$form_field]}** terlalu besar. Maksimal 5MB.<br>";
                    $upload_ok = false;
                    break;
                }
                if (move_uploaded_file($file_temp, $target_file)) {
                    // Simpan nama file yang di-sanitize
                    $file_paths[$db_column] = $conn->real_escape_string($new_file_name); 
                } else {
                    $error_message .= "Gagal mengunggah file **{$file_descriptions[$form_field]}**. Periksa izin folder.<br>";
                    $upload_ok = false;
                    break;
                }

            } else {
                 $error_message .= "File **{$file_descriptions[$form_field]}** tidak terdeteksi dalam pengiriman form.<br>";
                 $upload_ok = false;
                 break;
            }
        }
    }
    
    // 3. Masukkan data ke database
    if ($upload_ok && empty($error_message) && count($file_paths) == count($file_map)) {
        
        $columns = "nip, nama,jabatan_saat_ini, jf_pkp_tujuan, email, hp, jabatan,pangkat, tmt_pangkat, jenjang_pendidikan, program_studi, instansi, unit_organisasi, unit_daerah, unit_saat_ini, unit_sebelumnya, jenis_pengajuan, tanggal_pengajuan, status_pengajuan";
        
        // Sesuaikan nilai unit_organisasi dan unit_daerah
        $unit_organisasi_value = '';
        $unit_daerah_value = '';

        if ($instansi == 'Kementerian/Lembaga') {
             $unit_organisasi_value = $unit_organisasi;
        } elseif ($instansi == 'Pemerintah Daerah') {
             $unit_daerah_value = $instansi_daerah;
        }
        
        $jenis_pengajuan = 'Perpindahan Jabatan'; 

        // Sanitize semua variabel yang akan masuk ke query
        $safe_nip = $conn->real_escape_string($nip);
        $safe_nama = $conn->real_escape_string($nama);
        $safe_email = $conn->real_escape_string($email);
        $safe_hp = $conn->real_escape_string($hp);

        $safe_jabatan_saat_ini = $conn->real_escape_string($jabatan_saat_ini);
        $safe_jf_pkp_tujuan = $conn->real_escape_string($jf_pkp_tujuan);
        $safe_jabatan = $conn->real_escape_string($jabatan);
        $safe_pangkat = $conn->real_escape_string($pangkat);
        $safe_tmt = $conn->real_escape_string($tmt);
        $safe_jenjang = $conn->real_escape_string($jenjang);
        $safe_prodi = $conn->real_escape_string($prodi);
        $safe_instansi = $conn->real_escape_string($instansi);
        $safe_unit_organisasi = $conn->real_escape_string($unit_organisasi_value);
        $safe_unit_daerah = $conn->real_escape_string($unit_daerah_value);
        $safe_unit_saat_ini = $conn->real_escape_string($unit_saat_ini);
        $safe_unit_sebelumnya = $conn->real_escape_string($unit_sebelumnya);

        $values = "'$safe_nip', '$safe_nama','$safe_jabatan_saat_ini','$safe_jf_pkp_tujuan', '$safe_email', '$safe_hp', '$safe_jabatan', '$safe_pangkat', '$safe_tmt', '$safe_jenjang', '$safe_prodi', '$safe_instansi', '$safe_unit_organisasi', '$safe_unit_daerah', '$safe_unit_saat_ini', '$safe_unit_sebelumnya', '$jenis_pengajuan', NOW(), 'Menunggu Verifikasi'"; 

        // Tambahkan kolom dan nilai file
        foreach ($file_paths as $col => $path) {
            $columns .= ", " . $col;
            $values .= ", '" . $path . "'";
        }
        
        // Kumpulkan semua kolom dan nilai yang akan di-INSERT
        $sql_insert = "INSERT INTO {$NAMA_TABEL_UJIKOM} ({$columns}) VALUES ({$values})";
        
        if ($conn->query($sql_insert)) {
            // $last_id = $conn->insert_id; // Dapatkan ID record yang baru dibuat

            // --- PERUBAHAN KRITIS DI SINI: REDIRECT KE LIST PENGUSUL ---
            header("Location: list_perpindahan_pengusul.php?status=success"); 
            exit(); 
            // -------------------------------------------------------------
        } else {
             $error_message .= "❌ Gagal menyimpan data formulir ke database: " . $conn->error;
             // Lanjutkan ke logic cleanup file jika terjadi error DB
        }
    }

    // JIKA ADA ERROR SAAT UPLOAD ATAU ERROR DB, FILE JUGA HARUS DIHAPUS (CLEANUP)
    if (!empty($error_message) && !empty($file_paths)) { 
        // Logic cleanup file saat error upload atau error DB
        foreach ($file_paths as $path) {
            $full_path = $TARGET_DIR . $path;
            if (file_exists($full_path)) @unlink($full_path); 
        }
        // Tambahkan peringatan umum untuk kejelasan
        if (strpos($error_message, "Data pendaftaran GAGAL dikirim") === false) {
             $error_message = "Data pendaftaran GAGAL dikirim. " . $error_message;
        }
    }
} 

// Handle redirect success message
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $success_message = "✅ Data pendaftaran uji kompetensi **berhasil dikirim**! Status: Menunggu Verifikasi.";
}


// Data sesi untuk template
$user_data = [
    'nama' => $user_nama_sesi ?? 'Pengguna JF',
    'role' => $user_role_sesi,
    'email' => $user_email_sesi ?? 'user@instansi.go.id',
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023'
];
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
        /* Styling tambahan untuk Brand/Logo */
        .brand-link { background-color: #111827; }
        .brand-link .logo-pupr i { color: #0f62fe; }
        .logo-pupr { background: #fff; padding: 4px; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 50%;}
        .step-box { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .step-header { background-color: #f8f9fa; padding: 10px; margin: -15px -15px 15px -15px; border-bottom: 1px solid #ccc; font-weight: bold; }
        .progress-indicator { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .progress-step { flex: 1; text-align: center; padding: 10px; border-bottom: 3px solid #ccc; }
        .progress-step.active { border-bottom-color: #007bff; font-weight: bold; color: #007bff; }
        .progress-step.completed { border-bottom-color: #28a745; color: #28a745; }
        .message.error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; border-radius: .25rem; margin-bottom: 15px; }
        .message.success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 10px; border-radius: .25rem; margin-bottom: 15px; }
        .message.warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; padding: 10px; border-radius: .25rem; margin-bottom: 15px; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <?php include 'template/navbar.php'; ?>
    <?php include 'template/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-file-upload"></i> Pendaftaran Uji Kompetensi</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Formulir</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title" id="card-title-step"><i class="fas fa-edit"></i> Formulir Perpindahan Jabatan Fungsional (Langkah 1/3)</h3>
                            </div>
                            <div class="card-body">
    <?php if ($success_message): ?>
        <div class="message success">
            <?php echo $success_message; ?>
            <p class="mt-2"><a href="list_perpindahan_pengusul.php" class="btn btn-sm btn-success">Lihat Status Pengajuan</a></p>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error">
            <i class="fas fa-times-circle"></i> **Gagal Mengirimkan Data:** <p><?php echo $error_message; ?></p>
        </div>
    <?php endif; ?>

    <?php if ($show_rereg_message): ?>
    <div class="alert alert-info">
        <h5><i class="icon fas fa-info-circle"></i> Informasi Re-Registrasi</h5>
        Anda dinyatakan <b>TIDAK LULUS</b>.<br>
        Anda dapat melakukan <b>Re-Registrasi mulai tanggal 
        (<?php echo date('d F Y', strtotime($rereg_date)); ?>)</b>.
        <br><br>
        Silakan cek kembali setelah tanggal tersebut.
    </div>

    <?php 
        // Kunci form
        $is_form_disabled = true;
        $disabled_attr = 'disabled';
        $readonly_attr = 'readonly';
    ?>

<?php elseif ($nip_sudah_ada): ?>
    <div class="alert alert-warning">
        <h5><i class="icon fas fa-exclamation-triangle"></i> Pengajuan Terdeteksi!</h5>
        Sistem mendeteksi bahwa NIP Anda (<b><?php echo htmlspecialchars($user_nip); ?></b>) sudah pernah mengirimkan data pengajuan Perpindahan Jabatan. 
        Sesuai ketentuan, Anda hanya diperbolehkan mengirimkan pengajuan sebanyak <b>satu kali</b>. <br>
        <a href="list_perpindahan_pengusul.php" class="btn btn-sm btn-dark mt-2">Cek Riwayat Pengajuan Saya</a>
    </div>

    <?php 
        $is_form_disabled = true; 
        $disabled_attr = 'disabled';
        $readonly_attr = 'readonly';
    ?>

<?php elseif ($is_form_disabled): ?>
        <div class="message warning">
            <i class="fas fa-exclamation-triangle"></i> **PERINGATAN AKSES DIBATASI!**
            <p>Peran Anda saat ini (**<?php echo htmlspecialchars(strtoupper($user_role_sesi)); ?>**) hanya diizinkan untuk melihat tampilan formulir ini. Silakan hubungi Administrator jika Anda memerlukan perubahan peran.</p>
        </div>
    <?php else: ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> **AKSES FORMULIR AKTIF!**
            <p>Anda sedang mengisi formulir sebagai **<?php echo htmlspecialchars(strtoupper($user_role_sesi)); ?>**. Pastikan data yang dimasukkan sudah benar.</p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($success_message)): ?>
        <div class="progress-indicator">
            <div class="progress-step active" data-step="1">Data Pribadi</div>
            <div class="progress-step" data-step="2">Data Instansi</div>
            <div class="progress-step" data-step="3">Upload Dokumen</div>
        </div>

                                    <form id="perpindahanForm" method="POST" enctype="multipart/form-data" action="form_perpindahan_jabatan.php">
    
    <div class="step-box" id="step1">
    <div class="step-header"><i class="fas fa-user"></i> Langkah 1: Data Pribadi</div>
    
    <div class="row">
    <div class="col-md-6 form-group">
        <label>Nama Lengkap dan Gelar <span class="text-danger">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
            <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required value="<?php echo htmlspecialchars($_POST['nama'] ?? $user_nama_sesi ?? ''); ?>" <?php echo $readonly_attr; ?>>
        </div>
    </div>
    <div class="col-md-6 form-group">
        <label>NIP <span class="text-danger">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-id-card"></i></span></div>
            <input type="text" name="nip" class="form-control" placeholder="Nomor Induk Pegawai" required value="<?php echo htmlspecialchars($_POST['nip'] ?? $user_nip ?? ''); ?>" <?php echo $nip_control_attr; ?> <?php echo $nip_title_attr; ?>>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label>Jabatan Saat Ini <span class="text-danger">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-briefcase"></i></span></div>
            <input type="text" name="jabatan_saat_ini" class="form-control" placeholder="Cth: Analis Kebijakan Ahli Muda" required value="<?php echo htmlspecialchars($_POST['jabatan_saat_ini'] ?? $jabatan_saat_ini ??''); ?>" <?php echo $readonly_attr; ?>>
        </div>
    </div>
    <div class="col-md-6 form-group">
        <label>JF PKP Yang Dituju <span class="text-danger">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-bullseye"></i></span></div>
            <?php 
                $current_val = $_POST['jf_pkp_tujuan'] ?? $jf_pkp_tujuan ?? ''; 
                $select_disabled = (!empty($readonly_attr)) ? 'disabled' : '';
            ?>
            <select name="jf_pkp_tujuan" class="form-control select2" required <?php echo $select_disabled; ?>>
                <option value="">-- Pilih Jenjang Tujuan --</option>
                <option value="Penata Kelola Perumahan Ahli Pertama" <?php echo ($current_val == 'Penata Kelola Perumahan Ahli Pertama') ? 'selected' : ''; ?>>Penata Kelola Perumahan Ahli Pertama</option>
                <option value="Penata Kelola Perumahan Ahli Muda" <?php echo ($current_val == 'Penata Kelola Perumahan Ahli Muda') ? 'selected' : ''; ?>>Penata Kelola Perumahan Ahli Muda</option>
                <option value="Penata Kelola Perumahan Ahli Madya" <?php echo ($current_val == 'Penata Kelola Perumahan Ahli Madya') ? 'selected' : ''; ?>>Penata Kelola Perumahan Ahli Madya</option>
                <option value="Penata Kelola Perumahan Ahli Utama" <?php echo ($current_val == 'Penata Kelola Perumahan Ahli Utama') ? 'selected' : ''; ?>>Penata Kelola Perumahan Ahli Utama</option>
            </select>
        </div>
        <?php if ($select_disabled): ?>
            <input type="hidden" name="jf_pkp_tujuan" value="<?php echo htmlspecialchars($current_val); ?>">
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label>Pangkat/Golongan <span class="text-danger">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-layer-group"></i></span></div>
            <select name="pangkat" class="form-control" required <?php echo $disabled_attr; ?>>
                <option value="">-- Pilih Pangkat/Golongan --</option>
                <?php
                $golongans = ['III/a', 'III/b', 'III/c', 'III/d', 'IV/a', 'IV/b', 'IV/c', 'IV/d', 'IV/e'];
                foreach ($golongans as $gol) {
                    $selected = (($_POST['pangkat'] ?? $pangkat ?? '') == $gol) ? 'selected' : '';
                    echo "<option value=\"$gol\" $selected>$gol</option>";
                }
                ?>
            </select>
        </div>
    </div>
    <div class="col-md-6 form-group">
        <label>TMT Pangkat/Golongan <span class="text-danger">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i></span></div>
            <input type="date" name="tmt" class="form-control" required value="<?php echo htmlspecialchars($_POST['tmt'] ?? $tmt ?? date('Y-m-d')); ?>" <?php echo $readonly_attr; ?>>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label>Jenjang Pendidikan <span class="text-danger">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-graduation-cap"></i></span></div>
            <select name="jenjang" class="form-control" required <?php echo $disabled_attr; ?>>
                <option value="">-- Pilih Jenjang --</option>
                <?php 
                $levels = ["S1 / D4", "S2", "S3"];
                foreach($levels as $lvl) {
                    $selected = (($_POST['jenjang'] ?? $jenjang ?? '') == $lvl) ? 'selected' : '';
                    echo "<option value=\"$lvl\" $selected>$lvl</option>";
                }
                ?>
            </select>
        </div>
    </div>
    <div class="col-md-6 form-group">
        <label>Program Studi/Jurusan <span class="text-danger">*</span></label>
        <input type="text" name="prodi" class="form-control" placeholder="Cth: Teknik Sipil" required value="<?php echo htmlspecialchars($_POST['prodi'] ?? $prodi ?? ''); ?>" <?php echo $readonly_attr; ?>>
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label>Email <span class="text-danger">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div>
            <input type="email" name="email" class="form-control" placeholder="alamat@email.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? $user_email_sesi ?? ''); ?>" <?php echo $readonly_attr; ?>>
        </div>
    </div>
    <div class="col-md-6 form-group">
        <label>Nomor HP (WhatsApp) <span class="text-danger">*</span></label>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-whatsapp"></i></span></div>
            <input type="text" name="hp" class="form-control" placeholder="08123456789" required value="<?php echo htmlspecialchars($_POST['hp'] ?? $hp ?? ''); ?>" <?php echo $readonly_attr; ?>>
        </div>
    </div>
</div>

    <div class="actions text-right">
        <button type="button" class="btn btn-primary next-step" data-next="2" <?php echo $disabled_attr; ?>>Lanjut <i class="fas fa-arrow-right"></i></button>
    </div>
</div>

    <div class="step-box" id="step2" style="display:none;">
        <div class="step-header"><i class="fas fa-building"></i> Langkah 2: Data Instansi</div>
        <div class="form-group">
            <label for="instansi">Instansi Saat Ini <span class="text-danger">*</span></label>
            <select id="instansi" name="instansi" class="form-control" required <?php echo $disabled_attr; ?>>
                <option value="">-- Pilih --</option>
                <option value="Kementerian/Lembaga" <?php echo (($_POST['instansi'] ?? '') == 'Kementerian/Lembaga') ? 'selected' : ''; ?>>Kementerian/Lembaga (Pusat)</option>
                <option value="Pemerintah Daerah" <?php echo (($_POST['instansi'] ?? '') == 'Pemerintah Daerah') ? 'selected' : ''; ?>>Pemerintah Daerah (Pemda)</option>
            </select>
        </div>
        
        <div class="form-group" id="group-unit-organisasi">
            <label for="unit_organisasi">Nama Unit Organisasi (Eselon I) <span class="text-danger required-star">*</span></label>
            <input type="text" id="unit_organisasi" name="unit_organisasi" class="form-control" placeholder="Cth: Direktorat Jenderal Cipta Karya" value="<?php echo htmlspecialchars($_POST['unit_organisasi'] ?? ''); ?>" <?php echo $readonly_attr; ?>>
        </div>

        <div class="form-group" id="group-instansi-daerah" style="display:none;">
            <label for="instansi_daerah">Nama Instansi Pemerintah Daerah <span class="text-danger required-star-daerah">*</span></label>
            <input type="text" id="instansi_daerah" name="instansi_daerah" class="form-control" placeholder="Cth: Pemerintah Provinsi Jawa Barat / Pemerintah Kota Bandung" value="<?php echo htmlspecialchars($_POST['instansi_daerah'] ?? ''); ?>" <?php echo $readonly_attr; ?>>
        </div>
        
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="unit_saat_ini">Unit Kerja Saat Ini (Eselon II) <span class="text-danger">*</span></label>
                <input type="text" id="unit_saat_ini" name="unit_saat_ini" class="form-control" required placeholder="Cth: Dinas Perumahan dan Permukiman Provinsi Jawa Barat" value="<?php echo htmlspecialchars($_POST['unit_saat_ini'] ?? ''); ?>" <?php echo $readonly_attr; ?>>
            </div>
            <div class="col-md-6 form-group">
                <label for="unit_sebelumnya">Unit Kerja Sebelumnya <span class="text-danger">*</span></label>
                <input type="text" id="unit_sebelumnya" name="unit_sebelumnya" class="form-control" required placeholder="Cth: Sub Bagian Umum" value="<?php echo htmlspecialchars($_POST['unit_sebelumnya'] ?? ''); ?>" <?php echo $readonly_attr; ?>>
            </div>
        </div>

        <div class="actions text-right">
            <button type="button" class="btn btn-secondary prev-step" data-prev="1" <?php echo $disabled_attr; ?>><i class="fas fa-arrow-left"></i> Kembali</button>
            <button type="button" class="btn btn-primary next-step" data-next="3" <?php echo $disabled_attr; ?>>Lanjut <i class="fas fa-arrow-right"></i></button>
        </div>
    </div>

    <div class="step-box" id="step3" style="display:none;">
        <div class="step-header"><i class="fas fa-file-pdf"></i> Langkah 3: Upload Dokumen Persyaratan (Wajib PDF, Maks 5MB)</div>
        <p class="text-info"><i class="fas fa-info-circle"></i> Pastikan semua file di bawah ini sudah dalam format PDF dan diunggah.</p>
        
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th style="width: 10px;">No</th>
                    <th>Nama Dokumen Persyaratan</th>
                    <th>Contoh Nama File</th>
                    <th>Upload File <span class="text-danger">*</span></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $upload_fields = [
                    ['file_usulan', $file_descriptions['file_usulan'], 'Usulan_Perpindahan_NIP_Nama'],
                    ['file_kebutuhan', $file_descriptions['file_kebutuhan'], 'Kebutuhan_JF_NIP_Nama'],
                    ['file_usulan_ukom', $file_descriptions['file_usulan_ukom'], 'Usulan_Ukom_NIP_Nama'],
                    ['file_drh', $file_descriptions['file_drh'], 'DRH_Portofolio_NIP_Nama'],
                    ['file_cpns_pns', $file_descriptions['file_cpns_pns'], 'SK_CPNS_PNS_NIP_Nama'],
                    ['file_pangkat', $file_descriptions['file_pangkat'], 'SK_Pangkat_NIP_Nama'],
                    ['file_jabatan', $file_descriptions['file_jabatan'], 'SK_Jabatan_NIP_Nama'],
                    ['file_skp', $file_descriptions['file_skp'], 'SKP_NIP_Nama'],
                    ['file_ijazah_transkrip', $file_descriptions['file_ijazah_transkrip'], 'Ijazah_Transkrip_NIP_Nama'],
                    ['file_integritas', $file_descriptions['file_integritas'], 'Pernyataan_Integritas_NIP_Nama'],
                    ['file_bersedia', $file_descriptions['file_bersedia'], 'Pernyataan_Bersedia_NIP_Nama'],
                    ['file_pengalaman', $file_descriptions['file_pengalaman'], 'Pernyataan_Pengalaman_NIP_Nama'],
                    ['file_penempatan', $file_descriptions['file_penempatan'], 'Rencana_Penempatan_NIP_Nama'],
                ];

                $no = 1;
                foreach ($upload_fields as $upload):
                ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $upload[1]; ?></td>
                        <td><small class="text-muted"><?php echo $upload[2]; ?>.pdf</small></td>
                        <td>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="<?php echo $upload[0]; ?>" name="<?php echo $upload[0]; ?>" required accept="application/pdf" <?php echo $disabled_attr; ?>>
                                    <label class="custom-file-label" for="<?php echo $upload[0]; ?>" data-original-text="Pilih file PDF" id="label-<?php echo $upload[0]; ?>">Pilih file PDF</label>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="actions text-right mt-4">
            <button type="button" class="btn btn-secondary prev-step" data-prev="2" <?php echo $disabled_attr; ?>><i class="fas fa-arrow-left"></i> Kembali</button>
            <button type="submit" name="submit_perpindahan" class="btn btn-success" <?php echo $disabled_attr; ?>>
                <i class="fas fa-paper-plane"></i> Selesaikan & Kirim Pendaftaran
            </button>
        </div>
    </div>

</form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            
        </div>
        <strong>© <?php echo date('Y'); ?> Instansi Pembina Jabatan Fungsional</strong>
    </footer>
</div>

    <?php
    // --- PANGGIL FOOTER ---
    include 'template/footer.php';
    ?>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/adminlte.min.js"></script>

<script>
$(function () {
    let currentStep = 1;
    const MAX_FILE_SIZE = <?php echo $MAX_FILE_SIZE; ?>; // 5 MB
    const IS_FORM_DISABLED = <?php echo $is_form_disabled ? 'true' : 'false'; ?>;
    const IS_NIP_LOCKED = <?php echo $is_nip_locked ? 'true' : 'false'; ?>;

    function updateStepDisplay(step) {
        currentStep = step;
        $('.step-box').hide();
        $('#step' + step).show();

        $('.progress-step').removeClass('active completed');
        for (let i = 1; i <= 3; i++) {
            const $stepIndicator = $(`.progress-step[data-step="${i}"]`);
            if (i < step) {
                $stepIndicator.addClass('completed');
            } else if (i === step) {
                $stepIndicator.addClass('active');
            }
        }
        
        // Update card title
        $('#card-title-step').html('<i class="fas fa-edit"></i> Formulir Perpindahan Jabatan Fungsional (Langkah ' + step + '/3)');

        // Scroll to top of card body
        $('.card-body').get(0).scrollIntoView({ behavior: 'smooth' });
    }

    // Custom file input label update
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).siblings('.custom-file-label').html(fileName);
        } else {
             $(this).siblings('.custom-file-label').html($(this).siblings('.custom-file-label').data('original-text'));
        }
    });

    function validateStep(step) {
        let isValid = true;
        
        // Reset validation classes untuk step yang sedang divalidasi
        $('#step' + step).find('.is-invalid').removeClass('is-invalid');

        const $requiredInputs = $('#step' + step).find('input[required]:not([type="file"]), select[required], textarea[required]');
        
        $requiredInputs.each(function() {
             // Cek input yang tidak disabled
             if (!$(this).prop('disabled') && !$(this).prop('readonly')) {
                 if ($(this).val() === '' || $(this).val() === null) {
                     $(this).addClass('is-invalid');
                     isValid = false;
                 } else {
                     $(this).removeClass('is-invalid');
                 }
             }
        });
        
        // Custom check for NIP length if not locked (Admin/Super Admin)
        if (step === 1 && !IS_NIP_LOCKED) {
             const $nipInput = $('input[name="nip"]');
             // NIP harus 18 digit
             if ($nipInput.val() && $nipInput.val().length !== 18) {
                 $nipInput.addClass('is-invalid').prop('title', 'NIP harus 18 digit.');
                 isValid = false;
             } else {
                 $nipInput.removeClass('is-invalid').prop('title', '');
             }
        }
        
        // Custom check for step 3 (File uploads)
        if (step === 3) {
            const $fileInputs = $('#step3').find('input[type="file"][required]');
            $fileInputs.each(function() {
                const $input = $(this);
                const file = $input[0].files[0];
                const $label = $input.siblings('.custom-file-label');

                $input.removeClass('is-invalid'); // Reset

                if (!file) {
                    $input.addClass('is-invalid');
                    $label.attr('title', 'File wajib diunggah.');
                    isValid = false;
                } else if (!file.name.toLowerCase().endsWith('.pdf')) {
                    $input.addClass('is-invalid');
                    $label.attr('title', 'File harus berformat PDF.');
                    isValid = false;
                } else if (file.size > MAX_FILE_SIZE) { 
                    $input.addClass('is-invalid');
                    $label.attr('title', 'Ukuran file melebihi batas (Maks 5MB).');
                    isValid = false;
                } else {
                    $label.attr('title', '');
                }
            });
        }
        
        // Custom check for step 2 (Instansi)
        if (step === 2) {
             const instansiValue = $('#instansi').val();
             
             // Check Unit Organisasi if K/L
             const unitOrganisasiInput = $('#unit_organisasi');
             if (instansiValue === 'Kementerian/Lembaga' && !unitOrganisasiInput.prop('disabled')) {
                 if (!unitOrganisasiInput.val()) {
                     unitOrganisasiInput.addClass('is-invalid');
                     isValid = false;
                 }
             } else {
                 unitOrganisasiInput.removeClass('is-invalid');
             }

             // Check Instansi Daerah if Pemda
             const instansiDaerahInput = $('#instansi_daerah');
             if (instansiValue === 'Pemerintah Daerah' && !instansiDaerahInput.prop('disabled')) {
                 if (!instansiDaerahInput.val()) {
                     instansiDaerahInput.addClass('is-invalid');
                     isValid = false;
                 }
             } else {
                 instansiDaerahInput.removeClass('is-invalid');
             }
        }

        if (!isValid) {
            // Tampilkan alert hanya jika form TIDAK disabled
            if (!IS_FORM_DISABLED) {
                 alert('Mohon lengkapi semua data yang wajib diisi atau perbaiki input yang tidak valid.');
            }
        }

        return isValid;
    }

    $('.next-step').on('click', function() {
        if (!IS_FORM_DISABLED) {
             if (validateStep(currentStep)) {
                const nextStep = parseInt($(this).data('next'));
                if (nextStep) {
                    updateStepDisplay(nextStep);
                }
             }
        } else { 
             // Jika form disabled, tetap izinkan navigasi untuk melihat-lihat
             const nextStep = parseInt($(this).data('next'));
             if (nextStep) {
                 updateStepDisplay(nextStep);
             }
        }
    });

    $('.prev-step').on('click', function() {
        const prevStep = parseInt($(this).data('prev'));
        if (prevStep) {
            updateStepDisplay(prevStep);
        }
    });

    function applyInstansiRule() {
        const isPemda = $('#instansi').val() === 'Pemerintah Daerah';
        const isKL = $('#instansi').val() === 'Kementerian/Lembaga';
        
        const unitOrganisasiInput = $('#unit_organisasi');
        const instansiDaerahInput = $('#instansi_daerah');
        
        const unitOrganisasiRequiredStar = $('.required-star');
        const instansiDaerahRequiredStar = $('.required-star-daerah');
        
        // Tampilkan/sembunyikan grup input
        $('#group-unit-organisasi').toggle(isKL); // Hanya tampil jika K/L
        $('#group-instansi-daerah').toggle(isPemda); // Hanya tampil jika Pemda
        
        // --- LOGIKA K/L (Unit Organisasi) ---
        
        // Set required jika K/L
        unitOrganisasiInput.prop('required', isKL);
        unitOrganisasiRequiredStar.toggle(isKL);

        if (IS_FORM_DISABLED) {
            // Jika form disabled, biarkan readonly, hapus disabled agar datanya terkirim (jika diisi)
            unitOrganisasiInput.attr('readonly', true).prop('disabled', false); 
        } else {
            // Jika K/L, aktifkan. Jika Pemda/Kosong, non-aktifkan dan kosongkan.
            unitOrganisasiInput.prop('disabled', !isKL);
            unitOrganisasiInput.attr('readonly', false);
            if (!isKL) {
                 unitOrganisasiInput.val('');
            }
        }

        // --- LOGIKA Pemda (Instansi Daerah) ---
        
        // Set required jika Pemda
        instansiDaerahInput.prop('required', isPemda);
        instansiDaerahRequiredStar.toggle(isPemda);

        if (IS_FORM_DISABLED) {
             // Jika form disabled, biarkan readonly, hapus disabled agar datanya terkirim (jika diisi)
            instansiDaerahInput.attr('readonly', true).prop('disabled', false); 
        } else {
             // Jika Pemda, aktifkan. Jika K/L/Kosong, non-aktifkan dan kosongkan.
             instansiDaerahInput.prop('disabled', !isPemda);
             instansiDaerahInput.attr('readonly', false);
             if (!isPemda) {
                 instansiDaerahInput.val('');
             }
        }
    }

    $('#instansi').on('change', applyInstansiRule).trigger('change'); 

    updateStepDisplay(1);
    
    // Pastikan semua input form mendapatkan kelas form-control AdminLTE
    $('input:not([type="file"]), select').addClass('form-control');
});
</script>

<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>