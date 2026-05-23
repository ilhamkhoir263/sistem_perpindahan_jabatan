<?php
/**
 * ==================================================================================
 * FILE: index_kasubdit.php
 * DESKRIPSI: Panel Monitoring & Disposisi Kasubdit
 * UPDATE: Tambahan Warna Lulus & Tidak Lulus, Gelombang, Perbaikan Join Tabel, & Filter Export Excel
 * ==================================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'auth_guard.php'; 
require_once 'koneksi.php';    

if (!isset($conn) || !$conn) {
    die("Fatal Error: Koneksi database tidak tersedia.");
}

// 1. IDENTITAS HALAMAN
$page_title = "Panel Monitoring Kasubdit";

// 2. AMBIL DATA USER
$session_user_id = $_SESSION['user_id_sesi'] ?? 0;
$user_nama = "Kasubdit"; 
$sql_user = "SELECT nama FROM users WHERE id = ?"; 
if ($stmt_user = $conn->prepare($sql_user)) {
    $stmt_user->bind_param("i", $session_user_id);
    $stmt_user->execute();
    $res_user = $stmt_user->get_result();
    if ($user_data = $res_user->fetch_assoc()) { $user_nama = $user_data['nama']; }
}

// 3. AMBIL DAFTAR VERIFIKATOR
$sql_verifikator = "SELECT id, nama FROM users WHERE role = 'user_verifikator'";
$res_verif = $conn->query($sql_verifikator);
$list_verifikator = ($res_verif) ? $res_verif->fetch_all(MYSQLI_ASSOC) : [];

// 4. AMBIL DAFTAR GELOMBANG
$sql_gelombang = "SELECT id, gelombang, bln_gelombang FROM tb_gelombang ORDER BY id DESC";
$res_gel = $conn->query($sql_gelombang);
$list_gelombang = ($res_gel) ? $res_gel->fetch_all(MYSQLI_ASSOC) : [];

// 5. QUERY DATA PENGAJUAN (UTAMA)
$sql = "SELECT p.*, v.nama as nama_verifikator, g.gelombang as nama_gelombang, g.bln_gelombang
        FROM pengajuan_ujikom p
        LEFT JOIN users v ON p.verifikator_id = v.id
        LEFT JOIN tb_gelombang g ON p.gelombang = g.id
        ORDER BY p.tanggal_pengajuan DESC";
$result = $conn->query($sql);
$data_pengusul = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];

// ==================================================================================
// LOGIKA EXPORT EXCEL (Filter Berdasarkan Gelombang Pilihan)
// ==================================================================================
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $gelombang_filter = $_GET['gelombang_export'] ?? '';
    
    // Cari nama gelombang untuk judul
    $nama_gel_title = "Gelombang Tidak Diketahui";
    foreach ($list_gelombang as $g) {
        if ($g['id'] == $gelombang_filter) {
            $nama_gel_title = $g['gelombang'] . (!empty($g['bln_gelombang']) ? " - " . $g['bln_gelombang'] : "");
            break;
        }
    }

    $filename = "Data_Peserta_" . preg_replace('/[^A-Za-z0-9]/', '_', $nama_gel_title) . ".xls";
    header("Content-Type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<table border="1">';
    echo '<tr><th colspan="8" style="font-size: 12pt; font-weight: bold; text-align: center; padding: 10px;">Data Peserta Ujikom - ' . htmlspecialchars($nama_gel_title) . '</th></tr>';
    echo '<tr style="font-weight: bold; text-align: center;">
            <th>No</th>
            <th>Nama</th>
            <th>NIP</th>
            <th>No HP/WA</th>
            <th>Pangkat/Golongan</th>
            <th>Jabatan Saat Ini</th>
            <th>JF PKP yang Dituju</th>
            <th>Unit Kerja</th>
          </tr>';
    
    $no_ex = 1;
    foreach ($data_pengusul as $p) {
        // FILTER: Lewati jika gelombangnya tidak sesuai dengan yang dipilih
        if ($p['gelombang'] != $gelombang_filter) {
            continue;
        }

        $hp = htmlspecialchars($p['no_hp'] ?? $p['hp'] ?? '-');
        $pangkat = htmlspecialchars($p['pangkat'] ?? '-');
        $jabatan = htmlspecialchars($p['jabatan_saat_ini'] ?? '-');
        $jf_tujuan = htmlspecialchars($p['jf_pkp_tujuan'] ?? '-');
        $unit = htmlspecialchars($p['unit_saat_ini'] ?? $p['unit_kerja'] ?? '-');

        echo '<tr>';
        echo '<td style="text-align:center;">' . $no_ex++ . '</td>';
        echo '<td>' . htmlspecialchars($p['nama']) . '</td>';
        echo '<td style="mso-number-format:\'\@\';">' . htmlspecialchars($p['nip']) . '</td>';
        echo '<td style="mso-number-format:\'\@\';">' . $hp . '</td>';
        echo '<td>' . $pangkat . '</td>';
        echo '<td>' . $jabatan . '</td>';
        echo '<td>' . $jf_tujuan . '</td>';
        echo '<td>' . $unit . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit; // Berhenti di sini
}
// ==================================================================================

// --- HITUNG STATISTIK ---
$count_v1 = 0; $count_v2 = 0; $count_belum = 0; $rekap_status = [];
foreach ($data_pengusul as $row) {
    if (!$row['verifikator_id']) { $count_belum++; } 
    else {
        if (stripos($row['nama_verifikator'], '1') !== false) { $count_v1++; } 
        elseif (stripos($row['nama_verifikator'], '2') !== false) { $count_v2++; }
    }
    $st = $row['status_pengajuan'] ?? 'Unknown';
    if (!isset($rekap_status[$st])) { $rekap_status[$st] = 0; }
    $rekap_status[$st]++;
}

require_once 'template/header.php'; 
require_once 'template/navbar.php'; 
require_once 'template/sidebar.php'; 
?>

<style>
    :root { --banner-kasubdit: linear-gradient(135deg, #155263 0%, #2c7873 100%); }
    .content-wrapper { background-color: #f4f7f6; }
    .main-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    .dashboard-banner { background: var(--banner-kasubdit); color: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .status-badge { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: bold; }
    .stat-box { background: rgba(255, 255, 255, 0.15); border-radius: 10px; padding: 10px 15px; text-align: center; min-width: 130px; border: 1px solid rgba(255, 255, 255, 0.2); }
    .stat-box h3 { margin-bottom: 0; font-weight: 800; font-size: 26px; }
    .rekap-container { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 15px; }
    .rekap-item { flex: 0 0 auto; background: white; padding: 10px 15px; border-radius: 10px; border-left: 4px solid #6c757d; box-shadow: 0 2px 5px rgba(0,0,0,0.05); min-width: 160px; }
    .badge-verif-none { background-color: #dc3545 !important; color: white !important; font-weight: 700 !important; padding: 4px 8px; border-radius: 4px; font-size: 10px; }
    .btn-detail { background-color: #17a2b8; color: white; font-weight: bold; border-radius: 8px; border: none; transition: 0.3s; }
    .btn-direktur-main { background-color: #6610f2; color: white; font-weight: bold; border-radius: 8px; border: none; transition: 0.3s; padding: 8px 15px; }
    .btn-direktur-main:hover { background-color: #520dc2; color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 16, 242, 0.4); }
    .bulk-action-area { display: none; margin-bottom: 15px; animation: fadeIn 0.3s; }
    .filter-card { background: #fff; border-radius: 10px; margin-bottom: 15px; border-left: 5px solid #2c7873; }
    .step-wizard { display: none; } .step-wizard.active { display: block; }
    #wizard_data_body tr:hover { background-color: #f1f1f1; cursor: pointer; }
</style>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-4 mt-3">
                <div class="col-sm-6">
                    <h1 class="font-weight-bold text-dark"><i class="fas fa-desktop text-success mr-2"></i> Monitoring & Disposisi</h1>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="dashboard-banner">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h2 class="font-weight-bold mb-1">Kelola Disposisi</h2>
                                <p class="mb-0 opacity-8">Pantau progres usulan secara real-time</p>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap justify-content-end" style="gap: 15px;">
                                    <div class="stat-box"><h3><?= count($data_pengusul); ?></h3><small>Total Usulan</small></div>
                                    <div class="stat-box" style="border-bottom: 4px solid #007bff;"><h3><?= $count_v1; ?></h3><small>Verifikator 1</small></div>
                                    <div class="stat-box" style="border-bottom: 4px solid #28a745;"><h3><?= $count_v2; ?></h3><small>Verifikator 2</small></div>
                                    <div class="stat-box" style="border-bottom: 4px solid #dc3545; background: rgba(220, 53, 69, 0.2);"><h3><?= $count_belum; ?></h3><small>Belum Disposisi</small></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <h5 class="font-weight-bold mb-3 text-muted"><i class="fas fa-chart-pie mr-2"></i> Rekapitulasi Per Status</h5>
            <div class="rekap-container">
                <?php 
                $status_colors = [
                    'Menunggu Verifikasi' => '#ffc107', 
                    'Proses Verifikasi' => '#fd7e14', 
                    'Lulus Administrasi' => '#007bff', 
                    'Disetujui Verifikator' => '#28a745', 
                    'Proses Direktur' => '#20c997', 
                    'Perlu Perbaikan' => '#dc3545', 
                    'Menunggu Disposisi' => '#6610f2', 
                    'Terjadwal' => '#6f42c1',
                    'Lulus' => '#28a745',
                    'Tidak Lulus' => '#dc3545'
                ];
                foreach($rekap_status as $st_name => $st_count): 
                    $b_color = $status_colors[$st_name] ?? '#6c757d'; ?>
                    <div class="rekap-item" style="border-left-color: <?= $b_color; ?>;">
                        <span class="val" style="color: <?= $b_color; ?>;"><?= $st_count; ?></span>
                        <span class="lbl"><?= $st_name; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card filter-card elevation-1">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-1 text-muted font-weight-bold">FILTER:</div>
                        <div class="col-md-2">
                            <select id="filterJenis" class="form-control form-control-sm border-info">
                                <option value="">-- Semua Jenis --</option>
                                <option value="Perpindahan Jabatan">Perpindahan Jabatan</option>
                                <option value="Kenaikan Jabatan">Kenaikan Jabatan</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filterGelombang" class="form-control form-control-sm border-secondary">
                                <option value="">-- Semua Gelombang --</option>
                                <?php foreach($list_gelombang as $g): 
                                    $label_gel = htmlspecialchars($g['gelombang']) . (!empty($g['bln_gelombang']) ? " - " . htmlspecialchars($g['bln_gelombang']) : "");
                                ?>
                                    <option value="<?= htmlspecialchars($g['gelombang']); ?>"><?= $label_gel; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterStatus" class="form-control form-control-sm border-success">
                                <option value="">-- Semua Status --</option>
                                <?php foreach(array_keys($status_colors) as $st_opt): ?>
                                    <option value="<?= $st_opt; ?>"><?= $st_opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filterVerifikator" class="form-control form-control-sm border-primary">
                                <option value="">-- Semua Verifikator --</option>
                                <option value="BELUM DISPOSISI">BELUM DISPOSISI</option>
                                <?php foreach($list_verifikator as $v): ?>
                                    <option value="<?= htmlspecialchars($v['nama']); ?>"><?= htmlspecialchars($v['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 text-right"><button class="btn btn-sm btn-outline-secondary" onclick="resetFilter()"><i class="fas fa-undo"></i> Reset</button></div>
                    </div>
                </div>
            </div>

            <div class="card main-card">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-2"></i> Daftar Pengusul</h3>
                        <div class="d-flex">
                            <div id="bulkActionArea" class="bulk-action-area mr-2">
                                <button type="button" class="btn btn-primary font-weight-bold elevation-2 mr-2" id="btnBulkDisposisi"><i class="fas fa-users-cog mr-1"></i> Disposisi Massal (<span class="countCheck">0</span>)</button>
                                <button type="button" class="btn btn-info font-weight-bold elevation-2" id="btnBulkGelombang"><i class="fas fa-layer-group mr-1"></i> Set Gelombang (<span class="countCheck">0</span>)</button>
                            </div>
                            
                            <button type="button" class="btn btn-success font-weight-bold elevation-2 mr-2" data-toggle="modal" data-target="#modalExportExcel">
                                <i class="fas fa-file-excel mr-1"></i> Export Excel
                            </button>

                            <button type="button" class="btn btn-direktur-main elevation-2" data-toggle="modal" data-target="#modalKirimDirektur">
                                <i class="fas fa-paper-plane mr-1"></i> Kirim ke Direktur
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableKasubdit" class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="30"><input type="checkbox" id="checkAll"></th>
                                    <th>No</th>
                                    <th>Pengusul</th>
                                    <th>Jenis</th>
                                    <th>Gelombang</th> <th>Verifikator</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach ($data_pengusul as $row): 
                                    $status = $row['status_pengajuan'];
                                    
                                    if ($status == 'Lulus Administrasi') { $badge_class = 'bg-primary'; }
                                    elseif ($status == 'Disetujui Verifikator' || $status == 'Lulus') { $badge_class = 'bg-success'; }
                                    elseif ($status == 'Proses Direktur') { $badge_class = 'bg-teal'; }
                                    elseif ($status == 'Perlu Perbaikan' || $status == 'Tidak Lulus') { $badge_class = 'bg-danger'; }
                                    elseif ($status == 'Menunggu Verifikasi') { $badge_class = 'bg-warning'; }
                                    elseif ($status == 'Menunggu Disposisi') { $badge_class = 'bg-indigo'; }
                                    else { $badge_class = 'bg-secondary'; }
                                    
                                    $disp_gel = !empty($row['nama_gelombang']) ? $row['nama_gelombang'] : $row['gelombang'];
                                    $disp_bln = !empty($row['bln_gelombang']) ? " - " . $row['bln_gelombang'] : "";
                                    $tampil_gelombang = $disp_gel . $disp_bln;
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="check-child" value="<?= $row['id']; ?>"></td>
                                    <td><?= $no++; ?></td>
                                    <td><strong><?= htmlspecialchars($row['nama']); ?></strong><br><small class="text-muted"><?= htmlspecialchars($row['nip']); ?></small></td>
                                    <td><small><?= htmlspecialchars($row['jenis_pengajuan']); ?></small></td>
                                    <td> 
                                        <?php if(!empty($disp_gel)): ?>
                                            <span class="badge badge-outline-secondary" style="border: 1px solid #ddd; color: #555;"><i class="fas fa-tag mr-1"></i> <?= htmlspecialchars($tampil_gelombang); ?></span>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['verifikator_id'] ? '<span class="badge badge-info p-1">'.htmlspecialchars($row['nama_verifikator']).'</span>' : '<span class="badge-verif-none">BELUM DISPOSISI</span>'; ?></td>
                                    <td class="text-center"><span class="badge <?= $badge_class; ?> status-badge"><?= $status; ?></span></td>
                                    <td class="text-center">
                                        <div class="action-container">
                                            <a href="detail_verif_perpindahan.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-detail px-3"><i class="fas fa-eye"></i> Detail</a>
                                            <?php if (in_array($status, ['Menunggu Verifikasi', 'Menunggu Disposisi', 'Verifikasi Dokumen'])): ?>
                                                <button class="btn btn-sm btn-warning font-weight-bold btn-buka-disposisi px-3" data-id="<?= $row['id']; ?>" data-nama="<?= htmlspecialchars($row['nama']); ?>" data-verif="<?= $row['verifikator_id']; ?>" data-toggle="modal" data-target="#modalDisposisi"><i class="fas fa-share-square"></i> Disposisi</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalExportExcel" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-file-excel mr-2"></i> Export Data ke Excel</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="index_kasubdit.php" method="GET">
                <input type="hidden" name="export" value="excel">
                
                <div class="modal-body py-4">
                    <p>Pilih gelombang data peserta yang ingin Anda ekspor:</p>
                    <div class="form-group mt-3">
                        <label class="font-weight-bold text-muted">Pilih Gelombang: <span class="text-danger">*</span></label>
                        <select name="gelombang_export" class="form-control" required>
                            <option value="">-- Pilih Gelombang --</option>
                            <?php foreach($list_gelombang as $g): 
                                $exp_label = htmlspecialchars($g['gelombang']) . (!empty($g['bln_gelombang']) ? " - " . htmlspecialchars($g['bln_gelombang']) : "");
                            ?>
                                <option value="<?= $g['id']; ?>"><?= $exp_label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary shadow-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success font-weight-bold px-4 shadow">
                        <i class="fas fa-download mr-1"></i> Unduh Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKirimDirektur" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-indigo text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-paper-plane mr-2"></i> Kirim ke Direktur</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            
            <div id="step1" class="step-wizard active">
                <div class="modal-body py-4">
                    <div class="alert alert-info border-0 shadow-sm"><i class="fas fa-info-circle mr-2"></i> <strong>Langkah 1:</strong> Pilih gelombang tujuan pengiriman.</div>
                    <div class="form-group mt-4">
                        <label class="font-weight-bold">Tujuan Gelombang:</label>
                        <select id="wizard_gelombang" class="form-control form-control-lg border-primary">
                            <option value="">-- Pilih Gelombang --</option>
                            <?php foreach($list_gelombang as $g): 
                                $wiz_gel_label = htmlspecialchars($g['gelombang']) . (!empty($g['bln_gelombang']) ? " - " . htmlspecialchars($g['bln_gelombang']) : "");
                            ?>
                                <option value="<?= $g['id']; ?>" data-nama="<?= $wiz_gel_label; ?>"><?= $wiz_gel_label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-primary px-4 font-weight-bold shadow" id="btnNextStep">Selanjutnya <i class="fas fa-arrow-right ml-1"></i></button>
                </div>
            </div>

            <div id="step2" class="step-wizard">
                <div class="modal-body py-4">
                    <div class="alert alert-warning border-0 shadow-sm"><i class="fas fa-user-check mr-2"></i> <strong>Langkah 2:</strong> Pilih pengusul yang akan dimasukkan ke <strong><span id="text_gel_selected"></span></strong> dan dikirim ke Direktur.</div>
                    <div id="container_list_direktur" class="mt-3">
                        <div class="table-responsive" style="max-height: 350px; border: 1px solid #dee2e6; border-radius: 8px;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-dark text-white">
                                    <tr>
                                        <th width="50" class="text-center"><input type="checkbox" id="checkAllWizard"></th>
                                        <th>Nama Pengusul / NIP</th>
                                        <th class="text-center">Status Saat Ini</th>
                                    </tr>
                                </thead>
                                <tbody id="wizard_data_body"></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="wizard_empty" class="text-center py-5 d-none">
                        <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                        <p class="text-muted font-italic">Tidak ditemukan pengusul dengan status "Disetujui Verifikator".</p>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary shadow-sm" id="btnPrevStep"><i class="fas fa-arrow-left mr-1"></i> Kembali</button>
                    <button type="button" class="btn btn-success font-weight-bold px-4 shadow" id="btnFinalKirimDirektur">Kirim ke Direktur <i class="fas fa-check-circle ml-1"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDisposisi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-weight-bold" id="modalTitle">Tugaskan Verifikator</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formDisposisi">
                <input type="hidden" name="id_pengajuan" id="disp_id_pengajuan">
                <div class="modal-body">
                    <p id="label_pilih">Pilih Verifikator untuk pengusul:</p>
                    <h5 id="disp_nama_pengusul" class="font-weight-bold text-primary mb-3"></h5>
                    <div class="form-group">
                        <label>Nama Verifikator:</label>
                        <select name="id_verifikator" id="disp_select_verif" class="form-control">
                            <option value="">-- Biarkan Kosong (Belum Disposisi) --</option>
                            <?php foreach($list_verifikator as $v): ?>
                                <option value="<?= $v['id']; ?>"><?= htmlspecialchars($v['nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success font-weight-bold" id="btnSimpanDisp">Kirim Tugas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGelombang" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-layer-group mr-2"></i> Set Gelombang Masal</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formGelombangMasal">
                <input type="hidden" name="id_pengajuan_masal" id="gel_id_pengajuan">
                <div class="modal-body">
                    <p>Tentukan gelombang untuk <span id="count_gel_text" class="font-weight-bold text-danger">0</span> pengusul:</p>
                    <div class="form-group">
                        <label>Pilih Gelombang:</label>
                        <select name="gelombang_pilihan" class="form-control" required>
                            <option value="">-- Pilih Gelombang --</option>
                            <?php foreach($list_gelombang as $g): 
                                $bulk_label = htmlspecialchars($g['gelombang']) . (!empty($g['bln_gelombang']) ? " - " . htmlspecialchars($g['bln_gelombang']) : "");
                            ?>
                                <option value="<?= $g['id']; ?>"><?= $bulk_label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info font-weight-bold" id="btnSimpanGel">Update Gelombang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'template/footer.php'; ?>

<script>
$(document).ready(function() {
    var table = $('#tableKasubdit').DataTable({ 
        "responsive": true, 
        "autoWidth": false,
        "language": { "search": "Cari Cepat:" }
    });

    // Filtering
    $('#filterJenis').on('change', function() { table.column(3).search(this.value).draw(); });
    $('#filterGelombang').on('change', function() { table.column(4).search(this.value).draw(); });
    $('#filterStatus').on('change', function() { table.column(6).search(this.value).draw(); });
    $('#filterVerifikator').on('change', function() { table.column(5).search(this.value).draw(); }); 
    
    window.resetFilter = function() {
        $('#filterJenis, #filterGelombang, #filterStatus, #filterVerifikator').val('');
        table.column(3).search('').column(4).search('').column(5).search('').column(6).search('').draw();
    };

    // Bulk Checkbox
    $('#checkAll').on('click', function() { $('.check-child').prop('checked', this.checked); toggleBulkButton(); });
    $('#tableKasubdit').on('change', '.check-child', function() { toggleBulkButton(); });
    function toggleBulkButton() {
        var count = $('.check-child:checked').length;
        if (count > 0) { $('#bulkActionArea').show(); $('.countCheck').text(count); } 
        else { $('#bulkActionArea').hide(); }
    }

    // LOGIKA WIZARD KIRIM KE DIREKTUR
    $('#btnNextStep').on('click', function() {
        const gelId = $('#wizard_gelombang').val();
        const gelNama = $('#wizard_gelombang option:selected').data('nama');
        
        if(!gelId) { Swal.fire('Perhatian', 'Silakan pilih gelombang terlebih dahulu', 'warning'); return; }

        $('#text_gel_selected').text(gelNama);
        $('#wizard_data_body').html('<tr><td colspan="3" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Sedang memuat data...</td></tr>');
        $('#step1').removeClass('active');
        $('#step2').addClass('active');

        $.ajax({
            url: 'get_pengusul.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success' && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(row => {
                        html += `<tr>
                            <td class="text-center"><input type="checkbox" class="check-wizard" value="${row.id}"></td>
                            <td><div class="font-weight-bold text-dark">${row.nama}</div><small class="text-muted">${row.nip}</small></td>
                            <td class="text-center"><span class="badge badge-success px-3 py-2 shadow-sm">${row.status_pengajuan}</span></td>
                        </tr>`;
                    });
                    $('#wizard_data_body').html(html);
                    $('#container_list_direktur').show();
                    $('#wizard_empty').addClass('d-none');
                } else {
                    $('#container_list_direktur').hide();
                    $('#wizard_empty').removeClass('d-none');
                }
            },
            error: function(xhr) {
                console.error("Error Fetch:", xhr.responseText);
                $('#wizard_data_body').html('<tr><td colspan="3" class="text-center text-danger">Gagal mengambil data.</td></tr>');
            }
        });
    });

    $('#btnPrevStep').on('click', function() {
        $('#step2').removeClass('active');
        $('#step1').addClass('active');
    });

    $('#checkAllWizard').on('click', function() {
        $('.check-wizard').prop('checked', this.checked);
    });

    $('#wizard_data_body').on('click', 'tr', function(e) {
        if (e.target.type !== 'checkbox') {
            const cb = $(this).find('input.check-wizard');
            cb.prop('checked', !cb.prop('checked'));
        }
    });

    $('#btnFinalKirimDirektur').on('click', function() {
        const selectedIds = [];
        $('.check-wizard:checked').each(function() { selectedIds.push($(this).val()); });
        const gelId = $('#wizard_gelombang').val();
        const gelNama = $('#wizard_gelombang option:selected').data('nama');

        if(selectedIds.length === 0) { Swal.fire('Peringatan', 'Pilih minimal satu pengusul.', 'warning'); return; }

        Swal.fire({
            title: 'Konfirmasi',
            text: 'Kirim ' + selectedIds.length + ' data ke Direktur untuk ' + gelNama + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6610f2',
            confirmButtonText: 'Ya, Kirim!'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $(this);
                const originalText = btn.html();
                
                btn.html('<i class="fas fa-spinner fa-spin"></i> Memproses...').attr('disabled', true);
                
                $.ajax({
                    url: 'proses_ke_direktur.php',
                    type: 'POST',
                    data: { 
                        id_pengajuan: selectedIds.join(','), 
                        status_baru: 'Proses Direktur',
                        gelombang: gelId 
                    },
                    dataType: 'json',
                    success: function(res) {
                        if(res.status === 'success') { 
                            Swal.fire('Berhasil', res.message, 'success')
                            .then(() => { location.reload(); });
                        } else { 
                            Swal.fire('Gagal', res.message, 'error');
                            btn.html(originalText).attr('disabled', false); 
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error Sistem', 'Terjadi kesalahan pada server.', 'error');
                        btn.html(originalText).attr('disabled', false);
                    }
                });
            }
        });
    });

    // LOGIKA DISPOSISI & GELOMBANG MASAL
    $('.btn-buka-disposisi').on('click', function() {
        $('#disp_id_pengajuan').val($(this).data('id'));
        $('#disp_nama_pengusul').text($(this).data('nama'));
        $('#disp_select_verif').val($(this).data('verif'));
    });

    $('#formDisposisi').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSimpanDisp');
        btn.attr('disabled', true).text('Loading...');
        $.ajax({
            url: 'proses_disposisi.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(res) { 
                if(res.status === 'success') { location.reload(); } 
                else { Swal.fire('Gagal', res.message, 'error'); btn.attr('disabled', false).text('Kirim Tugas'); } 
            },
            error: function() { Swal.fire('Error', 'Gagal memproses disposisi.', 'error'); btn.attr('disabled', false).text('Kirim Tugas'); }
        });
    });

    $('#btnBulkDisposisi').on('click', function() {
        var ids = []; $('.check-child:checked').each(function() { ids.push($(this).val()); });
        $('#disp_id_pengajuan').val(ids.join(','));
        $('#disp_nama_pengusul').text(ids.length + ' Pengusul Terpilih');
        $('#modalDisposisi').modal('show');
    });

    $('#btnBulkGelombang').on('click', function() {
        var ids = []; $('.check-child:checked').each(function() { ids.push($(this).val()); });
        $('#gel_id_pengajuan').val(ids.join(','));
        $('#count_gel_text').text(ids.length);
        $('#modalGelombang').modal('show');
    });

    $('#formGelombangMasal').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSimpanGel');
        btn.attr('disabled', true).text('Updating...');
        $.ajax({
            url: 'proses_gelombang_masal.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(res) { 
                if(res.status === 'success') { location.reload(); } 
                else { Swal.fire('Gagal', res.message, 'error'); btn.attr('disabled', false).text('Update Gelombang'); } 
            },
            error: function() { Swal.fire('Error', 'Gagal update gelombang.', 'error'); btn.attr('disabled', false).text('Update Gelombang'); }
        });
    });
});
</script>