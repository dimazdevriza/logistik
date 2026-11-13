<div class="bg-body-tertiary p-3 rounded-3 mb-3 border">
    <h6 class="fw-bold extra-small text-uppercase text-body-secondary mb-2">Aturan Validasi Impor</h6>
    <ul class="extra-small text-body-secondary mb-0 ps-3 d-flex flex-column gap-1">
        <li><strong>Tanggal:</strong> Wajib format tanggal valid (<code>01 Jan 2024</code>, <code>YYYY-MM-DD</code>, <code>DD/MM/YYYY</code>, atau format tanggal Excel).</li>
        <li><strong>Gudang:</strong> Harus terdaftar dan masuk dalam cakupan gudang Anda (jika hanya ada 1 gudang atau cocok dengan gudang cluster/item, dipilih otomatis).</li>
        <li><strong>Blok Rumah (wajib untuk transaksi keluar):</strong> Harus sudah terdaftar di sistem (mendukung format <code>A1</code> &rarr; <code>Blok A-01</code> / <code>Blok A1</code>, atau <code>-</code> / <code>infra</code>), unik jika tanpa kolom <code>Cluster</code>, dan masuk cakupan cluster Anda.</li>
        <li><strong>Volume &amp; Aturan Stok:</strong> Material keluar/masuk minimal <code>0.01</code>; alat keluar bilangan bulat minimal <code>1</code> dan tidak sedang dipinjam aktif di blok rumah yang sama. Pada template multi-sheet <code>BARANG KELUAR</code> / <code>ALAT KELUAR</code>, jika stok kurang sistem otomatis mencatat stok masuk penutup selisih. Transaksi duplikat dilewati tanpa menggandakan stok.</li>
    </ul>
</div>
