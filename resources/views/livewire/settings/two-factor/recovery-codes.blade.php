<div
    class="vstack gap-3"
    wire:cloak
    x-data="{ showRecoveryCodes: false }"
>
    <div>
        <h6 class="fw-bold text-body mb-1">Kode Pemulihan Cadangan (Recovery Codes)</h6>
        <p class="text-secondary small mb-0">
            Kode pemulihan dapat digunakan untuk masuk jika Anda kehilangan akses ke perangkat otentikator. Simpan di tempat yang aman.
        </p>
    </div>

    <div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary font-semibold rounded-3"
                x-show="!showRecoveryCodes"
                @click="showRecoveryCodes = true;"
            >
                Tampilkan Kode Pemulihan
            </button>

            <button
                type="button"
                class="btn btn-sm btn-outline-secondary font-semibold rounded-3"
                x-show="showRecoveryCodes"
                @click="showRecoveryCodes = false"
            >
                Sembunyikan Kode
            </button>

            @if (filled($recoveryCodes))
                <button
                    type="button"
                    class="btn btn-sm btn-light border font-semibold rounded-3"
                    x-show="showRecoveryCodes"
                    wire:click="regenerateRecoveryCodes"
                >
                    Buat Ulang Kode (Regenerate)
                </button>
            @endif
        </div>

        <div
            x-show="showRecoveryCodes"
            x-transition
            class="mt-3"
        >
            @error('recoveryCodes')
                <div class="alert alert-danger py-2 px-3 extra-small mb-3">{{ $message }}</div>
            @enderror

            @if (filled($recoveryCodes))
                <div class="card bg-body-tertiary border rounded-3 p-3 font-mono extra-small mb-2">
                    <div class="row g-2">
                        @foreach($recoveryCodes as $code)
                            <div class="col-6 col-sm-4 text-body fw-bold">
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="extra-small text-secondary">
                    Setiap kode hanya dapat digunakan satu kali. Jika kode Anda habis, klik tombol <strong>Buat Ulang Kode</strong>.
                </div>
            @endif
        </div>
    </div>
</div>
