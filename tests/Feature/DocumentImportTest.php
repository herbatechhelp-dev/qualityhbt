<?php

namespace Tests\Feature;

use App\Models\MasterDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DocumentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_qa_can_import_csv_with_19_columns()
    {
        $qa = User::factory()->create(['role' => 'qa']);

        // 5 header/title rows to be skipped, followed by the main data row at line 6
        $headerRows = "Title Row 1\nTitle Row 2\nTitle Row 3\nTitle Row 4\nTitle Row 5\n";
        // 19 columns structure:
        // A: NO | B: JUDUL | C: NO DOKUMEN | D: REV | E: PERUBAHAN | F: NO PERUBAHAN / CR | G: TGL BERLAKU | H: TGL REVIEW | I: TGL REVIEW I | J: TGL REVIEW II | K: PENGGANTI | L: LAMPIRAN | M: NO. CATATAN MUTU | N: DOKUMEN TERKAIT | O: TGL SOSIALISASI | P: DISTRIBUSI | Q: NO PEMUSNAHAN | R: TGL PEMUSNAHAN | S: TEMPAT PENYIMPANAN
        $dataRow = "1,Prosedur Sanitasi Ruang Produksi,DOC-PRO-001,01,Penambahan Ruang Lingkup,CC-2026-001,2026-06-01,2028-06-01,2029-06-01,,Lampiran B,Form Utama,CM-001,DOC-PRO-002,2026-06-05,Distribusi QC;QA,PM-001,2030-06-01,Gudang Dokumen A\n";
        
        $csvContent = $headerRows . $dataRow;
        $file = UploadedFile::fake()->createWithContent('master_list.csv', $csvContent);

        $response = $this->actingAs($qa)->post(route('documents.import'), [
            'file' => $file,
            'type' => 'PROTAP',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('master_documents', [
            'excel_no' => '1',
            'title' => 'Prosedur Sanitasi Ruang Produksi',
            'document_number' => 'DOC-PRO-001',
            'revision' => '01 - Penambahan Ruang Lingkup', // Merged D & E
            'no_perubahan_cc' => 'CC-2026-001', // F
            'effective_date' => '2026-06-01', // G
            'tgl_review' => '2028-06-01', // H
            'tgl_review_2' => '2029-06-01', // I
            'pengganti_lampiran' => 'Lampiran B - Form Utama', // Merged K & L
            'no_catatan_mutu' => 'CM-001', // M
            'dokumen_terkait' => 'DOC-PRO-002', // N
            'tgl_sosialisasi' => '2026-06-05', // O
            'distribusi' => 'Distribusi QC;QA', // P
            'no_pemusnahan' => 'PM-001', // Q
            'tgl_pemusnahan' => '2030-06-01', // R
            'tempat_penyimpanan' => 'Gudang Dokumen A', // S
            'type' => 'PROTAP',
            'status' => 'Active',
        ]);
    }

    public function test_non_qa_cannot_import_csv()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);

        $headerRows = "Title Row 1\nTitle Row 2\nTitle Row 3\nTitle Row 4\nTitle Row 5\n";
        $dataRow = "1,Prosedur Sanitasi Ruang Produksi,DOC-PRO-001,01,Penambahan Ruang Lingkup,CC-2026-001,2026-06-01,2028-06-01,2029-06-01,,Lampiran B,Form Utama,CM-001,DOC-PRO-002,2026-06-05,Distribusi QC;QA,PM-001,2030-06-01,Gudang Dokumen A\n";
        
        $csvContent = $headerRows . $dataRow;
        $file = UploadedFile::fake()->createWithContent('master_list.csv', $csvContent);

        $response = $this->actingAs($initiator)->post(route('documents.import'), [
            'file' => $file,
            'type' => 'PROTAP',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('master_documents', 0);
    }
}
