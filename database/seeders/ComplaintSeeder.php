<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Services\ComplaintTriageService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComplaintSeeder extends Seeder
{
    public function run(): void
    {
        if (Complaint::count() > 0) {
            return;
        }

        $seedPath = storage_path('app/ml/test_aduan_seed.json');
        if (!file_exists($seedPath)) {
            return;
        }

        $records = json_decode(file_get_contents($seedPath), true);
        if (!is_array($records)) {
            return;
        }

        $now = now()->toDateTimeString();
        $batch = [];

        foreach ($records as $idx => $row) {
            $kat = $row['kategori_prediksi'];
            $opdInfo = ComplaintTriageService::OPD_MAPPING[$kat] ?? [
                'opd' => 'Sekretariat Daerah',
                'unit' => 'Layanan Pengaduan',
                'sla' => '< 48 Jam',
            ];

            $reportedAt = null;
            if (!empty($row['waktu_aduan'])) {
                try {
                    $reportedAt = Carbon::createFromFormat('d/m/Y, H.i.s', $row['waktu_aduan'])->toDateTimeString();
                } catch (\Throwable) {
                    $reportedAt = $now;
                }
            }

            $status = match (true) {
                $row['urgensi_prediksi'] === 'Tinggi' && $idx % 3 === 0 => 'Dalam Penanganan',
                $idx % 5 === 0 => 'Selesai',
                default => 'Terdisposisi ke OPD',
            };

            $batch[] = [
                'ticket_code' => $row['ticket_code'],
                'reporter_name' => 'Arsip Warga Makassar',
                'kecamatan' => $row['kecamatan'] ?: 'Makassar',
                'kelurahan' => $row['kelurahan'] ?: '-',
                'raw_text' => $row['pesan_aduan'],
                'clean_text' => $row['clean_text'],
                'predicted_category' => $kat,
                'actual_category' => $row['kategori_aktual'],
                'predicted_urgency' => $row['urgensi_prediksi'],
                'actual_urgency' => $row['urgensi_aktual'],
                'assigned_opd' => $opdInfo['opd'],
                'assigned_unit' => $opdInfo['unit'],
                'sla_target' => $opdInfo['sla'],
                'confidence_svm' => $row['confidence'],
                'latency_ms' => 0.85,
                'model_engine' => 'Linear SVM + TF-IDF (Benchmark Uji)',
                'matched_tokens' => json_encode([]),
                'class_scores' => json_encode([]),
                'status' => $status,
                'source' => 'dataset_uji_pemkot',
                'reported_at' => $reportedAt ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 250) {
                DB::table('complaints')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('complaints')->insert($batch);
        }
    }
}
