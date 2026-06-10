<?php

namespace App\Http\Controllers;

use App\Models\MasterDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterDocument::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('document_number', 'like', '%' . $request->search . '%')
                  ->orWhere('title', 'like', '%' . $request->search . '%')
                  ->orWhere('no_perubahan_cc', 'like', '%' . $request->search . '%')
                  ->orWhere('tempat_penyimpanan', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $documents = $query->orderBy('effective_date', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    public function import(Request $request)
    {
        // Only QA or Super Admin can import
        if (!in_array($request->user()->role, ['qa', 'superadmin'])) {
            abort(403, 'Unauthorized. Only QA and Super Admin can import documents.');
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls,txt',
            'type' => 'required|in:PROTAP,QMS,IK,SPESIFIKASI,CRF_CRE,PROTOKOL,LAPORAN_TAHUNAN',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());
        $type = $request->input('type');

        $rows = [];

        if (in_array($extension, ['xlsx', 'xls'])) {
            try {
                $spreadsheet = IOFactory::load($path);
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray(null, true, true, true);
                
                foreach ($data as $rowIndex => $row) {
                    // Skip the first 5 rows (headers, titles, blank rows)
                    if ($rowIndex < 6) {
                        continue;
                    }
                    if (empty($row['C'])) continue; // skip if document number (Column C) is empty
                    
                    $revVal = trim($row['D'] ?? '');
                    $perubahanVal = trim($row['E'] ?? '');
                    $revision = trim($revVal . ($perubahanVal !== '' ? ' - ' . $perubahanVal : ''));

                    $penggantiVal = trim($row['K'] ?? '');
                    $lampiranVal = trim($row['L'] ?? '');
                    $penggantiLampiran = trim($penggantiVal . ($lampiranVal !== '' ? ' - ' . $lampiranVal : ''));

                    $dateH = $this->formatDate($row['H'] ?? null);
                    $dateI = $this->formatDate($row['I'] ?? null);
                    $dateJ = $this->formatDate($row['J'] ?? null);
                    $reviewDates = array_values(array_filter([$dateH, $dateI, $dateJ]));

                    $rows[] = [
                        'excel_no'           => trim($row['A'] ?? ''),
                        'title'              => trim($row['B'] ?? ''),
                        'document_number'    => trim($row['C']),
                        'revision'           => $revision,
                        'no_perubahan_cc'    => trim($row['F'] ?? ''),
                        'effective_date'     => $this->formatDate($row['G'] ?? null),
                        'tgl_review'         => $reviewDates[0] ?? null,
                        'tgl_review_2'       => $reviewDates[1] ?? null,
                        'pengganti_lampiran' => $penggantiLampiran,
                        'no_catatan_mutu'    => trim($row['M'] ?? ''),
                        'dokumen_terkait'    => trim($row['N'] ?? ''),
                        'tgl_sosialisasi'    => $this->formatDate($row['O'] ?? null),
                        'distribusi'         => trim($row['P'] ?? ''),
                        'no_pemusnahan'      => trim($row['Q'] ?? ''),
                        'tgl_pemusnahan'     => $this->formatDate($row['R'] ?? null),
                        'tempat_penyimpanan' => trim($row['S'] ?? ''),
                        'type'               => $type,
                        'status'             => 'Active',
                    ];
                }
            } catch (\Exception $e) {
                return back()->withErrors(['file' => 'Error reading Excel file: ' . $e->getMessage()]);
            }
        } else {
            // Assume CSV
            if (($handle = fopen($path, 'r')) !== false) {
                $rowCount = 0;
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $rowCount++;
                    // Skip the first 5 rows
                    if ($rowCount < 6) {
                        continue;
                    }
                    if (empty($data[2])) continue; // Document number is Column C (index 2)
                    
                    $revVal = trim($data[3] ?? '');
                    $perubahanVal = trim($data[4] ?? '');
                    $revision = trim($revVal . ($perubahanVal !== '' ? ' - ' . $perubahanVal : ''));

                    $penggantiVal = trim($data[10] ?? '');
                    $lampiranVal = trim($data[11] ?? '');
                    $penggantiLampiran = trim($penggantiVal . ($lampiranVal !== '' ? ' - ' . $lampiranVal : ''));

                    $dateH = $this->formatDate($data[7] ?? null);
                    $dateI = $this->formatDate($data[8] ?? null);
                    $dateJ = $this->formatDate($data[9] ?? null);
                    $reviewDates = array_values(array_filter([$dateH, $dateI, $dateJ]));

                    $rows[] = [
                        'excel_no'           => trim($data[0] ?? ''),
                        'title'              => trim($data[1] ?? ''),
                        'document_number'    => trim($data[2]),
                        'revision'           => $revision,
                        'no_perubahan_cc'    => trim($data[5] ?? ''),
                        'effective_date'     => $this->formatDate($data[6] ?? null),
                        'tgl_review'         => $reviewDates[0] ?? null,
                        'tgl_review_2'       => $reviewDates[1] ?? null,
                        'pengganti_lampiran' => $penggantiLampiran,
                        'no_catatan_mutu'    => trim($data[12] ?? ''),
                        'dokumen_terkait'    => trim($data[13] ?? ''),
                        'tgl_sosialisasi'    => $this->formatDate($data[14] ?? null),
                        'distribusi'         => trim($data[15] ?? ''),
                        'no_pemusnahan'      => trim($data[16] ?? ''),
                        'tgl_pemusnahan'     => $this->formatDate($data[17] ?? null),
                        'tempat_penyimpanan' => trim($data[18] ?? ''),
                        'type'               => $type,
                        'status'             => 'Active',
                    ];
                }
                fclose($handle);
            }
        }

        if (empty($rows)) {
            return back()->withErrors(['file' => 'The file does not contain any valid data rows.']);
        }

        // Validate rows mapping
        foreach ($rows as $index => $row) {
            if (empty($row['document_number'])) {
                return back()->withErrors(['file' => "Row " . ($index + 2) . ": Document number (Column C) is required."]);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                MasterDocument::updateOrCreate(
                    ['document_number' => $row['document_number']],
                    [
                        'excel_no'           => $row['excel_no'],
                        'title'              => $row['title'],
                        'type'               => $row['type'],
                        'revision'           => $row['revision'],
                        'no_perubahan_cc'    => $row['no_perubahan_cc'],
                        'effective_date'     => $row['effective_date'],
                        'tgl_review'         => $row['tgl_review'],
                        'tgl_review_2'       => $row['tgl_review_2'],
                        'pengganti_lampiran' => $row['pengganti_lampiran'],
                        'no_catatan_mutu'    => $row['no_catatan_mutu'],
                        'dokumen_terkait'    => $row['dokumen_terkait'],
                        'tgl_sosialisasi'    => $row['tgl_sosialisasi'],
                        'distribusi'         => $row['distribusi'],
                        'no_pemusnahan'      => $row['no_pemusnahan'],
                        'tgl_pemusnahan'     => $row['tgl_pemusnahan'],
                        'tempat_penyimpanan' => $row['tempat_penyimpanan'],
                        'status'             => $row['status'],
                    ]
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'Failed to save documents: ' . $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Documents imported successfully!');
    }

    public function syncSheets(Request $request)
    {
        // Only QA or Super Admin can sync
        if (!in_array($request->user()->role, ['qa', 'superadmin'])) {
            abort(403, 'Unauthorized. Only QA and Super Admin can sync documents.');
        }

        $request->validate([
            'type' => 'required|in:PROTAP,QMS,IK,SPESIFIKASI,CRF_CRE,PROTOKOL,LAPORAN_TAHUNAN',
        ]);

        $type = $request->input('type');
        $spreadsheetId = \App\Models\Setting::getValue('google_spreadsheet_id', env('GOOGLE_SPREADSHEET_ID'));
        $range = env('GOOGLE_SPREADSHEET_RANGE', 'A6:S');

        if (empty($spreadsheetId)) {
            return back()->withErrors(['sync' => 'GOOGLE_SPREADSHEET_ID is not configured.']);
        }

        try {
            $client = $this->getGoogleClient();
            app()->instance(\Google\Client::class, $client);
            $service = app(\Google\Service\Sheets::class);
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values)) {
                return back()->withErrors(['sync' => 'The Google Sheet range contains no data.']);
            }

            $rows = [];
            foreach ($values as $row) {
                // Document number (Column C / index 2) is required
                if (empty($row[2])) continue;

                $revVal = trim($row[3] ?? '');
                $perubahanVal = trim($row[4] ?? '');
                $revision = trim($revVal . ($perubahanVal !== '' ? ' - ' . $perubahanVal : ''));

                $penggantiVal = trim($row[10] ?? '');
                $lampiranVal = trim($row[11] ?? '');
                $penggantiLampiran = trim($penggantiVal . ($lampiranVal !== '' ? ' - ' . $lampiranVal : ''));

                $dateH = $this->formatDate($row[7] ?? null);
                $dateI = $this->formatDate($row[8] ?? null);
                $dateJ = $this->formatDate($row[9] ?? null);
                $reviewDates = array_values(array_filter([$dateH, $dateI, $dateJ]));

                $rows[] = [
                    'excel_no'           => trim($row[0] ?? ''),
                    'title'              => trim($row[1] ?? ''),
                    'document_number'    => trim($row[2]),
                    'revision'           => $revision,
                    'no_perubahan_cc'    => trim($row[5] ?? ''),
                    'effective_date'     => $this->formatDate($row[6] ?? null),
                    'tgl_review'         => $reviewDates[0] ?? null,
                    'tgl_review_2'       => $reviewDates[1] ?? null,
                    'pengganti_lampiran' => $penggantiLampiran,
                    'no_catatan_mutu'    => trim($row[12] ?? ''),
                    'dokumen_terkait'    => trim($row[13] ?? ''),
                    'tgl_sosialisasi'    => $this->formatDate($row[14] ?? null),
                    'distribusi'         => trim($row[15] ?? ''),
                    'no_pemusnahan'      => trim($row[16] ?? ''),
                    'tgl_pemusnahan'     => $this->formatDate($row[17] ?? null),
                    'tempat_penyimpanan' => trim($row[18] ?? ''),
                    'type'               => $type,
                    'status'             => 'Active',
                ];
            }

            if (empty($rows)) {
                return back()->withErrors(['sync' => 'No valid data rows found in the sheet (Column C/No Dokumen was empty).']);
            }

            DB::beginTransaction();
            foreach ($rows as $row) {
                MasterDocument::updateOrCreate(
                    ['document_number' => $row['document_number']],
                    [
                        'excel_no'           => $row['excel_no'],
                        'title'              => $row['title'],
                        'type'               => $row['type'],
                        'revision'           => $row['revision'],
                        'no_perubahan_cc'    => $row['no_perubahan_cc'],
                        'effective_date'     => $row['effective_date'],
                        'tgl_review'         => $row['tgl_review'],
                        'tgl_review_2'       => $row['tgl_review_2'],
                        'pengganti_lampiran' => $row['pengganti_lampiran'],
                        'no_catatan_mutu'    => $row['no_catatan_mutu'],
                        'dokumen_terkait'    => $row['dokumen_terkait'],
                        'tgl_sosialisasi'    => $row['tgl_sosialisasi'],
                        'distribusi'         => $row['distribusi'],
                        'no_pemusnahan'      => $row['no_pemusnahan'],
                        'tgl_pemusnahan'     => $row['tgl_pemusnahan'],
                        'tempat_penyimpanan' => $row['tempat_penyimpanan'],
                        'status'             => $row['status'],
                    ]
                );
            }
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['sync' => 'Google Sheets Sync failed: ' . $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Documents synced from Google Sheets successfully!');
    }

    private function getGoogleClient()
    {
        $client = new \Google\Client();
        
        // Disable SSL certificate verification in local environment to bypass Laragon cURL error 77 issues
        if (app()->environment('local')) {
            $guzzleClient = new \GuzzleHttp\Client([
                'verify' => false,
            ]);
            $client->setHttpClient($guzzleClient);
        }
        
        $jsonKey = \App\Models\Setting::getValue('google_service_account_json', env('GOOGLE_SERVICE_ACCOUNT_JSON'));
        $jsonFile = env('GOOGLE_SERVICE_ACCOUNT_FILE');
        
        if (!empty($jsonKey)) {
            $client->setAuthConfig(json_decode($jsonKey, true));
        } elseif (!empty($jsonFile)) {
            $path = base_path($jsonFile);
            if (file_exists($path)) {
                $client->setAuthConfig($path);
            } else {
                throw new \Exception("Google Service Account credentials file not found at: " . $path);
            }
        } else {
            $path = storage_path('app/google-credentials.json');
            if (file_exists($path)) {
                $client->setAuthConfig($path);
            } else {
                throw new \Exception('Google Service Account credentials not found. Set GOOGLE_SERVICE_ACCOUNT_JSON or GOOGLE_SERVICE_ACCOUNT_FILE in .env, or place file at storage/app/google-credentials.json');
            }
        }
        
        $client->addScope(\Google\Service\Sheets::SPREADSHEETS_READONLY);
        return $client;
    }

    private function formatDate($value)
    {
        if (empty($value) || trim($value) === '' || trim($value) === '-') {
            return null;
        }
        
        // If it's a numeric date from Excel
        if (is_numeric($value)) {
            try {
                $unixTimestamp = (\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                return date('Y-m-d', $unixTimestamp);
            } catch (\Exception $e) {
                return null;
            }
        }

        // Parse formatted string (e.g. YYYY-MM-DD, DD/MM/YYYY)
        $date = date_create(trim($value));
        if ($date !== false) {
            return date_format($date, 'Y-m-d');
        }

        return null;
    }
}
