@extends('layouts.app')

@section('title', 'Meja Kerja Antrean OPD — TriasePublik Makassar')

@section('content')
<div class="page-head">
    <div>
        <p class="page-kicker">Operasional Disposisi · 1.508 Sampel Uji Pemkot + Input Portal</p>
        <h1 class="page-title">Meja Kerja Operator &amp; Antrean OPD</h1>
        <p class="page-desc">
            Daftar aduan yang telah diklasifikasikan otomatis dan diurutkan berdasarkan laporan portal terbaru serta skala urgensi keselamatan warga.
        </p>
    </div>
    <a href="{{ route('portal.index') }}" class="btn btn-primary">+ Tambah Uji Laporan Baru</a>
</div>

@if(session('status_updated'))
    <div class="alert alert-success">{{ session('status_updated') }}</div>
@endif

<div class="summary-strip">
    <div class="stat-cell">
        <span class="stat-label">Lampu Jalan (Dishub)</span>
        <span class="stat-value">{{ number_format($summaryByCategory['Lampu Jalan'] ?? 0, 0, ',', '.') }}</span>
        <span class="stat-note">Sektor dominan (70,4% korpus)</span>
    </div>
    <div class="stat-cell">
        <span class="stat-label">Drainase &amp; Jalan (DPU)</span>
        <span class="stat-value">{{ number_format(($summaryByCategory['Drainase'] ?? 0) + ($summaryByCategory['Jalan Rusak'] ?? 0), 0, ',', '.') }}</span>
        <span class="stat-note">Drainase {{ $summaryByCategory['Drainase'] ?? 0 }} &middot; Jalan {{ $summaryByCategory['Jalan Rusak'] ?? 0 }}</span>
    </div>
    <div class="stat-cell">
        <span class="stat-label">Pohon &amp; Sampah (DLH)</span>
        <span class="stat-value">{{ number_format(($summaryByCategory['Pangkas/Tebang Pohon'] ?? 0) + ($summaryByCategory['Sampah'] ?? 0), 0, ',', '.') }}</span>
        <span class="stat-note">Pohon {{ $summaryByCategory['Pangkas/Tebang Pohon'] ?? 0 }} &middot; Sampah {{ $summaryByCategory['Sampah'] ?? 0 }}</span>
    </div>
    <div class="stat-cell">
        <span class="stat-label">Urgensi Tinggi / Darurat</span>
        <span class="stat-value" style="color: var(--urg-high-ink);">{{ number_format($summaryByUrgency['Tinggi'] ?? 0, 0, ',', '.') }}</span>
        <span class="stat-note">Sedang {{ $summaryByUrgency['Sedang'] ?? 0 }} &middot; Rendah {{ $summaryByUrgency['Rendah'] ?? 0 }}</span>
    </div>
</div>

<div class="panel">
    <form method="GET" action="{{ route('queue.index') }}" class="filter-bar">
        <div class="filter-field">
            <label for="f-q">Cari Tiket / Isi Aduan / Kelurahan</label>
            <input id="f-q" type="text" name="q" class="input" placeholder="Misal: MKS-4159, pettarani, got..." value="{{ $filters['q'] ?? '' }}">
        </div>

        <div class="filter-field">
            <label for="f-kec">Kecamatan</label>
            <select id="f-kec" name="kecamatan" class="select">
                <option value="">Semua Kecamatan</option>
                @foreach($kecamatans as $kec)
                    <option value="{{ $kec }}" {{ ($filters['kecamatan'] ?? '') === $kec ? 'selected' : '' }}>{{ $kec }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="f-kat">Kategori</label>
            <select id="f-kat" name="kategori" class="select">
                <option value="">Semua Kategori</option>
                @foreach($categories as $kat)
                    <option value="{{ $kat }}" {{ ($filters['kategori'] ?? '') === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="f-urg">Urgensi</label>
            <select id="f-urg" name="urgensi" class="select">
                <option value="">Semua Skala</option>
                @foreach($urgencies as $urg)
                    <option value="{{ $urg }}" {{ ($filters['urgensi'] ?? '') === $urg ? 'selected' : '' }}>{{ $urg }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label for="f-stat">Status</label>
            <select id="f-stat" name="status" class="select">
                <option value="">Semua Status</option>
                @foreach($statuses as $st)
                    <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; gap: 6px;">
            <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">Terapkan</button>
            <a href="{{ route('queue.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;">Reset</a>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tiket &amp; Sumber</th>
                    <th>Kecamatan / Kel.</th>
                    <th style="min-width: 280px;">Isi Laporan Warga</th>
                    <th>Kategori &amp; OPD</th>
                    <th>Urgensi</th>
                    <th>Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($complaints as $row)
                    @php
                        $urgClass = $row->predicted_urgency === 'Tinggi'
                            ? 'badge-high'
                            : ($row->predicted_urgency === 'Sedang' ? 'badge-med' : 'badge-low');
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('queue.show', $row) }}" class="mono" style="font-weight: 700; display: block;">
                                {{ $row->ticket_code }}
                            </a>
                            <span style="font-size: 11.5px; color: var(--ink-muted);">
                                {{ $row->source === 'portal_warga' ? 'Input Portal' : 'Set Uji Pemkot' }}
                            </span>
                        </td>
                        <td>
                            <strong style="display: block; font-size: 13px;">{{ $row->kecamatan }}</strong>
                            <span style="font-size: 12px; color: var(--ink-muted);">{{ $row->kelurahan }}</span>
                        </td>
                        <td>
                            <a href="{{ route('queue.show', $row) }}" style="color: var(--ink-primary); display: block; margin-bottom: 3px;">
                                {{ \Illuminate\Support\Str::limit($row->raw_text, 125) }}
                            </a>
                            <span class="mono" style="font-size: 11px; color: var(--ink-muted);">
                                Keyakinan {{ number_format($row->confidence_svm, 1) }}% &middot; {{ number_format($row->latency_ms, 2) }} ms
                            </span>
                        </td>
                        <td>
                            <strong style="display: block;">{{ $row->predicted_category }}</strong>
                            <span style="font-size: 12px; color: var(--ink-secondary);">{{ $row->assigned_opd }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $urgClass }}">{{ $row->predicted_urgency }}</span>
                            <div class="mono" style="font-size: 11px; color: var(--ink-muted); margin-top: 3px;">SLA {{ $row->sla_target }}</div>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('queue.status', $row) }}">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="select" style="font-size: 12.5px; padding: 5px 8px; min-width: 155px;" onchange="this.form.submit()">
                                    @foreach($statuses as $st)
                                        <option value="{{ $st }}" {{ $row->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 32px; color: var(--ink-muted);">
                            Tidak ada aduan yang cocok dengan kriteria filter saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.pagination', ['paginator' => $complaints])
</div>
@endsection
