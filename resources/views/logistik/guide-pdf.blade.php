<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buku Panduan Sistem Logistik D'Royal Village (A4)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-green: #173f2a;
            --brand-accent: #1f9b3a;
            --brand-accent-soft: rgba(31, 155, 58, 0.1);
            --brand-text: #17202a;
            --brand-muted: #596573;
            --brand-border: #dbe2ea;
            --brand-bg: #f8fafc;
            --brand-card-bg: #ffffff;
            --brand-gold: #b38600;
        }

        @page {
            size: A4 portrait;
            margin: 12mm 14mm 14mm 14mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--brand-text);
            background-color: #ffffff;
            line-height: 1.5;
            font-size: 9.5pt;
        }

        /* Screen Toolbar */
        .screen-toolbar {
            position: sticky;
            top: 0;
            z-index: 9999;
            background: #173f2a;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .screen-toolbar a,
        .screen-toolbar button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-toolbar-back {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .btn-toolbar-back:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .btn-toolbar-print {
            background: #1f9b3a;
            color: #ffffff;
        }

        .btn-toolbar-print:hover {
            background: #25b745;
        }

        @media print {
            .screen-toolbar {
                display: none !important;
            }
            body {
                background: #ffffff;
            }
        }

        /* Document Container */
        .pdf-document {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
        }

        @media screen {
            .pdf-document {
                padding: 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                margin-top: 15px;
                margin-bottom: 30px;
            }
            .pdf-page {
                box-shadow: 0 0 10px rgba(0,0,0,0.06);
                border: 1px solid #e2e8f0;
                margin-bottom: 24px;
                padding: 14mm 16mm;
            }
        }

        /* Page Breaks */
        .page-break {
            page-break-before: always;
            break-before: page;
        }

        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Cover Page */
        .cover-page {
            min-height: 260mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 8mm 4mm;
            border: 2px solid var(--brand-green);
            border-radius: 8px;
            position: relative;
        }

        .cover-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid var(--brand-green);
            padding-bottom: 14px;
        }

        .cover-logo {
            height: 48px;
            width: auto;
            object-fit: contain;
        }

        .cover-doc-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 8pt;
            font-weight: 600;
            background: var(--brand-green);
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        .cover-main {
            margin: 30mm 0 20mm 0;
        }

        .cover-eyebrow {
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--brand-accent);
            margin-bottom: 8px;
        }

        .cover-title {
            font-family: 'Outfit', sans-serif;
            font-size: 26pt;
            font-weight: 800;
            line-height: 1.15;
            color: var(--brand-green);
            margin: 0 0 14px 0;
        }

        .cover-title span {
            color: var(--brand-accent);
        }

        .cover-subtitle {
            font-size: 11pt;
            color: var(--brand-muted);
            max-width: 160mm;
            line-height: 1.5;
            margin: 0 0 24px 0;
        }

        .cover-divider {
            width: 60mm;
            height: 4px;
            background: var(--brand-accent);
            margin-bottom: 24px;
            border-radius: 2px;
        }

        .cover-feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15mm;
        }

        .cover-feature-item {
            background: var(--brand-bg);
            border: 1px solid var(--brand-border);
            border-radius: 6px;
            padding: 12px 14px;
        }

        .cover-feature-num {
            font-family: 'Outfit', sans-serif;
            font-size: 16pt;
            font-weight: 800;
            color: var(--brand-accent);
            line-height: 1;
            margin-bottom: 4px;
        }

        .cover-feature-title {
            font-size: 8.5pt;
            font-weight: 700;
            color: var(--brand-green);
            margin-bottom: 2px;
        }

        .cover-feature-desc {
            font-size: 7.5pt;
            color: var(--brand-muted);
            line-height: 1.35;
        }

        .cover-footer {
            border-top: 1px solid var(--brand-border);
            padding-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 8pt;
            color: var(--brand-muted);
        }

        .cover-meta-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 4px 12px;
            font-size: 8pt;
        }

        .cover-meta-label {
            font-weight: 600;
            color: var(--brand-green);
        }

        .cover-roles-box {
            background: var(--brand-bg);
            border: 1px solid var(--brand-border);
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 7.5pt;
            max-width: 85mm;
        }

        .cover-roles-box strong {
            color: var(--brand-green);
            display: block;
            margin-bottom: 2px;
        }

        /* Section Headings */
        .doc-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid var(--brand-green);
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .doc-section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16pt;
            font-weight: 700;
            color: var(--brand-green);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .doc-section-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 8pt;
            background: var(--brand-accent-soft);
            color: var(--brand-accent);
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        /* TOC Grid */
        .toc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 8.5pt;
        }

        .toc-table th {
            background: var(--brand-green);
            color: #ffffff;
            font-weight: 600;
            text-align: left;
            padding: 6px 10px;
            font-size: 8pt;
        }

        .toc-table td {
            padding: 7px 10px;
            border-bottom: 1px solid var(--brand-border);
            vertical-align: middle;
        }

        .toc-table tr:nth-child(even) td {
            background: #fafbfc;
        }

        .toc-num {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: var(--brand-accent);
            font-size: 8.5pt;
        }

        .toc-title {
            font-weight: 700;
            color: var(--brand-green);
        }

        .toc-desc {
            font-size: 7.5pt;
            color: var(--brand-muted);
            margin-top: 1px;
        }

        .role-pill {
            display: inline-block;
            font-size: 7pt;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 3px;
            background: #eef2f6;
            color: #334155;
            margin-right: 2px;
            margin-bottom: 2px;
            white-space: nowrap;
        }

        .role-pill.role-logistik { background: #e0f2fe; color: #0369a1; }
        .role-pill.role-pengawas { background: #fef3c7; color: #92400e; }
        .role-pill.role-keuangan { background: #dcfce7; color: #166534; }
        .role-pill.role-admin { background: #f3e8ff; color: #7e22ce; }

        /* Role Matrix Table */
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 8pt;
        }

        .matrix-table th {
            background: var(--brand-bg);
            border: 1px solid var(--brand-border);
            color: var(--brand-green);
            padding: 6px 10px;
            text-align: left;
            font-weight: 700;
        }

        .matrix-table td {
            border: 1px solid var(--brand-border);
            padding: 6px 10px;
            vertical-align: top;
        }

        /* Chapter Detail Layout */
        .chapter-box {
            margin-bottom: 14px;
        }

        .chapter-top-bar {
            background: var(--brand-green);
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 6px 6px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chapter-badge-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chapter-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 9pt;
            font-weight: 700;
            background: var(--brand-accent);
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .chapter-heading-title {
            font-family: 'Outfit', sans-serif;
            font-size: 13pt;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
        }

        .chapter-body {
            border: 1px solid var(--brand-border);
            border-top: none;
            padding: 12px 14px;
            background: #ffffff;
            border-radius: 0 0 6px 6px;
        }

        .chapter-summary {
            font-size: 8.5pt;
            color: var(--brand-muted);
            margin: 0 0 10px 0;
            line-height: 1.45;
        }

        /* Screenshots Layout for Print */
        .screenshots-layout {
            display: grid;
            grid-template-columns: 1.65fr 1fr;
            gap: 10px;
            margin-bottom: 12px;
            background: var(--brand-bg);
            border: 1px solid var(--brand-border);
            border-radius: 6px;
            padding: 8px;
        }

        .ss-frame {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .ss-frame-header {
            background: #f1f5f9;
            padding: 4px 8px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 7pt;
            font-weight: 600;
            color: #475569;
        }

        .ss-dots {
            display: flex;
            gap: 3px;
        }

        .ss-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .ss-img-container {
            width: 100%;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ss-img-container img {
            width: 100%;
            height: auto;
            max-height: 185px;
            object-fit: cover;
            object-position: top;
            display: block;
        }

        .ss-caption {
            font-size: 6.8pt;
            color: var(--brand-muted);
            padding: 4px 6px;
            background: #ffffff;
            border-top: 1px solid #f1f5f9;
            line-height: 1.3;
        }

        /* Steps Layout */
        .steps-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
            margin-bottom: 10px;
        }

        .step-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fafbfc;
            border: 1px solid var(--brand-border);
            border-radius: 5px;
            padding: 7px 10px;
        }

        .step-num-badge {
            flex-shrink: 0;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--brand-green);
            color: #ffffff;
            font-weight: 700;
            font-size: 8pt;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .step-content {
            flex: 1;
        }

        .step-heading {
            font-size: 8.5pt;
            font-weight: 700;
            color: var(--brand-green);
            margin: 0 0 2px 0;
        }

        .step-desc {
            font-size: 7.8pt;
            color: #334155;
            margin: 0;
            line-height: 1.4;
        }

        /* Note Callout */
        .chapter-note-box {
            background: #fffbeb;
            border-left: 3px solid #d97706;
            border-top: 1px solid #fef3c7;
            border-right: 1px solid #fef3c7;
            border-bottom: 1px solid #fef3c7;
            padding: 6px 10px;
            border-radius: 0 4px 4px 0;
            font-size: 7.5pt;
            line-height: 1.4;
            color: #92400e;
        }

        .chapter-note-box strong {
            color: #78350f;
            font-weight: 700;
        }

        /* Page Running Header & Footer */
        .doc-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--brand-border);
            padding-bottom: 5px;
            margin-bottom: 12px;
            font-size: 7.5pt;
            color: var(--brand-muted);
        }

        .doc-page-header .brand-title {
            font-weight: 700;
            color: var(--brand-green);
        }

        .doc-page-footer {
            border-top: 1px solid var(--brand-border);
            padding-top: 5px;
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 7.5pt;
            color: var(--brand-muted);
        }

        /* FAQ Box */
        .faq-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .faq-item {
            background: var(--brand-bg);
            border: 1px solid var(--brand-border);
            border-radius: 6px;
            padding: 10px 12px;
        }

        .faq-q {
            font-weight: 700;
            font-size: 8.5pt;
            color: var(--brand-green);
            margin: 0 0 4px 0;
        }

        .faq-a {
            font-size: 7.8pt;
            color: #334155;
            margin: 0;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <!-- On-screen Interactive Navigation / Print Bar -->
    <div class="screen-toolbar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="{{ route('logistik.guide') }}" class="btn-toolbar-back">
                &larr; Kembali ke Panduan Web Interaktif
            </a>
            <span style="font-size: 13px; opacity: 0.85;">
                Pratinjau Cetak A4 Sistem Logistik D'Royal Village
            </span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('logistik.guide.download') }}" class="btn-toolbar-back" style="background: rgba(255,255,255,0.2);">
                Unduh PDF Resmi (.pdf)
            </a>
            <button onclick="window.print()" class="btn-toolbar-print">
                Cetak Dokumen (Ctrl + P)
            </button>
        </div>
    </div>

    <div class="pdf-document">

        <!-- ========================================== -->
        <!-- HALAMAN 1: COVER / HALAMAN SAMPUL RESMI   -->
        <!-- ========================================== -->
        <div class="pdf-page cover-page">
            <div class="cover-header">
                <img src="{{ asset('images/logo-dark.png') }}" alt="Logo D'Royal Village" class="cover-logo">
                <div class="cover-doc-badge">DOKUMEN RESMI OPERASIONAL</div>
            </div>

            <div class="cover-main">
                <div class="cover-eyebrow">D'ROYAL VILLAGE &bull; SISTEM MANAJEMEN LOGISTIK &amp; PROYEK</div>
                <h1 class="cover-title">
                    Buku Panduan<br>
                    <span>Sistem Logistik</span>
                </h1>
                <p class="cover-subtitle">
                    Standar Operasional Pengelolaan Stok Material, Inventaris Alat Kerja, Alokasi Mandor Lapangan, Transfer Antar Gudang, dan Pemantauan Anggaran Realisasi Pembangunan Unit Rumah.
                </p>
                <div class="cover-divider"></div>

                <div class="cover-feature-grid">
                    <div class="cover-feature-item">
                        <div class="cover-feature-num">08</div>
                        <div class="cover-feature-title">Bab Operasional</div>
                        <div class="cover-feature-desc">Mencakup seluruh alur kerja mulai dari input barang masuk hingga audit buku besar.</div>
                    </div>
                    <div class="cover-feature-item">
                        <div class="cover-feature-num">HP &amp; PC</div>
                        <div class="cover-feature-title">Tampilan Responsif</div>
                        <div class="cover-feature-desc">Disertai panduan tangkapan layar ponsel di lapangan dan komputer kantor.</div>
                    </div>
                    <div class="cover-feature-item">
                        <div class="cover-feature-num">Audit</div>
                        <div class="cover-feature-title">Jejak Transaksi Lengkap</div>
                        <div class="cover-feature-desc">Perhitungan biaya per batch kedatangan dan mekanisme void terverifikasi.</div>
                    </div>
                </div>
            </div>

            <div class="cover-footer">
                <div class="cover-meta-grid">
                    <div class="cover-meta-label">Versi Sistem:</div>
                    <div>v2.4 (Produksi Aktif)</div>

                    <div class="cover-meta-label">Edisi Dokumen:</div>
                    <div>Oktober 2026</div>

                    <div class="cover-meta-label">Penerbit:</div>
                    <div>Departemen Operasional &amp; IT D'Royal Village</div>

                    <div class="cover-meta-label">Alamat Portal:</div>
                    <div>https://portal.droyalvillage.com/panduan</div>
                </div>

                <div class="cover-roles-box">
                    <strong>Target Pembaca Dokumen:</strong>
                    Staf Logistik Gudang &bull; Pengawas Lapangan &bull; Tim Keuangan &amp; Akuntansi &bull; Administrator Sistem
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- HALAMAN 2: DAFTAR ISI & MATRIKS HAK AKSES -->
        <!-- ========================================== -->
        <div class="page-break"></div>
        <div class="pdf-page">
            <div class="doc-page-header">
                <span class="brand-title">D'Royal Village &bull; Buku Panduan Operasional Sistem Logistik</span>
                <span>Daftar Isi &amp; Matriks Peran</span>
            </div>

            <div class="doc-section-header">
                <h2 class="doc-section-title">
                    Daftar Isi Panduan Operasional
                </h2>
                <span class="doc-section-tag">8 BAB LENGKAP</span>
            </div>

            <table class="toc-table">
                <thead>
                    <tr>
                        <th style="width: 32px;">Bab</th>
                        <th>Judul Bab &amp; Ringkasan Kerja</th>
                        <th style="width: 130px;">Sasaran Peran</th>
                        <th style="width: 85px;">Halaman</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(config('guide.chapters') as $index => $c)
                    <tr>
                        <td class="toc-num">{{ $c['num'] }}</td>
                        <td>
                            <div class="toc-title">{{ $c['title'] }}</div>
                            <div class="toc-desc">{{ $c['summary'] }}</div>
                        </td>
                        <td>
                            @foreach($c['roles'] as $role)
                                <span class="role-pill role-{{ strtolower($role) }}">{{ $role }}</span>
                            @endforeach
                        </td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-size: 8pt; color: #475569;">
                            Hal. {{ $index + 3 }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="doc-section-header" style="margin-top: 18px;">
                <h2 class="doc-section-title">
                    Matriks Peran &amp; Tanggung Jawab Operasional
                </h2>
                <span class="doc-section-tag">HAK AKSES</span>
            </div>

            <table class="matrix-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">Peran (Role)</th>
                        <th>Deskripsi Tugas &amp; Tanggung Jawab</th>
                        <th style="width: 175px;">Modul Akses Utama</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(config('guide.roles') as $r)
                    <tr>
                        <td>
                            <strong style="color: var(--brand-green); font-size: 8.5pt;">{{ $r['role'] }}</strong>
                            <div style="font-size: 7pt; color: var(--brand-muted);">{{ $r['deskripsi'] }}</div>
                        </td>
                        <td>{{ $r['tanggung_jawab'] }}</td>
                        <td>
                            @foreach($r['modul'] as $m)
                                <span style="display: inline-block; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 3px; font-size: 6.8pt; padding: 1px 5px; margin: 1px;">{{ $m }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="doc-page-footer">
                <span>D'Royal Village Logistics Manual &bull; Confidential Internal Document</span>
                <span>Halaman 2</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- BAB 01 HINGGA BAB 08 (SATU PER SATU HALAMAN) -->
        <!-- ========================================== -->
        @foreach(config('guide.chapters') as $index => $c)
        <div class="page-break"></div>
        <div class="pdf-page">
            <div class="doc-page-header">
                <span class="brand-title">D'Royal Village &bull; Buku Panduan Operasional Sistem Logistik</span>
                <span>Bab {{ $c['num'] }}: {{ $c['title'] }}</span>
            </div>

            <div class="chapter-box">
                <div class="chapter-top-bar">
                    <div class="chapter-badge-wrap">
                        <span class="chapter-badge">BAB {{ $c['num'] }}</span>
                        <h2 class="chapter-heading-title">{{ $c['title'] }}</h2>
                    </div>
                    <div>
                        @foreach($c['roles'] as $role)
                            <span class="role-pill role-{{ strtolower($role) }}">{{ $role }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="chapter-body">
                    <p class="chapter-summary">{{ $c['summary'] }}</p>

                    <!-- Tangkapan Layar Asli Sistem (Desktop + Mobile) -->
                    <div class="screenshots-layout">
                        <!-- Desktop Frame -->
                        <div class="ss-frame">
                            <div class="ss-frame-header">
                                <span>Tampilan Layar Lebar (Komputer / Tablet)</span>
                                <div class="ss-dots">
                                    <div class="ss-dot"></div>
                                    <div class="ss-dot"></div>
                                    <div class="ss-dot"></div>
                                </div>
                            </div>
                            <div class="ss-img-container">
                                <img src="{{ asset($c['desktop_img']) }}" alt="{{ $c['desktop_alt'] }}">
                            </div>
                            <div class="ss-caption">{{ $c['desktop_caption'] }}</div>
                        </div>

                        <!-- Mobile Frame -->
                        <div class="ss-frame">
                            <div class="ss-frame-header">
                                <span>Tampilan Ponsel (Layar HP)</span>
                                <div class="ss-dots">
                                    <div class="ss-dot"></div>
                                    <div class="ss-dot"></div>
                                    <div class="ss-dot"></div>
                                </div>
                            </div>
                            <div class="ss-img-container">
                                <img src="{{ asset($c['mobile_img']) }}" alt="{{ $c['mobile_alt'] }}">
                            </div>
                            <div class="ss-caption">{{ $c['mobile_caption'] }}</div>
                        </div>
                    </div>

                    <!-- Langkah-Langkah Operasional -->
                    <div style="font-size: 8.5pt; font-weight: 700; color: var(--brand-green); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        Langkah Kerja Pelaksanaan:
                    </div>

                    <div class="steps-container">
                        @foreach($c['steps'] as $sIndex => $step)
                        <div class="step-row">
                            <div class="step-num-badge">{{ $sIndex + 1 }}</div>
                            <div class="step-content">
                                <div class="step-heading">{{ $step['title'] }}</div>
                                <div class="step-desc">{{ $step['desc'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Catatan Operasional / Best Practice -->
                    <div class="chapter-note-box">
                        <strong>Ketentuan Khusus &amp; Catatan Lapangan:</strong>
                        {{ $c['note'] }}
                    </div>
                </div>
            </div>

            <div class="doc-page-footer">
                <span>D'Royal Village Logistics Manual &bull; Bab {{ $c['num'] }}</span>
                <span>Halaman {{ $index + 3 }}</span>
            </div>
        </div>
        @endforeach

        <!-- ========================================== -->
        <!-- HALAMAN AKHIR: FAQ & BANTUAN OPERASIONAL   -->
        <!-- ========================================== -->
        <div class="page-break"></div>
        <div class="pdf-page">
            <div class="doc-page-header">
                <span class="brand-title">D'Royal Village &bull; Buku Panduan Operasional Sistem Logistik</span>
                <span>Lampiran: Prosedur Darurat &amp; Bantuan</span>
            </div>

            <div class="doc-section-header">
                <h2 class="doc-section-title">
                    Prosedur Khusus &amp; Tanya Jawab Operasional (FAQ)
                </h2>
                <span class="doc-section-tag">STANDAR PROSEDUR</span>
            </div>

            <div class="faq-grid">
                <div class="faq-item">
                    <h3 class="faq-q">1. Apa yang harus dilakukan jika terjadi selisih stok fisik di gudang?</h3>
                    <p class="faq-a">
                        Periksa buku besar transaksi pada menu <strong>Catatan Material</strong>. Saring berdasarkan gudang dan rentang tanggal untuk menemukan transaksi alokasi atau penerimaan yang belum tercatat. Jika ada barang rusak/hilang di gudang, koordinasikan dengan Pengawas dan Admin untuk audit stok penyesuaian.
                    </p>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">2. Bagaimana membatalkan transaksi alokasi yang salah ketik?</h3>
                    <p class="faq-a">
                        Buka menu <strong>Catatan Material</strong> atau <strong>Catatan Alat</strong>. Cari baris transaksi yang salah, lalu ketuk tombol <strong>Void (Batalkan)</strong>. Tuliskan alasan pembatalan dengan jelas. Saldo batch material atau saldo alat akan dikembalikan otomatis ke gudang asal tanpa menghilangkan jejak audit.
                    </p>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">3. Mengapa material untuk rumah yang sudah selesai dicatat sebagai Garansi?</h3>
                    <p class="faq-a">
                        Unit rumah yang telah berstatus <em>Selesai</em> dan masih dalam masa pemeliharaan (garansi) akan otomatis memisahkan alokasi material baru ke dalam pos <strong>Biaya Garansi</strong>. Hal ini mempermudah audit akuntansi agar tidak mencemari biaya konstruksi awal (Capex).
                    </p>
                </div>

                <div class="faq-item">
                    <h3 class="faq-q">4. Apakah unit rumah bisa ditandai selesai jika alat belum dikembalikan?</h3>
                    <p class="faq-a">
                        Sistem memproteksi unit rumah agar tidak dapat diselesaikan jika masih terdapat saldo alat yang berstatus <em>Sedang Dipinjam</em> di unit tersebut. Buka tab <strong>Kembalikan Alat</strong> pada menu Alokasi untuk memproses serah terima alat kembali ke gudang sebelum mengubah status rumah.
                    </p>
                </div>
            </div>

            <div style="margin-top: 18px; background: var(--brand-bg); border: 1px solid var(--brand-border); border-radius: 6px; padding: 12px 14px;">
                <div style="font-weight: 700; color: var(--brand-green); font-size: 9pt; margin-bottom: 4px;">
                    Saluran Bantuan &amp; Dukungan Teknis:
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 8pt; color: #475569;">
                    <div>
                        <strong>Departemen Logistik &amp; Gudang:</strong><br>
                        Email: logistik@droyalvillage.com<br>
                        Telepon Lapangan: 0812-XXXX-XXXX
                    </div>
                    <div>
                        <strong>Departemen IT &amp; Administrator Sistem:</strong><br>
                        Email: admin.sistem@droyalvillage.com<br>
                        Portal Web: https://portal.droyalvillage.com/panduan
                    </div>
                </div>
            </div>

            <div class="doc-page-footer" style="margin-top: 25mm;">
                <span>D'Royal Village Logistics Manual &bull; Hak Cipta &copy; 2026 PT D'Royal Village. Seluruh Hak Dilindungi.</span>
                <span>Halaman 11 (Akhir)</span>
            </div>
        </div>

    </div>

</body>
</html>
