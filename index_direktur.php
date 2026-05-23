<?php
/**
 * ==================================================================================
 * FILE: index_direktur.php
 * DESKRIPSI: Dashboard Direktur - Tampilan Rekap Sesuai Referensi Gambar
 * UPDATE: Menampilkan seluruh data tanpa memandang status + Wajib Upload Surat Pengantar
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

// --- LOGIKA UPDATE NOTIFIKASI ---
if (isset($_GET['update_read_d'])) {
    $ids_to_update = $_GET['update_read_d'];
    $ids_to_update = preg_replace('/[^0-9,]/', '', $ids_to_update);
    if (!empty($ids_to_update)) {
        $conn->query("UPDATE pengajuan_ujikom SET is_read_direktur = 1, tgl_update = NOW() WHERE id IN ($ids_to_update)");
    }
    header("Location: index_direktur.php");
    exit;
}

$page_title = "Executive Dashboard - Gelombang";

// --- DATA USER ---
$session_user_id = $_SESSION['user_id_sesi'] ?? 0;
$user_nama = "Direktur"; 
$sql_user = "SELECT nama FROM users WHERE id = ?"; 
if ($stmt_user = $conn->prepare($sql_user)) {
    $stmt_user->bind_param("i", $session_user_id);
    $stmt_user->execute();
    $res_user = $stmt_user->get_result();
    if ($user_data = $res_user->fetch_assoc()) {
        $user_nama = $user_data['nama'];
    }
}

// --- CONFIG STATUS (Untuk keperluan perhitungan Box Atas) ---
$status_allowed = "'Proses Direktur', 'Disetujui Direktur', 'Proses PPSDM', 'Menunggu Jadwal Ujikom', 'Terjadwal', 'Selesai', 'Cadangan'";

/**
 * 4. QUERY UTAMA: MENGELOMPOKKAN BERDASARKAN GELOMBANG
 * PERBAIKAN: Menghapus filter status_pengajuan agar SEMUA data muncul
 */
$sql = "SELECT 
            p.gelombang as id_gel_asli, 
            g.gelombang as nama_gel_text,
            g.surat_gelombang as surat_gelombang,
            COUNT(*) as total_peserta,
            SUM(CASE WHEN p.status_pengajuan = 'Proses Direktur' THEN 1 ELSE 0 END) as jml_proses_direktur,
            SUM(CASE WHEN p.status_pengajuan = 'Cadangan' THEN 1 ELSE 0 END) as jml_cadangan,
            SUM(CASE WHEN p.status_pengajuan NOT IN ('Proses Direktur', 'Cadangan') THEN 1 ELSE 0 END) as jml_terkirim_ppsdm,
            MAX(p.tgl_update) as tgl_terbaru 
        FROM pengajuan_ujikom p
        LEFT JOIN tb_gelombang g ON p.gelombang = g.id
        WHERE p.gelombang IS NOT NULL AND p.gelombang != ''
        GROUP BY p.gelombang 
        ORDER BY tgl_terbaru DESC";

$result = $conn->query($sql);
$data_gelombang = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];

// 5. REKAPITULASI BOX ATAS
$total_seluruh_data = $conn->query("SELECT COUNT(*) as total FROM pengajuan_ujikom")->fetch_assoc()['total'] ?? 0;
$total_disetujui = $conn->query("SELECT COUNT(*) as total FROM pengajuan_ujikom WHERE status_pengajuan IN ('Disetujui Direktur', 'Proses PPSDM', 'Menunggu Jadwal Ujikom', 'Terjadwal', 'Selesai')")->fetch_assoc()['total'] ?? 0;
$total_pending = $conn->query("SELECT COUNT(*) as total FROM pengajuan_ujikom WHERE status_pengajuan IN ('Proses Direktur', 'Cadangan')")->fetch_assoc()['total'] ?? 0;

require_once 'template/header.php'; 
require_once 'template/navbar.php'; 
require_once 'template/sidebar.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .content-wrapper { background-color: #f0f2f5; }
    .header-custom {
        background: #1a3a3a; color: white; padding: 25px 30px; border-radius: 15px;
        margin: 20px; display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .header-info h2 { font-weight: 700; margin-bottom: 5px; font-size: 1.8rem; }
    .header-info p { opacity: 0.8; margin-bottom: 0; font-size: 0.95rem; }
    .rekap-wrapper { display: flex; gap: 15px; }
    .card-stat {
        background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px; min-width: 110px; padding: 10px; text-align: center;
    }
    .stat-total { border-bottom: 4px solid #f0f0f0; }
    .stat-verif1 { border-bottom: 4px solid #3b82f6; } 
    .stat-pending { border-bottom: 4px solid #ef4444; } 
    .stat-value { font-size: 1.6rem; font-weight: 800; display: block; line-height: 1.2; }
    .stat-label { font-size: 0.7rem; text-transform: uppercase; font-weight: 600; opacity: 0.9; }
    .main-card { border: none; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; margin: 0 20px; }
    .btn-detail { background: #3b82f6; color: white; border-radius: 10px; font-weight: bold; padding: 8px 15px; border: none; }
    .btn-detail:hover { background: #2563eb; color: white; }
    .btn-ppsdm { background: #10b981; color: white; border-radius: 10px; font-weight: bold; padding: 8px 15px; border: none; margin-left: 5px; }
    .btn-ppsdm:hover { background: #059669; color: white; }
    .btn-surat { background: #f59e0b; color: white; border-radius: 10px; font-weight: bold; padding: 8px 12px; font-size: 0.85rem; }
    
    /* Style untuk form input file di dalam SweetAlert */
    .swal2-file-input {
        border: 2px dashed #cbd5e1;
        padding: 10px;
        width: 100%;
        border-radius: 8px;
        margin-top: 15px;
        background: #f8fafc;
        cursor: pointer;
    }
</style>

<div class="content-wrapper">
    <div class="header-custom">
        <div class="header-info">
            <h2>Executive Dashboard</h2>
            <p>Selamat Datang, <?= htmlspecialchars($user_nama); ?></p>
        </div>
        <div class="rekap-wrapper">
            <div class="card-stat stat-total"><span class="stat-value"><?= $total_seluruh_data; ?></span><span class="stat-label">Total Usulan</span></div>
            <div class="card-stat stat-verif1"><span class="stat-value"><?= $total_disetujui; ?></span><span class="stat-label">Terkirim</span></div>
            <div class="card-stat stat-pending"><span class="stat-value"><?= $total_pending; ?></span><span class="stat-label">Belum Kirim / Cadangan</span></div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card main-card">
                <div class="card-header bg-white border-0 py-4">
                    <h4 class="font-weight-bold mb-0 text-dark">Daftar Gelombang Pengajuan</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="tableDirektur" class="table table-hover mb-0">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th width="60" class="text-center">NO</th>
                                    <th>NAMA GELOMBANG</th>
                                    <th class="text-center">SURAT PENGUMUMAN</th>
                                    <th class="text-center">TOTAL PESERTA</th>
                                    <th class="text-center">STATUS VALIDASI</th>
                                    <th class="text-center">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($data_gelombang as $row): 
                                    $is_ada_proses = ($row['jml_proses_direktur'] > 0);
                                    $has_cadangan = ($row['jml_cadangan'] > 0);
                                    $display_name = !empty($row['nama_gel_text']) ? $row['nama_gel_text'] : "Gelombang " . $row['id_gel_asli'];
                                ?>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold"><?= $no++; ?></td>
                                    <td class="align-middle">
                                        <span class="text-dark font-weight-bold" style="font-size: 1.1rem;">
                                            <i class="fas fa-layer-group text-primary mr-2"></i><?= htmlspecialchars($display_name); ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if(!empty($row['surat_gelombang'])): ?>
                                            <a href="uploads/pengumuman/<?= $row['surat_gelombang']; ?>" target="_blank" class="btn-surat shadow-sm">
                                                <i class="fas fa-file-pdf mr-1"></i> Buka Surat
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small"><i>Belum Upload</i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-pill badge-info px-3 py-2">
                                            <?= $row['total_peserta']; ?> Pengusul
                                            <?php if($has_cadangan): ?>
                                                <small class="d-block">(Termasuk <?= $row['jml_cadangan']; ?> Cadangan)</small>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if($is_ada_proses): ?>
                                            <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i> Proses Direktur</span>
                                        <?php elseif($row['jml_terkirim_ppsdm'] > 0): ?>
                                            <span class="badge badge-primary" style="background-color: #059669; color: white;"><i class="fas fa-paper-plane mr-1"></i> Proses PPSDM</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><i class="fas fa-archive mr-1"></i> Cadangan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <a href="detail_gelombang_direktur.php?gel=<?= urlencode($row['id_gel_asli']); ?>" class="btn-detail shadow-sm px-3">
                                                <i class="fas fa-search-plus mr-1"></i> LIHAT
                                            </a>
                                            <?php if($is_ada_proses): ?>
                                            <button type="button" class="btn-ppsdm shadow-sm btnKirimPPSDM" data-id="<?= $row['id_gel_asli']; ?>" data-nama="<?= htmlspecialchars($display_name); ?>">
                                                <i class="fas fa-paper-plane mr-1"></i> PPSDM
                                            </button>
                                            <?php else: ?>
                                            <span class="badge badge-light border border-success text-success ml-2 py-2 px-3 shadow-sm" style="font-size: 0.85rem;">
                                                <i class="fas fa-check-double mr-1"></i> Terkirim
                                            </span>
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

<?php require_once 'template/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#tableDirektur').DataTable({ "responsive": true, "order": [] });

    $('.btnKirimPPSDM').on('click', function() {
        const idGel = $(this).data('id');
        const namaGel = $(this).data('nama');

        // Menampilkan Form Upload melalui SweetAlert2
        Swal.fire({
            title: 'Kirim ke PPSDM?',
            html: 
                `<p class="mb-2">Kirim data <strong>${namaGel}</strong> ke PPSDM?</p>
                 <div style="text-align: left; margin-top: 15px;">
                    <label class="font-weight-bold mb-0 text-sm">Lampirkan Surat Pengantar (Wajib, format PDF) <span class="text-danger">*</span></label>
                    <input type="file" id="surat_pengantar" class="swal2-file-input" accept=".pdf" required>
                 </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Ya, Kirim dengan Dokumen!',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                // Validasi Dokumen sebelum mengirim
                const fileInput = document.getElementById('surat_pengantar');
                if (!fileInput.files.length) {
                    Swal.showValidationMessage('Anda wajib melampirkan Surat Pengantar!');
                    return false;
                }
                const file = fileInput.files[0];
                if (file.type !== 'application/pdf') {
                    Swal.showValidationMessage('File harus berformat PDF!');
                    return false;
                }
                return file;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mempersiapkan FormData untuk mengirim data + file sekaligus
                let formData = new FormData();
                formData.append('gelombang', idGel);
                formData.append('surat_pengantar', result.value); // Menyisipkan objek file dari SweetAlert

                // Proses loading state
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Sedang mengunggah dokumen dan mengirim data.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Request AJAX dengan FormData
                $.ajax({
                    url: 'proses_kolektif_ppsdm.php',
                    type: 'POST',
                    data: formData, 
                    processData: false, // Wajib false untuk FormData
                    contentType: false, // Wajib false untuk FormData
                    dataType: 'json',
                    success: function(res) {
                        if(res.status === 'success') {
                            Swal.fire('Berhasil!', res.message, 'success').then(() => { location.reload(); });
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Gagal', 'Terjadi kesalahan sistem saat menghubungi server.', 'error');
                    }
                });
            }
        });
    });
});
</script>