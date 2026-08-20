<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div
            x-data="{
                theme: (function() {
                    try {
                        return localStorage.getItem('theme') || document.documentElement.getAttribute('data-bs-theme') || 'light';
                    } catch(e) {
                        return 'light';
                    }
                })(),
                init() {
                    let current = document.documentElement.getAttribute('data-bs-theme');
                    if (current) { this.theme = current; }
                },
                set(v) {
                    this.theme = v;
                    document.documentElement.setAttribute('data-bs-theme', v);
                    try { localStorage.setItem('theme', v); } catch (e) {}
                },
            }"
            class="vstack gap-3"
        >
            <div class="card border rounded-3 p-3 bg-body-tertiary">
                <span class="d-block font-semibold text-body mb-1">Tema Aplikasi</span>
                <span class="d-block extra-small text-secondary mb-3">Pilih preferensi tema visual untuk antarmuka sistem.</span>

                <div class="btn-group w-100" role="group" aria-label="{{ __('Appearance') }}">
                    <button type="button" class="btn py-2.5 font-semibold d-flex align-items-center justify-content-center gap-2" :class="theme === 'light' ? 'btn-success' : 'btn-outline-secondary'" @click="set('light')">
                        <svg width="16" height="16" fill="currentColor"><use href="#i-sun"/></svg>
                        <span>{{ __('Light') }}</span>
                    </button>
                    <button type="button" class="btn py-2.5 font-semibold d-flex align-items-center justify-content-center gap-2" :class="theme === 'dark' ? 'btn-success' : 'btn-outline-secondary'" @click="set('dark')">
                        <svg width="16" height="16" fill="currentColor"><use href="#i-moon"/></svg>
                        <span>{{ __('Dark') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>
