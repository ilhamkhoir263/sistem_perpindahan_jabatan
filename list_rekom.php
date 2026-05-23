<?php
// FILE: list_rekom.php - Halaman Daftar Pengajuan Rekomendasi Formasi (Dilengkapi Sorting Tanggal & Filter Instansi)

// 1. --- PENGATURAN SESSION & KONEKSI ---
require_once 'auth_guard.php'; 
require_once 'koneksi.php'; 

// Cek koneksi
if (!$conn) {
    die("Fatal Error: Koneksi database tidak tersedia. Mohon cek koneksi.php");
}

// 2. --- PENGATURAN VARIABEL HALAMAN ---
$page = 'rekomendasi';      // Menu utama: Rekomendasi Formasi
$sub_page = 'list_rekom';   // Submenu: Daftar Rekomendasi
$page_title = 'Daftar Data Pengajuan Rekomendasi Formasi'; 
$NAMA_TABEL_REKOM = "rekomendasi_formasi";

// 3. --- LOGIKA PENGAMBILAN DATA & FILTER/SORT ---
// Tangkap parameter filter/sort
$sort_tanggal = isset($_GET['sort_tanggal']) ? mysqli_real_escape_string($conn, $_GET['sort_tanggal']) : 'terbaru'; // <-- BERUBAH: Menjadi Sort Order
$filter_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($conn, $_GET['filter_status']) : '';
$filter_instansi = isset($_GET['filter_instansi']) ? mysqli_real_escape_string($conn, $_GET['filter_instansi']) : ''; 

$data_rekomendasi = [];
$error_message = null;
$where_clause = " WHERE 1=1 "; // Kondisi awal

// --- KONSTRUKSI KLAUSA WHERE (Filter Status & Instansi) ---

// Filter Status
if (!empty($filter_status)) {
    $where_clause .= " AND status = '{$filter_status}' ";
}

// Filter Instansi
if (!empty($filter_instansi)) {
    $where_clause .= " AND instansi = '{$filter_instansi}' ";
}

// --- KONSTRUKSI KLAUSA ORDER BY (Sorting Tanggal BARU) ---
$order_clause = " ORDER BY tanggal_pengajuan ";

if ($sort_tanggal === 'terlama') {
    $order_clause .= " ASC"; // Urutkan dari yang paling lama
} else { // Default adalah 'terbaru'
    $order_clause .= " DESC"; // Urutkan dari yang paling baru
}


// --- QUERY UTAMA PENGAMBILAN DATA ---
$query = "SELECT 
            id, 
            tanggal_pengajuan, 
            nama_pengusul, 
            nip, 
            instansi, 
            provinsi, 
            kota_kab, 
            status 
          FROM {$NAMA_TABEL_REKOM} 
          {$where_clause}
          {$order_clause}"; // Menggunakan order clause yang dinamis

$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data_rekomendasi[] = $row;
    }
} else {
    // Handle error jika query gagal
    $error_message = "Error mengambil data: " . mysqli_error($conn);
}

// --- QUERY UNTUK MENGAMBIL DAFTAR STATUS UNIK (Untuk Dropdown Filter) ---
$unique_statuses = [];
$status_query = "SELECT DISTINCT status FROM {$NAMA_TABEL_REKOM} ORDER BY status ASC";
$status_result = mysqli_query($conn, $status_query);
if ($status_result) {
    while ($row = mysqli_fetch_assoc($status_result)) {
        $unique_statuses[] = trim($row['status']);
    }
}

// --- QUERY UNTUK MENGAMBIL DAFTAR INSTANSI UNIK (Untuk Dropdown Filter) ---
$unique_instansis = [];
$instansi_query = "SELECT DISTINCT instansi FROM {$NAMA_TABEL_REKOM} ORDER BY instansi ASC";
$instansi_result = mysqli_query($conn, $instansi_query);
if ($instansi_result) {
    while ($row = mysqli_fetch_assoc($instansi_result)) {
        // Membersihkan dan memasukkan instansi unik
        $unique_instansis[] = trim($row['instansi']); 
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?></title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    
    <style>
        /* CSS styling tambahan (sesuaikan dari file sebelumnya) */
        .content-wrapper { background: #f4f6f9 !important; }
        .form-container { max-width:1400px; width:100%; padding:18px; box-sizing:border-box; margin: 0 auto; }
        .card-custom {border-radius:12px;box-shadow: 0 10px 30px rgba(10,20,30,0.15); border:none !important;}
        .data-status {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap; 
        }
        /* Mengatur agar elemen filter sejajar */
        .form-inline .form-control {
            height: calc(1.8125rem + 2px); /* Standard height for sm inputs */
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .form-inline .input-group-sm .form-control,
        .form-inline .input-group-sm .input-group-text {
            height: calc(1.8125rem + 2px); 
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .filter-row {
            padding: 10px 0;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <?php include 'template/navbar.php'; ?> 
    <?php include 'template/sidebar.php'; ?> 

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-list"></i> <?php echo $page_title; ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right" style="background-color: transparent;">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Rekomendasi Formasi</li>
                        </ol>
                    </div>
                </div>
                
                <!-- 4. --- FILTER/SORT FORM ROW DENGAN INSTANSI --- -->
                <div class="row filter-row">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <!-- Filter Form Start -->
                        <form action="" method="GET" class="form-inline">
                            <h5 class="mr-3 mb-0 text-muted small font-weight-bold">Atur Data:</h5>
                            
                            <!-- 4.1 Sort Tanggal (BARU) -->
                            <div class="input-group input-group-sm mr-2">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-sort-amount-down"></i> Urut Tgl</span></div>
                                <select id="sort_tanggal" name="sort_tanggal" class="form-control">
                                    <option value="terbaru" <?php echo ($sort_tanggal === 'terbaru') ? 'selected' : ''; ?>>Terbaru</option>
                                    <option value="terlama" <?php echo ($sort_tanggal === 'terlama') ? 'selected' : ''; ?>>Terlama</option>
                                </select>
                            </div>

                            <!-- 4.2 Filter Instansi -->
                            <div class="input-group input-group-sm mr-2">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-building"></i> Instansi</span></div>
                                <select id="filter_instansi" name="filter_instansi" class="form-control">
                                    <option value="">-- Semua Instansi --</option>
                                    <?php 
                                    foreach ($unique_instansis as $instansi_option): 
                                        if (empty($instansi_option)) continue;
                                        $display_text = htmlspecialchars($instansi_option);
                                    ?>
                                        <option value="<?php echo htmlspecialchars($instansi_option); ?>" 
                                            <?php echo ($filter_instansi === $instansi_option) ? 'selected' : ''; ?>>
                                            <?php echo $display_text; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- 4.3 Filter Status -->
                            <div class="input-group input-group-sm mr-2">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-tag"></i> Status</span></div>
                                <select id="filter_status" name="filter_status" class="form-control">
                                    <option value="">-- Semua Status --</option>
                                    <?php 
                                    foreach ($unique_statuses as $status_option): 
                                        if (empty($status_option)) continue; 
                                        $display_text = htmlspecialchars(ucwords(strtolower($status_option)));
                                    ?>
                                        <option value="<?php echo htmlspecialchars($status_option); ?>" 
                                            <?php echo ($filter_status === $status_option) ? 'selected' : ''; ?>>
                                            <?php echo $display_text; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-sm btn-primary mr-2"><i class="fas fa-filter"></i> Terapkan</button>
                            <a href="list_rekom.php" class="btn btn-sm btn-secondary"><i class="fas fa-undo"></i> Reset</a>
                        </form>
                        <!-- Filter Form End -->

                        <!-- Input Baru Button -->
                        <a href="input_rekom.php" class="btn btn-success float-right">
                            <i class="fas fa-plus"></i> Input Pengajuan Baru
                        </a>
                    </div>
                </div>
                <!-- END FILTER/SORT FORM ROW -->

            </div>
        </div>

        <section class="content">
            <div class="container-fluid form-container">
                
                <?php if (isset($error_message) && $error_message): ?>
                    <div class="alert alert-danger" role="alert"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card card-custom">
                    <div class="card-header border-0">
                        <h3 class="card-title">Data Pengajuan Rekomendasi (<?php echo count($data_rekomendasi); ?> Data Ditemukan)</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="rekomendasiTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Tanggal</th>
                                        <th>Nama Pengusul</th>
                                        <th>NIP</th>
                                        <th>Instansi</th>
                                        <th>Provinsi</th>
                                        <th>Kab/Kota</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($data_rekomendasi as $data): 
                                        // Menentukan warna badge berdasarkan status
                                        $badge_class = '';
                                        $status_db = trim($data['status']); // Membersihkan spasi ekstra
                                        $status_display = htmlspecialchars($status_db); // Default display text
                                        
                                        // Logika Warna Badge & Ubah Teks Status
                                        switch (strtoupper($status_db)) {
                                            case 'DIAJUKAN':
                                            case 'MENUNGGU VERIFIKASI':
                                                $badge_class = 'bg-warning text-dark'; // Kuning
                                                $status_display = 'Menunggu Verifikasi';
                                                break;
                                            
                                            case 'DIVERIFIKASI':
                                            case 'DISETUJUI': 
                                            case 'LAYAK':
                                                $badge_class = 'bg-success'; 
                                                $status_display = 'Disetujui'; 
                                                break;
                                            
                                            case 'DITOLAK':
                                            case 'PERLU REVISI':
                                            case 'PERLU PERBAIKAN':
                                                $badge_class = 'bg-danger'; // Merah
                                                if (strtoupper($status_db) == 'PERLU REVISI' || strtoupper($status_db) == 'PERLU PERBAIKAN') {
                                                    $status_display = 'Perlu Perbaikan';
                                                } else {
                                                    $status_display = 'Ditolak';
                                                }
                                                break;
                                            
                                            default:
                                                $badge_class = 'bg-secondary'; // Abu-abu (Default)
                                                break;
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d-m-Y H:i', strtotime($data['tanggal_pengajuan'])); ?></td>
                                        <td><?php echo htmlspecialchars($data['nama_pengusul']); ?></td>
                                        <td><?php echo htmlspecialchars($data['nip']); ?></td>
                                        <td><?php echo htmlspecialchars($data['instansi']); ?></td> 
                                        <td><?php echo htmlspecialchars($data['provinsi']); ?></td> 
                                        <td><?php echo htmlspecialchars($data['kota_kab']); ?></td>
                                        
                                        <!-- Tampilkan Status dengan Badge Warna yang Benar -->
                                        <td><span class="data-status <?php echo $badge_class; ?>"><?php echo $status_display; ?></span></td>
                                        
                                        <td>
                                            <!-- Mengarahkan ke eval_rekom.php dengan ID yang benar -->
                                            <a href="eval_rekom.php?id=<?php echo htmlspecialchars($data['id']); ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($data_rekomendasi)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">Tidak ada data yang ditemukan sesuai kriteria.</td>
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
    
    <?php 
    include 'template/footer.php'; 
    ?> 

</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        $('#rekomendasiTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            // DataTables sorting default dihilangkan karena sorting utama dilakukan oleh server (PHP/SQL)
            "order": [] 
        });
    });
</script>

<?php
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>