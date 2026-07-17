<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MasterDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleSheetsSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['GOOGLE_SPREADSHEET_ID'] = 'mocked-spreadsheet-id';
        $_SERVER['GOOGLE_SPREADSHEET_ID'] = 'mocked-spreadsheet-id';
        $_ENV['GOOGLE_SPREADSHEET_RANGE'] = 'A6:S';
        $_SERVER['GOOGLE_SPREADSHEET_RANGE'] = 'A6:S';
    }

    public function test_only_qa_can_sync_google_sheets()
    {
        $initiator = User::factory()->create(['role' => 'initiator']);

        $response = $this->actingAs($initiator)->post(route('documents.sync-sheets'), [
            'type' => 'PROTAP',
        ]);

        $response->assertStatus(403);
    }

    public function test_sync_sheets_handles_missing_configuration()
    {
        $qa = User::factory()->create(['role' => 'qa']);

        // Set spreadsheet ID empty
        $_ENV['GOOGLE_SPREADSHEET_ID'] = '';
        $_SERVER['GOOGLE_SPREADSHEET_ID'] = '';

        $response = $this->actingAs($qa)->post(route('documents.sync-sheets'), [
            'type' => 'PROTAP',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('sync');
    }

    public function test_sync_sheets_successful_mapping_with_16_columns()
    {
        $qa = User::factory()->create(['role' => 'qa']);

        // Set mock client credentials JSON so getGoogleClient doesn't throw file-not-found exception
        $fakeJson = json_encode([
            'type' => 'service_account',
            'project_id' => 'mock-project',
            'private_key_id' => 'mock-key-id',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nMockKey\n-----END PRIVATE KEY-----\n",
            'client_email' => 'mock-email@mock.iam.gserviceaccount.com',
            'client_id' => 'mock-client-id',
        ]);
        $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'] = $fakeJson;
        $_SERVER['GOOGLE_SERVICE_ACCOUNT_JSON'] = $fakeJson;

        // Create Mocks
        $sheetsMock = \Mockery::mock(\Google\Service\Sheets::class);
        $valuesMock = \Mockery::mock();
        $responseMock = \Mockery::mock();

        $sheetsMock->spreadsheets_values = $valuesMock;
        $valuesMock->shouldReceive('get')
            ->once()
            ->with('mocked-spreadsheet-id', 'A1:S')
            ->andReturn($responseMock);

        $responseMock->shouldReceive('getValues')
            ->once()
            ->andReturn([
                ['Title: Master Protap List'],
                [''],
                [''],
                [''],
                [
                    'NO', 'JUDUL', 'NO DOKUMEN', 'REV', 'PERUBAHAN', 
                    'NO PERUBAHAN / CR', 'TGL BERLAKU', 'TGL REVIEW', 'TGL REVIEW I', 
                    'TGL REVIEW II', 'PENGGANTI', 'LAMPIRAN', 'NO. CATATAN MUTU', 
                    'DOKUMEN TERKAIT', 'TGL SOSIALISASI', 'DISTRIBUSI', 'NO PEMUSNAHAN', 
                    'TGL PEMUSNAHAN', 'TEMPAT PENYIMPANAN'
                ],
                [
                    '1', 
                    'Prosedur Stabilitas Cloud', 
                    'DOC-CLD-999', 
                    '02', 
                    'Perbaikan Penomoran',
                    'CC-2026-999', 
                    '2026-06-09', 
                    '2028-06-09', 
                    '2029-06-09', 
                    '',
                    'Lampiran X', 
                    'Form Cloud',
                    'CM-999', 
                    'DOC-CLD-998', 
                    '2026-06-10', 
                    'QA;QC', 
                    'PM-999', 
                    '2031-06-09', 
                    'Rack A1'
                ]
            ]);

        $this->app->instance(\Google\Service\Sheets::class, $sheetsMock);

        // Run sync Sheets
        $response = $this->actingAs($qa)->post(route('documents.sync-sheets'), [
            'type' => 'PROTAP',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify it exists in database with correctly parsed/merged attributes
        $this->assertDatabaseHas('master_documents', [
            'excel_no' => '1',
            'title' => 'Prosedur Stabilitas Cloud',
            'document_number' => 'DOC-CLD-999',
            'revision' => '02 - Perbaikan Penomoran', // Merged D & E
            'no_perubahan_cc' => 'CC-2026-999',
            'effective_date' => '2026-06-09',
            'tgl_review' => '2028-06-09',
            'tgl_review_2' => '2029-06-09',
            'pengganti_lampiran' => 'Lampiran X - Form Cloud', // Merged K & L
            'no_catatan_mutu' => 'CM-999',
            'dokumen_terkait' => 'DOC-CLD-998',
            'tgl_sosialisasi' => '2026-06-10',
            'distribusi' => 'QA;QC',
            'no_pemusnahan' => 'PM-999',
            'tgl_pemusnahan' => '2031-06-09',
            'tempat_penyimpanan' => 'Rack A1',
            'type' => 'PROTAP',
            'status' => 'Active',
        ]);

        // Clean up environment variables
        $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'] = '';
        $_SERVER['GOOGLE_SERVICE_ACCOUNT_JSON'] = '';
    }
}
