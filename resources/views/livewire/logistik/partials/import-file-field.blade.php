<div class="mb-3">
    <label class="form-label fw-semibold">Pilih Berkas Excel / CSV</label>
    <input type="file" wire:model="importFile" class="form-control @error('importFile') is-invalid @enderror" accept=".xlsx,.xls,.csv" />
    <div class="form-text extra-small text-body-secondary mt-1">
        <code>.xlsx</code> / <code>.xls</code> / <code>.csv</code> &le; 10 MB
    </div>
    @error('importFile')
        <div class="alert alert-danger d-flex align-items-start gap-2 mt-2 mb-0 py-2 px-3 small" role="alert">
            <svg width="16" height="16" fill="currentColor" class="flex-shrink-0 mt-1" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            <div>
                <div class="fw-bold">Validasi Berkas Excel Gagal</div>
                <div>{{ $message }}</div>
                <div class="extra-small opacity-75 mt-1">Periksa dan perbaiki baris terkait pada berkas Excel Anda lalu unggah kembali.</div>
            </div>
        </div>
    @enderror
</div>

<div wire:loading wire:target="importFile" class="text-primary small fw-semibold">
    <div class="spinner-border spinner-border-sm me-1" role="status"></div> Mengunggah berkas...
</div>
