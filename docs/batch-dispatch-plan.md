# Rencana Batch, Pengiriman, dan Penerimaan Barang

Tanggal: 24 September 2026  
Status: G1 sampai G6 selesai di workspace lokal. G7 sebagian selesai. Pengujian otomatis hanya memakai MySQL `logistik_test`; migrasi skema pada `logistik` diterapkan pada 25 September setelah persetujuan pengguna dan pencadangan. G8 masih menunggu rekonsiliasi data lama, pemeriksaan browser setelah login ulang, dan keputusan penerimaan produksi.

## Tujuan utama

Mencatat data riil setiap kedatangan, pengiriman, penerimaan, kerusakan, dan pengembalian material atau alat. Setiap jumlah dapat ditelusuri ke batch asal, lokasi, waktu kejadian, dan pengguna yang mencatatnya.

Dokumen ini mencatat persyaratan, keputusan, dan bukti implementasi lokal. Status goal bukan bukti rekonsiliasi workbook atau migrasi data produksi.

## Hasil audit G1

Audit kode pada 24 September 2026 menemukan alur berikut:

- **Kedatangan material:** `stock_ins` sudah menyimpan jumlah, harga, supplier, tanggal, pencatat, dan bukti. Baris belum memiliki kode batch, gudang, atau saldo sisa. Formulir tambah material membuat satu `StockIn` hanya jika stok awal lebih dari nol. Formulir restock membuat riwayat masuk, tetapi menggabungkan stok ke baris `materials` yang cocok.
- **Kedatangan alat:** `tools.code` unik per baris alat, tetapi stok tersedia dan rusak masih berupa agregat `tools`/`tool_warehouse_balances`. Formulir manual tidak membuat peristiwa kedatangan. Import memakai kode atau nama untuk menemukan dan memperbarui alat yang sudah ada.
- **Alokasi langsung:** `TransaksiLogistik` mengurangi stok agregat dan membuat `material_usages` atau `tool_usages`. Kode MAT/ALT saat ini mengidentifikasi transaksi, bukan batch sumber. Alur ini langsung mencatat penggunaan rumah tanpa konfirmasi kedatangan.
- **Permintaan dan pengiriman:** `MandorRequest` membuat satu `material_tool_requests` untuk satu jenis barang dan rumah. `Dispatches` mengurangi stok agregat saat kirim dan menyimpan gudang serta pengirim. `request_code` sudah unik, tetapi dibuat saat permintaan dan belum menjadi kode pengiriman terpisah. Mandor peminta saat ini mengunggah bukti foto untuk mengubah status ke tiba; jumlah diterima dan rusak belum dicatat.
- **Cluster:** rumah dan pengguna memiliki `cluster_id`, tetapi hanya permintaan Mandor yang membatasi rumah menurut cluster pada server. Formulir admin mewajibkan cluster untuk Mandor saja dan mengosongkannya untuk Logistik. Gudang belum memiliki cluster.
- **Pengembalian dan koreksi:** pengembalian alat ditautkan ke peminjaman dan gudang, bukan kedatangan batch. Pembatalan material memulihkan agregat. Koreksi, transfer gudang, penolakan pengiriman, dan import juga mengubah saldo di luar alur batch.

Perubahan data yang diperlukan: gunakan `stock_ins` sebagai sumber riwayat kedatangan material dengan identitas dan saldo batch; tambahkan riwayat kedatangan alat; tautkan setiap alokasi dan pengembalian ke sumber batch; simpan pengiriman dan konfirmasi penerimaan sebagai kejadian terpisah; terapkan batas cluster pada setiap query dan aksi server.

Data lama tidak menyimpan hubungan alokasi ke kedatangan sumber. Migrasi tidak boleh mengarang hubungan FIFO. Saldo lama harus tetap dapat dijelaskan sebagai saldo warisan yang belum terpetakan sampai direkonsiliasi. Perubahan skema harus aditif dan dapat dibatalkan sebelum data operasional baru bergantung pada kolom tersebut.

## Persyaratan yang sudah disepakati

- Tambah Material/Alat berarti barang sudah tiba di gudang.
- Setiap kedatangan dicatat sebagai batch terpisah dengan kode masuk yang unik dan permanen.
- Nama, harga, dan jumlah yang identik tetap boleh dicatat sebagai kedatangan berbeda. Sistem tidak menggabungkan batch otomatis berdasarkan kesamaan properti.
- Waktu barang diterima dan waktu pencatatan disimpan terpisah untuk mendukung pencatatan terlambat.
- Alokasi langsung memakai batch yang dipilih Logistik. Untuk permintaan, sistem memilih batch tersedia secara otomatis menurut waktu penerimaan tertua dan menampilkan pembagian sumber sebelum dikirim.
- Pengiriman memiliki kode unik dan referensi batch, gudang asal, rumah tujuan, jumlah, serta pengirim.
- Setiap pengguna Logistik dan Mandor ditugaskan ke tepat satu cluster. Admin dapat bekerja lintas cluster.
- Mandor, Logistik, atau Admin dapat mengonfirmasi kedatangan. Logistik boleh mengonfirmasi pengirimannya sendiri ketika menyaksikan kedatangan di lapangan.
- Identitas pengirim dan pengonfirmasi dicatat secara terpisah sesuai pengguna sebenarnya.
- Penerimaan mendukung jumlah parsial, barang rusak, dan jumlah yang belum tiba.
- Penerimaan berikutnya tetap terhubung ke pengiriman yang sama; setiap konfirmasi memiliki riwayat sendiri.
- Kerugian material rusak dicatat terpisah dari biaya material layak pakai, dengan hubungan ke rumah, batch, dan pengiriman.
- Pengembalian alat harus dapat ditelusuri ke pinjaman dan batch asal.
- Pengiriman formulir berulang secara tidak sengaja tidak boleh menggandakan pencatatan atau pemotongan stok.

## Definisi jumlah

Pada formulir penerimaan, **jumlah rusak merupakan bagian dari jumlah diterima**, bukan tambahan di luar jumlah diterima.

Contoh: dikirim 100, diterima 98, termasuk 3 rusak. Hasilnya 95 layak pakai, 3 rusak, dan 2 belum tiba.

Pada saat rekonsiliasi pengiriman, jumlah dikirim harus dapat dijelaskan melalui kategori yang tidak tumpang tindih: diterima layak pakai, diterima rusak, masih dalam perjalanan, dikembalikan sebelum diterima di lokasi, atau dinyatakan hilang.

Perubahan kondisi setelah penerimaan merupakan kejadian berikutnya yang ditautkan ke riwayat tersebut. Pengembalian atau penghapusan barang rusak tidak boleh dihitung lagi sebagai tambahan jumlah pada pengiriman awal.

## Usulan teknis dan operasional untuk implementasi

- Gunakan kode masuk untuk mengidentifikasi batch kedatangan. Jangan mewajibkan kode katalog tambahan sebelum kebutuhan dan struktur yang ada diperiksa.
- Satu dokumen pengiriman dapat berisi beberapa baris barang/batch. Setiap baris menunjuk satu batch dan satu rumah tujuan agar asal dan tujuan tidak ambigu.
- Saat pengiriman benar-benar berangkat, stok tersedia gudang berkurang dan jumlah dicatat dalam perjalanan. Jika ada draf, menyimpan draf belum berarti barang berangkat.
- Tampilkan nama barang, kode batch, gudang, tanggal masuk, satuan, harga, dan stok tersedia pada pemilihan batch.
- Gunakan transaksi database dan pembaruan stok yang aman terhadap pengiriman bersamaan.
- Batasi jumlah diterima terhadap sisa pengiriman dan jumlah rusak terhadap jumlah diterima pada konfirmasi tersebut.
- Gunakan status yang diturunkan dari jumlah dan penyelesaian selisih; penerimaan parsial tidak otomatis menutup pengiriman.
- Simpan riwayat koreksi. Jangan mengubah kode permanen atau menghapus jejak barang yang sudah bergerak.
- Barang rusak tetap mempunyai lokasi dan kondisi fisik yang tercatat sampai tindak lanjutnya selesai, meskipun nilai kerugiannya sudah dilaporkan.

## Keputusan yang masih perlu diselesaikan

| Keputusan | Usulan awal | Status |
| --- | --- | --- |
| Kepemilikan gudang | Gudang dan stok dipakai bersama oleh semua cluster; rumah dan transaksi rumah tetap dibatasi menurut cluster pengguna. | Disepakati |
| Hak menyatakan kehilangan/penghapusan | Logistik dapat mencatat dan menyelesaikan penghapusan sendiri. | Disepakati |
| Pengakuan biaya material layak pakai | Akui biaya hanya saat jumlah layak pakai dikonfirmasi; nilai dalam perjalanan ditampilkan terpisah. | Disepakati |
| Bukti penerimaan | Bukti wajib untuk barang rusak atau jumlah yang berbeda dari jumlah kirim. | Disepakati |
| Koreksi penerimaan | Hanya Admin yang boleh menambahkan koreksi; simpan pelaku, waktu, alasan, dan nilai sebelum/sesudah. | Disepakati |
| Perpindahan penugasan pengguna | Riwayat yang terlihat mengikuti cluster tugas pengguna saat ini; identitas pelaku tetap tercatat. | Disepakati |
| Penilaian alat rusak | Catat jumlah dan kondisi alat rusak saja; bila diperbaiki, biaya perbaikan dicatat pada kolom terpisah dan tidak digabung dengan biaya material habis pakai. | Disepakati |
| Pinjaman alat aktif | Tolak pinjaman kedua untuk alat yang sama ke rumah yang sama sampai pinjaman sebelumnya ditutup. | Disepakati |

Keputusan di tabel berlaku untuk alur baru. Catatan warisan tanpa bukti batch tetap ditandai sebagai sumber yang tidak tercatat.

## Goals implementasi

### G1. Audit dan pemetaan data yang ada

- [x] Telusuri alur tambah material/alat, stok masuk, alokasi, pengiriman, penerimaan, pengembalian, dan laporan biaya.
- [x] Inventarisasi kode dan relasi yang sudah ada sebelum menambah struktur baru.
- [x] Petakan semua jalur penulisan stok, termasuk impor dan koreksi.
- [x] Catat perbedaan perilaku saat ini dengan persyaratan di atas.
- [x] Susun pemetaan migrasi serta pilihan pemulihan jika migrasi gagal.

Selesai ketika ada pemetaan model/alur yang berbasis kode aktual, daftar perubahan yang diperlukan, dan daftar keputusan penghambat tiap tahap.

### G2. Batch kedatangan yang dapat ditelusuri

Bergantung pada: G1.

- [x] Setiap kedatangan menghasilkan batch dan kode masuk unik.
- [x] Simpan gudang, kuantitas, satuan, harga yang relevan, waktu kedatangan, waktu pencatatan, dan pencatat.
- [x] Izinkan kedatangan identik yang sengaja dicatat terpisah.
- [x] Cegah pengulangan permintaan yang sama menggandakan batch.
- [x] Terapkan aturan yang sama pada input manual dan impor yang merepresentasikan penerimaan barang.

Selesai ketika dua kedatangan identik mempunyai kode dan saldo masing-masing, sedangkan pengulangan satu pengiriman formulir tidak menciptakan kedatangan tambahan. Bukti saat ini: 23 tes terfokus lulus pada MySQL `logistik_test`; formulir penerimaan alat juga diuji di browser lokal dan menampilkan kode batch, waktu tiba, waktu pencatatan, serta pencatat. Pada 25 September, tindakan `Tambah stok` yang sebelumnya belum terhubung pada daftar Material tersedia di tombol baris desktop dan menu telepon. Browser `logistik_test` mencatat dua kedatangan 50 batang dengan harga sama sebagai kode RCV terpisah; total stok menjadi 100. `MaterialReceiptIdentityTest`: 2 tes, 17 asersi lulus.

### G3. Pembatasan satu cluster per pengguna

Bergantung pada: G1 dan keputusan kepemilikan gudang.

- [x] Terapkan penugasan tepat satu cluster untuk pengguna Logistik dan Mandor yang aktif menjalankan operasi.
- [x] Tangani pengguna lama yang belum memiliki penugasan secara jelas.
- [x] Batasi pembacaan dan perubahan data melalui otorisasi server, termasuk ID yang dikirim langsung.
- [x] Pertahankan akses lintas cluster untuk Admin.
- [x] Terapkan aturan akses transaksi saat penugasan pengguna berubah.

Selesai pada workspace lokal. Admin wajib menetapkan cluster untuk Logistik dan Mandor; akun warisan tanpa cluster tidak mendapat daftar rumah atau akses transaksi rumah. Query dan aksi server memakai cluster pengguna saat ini; Admin tetap lintas cluster. Bukti: `MandorClusterScopeTest`, `FollowUpIntegrityTest`, dan uji browser berlabel `[TEST]` untuk Admin, Logistik, dan Mandor pada MySQL `logistik_test`.

### G4. Pengiriman dengan sumber batch tercatat

Bergantung pada: G2 dan G3.

- [x] Hitung pembagian batch otomatis sesuai jumlah permintaan dan tampilkan sumber yang akan dipakai.
- [x] Buat kode pengiriman dan baris yang menautkan batch, gudang, rumah, dan jumlah.
- [x] Catat keberangkatan, pengirim, serta perubahan stok gudang menjadi barang dalam perjalanan.
- [x] Tolak jumlah melebihi stok dan cegah pemotongan berulang.
- [x] Dukung beberapa batch tanpa kehilangan rincian sumbernya.

Selesai pada workspace lokal. Pengiriman material dari banyak batch dan alat dari banyak gudang membuat satu baris per sumber; Logistik cukup mencatat permintaan dan mengunggah foto, sementara sistem membagi jumlah ke sumber tersedia tertua dan menampilkan rencana sebelum konfirmasi. Penggunaan rumah mempertahankan kode batch/gudang serta kode pengiriman. Alokasi langsung material tetap mewajibkan batch eksplisit, dan pembatalan memulihkan batch asal. Bukti sebelumnya: tes MySQL `logistik_test` pada `FollowUpIntegrityTest`, `TransactionFlowHardeningTest`, dan `TransactionHousePickerTest`.

### G5. Penerimaan parsial dan kondisi barang

Bergantung pada: G4 serta aturan bukti dan koreksi.

- [x] Izinkan konfirmasi oleh Mandor, Logistik, dan Admin sesuai lingkup akses.
- [x] Simpan setiap konfirmasi sebagai kejadian terpisah dengan pelaku dan waktu.
- [x] Catat diterima, rusak yang termasuk di dalamnya, dan layak pakai.
- [x] Hitung sisa belum tiba dan izinkan kedatangan lanjutan.
- [x] Cegah penerimaan melebihi sisa dan konfirmasi ganda akibat pengulangan permintaan.
- [x] Sediakan koreksi yang mempertahankan riwayat.

Selesai pada workspace lokal. Biaya material dibuat dari jumlah layak pakai saat tiap penerimaan dikonfirmasi; transit tetap terpisah. Bukti foto diwajibkan untuk kerusakan, penerimaan sebagian, atau selisih. Koreksi hanya tersedia untuk Admin dan menambah kejadian bertanda dengan jumlah delta; pelaku dan nilai sebelumnya tetap di riwayat. Skenario 100/98/3 dan penerimaan sisa dua unit diuji pada MySQL `logistik_test`.

### G6. Kerugian, pengembalian, dan penyelesaian selisih

Bergantung pada: G5 dan keputusan otorisasi serta pengakuan biaya.

- [x] Pisahkan laporan kerugian material dari biaya material layak pakai.
- [x] Catat keputusan untuk barang rusak, barang hilang, dan barang yang kembali.
- [x] Tautkan pengembalian alat ke pinjaman, batch, kondisi, serta gudang penerima.
- [x] Pertahankan jumlah dan nilai agar tidak dihitung dua kali ketika kondisi atau lokasi berubah.
- [x] Tetapkan kriteria pengiriman selesai dan pengiriman yang masih membutuhkan tindak lanjut.

Selesai pada workspace lokal. Selisih transit dapat ditandai kembali, hilang, atau dibuang dengan jumlah, alasan, waktu, dan pelaku. Material rusak dan hilang menjadi baris kerugian terpisah dari biaya material layak pakai. Alat rusak tetap di saldo kondisi rusak sampai diperbaiki atau dihapus; biaya perbaikan opsional tersimpan terpisah, sesuai keputusan pengguna. Rumah tidak dapat diselesaikan selama ada selisih transit atau kerusakan material yang belum ditindaklanjuti. Bukti: suite terfokus terakhir 74 tes / 432 asersi lulus; browser `logistik_test` memeriksa penyelesaian material, perbaikan alat, riwayat Mandor/Admin, dan perlindungan pinjaman lama.

### G7. UI dan laporan yang konsisten

Bergantung pada: G2 sampai G6; dikerjakan bersama setiap alur terkait.

- [x] Ikuti DESIGN.md dan gunakan label operasional berbahasa Indonesia.
- [x] Tampilkan stok gudang, barang dalam perjalanan, penerimaan layak pakai, kerusakan, dan selisih terbuka dengan jelas.
- [x] Sediakan rincian kode masuk dan kode pengiriman beserta riwayatnya.
- [x] Perbarui laporan rumah agar biaya material dan kerugian dapat dibaca terpisah.
- [x] Sediakan keadaan kosong, proses menyimpan, kesalahan, dan koreksi tanpa kehilangan input.
- [ ] Pastikan tampilan mobile, desktop, tema terang/gelap, keyboard, dan ikon SVG konsisten.

UI dispatch menampilkan jumlah dikirim, layak pakai, rusak, masih dalam perjalanan, kembali, dan hilang; jumlah material pecahan tidak dibulatkan. Modal membatasi tindakan ke sisa yang memenuhi syarat. Laporan detail rumah memisahkan nilai material layak pakai dan kerugian. Browser meja kerja Catatan Material diperiksa pada tema terang dan gelap; pencarian data uji dan fokus keyboard terlihat jelas. Tampilan desktop memakai area gulir tabel dan memotong nama panjang tanpa tumpang tindih. Pada 25 September, browser memeriksa baris dengan nama panjang pada lebar 476 × 350 dan 1280 × 720 di `logistik_test`: kartu telepon membungkus nama tanpa menyentuh volume/satuan/harga, dan tabel desktop memotong nama dengan elipsis tanpa menimpa kolom berikutnya. Dialog Tambah Stok diuji di tema terang/gelap; tombol tersedia di desktop dan menu telepon, Enter membuka dialog, fokus Tab terperangkap, dan Escape menutup dialog serta memulihkan fokus. Ikon aksi memakai SVG inline. Uji telepon untuk alokasi tercatat di `docs/transaction-ux-verification.md`; pemeriksaan seluruh layar batch pada semua ukuran, tema, dan keyboard masih perlu dilanjutkan.

Audit browser berikutnya menemukan pesan kosong antrean pengiriman terpotong pada telepon akibat aturan tabel global `white-space: nowrap`. Sel kosong kini dikecualikan dari aturan itu dan memakai tata letak kartu telepon yang sudah ada. Satu tab port 8011 memeriksa lebar 390 px pada tema terang/gelap, desktop 1280 px, dan fokus keyboard; pesan terbaca tanpa scroll halaman horizontal. Audit layar lain masih terbuka.

Audit Catatan Material berikutnya memakai satu tab dan server sementara dengan MySQL `logistik_test`. Penerimaan `[TEST] Rekonsiliasi Browser 20260925` sebanyak 1 sak seharga Rp 100.000 menghasilkan kode masuk `RCV-01M3AKCCMV0J99XBJPHJSKHH2J`; riwayat menampilkan waktu tiba, waktu catat, pelaku, jumlah, dan harga. Pada desktop, nama sepanjang 334 px dipotong di kolom 256 px dan kolom sebelahnya tidak bertumpuk saat tabel digulir. Pada lebar 390 dan 320 px, baris penerimaan menjadi kartu tanpa scroll halaman horizontal. Audit yang sama menemukan pesan kosong Catatan Alat terpotong pada 320 px karena label kosong memakan 124 px. CSS kini memusatkan dan membungkus pesan kosong Catatan Alat dan Catatan Material. Browser memeriksa hasil pada 320 px di tema gelap dan terang, serta memastikan tidak ada overflow halaman. Pemeriksaan seluruh layar batch tetap terbuka.

Pada 25 September, formulir edit alat mengubah nilai database `100000.00` menjadi `Rp 100.000` di tema terang dan gelap. Browser menemukan fokus keyboard dapat keluar dari dialog Tambah/Edit; dialog material dan alat kini menempatkan fokus awal, membatasi Tab/Shift+Tab, menutup lewat Escape, dan mengembalikan fokus ke tombol pembuka. Browser mengonfirmasi alur tersebut di satu tab pada port 8011; indikator fokus tombol tutup terlihat hijau di tema terang dan limau di tema gelap. `ToolStockEditTest` dan `MaterialPriceEditTest`: 2 tes, 38 asersi lulus; test material kini memilih batch sumber dan mengunci biaya ke harga batch, bukan harga katalog yang berubah. Semua fixture tetap berlabel `[TEST]` pada MySQL `logistik_test`.

Pada 25 September, empat dialog antrean pengiriman (batch, penerimaan, koreksi, dan penyelesaian selisih) diuji di browser pada satu tab di port 8011. Fokus awal masuk ke dialog; Tab/Shift+Tab membatasi fokus; Escape menutup dan mengembalikan fokus ke pemicu. Koreksi membuka kembali rincian penerimaan yang memuat pemicu tersembunyi. Simpan pengiriman mengarahkan fokus ke tindakan penerimaan berikutnya; simpan penerimaan dan penyelesaian selisih ke baris permintaan; simpan koreksi ke rincian koreksi. Seluruh mutasi memakai fixture `[TEST]` di MySQL `logistik_test`.

Audit Gudang pada satu tab menemukan hitungan baris material berlabel "jenis" dan total stok yang menjumlahkan satuan berbeda. Daftar kini menampilkan jumlah baris stok dan mengurutkan kolom Material berdasarkan jumlah itu; total kuantitas lintas satuan dihapus. Hasil pencarian kosong sekarang muncul di luar tabel sehingga terbaca pada 320 px, dengan pesan yang membedakan hasil tidak cocok dari belum ada gudang. Browser memeriksa baris normal, pengurutan, dan hasil kosong pada tema terang/gelap di 320 px tanpa scroll halaman horizontal. Audit layar lain masih terbuka.

Audit Detail Gudang menemukan ringkasan "jenis · stok" dengan masalah satuan yang sama dan pesan kosong di tengah tabel lebar yang tidak terlihat pada telepon. Ringkasan kini menyebut baris stok tanpa menjumlahkan satuan berbeda; kedua tab menampilkan pesan kosong di luar tabel. Satu tab browser memeriksa data normal Gudang Utama serta gudang kosong berlabel `[TEST] Gudang Kosong G7 20260925` pada 320 px. Pesan Material dan Alat terlihat pada tema terang/gelap tanpa scroll halaman horizontal; warna teks sekunder mengikuti tema. Fixture hanya dibuat di `logistik_test`. Audit layar lain masih terbuka.

Dialog Impor Material kini mengingatkan agar workbook sumber multi-sheet tidak langsung diunggah sebelum gudang, tanggal, dan format baris dipetakan. Dialog memakai pola fokus yang sudah digunakan formulir material: fokus masuk saat dibuka, Tab/Shift+Tab tetap di dialog, dan Escape mengembalikan fokus ke tombol Import. Browser memeriksa teks dan fokus pada satu tab di lebar 320 px serta tema terang/gelap; teks panduan mengikuti warna sekunder tiap tema. Audit layar lain masih terbuka.

Dialog Impor Alat memakai pola fokus yang sama dan memperjelas bahwa data alat harus dipisahkan dari material sebelum impor workbook lama. Browser pada satu tab memeriksa panduan, lebar 320 px tanpa scroll halaman horizontal, Tab/Shift+Tab yang tetap di dialog, dan Escape yang mengembalikan fokus ke tombol Import. Audit layar lain masih terbuka.

Retest 25 September pada dialog tersebut mengonfirmasi footer Batal dan Proses tetap terjangkau lewat gulir vertikal internal pada 320 px. Tab berputar kembali dari aksi terakhir ke tombol Tutup; Escape menutup dialog dan mengembalikan fokus ke Import. Tidak ada berkas yang diunggah.

Pada 25 September, dialog impor Material dan Alat dibuka di tab yang sama pada port 8011. Deskripsi keduanya kini menyebut Excel dan CSV, sesuai jenis berkas yang diterima pemilih berkas.

Pada 25 September, satu tab browser port 8011 menguji transfer `[TEST] Browser Transfer Source 20260925` sebanyak 4,5 sak di MySQL `logistik_test`. Kode transfer `TRF-01M3AYANSJ78T4G3HJ586PR03W` menautkan batch penerimaan `RCV-01M3AY8W6JAK5GB7T9MAEAABTB` ke batch tujuan. Setelah harga katalog berubah dari Rp 97.500 menjadi Rp 125.000, batch dan histori transfer tetap memakai harga sumber Rp 97.500; Catatan Material menandai transfer terpisah dengan biaya Rp 0. Subtitle dan petunjuk kolom Tipe kini juga mencakup transfer. Tema terang dan gelap diperiksa; tidak ada error console. `RemainingTasksTest`: 1 tes / 32 asersi lulus, termasuk ketersediaan batch tujuan di pemilih pengiriman dan label ekspor. Audit seluruh layar, ukuran telepon, serta fokus keyboard tetap terbuka.

Pemeriksaan browser tambahan pada 25 September memakai tab yang sama di Catatan Material desktop. Mengurutkan Volume lalu Satuan memindahkan indikator arah ke kolom aktif saja; tabel tetap memakai gulir horizontal internal. Pencarian tanpa hasil menampilkan pesan kosong, dan menghapus pencarian mengembalikan dua catatan uji. Tampilan normal dipulihkan setelah pemeriksaan. Pemeriksaan ini tidak menambah atau mengubah data.

Pada 25 September, dua penerimaan `[TEST]` dengan nama material panjang diperiksa di Catatan Material pada satu tab port 8011. Kolom nama berhenti tepat sebelum Volume dan memotong teks dengan elipsis; batas kanan nama dan batas kiri Volume sama-sama 653 px, tanpa teks menumpuk. Pada lebar 320 px, baris menjadi kartu, nama panjang membungkus, dan lebar halaman tetap 315 px tanpa scroll horizontal. Fixture tersimpan hanya di MySQL `logistik_test`; tampilan browser dikembalikan ke ukuran desktop.

Pemeriksaan alokasi menemukan node label Alpine berulang setelah pemilihan material. Label stok dan batas kini memakai elemen tetap; ringkasan material/alat memakai `x-show.important` agar keadaan tersembunyi tidak dilawan kelas Bootstrap `d-flex`. Harga satuan dan biaya per rumah membaca harga batch terpilih. `TransactionFlowHardeningTest`: 25 tes / 127 asersi lulus di `logistik_test`, termasuk harga katalog Rp30.000 dengan batch Rp25.000. Suite me-reset database uji, lalu snapshot sebelum suite dipulihkan dari `.tmp/acceptance-backup/logistik_test_before_material_picker.sql` (SHA-256 `F652A2D1C8640D808AED698399B2E8C9E045085BBC2BE64C8587911B3B4EA791`); database produksi `logistik` tidak disentuh. Pemeriksaan browser pascates pada satu tab port 8011 menyimpan alokasi berlabel `MAT-01M3B3H4BSB7V3M6W3K818JGKM` untuk dua rumah memakai batch Rp25.000/sak, dengan biaya Rp25.000 per rumah dan Rp50.000 total. Pembacaan ulang `logistik_test` memastikan stok material dan sisa batch masing-masing turun dari 10 menjadi 8, dengan dua baris pemakaian masing-masing satu sak dan biaya Rp25.000. Konsol browser tidak melaporkan warning atau error.

Pada 25 September, dialog penerimaan Mandor dipindahkan ke `body` dengan pola teleport yang sudah dipakai dialog lain; ini membuat posisi tetap terhadap viewport, bukan shell aplikasi yang bertransformasi. Satu tab browser port 8011 menampilkan dialog di tengah layar dengan footer terlihat. Alur berlabel `[TEST] Browser modal teleport check 20260925` berhasil dari permintaan hingga penerimaan 1/1; `logistik_test` mencatat status `arrived`, bukti opsional kosong untuk penerimaan penuh yang layak, dan biaya rumah Rp 25.000 dari batch Rp 25.000. `MandorClusterScopeTest`: 11 tes / 46 asersi lulus. `logistik_test` dipulihkan dari snapshot `after-modal-browser-before-tests-20260925.sql` (SHA-256 `E1CE5AE7E872FB6F9E37FE6AD1E003EF2F6075C5C1AD62507209F0FEAF926291`); database `logistik` tidak disentuh.

### G8. Migrasi dan verifikasi akhir

Bergantung pada: seluruh goal sebelumnya.

- [ ] Pertahankan kode, saldo, dan histori lama yang dapat dipetakan dengan benar.
- [ ] Tandai informasi warisan yang tidak tersedia; jangan membuat bukti kedatangan atau konfirmasi fiktif.
- [ ] Bandingkan saldo dan nilai sebelum/sesudah migrasi serta dokumentasikan setiap perbedaan yang memang diperlukan.
- [x] Tandai asal gudang pinjaman alat yang tidak terbukti; jangan gunakan lokasi gudang saat ini untuk mengoreksi saldo riwayat lama.
- [x] Jalankan pengujian otomatis pada database MySQL pengujian yang terisolasi.
- [x] Uji alur browser sebagai Logistik, Mandor, dan Admin dengan data pengujian berlabel jelas.
- [ ] Catat hasil pemeriksaan serta keputusan operasional final sebelum penggunaan produksi.

Migrasi skema selesai pada `logistik_test`. Kolom provenance menandai gudang asal lama yang belum terverifikasi meskipun kolom gudang berisi hasil backfill sebelumnya; pembatalan tidak memakai nilai tersebut. Pengiriman baru dan impor checkout merekam sumber eksplisit; penerimaan catatan lama tanpa sumber tetap kosong. Impor pengembalian memakai gudang tujuan yang dipilih tanpa menambah saldo awal fiktif di gudang itu, dan sisa pinjaman impor tetap membawa provenance asal. Suite terfokus lulus 74 tes / 432 asersi. Browser memeriksa panel Logistik, riwayat Mandor, laporan biaya Admin, dan pesan perlindungan pinjaman lama memakai fixture `[TEST]`. Browser juga menemukan `logistik.test` melempar 500 karena database `logistik` belum memiliki kolom `material_usages.dispatch_code`; database itu tidak diubah. Belum ada perbandingan saldo warisan sebelum/sesudah atau migrasi ke `logistik`; verifikasi itu wajib sebelum penggunaan produksi.

Pembaruan 25 September setelah persetujuan pengguna: seluruh 12 migrasi tertunda diterapkan ke `logistik` setelah dump penuh tersimpan di `.tmp/acceptance-backup/logistik_before_authorized_migrations_20260925_095309.sql` (SHA-256 `a33c8015e3051cb94c7ba2b2deed6eb2d541e59e93b33e570ff47182d805e8a5`). Sesudah migrasi, kuantitas material tetap 105,50 dan nilai katalog tetap Rp 12.907.036,05; alat tersedia tetap 4 dan rusak tetap 1; jumlah pemakaian material/alat dan permintaan tetap sama. Empat batch material saldo warisan menandai 105,50 unit tanpa bukti waktu tiba; empat baris alat ditandai sebagai saldo warisan. Semua migrasi berstatus `Ran`. Permintaan Mandor dibuka ulang di tab browser yang sama, tetapi dialihkan ke login; pemeriksaan halaman terautentikasi pascamigrasi masih menunggu login ulang. Perbandingan per gudang/rumah, rekonsiliasi workbook, cutoff, dan hitungan fisik terbaru belum selesai; sistem belum dinyatakan siap produksi.

Masukan 25 September: formulir kirim tidak meminta Logistik mengetik ulang jumlah. Satu tombol membuka ringkasan jumlah permintaan, pembagian batch otomatis tertua lebih dulu, dan unggah foto wajib; kode sumber tiap baris tetap disimpan. Foto pengiriman dapat dilihat Logistik dan Mandor. Migrasi nullable `dispatch_proof_image` diterapkan setelah backup `.tmp/acceptance-backup/logistik_before_dispatch_proof_20260925_100648.sql` (SHA-256 `03f65eabdfd4be26ec0a0890c899ca6f2e7f07c691b4da98bf5a4dcf13bbf195`). Browser satu tab membuka permintaan `[TEST]` 1 sak dan menampilkan batch `OPEN-MAT-2` dengan jumlah otomatis 1 sak; pengiriman tidak dikonfirmasi dan tidak ada stok yang berubah.

Snapshot rekonsiliasi kini menyimpan kuantitas dan nilai stok material per gudang, biaya material per rumah, biaya langsung per cluster, saldo alat tersedia/rusak per gudang, serta pinjaman aktif yang terlacak dan tidak terlacak asalnya. Tes MySQL `ImportIntegrityTest`: 13 tes / 59 asersi lulus. Impor browser berlabel `[TEST] Rekonsiliasi Browser 20260925` selesai pada satu tab port 8011; snapshot tersimpan di `logistik_test` dengan kenaikan nilai stok Rp 24.691,34. Ini membuktikan pencatatan snapshot aplikasi; bukan rekonsiliasi workbook perusahaan atau persetujuan saldo awal.

Profil baca-saja workbook kandidat `TESTES.xlsx..xlsx` tercatat di `docs/production-acceptance-checklist.md`: 31.920 baris berkode pada `M.KELUAR`, 5.334 sel penerimaan tidak nol pada `M.MASUK`, tanggal sumber yang tidak konsisten, dan daftar gudang yang tidak mutakhir sebagai hitungan fisik. Folder sumber yang diberikan hanya memuat workbook ini. Tanggal cutover, aturan saldo awal versus rekonstruksi kronologis, pemetaan gudang dan alat, serta hitungan fisik terbaru masih memerlukan keputusan dan sumber perusahaan. Tidak ada data workbook yang diimpor.

Pemeriksaan migrasi menemukan impor transaksi material dan alat dapat memilih rumah dengan pencocokan nama parsial, lalu membuat rumah baru tanpa cluster jika tidak cocok. Kedua impor kini hanya menerima kode/nama rumah yang cocok persis dan unik; kolom Cluster dapat membedakan nama berulang, sedangkan Logistik hanya dapat memakai rumah di cluster tugasnya. Importer tidak membuat rumah dari baris transaksi. `ImportIntegrityTest`, `MaterialImportStockTest`, dan `ExcelImportFeatureTest`: 34 tes / 196 asersi lulus. Browser satu tab mengirim `[TEST] UI Unmapped Material 20260925`; aplikasi menampilkan alasan rumah belum terdaftar, daftar stok tetap kosong, dan tidak ada error console. Data uji berada di `logistik_test`; produksi tidak disentuh.

Impor inventaris material tanpa jenis transaksi kini membuat batch `OPEN-MAT` bertipe saldo awal yang dapat dipilih saat alokasi. Catatan menyebut waktu dan bukti kedatangan tidak tercatat; impor ulang tidak mengisi ulang batch yang sudah habis. Saldo negatif ditolak untuk rekonsiliasi. Impor inventaris alat yang bukan penerimaan nyata memakai kode `ALT-OPEN`. `MaterialImportStockTest` dan `ExcelImportFeatureTest`: 20 tes / 137 asersi lulus bersama; pemeriksaan terbaru `MaterialImportStockTest`: 11 tes / 72 asersi lulus. Pada satu tab browser port 8011, fixture `[TEST] Imported Opening Batch UI 20260925` tampil sebagai Saldo warisan tanpa waktu tiba dan dapat dipilih sebagai sumber alokasi Rp100.000/sak, maksimum 3,5 sak; alokasi tidak disimpan. Database `logistik_test` dipulihkan dari snapshot sebelum pemeriksaan dan `logistik` tidak disentuh. Workbook perusahaan tetap hanya dibaca; cutoff, pemetaan, dan hitungan fisik belum disetujui.

## Skenario penerimaan wajib

| Skenario | Hasil yang harus dibuktikan |
| --- | --- |
| Dua kedatangan identik | Dua kode masuk, saldo dan histori terpisah. |
| Klik simpan dua kali | Satu kejadian tercatat, satu perubahan stok. |
| Stok tidak cukup atau pengiriman bersamaan | Tidak ada saldo negatif atau pemotongan ganda. |
| Satu pengiriman memakai beberapa batch | Sumber setiap baris dapat ditelusuri. |
| Kedatangan lengkap | Semua jumlah dijelaskan dan pengiriman selesai sesuai aturan. |
| Kedatangan parsial | Sisa tetap terbuka; konfirmasi berikutnya menambah histori. |
| Barang diterima rusak | Layak pakai, rusak, dan outstanding dihitung tanpa tumpang tindih. |
| Logistik mengonfirmasi pengiriman sendiri | Pengirim dan pengonfirmasi tercatat sebagai pengguna sebenarnya. |
| Permintaan lintas cluster tanpa hak | Ditolak pada server. |
| Admin menangani cluster berbeda | Diizinkan dan pelakunya tercatat. |
| Pengembalian alat parsial/rusak | Saldo pinjaman dan gudang diperbarui sesuai kondisi dan batch asal. |
| Alat rusak diperbaiki atau dihapus | Jumlah dan kondisi berubah sesuai keputusan; biaya perbaikan dicatat terpisah. |
| Penyelesaian kehilangan atau koreksi | Memerlukan hak yang disepakati dan mempertahankan jejak perubahan. |
| Migrasi catatan lama | Tidak membuat fakta penerimaan yang tidak didukung data. |

## Urutan kerja

G1 dimulai terlebih dahulu. Selesaikan keputusan operasional yang relevan, kemudian G2 dan G3, dilanjutkan G4, G5, dan G6. G7 mengikuti setiap perubahan alur. G8 menjadi syarat penyelesaian implementasi.

## Kriteria tujuan utama tercapai

- [ ] Setiap kedatangan fisik memiliki identitas batch yang permanen.
- [ ] Setiap pengiriman menunjuk batch yang dipilih pengguna.
- [ ] Setiap unit yang dikirim dapat dijelaskan tanpa kehilangan atau penghitungan ganda.
- [ ] Riwayat menunjukkan pelaku, waktu kejadian, waktu pencatatan, dan koreksi.
- [ ] Akses cluster diterapkan konsisten pada semua alur terkait.
- [ ] Laporan membedakan material layak pakai, barang dalam perjalanan, dan kerugian.
- [ ] Data lama tetap dapat ditelusuri dan hasil pengujian terdokumentasi.
