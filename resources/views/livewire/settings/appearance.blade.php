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
            class="btn-group w-100"
            role="group"
            aria-label="{{ __('Appearance') }}"
        >
            <button type="button" class="btn py-2 fw-semibold" :class="theme === 'light' ? 'btn-success' : 'btn-outline-secondary'" @click="set('light')">
                {{ __('Light') }}
            </button>
            <button type="button" class="btn py-2 fw-semibold" :class="theme === 'dark' ? 'btn-success' : 'btn-outline-secondary'" @click="set('dark')">
                {{ __('Dark') }}
            </button>
        </div>
    </x-settings.layout>
</section>
