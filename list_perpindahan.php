<?php
// FILE: list_perpindahan.php - Daftar Pengajuan Uji Kompetensi Khusus Perpindahan Jabatan

// =========================================================
// 1. PENGATURAN AWAL: OUTPUT BUFFERING & SESSION
// =========================================================
// ✅ Pastikan ob_start() ada di baris paling awal
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------------------------------
// 2. PANGGIL FILE PENDUKUNG KRITIS
// ----------------------------------------------------------------------------------
require_once 'auth_guard.php'; // Asumsi file ini ada
require_once 'koneksi.php';     // Asumsi file ini ada



// --- PENGGUNAAN DATA SESSION ---
// Menggunakan ?? untuk memberikan nilai default jika variabel sesi belum diset (misalnya, jika auth_guard gagal)
$user_data = [
    'nama'      => $user_nama_sesi ?? 'Pengguna JF', 
    'role'      => $user_role_sesi ?? 'User', 
    'email'     => $user_email_sesi ?? 'user@instansi.go.id', 
    'join_date' => $_SESSION['join_date'] ?? 'Maret 2023'
];

// --- DEKLARASI VARIABEL UNTUK HIGHLIGHT MENU SIDEBAR ---
$page = 'ujikom'; 
$sub_page = 'list_perpindahan'; 
$page_title = 'Daftar Pengajuan Perpindahan Jabatan'; 
// ----------------------------------------------------------------------------------

// --- PENGATURAN FILTER DARI INPUT GET ---
// Ambil status dari URL, default kosong
$filter_status = $_GET['status'] ?? ''; 
// Tambahan: Filter Persentase Kelengkapan
$filter_persentase = $_GET['persentase'] ?? '';
// Ambil sorting dari URL (tetap ada untuk ORDER BY), default terbaru (DESC)
$sort_by = $_GET['sort_by'] ?? 'terbaru'; 

// --- PENGATURAN GLOBAL DATABASE ---
$conn = $conn ?? null; // Pastikan $conn diinisialisasi untuk pengecekan berikutnya
$NAMA_TABEL_PENGAJUAN = "pengajuan_ujikom"; 
$data_pengajuan = []; 
$is_error = false;
$error_message = ''; 


// ----------------------------------------------------------------------------------
// 3. BLOK FETCH DATA (MENGGUNAKAN PREPARED STATEMENT UNTUK ROBUSTNESS)
// ----------------------------------------------------------------------------------
$stmt = null; // Inisialisasi statement untuk penutupan di blok finally
try {
    if (!isset($conn) || !$conn) {
        throw new Exception("Koneksi database tidak tersedia. Mohon cek file koneksi.php.");
    }
    
    // --- KONSTRUKSI QUERY DINAMIS BERDASARKAN FILTER ---
    $where_clauses = ["TRIM(jenis_pengajuan) LIKE ?"];
    $params = ['Perpindahan%'];
    $params_types = 's';

    // 1. Tambahkan filter status jika ada
    if (!empty($filter_status) && $filter_status !== 'Semua Status') {
        // Hanya menerima status yang valid dan aman
        $valid_statuses = [
            
            'Perlu Perbaikan',
            'Terjadwal',
            'Proses Evaluasi PPSDM',
            'Proses Evaluasi Direktur',
            'Menunggu Verifikasi',
            'Disetujui Verifikator',
            'Disetujui Direktur',
            'Disetujui PPSDM',
            'ditolak'
        ];
        if (in_array($filter_status, $valid_statuses)) {
            $where_clauses[] = "status_pengajuan = ?";
            $params[] = $filter_status;
            $params_types .= 's';
        } 
    }
    
    // 2. Tambahkan filter persentase kelengkapan
    if (!empty($filter_persentase)) {
        if ($filter_persentase === 'lebih_85') {
            $where_clauses[] = "kelengkapan >= 85"; // Filter Kelengkapan >= 85%
        } elseif ($filter_persentase === 'kurang_85') {
            $where_clauses[] = "kelengkapan < 85";  // Filter Kelengkapan < 85%
        }
    }
    

    // 3. Tentukan urutan tanggal (default sorting)
    $order_clause = ($sort_by == 'terlama') ? 'tanggal_pengajuan ASC' : 'tanggal_pengajuan DESC';

    // Gabungkan klausa WHERE
    $where_sql = implode(' AND ', $where_clauses);
    
    $sql = "
        SELECT 
            id, nip, nama, jenis_pengajuan, tanggal_pengajuan, status_pengajuan, kelengkapan
        FROM 
            {$NAMA_TABEL_PENGAJUAN}
        WHERE 
            {$where_sql}
        ORDER BY 
            {$order_clause}
    "; 
    
    // Persiapan statement
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Gagal menyiapkan statement SQL. Pastikan kolom dan sintaksis benar. Error: " . $conn->error);
    }
    
    // Binding parameter dinamis
    // Menggunakan call_user_func_array untuk bind_param dengan array parameter dinamis
    if (!empty($params)) {
        // Mempersiapkan array referensi untuk bind_param
        $bind_args = array_merge([$params_types], $params);
        $bind_refs = [];
        foreach ($bind_args as $key => $value) {
            $bind_refs[$key] = &$bind_args[$key];
        }
        
        // Memanggil bind_param
        if (!call_user_func_array([$stmt, 'bind_param'], $bind_refs)) {
            throw new Exception("Gagal mengikat parameter.");
        }
    }

    // Eksekusi statement
    if (!$stmt->execute()) {
        throw new Exception("Gagal mengeksekusi query. Error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result === false) {
        throw new Exception("Result set kosong atau gagal diambil.");
    }

    $data_pengajuan = $result->fetch_all(MYSQLI_ASSOC);
    $error_message = ''; 

} catch (Exception $e) {
    $is_error = true;
    $error_message = "❌ Error Database! Gagal memproses data: " . htmlspecialchars($e->getMessage());
    $data_pengajuan = [];
} finally {
    // Pastikan statement ditutup, jika telah dibuat
    if ($stmt) {
        $stmt->close();
    }
}
// ----------------------------------------------------------------------------------

// --- 4. PANGGIL TEMPLATE HEADER & SIDEBAR ---
// header.php akan membuka <div class="wrapper">
// sidebar.php akan berada di dalam .wrapper
include 'template/header.php'; 
include 'template/sidebar.php'; 
?>

<div class="content-wrapper"> 
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="m-0 text-dark"><?php echo $page_title; ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-indigo card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Data Pengajuan Khusus Perpindahan Jabatan</h3>
                        </div>
                        <div class="card-body">
                            
                            <div class="mb-4 p-3 border border-indigo rounded-lg bg-light">
                                <form method="GET" action="list_perpindahan.php" class="form-row align-items-end">

                                    <div class="col-md-4 col-sm-6 mb-3">
                                        <label for="filter_status" class="text-sm">Filter Status</label>
                                        <select name="status" id="filter_status" class="form-control form-control-sm">
                                            <option value="">-- Pilih --</option>
                                            <?php 
                                            // Daftar status yang ada di switch case
                                            $available_statuses = [
                                                'Perlu Perbaikan',
                                                'Terjadwal',
                                                'Proses Evaluasi PPSDM',
                                                'Proses Evaluasi Direktur',
                                                'Menunggu Verifikasi',
                                                'Disetujui Verifikator',
                                                'Disetujui Direktur',
                                                'Disetujui PPSDM',
                                                'ditolak'
                                            ];
                                            foreach ($available_statuses as $s): 
                                                $selected = ($s == $filter_status) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $selected; ?>>
                                                    <?php echo htmlspecialchars($s); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 col-sm-6 mb-3">
                                        <label for="filter_persentase" class="text-sm">Filter Persentase Kelengkapan</label>
                                        <select name="persentase" id="filter_persentase" class="form-control form-control-sm">
                                            <option value="" <?php echo ($filter_persentase == '') ? 'selected' : ''; ?>>--Pilih--</option>
                                            <option value="lebih_85" <?php echo ($filter_persentase == 'lebih_85') ? 'selected' : ''; ?>>> 85%</option>
                                            <option value="kurang_85" <?php echo ($filter_persentase == 'kurang_85') ? 'selected' : ''; ?>>< 85%</option>
                                        </select>
                                
                                    </div>
                                    <div class="col-md-4 col-sm-12 mb-3">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                    
                                    <a href="list_perpindahan.php" class="btn btn-sm btn-outline-secondary ml-2">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                     </div>
                                </form>
                            </div>
                            <?php 
                            // Tampilkan pesan error/informasi
                            if (!empty($error_message)): 
                            ?>
                                <div class="alert alert-<?php echo $is_error ? 'danger' : 'warning'; ?> alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> <?php echo $is_error ? 'Gagal Total!' : 'Informasi!'; ?></h5> 
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($data_pengajuan) && !$is_error): ?>
                                <div class="alert alert-info alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fas fa-info"></i> Informasi!</h5>
                                    Tidak ditemukan data pengajuan **Perpindahan Jabatan** yang sesuai dengan filter yang diterapkan.
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($data_pengajuan)): ?>
                            
                            <div class="table-responsive"> 
                                <table id="tabelUjikomPerpindahan" class="table table-bordered table-striped dataTable dtr-inline">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%">No.</th>
                                            <th style="width: 15%">NIP</th>
                                            <th style="width: 25%">Nama</th>
                                            <th style="width: 15%">Jenis Pengajuan</th> 
                                            <th style="width: 15%">Tanggal Pengajuan</th>
                                            <th style="width: 10%">Status</th>
                                            <th style="width: 15%">kelengkapan</th>
                                            <th style="width: 15%">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        foreach ($data_pengajuan as $data): 
                                            $status = $data['status_pengajuan'] ?? 'Menunggu Verifikasi';
                                            $display_status = htmlspecialchars($status); // Teks default
                                            
                                            $badge_class = 'badge-secondary';
                                            switch ($status) {
                                                case 'Lulus Administrasi': 
                                                    $badge_class = 'bg-primary'; 
                                                    break;
                                                case 'Disetujui': // Misalnya status untuk yang sudah diverifikasi dan OK
                                                case 'Selesai Uji': 
                                                    $badge_class = 'bg-success'; 
                                                    break; 
                                                case 'Perlu Perbaikan': 
                                                    $badge_class = 'bg-danger'; 
                                                    $display_status = 'Perlu Perbaikan'; // PERUBAHAN TEXT: Ditolak -> Perlu Perbaikan
                                                    break;
                                                case 'Menunggu Verifikasi': 
                                                    $badge_class = 'bg-warning'; 
                                                    break;
                                                case 'Proses Verifikasi': 
                                                    $badge_class = 'bg-cyan'; 
                                                    break;
                                                case 'Pending': 
                                                    $badge_class = 'bg-orange'; 
                                                    break;
                                                case 'Terjadwal': 
                                                    $badge_class = 'bg-primary'; 
                                                    break;
                                                case 'Proses Evaluasi PPSDM': 
                                                    $badge_class = 'bg-lime'; 
                                                    break;
                                                case 'Verifikasi Dokumen': 
                                                    $badge_class = 'bg-info'; 
                                                    break;
                                                default: 
                                                    $badge_class = 'bg-secondary'; 
                                                    break;
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($data['nip'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($data['nama'] ?? 'N/A'); ?></td>    
                                                <td>
                                                    <?php 
                                                    $jenis_pengajuan = htmlspecialchars($data['jenis_pengajuan'] ?? 'N/A'); 
                                                    $jenis_badge = 'badge-dark'; 
                                                    ?>
                                                    <span class="badge <?php echo $jenis_badge; ?> border border-secondary px-2">
                                                        <?php echo $jenis_pengajuan; ?>
                                                    </span>
                                                </td> 
                                                <td>
                                                    <?php 
                                                    // Format tanggal dari YYYY-MM-DD menjadi DD-MM-YYYY
                                                    $tanggal = $data['tanggal_pengajuan'] ?? date('Y-m-d');
                                                    // Tambahkan pengecekan apakah tanggal valid sebelum diformat
                                                    $timestamp = strtotime($tanggal);
                                                    if ($timestamp !== false) {
                                                        echo date('d-m-Y', $timestamp);
                                                    } else {
                                                        echo 'Tgl. Invalid';
                                                    }
                                                    ?>
                                                </td>
                                                
                                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $display_status; ?></span></td>
                                                
                                                <td>
                                                <?php
                                                // Ambil nilai kelengkapan (misalnya 88.89)
                                                $kelengkapan_float = $data['kelengkapan'] ?? 0.00;
                                                // Bulatkan nilai float untuk progress bar
                                                $kelengkapan_int = round($kelengkapan_float); 

                                                // Tentukan warna progress bar berdasarkan aturan baru:
                                                if ($kelengkapan_int > 85) {
                                                    // > 85% (86% ke atas) -> Biru
                                                    $progress_class = 'bg-primary'; 
                                                } elseif ($kelengkapan_int >= 65) {
                                                    // 65% - 85% -> Kuning
                                                    // Catatan: Jika 85%, akan masuk sini. Jika ingin >85, maka perlu pakai else if yang sangat spesifik.
                                                    // Berdasarkan permintaan (>85% biru, 65%-84% kuning), maka:
                                                    $progress_class = 'bg-warning'; 
                                                } else {
                                                    // < 65% (64% ke bawah) -> Merah
                                                    $progress_class = 'bg-danger'; 
                                                }

                                                // Tambahan: Jika 100%, mungkin tetap ingin highlight hijau (opsional, tapi disarankan)
                                                if ($kelengkapan_int == 100) {
                                                    $progress_class = 'bg-success'; 
                                                }
                                                
                                                // Tampilkan progress bar
                                                ?>
                                                <div class="progress progress-sm" style="height: 15px;">
                                                    <div class="progress-bar <?php echo $progress_class; ?>" 
                                                        role="progressbar" 
                                                        aria-valuenow="<?php echo $kelengkapan_int; ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100" 
                                                        style="width: <?php echo $kelengkapan_int; ?>%;">
                                                    </div>
                                                </div>
                                                <small class="text-sm font-weight-bold d-block mt-1">
                                                    <?php echo number_format($kelengkapan_float, 2) . '%'; ?>
                                                </small>
                                            </td>
                                                
                                                <td>
                                                    <a href="detail_verif_perpindahan.php?id=<?php echo htmlspecialchars($data['id']); ?>" 
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-search"></i> Detail / Verifikasi
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
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
    <strong>© <?php echo date('Y'); ?> Instansi Pembina Jabatan Fungsional Penata Kelola Perumahan</strong> — Semua Hak Dilindungi.
</footer>
<aside class="control-sidebar control-sidebar-dark"></aside>

<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    // Koneksi akan ditutup di sini
    mysqli_close($conn);
}

// --- PANGGIL FOOTER (Menutup tag body/html dan menambahkan script JS AdminLTE) ---
include 'template/footer.php';

// ✅ SOLUSI KRITIS 3: TUTUP OUTPUT BUFFERING
ob_end_flush(); 
?>