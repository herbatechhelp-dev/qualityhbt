<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Users
        \App\Models\User::firstOrCreate(
            ['email' => 'initiator@qms.com'],
            [
                'name' => 'Initiator User',
                'password' => 'password',
                'role' => 'initiator',
            ]
        );

        \App\Models\User::firstOrCreate(
            ['email' => 'qa@qms.com'],
            [
                'name' => 'QA Officer',
                'password' => 'password',
                'role' => 'qa',
            ]
        );

        \App\Models\User::firstOrCreate(
            ['email' => 'superadmin@qms.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'superadmin',
            ]
        );

        // Seed default Settings
        \App\Models\Setting::firstOrCreate(['key' => 'app_name'], ['value' => 'QMS Portal']);
        \App\Models\Setting::firstOrCreate(['key' => 'app_logo'], ['value' => '✨ QMS Portal']);
        \App\Models\Setting::firstOrCreate(['key' => 'google_spreadsheet_id'], ['value' => '1MPuQ48i_Ll2PKljSmwqspv_s17fyt-35']);


        // Seed Master Documents
        $now = now();
        $docs = [
            [
                'excel_no' => '1',
                'document_number' => 'SOP-QA-001',
                'title' => 'Prosedur Tetap Penanganan Deviasi Mutu',
                'type' => 'PROTAP',
                'revision' => '02',
                'effective_date' => '2026-01-15',
                'tempat_penyimpanan' => 'Lemari QA - A1',
                'status' => 'Active',
            ],
            [
                'excel_no' => '2',
                'document_number' => 'SOP-PROD-004',
                'title' => 'Instruksi Kerja Pembersihan Mesin Cetak Tablet',
                'type' => 'IK',
                'revision' => '01',
                'effective_date' => '2025-11-20',
                'tempat_penyimpanan' => 'Gedung Produksi - Lt 1',
                'status' => 'Active',
            ],
            [
                'excel_no' => '3',
                'document_number' => 'SPEC-MAT-012',
                'title' => 'Spesifikasi Bahan Baku Paracetamol',
                'type' => 'SPESIFIKASI',
                'revision' => '00',
                'effective_date' => '2026-03-01',
                'tempat_penyimpanan' => 'Lemari QC - B3',
                'status' => 'Active',
            ],
            [
                'excel_no' => '4',
                'document_number' => 'QMS-MAN-001',
                'title' => 'Manual Mutu QMS Terintegrasi',
                'type' => 'QMS',
                'revision' => '03',
                'effective_date' => '2026-05-10',
                'tempat_penyimpanan' => 'Ruang QA - C1',
                'status' => 'Active',
            ],
        ];

        foreach ($docs as $doc) {
            \App\Models\MasterDocument::firstOrCreate(
                ['document_number' => $doc['document_number']],
                $doc
            );
        }
    }
}
