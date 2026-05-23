<?php
/**
 * FILE: index_verifikator.php (Update: Fix Table Numbering & Enhanced Rekap UI)
 * DESKRIPSI: Daftar Pengajuan Uji Kompetensi Khusus Perpindahan Jabatan
 * TAMPILAN: AdminLTE 3 Standard dengan Modern Rekap Box (High Contrast Badge)
 * TERAKHIR DIPERBARUI: Februari 2026
 */

// ==================================================================================
// 1. PENGATURAN AWAL: SESSION & AUTORISASI
// ==================================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------------------------------
// 2. PANGGIL FILE PENDUKUNG KRITIS & INISIALISASI
// ----------------------------------------------------------------------------------
require_once 'auth_guard.php'; 
require_once 'koneksi.php';    

if (!isset($conn) || $conn === false) {
    $conn = null;
}

$user_id_sesi    = $_SESSION['user_id_sesi']    ?? 0; 
$user_nip_sesi   = $_SESSION['user_nip_sesi']   ?? '';
$user_nama_sesi  = $_SESSION['user_nama_sesi']  ?? 'Pengguna JF'; 
$user_role_sesi  = $_SESSION['user_role_sesi']  ?? 'User'; 
$user_email_sesi = $_SESSION['user_email_sesi'] ?? 'user@instansi.go.id'; 
$foto_session    = $_SESSION['foto_user_sesi']  ?? '';

$user_nama       = $_SESSION['nama_lengkap'] ?? $user_nama_sesi; 

$page        = 'ujikom'; 
$sub_page    = 'list_perpindahan'; 
$page_title  = 'Daftar Pengajuan Perpindahan Jabatan'; 

$NAMA_TABEL_PENGAJUAN = "pengajuan_ujikom"; 
$data_pengajuan       = []; 
$is_error             = false;

// --- Inisialisasi Counter Rekap ---
$count_lengkap = 0; 
$count_proses  = 0; 
$count_kosong  = 0;
$rekap_status  = []; 

// ----------------------------------------------------------------------------------
// 3. BLOK LOGIKA & FETCH DATA UTAMA
// ----------------------------------------------------------------------------------
$allowed_statuses = [
    'Menunggu Verifikasi', 'Verifikasi Dokumen', 'Perlu Perbaikan', 
    'Lulus Administrasi', 'Disetujui Verifikator', 'Disetujui PPSDM', 
    'Disetujui Direktur', 'Selesai Uji', 'proses verifikasi',
    'proses Evaluasi PPSDM', 'Proses Evaluasi Direktur'
];

$filter_status = $_GET['status'] ?? 'Semua Status';

$query_parts = ["verifikator_id = ?", "TRIM(jenis_pengajuan) LIKE ?"];
$params = [$user_id_sesi, 'Perpindahan%'];
$types = "is";

if ($filter_status !== 'Semua Status' && in_array($filter_status, $allowed_statuses)) {
    $query_parts[] = "status_pengajuan = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$where_clause = implode(" AND ", $query_parts);

try {
    $sql = "SELECT id, nip, nama, jenis_pengajuan, tanggal_pengajuan, status_pengajuan, progres_kelengkapan 
            FROM {$NAMA_TABEL_PENGAJUAN} WHERE {$where_clause} ORDER BY tanggal_pengajuan DESC"; 
    
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();
        $data_pengajuan = $result->fetch_all(MYSQLI_ASSOC);
        $total_data_ditemukan = count($data_pengajuan);
        $stmt->close();

        foreach ($data_pengajuan as $row) {
            // Rekap Kelengkapan
            $prog = (int)$row['progres_kelengkapan'];
            if ($prog >= 100) { $count_lengkap++; }
            elseif ($prog > 0) { $count_proses++; }
            else { $count_kosong++; }

            // Rekap Status Dinamis
            $st_name = $row['status_pengajuan'];
            if (!empty($st_name)) {
                if (!isset($rekap_status[$st_name])) {
                    $rekap_status[$st_name] = 0;
                }
                $rekap_status[$st_name]++;
            }
        }
        ksort($rekap_status);
    }
} catch (Exception $e) {
    $is_error = true;
    $error_message = "❌ Error Database! " . htmlspecialchars($e->getMessage());
}

require_once 'template/header.php'; 
require_once 'template/navbar.php'; 
require_once 'template/sidebar.php'; 
?>

<style>
    /* MODERNISED REKAP BOX STYLE */
    .rekap-card-main {
        background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .rekap-card-side {
        background: linear-gradient(135deg, #141e30, #243b55);
        border: none;
        border-radius: 15px;
    }
    .modern-status-item {
        background: rgba(255, 255, 255, 0.07);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 12px 15px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        transition: transform 0.2s, background 0.2s;
    }
    .modern-status-item:hover {
        transform: translateY(-3px);
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .status-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 1.1rem;
    }
    .status-label {
        color: rgba(255,255,255,0.9);
        font-weight: 500;
        font-size: 0.88rem;
        flex-grow: 1;
        margin-bottom: 0;
    }
    .status-badge-count {
        background: #ffc107; 
        color: #000;       
        min-width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px; 
        font-weight: 800;
        font-size: 0.85rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    .scroll-rekap-modern {
        max-height: 280px;
        overflow-y: auto;
        padding: 10px;
    }
    .scroll-rekap-modern::-webkit-scrollbar { width: 5px; }
    .scroll-rekap-modern::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
</style>

<div class="content-wrapper"> 
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><?php echo $page_title; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Ujikom</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card h-100 rekap-card-main">
                        <div class="card-header border-0 bg-transparent">
                            <h3 class="card-title text-white font-weight-bold">
                                <i class="fas fa-chart-pie mr-2 text-warning"></i> Rekapitulasi Status Pengajuan
                            </h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row scroll-rekap-modern">
                                <?php if (empty($rekap_status)): ?>
                                    <div class="col-12 text-center py-4">
                                        <p class="text-white-50 small">Tidak ada data status untuk ditampilkan.</p>
                                    </div>
                                <?php else:
                                    $chunks = array_chunk($rekap_status, ceil(count($rekap_status) / 2), true);
                                    foreach ($chunks as $chunk): ?>
                                        <div class="col-md-6">
                                            <?php foreach ($chunk as $status_label => $jumlah): 
                                                $icon = "fa-tag"; $icon_color = "text-info";
                                                // Icon tanpa fa-spin
                                                if(stripos($status_label, 'Perbaikan') !== false) { $icon = "fa-exclamation-circle"; $icon_color = "text-danger"; }
                                                elseif(stripos($status_label, 'Disetujui') !== false || stripos($status_label, 'Lulus') !== false) { $icon = "fa-check-circle"; $icon_color = "text-success"; }
                                                elseif(stripos($status_label, 'Proses') !== false || stripos($status_label, 'Verifikasi') !== false) { $icon = "fa-clock"; $icon_color = "text-warning"; }
                                            ?>
                                                <div class="modern-status-item">
                                                    <div class="status-icon-box bg-dark">
                                                        <i class="fas <?= $icon; ?> <?= $icon_color; ?>"></i>
                                                    </div>
                                                    <p class="status-label"><?= $status_label; ?></p>
                                                    <span class="status-badge-count"><?= $jumlah; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; 
                                endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 rekap-card-side">
                        <div class="card-header border-0 bg-transparent">
                            <h3 class="card-title font-weight-bold text-white">
                                <i class="fas fa-tasks mr-2 text-info"></i> Rekap Kelengkapan
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="modern-status-item">
                                <div class="status-icon-box" style="background: rgba(0, 255, 204, 0.1);">
                                    <i class="fas fa-check-double" style="color: #00ffcc;"></i>
                                </div>
                                <p class="status-label">Lengkap (100%)</p>
                                <span class="status-badge-count"><?= $count_lengkap; ?></span>
                            </div>
                            <div class="modern-status-item">
                                <div class="status-icon-box" style="background: rgba(255, 204, 0, 0.1);">
                                    <i class="fas fa-hourglass-end" style="color: #ffcc00;"></i>
                                </div>
                                <p class="status-label">Proses Verifikasi (< 100%)</p>
                                <span class="status-badge-count"><?= $count_proses; ?></span>
                            </div>
                            <div class="modern-status-item">
                                <div class="status-icon-box" style="background: rgba(255, 102, 102, 0.1);">
                                    <i class="fas fa-folder-open" style="color: #ff6666;"></i>
                                </div>
                                <p class="status-label">Belum Diverifikasi (0%)</p>
                                <span class="status-badge-count"><?= $count_kosong; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card card-indigo card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list mr-1"></i>
                                Tugas Verifikasi Saya (Disposisi Kasubdit)
                                <span class="badge badge-primary ml-2">(<?php echo $total_data_ditemukan ?? 0; ?> Data)</span>
                            </h3>
                        </div>
                        
                        <div class="card-body">
                            <div class="mb-4 bg-light p-3" style="border-radius: 5px;">
                                <form method="GET" action="index_verifikator.php" class="form-inline">
                                    <div class="form-group mr-3">
                                        <label class="mr-2 font-weight-bold">Status:</label>
                                        <select name="status" class="form-control form-control-sm">
                                            <option value="Semua Status">Semua Status</option>
                                            <?php foreach ($allowed_statuses as $status_option): ?>
                                                <option value="<?= $status_option; ?>" <?= ($status_option == $filter_status) ? 'selected' : ''; ?>>
                                                    <?= $status_option; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-filter mr-1"></i> Tampilkan</button>
                                    <a href="index_verifikator.php" class="btn btn-default btn-sm ml-2"><i class="fas fa-sync"></i> Reset</a>
                                </form>
                            </div>

                            <div class="table-responsive"> 
                                <table id="tabelUjikomPerpindahan" class="table table-bordered table-striped">
                                    <thead class="bg-indigo text-white">
                                        <tr>
                                            <th>No.</th>
                                            <th>NIP</th>
                                            <th>Nama</th>
                                            <th>Kelengkapan</th>
                                            <th>Tanggal Pengajuan</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1; 
                                        foreach ($data_pengajuan as $data): 
                                            $status = $data['status_pengajuan'];
                                            $badge_class = 'badge-secondary';
                                            switch ($status) {
                                                case 'Lulus Administrasi':        $badge_class = 'bg-primary'; break;
                                                case 'Disetujui Verifikator':     $badge_class = 'bg-success'; break;
                                                case 'Disetujui Direktur':        $badge_class = 'bg-navy'; break;
                                                case 'Disetujui PPSDM':           $badge_class = 'bg-success'; break;
                                                case 'Proses Direktur':           $badge_class = 'bg-indigo'; break;
                                                case 'Proses Evaluasi PPSDM':     $badge_class = 'bg-lime'; break;
                                                case 'Proses Verifikasi':         $badge_class = 'bg-cyan'; break;
                                                case 'Perlu Perbaikan':           $badge_class = 'bg-danger'; break;
                                                case 'Menunggu Verifikasi':       $badge_class = 'bg-warning'; break;
                                                case 'Verifikasi Dokumen':        $badge_class = 'bg-info'; break;
                                                default:                          $badge_class = 'bg-secondary'; break;
                                            }
                                        ?>
                                            <tr>
                                                <td class="text-center"><?php echo $no++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($data['nip']); ?></strong></td>
                                                <td><strong><?php echo htmlspecialchars($data['nama']); ?></strong></td>
                                                <td>
                                                    <?php $progres = (int)$data['progres_kelengkapan']; 
                                                          $p_class = ($progres >= 100) ? 'bg-success' : (($progres >= 50) ? 'bg-warning' : 'bg-danger'); ?>
                                                    <div class="progress progress-xs mb-1">
                                                        <div class="progress-bar <?= $p_class; ?>" style="width: <?= $progres; ?>%"></div>
                                                    </div>
                                                    <small class="badge <?= $p_class; ?>"><?= $progres; ?>%</small>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($data['tanggal_pengajuan'])); ?></td>
                                                <td><span class="badge <?php echo $badge_class; ?> p-2"><?php echo htmlspecialchars($status); ?></span></td>
                                                <td>
                                                    <a href="detail_verif_perpindahan.php?id=<?php echo $data['id']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-search"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
    $(function () {
      $("#tabelUjikomPerpindahan").DataTable({
        "responsive": true, "autoWidth": false, "order": [[4, "desc"]],
        "language": { "search": "Cari Nama/NIP:", "lengthMenu": "Tampilkan _MENU_ baris" }
      });
    });
</script>