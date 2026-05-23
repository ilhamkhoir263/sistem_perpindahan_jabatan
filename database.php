<?php
// FILE: database.php - Halaman Database Pegawai JF PKP dengan Filter Dinamis

// --- AUTH GUARD DITAMBAHKAN DI SINI ---
require_once 'auth_guard.php'; 

// Memuat koneksi database.
require_once 'koneksi.php'; 

// ⚠️ PENCEGAHAN ERROR FATAL: CEK KONEKSI (BARIS KRUSIAL)
// Jika koneksi gagal di koneksi.php, variabel $conn akan bernilai null.
if (!$conn) {
    // Tampilkan pesan error dan hentikan eksekusi script database.php
    die("<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'>
            <h1>❌ Koneksi Database Gagal!</h1>
            <p><strong>Penyebab:</strong> Kredensial di <strong>koneksi.php</strong> salah, atau MySQL Server belum berjalan.</p>
            <p><strong>Pesan Error MySQL:</strong> " . mysqli_connect_error() . "</p>
        </div>");
}
// END OF KRUSIAL CHECK

// --- PENGGUNAAN DATA SESSION DARI auth_guard.php ---
$user_data = [
    'nama' => $user_nama_sesi ?? 'Pengguna JF', 
    'role' => $user_role_sesi ?? 'User', 
    'email' => $user_email_sesi ?? 'user@instansi.go.id', 
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023'
];

// Tentukan nama tabel pegawai yang digunakan
$NAMA_TABEL_PEGAWAI = "detailpegawai"; 
$NAMA_TABEL_MASTER_INSTANSI = "masterinstansi"; 

// =========================================================
// ⚠️ KONSTANTA NAMA KOLOM UNTUK DB Anda
// HARUS SESUAI DENGAN KOLOM DI TABEL detailpegawai
// =========================================================
// Pastikan kolom-kolom ini sudah ada di tabel detailpegawai Anda.
const COL_JENIS_INSTANSI = "jenis_instansi"; 
const COL_NAMA_INSTANSI = "nama_instansi";  
const COL_PROVINSI = "provinsi";        
const COL_KABUPATEN = "kabupaten_kota"; 
// =========================================================


// --- PENGATURAN JUDUL HALAMAN & MENU ---
$page = 'database'; 
$sub_page = ''; 
$page_title = 'Database Pejabat Fungsional'; 

// --- Dapatkan Input Filter ---
$jenis_instansi = isset($_GET['jenisInstansi']) ? trim($_GET['jenisInstansi']) : '';
$nama_instansi = isset($_GET['namaInstansi']) ? trim($_GET['namaInstansi']) : '';
$provinsi = isset($_GET['provinsi']) ? trim($_GET['provinsi']) : '';
$kabupaten = isset($_GET['kabupaten']) ? trim($_GET['kabupaten']) : '';

$message = ''; 
$is_error = false;
$data_pegawai = [];
$master_provinsi = [];
$master_kabupaten = [];
$master_instansi_pusat = [];

// =========================================================
// LOGIKA FILTER DAN QUERY DATABASE
// =========================================================
// Koneksi status sudah dijamin di atas, jadi kita langsung query.
    
// 1. Ambil Data Master Provinsi
$sql_prov = "SELECT DISTINCT " . COL_PROVINSI . " FROM {$NAMA_TABEL_PEGAWAI} WHERE " . COL_PROVINSI . " IS NOT NULL AND " . COL_PROVINSI . " != '' ORDER BY " . COL_PROVINSI . " ASC";
$result_prov = mysqli_query($conn, $sql_prov);
if ($result_prov) {
    while ($row = mysqli_fetch_assoc($result_prov)) {
        $master_provinsi[] = $row[COL_PROVINSI];
    }
}

// 2. Ambil Data Master Instansi Pusat
$sql_instansi = "SELECT DISTINCT " . COL_NAMA_INSTANSI . " FROM {$NAMA_TABEL_PEGAWAI} WHERE " . COL_JENIS_INSTANSI . " = 'Pusat' AND " . COL_NAMA_INSTANSI . " IS NOT NULL AND " . COL_NAMA_INSTANSI . " != '' ORDER BY " . COL_NAMA_INSTANSI . " ASC";
$result_instansi = mysqli_query($conn, $sql_instansi);
if ($result_instansi) {
    while ($row = mysqli_fetch_assoc($result_instansi)) {
        $master_instansi_pusat[] = $row[COL_NAMA_INSTANSI];
    }
}

// 3. Ambil Data Master Kabupaten (jika provinsi dipilih)
if (!empty($provinsi) && $jenis_instansi == 'Daerah') {
    $sql_kab = "SELECT DISTINCT " . COL_KABUPATEN . " FROM {$NAMA_TABEL_PEGAWAI} WHERE " . COL_PROVINSI . " = ? AND " . COL_KABUPATEN . " IS NOT NULL AND " . COL_KABUPATEN . " != '' ORDER BY " . COL_KABUPATEN . " ASC";
    $stmt_kab = mysqli_prepare($conn, $sql_kab);
    if ($stmt_kab) {
        mysqli_stmt_bind_param($stmt_kab, "s", $provinsi);
        mysqli_stmt_execute($stmt_kab);
        $result_kab = mysqli_stmt_get_result($stmt_kab);
        while ($row = mysqli_fetch_assoc($result_kab)) {
            $master_kabupaten[] = $row[COL_KABUPATEN];
        }
        mysqli_stmt_close($stmt_kab);
    }
}


// 4. Bangun Query Data Pegawai
$where_clauses = [];
$param_types = '';
$param_values = [];

// Filter Jenis Instansi 
if (!empty($jenis_instansi)) {
    $where_clauses[] = COL_JENIS_INSTANSI . " = ?";
    $param_types .= 's';
    $param_values[] = &$jenis_instansi;
}

// Filter Nama Instansi (Hanya berlaku untuk Pusat)
if ($jenis_instansi == 'Pusat' && !empty($nama_instansi)) {
    $where_clauses[] = COL_NAMA_INSTANSI . " = ?";
    $param_types .= 's';
    $param_values[] = &$nama_instansi;
}

// Filter Provinsi (Hanya berlaku untuk Daerah)
if ($jenis_instansi == 'Daerah' && !empty($provinsi)) {
    $where_clauses[] = COL_PROVINSI . " = ?";
    $param_types .= 's';
    $param_values[] = &$provinsi;
}

// Filter Kabupaten (Hanya berlaku untuk Daerah jika provinsi dipilih)
if ($jenis_instansi == 'Daerah' && !empty($provinsi) && !empty($kabupaten)) {
    $where_clauses[] = COL_KABUPATEN . " = ?";
    $param_types .= 's';
    $param_values[] = &$kabupaten;
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// ✅ MODIFIKASI: Diubah dari ORDER BY nama ASC menjadi ORDER BY id DESC.
// ORDER BY id DESC akan mengurutkan data dari ID terbesar (data terbaru) ke ID terkecil (data terlama).
// Jika Anda memiliki kolom tanggal input (misal: 'tanggal_input' atau 'created_at'), 
// lebih baik gunakan kolom tersebut: ORDER BY tanggal_input DESC
$sql_pegawai = "SELECT * FROM {$NAMA_TABEL_PEGAWAI} {$where_sql} ORDER BY id DESC"; 

$stmt_pegawai = mysqli_prepare($conn, $sql_pegawai);

if (!$stmt_pegawai) {
    $is_error = true;
    $message = "❌ Gagal menyiapkan statement (Query Pegawai): " . mysqli_error($conn);
} else {
    if (count($param_values) > 0) {
        // Binding parameters
        $bind_params = [];
        $bind_params[] = $stmt_pegawai;
        $bind_params[] = $param_types;
        foreach ($param_values as &$value) {
            $bind_params[] = &$value;
        }
        // Menggunakan call_user_func_array untuk dynamic binding
        call_user_func_array('mysqli_stmt_bind_param', $bind_params);
    }

    mysqli_stmt_execute($stmt_pegawai);
    $result_pegawai = mysqli_stmt_get_result($stmt_pegawai);
    
    while ($row = mysqli_fetch_assoc($result_pegawai)) {
        $data_pegawai[] = $row;
    }
    mysqli_stmt_close($stmt_pegawai);
}


// Tutup koneksi database (di akhir script)
// Jika koneksi sudah ditutup di sini, pastikan tidak ada query lain setelah ini.
// Karena kita menggunakan die() di awal jika gagal, kita biarkan saja penutupan di akhir HTML.
// if (isset($conn) && $conn) {
//     mysqli_close($conn);
// }

// =========================================================
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
    
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
    
    <?php require_once 'template/sidebar.php'; ?>
    

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-database"></i> <?php echo $page_title; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Database</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">

                <?php if ($message && !$is_error): // Tampilkan sukses/info jika bukan error fatal ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php elseif ($is_error): // Tampilkan error non-fatal ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Filter Data Pegawai</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <form method="GET" action="database.php" id="filterForm">
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="jenisInstansi">Jenis Instansi</label>
                                    <select id="jenisInstansi" name="jenisInstansi" class="form-control">
                                        <option value="">-- Semua Jenis Instansi --</option>
                                        <option value="Pusat" <?php echo $jenis_instansi == 'Pusat' ? 'selected' : ''; ?>>Pusat</option>
                                        <option value="Daerah" <?php echo $jenis_instansi == 'Daerah' ? 'selected' : ''; ?>>Daerah</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="namaInstansi">Nama Instansi (Pusat)</label>
                                    <select id="namaInstansi" name="namaInstansi" class="form-control" <?php echo $jenis_instansi != 'Pusat' ? 'disabled' : ''; ?>>
                                        <option value="">-- Semua Instansi Pusat --</option>
                                        <?php foreach ($master_instansi_pusat as $instansi): ?>
                                            <option value="<?php echo htmlspecialchars($instansi); ?>" <?php echo $nama_instansi == $instansi ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($instansi); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="provinsiSelect">Provinsi (Daerah)</label>
                                    <select id="provinsiSelect" name="provinsi" class="form-control" <?php echo $jenis_instansi != 'Daerah' ? 'disabled' : ''; ?>>
                                        <option value="">-- Semua Provinsi --</option>
                                        <?php foreach ($master_provinsi as $prov): ?>
                                            <option value="<?php echo htmlspecialchars($prov); ?>" <?php echo $provinsi == $prov ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($prov); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="kabupatenSelect">Kabupaten/Kota (Daerah)</label>
                                    <select id="kabupatenSelect" name="kabupaten" class="form-control" <?php echo (empty($provinsi) || $jenis_instansi != 'Daerah') ? 'disabled' : ''; ?>>
                                        <option value="">-- Semua Kab/Kota --</option>
                                        <?php foreach ($master_kabupaten as $kab): ?>
                                            <option value="<?php echo htmlspecialchars($kab); ?>" <?php echo $kabupaten == $kab ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($kab); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Terapkan Filter</button>
                            <a href="database.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset Filter</a>
                        </div>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tabel Data Pegawai JF PKP (<?php echo count($data_pegawai); ?> Data)</h3>
                        <div class="card-tools">
                            <a href="tambah_pegawai.php" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Tambah Pegawai</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabelPegawai" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>NIP</th>
                                        <th>Nama Pegawai</th>
                                        <th>Instansi</th>
                                        <th>Jabatan</th>
                                        <th>Jenjang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($data_pegawai) > 0): ?>
                                        <?php $no = 1; foreach ($data_pegawai as $pegawai): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($pegawai['nip'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($pegawai['nama'] ?? '-'); ?></td>
                                                <td>
                                                    <?php 
                                                        $instansi_text = $pegawai[COL_NAMA_INSTANSI] ?? '-';
                                                        if (isset($pegawai[COL_PROVINSI]) && !empty($pegawai[COL_PROVINSI])) {
                                                            $instansi_text .= ' (' . $pegawai[COL_PROVINSI];
                                                            if (isset($pegawai[COL_KABUPATEN]) && !empty($pegawai[COL_KABUPATEN])) {
                                                                $instansi_text .= ', ' . $pegawai[COL_KABUPATEN];
                                                            }
                                                            $instansi_text .= ')';
                                                        }
                                                        echo htmlspecialchars($instansi_text); 
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($pegawai['jabatan'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($pegawai['jenjang'] ?? '-'); ?></td>
                                                <td>
                                                    <a href="detail_pegawai.php?id=<?php echo $pegawai['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                                    <a href="edit_pegawai.php?id=<?php echo $pegawai['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                                    <button class="btn btn-danger btn-sm" data-id="<?php echo $pegawai['id']; ?>"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada data pegawai yang ditemukan sesuai filter.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
    // Inisialisasi DataTables
    $(function () {
        // PERHATIAN: Karena kita sudah melakukan ORDER BY di SQL, 
        // pastikan DataTables tidak melakukan sorting secara default pada kolom pertama (No.).
        // Kolom pertama di tabel HTML (No.) memiliki indeks 0.
        $("#tabelPegawai").DataTable({
            "responsive": true, 
            "autoWidth": false,
            "pageLength": 10,
            // Menonaktifkan sorting pada kolom No. agar urutan dari DB tetap terjaga
            "columnDefs": [
                { "orderable": false, "targets": 0 }
            ],
            // DataTables akan tetap menggunakan urutan dari server (id DESC)
            "order": [] // Nonaktifkan ordering default oleh DataTables
        });

        // Kode JavaScript yang sudah dimodifikasi di database.php
$('.btn-danger').on('click', function(e) {
    e.preventDefault();
    var id_data = $(this).data('id');
    
    // Opsional: Perkuat pesan konfirmasi
    if (confirm('Anda yakin ingin menghapus data pegawai dengan ID ' + id_data + '? Tindakan ini TIDAK dapat dibatalkan.')) {
        
        // 🎯 Lakukan pengalihan (redirect) ke script penghapusan
        window.location.href = 'delete_pegawai.php?id=' + id_data; 
    }
});
    });

    // Logika Filter Dinamis 
    const jenisInstansi = document.getElementById("jenisInstansi");
    const namaInstansi = document.getElementById("namaInstansi");
    const provinsiSelect = document.getElementById("provinsiSelect");
    const kabupatenSelect = document.getElementById("kabupatenSelect");

    function setupFilterDaerah() {
        const selectedJenis = jenisInstansi.value;

        // Reset dan atur status disabled
        namaInstansi.disabled = true;
        provinsiSelect.disabled = true;
        kabupatenSelect.disabled = true;

        if (selectedJenis === 'Pusat') {
            namaInstansi.disabled = false;
        } else if (selectedJenis === 'Daerah') {
            provinsiSelect.disabled = false;
            // Hanya enable kabupaten jika provinsi sudah dipilih
            if (provinsiSelect.value) {
                kabupatenSelect.disabled = false;
            }
        }
    }
    
    // Event listener untuk perubahan Jenis Instansi
    jenisInstansi.addEventListener("change", () => {
        // Saat jenis instansi berubah, reset filter lainnya dan submit form
        namaInstansi.value = "";
        provinsiSelect.value = "";
        kabupatenSelect.value = "";
        
        jenisInstansi.form.submit();
    });

    // Event listener untuk perubahan Provinsi
    provinsiSelect.addEventListener("change", function () {
        // Saat provinsi berubah, submit form agar data DB terfilter
        kabupatenSelect.value = ""; // Reset kabupaten/kota
        this.form.submit(); 
    });
    
    // Event listener untuk perubahan Nama Instansi (Pusat)
    namaInstansi.addEventListener("change", function () {
        this.form.submit(); 
    });
    
    // Event listener untuk perubahan Kabupaten
    kabupatenSelect.addEventListener("change", function () {
        this.form.submit(); 
    });

    // Jalankan setup saat halaman dimuat
    window.addEventListener("DOMContentLoaded", setupFilterDaerah);
</script>
    
<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>
</body>
</html>