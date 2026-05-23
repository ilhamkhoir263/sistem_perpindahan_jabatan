<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pegawai</title>
    <!-- Memuat Tailwind CSS melalui CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Konfigurasi Tailwind untuk warna dan font
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'primary': '#4f46e5', // Biru Indigo
                        'secondary': '#10b981', // Hijau Emerald
                        'background': '#f9fafb', // Abu-abu Sangat Terang
                    }
                }
            }
        }

        // Contoh Data Pegawai (Anda bisa mengganti ini dengan data dari API/database)
        const employeeData = {
            id: 'EMP001',
            nama: 'Budi Santoso',
            jabatan: 'Manajer Pemasaran Senior',
            departemen: 'Pemasaran',
            email: 'budi.santoso@contoh.com',
            telepon: '+62 812 3456 7890',
            tanggal_bergabung: '2018-05-15',
            status: 'Aktif',
            gaji_pokok: 8500000,
            alamat: 'Jl. Merdeka No. 10, Jakarta Pusat',
            skills: ['Strategi Pemasaran', 'Analisis Data', 'Manajemen Tim', 'SEO/SEM'],
            riwayat_pekerjaan: [
                { tahun: '2022 - Sekarang', posisi: 'Manajer Pemasaran Senior', unit: 'Pemasaran' },
                { tahun: '2019 - 2022', posisi: 'Spesialis Pemasaran Digital', unit: 'Pemasaran' },
                { tahun: '2018 - 2019', posisi: 'Staf Pemasaran', unit: 'Pemasaran' },
            ],
            foto_url: 'https://placehold.co/150x150/4f46e5/ffffff?text=BS', // Placeholder image
        };

        // Fungsi untuk memformat mata uang Rupiah
        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
        }

        // Fungsi untuk mengisi data ke dalam template HTML
        function loadEmployeeDetails() {
            const data = employeeData;

            // Header dan Info Dasar
            document.getElementById('profile-img').src = data.foto_url;
            document.getElementById('employee-name').textContent = data.nama;
            document.getElementById('employee-position').textContent = data.jabatan;
            document.getElementById('employee-id').textContent = data.id;

            // Detail Kontak dan Administrasi
            document.getElementById('detail-departemen').textContent = data.departemen;
            document.getElementById('detail-email').textContent = data.email;
            document.getElementById('detail-telepon').textContent = data.telepon;
            document.getElementById('detail-bergabung').textContent = new Date(data.tanggal_bergabung).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('detail-alamat').textContent = data.alamat;
            document.getElementById('detail-gaji').textContent = formatRupiah(data.gaji_pokok);
            
            const statusElement = document.getElementById('detail-status');
            statusElement.textContent = data.status;
            // Menyesuaikan warna status
            if (data.status === 'Aktif') {
                statusElement.classList.add('bg-secondary/20', 'text-secondary');
            } else {
                statusElement.classList.add('bg-red-500/20', 'text-red-600');
            }

            // Skills
            const skillsList = document.getElementById('skills-list');
            skillsList.innerHTML = '';
            data.skills.forEach(skill => {
                const li = document.createElement('li');
                li.className = 'bg-primary/10 text-primary px-3 py-1 rounded-full text-sm font-medium';
                li.textContent = skill;
                skillsList.appendChild(li);
            });

            // Riwayat Pekerjaan
            const historyList = document.getElementById('history-list');
            historyList.innerHTML = '';
            data.riwayat_pekerjaan.forEach(job => {
                const item = document.createElement('div');
                item.className = 'border-l-4 border-secondary pl-4 py-2 mb-4 bg-white shadow-sm rounded-lg';
                item.innerHTML = `
                    <p class="text-sm font-semibold text-gray-700">${job.tahun}</p>
                    <h4 class="text-lg font-bold text-primary">${job.posisi}</h4>
                    <p class="text-gray-500">${job.unit}</p>
                `;
                historyList.appendChild(item);
            });
        }

        // Fungsi untuk menampilkan modal simulasi edit
        function editDetails() {
            const modal = document.getElementById('edit-modal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.add('opacity-100'), 10);
        }
        
        // Fungsi untuk menyembunyikan modal simulasi edit
        function closeModal() {
            const modal = document.getElementById('edit-modal');
            modal.classList.remove('opacity-100');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }


        // Panggil fungsi saat dokumen selesai dimuat
        document.addEventListener('DOMContentLoaded', loadEmployeeDetails);

        // Menambahkan fitur untuk menyalin email
        function copyEmail() {
            const email = employeeData.email;
            const tempInput = document.createElement('input');
            tempInput.value = email;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            const copyMessage = document.getElementById('copy-message');
            copyMessage.classList.remove('hidden');
            setTimeout(() => {
                copyMessage.classList.add('hidden');
            }, 2000);
        }

    </script>
</head>
<body class="bg-background font-sans p-4 md:p-8">

    <!-- Message box untuk Copy Email -->
    <div id="copy-message" class="hidden fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-xl transition-opacity duration-300 z-50">
        Email berhasil disalin!
    </div>
    
    <!-- Modal Simulasi Edit (Pengganti alert()) -->
    <div id="edit-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-40 transition-opacity opacity-0 duration-300">
        <div class="bg-white rounded-xl shadow-2xl p-6 md:p-8 max-w-sm w-full transform transition-all">
            <h3 class="text-2xl font-bold text-primary mb-4">Fungsionalitas Tidak Tersedia</h3>
            <p class="text-gray-600 mb-6">Tombol ini mensimulasikan navigasi ke halaman edit.</p>
            <p class="text-sm text-gray-500 italic">Dalam aplikasi nyata, ini akan mengarahkan ke URL seperti <code class="font-mono text-xs bg-gray-100 p-1 rounded">/pegawai/edit/EMP001</code>.</p>
            <button onclick="closeModal()" class="mt-6 w-full py-2 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition duration-300">
                Tutup
            </button>
        </div>
    </div>

    <!-- Kontainer Utama -->
    <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-2xl overflow-hidden">
        
        <!-- Header Profil -->
        <div class="p-6 md:p-10 bg-primary text-white flex flex-col md:flex-row items-center justify-between">
            <div class="flex items-center space-x-6">
                <img id="profile-img" class="w-24 h-24 object-cover rounded-full border-4 border-white shadow-lg" alt="Foto Profil">
                <div>
                    <h1 id="employee-name" class="text-3xl font-extrabold mb-1">Nama Pegawai</h1>
                    <p id="employee-position" class="text-xl font-medium opacity-90">Jabatan Pegawai</p>
                    <p class="text-sm opacity-80 mt-1">ID Pegawai: <span id="employee-id" class="font-semibold"></span></p>
                </div>
            </div>
            
            <button onclick="editDetails()" class="mt-4 md:mt-0 px-6 py-2 bg-secondary hover:bg-emerald-600 text-white font-semibold rounded-lg shadow-md transition duration-300 transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zm-3.004 8.232l-3 3.004c-.38.38-.41.97-.101 1.355l.775.775a.999.999 0 001.354-.101l3.004-3.004L10.582 11.82z" />
                </svg>
                Edit Data
            </button>
        </div>

        <!-- Konten Detail -->
        <div class="p-6 md:p-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Kolom 1: Informasi Dasar -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-gray-50 p-6 rounded-xl shadow-inner border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 border-b-2 border-primary pb-2 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Informasi Dasar
                    </h2>
                    <dl class="space-y-3 text-gray-600">
                        <div class="flex justify-between items-center border-b pb-2">
                            <dt class="font-medium text-gray-500">Departemen:</dt>
                            <dd id="detail-departemen" class="font-semibold"></dd>
                        </div>
                        <div class="flex justify-between items-center border-b pb-2">
                            <dt class="font-medium text-gray-500">Status Pegawai:</dt>
                            <dd>
                                <span id="detail-status" class="px-3 py-1 text-xs font-bold rounded-full"></span>
                            </dd>
                        </div>
                        <div class="flex justify-between items-center border-b pb-2">
                            <dt class="font-medium text-gray-500">Tanggal Bergabung:</dt>
                            <dd id="detail-bergabung" class="font-semibold"></dd>
                        </div>
                        <div class="flex justify-between items-center border-b pb-2">
                            <dt class="font-medium text-gray-500">Gaji Pokok (Bulanan):</dt>
                            <dd id="detail-gaji" class="font-semibold text-secondary"></dd>
                        </div>
                    </dl>
                </div>

                <!-- Riwayat Pekerjaan -->
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 border-b-2 border-primary pb-2 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15.35h.001M12 21v-4M4.77 17.513A9 9 0 0121 12a9 9 0 01-16.23-4.513M15.356 7.427A6 6 0 0012 5.05v-4M12 21v-4M4.77 17.513A9 9 0 0121 12a9 9 0 01-16.23-4.513" />
                        </svg>
                        Riwayat Pekerjaan di Perusahaan
                    </h2>
                    <div id="history-list" class="space-y-4">
                        <!-- Data riwayat akan diisi oleh JavaScript -->
                    </div>
                </div>

            </div>

            <!-- Kolom 2: Kontak dan Skills (Sidebar) -->
            <div class="space-y-8 lg:col-span-1">
                
                <!-- Detail Kontak -->
                <div class="bg-gray-50 p-6 rounded-xl shadow-inner border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 border-b-2 border-primary pb-2 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        Detail Kontak
                    </h2>
                    <dl class="space-y-4 text-gray-600">
                        <div>
                            <dt class="font-medium text-gray-500 mb-1">Email</dt>
                            <dd class="flex items-center justify-between bg-white p-2 rounded-lg shadow-sm border">
                                <span id="detail-email" class="truncate text-sm font-semibold"></span>
                                <button onclick="copyEmail()" class="ml-2 text-primary hover:text-primary/70 focus:outline-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" />
                                        <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z" />
                                    </svg>
                                </button>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500 mb-1">Telepon</dt>
                            <dd id="detail-telepon" class="font-semibold bg-white p-2 rounded-lg shadow-sm border"></dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500 mb-1">Alamat Domisili</dt>
                            <dd id="detail-alamat" class="font-semibold bg-white p-2 rounded-lg shadow-sm border"></dd>
                        </div>
                    </dl>
                </div>

                <!-- Kompetensi/Skills -->
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 border-b-2 border-primary pb-2 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.001 2.001M16 4l2.001 2.001M10 14l2 2m0 0l2 2m-2-2l-2 2m2-2l2-2m-2 2l-2-2m2 2l2 2m-2-2l-2 2m2-2l2-2" />
                        </svg>
                        Kompetensi & Skills
                    </h2>
                    <ul id="skills-list" class="flex flex-wrap gap-2">
                        <!-- Data skills akan diisi oleh JavaScript -->
                    </ul>
                </div>
                
            </div>
        </div>

    </div>

</body>
</html>