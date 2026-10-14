@php
    $chapters = [
        [
            'id' => 'bab-01',
            'num' => '01',
            'title' => 'Navigasi HP & Ringkasan Dashboard',
            'summary' => 'Cara berpindah menu melalui layar ponsel, membaca indikator stok kritis, dan memantau ringkasan operasional harian.',
            'roles' => ['Logistik', 'Pengawas', 'Keuangan', 'Admin'],
            'route' => 'dashboard',
            'route_label' => 'Buka Dashboard',
            'icon' => 'i-dashboard',
            'mobile_img' => 'images/guide/mobile-menu.png',
            'mobile_alt' => 'Tampilan menu navigasi HP pada portal D\'Royal Village',
            'mobile_caption' => 'Menu navigasi HP: ketuk tombol garis tiga di kiri atas untuk membuka seluruh menu kerja.',
            'mobile_img_secondary' => 'images/guide/dashboard-mobile.png',
            'mobile_caption_secondary' => 'Ringkasan Dashboard di layar HP: kartu indikator tersusun satu kolom dari atas ke bawah.',
            'desktop_img' => 'images/guide/dashboard-desktop.png',
            'desktop_alt' => 'Tampilan Dashboard utama pada layar lebar D\'Royal Village',
            'desktop_caption' => 'Dashboard layar lebar menampilkan sidebar kiri, ringkasan unit rumah, radar stok kritis, dan aktivitas terbaru.',
            'steps' => [
                [
                    'title' => 'Buka menu utama di layar HP',
                    'desc' => 'Di ponsel, ketuk tombol menu (ikon tiga garis) pada bilah atas di sebelah logo D\'Royal Village. Daftar menu akan terbuka lengkap mulai dari Ringkasan, Inventaris, Lapangan, Riwayat, hingga Keuangan.',
                ],
                [
                    'title' => 'Periksa kartu peringatan operasional',
                    'desc' => 'Kartu Stok Menipis atau Stok Kritis menandai material dengan sisa stok 10 unit atau kurang. Kartu Alat di Lapangan menampilkan jumlah transaksi peminjaman alat yang belum dikembalikan ke gudang.',
                ],
                [
                    'title' => 'Gunakan pengaturan tampilan Terang atau Gelap',
                    'desc' => 'Ketuk ikon roda gigi (Pengaturan) di bagian bawah menu untuk mengganti tema layar Terang (Linen) saat bekerja di bawah sinar matahari lapangan, atau tema Gelap saat bekerja di dalam ruangan.',
                ],
            ],
            'note' => 'Akun Logistik dan Pengawas otomatis menampilkan data sesuai Cluster tugas yang ditetapkan oleh Admin. Jika muncul peringatan cluster belum ditetapkan, hubungi Admin melalui menu Manajemen User.',
        ],
        [
            'id' => 'bab-02',
            'num' => '02',
            'title' => 'Inventaris Material & Pencatatan Stok Masuk',
            'summary' => 'Mengelola daftar material bangunan, mencatat kedatangan batch barang dari supplier, serta import dan export data Excel.',
            'roles' => ['Logistik', 'Pengawas', 'Keuangan', 'Admin'],
            'route' => 'logistik.materials',
            'route_label' => 'Buka Halaman Material',
            'icon' => 'i-box',
            'mobile_img' => 'images/guide/materials-mobile.png',
            'mobile_alt' => 'Halaman Kontrol Stok Material pada tampilan HP',
            'mobile_caption' => 'Halaman Material di HP: tombol Import, Export, dan Tambah Material berukuran penuh agar mudah ditekan.',
            'desktop_img' => 'images/guide/materials-desktop.png',
            'desktop_alt' => 'Halaman Kontrol Stok Material pada tampilan desktop',
            'desktop_caption' => 'Tabel stok material per batch menampilkan kode masuk, kategori, gudang, supplier, sisa stok, dan harga satuan.',
            'steps' => [
                [
                    'title' => 'Tambah material atau batch stok masuk',
                    'desc' => 'Ketuk tombol hijau Tambah Material. Pilih kategori, nama material, satuan (sak, batang, m3, pcs), gudang penerima, mitra supplier, jumlah masuk, dan harga beli satuan, lalu ketuk Simpan.',
                ],
                [
                    'title' => 'Cari dan saring berdasarkan gudang atau status stok',
                    'desc' => 'Ketik nama atau kode barang pada kolom pencarian, atau ketuk tombol Filter untuk menyaring berdasarkan Kategori, Mitra Supplier, Gudang, dan Status Stok (Aman, Menipis, atau Habis).',
                ],
                [
                    'title' => 'Import dan Export data melalui Excel',
                    'desc' => 'Gunakan tombol Import untuk mengunggah daftar material secara massal dari file Excel/CSV dan memvalidasi pratinjau baris sebelum disimpan. Gunakan tombol Export untuk mengunduh laporan stok terkini.',
                ],
            ],
            'note' => 'Setiap kedatangan material disimpan sebagai batch harga tersendiri (memiliki Kode Masuk unik). Saat material dialokasikan ke rumah, pengurangan stok dan perhitungan biaya mengikuti harga dari batch sumber yang dipilih.',
        ],
        [
            'id' => 'bab-03',
            'num' => '03',
            'title' => 'Inventaris Alat Kerja & Kondisi Unit',
            'summary' => 'Memantau jumlah alat konstruksi yang siap pakai di gudang, sedang dipinjam di unit rumah, atau mengalami kerusakan.',
            'roles' => ['Logistik', 'Pengawas', 'Keuangan', 'Admin'],
            'route' => 'logistik.tools',
            'route_label' => 'Buka Halaman Alat',
            'icon' => 'i-wrench',
            'mobile_img' => 'images/guide/tools-mobile.png',
            'mobile_alt' => 'Halaman Kontrol Inventaris Alat pada tampilan HP',
            'mobile_caption' => 'Halaman Alat di HP menampilkan ringkasan total unit alat dan tombol Tambah Alat Baru.',
            'desktop_img' => 'images/guide/tools-desktop.png',
            'desktop_alt' => 'Halaman Kontrol Inventaris Alat pada tampilan desktop',
            'desktop_caption' => 'Daftar alat kerja memperlihatkan rincian unit tersedia per gudang, unit sedang dipinjam, dan status ketersediaan.',
            'steps' => [
                [
                    'title' => 'Daftarkan alat kerja baru ke gudang',
                    'desc' => 'Ketuk tombol Tambah Alat Baru, isi nama alat, pilih kategori dan gudang penyimpanan awal, lalu masukkan jumlah unit yang tersedia.',
                ],
                [
                    'title' => 'Pantau saldo unit tersedia per gudang',
                    'desc' => 'Pada tabel daftar alat, periksa kolom Gudang dan Tersedia untuk melihat berapa unit yang masih siap dipinjamkan dari masing-masing gudang proyek.',
                ],
                [
                    'title' => 'Perbarui data atau tambah stok unit alat',
                    'desc' => 'Gunakan tombol aksi pada baris alat untuk menambah stok unit baru ke gudang tertentu atau memperbarui informasi alat.',
                ],
            ],
            'note' => 'Alat kerja bersifat pinjam-pakai dan tidak membebani biaya material rumah. Stok tersedia akan berkurang saat alat dipinjam ke rumah dan bertambah kembali saat alat dikembalikan dalam kondisi normal.',
        ],
        [
            'id' => 'bab-04',
            'num' => '04',
            'title' => 'Alokasi Lapangan: Material, Alat, Sewa, & Pengembalian',
            'summary' => 'Lembar kerja utama di lapangan untuk mencatat pengeluaran material ke rumah, peminjaman alat, sewa vendor, dan pengembalian alat.',
            'roles' => ['Logistik', 'Pengawas', 'Admin'],
            'route' => 'logistik.alokasi',
            'route_label' => 'Buka Alokasi Lapangan',
            'icon' => 'i-transfer',
            'mobile_img' => 'images/guide/alokasi-mobile.png',
            'mobile_alt' => 'Halaman Buat Alokasi Lapangan pada tampilan HP',
            'mobile_caption' => 'Halaman Alokasi di HP: pilih salah satu dari 5 tab alur kerja, lalu ketuk tombol Pilih rumah.',
            'desktop_img' => 'images/guide/alokasi-desktop.png',
            'desktop_alt' => 'Halaman Buat Alokasi Lapangan pada tampilan desktop',
            'desktop_caption' => 'Mode Alokasi Spreadsheet memudahkan staf menyusun material, pinjaman alat, dan pengembalian untuk beberapa rumah sekaligus.',
            'steps' => [
                [
                    'title' => 'Pilih jenis alur transaksi di bagian atas',
                    'desc' => 'Tersedia 5 mode kerja: Alokasi spreadsheet (input cepat banyak rumah dalam satu tabel), Alokasi Formulir (input bertahap), Vendor (jasa borongan/pekerjaan vendor), Sewa alat (alat milik pihak ketiga), dan Kembalikan alat.',
                ],
                [
                    'title' => 'Pilih satu atau beberapa rumah tujuan',
                    'desc' => 'Ketuk tombol Pilih rumah. Anda dapat mencentang satu atau beberapa unit rumah sekaligus dalam cluster tugas Anda, lalu ketuk Terapkan.',
                ],
                [
                    'title' => 'Pilih material beserta batch sumber atau pilih alat',
                    'desc' => 'Untuk material, pilih nama item lalu pilih batch gudang sumber, isi jumlah pemakaian, nama penerima (Mandor/PJ), dan peruntukkan pekerjaan (misal: Pemasangan Pondasi atau Pekerjaan Dinding). Untuk alat, pilih alat dan gudang asal.',
                ],
                [
                    'title' => 'Catat pengembalian alat selesai pakai',
                    'desc' => 'Buka tab Kembalikan alat, pilih rumah asal pinjaman, centang alat yang dikembalikan, pilih gudang penerima, dan isi rincian kondisi unit (Normal, Rusak, atau Hilang) sebelum menekan tombol simpan.',
                ],
            ],
            'note' => 'Alokasi ke rumah yang berstatus Selesai namun masih dalam masa Garansi akan otomatis ditandai sebagai pemakaian Garansi pada riwayat dan laporan biaya.',
        ],
        [
            'id' => 'bab-05',
            'num' => '05',
            'title' => 'Transfer Stok Antar Gudang',
            'summary' => 'Memindahkan sebagian stok material per batch atau unit alat kerja dari gudang utama ke gudang lapangan.',
            'roles' => ['Logistik', 'Pengawas', 'Admin'],
            'route' => 'logistik.transfers',
            'route_label' => 'Buka Transfer Gudang',
            'icon' => 'i-transfer',
            'mobile_img' => 'images/guide/transfers-mobile.png',
            'mobile_alt' => 'Halaman Transfer Gudang pada tampilan HP',
            'mobile_caption' => 'Formulir Transfer Gudang di HP tersusun satu kolom berurutan dari jenis inventaris hingga tombol Simpan transfer.',
            'desktop_img' => 'images/guide/transfers-desktop.png',
            'desktop_alt' => 'Halaman Transfer Gudang pada tampilan desktop',
            'desktop_caption' => 'Formulir Transfer Gudang menjaga riwayat harga batch material dari gudang asal ke gudang tujuan.',
            'steps' => [
                [
                    'title' => 'Tentukan jenis inventaris serta gudang asal dan tujuan',
                    'desc' => 'Pilih Jenis inventaris (Material atau Alat), pilih Gudang asal tempat barang berada saat ini, lalu pilih Gudang tujuan pengiriman.',
                ],
                [
                    'title' => 'Pilih item dan batch sumber yang dipindahkan',
                    'desc' => 'Khusus Material, pilih lot harga material lalu pilih Batch sumber yang masih memiliki saldo tersedia. Untuk Alat, pilih unit alat yang tersedia di gudang asal.',
                ],
                [
                    'title' => 'Isi jumlah, tanggal transfer, dan simpan',
                    'desc' => 'Masukkan jumlah yang dipindahkan (tidak boleh melebihi sisa stok batch asal), lengkapi catatan pengiriman bila perlu, lalu ketuk Simpan transfer.',
                ],
            ],
            'note' => 'Transfer gudang tidak menambah biaya proyek rumah. Sistem membuat batch penerima di gudang tujuan dengan harga satuan yang sama seperti batch asal.',
        ],
        [
            'id' => 'bab-06',
            'num' => '06',
            'title' => 'Proyek Unit Rumah, Garansi, & Penyelesaian',
            'summary' => 'Memantau daftar unit rumah per cluster, melihat rincian pemakaian material tiap unit, dan menandai rumah selesai dibangun.',
            'roles' => ['Logistik', 'Pengawas', 'Admin'],
            'route' => 'logistik.houses',
            'route_label' => 'Buka Daftar Rumah',
            'icon' => 'i-houses',
            'mobile_img' => 'images/guide/houses-mobile.png',
            'mobile_alt' => 'Halaman Daftar Unit Rumah Proyek pada tampilan HP',
            'mobile_caption' => 'Halaman Rumah di HP: cari blok/kode rumah atau saring berdasarkan status Perencanaan, Pembangunan, dan Selesai.',
            'desktop_img' => 'images/guide/houses-desktop.png',
            'desktop_alt' => 'Halaman Daftar Unit Rumah Proyek pada tampilan desktop',
            'desktop_caption' => 'Tabel Daftar Rumah menampilkan kode unit, nama blok, cluster, tipe rumah, status pembangunan, dan akumulasi biaya material.',
            'steps' => [
                [
                    'title' => 'Buka rincian pemakaian per unit rumah',
                    'desc' => 'Ketuk baris rumah atau ikon mata (Lihat Detail) untuk membuka halaman detail rumah yang memuat seluruh riwayat material terpakai, alat yang sedang dipinjam di unit tersebut, dan total biaya.',
                ],
                [
                    'title' => 'Selesaikan pembangunan unit rumah',
                    'desc' => 'Saat pembangunan fisik selesai dan tidak ada alat yang masih dipinjam di unit tersebut, buka detail rumah lalu proses penyelesaian unit untuk mengaktifkan pencatatan masa garansi.',
                ],
                [
                    'title' => 'Kelola penambahan unit atau import daftar rumah',
                    'desc' => 'Gunakan tombol Tambah Rumah untuk mendaftarkan blok/unit baru beserta cluster dan tanggal target selesai, atau gunakan Import untuk mengunggah daftar rumah dari file Excel.',
                ],
            ],
            'note' => 'Pastikan seluruh pinjaman alat kerja pada unit rumah sudah dikembalikan ke gudang sebelum mengubah status rumah menjadi Selesai.',
        ],
        [
            'id' => 'bab-07',
            'num' => '07',
            'title' => 'Catatan Riwayat, Export Laporan, & Pembatalan (Void)',
            'summary' => 'Menelusuri jejak audit seluruh transaksi material dan alat, mengunduh laporan Excel, serta membatalkan transaksi yang salah input.',
            'roles' => ['Logistik', 'Pengawas', 'Keuangan', 'Admin'],
            'route' => 'logistik.material-log',
            'route_label' => 'Buka Catatan Material',
            'icon' => 'i-journal-text',
            'mobile_img' => 'images/guide/material-log-mobile.png',
            'mobile_alt' => 'Halaman Catatan Material pada tampilan HP',
            'mobile_caption' => 'Catatan Material di HP menyajikan buku besar transaksi keluar-masuk beserta filter jenis transaksi dan tombol Export.',
            'desktop_img' => 'images/guide/material-log-desktop.png',
            'desktop_alt' => 'Halaman Catatan Material pada tampilan desktop',
            'desktop_caption' => 'Tabel Catatan Material menampilkan waktu, jenis Masuk/Keluar, item, jumlah, total nilai, serta rincian tujuan atau asal barang.',
            'steps' => [
                [
                    'title' => 'Saring riwayat Masuk dan Keluar',
                    'desc' => 'Buka menu Catatan Material atau Catatan Alat. Gunakan kolom pencarian dan tombol Filter untuk menyaring transaksi berdasarkan jenis (Masuk atau Keluar), Unit Rumah, atau Mitra Supplier.',
                ],
                [
                    'title' => 'Unduh buku besar ke file Excel',
                    'desc' => 'Ketuk tombol Export di bagian atas halaman Catatan Material untuk mengunduh rekaman transaksi sesuai filter yang sedang aktif dalam format .xlsx.',
                ],
                [
                    'title' => 'Batalkan (Void) transaksi apabila terjadi salah input',
                    'desc' => 'Jika terdapat kesalahan pencatatan jumlah atau salah pilih rumah, gunakan tombol Void pada baris transaksi terkait dan isi alasan pembatalan. Stok gudang akan dikembalikan secara otomatis dan jejak pembatalan tetap tercatat untuk audit.',
                ],
            ],
            'note' => 'Sistem tidak menghapus baris transaksi secara permanen agar buku besar audit tetap utuh. Transaksi yang dibatalkan akan diberi tanda Void beserta alasan dan waktu pembatalannya.',
        ],
        [
            'id' => 'bab-08',
            'num' => '08',
            'title' => 'Laporan Biaya Rumah & Beban Operasional Cluster',
            'summary' => 'Memantau realisasi anggaran material pembangunan, biaya perbaikan masa garansi, serta pengeluaran sewa alat dan jasa vendor per cluster.',
            'roles' => ['Keuangan', 'Pengawas', 'Admin'],
            'route' => 'logistik.house-costs',
            'route_label' => 'Buka Biaya Rumah',
            'icon' => 'i-chart',
            'mobile_img' => 'images/guide/house-costs-mobile.png',
            'mobile_alt' => 'Halaman Laporan Biaya Rumah pada tampilan HP',
            'mobile_caption' => 'Halaman Biaya Rumah di HP menampilkan total akumulasi pengeluaran seluruh proyek, biaya pembangunan, dan biaya garansi.',
            'desktop_img' => 'images/guide/house-costs-desktop.png',
            'desktop_alt' => 'Halaman Laporan Biaya Rumah pada tampilan desktop',
            'desktop_caption' => 'Rekapitulasi Biaya Pembangunan memisahkan biaya masa pembangunan, biaya masa garansi, dan total pengeluaran per unit rumah.',
            'steps' => [
                [
                    'title' => 'Periksa perbandingan biaya pembangunan dan garansi',
                    'desc' => 'Buka menu Biaya Rumah untuk melihat tiga indikator utama: Akumulasi Pengeluaran Seluruh Proyek, Biaya Pembangunan, dan Biaya Garansi.',
                ],
                [
                    'title' => 'Buka rincian biaya per unit rumah',
                    'desc' => 'Ketuk salah satu baris rumah pada tabel Rincian Biaya per Rumah untuk mengaudit daftar material yang membentuk total biaya pada unit tersebut.',
                ],
                [
                    'title' => 'Pantau beban operasional sewa dan vendor di Biaya Cluster',
                    'desc' => 'Buka menu Biaya Cluster untuk melihat pengeluaran non-gudang seperti sewa alat berat, perpanjangan masa sewa, dan pembayaran jasa vendor per cluster.',
                ],
            ],
            'note' => 'Staf Logistik yang ditugaskan pada cluster tertentu juga dapat mengakses menu Biaya Cluster untuk mencatat atau memeriksa perpanjangan sewa alat di area kerjanya.',
        ],
    ];
@endphp

@auth
<x-layouts::app.sidebar title="Panduan Sistem">
    @include('logistik.partials.guide-content', ['chapters' => $chapters])
</x-layouts::app.sidebar>
@else
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    @include('partials.head', ['title' => 'Panduan Sistem'])
    <script>
        try {
            var t = localStorage.getItem('theme');
            if (t !== 'light' && t !== 'dark') {
                t = 'dark';
            }
            document.documentElement.setAttribute('data-bs-theme', t);
        } catch (e) {}
    </script>
</head>
<body class="bg-body">
    @include('partials.icons')
    <div class="container py-3 py-md-4" x-data="{
        theme: document.documentElement.getAttribute('data-bs-theme'),
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', this.theme);
            try { localStorage.setItem('theme', this.theme); } catch (e) {}
        }
    }">
        <header class="card border shadow-sm rounded-4 mb-3 bg-body-tertiary">
            <div class="card-body py-2.5 px-3 d-flex align-items-center justify-content-between gap-2">
                <a href="{{ route('login') }}" class="d-inline-flex align-items-center text-decoration-none">
                    <img src="{{ asset('images/logo-light.png') }}" alt="D'Royal Village" class="img-fluid d-dark-none" style="height: 28px; object-fit: contain;" />
                    <img src="{{ asset('images/logo-dark.png') }}" alt="D'Royal Village" class="img-fluid d-light-none" style="height: 28px; object-fit: contain;" />
                </a>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" @click="toggleTheme()" class="guide-control-btn" :aria-label="theme === 'dark' ? 'Ubah ke tampilan terang' : 'Ubah ke tampilan gelap'">
                        <svg width="16" height="16" fill="currentColor" aria-hidden="true" x-show="theme === 'dark'"><use href="#i-sun"/></svg>
                        <svg width="16" height="16" fill="currentColor" aria-hidden="true" x-show="theme === 'light'" x-cloak><use href="#i-moon"/></svg>
                        <span class="d-none d-sm-inline" x-text="theme === 'dark' ? 'Mode Terang' : 'Mode Gelap'"></span>
                    </button>
                    <a href="{{ route('login') }}" class="btn btn-success font-semibold d-inline-flex align-items-center gap-2" style="min-height: 44px;">
                        <span>Masuk ke Portal</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="main-card-container p-3 p-md-4">
            @include('logistik.partials.guide-content', ['chapters' => $chapters])
        </main>
    </div>
    @livewireScripts
</body>
</html>
@endauth
