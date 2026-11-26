<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'TriasePublik Makassar — Sistem Disposisi Aduan Warga')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/civic.css') }}">
</head>
<body>
    <div class="utility-strip">
        <div class="container utility-strip-inner">
            <span>Purwarupa Triase Layanan Pengaduan Publik · Kota Makassar</span>
            <span class="utility-pill">
                <span class="dot-live"></span>
                @if(config('services.indobert.url'))
                    Dual-Track Live: Linear SVM + IndoBERT API
                @else
                    Mesin Aktif: Linear SVM + TF-IDF (PHP Native)
                @endif
            </span>
        </div>
    </div>

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('portal.index') }}" class="brand">
                <span class="brand-mark" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19h16" />
                        <path d="M6 16L12 4l6 12H6z" />
                        <path d="M12 4v12" />
                    </svg>
                </span>
                <span>
                    <span class="brand-title">TriasePublik Makassar</span>
                    <span class="brand-sub">Klasifikasi Kategori &amp; Skala Urgensi OPD</span>
                </span>
            </a>

            @php
                $openHighCount = \App\Models\Complaint::where('predicted_urgency', 'Tinggi')
                    ->where('status', '!=', 'Selesai')
                    ->count();
            @endphp

            <nav class="main-nav" aria-label="Navigasi Utama">
                <a href="{{ route('portal.index') }}" class="nav-link {{ request()->routeIs('portal.*') ? 'active' : '' }}">
                    Portal &amp; Uji Triase
                </a>
                <a href="{{ route('queue.index') }}" class="nav-link {{ request()->routeIs('queue.*') ? 'active' : '' }}">
                    Antrean OPD
                    @if($openHighCount > 0)
                        <span class="nav-count" title="Laporan urgensi tinggi belum selesai">{{ $openHighCount }}</span>
                    @endif
                </a>
                <a href="{{ route('research.index') }}" class="nav-link {{ request()->routeIs('research.*') ? 'active' : '' }}">
                    Evaluasi &amp; Riset
                </a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <span>Penelitian Sistem Informasi Cerdas · Haris (H071241070) · FMIPA Universitas Hasanuddin</span>
            <span class="mono">Korpus Pemkot Makassar: 15.090 rekaman (1.508 set uji terindeks)</span>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
