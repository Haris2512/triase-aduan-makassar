<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ComplaintTriageService
{
    private static ?array $bundle = null;

    private const SLANG_DICT = [
        'tlg' => 'tolong',
        'tlng' => 'tolong',
        'mohon' => 'tolong',
        'jln' => 'jalan',
        'jl' => 'jalan',
        'matii' => 'mati',
        'matit' => 'mati',
        'nyala' => 'menyala',
        'tutup' => 'tertutup',
        'tdk' => 'tidak',
        'ngga' => 'tidak',
        'gak' => 'tidak',
        'ndak' => 'tidak',
        'ndaa' => 'tidak',
        'nda' => 'tidak',
        'dkt' => 'dekat',
        'dpn' => 'depan',
        'blkng' => 'belakang',
        'sdh' => 'sudah',
        'udh' => 'sudah',
        'suda' => 'sudah',
        'bgt' => 'banget',
        'bgtu' => 'begitu',
        'sy' => 'saya',
        'dgn' => 'dengan',
        'krn' => 'karena',
        'karna' => 'karena',
        'klu' => 'kalau',
        'klo' => 'kalau',
        'bapake' => 'bapak',
        'bpk' => 'bapak',
        'ibu' => 'ibu',
        'pemerintah' => 'pemkot',
        'pohonx' => 'pohonnya',
        'lampux' => 'lampunya',
        'jalanny' => 'jalannya',
        'rusakny' => 'rusaknya',
        'smph' => 'sampah',
        'pju' => 'lampu jalan',
        'pdam' => 'layanan pdam',
    ];

    private const MAKASSAR_PARTICLES = ['ki', 'ji', 'mi', 'tawwa', 'kodong', 'skali', 'sikali', 'toh', 'pi'];

    private const KEYWORDS_TINGGI = [
        'darurat', 'bahaya', 'segera', 'cepat', 'urgens', 'korslet', 'konslet',
        'berasap', 'meledak', 'api', 'terbakar', 'roboh', 'tumbang', 'timpa',
        'patah', 'kecelakaan', 'korban', 'jatuh', 'begal', 'kriminal', 'rampok',
        'gelap gulita', 'rawan', 'amblas', 'longsor', 'banjir', 'tenggelam',
        'tersengat', 'putus', 'hancur parah', 'lubang besar', 'macet total',
    ];

    private const KEYWORDS_RENDAH = [
        'saran', 'usul', 'usulan', 'mohon dicek', 'periksa', 'perapian',
        'pemeliharaan', 'sekadar info', 'informasi', 'tolong bantu cek',
        'kalau bisa', 'nanti', 'jadwal',
    ];

    public const KECAMATAN_LIST = [
        'Biringkanaya',
        'Bontoala',
        'Kepulauan Sangkarrang',
        'Makassar',
        'Mamajang',
        'Manggala',
        'Mariso',
        'Panakkukang',
        'Rappocini',
        'Tallo',
        'Tamalanrea',
        'Tamalate',
        'Ujung Pandang',
        'Ujung Tanah',
        'Wajo',
    ];

    public const OPD_MAPPING = [
        'Lampu Jalan' => [
            'opd' => 'Dinas Perhubungan (Dishub) Kota Makassar',
            'unit' => 'UPT Lampu Penerangan Jalan Umum (PJU)',
            'sla' => '< 24 Jam',
            'kode' => 'DISHUB-PJU',
        ],
        'Drainase' => [
            'opd' => 'Dinas Pekerjaan Umum (DPU) Kota Makassar',
            'unit' => 'Bidang Drainase dan PSDA',
            'sla' => '< 12 Jam',
            'kode' => 'DPU-PSDA',
        ],
        'Pangkas/Tebang Pohon' => [
            'opd' => 'Dinas Lingkungan Hidup (DLH) Kota Makassar',
            'unit' => 'Bidang Ruang Terbuka Hijau & Pertamanan',
            'sla' => '< 4 Jam',
            'kode' => 'DLH-RTH',
        ],
        'Sampah' => [
            'opd' => 'Dinas Lingkungan Hidup (DLH) Kota Makassar',
            'unit' => 'Bidang Pengelolaan Kebersihan & Persampahan',
            'sla' => '< 24 Jam',
            'kode' => 'DLH-SMP',
        ],
        'Jalan Rusak' => [
            'opd' => 'Dinas Pekerjaan Umum (DPU) Kota Makassar',
            'unit' => 'Bidang Pembangunan Jalan dan Jembatan',
            'sla' => '< 48 Jam',
            'kode' => 'DPU-JLN',
        ],
    ];

    private function loadBundle(): array
    {
        if (self::$bundle !== null) {
            return self::$bundle;
        }

        $path = storage_path('app/ml/svm_bundle.json');
        if (!file_exists($path)) {
            throw new \RuntimeException("Model bundle tidak ditemukan di: {$path}");
        }

        $decoded = json_decode(file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Gagal membaca svm_bundle.json');
        }

        self::$bundle = $decoded;
        return self::$bundle;
    }

    /**
     * Membersihkan teks dan mencatat transformasi dialek Makassar untuk transparansi proses.
     */
    public function normalizeWithTrace(string $rawText): array
    {
        $text = preg_replace('/<br\s*\/?>/i', ' ', $rawText);
        $text = preg_replace('/_x[0-9a-fA-F]{4}_/', ' ', $text);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/https?:\/\/\S+|www\.\S+/', '', $text);
        $text = preg_replace('/\S+@\S+/', '', $text);
        $text = preg_replace('/\b\d{4,}\b/', '', $text);
        $text = preg_replace('/[^a-z\s]/', ' ', $text);
        $text = preg_replace('/([a-z])\1{2,}/', '$1', $text);

        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $normalized = [];
        $replacements = [];
        $particlesFound = [];

        foreach ($words as $w) {
            $original = $w;
            if (in_array($original, self::MAKASSAR_PARTICLES, true)) {
                $particlesFound[] = $original;
            }
            if (strlen($w) > 2 && str_ends_with($w, 'x')) {
                $w = substr($w, 0, -1) . 'nya';
            }
            $mapped = self::SLANG_DICT[$w] ?? $w;
            if ($mapped !== $original) {
                $replacements[] = "{$original} -> {$mapped}";
            }
            $normalized[] = $mapped;
        }

        $clean = preg_replace('/\s+/', ' ', trim(implode(' ', $normalized)));

        return [
            'clean_text' => $clean,
            'replacements' => array_values(array_unique($replacements)),
            'particles' => array_values(array_unique($particlesFound)),
        ];
    }

    public function classify(string $rawText): array
    {
        $startNs = hrtime(true);
        $bundle = $this->loadBundle();

        $trace = $this->normalizeWithTrace($rawText);
        $cleanText = $trace['clean_text'];

        // Ekstraksi token sesuai scikit-learn token_pattern=(?u)\b\w\w+\b & ngram_range=(1,2)
        preg_match_all('/\b[a-z0-9_]{2,}\b/', $cleanText, $matches);
        $unigrams = $matches[0] ?? [];

        $ngrams = $unigrams;
        $countUni = count($unigrams);
        for ($i = 0; $i < $countUni - 1; $i++) {
            $ngrams[] = $unigrams[$i] . ' ' . $unigrams[$i + 1];
        }

        $tfCounts = [];
        $termsDict = $bundle['terms'];
        foreach ($ngrams as $gram) {
            if (isset($termsDict[$gram])) {
                $tfCounts[$gram] = ($tfCounts[$gram] ?? 0) + 1;
            }
        }

        // Sublinear TF * IDF + L2 Norm
        $tfidfVec = [];
        $sumSq = 0.0;
        foreach ($tfCounts as $term => $count) {
            $idf = $termsDict[$term][0];
            $val = (1.0 + log((float) $count)) * $idf;
            $tfidfVec[$term] = $val;
            $sumSq += $val * $val;
        }

        $norm = $sumSq > 0.0 ? sqrt($sumSq) : 1.0;
        foreach ($tfidfVec as $term => $val) {
            $tfidfVec[$term] = $val / $norm;
        }

        // Hitung skor LinearSVC Kategori (5 kelas)
        $katClasses = $bundle['kat_classes'];
        $katScores = $bundle['kat_intercept'];
        $termContribsPerClass = array_fill(0, count($katClasses), []);

        foreach ($tfidfVec as $term => $xVal) {
            $coefs = $termsDict[$term][1];
            foreach ($coefs as $cIdx => $coef) {
                $contrib = $xVal * $coef;
                $katScores[$cIdx] += $contrib;
                if ($contrib > 0.01) {
                    $termContribsPerClass[$cIdx][$term] = $contrib;
                }
            }
        }

        // Softmax probabilitas kategori
        $maxScore = max($katScores);
        $expScores = [];
        $expSum = 0.0;
        foreach ($katScores as $cIdx => $s) {
            $e = exp($s - $maxScore);
            $expScores[$cIdx] = $e;
            $expSum += $e;
        }

        $classProbs = [];
        $bestKatIdx = 0;
        $bestKatScore = -INF;
        foreach ($katClasses as $cIdx => $name) {
            if ($katScores[$cIdx] > $bestKatScore) {
                $bestKatScore = $katScores[$cIdx];
                $bestKatIdx = $cIdx;
            }
            $classProbs[$name] = round(($expScores[$cIdx] / $expSum) * 100, 2);
        }
        arsort($classProbs);

        $predictedCategory = $katClasses[$bestKatIdx];
        $confidenceSvm = $classProbs[$predictedCategory];

        // Ambil 6 n-gram penentu keputusan tertinggi
        $winningContribs = $termContribsPerClass[$bestKatIdx];
        arsort($winningContribs);
        $topTokens = [];
        foreach (array_slice($winningContribs, 0, 6, true) as $term => $weight) {
            $topTokens[] = [
                'term' => $term,
                'weight' => round($weight, 4),
            ];
        }

        // Hitung skor LinearSVC Urgensi (3 kelas)
        $urgClasses = $bundle['urg_classes'];
        $urgScores = $bundle['urg_intercept'];
        foreach ($tfidfVec as $term => $xVal) {
            $coefs = $termsDict[$term][2];
            foreach ($coefs as $uIdx => $coef) {
                $urgScores[$uIdx] += $xVal * $coef;
            }
        }

        $bestUrgIdx = 0;
        $bestUrgScore = -INF;
        foreach ($urgScores as $uIdx => $s) {
            if ($s > $bestUrgScore) {
                $bestUrgScore = $s;
                $bestUrgIdx = $uIdx;
            }
        }
        $svmUrgency = $urgClasses[$bestUrgIdx];

        // Aturan Heuristik Leksikon Keselamatan Publik (sesuai Bab 3.3 artikel)
        $heuristic = $this->evaluateSafetyHeuristic($cleanText);
        $finalUrgency = $heuristic['override'] ?? $svmUrgency;

        $opdInfo = self::OPD_MAPPING[$predictedCategory] ?? [
            'opd' => 'Sekretariat Daerah / Pengaduan Umum',
            'unit' => 'Subbagian Layanan Pengaduan',
            'sla' => '< 48 Jam',
            'kode' => 'SETDA-UMM',
        ];

        $latencyMs = round((hrtime(true) - $startNs) / 1_000_000, 2);

        // Opsional: Bridge ke endpoint IndoBERT live jika dikonfigurasi di .env
        $indobertResult = $this->queryOptionalIndoBert($cleanText);
        $engineName = $indobertResult !== null
            ? 'Dual-Track Live (Linear SVM + IndoBERT API)'
            : 'Linear SVM + TF-IDF (PHP Native)';

        return [
            'raw_text' => $rawText,
            'clean_text' => $cleanText,
            'replacements' => $trace['replacements'],
            'particles' => $trace['particles'],
            'predicted_category' => $indobertResult['category'] ?? $predictedCategory,
            'svm_category' => $predictedCategory,
            'predicted_urgency' => $finalUrgency,
            'svm_urgency' => $svmUrgency,
            'urgency_keywords' => $heuristic['matched_keywords'],
            'urgency_rule' => $heuristic['rule_label'],
            'confidence_svm' => $confidenceSvm,
            'class_scores' => $classProbs,
            'top_tokens' => $topTokens,
            'opd' => $opdInfo,
            'latency_ms' => $latencyMs,
            'model_engine' => $engineName,
            'indobert_live' => $indobertResult,
        ];
    }

    private function evaluateSafetyHeuristic(string $cleanText): array
    {
        $matchedHigh = [];
        foreach (self::KEYWORDS_TINGGI as $kw) {
            if (str_contains($cleanText, $kw)) {
                $matchedHigh[] = $kw;
            }
        }

        if (!empty($matchedHigh)) {
            return [
                'override' => 'Tinggi',
                'matched_keywords' => $matchedHigh,
                'rule_label' => 'Leksikon Darurat Keselamatan Publik',
            ];
        }

        $matchedLow = [];
        foreach (self::KEYWORDS_RENDAH as $kw) {
            if (str_contains($cleanText, $kw)) {
                $matchedLow[] = $kw;
            }
        }

        if (!empty($matchedLow)) {
            return [
                'override' => 'Rendah',
                'matched_keywords' => $matchedLow,
                'rule_label' => 'Leksikon Permohonan Rutin / Informasi',
            ];
        }

        return [
            'override' => null,
            'matched_keywords' => [],
            'rule_label' => 'Klasifikasi Bobot Linear SVM Urgensi',
        ];
    }

    private function queryOptionalIndoBert(string $cleanText): ?array
    {
        $endpoint = config('services.indobert.url');
        if (empty($endpoint)) {
            return null;
        }

        try {
            $t0 = hrtime(true);
            $response = Http::timeout(4)->post($endpoint, ['text' => $cleanText]);
            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && !empty($data['category'])) {
                    return [
                        'category' => $data['category'],
                        'confidence' => $data['confidence'] ?? null,
                        'latency_ms' => round((hrtime(true) - $t0) / 1_000_000, 2),
                    ];
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
