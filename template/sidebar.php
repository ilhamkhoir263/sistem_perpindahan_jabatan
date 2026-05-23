<?php
/**
 * FILE: sidebar.php
 * DESKRIPSI: Sidebar Navigasi dengan Logika Role-Based Access Control
 * FITUR: Navigasi Dashboard + Smart Highlighting + Auto-Hide Menu Kontekstual + Log Update (Admin Only)
 */

// =========================================================
// 1. PENGATURAN ROLE & SESI
// =========================================================
$user_role = $_SESSION['user_role_sesi'] ?? 'Guest'; 

// Cek Role spesifik
$is_pengusul = ($user_role === 'user_pengusul'); 
$is_verifikator_only = ($user_role === 'user_verifikator');
$is_kasubdit = ($user_role === 'user_kasubdit'); 
$is_direktur = ($user_role === 'user_direktur'); 
$is_super_admin = ($user_role === 'user_super_admin'); 
$is_admin = ($user_role === 'user_admin'); 

$is_admin_verifikator = (
    $user_role === 'user_admin' || 
    $user_role === 'user_verifikator' || 
    $user_role === 'user_super_admin'
);
$is_evaluator = ($user_role === 'user_evaluator'); 
$is_ppsdm = ($user_role === 'user_ppsdm');

/**
 * PENYESUAIAN HAK AKSES:
 * 1. Rekomendasi: Sembunyikan untuk Pengusul, Super Admin, DAN Verifikator Only.
 * 2. Pengajuan Ujikom: Sembunyikan untuk Super Admin DAN Verifikator Only.
 */
$can_access_rekomendasi = ($is_admin_verifikator && !$is_super_admin && !$is_verifikator_only); 
$can_access_pengajuan_ujikom = ($is_admin_verifikator && !$is_super_admin && !$is_verifikator_only);

// Logika Log Update: Hanya untuk Super Admin dan Admin
$can_access_log_update = ($is_super_admin || $is_admin);

// Logika tambahan: Sembunyikan seluruh menu Ujikom jika Verifikator Only
$show_ujikom_menu = !$is_verifikator_only;

// =========================================================
// 2. LOGIKA PARAMETER URL & AUTO-HIDE MENU
// =========================================================
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil status hide dari URL agar tetap bertahan saat navigasi antar menu
$hide_perpindahan = isset($_GET['hide_perpindahan']) && $_GET['hide_perpindahan'] == '1';
$hide_kenaikan = isset($_GET['hide_kenaikan']) && $_GET['hide_kenaikan'] == '1';

/** * LOGIKA PENYEMBUNYIAN OTOMATIS BERDASARKAN HALAMAN:
 * 1. Hide Kenaikan jika di form perpindahan, detail isian, atau edit isian.
 * 2. Hide Perpindahan jika di form kenaikan.
 */
$pages_to_hide_kenaikan = [
    'form_perpindahan_jabatan.php', 
    'detail_isian.php', 
    'edit_isian.php'
];

if (in_array($current_page, $pages_to_hide_kenaikan)) {
    $hide_kenaikan = false;
} elseif ($current_page == 'form_kenaikan_jabatan.php') {
    $hide_perpindahan = false;
}

// Variabel bantu untuk mempertahankan parameter di setiap klik menu (URL Append)
$append_url = '';
if ($hide_perpindahan) {
    $append_url = '?hide_perpindahan=1';
} elseif ($hide_kenaikan) {
    $append_url = '?hide_kenaikan=1';
}

// =========================================================
// 3. LOGIKA AKTIVITAS MENU (HIGHLIGHTING)
// =========================================================
$page = $page ?? ''; 
$sub_page = $sub_page ?? ''; 

$is_dashboard_active = (
    $current_page == 'index_asli.php' || 
    $current_page == 'index_verifikator.php' || 
    $current_page == 'index_pengusul.php' ||
    $current_page == 'index_evaluator.php' ||
    $current_page == 'index_kasubdit.php' ||
    $current_page == 'index_direktur.php' ||
    $current_page == 'index_ppsdm.php' ||
    $page == 'dashboard'
);

// Logika Menu Rekomendasi Formasi
$rekomendasi_pages = ['rekomendasi', 'input_rekom', 'evaluasi', 'list_rekom', 'list_rekom_pengusul'];
$is_rekomendasi_active = in_array($page, $rekomendasi_pages) || in_array($sub_page, $rekomendasi_pages);
$rekomendasi_menu_open = $is_rekomendasi_active ? 'menu-open' : '';

// Logika Menu Uji Kompetensi
$is_ujikom_active = (
    $page == 'ujikom' || 
    in_array($sub_page, ['perpindahan_jabatan', 'form_kenaikan', 'list_perpindahan', 'list_kenaikan', 'list_perpindahan_pengusul']) ||
    in_array($current_page, ['form_perpindahan_jabatan.php', 'form_kenaikan_jabatan.php', 'list_perpindahan_pengusul.php', 'detail_isian.php', 'edit_isian.php'])
) && !$is_dashboard_active;

$ujikom_menu_open = $is_ujikom_active ? 'menu-open' : '';

// Sub-level Data Ujikom
$is_data_ujikom_active = in_array($sub_page, ['list_perpindahan', 'list_kenaikan']) && !$is_dashboard_active;
$data_ujikom_menu_open = $is_data_ujikom_active ? 'menu-open' : '';

// ---------------------------------------------------------
// PENGATURAN TEKS & URL DINAMIS
// ---------------------------------------------------------
$list_rekom_text = $is_pengusul ? 'Rekomendasi Saya' : 'List Rekomendasi';
$list_rekom_url = $is_pengusul ? 'list_rekom_pengusul.php' : 'list_rekom.php';
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="index_asli.php" class="brand-link" style="pointer-events: none; cursor: default;">
        <img src="assets/logo_instansi.jpeg" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Instansi Pembina JF</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-item">
                    <a href="<?php 
                        if ($is_pengusul) echo 'index_pengusul.php' . $append_url;
                        elseif ($is_verifikator_only) echo 'index_verifikator.php';
                        elseif ($is_evaluator) echo 'index_evaluator.php';
                        elseif ($is_kasubdit) echo 'index_kasubdit.php';
                        elseif ($is_direktur) echo 'index_direktur.php';
                        elseif ($is_ppsdm) echo 'index_ppsdm.php';
                        else echo 'index_asli.php' . $append_url;
                        ?>" 
                       class="nav-link <?php echo $is_dashboard_active ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <?php if ($is_admin_verifikator): ?>
                <li class="nav-item">
                    <a href="database.php" class="nav-link <?php echo ($current_page == 'database.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-database"></i>
                        <p>Database</p>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($can_access_rekomendasi): ?>
                <li class="nav-item <?php echo $rekomendasi_menu_open; ?>">
                    <a href="#" class="nav-link <?php echo $is_rekomendasi_active ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>
                            Rekomendasi Formasi
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="input_rekom.php" class="nav-link <?php echo ($current_page == 'input_rekom.php') ? 'active' : ''; ?>">
                                <i class="fas fa-plus-square nav-icon"></i>
                                <p>Input Rekomendasi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $list_rekom_url; ?>" class="nav-link <?php echo ($current_page == $list_rekom_url) ? 'active' : ''; ?>">
                                <i class="fas fa-tasks nav-icon"></i>
                                <p><?php echo $list_rekom_text; ?></p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if ($is_admin_verifikator): ?>
                <li class="nav-item">
                    <a href="peraturan.php" class="nav-link <?php echo ($current_page == 'peraturan.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Peraturan</p>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($show_ujikom_menu): ?>
                <li class="nav-item <?php echo $ujikom_menu_open; ?>">
                    <a href="#" class="nav-link <?php echo $is_ujikom_active ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>
                            Uji Kompetensi
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php if ($is_pengusul): ?>
                            
                            <?php if (!$hide_perpindahan): ?>
                            <li class="nav-item">
                                <a href="form_perpindahan_jabatan.php<?php echo $append_url; ?>" class="nav-link <?php echo ($current_page == 'form_perpindahan_jabatan.php') ? 'active' : ''; ?>">
                                    <i class="nav-icon fas fa-exchange-alt"></i>
                                    <p>Perpindahan Jabatan</p>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (!$hide_kenaikan): ?>
                            <li class="nav-item">
                                <a href="form_kenaikan_jabatan.php<?php echo $append_url; ?>" class="nav-link <?php echo ($current_page == 'form_kenaikan_jabatan.php') ? 'active' : ''; ?>">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>Kenaikan Jabatan</p>
                                </a>
                            </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <a href="list_perpindahan_pengusul.php<?php echo $append_url; ?>" class="nav-link <?php echo ($current_page == 'list_perpindahan_pengusul.php') ? 'active' : ''; ?>">
                                    <i class="fas fa-list-alt nav-icon"></i>
                                    <p>Daftar Pengajuan Saya</p>
                                </a>
                            </li>

                        <?php elseif ($is_admin_verifikator): ?>
                            
                            <?php if ($can_access_pengajuan_ujikom): ?>
                            <li class="nav-item <?php echo ($sub_page == 'perpindahan_jabatan' || $sub_page == 'form_kenaikan') ? 'menu-open' : ''; ?>">
                                <a href="#" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Pengajuan Ujikom <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="form_perpindahan_jabatan.php" class="nav-link <?php echo ($sub_page == 'perpindahan_jabatan') ? 'active' : ''; ?>">
                                            <i class="fas fa-file-export nav-icon"></i><p>Perpindahan Jabatan</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="form_kenaikan_jabatan.php" class="nav-link <?php echo ($sub_page == 'form_kenaikan') ? 'active' : ''; ?>">
                                            <i class="fas fa-file-upload nav-icon"></i><p>Kenaikan Jabatan</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <?php endif; ?>

                            <li class="nav-item <?php echo $data_ujikom_menu_open; ?>">
                                <a href="#" class="nav-link <?php echo $is_data_ujikom_active ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Data Ujikom <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="list_perpindahan.php" class="nav-link <?php echo ($sub_page == 'list_perpindahan') ? 'active' : ''; ?>">
                                            <i class="fas fa-list-alt nav-icon"></i><p>List Perpindahan</p>
                                        </a>
                                    </li>
                                    <li class="nav-item"> 
                                        <a href="list_kenaikan.php" class="nav-link <?php echo ($sub_page == 'list_kenaikan') ? 'active' : ''; ?>">
                                            <i class="fas fa-tasks nav-icon"></i><p>List Kenaikan</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a href="pengaturan.php<?php echo $append_url; ?>" class="nav-link <?php echo ($current_page == 'pengaturan.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Pengaturan</p>
                    </a>
                </li>

                <?php if ($can_access_log_update): ?>
                <li class="nav-item">
                    <a href="catatan_update.php<?php echo $append_url; ?>" class="nav-link <?php echo ($current_page == 'catatan_update.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Log Update</p>
                    </a>
                </li>
                 <li class="nav-item">
                    <a href="admin_update.php<?php echo $append_url; ?>" class="nav-link <?php echo ($current_page == 'admin_update.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Admin Update</p>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt text-danger"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>