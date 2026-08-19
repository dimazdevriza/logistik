<x-layouts::auth :title="__('Verifikasi Email')">
    <div class="vstack gap-3 text-center my-3">
        <p class="text-secondary small mb-0">
            {{ __('Silakan verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan ke email Anda.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success extra-small rounded-3 mb-0" role="alert">
                {{ __('Tautan verifikasi baru telah dikirimkan ke alamat email Anda.') }}
            </div>
        @endif

        <div class="vstack gap-2 mt-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-success w-100 font-semibold py-2.5 rounded-3 shadow-xs">
                    {{ __('Kirim Ulang Email Verifikasi') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-link text-secondary text-decoration-none extra-small" data-test="logout-button">
                    {{ __('Keluar') }}
                </button>
            </form>
        </div>
    </div>
</x-layouts::auth>
