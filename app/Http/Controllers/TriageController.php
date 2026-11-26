<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Services\ComplaintTriageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TriageController extends Controller
{
    public function __construct(
        private readonly ComplaintTriageService $triageService
    ) {}

    public function index(Request $request)
    {
        $kecamatans = ComplaintTriageService::KECAMATAN_LIST;
        $opdMapping = ComplaintTriageService::OPD_MAPPING;

        $recentUrgent = Complaint::query()
            ->where('predicted_urgency', 'Tinggi')
            ->latest('id')
            ->take(4)
            ->get();

        $totalCount = Complaint::count();
        $urgentCount = Complaint::where('predicted_urgency', 'Tinggi')->count();
        $portalCount = Complaint::where('source', 'portal_warga')->count();

        $result = session('triage_result');
        $savedComplaint = session('saved_complaint_id')
            ? Complaint::find(session('saved_complaint_id'))
            : null;

        return view('portal', compact(
            'kecamatans',
            'opdMapping',
            'recentUrgent',
            'totalCount',
            'urgentCount',
            'portalCount',
            'result',
            'savedComplaint'
        ));
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'pesan_aduan' => ['required', 'string', 'min:8', 'max:2000'],
            'kecamatan' => ['required', 'string', 'max:80'],
            'kelurahan' => ['nullable', 'string', 'max:80'],
            'reporter_name' => ['nullable', 'string', 'max:100'],
            'simpan_antrean' => ['nullable', 'boolean'],
        ], [
            'pesan_aduan.required' => 'Tuliskan isi laporan atau keluhan warga terlebih dahulu.',
            'pesan_aduan.min' => 'Pesan aduan minimal 8 karakter agar konteks masalah dapat dikenali.',
        ]);

        $result = $this->triageService->classify($validated['pesan_aduan']);
        $saveToQueue = $request->boolean('simpan_antrean', true);

        $savedId = null;
        if ($saveToQueue) {
            $nextNumber = (int) (Complaint::max('id') ?? 0) + 20001;
            $complaint = Complaint::create([
                'ticket_code' => 'MKS-' . $nextNumber,
                'reporter_name' => $validated['reporter_name'] ?: 'Warga Pelapor (Portal Web)',
                'kecamatan' => $validated['kecamatan'],
                'kelurahan' => $validated['kelurahan'] ?: '-',
                'raw_text' => $result['raw_text'],
                'clean_text' => $result['clean_text'] ?: $result['raw_text'],
                'predicted_category' => $result['predicted_category'],
                'predicted_urgency' => $result['predicted_urgency'],
                'assigned_opd' => $result['opd']['opd'],
                'assigned_unit' => $result['opd']['unit'],
                'sla_target' => $result['opd']['sla'],
                'confidence_svm' => $result['confidence_svm'],
                'latency_ms' => $result['latency_ms'],
                'model_engine' => $result['model_engine'],
                'matched_tokens' => $result['top_tokens'],
                'class_scores' => $result['class_scores'],
                'status' => 'Terdisposisi ke OPD',
                'source' => 'portal_warga',
                'reported_at' => now(),
            ]);
            $savedId = $complaint->id;
            $result['ticket_code'] = $complaint->ticket_code;
        } else {
            $result['ticket_code'] = 'SIMULASI-LIVE';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'saved_id' => $savedId,
                'result' => $result,
            ]);
        }

        return redirect()
            ->route('portal.index')
            ->with('triage_result', $result)
            ->with('saved_complaint_id', $savedId)
            ->withInput();
    }

    public function queue(Request $request)
    {
        $filters = $request->only(['q', 'kecamatan', 'kategori', 'urgensi', 'status', 'source']);

        $complaints = Complaint::query()
            ->filter($filters)
            ->orderByRaw("CASE WHEN source = 'portal_warga' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE predicted_urgency WHEN 'Tinggi' THEN 0 WHEN 'Sedang' THEN 1 ELSE 2 END")
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summaryByCategory = Complaint::select('predicted_category', DB::raw('count(*) as total'))
            ->groupBy('predicted_category')
            ->pluck('total', 'predicted_category')
            ->toArray();

        $summaryByUrgency = Complaint::select('predicted_urgency', DB::raw('count(*) as total'))
            ->groupBy('predicted_urgency')
            ->pluck('total', 'predicted_urgency')
            ->toArray();

        $kecamatans = Complaint::select('kecamatan')
            ->distinct()
            ->orderBy('kecamatan')
            ->pluck('kecamatan');

        $categories = array_keys(ComplaintTriageService::OPD_MAPPING);
        $urgencies = ['Tinggi', 'Sedang', 'Rendah'];
        $statuses = ['Menunggu Verifikasi', 'Terdisposisi ke OPD', 'Dalam Penanganan', 'Selesai'];

        return view('queue', compact(
            'complaints',
            'filters',
            'summaryByCategory',
            'summaryByUrgency',
            'kecamatans',
            'categories',
            'urgencies',
            'statuses'
        ));
    }

    public function show(Complaint $complaint)
    {
        $liveAnalysis = $this->triageService->classify($complaint->raw_text);
        return view('detail', compact('complaint', 'liveAnalysis'));
    }

    public function updateStatus(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Menunggu Verifikasi,Terdisposisi ke OPD,Dalam Penanganan,Selesai'],
        ]);

        $complaint->update(['status' => $validated['status']]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $complaint->status]);
        }

        return back()->with('status_updated', "Status tiket {$complaint->ticket_code} diperbarui menjadi {$complaint->status}.");
    }

    public function research()
    {
        $datasetStats = [
            ['kategori' => 'Lampu Jalan (PJU)', 'opd' => 'Dishub', 'jumlah' => 10630, 'persen' => 70.44, 'test' => 1063, 'svm_f1' => 99.48, 'bert_f1' => 99.29],
            ['kategori' => 'Drainase / Got', 'opd' => 'DPU', 'jumlah' => 1398, 'persen' => 9.26, 'test' => 140, 'svm_f1' => 91.91, 'bert_f1' => 93.53],
            ['kategori' => 'Pangkas/Tebang Pohon', 'opd' => 'DLH', 'jumlah' => 1253, 'persen' => 8.30, 'test' => 125, 'svm_f1' => 97.62, 'bert_f1' => 98.39],
            ['kategori' => 'Sampah & Kebersihan', 'opd' => 'DLH', 'jumlah' => 962, 'persen' => 6.38, 'test' => 96, 'svm_f1' => 97.89, 'bert_f1' => 93.88],
            ['kategori' => 'Jalan Rusak', 'opd' => 'DPU', 'jumlah' => 840, 'persen' => 5.57, 'test' => 84, 'svm_f1' => 90.06, 'bert_f1' => 93.41],
        ];

        $errorCases = [
            [
                'teks' => 'Pemasangan paving & drainase saluran air belum ada jalanan becek',
                'aktual' => 'Jalan Rusak',
                'svm' => 'Drainase',
                'indobert' => 'Jalan Rusak',
                'analisis' => 'SVM terjebak bobot kata "drainase" dan "saluran air". IndoBERT memahami inti permohonan warga adalah pengerasan paving jalan.',
            ],
            [
                'teks' => 'Bantuan bersihkan tumpukan sampah di kanal veteran air terhambat',
                'aktual' => 'Drainase',
                'svm' => 'Sampah',
                'indobert' => 'Drainase',
                'analisis' => 'SVM terkecoh kemunculan kata "sampah". IndoBERT mengenali entitas "kanal veteran" sebagai saluran air makro kewenangan DPU.',
            ],
        ];

        return view('research', compact('datasetStats', 'errorCases'));
    }

    public function normalizePreview(Request $request)
    {
        $text = (string) $request->input('text', '');
        if (trim($text) === '') {
            return response()->json(['clean_text' => '', 'replacements' => [], 'particles' => []]);
        }

        return response()->json($this->triageService->normalizeWithTrace($text));
    }
}
