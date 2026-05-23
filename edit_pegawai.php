<?php
// FILE: edit_pegawai.php - Logika dan Tampilan untuk Mengedit Data Pegawai
// -------------------------------------------------------------------------

// --- AUTH GUARD DAN KONEKSI DATABASE ---
require_once 'koneksi.php'; 
require_once 'auth_guard.php'; 

// PENTING: Tambahkan session_start() jika auth_guard.php belum memanggilnya
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ⚠️ PENCEGAHAN ERROR FATAL: CEK KONEKSI
if (!$conn) {
    die("<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'>
        <h1>❌ Koneksi Database Gagal!</h1>
        <p><strong>Pesan Error MySQL:</strong> " . mysqli_connect_error() . "</p>
    </div>");
}

// --- PENGATURAN JUDUL HALAMAN & MENU ---
$page = 'database'; 
$sub_page = 'edit_pegawai'; 
$page_title = 'Edit Data Pegawai JF'; 

// Tentukan nama tabel pegawai
$NAMA_TABEL_PEGAWAI = "detailpegawai"; 

$message = ''; 
$is_error = false;
$id_pegawai = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pegawai_data = []; // Akan menampung data lama atau nilai sticky form
$error_ambil_data = false;

// =========================================================
// LOGIKA 1: AMBIL DATA PEGAWAI BERDASARKAN ID (GET Request)
// =========================================================
if ($id_pegawai > 0) {
    $sql_select = "SELECT * FROM {$NAMA_TABEL_PEGAWAI} WHERE id = ?";
    $stmt_select = mysqli_prepare($conn, $sql_select);
    
    if ($stmt_select) {
        mysqli_stmt_bind_param($stmt_select, "i", $id_pegawai);
        mysqli_stmt_execute($stmt_select);
        $result_select = mysqli_stmt_get_result($stmt_select);
        
        if (mysqli_num_rows($result_select) === 1) {
            $pegawai_data = mysqli_fetch_assoc($result_select);
            $page_title = 'Edit Data Pegawai: ' . htmlspecialchars($pegawai_data['nama']);
        } else {
            $error_ambil_data = true;
            $message = "❌ Error: Data pegawai dengan ID $id_pegawai tidak ditemukan.";
        }
        mysqli_stmt_close($stmt_select);
    } else {
        $error_ambil_data = true;
        $message = "❌ Gagal menyiapkan statement SELECT: " . mysqli_error($conn);
    }
} else {
    // Jika tidak ada ID yang valid di URL
    $error_ambil_data = true;
    $message = "❌ Error: ID pegawai tidak valid atau tidak diberikan.";
}


// =========================================================
// LOGIKA 2: TANGANI SUBMIT FORM UPDATE (POST Request)
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && $id_pegawai > 0 && !$error_ambil_data) {
    
    // Ambil dan Sanitasi Data LENGKAP dari Formulir
    $id_update = (int)($_POST['id_pegawai'] ?? 0); // ID dari hidden field
    
    // 13 KOLOM DATA (kecuali ID)
    $nip = trim($_POST['nip'] ?? '');
    $nama = trim($_POST['nama'] ?? ''); 
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $unit_kerja = trim($_POST['unit_kerja'] ?? '');
    $instansi_asal = trim($_POST['instansi_asal'] ?? '');
    $golongan = trim($_POST['golongan'] ?? '');
    $jabatan_lama = trim($_POST['jabatan_lama'] ?? '');
    $provinsi = trim($_POST['provinsi'] ?? '');
    $kabupaten_kota = trim($_POST['kabupaten_kota'] ?? '');
    $nama_instansi = trim($_POST['nama_instansi'] ?? '');
    $jenis_instansi = trim($_POST['jenis_instansi'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $jenjang = trim($_POST['jenjang'] ?? '');
    
    // Simpan nilai input baru untuk 'sticky form' jika terjadi error
    $pegawai_data = $_POST;
    $pegawai_data['id'] = $id_pegawai; 

    // Validasi Minimal
    if ($id_update !== $id_pegawai || empty($nip) || empty($nama) || empty($jabatan)) {
        $message = "Nama Pegawai, NIP, Jabatan wajib diisi, dan ID Pegawai harus sesuai.";
        $is_error = true;
    } else {
        // Query UPDATE (Total 13 kolom SET + 1 kolom WHERE)
        $sql_update = "UPDATE {$NAMA_TABEL_PEGAWAI} SET
            nip = ?,
            nama = ?,
            nama_lengkap = ?,
            unit_kerja = ?,
            instansi_asal = ?,
            golongan = ?,
            jabatan_lama = ?,
            provinsi = ?,
            kabupaten_kota = ?,
            nama_instansi = ?,
            jenis_instansi = ?,
            jabatan = ?,
            jenjang = ?
            WHERE id = ?"; 
        
        $stmt_update = mysqli_prepare($conn, $sql_update);
        
        if ($stmt_update) {
            // Binding parameter: Total 13 string (s) + 1 integer (i)
            mysqli_stmt_bind_param($stmt_update, "sssssssssssssi", 
                $nip, $nama, $nama_lengkap, $unit_kerja, $instansi_asal, $golongan, $jabatan_lama, 
                $provinsi, $kabupaten_kota, $nama_instansi, $jenis_instansi, $jabatan, $jenjang,
                $id_update
            );
            
            if (mysqli_stmt_execute($stmt_update)) {
                // >>> PERUBAHAN UTAMA: GANTI REDIRECT DENGAN PESAN SESSION <<<
                $_SESSION['notif_message'] = "Data pegawai **" . htmlspecialchars($nama) . "** (ID: {$id_update}) berhasil diperbarui! 🎉";
                $_SESSION['notif_type'] = 'success';
                
                header("Location: database.php");
                exit();
                // <<< AKHIR PERUBAHAN UTAMA >>>
            } else {
                // Gagal Update
                $message = "❌ Gagal memperbarui data: " . mysqli_error($conn);
                $is_error = true;
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $message = "❌ Gagal menyiapkan query UPDATE: " . mysqli_error($conn);
            $is_error = true;
        }
    }
}

// Tutup koneksi (sebelum output HTML)
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
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
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <?php require_once 'template/header.php'; // Asumsi file ini ada ?>
    <?php require_once 'template/sidebar.php'; // Asumsi file ini ada ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-edit"></i> <?php echo $page_title; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="database.php">Database</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <section class="content">
            <div class="container-fluid">

                <?php if ($message): // Tampilkan pesan error jika ada (menggunakan Bootstrap Alert biasa) ?>
                    <div class="alert alert-<?php echo $is_error ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if (!$error_ambil_data && $pegawai_data): // Tampilkan form hanya jika data berhasil dimuat ?>
                    <div class="card card-primary"> 
                        <div class="card-header">
                            <h3 class="card-title">Formulir Edit Data Pegawai ID: <?php echo htmlspecialchars($id_pegawai); ?></h3>
                        </div>
                        <form method="POST" action="edit_pegawai.php?id=<?php echo htmlspecialchars($id_pegawai); ?>"> 
                            
                            <input type="hidden" name="id_pegawai" value="<?php echo htmlspecialchars($id_pegawai); ?>">

                            <div class="card-body">
                                
                                <div class="form-group">
                                    <label for="nama">Nama Panggilan/Singkat (Kolom 'nama')</label>
                                    <input type="text" class="form-control" id="nama" name="nama" required 
                                        value="<?php echo htmlspecialchars($pegawai_data['nama'] ?? ''); ?>">
                                    <small class="form-text text-muted">Contoh: Budi S. (Sesuai kolom 'nama' di database)</small>
                                </div>
                                <div class="form-group">
                                    <label for="nama_lengkap">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                        value="<?php echo htmlspecialchars($pegawai_data['nama_lengkap'] ?? ''); ?>">
                                    <small class="form-text text-muted">Contoh: Budi Santoso (Sesuai kolom 'nama_lengkap')</small>
                                </div>
                                <div class="form-group">
                                    <label for="nip">NIP</label>
                                    <input type="text" class="form-control" id="nip" name="nip" required 
                                        value="<?php echo htmlspecialchars($pegawai_data['nip'] ?? ''); ?>">
                                </div>
                                
                                <hr>
                                
                                <h4>Informasi Jabatan & Golongan</h4>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="jabatan">Jabatan Fungsional</label>
                                        <input type="text" class="form-control" id="jabatan" name="jabatan" required
                                            value="<?php echo htmlspecialchars($pegawai_data['jabatan'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="jenjang">Jenjang Jabatan</label>
                                        <input type="text" class="form-control" id="jenjang" name="jenjang"
                                            value="<?php echo htmlspecialchars($pegawai_data['jenjang'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="golongan">Golongan</label>
                                        <input type="text" class="form-control" id="golongan" name="golongan" 
                                            value="<?php echo htmlspecialchars($pegawai_data['golongan'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="jabatan_lama">Jabatan Lama</label>
                                        <input type="text" class="form-control" id="jabatan_lama" name="jabatan_lama" 
                                            value="<?php echo htmlspecialchars($pegawai_data['jabatan_lama'] ?? ''); ?>">
                                    </div>
                                </div>

                                <hr>
                                
                                <h4>Informasi Instansi</h4>

                                <div class="form-group">
                                    <label for="jenis_instansi">Jenis Instansi</label>
                                    <select id="jenis_instansi" name="jenis_instansi" class="form-control" required>
                                        <option value="">-- Pilih Jenis Instansi --</option>
                                        <option value="Pusat" <?php echo ($pegawai_data['jenis_instansi'] ?? '') == 'Pusat' ? 'selected' : ''; ?>>Pusat</option>
                                        <option value="Daerah" <?php echo ($pegawai_data['jenis_instansi'] ?? '') == 'Daerah' ? 'selected' : ''; ?>>Daerah</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nama_instansi">Nama Instansi (Pusat)</label>
                                    <input type="text" class="form-control" id="nama_instansi" name="nama_instansi" required 
                                        value="<?php echo htmlspecialchars($pegawai_data['nama_instansi'] ?? ''); ?>">
                                    <small class="form-text text-muted">Contoh: Kementerian PUPR (Sesuai kolom 'nama_instansi')</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="unit_kerja">Unit Kerja</label>
                                    <input type="text" class="form-control" id="unit_kerja" name="unit_kerja" 
                                        value="<?php echo htmlspecialchars($pegawai_data['unit_kerja'] ?? ''); ?>">
                                    <small class="form-text text-muted">Contoh: Direktorat Jenderal Cipta Karya</small>
                                </div>

                                <div class="form-group">
                                    <label for="instansi_asal">Instansi Asal</label>
                                    <input type="text" class="form-control" id="instansi_asal" name="instansi_asal" 
                                        value="<?php echo htmlspecialchars($pegawai_data['instansi_asal'] ?? ''); ?>">
                                    <small class="form-text text-muted">Contoh: Pemerintah Provinsi Jawa Barat (Sesuai kolom 'instansi_asal')</small>
                                </div>


                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="provinsi">Provinsi</label>
                                        <input type="text" class="form-control" id="provinsi" name="provinsi" 
                                            value="<?php echo htmlspecialchars($pegawai_data['provinsi'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="kabupaten_kota">Kabupaten/Kota</label>
                                        <input type="text" class="form-control" id="kabupaten_kota" name="kabupaten_kota" 
                                            value="<?php echo htmlspecialchars($pegawai_data['kabupaten_kota'] ?? ''); ?>">
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                                <a href="database.php" class="btn btn-secondary float-right">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </section>
    </div>

    <?php require_once 'template/footer.php'; // Asumsi file ini ada ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

</body>
</html>