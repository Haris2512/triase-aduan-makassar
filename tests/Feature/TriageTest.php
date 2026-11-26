<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Services\ComplaintTriageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriageTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_page_loads_cleanly(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('TriasePublik Makassar');
        $response->assertSee('Portal Pengaduan');
    }

    public function test_native_php_svm_matches_trained_predictions(): void
    {
        $service = app(ComplaintTriageService::class);

        $resLamp = $service->classify('Lampux di lorong 5 mati mi tawwa gelap gulita rawan begal');
        $this->assertSame('Lampu Jalan', $resLamp['predicted_category']);
        $this->assertSame('Tinggi', $resLamp['predicted_urgency']);
        $this->assertSame('Dinas Perhubungan (Dishub) Kota Makassar', $resLamp['opd']['opd']);

        $resTree = $service->classify('Pohon besar tumbang timpa kabel listrik berasap bahaya');
        $this->assertSame('Pangkas/Tebang Pohon', $resTree['predicted_category']);
        $this->assertSame('Tinggi', $resTree['predicted_urgency']);
    }

    public function test_submit_complaint_saves_and_redirects(): void
    {
        $response = $this->post('/triase', [
            'kecamatan' => 'Tamalanrea',
            'kelurahan' => 'Tamalanrea Indah',
            'reporter_name' => 'Warga Uji',
            'pesan_aduan' => 'Jalanan berlubang besar depan kampus sangat berbahaya bagi pengendara',
            'simpan_antrean' => '1',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('complaints', [
            'kecamatan' => 'Tamalanrea',
            'source' => 'portal_warga',
        ]);
    }

    public function test_queue_and_research_pages_render(): void
    {
        $complaint = Complaint::create([
            'ticket_code' => 'MKS-99999',
            'reporter_name' => 'Warga',
            'kecamatan' => 'Rappocini',
            'kelurahan' => 'Balla Parang',
            'raw_text' => 'Saluran drainase tersumbat bau sekali',
            'clean_text' => 'saluran drainase tersumbat bau sekali',
            'predicted_category' => 'Drainase',
            'predicted_urgency' => 'Sedang',
            'assigned_opd' => 'Dinas Pekerjaan Umum (DPU) Kota Makassar',
            'assigned_unit' => 'Bidang Drainase dan PSDA',
            'sla_target' => '< 12 Jam',
            'confidence_svm' => 94.5,
            'latency_ms' => 0.72,
            'status' => 'Terdisposisi ke OPD',
            'source' => 'portal_warga',
        ]);

        $this->get('/antrean')->assertStatus(200)->assertSee('MKS-99999');
        $this->get("/antrean/{$complaint->id}")->assertStatus(200)->assertSee('Saluran drainase tersumbat');
        $this->get('/riset')->assertStatus(200)->assertSee('95,70%');

        $this->patch("/antrean/{$complaint->id}/status", ['status' => 'Selesai'])->assertRedirect();
        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status' => 'Selesai']);
    }
}
