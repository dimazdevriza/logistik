<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div
            x-data="{
                theme: document.documentElement.getAttribute('data-bs-theme'),
                set(v) {
                    this.theme = v
                    document.documentElement.setAttribute('data-bs-theme', v)
                    try { localStorage.setItem('theme', v) } catch (e) {}
                },
            }"
            class="vstack gap-3"
        >
            <div class="d-flex align-items-center justify-content-between p-3 border rounded-3 bg-body">
                <div>
                    <span class="d-block font-semibold text-body">Mode Gelap / Theme Toggle</span>
                    <span class="d-block extra-small text-secondary">Pilih mode tampilan aplikasi (Terang atau Gelap).</span>
                </div>
                <div class="form-check form-switch fs-4 mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" :checked="theme === 'dark'" style="cursor: pointer;" @change="set(theme === 'dark' ? 'light' : 'dark')">
                </div>
            </div>

            <div class="btn-group w-100" role="group" aria-label="{{ __('Appearance') }}">
                <button type="button" class="btn py-2 font-semibold" :class="theme === 'light' ? 'btn-success' : 'btn-outline-secondary'" @click="set('light')">
                    {{ __('Light') }}
                </button>
                <button type="button" class="btn py-2 font-semibold" :class="theme === 'dark' ? 'btn-success' : 'btn-outline-secondary'" @click="set('dark')">
                    {{ __('Dark') }}
                </button>
            </div>
        </div>
    </x-settings.layout>
</section>
