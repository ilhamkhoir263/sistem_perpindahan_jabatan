    <?php
    // database.php - Halaman Database Pegawai JF PKP dengan Filter Dinamis

    // --- AUTH GUARD DITAMBAHKAN DI SINI ---
    // File ini akan memulai sesi (jika belum) dan memastikan pengguna telah login.
    require_once 'auth_guard.php'; 

    // Memuat koneksi database.
    require_once 'koneksi.php'; 

    // --- PENGGUNAAN DATA SESSION DARI auth_guard.php ---
    // Menggunakan variabel global yang disiapkan oleh auth_guard.php
    $user_data = [
        'nama' => $user_nama_sesi ?? 'Pengguna JF', 
        'role' => $user_role_sesi ?? 'User', 
        'email' => $user_email_sesi ?? 'user@instansi.go.id', 
        'join_date' => $_SESSION['join_date'] ?? 'Maret 2023' // join_date mungkin harus disiapkan di login.php
    ];
    // CATATAN: Dummy data di atas (Henry Klein, Admin, dll.) telah dihapus.

    // Tentukan nama tabel pegawai yang digunakan
    $NAMA_TABEL_PEGAWAI = "detailpegawai"; 

    // --- PENGATURAN JUDUL HALAMAN ---
    $page = 'database'; // Digunakan untuk mengaktifkan menu di sidebar
    $page_title = 'Database Pejabat Fungsional'; // Digunakan untuk judul halaman

    // --- Dapatkan Input Filter ---
    $jenis_instansi = isset($_GET['jenisInstansi']) ? trim($_GET['jenisInstansi']) : '';
    $nama_instansi = isset($_GET['namaInstansi']) ? trim($_GET['namaInstansi']) : '';
    $provinsi = isset($_GET['provinsi']) ? trim($_GET['provinsi']) : '';
    $kabupaten = isset($_GET['kabupaten']) ? trim($_GET['kabupaten']) : '';
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $where_clauses = [];

    // Daftar Instansi Pusat yang mungkin ada di kolom 'instansi' (Harap disesuaikan!)
    $instansi_pusat = ["Kementerian PKP", "Kementerian PU", "Kementerian PUPR"]; 

    // --- 1. Logika Filter Instansi (Pusat/Daerah, Nama Instansi, Lokasi) ---
    if ($jenis_instansi === 'pusat') {
        if (!empty($nama_instansi)) {
            // Filter spesifik instansi pusat
            $where_clauses[] = "instansi = '" . mysqli_real_escape_string($conn, $nama_instansi) . "'";
        } else {
            // Filter semua instansi pusat
            $pusat_list = array_map(function($i) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $i) . "'";
            }, $instansi_pusat);
            $where_clauses[] = "instansi IN (" . implode(",", $pusat_list) . ")";
        }
    } elseif ($jenis_instansi === 'daerah') {
        // Filter non-pusat (Daerah)
        $pusat_list = array_map(function($i) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $i) . "'";
        }, $instansi_pusat);
        $where_clauses[] = "instansi NOT IN (" . implode(",", $pusat_list) . ")";

        // Filter Provinsi/Kabupaten (hanya berlaku untuk Daerah)
        if (!empty($kabupaten)) {
            // Paling spesifik: cari nama Kabupaten/Kota di kolom instansi
            $where_clauses[] = "instansi LIKE '%" . mysqli_real_escape_string($conn, $kabupaten) . "%'";
        } elseif (!empty($provinsi)) {
            // Filter berdasarkan Provinsi (Asumsi: Nama instansi mengandung nama Provinsi)
            $where_clauses[] = "instansi LIKE '%" . mysqli_real_escape_string($conn, $provinsi) . "%'";
        }
    }

    // --- 2. Logika Filter Pencarian (Search Box) ---
    if (!empty($search_query)) {
        // Memastikan koneksi tersedia sebelum menggunakan mysqli_real_escape_string
        $safe_search = isset($conn) ? mysqli_real_escape_string($conn, $search_query) : $search_query;
        // Kolom yang dicari: nip, nama_lengkap_gelar, instansi, unit_kerja
        $where_clauses[] = " (nip LIKE '%{$safe_search}%' OR nama_lengkap_gelar LIKE '%{$safe_search}%' OR instansi LIKE '%{$safe_search}%' OR unit_kerja LIKE '%{$safe_search}%') ";
    }

    // Gabungkan semua klausa WHERE
    $where_clause = '';
    if (!empty($where_clauses)) {
        $where_clause = ' WHERE ' . implode(' AND ', $where_clauses);
    }

    // --- 3. Query Database ---
    $sql_select = "SELECT id, nip, nama_lengkap_gelar, instansi, unit_kerja FROM {$NAMA_TABEL_PEGAWAI} {$where_clause} ORDER BY id DESC";

    // Menghindari error jika $conn belum terdefinsi dari koneksi.php
    $res_data_pegawai = isset($conn) ? @mysqli_query($conn, $sql_select) : false;
    $data_pegawai_gagal = !$res_data_pegawai;
    $total_data = 0;
    $data_pegawai = [];

    if (!$data_pegawai_gagal && $res_data_pegawai) {
        $total_data = mysqli_num_rows($res_data_pegawai);
        while($row = mysqli_fetch_assoc($res_data_pegawai)) {
            $data_pegawai[] = $row;
        }
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

        <style>
            .hold-transition.sidebar-mini { margin: 0; }
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
                            <h1 class="m-0"><i class="fas fa-database"></i> <?php echo $page_title; ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right" style="background-color: transparent;">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Database</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-8 col-sm-12">
                            <p class="text-muted mb-0">
                                Data Pejabat Pemerintah dan Pemerintah Daerah | Total Data: 
                                <span class="badge bg-primary"><?php echo $total_data; ?></span>
                            </p>
                        </div>
                        <div class="col-md-4 col-sm-12 text-right">
                            <a href="tambah_pegawai.php" class="btn btn-primary btn-sm my-1"><i class="fas fa-plus"></i> Tambah Pegawai</a>
                            <button class="btn btn-success btn-sm my-1"><i class="fas fa-download"></i> Export CSV</button>
                        </div>
                    </div>

                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter Data Pegawai</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="database.php" class="form-row">
                                
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                    <select id="jenisInstansi" name="jenisInstansi" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="" <?php if ($jenis_instansi == '') echo 'selected'; ?>>Pilih Jenis Instansi</option>
                                        <option value="pusat" <?php if ($jenis_instansi == 'pusat') echo 'selected'; ?>>Pusat</option>
                                        <option value="daerah" <?php if ($jenis_instansi == 'daerah') echo 'selected'; ?>>Pemerintah Daerah</option>
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                    <select id="namaInstansi" name="namaInstansi" class="form-control form-control-sm" <?php if ($jenis_instansi !== 'pusat') echo 'disabled'; ?>>
                                        <option value="">Pilih Nama Instansi</option>
                                        <?php 
                                        $list_pusat = ["Kementerian PKP", "Kementerian PU", "Kementerian PUPR", "Instansi Pusat Lain"];
                                        foreach ($list_pusat as $inst) {
                                            echo "<option value='".htmlspecialchars($inst)."' ".($nama_instansi == $inst ? 'selected' : '').">".htmlspecialchars($inst)."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                    <select id="provinsi" name="provinsi" class="form-control form-control-sm" <?php if ($jenis_instansi !== 'daerah') echo 'disabled'; ?>>
                                        <option value="">Pilih Provinsi</option>
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-4 col-sm-6 mb-2">
                                    <select id="kabupaten" name="kabupaten" class="form-control form-control-sm" <?php if (empty($provinsi) || $jenis_instansi !== 'daerah') echo 'disabled'; ?>>
                                        <option value="">Pilih Kabupaten/Kota</option>
                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-8 col-sm-12 mb-2 d-flex">
                                    <input type="text" id="search" name="search" class="form-control form-control-sm mr-1" placeholder="Cari NIP/Nama/Instansi..." value="<?php echo htmlspecialchars($search_query); ?>" />
                                    
                                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search"></i> Cari</button>
                                    
                                    <?php if (!empty($search_instansi) || !empty($provinsi) || !empty($kabupaten) || !empty($search_query) || !empty($jenis_instansi)): ?>
                                        <a href="database.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Reset</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header border-0">
                            <h3 class="card-title"><i class="fas fa-table mr-1"></i> Data Pegawai Fungsional</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-striped table-valign-middle" aria-label="Tabel Data Pejabat Fungsional">
                                <thead>
                                    <tr>
                                        <th style="width:5%">No</th>
                                        <th style="width:20%">Nama</th>
                                        <th style="width:15%">NIP</th>
                                        <th style="width:25%">Instansi</th>
                                        <th style="width:25%">Unit Kerja</th>
                                        <th style="width:10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <?php if ($data_pegawai_gagal): ?>
                                        <tr><td colspan='6' style='text-align:center; color:#dc3545; font-weight:bold;'>❌ Gagal memuat database pegawai. Error: <?php echo isset($conn) ? mysqli_error($conn) : 'Koneksi database tidak tersedia.'; ?></td></tr>
                                    <?php elseif (empty($data_pegawai)): ?>
                                        <tr><td colspan='6' style='text-align:center; color:#6c757d;'>Tidak ada data pegawai yang ditemukan.</td></tr>
                                    <?php else: 
                                        // Paging logic should be implemented here for large datasets, but keeping the original logic for now
                                        $no = 1;
                                        foreach($data_pegawai as $row):
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_lengkap_gelar'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['nip'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['instansi'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['unit_kerja'] ?? '-'); ?></td>
                                            <td><a href="detail_pegawai.php?id=<?php echo $row['id']; ?>" class="text-primary"><i class="fas fa-eye"></i> detail</a></td>
                                        </tr>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
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
        // --- Data Provinsi/Kabupaten (TETAP SAMA) ---
        const provinsiData = {
            "Aceh": ["Kabupaten Aceh Barat", "Kabupaten Aceh Barat Daya", "Kabupaten Aceh Besar", "Kabupaten Aceh Jaya", "Kabupaten Aceh Selatan", "Kabupaten Aceh Singkil", "Kabupaten Aceh Tamiang", "Kabupaten Aceh Tengah", "Kabupaten Aceh Tenggara", "Kabupaten Aceh Timur", "Kabupaten Aceh Utara", "Kabupaten Bener Meriah", "Kabupaten Bireuen", "Kabupaten Gayo Lues", "Kabupaten Nagan Raya", "Kabupaten Pidie", "Kabupaten Pidie Jaya", "Kabupaten Simeulue", "Kota Banda Aceh", "Kota Langsa", "Kota Lhokseumawe", "Kota Sabang", "Kota Subulussalam"],
            "Sumatera Utara": ["Kabupaten Asahan", "Kabupaten Batubara", "Kabupaten Dairi", "Kabupaten Deli Serdang", "Kabupaten Humbang Hasundutan", "Kabupaten Karo", "Kabupaten Labuhanbatu", "Kabupaten Labuhanbatu Selatan", "Kabupaten Labuhanbatu Utara", "Kabupaten Langkat", "Kabupaten Mandailing Natal", "Kabupaten Nias", "Kabupaten Nias Barat", "Kabupaten Nias Selatan", "Kabupaten Nias Utara", "Kabupaten Padang Lawas", "Kabupaten Padang Lawas Utara", "Kabupaten Pakpak Bharat", "Kabupaten Samosir", "Kabupaten Serdang Bedagai", "Kabupaten Simalungun", "Kabupaten Tapanuli Selatan", "Kabupaten Tapanuli Tengah", "Kabupaten Tapanuli Utara", "Kabupaten Toba", "Kota Binjai", "Kota Gunungsitoli", "Kota Medan", "Kota Padangsidimpuan", "Kota Pematangsiantar", "Kota Sibolga", "Kota Tanjungbalai", "Kota Tebing Tinggi"],
            "Sumatera Barat": ["Kabupaten Agam", "Kabupaten Dharmasraya", "Kabupaten Kepulauan Mentawai", "Kabupaten Lima Puluh Kota", "Kabupaten Padang Pariaman", "Kabupaten Pasaman", "Kabupaten Pasaman Barat", "Kabupaten Pesisir Selatan", "Kabupaten Sijunjung", "Kabupaten Solok", "Kabupaten Solok Selatan", "Kabupaten Tanah Datar", "Kota Bukittinggi", "Kota Padang", "Kota Padang Panjang", "Kota Pariaman", "Kota Payakumbuh", "Kota Sawahlunto", "Kota Solok"],
            "Riau": ["Kabupaten Bengkalis", "Kabupaten Indragiri Hilir", "Kabupaten Indragiri Hulu", "Kabupaten Kampar", "Kabupaten Kepulauan Meranti", "Kabupaten Kuantan Singingi", "Kabupaten Pelalawan", "Kabupaten Rokan Hilir", "Kabupaten Rokan Hulu", "Kabupaten Siak", "Kota Dumai", "Kota Pekanbaru"],
            "Kepulauan Riau": ["Kabupaten Bintan", "Kabupaten Karimun", "Kabupaten Kepulauan Anambas", "Kabupaten Lingga", "Kabupaten Natuna", "Kota Batam", "Kota Tanjungpinang"],
            "Jambi": ["Kabupaten Batanghari", "Kabupaten Bungo", "Kabupaten Kerinci", "Kabupaten Merangin", "Kabupaten Muaro Jambi", "Kabupaten Sarolangun", "Kabupaten Tanjung Jabung Barat", "Kabupaten Tanjung Jabung Timur", "Kabupaten Tebo", "Kota Jambi", "Kota Sungai Penuh"],
            "Sumatera Selatan": ["Kabupaten Banyuasin", "Kabupaten Empat Lawang", "Kabupaten Lahat", "Kabupaten Muara Enim", "Kabupaten Musi Banyuasin", "Kabupaten Musi Rawas", "Kabupaten Musi Rawas Utara", "Kabupaten Ogan Ilir", "Kabupaten Ogan Komering Ilir", "Kabupaten Ogan Komering Ulu", "Kabupaten Ogan Komering Ulu Selatan", "Kabupaten Ogan Komering Ulu Timur", "Kabupaten Penukal Abab Lematang Ilir", "Kota Lubuklinggau", "Kota Pagar Alam", "Kota Palembang", "Kota Prabumulih"],
            "Bengkulu": ["Kabupaten Bengkulu Selatan", "Kabupaten Bengkulu Tengah", "Kabupaten Bengkulu Utara", "Kabupaten Kaur", "Kabupaten Kepahiang", "Kabupaten Lebong", "Kabupaten Mukomuko", "Kabupaten Rejang Lebong", "Kabupaten Seluma", "Kota Bengkulu"],
            "Lampung": ["Kabupaten Lampung Barat", "Kabupaten Lampung Selatan", "Kabupaten Lampung Tengah", "Kabupaten Lampung Timur", "Kabupaten Lampung Utara", "Kabupaten Mesuji", "Kabupaten Pesawaran", "Kabupaten Pesisir Barat", "Kabupaten Pringsewu", "Kabupaten Tanggamus", "Kabupaten Tulang Bawang", "Kabupaten Tulang Bawang Barat", "Kabupaten Way Kanan", "Kota Bandar Lampung", "Kota Metro"],
            "Banten": ["Kabupaten Lebak", "Kabupaten Pandeglang", "Kabupaten Serang", "Kabupaten Tangerang", "Kota Cilegon", "Kota Serang", "Kota Tangerang", "Kota Tangerang Selatan"],
            "DKI Jakarta": ["Kabupaten Kepulauan Seribu", "Kota Jakarta Barat", "Kota Jakarta Pusat", "Kota Jakarta Selatan", "Kota Jakarta Timur", "Kota Jakarta Utara"],
            "Jawa Barat": ["Kabupaten Bandung", "Kabupaten Bandung Barat", "Kabupaten Bekasi", "Kabupaten Bogor", "Kabupaten Ciamis", "Kabupaten Cianjur", "Kabupaten Cirebon", "Kabupaten Garut", "Kabupaten Indramayu", "Kabupaten Karawang", "Kabupaten Kuningan", "Kabupaten Majalengka", "Kabupaten Pangandaran", "Kabupaten Purwakarta", "Kabupaten Subang", "Kabupaten Sukabumi", "Kabupaten Sumedang", "Kabupaten Tasikmalaya", "Kota Bandung", "Kota Banjar", "Kota Bekasi", "Kota Bogor", "Kota Cimahi", "Kota Cirebon", "Kota Depok", "Kota Sukabumi", "Kota Tasikmalaya"],
            "Jawa Tengah": ["Kabupaten Banjarnegara", "Kabupaten Banyumas", "Kabupaten Batang", "Kabupaten Blora", "Kabupaten Boyolali", "Kabupaten Brebes", "Kabupaten Cilacap", "Kabupaten Demak", "Kabupaten Grobogan", "Kabupaten Jepara", "Kabupaten Karanganyar", "Kabupaten Kebumen", "Kabupaten Kendal", "Kabupaten Klaten", "Kabupaten Kudus", "Kabupaten Magelang", "Kabupaten Pati", "Kabupaten Pekalongan", "Kabupaten Pemalang", "Kabupaten Purbalingga", "Kabupaten Purworejo", "Kabupaten Rembang", "Kabupaten Semarang", "Kabupaten Sragen", "Kabupaten Sukoharjo", "Kabupaten Tegal", "Kabupaten Temanggung", "Kabupaten Wonogiri", "Kabupaten Wonosobo", "Kota Magelang", "Kota Pekalongan", "Kota Salatiga", "Kota Semarang", "Kota Surakarta", "Kota Tegal"],
            "Jawa Timur": ["Kabupaten Bangkalan", "Kabupaten Banyuwangi", "Kabupaten Blitar", "Kabupaten Bojonegoro", "Kabupaten Bondowoso", "Kabupaten Gresik", "Kabupaten Jember", "Kabupaten Jombang", "Kabupaten Kediri", "Kabupaten Lamongan", "Kabupaten Lumajang", "Kabupaten Madiun", "Kabupaten Magetan", "Kabupaten Malang", "Kabupaten Mojokerto", "Kabupaten Nganjuk", "Kabupaten Ngawi", "Kabupaten Pacitan", "Kabupaten Pamekasan", "Kabupaten Pasuruan", "Kabupaten Ponorogo", "Kabupaten Probolinggo", "Kabupaten Sampang", "Kabupaten Sidoarjo", "Kabupaten Situbondo", "Kabupaten Sumenep", "Kabupaten Trenggalek", "Kabupaten Tuban", "Kabupaten Tulungagung", "Kota Batu", "Kota Blitar", "Kota Kediri", "Kota Madiun", "Kota Malang", "Kota Mojokerto", "Kota Pasuruan", "Kota Probolinggo", "Kota Surabaya"],
            "Bali": ["Kabupaten Badung", "Kabupaten Bangli", "Kabupaten Buleleng", "Kabupaten Gianyar", "Kabupaten Jembrana", "Kabupaten Karangasem", "Kabupaten Klungkung", "Kabupaten Tabanan", "Kota Denpasar"],
            "Nusa Tenggara Barat": ["Kabupaten Bima", "Kabupaten Dompu", "Kabupaten Lombok Barat", "Kabupaten Lombok Tengah", "Kabupaten Lombok Timur", "Kabupaten Lombok Utara", "Kabupaten Sumbawa", "Kabupaten Sumbawa Barat", "Kota Bima", "Kota Mataram"],
            "Nusa Tenggara Timur": ["Kabupaten Alor", "Kabupaten Belu", "Kabupaten Ende", "Kabupaten Flores Timur", "Kabupaten Kupang", "Kabupaten Lembata", "Kabupaten Malaka", "Kabupaten Manggarai", "Kabupaten Manggarai Barat", "Kabupaten Manggarai Timur", "Kabupaten Nagekeo", "Kabupaten Ngada", "Kabupaten Rote Ndao", "Kabupaten Sabu Raijua", "Kabupaten Sikka", "Kabupaten Sumba Barat", "Kabupaten Sumba Barat Daya", "Kabupaten Sumba Tengah", "Kabupaten Sumba Timur", "Kabupaten Timor Tengah Selatan", "Kabupaten Timor Tengah Utara", "Kota Kupang"],
            "Kalimantan Barat": ["Kabupaten Bengkayang", "Kabupaten Kapuas Hulu", "Kabupaten Kayong Utara", "Kabupaten Ketapang", "Kabupaten Kubu Raya", "Kabupaten Landak", "Kabupaten Melawi", "Kabupaten Mempawah", "Kabupaten Sambas", "Kabupaten Sanggau", "Kabupaten Sekadau", "Kabupaten Sintang", "Kota Pontianak", "Kota Singkawang"],
            "Kalimantan Tengah": ["Kabupaten Barito Selatan", "Kabupaten Barito Timur", "Kabupaten Barito Utara", "Kabupaten Gunung Mas", "Kabupaten Kapuas", "Kabupaten Katingan", "Kabupaten Kotawaringin Barat", "Kabupaten Kotawaringin Timur", "Kabupaten Lamandau", "Kabupaten Murung Raya", "Kabupaten Pulang Pisau", "Kabupaten Sukamara", "Kabupaten Seruyan", "Kota Palangka Raya"],
            "Kalimantan Selatan": ["Kabupaten Balangan", "Kabupaten Banjar", "Kabupaten Barito Kuala", "Kabupaten Hulu Sungai Selatan", "Kabupaten Hulu Sungai Tengah", "Kabupaten Hulu Sungai Utara", "Kabupaten Kotabaru", "Kabupaten Tabalong", "Kabupaten Tanah Bumbu", "Kabupaten Tanah Laut", "Kabupaten Tapin", "Kota Banjarbaru", "Kota Banjarmasin"],
            "Kalimantan Timur": ["Kabupaten Berau", "Kabupaten Kutai Barat", "Kabupaten Kutai Kartanegara", "Kabupaten Kutai Timur", "Kabupaten Mahakam Ulu", "Kabupaten Paser", "Kabupaten Penajam Paser Utara", "Kota Balikpapan", "Kota Bontang", "Kota Samarinda"],
            "Kalimantan Utara": ["Kabupaten Bulungan", "Kabupaten Malinau", "Kabupaten Nunukan", "Kabupaten Tana Tidung", "Kabupaten Tana Tidung", "Kota Tarakan"],
            "Sulawesi Utara": ["Kabupaten Bolaang Mongondow", "Kabupaten Bolaang Mongondow Selatan", "Kabupaten Bolaang Mongondow Timur", "Kabupaten Bolaang Mongondow Utara", "Kabupaten Kepulauan Sangihe", "Kabupaten Kepulauan Siau Tagulandang Biaro", "Kabupaten Kepulauan Talaud", "Kabupaten Minahasa", "Kabupaten Minahasa Selatan", "Kabupaten Minahasa Tenggara", "Kabupaten Minahasa Utara", "Kota Bitung", "Kota Kotamobagu", "Kota Manado", "Kota Tomohon"],
            "Sulawesi Tengah": ["Kabupaten Banggai", "Kabupaten Banggai Kepulauan", "Kabupaten Banggai Laut", "Kabupaten Buol", "Kabupaten Donggala", "Kabupaten Morowali", "Kabupaten Morowali Utara", "Kabupaten Parigi Moutong", "Kabupaten Poso", "Kabupaten Sigi", "Kabupaten Tojo Una-una", "Kabupaten Tolitoli", "Kota Palu"],
            "Sulawesi Selatan": ["Kabupaten Bantaeng", "Kabupaten Barru", "Kabupaten Bone", "Kabupaten Bulukumba", "Kabupaten Enrekang", "Kabupaten Gowa", "Kabupaten Jeneponto", "Kabupaten Kepulauan Selayar", "Kabupaten Luwu", "Kabupaten Luwu Timur", "Kabupaten Luwu Utara", "Kabupaten Maros", "Kabupaten Pangkajene dan Kepulauan", "Kabupaten Pinrang", "Kabupaten Sidenreng Rappang", "Kabupaten Sinjai", "Kabupaten Soppeng", "Kabupaten Takalar", "Kabupaten Tana Toraja", "Kabupaten Toraja Utara", "Kabupaten Wajo", "Kota Makassar", "Kota Palopo", "Kota Parepare"],
            "Sulawesi Tenggara": ["Kabupaten Bombana", "Kabupaten Buton", "Kabupaten Buton Selatan", "Kabupaten Buton Tengah", "Kabupaten Buton Utara", "Kabupaten Kolaka", "Kabupaten Kolaka Timur", "Kabupaten Kolaka Utara", "Kabupaten Konawe", "Kabupaten Konawe Kepulauan", "Kabupaten Konawe Selatan", "Kabupaten Konawe Utara", "Kabupaten Muna", "Kabupaten Muna Barat", "Kabupaten Wakatobi", "Kota Bau-Bau", "Kota Kendari"],
            "Sulawesi Barat": ["Kabupaten Majene", "Kabupaten Mamasa", "Kabupaten Mamuju", "Kabupaten Mamuju Tengah", "Kabupaten Pasangkayu", "Kabupaten Polewali Mandar"],
            "Gorontalo": ["Kabupaten Boalemo", "Kabupaten Bone Bolango", "Kabupaten Gorontalo", "Kabupaten Gorontalo Utara", "Kabupaten Pohuwato", "Kota Gorontalo"],
            "Maluku": ["Kabupaten Buru", "Kabupaten Buru Selatan", "Kabupaten Kepulauan Aru", "Kabupaten Maluku Barat Daya", "Kabupaten Maluku Tengah", "Kabupaten Maluku Tenggara", "Kabupaten Seram Bagian Barat", "Kabupaten Seram Bagian Timur", "Kota Ambon", "Kota Tual"],
            "Maluku Utara": ["Kabupaten Halmahera Barat", "Kabupaten Halmahera Tengah", "Kabupaten Halmahera Timur", "Kabupaten Halmahera Selatan", "Kabupaten Halmahera Utara", "Kabupaten Kepulauan Sula", "Kabupaten Pulau Morotai", "Kabupaten Pulau Taliabu", "Kota Ternate", "Kota Tidore Kepulauan"],
            "Papua": ["Kabupaten Asmat", "Kabupaten Biak Numfor", "Kabupaten Boven Digoel", "Kabupaten Jayapura", "Kabupaten Jayawijaya", "Kabupaten Keerom", "Kabupaten Kepulauan Yapen", "Kabupaten Mamberamo Raya", "Kabupaten Mappi", "Kabupaten Merauke", "Kabupaten Mimika", "Kabupaten Nabire", "Kabupaten Paniai", "Kabupaten Pegunungan Bintang", "Kabupaten Sarmi", "Kabupaten Supiori", "Kabupaten Tolikara", "Kabupaten Waropen", "Kabupaten Yahukimo", "Kabupaten Yalimo", "Kota Jayapura"],
            "Papua Barat": ["Kabupaten Fakfak", "Kabupaten Kaimana", "Kabupaten Manokwari", "Kabupaten Manokwari Selatan", "Kabupaten Pegunungan Arfak", "Kabupaten Teluk Bintuni", "Kabupaten Teluk Wondama", "Kota Sorong"],
            "Papua Selatan": ["Kabupaten Asmat", "Kabupaten Mappi", "Kabupaten Merauke", "Kabupaten Boven Digoel"],
            "Papua Tengah": ["Kabupaten Mimika", "Kabupaten Nabire", "Kabupaten Paniai", "Kabupaten Dogiyai", "Kabupaten Deiyai", "Kabupaten Intan Jaya"],
            "Papua Pegunungan": ["Kabupaten Jayawijaya", "Kabupaten Lanny Jaya", "Kabupaten Mamberamo Tengah", "Kabupaten Nduga", "Kabupaten Tolikara", "Kabupaten Yahukimo", "Kabupaten Yalimo"],
            "Papua Barat Daya": ["Kabupaten Sorong", "Kabupaten Sorong Selatan", "Kabupaten Maybrat", "Kabupaten Raja Ampat", "Kota Sorong"]
        };

        const jenisInstansi = document.getElementById("jenisInstansi");
        const namaInstansi = document.getElementById("namaInstansi");
        const provinsiSelect = document.getElementById("provinsi");
        const kabupatenSelect = document.getElementById("kabupaten");
        
        // Perbaikan: Ganti variabel PHP yang tidak terdefinsi di scope JS
        // Gunakan variabel yang sudah ada di PHP untuk current value
        const currentProvinsi = "<?php echo htmlspecialchars($provinsi); ?>";
        const currentKabupaten = "<?php echo htmlspecialchars($kabupaten); ?>";
        const currentJenis = "<?php echo htmlspecialchars($jenis_instansi); ?>";

        // Fungsi untuk mengisi opsi dropdown
        function fillSelect(selectElement, options, currentValue) {
            selectElement.innerHTML = `<option value="">Pilih ${selectElement.id === 'provinsi' ? 'Provinsi' : 'Kabupaten/Kota'}</option>`;
            options.forEach(optValue => {
                const opt = document.createElement("option");
                opt.value = optValue;
                opt.textContent = optValue;
                if (optValue === currentValue) {
                    opt.selected = true;
                }
                selectElement.appendChild(opt);
            });
        }

        // Fungsi utama untuk mengatur status dan mengisi dropdown Daerah
        function setupFilterDaerah() {
            // Isi dropdown Provinsi saat load
            const provinsiOptions = Object.keys(provinsiData);
            fillSelect(provinsiSelect, provinsiOptions, currentProvinsi);
            
            // Atur status awal (agar filter tetap aktif setelah submit)
            if (currentJenis === 'pusat') {
                namaInstansi.disabled = false;
                provinsiSelect.disabled = true;
                kabupatenSelect.disabled = true;
            } else if (currentJenis === 'daerah') {
                namaInstansi.disabled = true;
                provinsiSelect.disabled = false;
                // Jika ada provinsi yang terpilih, isi kabupatennya
                if (currentProvinsi && provinsiData[currentProvinsi]) {
                    kabupatenSelect.disabled = false;
                    fillSelect(kabupatenSelect, provinsiData[currentProvinsi], currentKabupaten);
                } else {
                    kabupatenSelect.disabled = true;
                }
            } else {
                namaInstansi.disabled = true;
                provinsiSelect.disabled = true;
                kabupatenSelect.disabled = true;
            }
        }
        
        // Event listener untuk perubahan Jenis Instansi
        jenisInstansi.addEventListener("change", () => {
            // Saat jenis instansi berubah, reset filter lainnya dan submit form
            namaInstansi.value = "";
            provinsiSelect.value = "";
            kabupatenSelect.value = "";
            
            // Disabled status akan diatur lagi oleh PHP setelah form disubmit
            // Kita tetap submit form-nya
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