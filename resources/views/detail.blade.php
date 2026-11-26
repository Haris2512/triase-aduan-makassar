@extends('layouts.app')

@section('title', 'Detail Tiket ' . $complaint->ticket_code . ' — TriasePublik Makassar')

@section('content')
<div style="margin-bottom: 16px;">
    <a href="{{ route('queue.index') }}" class="mono" style="font-size: 13px;">&larr; Kembali ke Antrean OPD</a>
</div>

@if(session('status_updated'))
    <div class="alert alert-success">{{ session('status_updated') }}</div>
@endif

<div class="page-head">
    <div>
        <p class="page-kicker">Audit Klasifikasi Tiket · {{ $complaint->source === 'portal_warga' ? 'Laporan Portal Baru' : 'Arsip Set Uji Pemkot Makassar' }}</p>
        <h1 class="page-title mono">{{ $complaint->ticket_code }}</h1>
        <p class="page-desc">
            Kecamatan {{ $complaint->kecamatan }}, Kelurahan {{ $complaint->kelurahan }} &middot; Dilaporkan {{ optional($complaint->reported_at)->format('d M Y, H:i') ?? '-' }}
        </p>
    </div>

    <form method="POST" action="{{ route('queue.status', $complaint) }}" style="display: flex; gap: 8px; align-items: center;">
        @csrf
        @method('PATCH')
        <select name="status" class="select" style="width: auto;">
            @foreach(['Menunggu Verifikasi', 'Terdisposisi ke OPD', 'Dalam Penanganan', 'Selesai'] as $st)
                <option value="{{ $st }}" {{ $complaint->status === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Perbarui Status</button>
    </form>
</div>

<div class="portal-grid">
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Rekaman Teks &amp; Jejak Normalisasi</h2>
            <span class="mono" style="font-size: 12px; color: var(--ink-muted);">{{ $complaint->reporter_name }}</span>
        </div>
        <div class="panel-body">
            <div style="margin-bottom: 16px;">
                <span style="font-size: 12px; font-weight: 700; color: var(--ink-muted); display: block; margin-bottom: 6px;">PESAN ADUAN ASLI</span>
                <div style="padding: 12px 14px; background: #faf9f5; border: 1px solid var(--border-hairline); border-radius: 4px; font-size: 15px;">
                    {{ $complaint->raw_text }}
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <span style="font-size: 12px; font-weight: 700; color: var(--ink-muted); display: block; margin-bottom: 6px;">HASIL NORMALISASI TEKS &amp; DIALEK</span>
                <div class="mono" style="padding: 12px 14px; background: #f8f7f2; border: 1px solid var(--border-hairline); border-radius: 4px; font-size: 13px;">
                    {{ $liveAnalysis['clean_text'] }}
                </div>
            </div>

            @if($complaint->actual_category)
                <div class="two-col-grid" style="gap: 12px;">
                    <div style="padding: 10px 12px; border: 1px solid var(--border-hairline); border-radius: 4px;">
                        <span style="font-size: 11.5px; color: var(--ink-muted); display: block;">Label Aktual Pemkot</span>
                        <strong>{{ $complaint->actual_category }}</strong>
                        <span style="font-size: 12px; color: var(--ink-secondary); display: block;">Urgensi: {{ $complaint->actual_urgency }}</span>
                    </div>
                    <div style="padding: 10px 12px; border: 1px solid var(--border-hairline); border-radius: 4px;">
                        <span style="font-size: 11.5px; color: var(--ink-muted); display: block;">Kecocokan Prediksi Model</span>
                        @if($complaint->actual_category === $liveAnalysis['predicted_category'])
                            <span class="badge badge-low">Tepat (Match)</span>
                        @else
                            <span class="badge badge-high">Berbeda ({{ $liveAnalysis['predicted_category'] }})</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Analisis Fitur TF-IDF &amp; Disposisi OPD</h2>
            <span class="mono" style="font-size: 12px;">{{ number_format($liveAnalysis['latency_ms'], 2) }} ms</span>
        </div>
        <div class="panel-body">
            <div class="opd-box">
                <div class="opd-box-label">Tujuan Disposisi · {{ $liveAnalysis['opd']['kode'] }}</div>
                <p class="opd-box-name">{{ $complaint->assigned_opd }}</p>
                <div class="opd-box-unit">{{ $complaint->assigned_unit }} &middot; SLA {{ $complaint->sla_target }}</div>
            </div>

            <div class="prob-list" style="margin-bottom: 16px;">
                @foreach($liveAnalysis['class_scores'] as $cls => $pct)
                    <div>
                        <div class="prob-row-head">
                            <span style="{{ $cls === $liveAnalysis['predicted_category'] ? 'font-weight:700; color:var(--navy);' : '' }}">{{ $cls }}</span>
                            <span class="mono">{{ number_format($pct, 1) }}%</span>
                        </div>
                        <div class="prob-track">
                            <div class="prob-fill {{ $cls === $liveAnalysis['predicted_category'] ? 'is-top' : '' }}" style="width: {{ max(2, $pct) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!empty($liveAnalysis['top_tokens']))
                <div style="font-size: 12px; font-weight: 700; color: var(--ink-secondary);">
                    Token Penentu Keputusan (Linear SVM)
                </div>
                <div class="token-list">
                    @foreach($liveAnalysis['top_tokens'] as $tok)
                        <span class="token-tag">
                            {{ $tok['term'] }}<span class="token-weight">+{{ number_format($tok['weight'], 2) }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
