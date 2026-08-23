<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Tema & Tampilan')" :subheading="__('Pilih mode tampilan yang nyaman untuk mata Anda.')">
        <div
            x-data="{
                theme: document.documentElement.getAttribute('data-bs-theme') || localStorage.getItem('theme') || 'light',
                init() {
                    this.syncTheme();
                    window.addEventListener('storage', () => this.syncTheme());
                    document.addEventListener('livewire:navigated', () => this.syncTheme());
                },
                syncTheme() {
                    this.theme = document.documentElement.getAttribute('data-bs-theme') || localStorage.getItem('theme') || 'light';
                },
                set(v) {
                    this.theme = v;
                    document.documentElement.setAttribute('data-bs-theme', v);
                    try { localStorage.setItem('theme', v); } catch (e) {}
                    window.dispatchEvent(new CustomEvent('theme-changed', { detail: v }));
                },
            }"
            class="vstack gap-4"
        >
            <div class="row g-4">
                <div class="col-md-6">
                    <div
                        class="card rounded-4 p-4 p-lg-5 cursor-pointer transition-all border-2 text-start h-100 position-relative"
                        :class="theme === 'light' ? 'border-success bg-success-subtle bg-opacity-25 shadow-sm' : 'border-secondary border-opacity-25 bg-body hover-bg'"
                        @click="set('light')"
                        style="cursor: pointer;"
                    >
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center shadow-xs" style="width: 52px; height: 52px;">
                                <svg width="24" height="24" fill="currentColor"><use href="#i-sun"/></svg>
                            </div>
                            <span class="badge rounded-pill px-3 py-1.5 font-geist" :class="theme === 'light' ? 'bg-success text-white' : 'bg-body-secondary text-secondary'">
                                <span x-text="theme === 'light' ? 'Aktif' : 'Pilih'"></span>
                            </span>
                        </div>
                        <h5 class="fw-bold text-body font-outfit mb-2">{{ __('Mode Terang') }}</h5>
                        <p class="text-secondary small mb-0 lh-base">Antarmuka putih bersih dengan kontras tinggi, ideal untuk lingkungan kerja siang hari.</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div
                        class="card rounded-4 p-4 p-lg-5 cursor-pointer transition-all border-2 text-start h-100 position-relative"
                        :class="theme === 'dark' ? 'border-success bg-success-subtle bg-opacity-25 shadow-sm' : 'border-secondary border-opacity-25 bg-body hover-bg'"
                        @click="set('dark')"
                        style="cursor: pointer;"
                    >
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center shadow-xs" style="width: 52px; height: 52px;">
                                <svg width="24" height="24" fill="currentColor"><use href="#i-moon"/></svg>
                            </div>
                            <span class="badge rounded-pill px-3 py-1.5 font-geist" :class="theme === 'dark' ? 'bg-success text-white' : 'bg-body-secondary text-secondary'">
                                <span x-text="theme === 'dark' ? 'Aktif' : 'Pilih'"></span>
                            </span>
                        </div>
                        <h5 class="fw-bold text-body font-outfit mb-2">{{ __('Mode Gelap') }}</h5>
                        <p class="text-secondary small mb-0 lh-base">Antarmuka gelap elegan yang nyaman di mata pada malam hari dan menghemat baterai.</p>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>
