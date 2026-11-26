@extends('layouts.app')

@section('title', 'Portal & Uji Triase Aduan — TriasePublik Makassar')

@section('content')
<div class="page-head">
    <div>
        <p class="page-kicker">Layanan Disposisi Otomatis · 5 Sektor Fisik Perkotaan</p>
        <h1 class="page-title">Portal Pengaduan &amp; Simulasi Triase Cerdas</h1>
        <p class="page-desc">
            Kirimkan keluhan warga menggunakan bahasa Indonesia maupun dialek Makassar sehari-hari. Sistem menormalisasi teks, memprediksi kategori OPD, dan menilai skala kedaruratan secara langsung.
        </p>
    </div>
    <div class="mono" style="font-size: 12.5px; color: var(--ink-secondary); text-align: right;">
        <div>Terindeks: <strong>{{ number_format($totalCount, 0, ',', '.') }}</strong> laporan</div>
        <div>Input Portal Baru: <strong>{{ $portalCount }}</strong> &middot; Darurat Aktif: <strong>{{ $urgentCount }}</strong></div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-error">
        <strong>Periksa isian Anda:</strong> {{ $errors->first() }}
    </div>
@endif

<div class="portal-grid">
    {{-- Kolom Kiri: Form Pengaduan --}}
    <section class="panel" aria-labelledby="form-heading">
        <div class="panel-header">
            <h2 id="form-heading" class="panel-title">Formulir Masuk Laporan Warga</h2>
            <span class="mono" style="font-size: 11.5px; color: var(--ink-muted);">POST /triase</span>
        </div>
        <div class="panel-body">
            <div class="sample-bar">
                <span class="sample-label">Contoh cepat dari korpus &amp; uji dialek:</span>
                <div class="sample-chips">
                    <button type="button" class="sample-chip" data-kec="Rappocini" data-kel="Balla Parang" data-text="Tolong pak di Pettarani ada pohon besar tumbang menimpa kabel listrik sampai berasap bahaya sekali warga takut lewat!">
                        Darurat Pohon Tumbang
                    </button>
                    <button type="button" class="sample-chip" data-kec="Biringkanaya" data-kel="Paccerakkang" data-text="Lampux di lorong 5 mati mi tawwa sudah 3 minggu gelap gulita rawan begal, tlg ki perbaiki.">
                        Dialek Makassar (PJU)
                    </button>
                    <button type="button" class="sample-chip" data-kec="Tamalanrea" data-kel="Tamalanrea Indah" data-text="Pemasangan paving & drainase saluran air belum ada jalanan becek rusak parah kalau hujan">
                        Kalimat Majemuk
                    </button>
                    <button type="button" class="sample-chip" data-kec="Panakkukang" data-kel="Karampuang" data-text="Saluran got di depan pasar mampet bau sekali sampah menumpuk air meluap ke jalan">
                        Drainase &amp; Sampah
                    </button>
                    <button type="button" class="sample-chip" data-kec="Manggala" data-kel="Antang" data-text="Mohon info jadwal rutin pengangkutan bak sampah di kelurahan kami terima kasih">
                        Permohonan Rutin
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('portal.submit') }}" id="triage-form">
                @csrf

                <div class="form-grid">
                    <div class="field">
                        <label for="kecamatan">Kecamatan</label>
                        <select id="kecamatan" name="kecamatan" class="select" required>
                            @foreach($kecamatans as $kec)
                                <option value="{{ $kec }}" {{ old('kecamatan', 'Tamalanrea') === $kec ? 'selected' : '' }}>
                                    {{ $kec }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="kelurahan">Kelurahan / Lokasi Jalan</label>
                        <input
                            type="text"
                            id="kelurahan"
                            name="kelurahan"
                            class="input"
                            placeholder="Contoh: Tamalanrea Indah / Jl. Pettarani"
                            value="{{ old('kelurahan') }}"
                        >
                    </div>
                </div>

                <div class="field">
                    <label for="reporter_name">Nama Pelapor <span style="font-weight: 400; color: var(--ink-muted);">(opsional)</span></label>
                    <input
                        type="text"
                        id="reporter_name"
                        name="reporter_name"
                        class="input"
                        placeholder="Kosongkan untuk melapor secara anonim"
                        value="{{ old('reporter_name') }}"
                    >
                </div>

                <div class="field">
                    <label for="pesan_aduan">Isi Pesan Keluhan Warga</label>
                    <textarea
                        id="pesan_aduan"
                        name="pesan_aduan"
                        class="textarea"
                        required
                        placeholder="Tuliskan kondisi kerusakan fasilitas umum yang ditemui di lapangan..."
                    >{{ old('pesan_aduan') }}</textarea>
                </div>

                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-top: 6px;">
                    <label class="checkbox-row">
                        <input type="checkbox" name="simpan_antrean" value="1" {{ old('simpan_antrean', '1') ? 'checked' : '' }}>
                        <span>Simpan tiket ke daftar Antrean OPD</span>
                    </label>

                    <button type="submit" class="btn btn-primary">
                        Proses Triase &amp; Disposisi
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Kolom Kanan: Lembar Hasil Triase ATAU Panduan SLA & Darurat Terbaru --}}
    <section class="panel" aria-live="polite">
        @if($result)
            <div class="panel-header">
                <h2 class="panel-title">Lembar Keputusan Triase</h2>
                <span class="mono badge badge-navy">{{ $result['ticket_code'] ?? 'SIMULASI' }}</span>
            </div>
            <div class="panel-body">
                <div class="receipt-top">
                    <div>
                        <span style="font-size: 12px; color: var(--ink-muted); display: block;">Mesin Klasifikasi</span>
                        <span class="mono" style="font-size: 12.5px; font-weight: 600;">{{ $result['model_engine'] }}</span>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 12px; color: var(--ink-muted); display: block;">Latensi Inferensi PHP</span>
                        <span class="mono" style="font-size: 13px; font-weight: 700; color: var(--navy);">{{ number_format($result['latency_ms'], 2) }} ms</span>
                    </div>
                </div>

                <div class="opd-box">
                    <div class="opd-box-label">Disposisi Otomatis · {{ $result['opd']['kode'] }}</div>
                    <p class="opd-box-name">{{ $result['opd']['opd'] }}</p>
                    <div class="opd-box-unit">{{ $result['opd']['unit'] }} &middot; Target SLA: <strong>{{ $result['opd']['sla'] }}</strong></div>
                </div>

                <div class="two-col-grid" style="gap: 12px; margin-bottom: 16px;">
                    <div style="padding: 10px 12px; border: 1px solid var(--border-hairline); border-radius: 4px; background: #faf9f5;">
                        <span style="font-size: 11.5px; color: var(--ink-muted); display: block; margin-bottom: 4px;">Kategori Terpilih</span>
                        <strong style="font-size: 15px;">{{ $result['predicted_category'] }}</strong>
                        <div class="mono" style="font-size: 12px; color: var(--ink-secondary); margin-top: 2px;">
                            Keyakinan: {{ number_format($result['confidence_svm'], 1) }}%
                        </div>
                    </div>

                    <div style="padding: 10px 12px; border: 1px solid var(--border-hairline); border-radius: 4px; background: #faf9f5;">
                        <span style="font-size: 11.5px; color: var(--ink-muted); display: block; margin-bottom: 4px;">Skala Urgensi</span>
                        @php
                            $urg = $result['predicted_urgency'];
                            $urgClass = $urg === 'Tinggi' ? 'badge-high' : ($urg === 'Sedang' ? 'badge-med' : 'badge-low');
                        @endphp
                        <span class="badge {{ $urgClass }}" style="font-size: 13px; padding: 3px 9px;">{{ $urg }}</span>
                        <div style="font-size: 11.5px; color: var(--ink-secondary); margin-top: 4px;">
                            {{ $result['urgency_rule'] }}
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <div style="font-size: 12px; font-weight: 700; color: var(--ink-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.04em;">
                        Distribusi Probabilitas 5 Kelas
                    </div>
                    <div class="prob-list">
                        @foreach($result['class_scores'] as $cls => $pct)
                            <div>
                                <div class="prob-row-head">
                                    <span style="{{ $cls === $result['predicted_category'] ? 'font-weight:700; color:var(--navy);' : '' }}">{{ $cls }}</span>
                                    <span class="mono">{{ number_format($pct, 1) }}%</span>
                                </div>
                                <div class="prob-track">
                                    <div class="prob-fill {{ $cls === $result['predicted_category'] ? 'is-top' : '' }}" style="width: {{ max(2, $pct) }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="padding-top: 14px; border-top: 1px solid var(--border-hairline);">
                    <div style="font-size: 12px; font-weight: 700; color: var(--ink-secondary); margin-bottom: 6px;">
                        Jejak Praprosesing &amp; Normalisasi Dialek Makassar
                    </div>
                    <div class="mono" style="font-size: 12px; padding: 8px 10px; background: #f8f7f2; border: 1px solid var(--border-hairline); border-radius: 4px; color: var(--ink-primary);">
                        {{ $result['clean_text'] ?: '(kosong setelah pembersihan)' }}
                    </div>

                    @if(!empty($result['replacements']) || !empty($result['particles']))
                        <div style="font-size: 12px; color: var(--ink-secondary); margin-top: 6px;">
                            @if(!empty($result['replacements']))
                                Konversi slang: <span class="mono">{{ implode(', ', $result['replacements']) }}</span>
                            @endif
                            @if(!empty($result['particles']))
                                &middot; Partikel lokal: <span class="mono">{{ implode(', ', $result['particles']) }}</span>
                            @endif
                        </div>
                    @endif

                    @if(!empty($result['top_tokens']))
                        <div style="font-size: 12px; font-weight: 700; color: var(--ink-secondary); margin-top: 12px;">
                            N-Gram Penentu Keputusan (TF-IDF &times; Bobot SVM)
                        </div>
                        <div class="token-list">
                            @foreach($result['top_tokens'] as $tok)
                                <span class="token-tag">
                                    {{ $tok['term'] }}<span class="token-weight">+{{ number_format($tok['weight'], 2) }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($result['urgency_keywords']))
                        <div style="font-size: 12px; color: var(--urg-high-ink); margin-top: 10px;">
                            Kata kunci pemicu urgensi: <span class="mono">{{ implode(', ', $result['urgency_keywords']) }}</span>
                        </div>
                    @endif
                </div>

                @if($savedComplaint)
                    <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-hairline); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12.5px; color: var(--urg-low-ink); font-weight: 600;">Tersimpan di Antrean OPD</span>
                        <a href="{{ route('queue.show', $savedComplaint) }}" class="btn btn-secondary btn-sm">Buka Detail Tiket &rarr;</a>
                    </div>
                @endif
            </div>
        @else
            <div class="panel-header">
                <h2 class="panel-title">Prioritas Darurat Terbaru di Antrean</h2>
                <a href="{{ route('queue.index', ['urgensi' => 'Tinggi']) }}" style="font-size: 12.5px; font-weight: 600;">Lihat Semua &rarr;</a>
            </div>
            <div class="panel-body">
                @forelse($recentUrgent as $item)
                    <div style="padding: 10px 0; {{ !$loop->last ? 'border-bottom: 1px solid var(--border-hairline);' : '' }}">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <a href="{{ route('queue.show', $item) }}" class="mono" style="font-size: 12.5px; font-weight: 600;">
                                {{ $item->ticket_code }} &middot; {{ $item->kecamatan }}
                            </a>
                            <span class="badge badge-high">{{ $item->predicted_urgency }}</span>
                        </div>
                        <p style="margin: 0 0 4px; font-size: 13px; color: var(--ink-primary);">
                            {{ \Illuminate\Support\Str::limit($item->raw_text, 115) }}
                        </p>
                        <div style="font-size: 12px; color: var(--ink-muted);">
                            {{ $item->predicted_category }} &rarr; {{ $item->assigned_opd }} (SLA {{ $item->sla_target }})
                        </div>
                    </div>
                @empty
                    <p style="font-size: 13.5px; color: var(--ink-muted); margin: 0;">Belum ada aduan darurat aktif.</p>
                @endforelse

                <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-hairline);">
                    <div style="font-size: 12px; font-weight: 700; color: var(--ink-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.04em;">
                        Matriks Disposisi OPD &amp; SLA
                    </div>
                    <table class="data-table" style="font-size: 12.5px;">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>OPD Teknis</th>
                                <th>SLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($opdMapping as $kat => $info)
                                <tr>
                                    <td style="font-weight: 600;">{{ $kat }}</td>
                                    <td>{{ $info['kode'] }}</td>
                                    <td class="mono">{{ $info['sla'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.sample-chip').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('kecamatan').value = btn.dataset.kec;
        document.getElementById('kelurahan').value = btn.dataset.kel;
        document.getElementById('pesan_aduan').value = btn.dataset.text;
        document.getElementById('pesan_aduan').focus();
    });
});
</script>
@endpush
