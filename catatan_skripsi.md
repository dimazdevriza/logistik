# Catatan Skripsi (Thesis Devlog)

File ini digunakan oleh Roger (AI) untuk mencatat setiap masalah, solusi, dan breakthrough utama selama proses pengembangan **Sistem Informasi Logistik D'Royal Village**. Catatan ini sangat krusial untuk keperluan penulisan Skripsi, terutama untuk Bab 4 (Implementasi dan Pengujian).

## 1. Concurrency Vulnerability (Time-Of-Check to Time-Of-Use / TOCTOU)
- **Tanggal**: 28 Maret 2026
- **Konteks**: Modul Inventaris (`Materials.php` & `ToolUsage.php`).
- **Masalah (The Problem)**: Pengurangan dan penambahan stok material serta stok alat (`available_qty`) memvalidasi data menggunakan memori PHP (snapshot) di luar mekanisme Transaction database. Jika terjadi akses paralel (Concurrent Requests), terjadi balapan data (Race Condition), menyebabkan stok bisa minus (negatif) atau terjadi anomali data stock update (Ghost Updates).
- **Solusi (The Breakthrough)**: Mengimplementasikan **Pessimistic Locking** tingkat database (`SELECT ... FOR UPDATE`). Fungsi `lockForUpdate()` dan seluruh logika kalkulasi murni dibungkus secara absolut di dalam closure `DB::transaction()`.
- **Hasil**: Sistem sekarang aman dari inkonsistensi data inventaris, bahkan di bawah simulasi beban operasional tinggi. Database secara antrean memblokir transaksi hingga transaksi pertama selesai.

## 2. Real-Time Currency Formatting (Reactive UI)
- **Konteks**: Input form harga pada `materials.blade.php`.
- **Masalah**: Framework Livewire cenderung kaku ketika menangani input angka rupiah (e.g., `Rp1.000.000`) karena Livewire model membutuhkan integer murni tanpa titik atau huruf, menyebabkan user kesulitan saat mengetik nominal harga beli material secara live.
- **Solusi**: Mengawinkan Livewire dengan **Alpine.js** (`x-model`). Dibuat arsitektur di mana Alpine.js mencegat input pengguna secara *real-time*, memformatnya dengan *Thousands Separator* (`Intl.NumberFormat` / Regex Masking), memberikan feedback UI instan (`Rp1.000.000`) kepada user, namun dalam layar belakang secara halus membersihkan string tersebut menjadi raw integer dan mengentangkannya (`$wire.entangle` / manual set) ke Livewire state.
- **Hasil**: UX aplikasi menjadi setingkat *Enterprise* tanpa membebani performa server atau memecahkan validasi backend.

## 3. Livewire Pagination State Hydration Bug
- **Konteks**: Semua tabel datatable (Material, Alat, Supplier).
- **Masalah**: Menambahkan komponen pagination bawaan (`$items->links()`) membuat tombol halaman (Contoh: Halaman 2, Next) menjadi tautan mati (dead links) atau mereload seluruh halaman menyebabkan hilangnya `state` dari input pencarian.
- **Solusi**: Ditemukan bahwa Livewire versi terbaru membutuhkan deklarasi eksplisit trait `Livewire\WithPagination`. Roger (AI) membuat sebuah *script injeksi* khusus yang mengaudit 10 file komponen dan menyematkan trait ini secara otomatis.
- **Hasil**: Transisi tabel terjadi seperti Single Page Application (SPA), sangat instan tanpa *flicker* halaman.

## 4. Historical Cost Data Integrity & Stale Models
- **Konteks**: Modul `MaterialUsage.php` (Fungsi Edit/Update dan Delete).
- **Masalah**: Terdeteksi 3 celah (logical flaws) dalam skenario modifikasi data historis: 1) Saat user mengubah kuantitas penggunaan material, sistem secara otomatis me-*rewrite* harga historis material tersebut dengan harga master data hari ini (`newMaterial->unit_price`), merusak nilai laporan awal rumah. 2) Saat stok model di-`increment` ke dalam database, snapshot instance PHP menjadi basi (stale) dan menyebabkan penolakan editan yang sesungguhnya valid. 3) Fitur `delete()` berjalan tanpa proteksi Transaksi ACID.
- **Solusi**: 1) Diimplementasikan operator percabangan perlindungan: Jika material ID tidak di-*swap* (sama), sistem memaksa penggunaan `unit_price_at_usage` yang lama. 2) Menyuntikkan sinkronisasi `$oldMaterial->refresh()` di memori PHP tepat setelah interaksi SQL increment selesai. 3) Membungkus hapus data dan integrasi stok pengembalian ke dalam `DB::transaction()` tunggal.
- **Hasil**: Log Biaya Rencana Anggaran (RAB) dan biaya bangun actual proyek dipastikan "beku" dan absolut sejak detik transaksi pertama, mencegah bahaya audit laporan operasional secara permanen.

## 5. Logic Drift & Capacity Ceiling Guards (Tooling Module)
- **Konteks**: Modul Peminjaman Alat `ToolUsage.php` (Fitur Edit Swap, Return, dan Delete).
- **Masalah**: 1) **Tool Swap Bug**: Jika admin memodifikasi log peminjaman dan secara fisik mengganti (swap) objek alat (misal: dari Tool A ke Tool B), sistem secara keliru merestorasi kuantitas alat lama ke dalam ID alat baru, menyebabkan tool A bocor tak terbatas dan tool B menggelembung (*Ghost Quantities*). 2) **No Ceiling Guard**: Fungsi `returnTool()` dan `delete()` dapat me-`increment` nilai `available_qty` melampaui nilai absolute kapasitas fisik gudang (`total_qty`) jika terjadi anomali drift.
- **Solusi**: 1) Membangun logika *Double-Locking* independen yang secara terpisah menargetkan relasi `oldToolId` untuk direstorasi, dan secara asinkron mengevaluasi kelayakan potong pada `newToolId`. 2) Menginjeksi *Ceiling Guard* menggunakan kalkulasi `min(available + quantity, total_qty)` menjamin stok gudang tidak pernah melampaui kapasitas 100%.
- **Hasil**: Alur peminjaman alat (`checkout/return`) kebal dari rekayasa bug UI maupun anomali perhitungan, menjamin jumlah sisa alat di inventaris memiliki validitas absolut terhadap barang fisik gudang.

## 6. Deterministic String Collisions (House Generation)
- **Konteks**: Modul Proyek `Houses.php` (Fitur Auto-Generate `house_code`).
- **Masalah**: Arsitektur pembuatan kode unik rumah ternyata menggunakan pola deterministik murni (e.g., Nama + Tahun) tanpa *Sequence Database Counter*. Hal ini menimbulkan celah tabrakan (*Collision*): Jika input nama rumah identik pada sistem, maka duplikasi kode akan tercipta secara instan, merusak struktur unik database. Ditambah lagi, kode otomatis tersebut terus me-regenerate nilainya setiap kali fungsi `Edit` dipanggil.
- **Solusi**: 1) Menutup celah re-generation pada alur `Update` (Kode rumah bersifat statis seumur hidup setelah diciptakan). 2) Mengeksekusi manual eksistensi kode (*Pre-save DB Existence Check*) pada alur `Create` untuk mendeteksi *Collision* sesaat sebelum operasi insert. 3) Memanfaatkan struktur tabel MySQL `->unique('house_code')` sebagai penjaga gawang absolut.
- **Hasil**: Operasional kode rumah menjadi aman dari insiden duplikasi input petugas human error.
