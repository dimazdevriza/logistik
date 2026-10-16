<div
    class="guide-page container-fluid p-0"
    x-data="{
        search: '',
        roleFilter: 'all',
        viewMode: 'mobile',
        zoomOpen: false,
        zoomTitle: '',
        zoomMobileImg: '',
        zoomDesktopImg: '',
        zoomCaption: '',
        chaptersMeta: @js(collect($chapters)->map(fn ($c) => [
            'id' => $c['id'],
            'roles' => $c['roles'],
            'searchText' => mb_strtolower($c['num'].' '.$c['title'].' '.$c['summary'].' '.$c['note'].' '.implode(' ', array_column($c['steps'], 'title')).' '.implode(' ', array_column($c['steps'], 'desc'))),
        ])->values()),
        matchesChapter(id) {
            const item = this.chaptersMeta.find(c => c.id === id);
            if (!item) return true;
            const roleOk = this.roleFilter === 'all' || item.roles.includes(this.roleFilter);
            const term = this.search.trim().toLowerCase();
            const searchOk = !term || item.searchText.includes(term);
            return roleOk && searchOk;
        },
        get visibleCount() {
            return this.chaptersMeta.filter(c => this.matchesChapter(c.id)).length;
        },
        openZoom(title, mobileImg, desktopImg, caption) {
            this.zoomTitle = title;
            this.zoomMobileImg = mobileImg;
            this.zoomDesktopImg = desktopImg;
            this.zoomCaption = caption;
            this.zoomOpen = true;
        },
        resetFilters() {
            this.search = '';
            this.roleFilter = 'all';
        }
    }"
    @keydown.escape.window="zoomOpen = false"
>
    {{-- Hero Header with D'Royal Village Logo --}}
    <section class="card border-0 shadow-sm rounded-4 mb-4 bg-body-tertiary" aria-labelledby="guide-page-title">
        <div class="card-body p-3 p-md-5">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <div class="guide-brand-badge">
                            <img src="{{ asset('images/logo-light.png') }}" alt="Logo D'Royal Village" width="148" height="36" class="d-dark-none" />
                            <img src="{{ asset('images/logo-dark.png') }}" alt="Logo D'Royal Village" width="148" height="36" class="d-light-none" />
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 extra-small font-mono">
                            8 Bab Operasional
                        </span>
                    </div>
                    <h1 id="guide-page-title" class="display-5 fw-black text-body mb-2 font-outfit">
                        Buku Panduan <span class="text-success">Sistem Logistik</span>
                    </h1>
                    <p class="text-secondary mb-0" style="max-width: 44rem;">
                        Petunjuk langkah demi langkah penggunaan sistem inventaris, alokasi material dan alat, transfer gudang, serta pemantauan biaya proyek D'Royal Village melalui HP maupun komputer.
                    </p>
                </div>

                <div class="d-flex flex-column gap-2 flex-shrink-0">
                    <span class="extra-small fw-bold text-uppercase font-geist text-secondary">Mode Tangkapan Layar</span>
                    <div class="d-flex flex-wrap gap-2" role="group" aria-label="Pilih mode tangkapan layar">
                        <button
                            type="button"
                            class="guide-control-btn flex-fill"
                            :class="{ 'is-active': viewMode === 'mobile' }"
                            :aria-pressed="(viewMode === 'mobile').toString()"
                            @click="viewMode = 'mobile'"
                        >
                            <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-phone"/></svg>
                            <span>Tampilan HP</span>
                        </button>
                        <button
                            type="button"
                            class="guide-control-btn flex-fill"
                            :class="{ 'is-active': viewMode === 'desktop' }"
                            :aria-pressed="(viewMode === 'desktop').toString()"
                            @click="viewMode = 'desktop'"
                        >
                            <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#i-display"/></svg>
                            <span>Layar Lebar</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('logistik.guide.download') }}" class="btn btn-success font-semibold d-inline-flex align-items-center gap-2 shadow-sm" style="min-height: 44px;">
                    <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-download"/></svg>
                    <span>Unduh Buku Panduan PDF A4 (3.3 MB)</span>
                </a>
                <a href="{{ route('logistik.guide.print') }}" target="_blank" class="guide-control-btn d-inline-flex align-items-center gap-2" style="min-height: 44px;">
                    <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-printer"/></svg>
                    <span>Pratinjau / Cetak Format A4</span>
                </a>
            </div>
        </div>
    </section>

    {{-- Search, Role Filter & Table of Contents --}}
    <section class="guide-toolbar-card p-3 p-md-4 mb-4 shadow-sm" aria-label="Pencarian dan daftar isi panduan">
        <div class="row g-3 align-items-start mb-3">
            <div class="col-12 col-lg-5">
                <label for="guide-search-input" class="form-label small fw-bold text-uppercase font-geist text-secondary mb-1">
                    Cari Topik atau Menu Kerja
                </label>
                <div class="d-flex gap-2">
                    <input
                        id="guide-search-input"
                        type="search"
                        x-model="search"
                        class="form-control"
                        style="min-height: 44px;"
                        placeholder="Cari: stok masuk, alokasi, garansi..."
                    />
                    <button
                        type="button"
                        x-show="search.length > 0"
                        x-cloak
                        @click="search = ''"
                        class="guide-control-btn flex-shrink-0"
                        aria-label="Hapus kata kunci pencarian"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <span class="d-block form-label small fw-bold text-uppercase font-geist text-secondary mb-1">
                    Saring Sesuai Peran Tugas
                </span>
                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Saring bab berdasarkan peran">
                    @foreach ([
                        ['key' => 'all', 'label' => 'Semua Peran'],
                        ['key' => 'Logistik', 'label' => 'Logistik'],
                        ['key' => 'Pengawas', 'label' => 'Pengawas'],
                        ['key' => 'Keuangan', 'label' => 'Keuangan'],
                        ['key' => 'Admin', 'label' => 'Admin'],
                    ] as $roleOption)
                        <button
                            type="button"
                            class="guide-control-btn"
                            :class="{ 'is-active': roleFilter === '{{ $roleOption['key'] }}' }"
                            :aria-pressed="(roleFilter === '{{ $roleOption['key'] }}').toString()"
                            @click="roleFilter = '{{ $roleOption['key'] }}'"
                        >
                            {{ $roleOption['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="pt-3 border-top">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <span class="extra-small fw-bold text-uppercase font-geist text-secondary">Daftar Isi Cepat (Ketuk untuk Lompat ke Bab)</span>
                <span class="extra-small font-mono text-secondary" x-text="visibleCount + ' dari {{ count($chapters) }} bab ditampilkan'"></span>
            </div>
            <nav class="guide-toc-grid" aria-label="Daftar isi bab panduan">
                @foreach ($chapters as $chapter)
                    <a
                        href="#{{ $chapter['id'] }}"
                        class="guide-toc-link"
                        x-show="matchesChapter('{{ $chapter['id'] }}')"
                    >
                        <span class="guide-toc-num">{{ $chapter['num'] }}</span>
                        <span>{{ $chapter['title'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    {{-- Honest Empty State when filter/search returns 0 chapters --}}
    <div
        x-show="visibleCount === 0"
        x-cloak
        class="card border rounded-4 p-4 p-md-5 text-center bg-body-tertiary mb-4"
        role="status"
    >
        <div class="mx-auto mb-3 p-3 rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <svg width="22" height="22" fill="currentColor" aria-hidden="true"><use href="#i-search"/></svg>
        </div>
        <h2 class="h5 fw-bold text-body font-outfit mb-2">Bab panduan tidak ditemukan</h2>
        <p class="text-secondary small mb-3 mx-auto" style="max-width: 32rem;">
            Tidak ada bab panduan yang cocok dengan kata kunci atau filter peran yang dipilih. Silakan hapus kata kunci atau tampilkan semua peran.
        </p>
        <div>
            <button type="button" @click="resetFilters()" class="btn btn-success font-semibold px-4" style="min-height: 44px;">
                Tampilkan Semua Bab Panduan
            </button>
        </div>
    </div>

    {{-- Guide Chapters --}}
    <div class="d-flex flex-column gap-4 pb-4">
        @foreach ($chapters as $chapter)
            <article
                id="{{ $chapter['id'] }}"
                class="guide-chapter-card shadow-sm"
                x-show="matchesChapter('{{ $chapter['id'] }}')"
                aria-labelledby="heading-{{ $chapter['id'] }}"
            >
                <header class="guide-chapter-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <span class="guide-chapter-index" aria-hidden="true">{{ $chapter['num'] }}</span>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-1.5 mb-1">
                                @foreach ($chapter['roles'] as $roleBadge)
                                    <span class="badge bg-body border text-body extra-small font-mono px-2 py-1 rounded-2">{{ $roleBadge }}</span>
                                @endforeach
                            </div>
                            <h2 id="heading-{{ $chapter['id'] }}" class="h4 fw-bold text-body font-outfit mb-1">
                                {{ $chapter['title'] }}
                            </h2>
                            <p class="text-secondary small mb-0">{{ $chapter['summary'] }}</p>
                        </div>
                    </div>

                    @auth
                        <div class="flex-shrink-0">
                            <a
                                href="{{ route($chapter['route']) }}"
                                wire:navigate
                                class="guide-control-btn w-100"
                            >
                                <svg width="15" height="15" fill="currentColor" aria-hidden="true"><use href="#{{ $chapter['icon'] }}"/></svg>
                                <span>{{ $chapter['route_label'] }}</span>
                            </a>
                        </div>
                    @endauth
                </header>

                <div class="p-3 p-md-4">
                    <div class="row g-4 align-items-start">
                        {{-- Left Column: Operational Steps & Field Note --}}
                        <div class="col-12 col-xl-6">
                            <h3 class="extra-small fw-bold text-uppercase font-geist text-secondary mb-3">
                                Langkah Kerja Operasional
                            </h3>

                            <ol class="guide-step-list mb-3">
                                @foreach ($chapter['steps'] as $idx => $step)
                                    <li class="guide-step-item">
                                        <span class="guide-step-badge" aria-hidden="true">{{ $idx + 1 }}</span>
                                        <div>
                                            <div class="fw-bold text-body mb-1">{{ $step['title'] }}</div>
                                            <p class="text-secondary small mb-0" style="line-height: 1.55;">{{ $step['desc'] }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>

                            <div class="guide-field-note d-flex align-items-start gap-2.5">
                                <span class="text-success flex-shrink-0 mt-0.5">
                                    <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-info"/></svg>
                                </span>
                                <div class="small text-body" style="line-height: 1.5;">
                                    <strong class="d-block mb-0.5 font-outfit">Catatan Operasional:</strong>
                                    <span class="text-secondary">{{ $chapter['note'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: Actual System Screenshots --}}
                        <div class="col-12 col-xl-6">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <span class="extra-small fw-bold text-uppercase font-geist text-secondary">
                                    Tangkapan Layar Sistem Asli
                                </span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle extra-small font-mono" x-text="viewMode === 'mobile' ? 'Tampilan HP' : 'Tampilan Layar Lebar'"></span>
                            </div>

                            <div class="guide-screenshot-box">
                                <div class="guide-screenshot-stage">
                                    <button
                                        type="button"
                                        class="guide-screenshot-btn"
                                        @click="openZoom(@js($chapter['title']), '{{ asset($chapter['mobile_img']) }}', '{{ asset($chapter['desktop_img']) }}', viewMode === 'mobile' ? @js($chapter['mobile_caption']) : @js($chapter['desktop_caption']))"
                                        aria-label="Perbesar tangkapan layar {{ $chapter['title'] }}"
                                    >
                                        <img
                                            x-show="viewMode === 'mobile'"
                                            src="{{ asset($chapter['mobile_img']) }}"
                                            alt="{{ $chapter['mobile_alt'] }}"
                                            width="412"
                                            height="820"
                                            class="guide-screenshot-img guide-screenshot-img--mobile"
                                            loading="lazy"
                                        />
                                        <img
                                            x-show="viewMode === 'desktop'"
                                            x-cloak
                                            src="{{ asset($chapter['desktop_img']) }}"
                                            alt="{{ $chapter['desktop_alt'] }}"
                                            width="1280"
                                            height="800"
                                            class="guide-screenshot-img"
                                            loading="lazy"
                                        />
                                    </button>
                                </div>

                                <div class="guide-screenshot-caption">
                                    <p class="extra-small text-secondary mb-0" style="flex: 1 1 14rem;" x-text="viewMode === 'mobile' ? @js($chapter['mobile_caption']) : @js($chapter['desktop_caption'])"></p>
                                    <button
                                        type="button"
                                        class="guide-control-btn flex-shrink-0"
                                        @click="openZoom(@js($chapter['title']), '{{ asset($chapter['mobile_img']) }}', '{{ asset($chapter['desktop_img']) }}', viewMode === 'mobile' ? @js($chapter['mobile_caption']) : @js($chapter['desktop_caption']))"
                                    >
                                        <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-zoom-in"/></svg>
                                        <span>Perbesar Gambar</span>
                                    </button>
                                </div>
                            </div>

                            @if (!empty($chapter['mobile_img_secondary']))
                                <div class="guide-screenshot-box mt-3" x-show="viewMode === 'mobile'">
                                    <div class="guide-screenshot-stage">
                                        <button
                                            type="button"
                                            class="guide-screenshot-btn"
                                            @click="openZoom(@js($chapter['title'].' (Ringkasan Dashboard HP)'), '{{ asset($chapter['mobile_img_secondary']) }}', '{{ asset($chapter['desktop_img']) }}', @js($chapter['mobile_caption_secondary']))"
                                            aria-label="Perbesar tangkapan layar ringkasan dashboard HP"
                                        >
                                            <img
                                                src="{{ asset($chapter['mobile_img_secondary']) }}"
                                                alt="Tampilan ringkasan Dashboard pada layar HP"
                                                width="412"
                                                height="820"
                                                class="guide-screenshot-img guide-screenshot-img--mobile"
                                                loading="lazy"
                                            />
                                        </button>
                                    </div>
                                    <div class="guide-screenshot-caption">
                                        <p class="extra-small text-secondary mb-0" style="flex: 1 1 14rem;">{{ $chapter['mobile_caption_secondary'] }}</p>
                                        <button
                                            type="button"
                                            class="guide-control-btn flex-shrink-0"
                                            @click="openZoom(@js($chapter['title'].' (Ringkasan Dashboard HP)'), '{{ asset($chapter['mobile_img_secondary']) }}', '{{ asset($chapter['desktop_img']) }}', @js($chapter['mobile_caption_secondary']))"
                                        >
                                            <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-zoom-in"/></svg>
                                            <span>Perbesar Gambar</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Accessible Screenshot Lightbox Modal --}}
    <template x-teleport="body">
        <div
            x-show="zoomOpen"
            x-cloak
            class="guide-zoom-backdrop"
            role="dialog"
            aria-modal="true"
            :aria-label="'Pratinjau gambar: ' + zoomTitle"
            @click.self="zoomOpen = false"
        >
            <div class="guide-zoom-dialog shadow-lg">
                <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2 bg-body-tertiary">
                    <div class="d-flex align-items-center gap-2">
                        <div class="guide-brand-badge py-1 px-2">
                            <img src="{{ asset('images/logo-light.png') }}" alt="D'Royal Village" class="d-dark-none" style="height: 22px;" />
                            <img src="{{ asset('images/logo-dark.png') }}" alt="D'Royal Village" class="d-light-none" style="height: 22px;" />
                        </div>
                        <strong class="font-outfit text-body small" x-text="zoomTitle"></strong>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button
                            type="button"
                            class="guide-control-btn"
                            :class="{ 'is-active': viewMode === 'mobile' }"
                            @click="viewMode = 'mobile'"
                        >
                            <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-phone"/></svg>
                            <span>HP</span>
                        </button>
                        <button
                            type="button"
                            class="guide-control-btn"
                            :class="{ 'is-active': viewMode === 'desktop' }"
                            @click="viewMode = 'desktop'"
                        >
                            <svg width="14" height="14" fill="currentColor" aria-hidden="true"><use href="#i-display"/></svg>
                            <span>Desktop</span>
                        </button>
                        <button
                            type="button"
                            class="guide-control-btn"
                            @click="zoomOpen = false"
                            aria-label="Tutup pratinjau gambar"
                        >
                            <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="#i-x"/></svg>
                            <span>Tutup</span>
                        </button>
                    </div>
                </div>
                <div class="guide-zoom-body">
                    <img
                        :src="viewMode === 'mobile' ? zoomMobileImg : zoomDesktopImg"
                        :alt="zoomTitle"
                    />
                </div>
                <div class="p-2.5 px-3 border-top bg-body-tertiary text-secondary extra-small" x-text="zoomCaption"></div>
            </div>
        </div>
    </template>
</div>
