# Sistem Informasi Perpindahan Jabatan (E-Ujikom Modul Perpindahan)

Sistem Informasi Perpindahan Jabatan adalah sebuah aplikasi berbasis web yang dirancang untuk mendigitalisasi dan mengotomatisasi seluruh alur proses pengajuan perpindahan jabatan fungsional pegawai. Sistem ini mencakup manajemen gelombang pendaftaran, verifikasi berkas bertingkat, penentuan kuota ujian (ujikom) utama/cadangan, hingga penerbitan sertifikat secara digital.

Aplikasi ini dikembangkan untuk memastikan transparansi, akuntabilitas, dan efisiensi dalam proses transformasi digital pada sektor birokrasi pemerintahan.

---

## 👥 Hak Akses & Peran Pengguna (Aktor)

Berdasarkan arsitektur sistem, terdapat 5 aktor utama yang saling terintegrasi di dalam proses bisnis perpindahan jabatan ini:

1. **Admin**
   * Mengelola pengumuman di landing page.
   * Menambahkan gelombang pendaftaran (menentukan periode pendaftaran dan kuota peserta).
   * Menginput hasil kelulusan akhir peserta (Lulus / Tidak Lulus) berdasarkan surat keputusan dari PPSDM.
2. **Peserta**
   * Membuat akun dan mendaftarkan diri pada gelombang yang aktif.
   * Mengisi formulir pengajuan perpindahan jabatan dan mengunggah dokumen persyaratan.
   * Melakukan perbaikan dokumen jika dikembalikan oleh Verifikator.
   * Melengkapi formulir pasca-lulus untuk mengunduh sertifikat dan melihat angka kredit.
3. **Kasubdit**
   * Menerima berkas pengajuan awal dari peserta.
   * Mendisposisikan tugas verifikasi secara adil ke Verifikator 1 atau Verifikator 2.
   * Menerima kembali data hasil verifikasi yang telah sesuai.
   * Meneruskan data peserta terpilih ke Direktur berdasarkan gelombang yang ditentukan.
4. **Verifikator (1 / 2)**
   * Menerima disposisi pengajuan dari Kasubdit.
   * Melakukan validasi, pemeriksaan kecocokan data, dan pemeriksaan berkas fisik digital peserta.
   * Memiliki wewenang untuk:
     * **YA (Sesuai):** Meneruskan data yang valid kembali ke Kasubdit.
     * **TIDAK (Tidak Sesuai):** Mengembalikan berkas ke akun Peserta disertai catatan perbaikan.
5. **Direktur Pergelombang**
   * Menerima data peserta yang telah lolos verifikasi berdasarkan gelombang aktif.
   * Meninjau data peserta dan dokumen surat pengumuman gelombang.
   * Meneruskan data kompilasi peserta ke pihak PPSDM dengan melampirkan Surat Pengantar resmi.
6. **PPSDM (Pusat Pengembangan Sumber Daya Manusia)**
   * Menerima data kiriman dari Direktur.
   * Melakukan pengecekan sisa kuota pelaksanaan ujian kompetensi (ujikom):
     * **Jika Kuota Belum Penuh:** Menetapkan jadwal ujikom dan memproses peserta untuk mengikuti ujian.
     * **Jika Kuota Penuh (YA):** Mengubah status peserta menjadi **CADANGAN** untuk diikutsertakan pada jadwal ujikom periode berikutnya.

---

---

## 🛠️ Teknologi yang Digunakan

* **Backend:** PHP (Native / Structured)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, JavaScript (Chatbot Interaktif & Widget terintegrasi)
* **Library & Tema UI:** Vendor Assets (Bootstrap / AdminLTE sesuai dengan struktur folder `dist/` dan `assets/`)
* **Email Service:** PHP内置/PHPMailer (Terstruktur di folder `vendor/phpmailer/` untuk fitur pengiriman OTP, Verifikasi Registrasi, dan Notifikasi Status Berkas)

