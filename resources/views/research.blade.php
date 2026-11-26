@extends('layouts.app')

@section('title', 'Evaluasi & Metodologi Riset — TriasePublik Makassar')

@section('content')
<div class="page-head">
    <div>
        <p class="page-kicker">Benchmark Empiris Skripsi · Dual-Track NLP Pipeline</p>
        <h1 class="page-title">Evaluasi Model: Baseline Linear SVM vs Fine-Tuned IndoBERT</h1>
        <p class="page-desc">
            Dokumentasi kuantitatif atas 15.090 log aduan Pemkot Makassar dengan rasio ketimpangan kelas ekstrem 13:1 serta pengujian pada 1.508 sampel uji independen.
        </p>
    </div>
</div>

{{-- Alur 5 Tahap --}}
<div class="pipeline-steps">
    <div class="pipeline-step">
        <span class="step-num">TAHAP 01</span>
        <h2 class="step-name">Akuisisi &amp; Seleksi</h2>
        <p class="step-desc">19.466 log mentah disaring pada 5 sektor fisik utama menjadi 15.090 rekaman valid.</p>
    </div>
    <div class="pipeline-step">
        <span class="step-num">TAHAP 02</span>
        <h2 class="step-name">Normalisasi Lokal</h2>
        <p class="step-desc">Sanitasi tag Excel, akhiran <code>-x</code> &rarr; <code>-nya</code>, slang, serta partikel Makassar.</p>
    </div>
    <div class="pipeline-step">
        <span class="step-num">TAHAP 03</span>
        <h2 class="step-name">Stratified Split</h2>
        <p class="step-desc">Partisi terkunci 80:10:10 (12.063 latih, 1.508 validasi, 1.508 uji independen).</p>
    </div>
    <div class="pipeline-step">
        <span class="step-num">TAHAP 04</span>
        <h2 class="step-name">Dual-Track Model</h2>
        <p class="step-desc">Linear SVM (TF-IDF N-gram 10K fitur) vs Fine-Tuned <code>indobert-base-p1</code>.</p>
    </div>
    <div class="pipeline-step">
        <span class="step-num">TAHAP 05</span>
        <h2 class="step-name">Triase &amp; Disposisi</h2>
        <p class="step-desc">Pemetaan OPD (Dishub, DPU, DLH) beserta SLA &amp; heuristik keselamatan warga.</p>
    </div>
</div>

{{-- Ringkasan Benchmark Utama --}}
<div class="summary-strip">
    <div class="stat-cell">
        <span class="stat-label">Macro F1-Score (Metrik Utama)</span>
        <span class="stat-value">95,70%</span>
        <span class="stat-note">IndoBERT (+0,31% atas SVM 95,39%)</span>
    </div>
    <div class="stat-cell">
        <span class="stat-label">Kelas Minoritas Jalan Rusak</span>
        <span class="stat-value" style="color: var(--navy);">+3,35%</span>
        <span class="stat-note">F1 IndoBERT 93,41% vs SVM 90,06%</span>
    </div>
    <div class="stat-cell">
        <span class="stat-label">Akurasi Global Test Set</span>
        <span class="stat-value">98,01%</span>
        <span class="stat-note">Identik pada 1.508 data uji independen</span>
    </div>
    <div class="stat-cell">
        <span class="stat-label">Trade-Off Latensi Inferensi</span>
        <span class="stat-value mono">0,85 / 12,36 ms</span>
        <span class="stat-note">SVM CPU sangat ringan &middot; IndoBERT &lt;200 ms</span>
    </div>
</div>

<div class="two-col-grid" style="margin-bottom: 22px;">
    {{-- Tabel Performa Per Kelas --}}
    <section class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Komparasi F1-Score Per Kelas &amp; Ketimpangan Data</h2>
            <span class="mono" style="font-size: 11.5px; color: var(--ink-muted);">n = 15.090 (Uji = 1.508)</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kategori &amp; OPD</th>
                        <th>Proporsi Data</th>
                        <th>F1 SVM</th>
                        <th>F1 IndoBERT</th>
                        <th>Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datasetStats as $st)
                        @php $diff = round($st['bert_f1'] - $st['svm_f1'], 2); @endphp
                        <tr>
                            <td>
                                <strong>{{ $st['kategori'] }}</strong>
                                <span style="font-size: 11.5px; color: var(--ink-muted); display: block;">{{ $st['opd'] }} &middot; {{ number_format($st['jumlah'], 0, ',', '.') }} baris</span>
                            </td>
                            <td>
                                <span class="mono">{{ number_format($st['persen'], 2, ',', '.') }}%</span>
                                <div class="prob-track" style="margin-top: 4px; width: 90px;">
                                    <div class="prob-fill is-top" style="width: {{ max(5, $st['persen']) }}%;"></div>
                                </div>
                            </td>
                            <td class="mono">{{ number_format($st['svm_f1'], 2, ',', '.') }}%</td>
                            <td class="mono" style="font-weight: 700;">{{ number_format($st['bert_f1'], 2, ',', '.') }}%</td>
                            <td class="mono" style="color: {{ $diff >= 0 ? 'var(--urg-low-ink)' : 'var(--ink-muted)' }}; font-weight: 600;">
                                {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2, ',', '.') }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Penguji Normalisasi Dialek Interaktif --}}
    <section class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Uji Langsung Normalisasi Dialek Makassar</h2>
            <span class="mono" style="font-size: 11.5px; color: var(--ink-muted);">POST /api/normalisasi</span>
        </div>
        <div class="panel-body">
            <p style="font-size: 13px; color: var(--ink-secondary); margin-top: 0;">
                Ketik kalimat berimbuhan akhiran <code>-x</code>, singkatan pesan singkat, atau partikel Bugis-Makassar untuk melihat keluaran praprosesing sistem.
            </p>
            <div class="field">
                <label for="norm-input">Teks Masukan Mentah</label>
                <input
                    type="text"
                    id="norm-input"
                    class="input"
                    value="lampux di jln dpn lorong matiii ki tawwa, tlg pju tdk nyala&lt;br/&gt;_x000D_"
                >
            </div>
            <button type="button" id="btn-norm" class="btn btn-secondary btn-sm">Jalankan Normalisasi</button>

            <div style="margin-top: 14px;">
                <span style="font-size: 12px; font-weight: 700; color: var(--ink-muted); display: block; margin-bottom: 4px;">TEKS BERSIH HASIL TRANSFORMASI</span>
                <div id="norm-output" class="mono" style="padding: 10px 12px; background: #f8f7f2; border: 1px solid var(--border-hairline); border-radius: 4px; font-size: 13px;">
                    lampunya di jalan depan lorong mati ki tawwa tolong lampu jalan tidak menyala
                </div>
                <div id="norm-meta" style="font-size: 12px; color: var(--ink-secondary); margin-top: 6px;"></div>
            </div>
        </div>
    </section>
</div>

{{-- Analisis Kesalahan Multi-Topik --}}
<section class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Studi Kasus Error Analysis: Kalimat Majemuk Multi-Topik</h2>
        <span style="font-size: 12px; color: var(--ink-secondary);">Mengapa pemahaman semantik IndoBERT unggul atas pencocokan kata TF-IDF</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="min-width: 240px;">Teks Aduan Warga</th>
                    <th>Label Aktual</th>
                    <th>Prediksi SVM</th>
                    <th>Prediksi IndoBERT</th>
                    <th>Analisis Linguistik &amp; Semantik</th>
                </tr>
            </thead>
            <tbody>
                @foreach($errorCases as $case)
                    <tr>
                        <td style="font-style: italic;">"{{ $case['teks'] }}"</td>
                        <td><span class="badge badge-navy">{{ $case['aktual'] }}</span></td>
                        <td><span class="badge badge-high">{{ $case['svm'] }} (Salah)</span></td>
                        <td><span class="badge badge-low">{{ $case['indobert'] }} (Benar)</span></td>
                        <td style="font-size: 13px; color: var(--ink-secondary);">{{ $case['analisis'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
async function runNormalizer() {
    const text = document.getElementById('norm-input').value;
    const out = document.getElementById('norm-output');
    const meta = document.getElementById('norm-meta');
    try {
        const res = await fetch("{{ route('api.normalize') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ text })
        });
        const data = await res.json();
        out.textContent = data.clean_text || '(kosong)';
        const parts = [];
        if (data.replacements && data.replacements.length) {
            parts.push('Konversi: ' + data.replacements.join(', '));
        }
        if (data.particles && data.particles.length) {
            parts.push('Partikel lokal terdeteksi: ' + data.particles.join(', '));
        }
        meta.textContent = parts.join(' | ');
    } catch (e) {
        out.textContent = 'Gagal memproses normalisasi.';
    }
}
document.getElementById('btn-norm').addEventListener('click', runNormalizer);
runNormalizer();
</script>
@endpush
