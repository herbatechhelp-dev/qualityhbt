<!DOCTYPE html>
@php
    $logoType = \App\Models\Setting::getValue('print_logo_type', 'text');
    $logoText = \App\Models\Setting::getValue('print_company_name', 'HERBATECH');
    $logoPath = \App\Models\Setting::getValue('print_logo_path');
@endphp
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak CRA - {{ $changeRequest->cr_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 12mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        
        /* Print helpers */
        .page {
            page-break-after: always;
            position: relative;
            min-height: 265mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        .page:last-child {
            page-break-after: avoid;
        }

        /* Stamps */
        .master-copy-stamp {
            border: 3px double #3b82f6;
            color: #3b82f6;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 12px;
            display: inline-block;
            transform: rotate(-3deg);
            opacity: 0.85;
            position: absolute;
            bottom: 15px;
            left: 5px;
        }
        .master-copy-stamp-small {
            border: 2px double #3b82f6;
            color: #3b82f6;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2px 8px;
            display: inline-block;
            transform: rotate(-3deg);
            opacity: 0.85;
            position: absolute;
            bottom: 5px;
            left: 5px;
        }
        .master-stamp {
            border: 3px double #ef4444;
            color: #ef4444;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 12px;
            display: inline-block;
            transform: rotate(2deg);
            opacity: 0.85;
            position: absolute;
            bottom: 15px;
            right: 120px;
        }
        .master-stamp-small {
            border: 2px double #ef4444;
            color: #ef4444;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2px 8px;
            display: inline-block;
            transform: rotate(2deg);
            opacity: 0.85;
            position: absolute;
            bottom: 5px;
            right: 120px;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
        }
        .logo-cell {
            width: 25%;
            text-align: center;
        }
        .logo-text {
            font-weight: bold;
            font-size: 16px;
            color: #16a34a; /* Herbatech green */
            letter-spacing: 1px;
        }
        .title-cell {
            width: 50%;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .doc-no-cell {
            width: 25%;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
        }

        /* Main Form Tables */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .form-table th, .form-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: top;
        }
        .bg-light {
            background-color: #f3f4f6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .label-bold {
            font-weight: bold;
            font-size: 10px;
        }
        .label-gray {
            font-size: 9px;
            color: #4b5563;
        }

        /* Checkbox lists */
        .checkbox-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            padding: 4px 0;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            font-size: 10px;
        }
        .chk-box {
            width: 11px;
            height: 11px;
            border: 1px solid #000;
            margin-right: 6px;
            display: inline-block;
            text-align: center;
            line-height: 10px;
            font-weight: bold;
            font-size: 9px;
        }

        .content-area {
            min-height: 70px;
            font-size: 11px;
            white-space: pre-wrap;
            padding-top: 4px;
        }

        /* Signature layout */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: auto;
            margin-bottom: 40px;
        }
        .signature-table td {
            border: 1px solid #000;
            width: 50%;
            padding: 6px;
        }
        .sig-space {
            height: 50px;
        }

        /* Footer Layout */
        .page-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 9px;
            border-top: 1px solid #000;
            padding-top: 4px;
            position: relative;
        }
        .doc-code {
            font-weight: bold;
        }

        /* Table custom settings */
        .kajian-table th, .kajian-table td {
            padding: 8px;
            font-size: 10px;
        }
    </style>
</head>
<body>

    @php
        $qa1 = $changeRequest->qa_verification_data['qa_1'] ?? [];
        $qa2 = $changeRequest->qa_verification_data['qa_2'] ?? [];
        $qa3 = $changeRequest->qa_verification_data['qa_3'] ?? [];
        
        $sifat = $changeRequest->sifat_perubahan;
        $jenisOptions = [
            'Formula' => ['Formula', 'Formula / Bentuk Sediaan'],
            'Proses Produksi' => ['Proses Produksi'],
            'Pemasok' => ['Pemasok', 'Pemasok / Supplier'],
            'Bahan Baku' => ['Bahan Baku'],
            'Bahan kemas' => ['Bahan kemas', 'Bahan Pengemas'],
            'Fasilitas' => ['Fasilitas', 'Fasilitas / Sarana'],
            'Peralatan' => ['Peralatan'],
            'Stabilitas' => ['Stabilitas'],
            'Metode Analisa' => ['Metode Analisa'],
            'Permintaan BPOM' => ['Permintaan BPOM']
        ];
        
        $isLainLain = true;
        foreach ($jenisOptions as $key => $values) {
            foreach ($values as $val) {
                if (stripos($sifat, $val) !== false) {
                    $isLainLain = false;
                }
            }
        }
        if (empty($sifat)) {
            $isLainLain = false;
        }
    @endphp

    <!-- ========================================================================= -->
    <!-- HALAMAN 1 -->
    <!-- ========================================================================= -->
    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoType === 'image' && !empty($logoPath))
                        <img src="{{ asset('storage/' . $logoPath) }}" style="max-height: 25px; max-width: 120px; object-fit: contain; display: block; margin: 0 auto;" />
                    @else
                        <span class="logo-text">{{ strtoupper($logoText) }}</span>
                    @endif
                </td>
                <td class="title-cell">
                    FORM CHANGE REQUEST<br>(CRA)
                </td>
                <td class="doc-no-cell">
                    No. CM- 01/QA/001B.00
                </td>
            </tr>
        </table>

        <!-- Info Umum -->
        <table class="form-table">
            <tr>
                <td colspan="2">
                    <span class="label-bold">PENGENDALIAN PERUBAHAN NO. :</span> (Diisi Oleh QA)<br>
                    <strong>{{ $changeRequest->cr_number }}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">NAMA PRODUK / PROSES / PEMERIKSAAN / SISTEM / ALAT :</span><br>
                    <span>{{ $qa2['nama_produk'] ?? '-' }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">DIUSULKAN OLEH DEPARTEMEN :</span><br>
                    <span>{{ $changeRequest->department }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="bg-light" style="padding: 4px 8px;">
                    <span class="label-bold">JENIS PERUBAHAN</span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 6px 12px;">
                    <div class="checkbox-container">
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'Formula') !== false) ? '✓' : '' }}</span>
                            Formula / Bentuk Sediaan
                        </div>
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'Fasilitas') !== false || stripos($sifat, 'Sarana') !== false) ? '✓' : '' }}</span>
                            Fasilitas / Sarana
                        </div>
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'Proses') !== false) ? '✓' : '' }}</span>
                            Proses Produksi
                        </div>
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'Peralatan') !== false) ? '✓' : '' }}</span>
                            Peralatan
                        </div>
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'Pemasok') !== false || stripos($sifat, 'Supplier') !== false) ? '✓' : '' }}</span>
                            Pemasok / Supplier
                        </div>
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'Stabilitas') !== false) ? '✓' : '' }}</span>
                            Stabilitas
                        </div>
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'Bahan Baku') !== false) ? '✓' : '' }}</span>
                            Bahan Baku
                        </div>
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'Metode') !== false) ? '✓' : '' }}</span>
                            Metode Analisa
                        </div>
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'kemas') !== false || stripos($sifat, 'Pengemas') !== false) ? '✓' : '' }}</span>
                            Bahan Pengemas
                        </div>
                        <div class="checkbox-item">
                            <span class="chk-box">{{ (stripos($sifat, 'BPOM') !== false) ? '✓' : '' }}</span>
                            Permintaan BPOM
                        </div>
                    </div>
                    <div style="margin-top: 4px; font-size: 10px; display: flex; gap: 20px;">
                        <div>
                            <span class="chk-box">{{ $isLainLain ? '✓' : '' }}</span> Lain - Lain :
                        </div>
                        <div style="flex-grow:1; border-bottom: 1px dotted #000; padding-bottom: 2px;">
                            {{ $isLainLain ? $sifat : '' }}
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">AWAL SEBELUM PERUBAHAN</span> <span class="label-gray">(tulis rinciannya dalam lampiran, bila perlu) :</span>
                    <div class="content-area">{{ $changeRequest->awal_sebelum_perubahan }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">USULAN PERUBAHAN</span> <span class="label-gray">(tulis rinciannya dalam lampiran, bila perlu) :</span>
                    <div class="content-area">{{ $changeRequest->usulan_perubahan }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">ALASAN PERUBAHAN</span> <span class="label-gray">(tulis rinciannya dalam lampiran, bila perlu) :</span>
                    <div class="content-area">{{ $changeRequest->alasan_perubahan }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">ANALISIS DAMPAK DAN PENILAIAN RESIKO</span> <span class="label-gray">(tulis kajian resiko, dan juga benefit/ manfaat dari perubahan yang terjadi termasuk analisa biaya yang timbul untuk pelaksanaan perubahan dan dampak dari perubahan, bila perlu dapat dilampirkan form risk analisis) :</span>
                    <div class="content-area">{{ $changeRequest->analisis_dampak }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 6px 8px;">
                    <span class="label-bold">WAKTU PERUBAHAN DIHARAPKAN DAPAT DILAKSANAKAN :</span>
                    <span style="margin-left: 10px;">{{ $changeRequest->timeline ? \Carbon\Carbon::parse($changeRequest->timeline)->format('d/m/Y') : '..................................................' }}</span>
                </td>
            </tr>
        </table>

        <!-- Signatures -->
        <table class="signature-table">
            <tr>
                <td>
                    <span class="label-bold">Usulan Dibuat Oleh :</span>
                    @if($changeRequest->initiator->signature_path)
                        <div style="text-align: center; margin-top: 8px;">
                            <img src="{{ asset('storage/' . $changeRequest->initiator->signature_path) }}" style="max-height: 45px; max-width: 180px; object-fit: contain; display: block; margin: 0 auto 4px;" /><br>
                            <span style="font-weight: bold; text-decoration: underline; font-size: 10px;">{{ $changeRequest->initiator->name }}</span>
                            <div style="font-size: 8px; color: #4b5563; margin-top: 2px;">Tanggal: {{ $changeRequest->created_at->format('d/m/Y') }}</div>
                        </div>
                    @else
                        <div style="text-align: center; border: 1px dashed #3b82f6; padding: 6px; border-radius: 4px; background-color: #eff6ff; margin-top: 8px; max-width: 200px;">
                            <span style="color: #2563eb; font-weight: bold; display: block; font-size: 8px;">✓ DIAJUKAN ELEKTRONIK</span>
                            <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block; font-size: 11px; margin: 3px 0;">{{ $changeRequest->initiator->name }}</span>
                            <span style="color: #6b7280; display: block; font-size: 7px;">Tgl: {{ $changeRequest->created_at->format('d/m/Y') }}</span>
                        </div>
                    @endif
                </td>
                <td>
                    <span class="label-bold">Manager Terkait :</span>
                    @if(($qa1['hu_approved'] ?? '') === 'APPROVED')
                        @if($huUser && $huUser->signature_path)
                            <div style="text-align: center; margin-top: 8px;">
                                <img src="{{ asset('storage/' . $huUser->signature_path) }}" style="max-height: 45px; max-width: 180px; object-fit: contain; display: block; margin: 0 auto 4px;" /><br>
                                <span style="font-weight: bold; text-decoration: underline; font-size: 10px;">{{ $huUser->name }}</span>
                                <div style="font-size: 8px; color: #4b5563; margin-top: 2px;">Tanggal: {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}</div>
                            </div>
                        @else
                            <div style="text-align: center; border: 1px dashed #22c55e; padding: 6px; border-radius: 4px; background-color: #f0fdf4; margin-top: 8px; max-width: 200px;">
                                <span style="color: #16a34a; font-weight: bold; display: block; font-size: 8px;">✓ DISETUJUI ELEKTRONIK</span>
                                <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block; font-size: 11px; margin: 3px 0;">{{ $huUser->name ?? 'Head of Quality' }}</span>
                                <span style="color: #6b7280; display: block; font-size: 7px;">Tgl: {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}</span>
                            </div>
                        @endif
                    @else
                        <div class="sig-space" style="display: flex; align-items: flex-end;">
                            <span style="font-style: italic; color: #6b7280;">(Tanda Tangan)</span>
                        </div>
                        <div>Tanggal : .................................</div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="page-footer">
            <span class="master-copy-stamp">MASTER COPY</span>
            <span class="doc-code">LAMP. B PR-01/QA/001.00</span>
            <span class="master-stamp">MASTER</span>
            <span>Hal. 1 dari 4</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- HALAMAN 2 -->
    <!-- ========================================================================= -->
    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoType === 'image' && !empty($logoPath))
                        <img src="{{ asset('storage/' . $logoPath) }}" style="max-height: 25px; max-width: 120px; object-fit: contain; display: block; margin: 0 auto;" />
                    @else
                        <span class="logo-text">{{ strtoupper($logoText) }}</span>
                    @endif
                </td>
                <td class="title-cell">
                    FORM CHANGE REQUEST<br>(CRA)
                </td>
                <td class="doc-no-cell">
                    No. CM- 01/QA/001B.00
                </td>
            </tr>
        </table>

        <!-- Pengkajian -->
        <table class="form-table kajian-table">
            <tr>
                <td colspan="3" class="bg-light" style="text-align: center; font-weight: bold; font-size: 11px;">
                    PENGKAJIAN
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="label-bold">KATEGORI PERUBAHAN :</span> &nbsp; &nbsp; 
                    <strong>
                        @if(($changeRequest->rpn ?? 0) > 100)
                            MAYOR
                        @else
                            MINOR
                        @endif
                    </strong>
                    &nbsp; &nbsp; <span class="label-gray">( Diisi Oleh QA )</span>
                </td>
            </tr>
            <tr style="text-align: center; font-weight: bold;">
                <td style="width: 25%;">DIKAJI OLEH ( Nama, ttd )</td>
                <td style="width: 20%;">TANGGAL</td>
                <td>KAJIAN</td>
            </tr>
            
            <!-- Row 1: QA/Reviewer Initial Ulasan -->
            <tr style="height: 100px;">
                <td>
                    <strong>{{ $qa1['diulas_oleh'] ?? '-' }}</strong><br>
                    <span style="font-size: 8px; color: #4b5563;">QA Dept / Reviewer</span>
                </td>
                <td style="text-align: center;">
                    {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '-' }}
                </td>
                <td>
                    {{ $qa1['ulasan'] ?? '-' }}
                </td>
            </tr>
            
            <!-- Extra Blank Kajian Rows as in paper template -->
            <tr style="height: 100px;">
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr style="height: 100px;">
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr style="height: 100px;">
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <!-- Approval Box -->
        <table class="form-table" style="margin-top: 10px;">
            <tr>
                <td colspan="4" class="bg-light" style="font-weight: bold; font-size: 10px;">
                    USULAN DISETUJUI / TIDAK DISETUJUI *)
                </td>
            </tr>
            <tr style="text-align: center; font-weight: bold; font-size: 10px;">
                <td style="width: 25%;">Jabatan</td>
                <td style="width: 20%;">Tanda Tangan</td>
                <td style="width: 15%;">Tanggal</td>
                <td>Tanggapan</td>
            </tr>
            
            <!-- QA Supervisor / Head of Quality -->
            <tr style="height: 55px;">
                <td><strong>Head of Quality (HU)</strong></td>
                <td style="text-align: center; vertical-align: middle;">
                    @if(($qa1['hu_approved'] ?? '') === 'APPROVED')
                        @if($huUser && $huUser->signature_path)
                            <img src="{{ asset('storage/' . $huUser->signature_path) }}" style="max-height: 40px; max-width: 100px; object-fit: contain;" />
                        @else
                            <div style="text-align: center; border: 1px dashed #22c55e; padding: 2px; border-radius: 4px; background-color: #f0fdf4; font-size: 8px; line-height: 1.1; display: inline-block; min-width: 110px;">
                                <span style="color: #16a34a; font-weight: bold; display: block; font-size: 7px;">✓ DISETUJUI</span>
                                <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block;">{{ $huUser->name ?? 'HU' }}</span>
                            </div>
                        @endif
                    @elseif(($qa1['hu_approved'] ?? '') === 'REJECTED')
                        <span style="color: #ef4444; font-weight: bold;">REJECTED</span>
                    @else
                        <span style="color: #6b7280; font-style: italic;">PENDING</span>
                    @endif
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    {{ (($qa1['hu_approved'] ?? '') !== 'PENDING' && !empty($qa1['tanggal'])) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '-' }}
                </td>
                <td style="font-size: 9px; vertical-align: middle;">
                    Persetujuan awal ulasan pengerjaan.
                </td>
            </tr>
            
            <!-- Operational Manager -->
            <tr style="height: 55px;">
                <td><strong>Operational Manager (OM)</strong></td>
                <td style="text-align: center; vertical-align: middle;">
                    @if(($qa1['om_approved'] ?? '') === 'APPROVED')
                        @if($omUser && $omUser->signature_path)
                            <img src="{{ asset('storage/' . $omUser->signature_path) }}" style="max-height: 40px; max-width: 100px; object-fit: contain;" />
                        @else
                            <div style="text-align: center; border: 1px dashed #22c55e; padding: 2px; border-radius: 4px; background-color: #f0fdf4; font-size: 8px; line-height: 1.1; display: inline-block; min-width: 110px;">
                                <span style="color: #16a34a; font-weight: bold; display: block; font-size: 7px;">✓ DISETUJUI</span>
                                <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block;">{{ $omUser->name ?? 'OM' }}</span>
                            </div>
                        @endif
                    @elseif(($qa1['om_approved'] ?? '') === 'REJECTED')
                        <span style="color: #ef4444; font-weight: bold;">REJECTED</span>
                    @else
                        <span style="color: #6b7280; font-style: italic;">PENDING</span>
                    @endif
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    {{ (($qa1['om_approved'] ?? '') !== 'PENDING' && !empty($qa1['tanggal'])) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '-' }}
                </td>
                <td style="font-size: 9px; vertical-align: middle;">
                    -
                </td>
            </tr>

            <!-- General Manager -->
            <tr style="height: 55px;">
                <td><strong>General Manager (GM)</strong></td>
                <td style="text-align: center; vertical-align: middle;">
                    @if(($qa1['gm_approved'] ?? '') === 'APPROVED')
                        @if($gmUser && $gmUser->signature_path)
                            <img src="{{ asset('storage/' . $gmUser->signature_path) }}" style="max-height: 40px; max-width: 100px; object-fit: contain;" />
                        @else
                            <div style="text-align: center; border: 1px dashed #22c55e; padding: 2px; border-radius: 4px; background-color: #f0fdf4; font-size: 8px; line-height: 1.1; display: inline-block; min-width: 110px;">
                                <span style="color: #16a34a; font-weight: bold; display: block; font-size: 7px;">✓ DISETUJUI</span>
                                <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block;">{{ $gmUser->name ?? 'GM' }}</span>
                            </div>
                        @endif
                    @elseif(($qa1['gm_approved'] ?? '') === 'REJECTED')
                        <span style="color: #ef4444; font-weight: bold;">REJECTED</span>
                    @else
                        <span style="color: #6b7280; font-style: italic;">PENDING</span>
                    @endif
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    {{ (($qa1['gm_approved'] ?? '') !== 'PENDING' && !empty($qa1['tanggal'])) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '-' }}
                </td>
                <td style="font-size: 9px; vertical-align: middle;">
                    -
                </td>
            </tr>
        </table>
        
        <div style="font-size: 8px; margin-top: 4px; font-style: italic;">
            *) Coret yang tidak perlu
        </div>

        <!-- Footer -->
        <div class="page-footer">
            <span class="master-copy-stamp">MASTER COPY</span>
            <span class="doc-code">LAMP. B PR-01/QA/001.00</span>
            <span class="master-stamp">MASTER</span>
            <span>Hal. 2 dari 4</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- HALAMAN 3 -->
    <!-- ========================================================================= -->
    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoType === 'image' && !empty($logoPath))
                        <img src="{{ asset('storage/' . $logoPath) }}" style="max-height: 25px; max-width: 120px; object-fit: contain; display: block; margin: 0 auto;" />
                    @else
                        <span class="logo-text">{{ strtoupper($logoText) }}</span>
                    @endif
                </td>
                <td class="title-cell">
                    FORM CHANGE REQUEST<br>(CRA)
                </td>
                <td class="doc-no-cell">
                    No. CM- 01/QA/001B.00
                </td>
            </tr>
        </table>

        <!-- Info Header Page 3 -->
        <table class="form-table">
            <tr>
                <td>
                    <span class="label-bold">PENGENDALIAN PERUBAHAN NO. :</span> (Diisi Oleh QA)<br>
                    <strong>{{ $changeRequest->cr_number }}</strong>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label-bold">NAMA PRODUK/ PROSES/ PEMERIKSAAN/ SISTEM/ ALAT :</span><br>
                    <span>{{ $qa2['nama_produk'] ?? '-' }}</span>
                </td>
            </tr>
        </table>

        <!-- BPOM Status -->
        <table class="form-table">
            <tr>
                <td class="bg-light">
                    <span class="label-bold">Perubahan dapat langsung dilaksanakan tanpa menunggu izin dari Badan POM karena ( DIISI OLEH BAGIAN REGISTRASI ):</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 12px;">
                    <div style="margin-bottom: 6px;">
                        <span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option1') ? '✓' : '' }}</span> 
                        Perubahan akan didokumentasikan bersama dengan pembaruan dokumen yang bersangkutan oleh Departemen Riset dan Pengembangan secara bertahap.
                    </div>
                    <div style="margin-bottom: 12px;">
                        <span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option2') ? '✓' : '' }}</span> 
                        Tidak diperlukan pemberitahuan perubahan.
                    </div>
                    <div style="border-top: 1px dashed #000; padding-top: 8px;">
                        <span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option3') ? '✓' : '' }}</span>
                        Perubahan memerlukan izin Badan POM terlebih dahulu
                        <div style="margin-left: 20px; margin-top: 4px; line-height: 1.6;">
                            a. Pemberitahuan perubahan akan dilaporkan oleh : <u>{{ $qa2['pom_pemberitahuan_dari'] ?? '........................' }}</u> tanggal <u>{{ $qa2['pom_pemberitahuan_sampai'] ?? '........................' }}</u><br>
                            b. Perubahan tidak dapat dilaksanakan sebelum persetujuan Badan POM diterima.<br>
                            c. Perubahan telah disetujui oleh Badan POM tanggal : <u>{{ $qa2['pom_rencana_disetujui_tanggal'] ?? '........................' }}</u>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Documents Checklist Table -->
        <table class="form-table" style="font-size: 9px;">
            <thead>
                <tr class="bg-light" style="font-weight: bold; text-align: center;">
                    <td colspan="7">
                        DOKUMEN YANG PERLU DIREVISI/ DISIAPKAN DALAM HUBUNGAN DENGAN RENCANA PERUBAHAN YANG AKAN DILAKUKAN ( DIISI OLEH QA )
                    </td>
                </tr>
                <tr style="font-weight: bold; text-align: center;">
                    <td rowspan="2" style="width: 30%;">Jenis Dokumen</td>
                    <td colspan="2">Check List *)</td>
                    <td rowspan="2" style="width: 20%;">No. Dokumen</td>
                    <td rowspan="2" style="width: 15%;">Tanggal Berlaku</td>
                    <td rowspan="2" style="width: 10%;">PIC</td>
                    <td rowspan="2" style="width: 12%;">Timeline</td>
                </tr>
                <tr style="font-weight: bold; text-align: center;">
                    <td style="width: 6%;">Perlu</td>
                    <td style="width: 8%;">Tidak Perlu</td>
                </tr>
            </thead>
            <tbody>
                @php
                    $docsList = $qa2['documents'] ?? [];
                    
                    // Predefined items
                    $predefinedDocs = [
                        'Prosedur pengolahan/ Pengemasan Induk',
                        'Spesifikasi',
                        'Metode Analisa',
                        'Validasi Metode Analisa',
                        'Validasi Proses',
                        'Pengamatan Stabilitas',
                        'Laporan-laporan Kualifikasi',
                        'Protap',
                        'Design Bahan Kemas (art work)',
                        'Penarikan Produk dan pemusnahan Bahan/Sampel/Dokumen',
                    ];
                @endphp

                @foreach($predefinedDocs as $pdoc)
                    @php
                        $found = null;
                        foreach($docsList as $dl) {
                            if(($dl['jenis'] ?? '') === $pdoc) {
                                $found = $dl;
                                break;
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $pdoc }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $found ? '✓' : '' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ !$found ? '✓' : '' }}</td>
                        <td>{{ $found['no_dokumen'] ?? '-' }}</td>
                        <td style="text-align: center;">{{ $found['tanggal_berlaku'] ?? '-' }}</td>
                        <td>{{ $found['pic'] ?? '-' }}</td>
                        <td style="text-align: center;">{{ $found['timeline'] ?? '-' }}</td>
                    </tr>
                @endforeach
                
                <!-- Lain-lain rows -->
                @php
                    $otherDocs = [];
                    foreach($docsList as $dl) {
                        if(!in_array($dl['jenis'] ?? '', $predefinedDocs)) {
                            $otherDocs[] = $dl;
                        }
                    }
                @endphp
                <tr>
                    <td>Lain-lain: 1. {{ $otherDocs[0]['jenis'] ?? '' }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ isset($otherDocs[0]) ? '✓' : '' }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ !isset($otherDocs[0]) ? '✓' : '' }}</td>
                    <td>{{ $otherDocs[0]['no_dokumen'] ?? '-' }}</td>
                    <td style="text-align: center;">{{ $otherDocs[0]['tanggal_berlaku'] ?? '-' }}</td>
                    <td>{{ $otherDocs[0]['pic'] ?? '-' }}</td>
                    <td style="text-align: center;">{{ $otherDocs[0]['timeline'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 2. {{ $otherDocs[1]['jenis'] ?? '' }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ isset($otherDocs[1]) ? '✓' : '' }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ !isset($otherDocs[1]) ? '✓' : '' }}</td>
                    <td>{{ $otherDocs[1]['no_dokumen'] ?? '-' }}</td>
                    <td style="text-align: center;">{{ $otherDocs[1]['tanggal_berlaku'] ?? '-' }}</td>
                    <td>{{ $otherDocs[1]['pic'] ?? '-' }}</td>
                    <td style="text-align: center;">{{ $otherDocs[1]['timeline'] ?? '-' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Other impacted systems -->
        <table class="form-table" style="margin-top: 10px;">
            <tr>
                <td>
                    <span class="label-bold">SISTEM LAIN YANG TERKENA DAMPAK ( Diisi Oleh QA ) :</span>
                    <div style="min-height: 40px; padding-top: 4px; font-size: 10px;">
                        -
                    </div>
                </td>
            </tr>
        </table>
        
        <div style="font-size: 8px; font-style: italic;">
            *) Coret yang tidak perlu
        </div>

        <!-- Footer -->
        <div class="page-footer">
            <span class="master-copy-stamp">MASTER COPY</span>
            <span class="doc-code">LAMP. B PR-01/QA/001.00</span>
            <span class="master-stamp">MASTER</span>
            <span>Hal. 3 dari 4</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- HALAMAN 4 -->
    <!-- ========================================================================= -->
    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoType === 'image' && !empty($logoPath))
                        <img src="{{ asset('storage/' . $logoPath) }}" style="max-height: 25px; max-width: 120px; object-fit: contain; display: block; margin: 0 auto;" />
                    @else
                        <span class="logo-text">{{ strtoupper($logoText) }}</span>
                    @endif
                </td>
                <td class="title-cell">
                    FORM CHANGE REQUEST<br>(CRA)
                </td>
                <td class="doc-no-cell">
                    No. CM- 01/QA/001B.00
                </td>
            </tr>
        </table>

        <!-- Info Header Page 4 -->
        <table class="form-table">
            <tr>
                <td>
                    <span class="label-bold">PENGENDALIAN PERUBAHAN NO. :</span> (Diisi Oleh QA)<br>
                    <strong>{{ $changeRequest->cr_number }}</strong>
                </td>
            </tr>
        </table>

        <!-- Actions implementation details -->
        <table class="form-table" style="font-size: 10px;">
            <thead>
                <tr class="bg-light" style="font-weight: bold; text-align: center;">
                    <td colspan="5">RINCIAN PERUBAHAN YANG DILAKUKAN (Diisi Oleh QA)</td>
                </tr>
                <tr style="font-weight: bold; text-align: center;">
                    <td style="width: 15%;">PIC</td>
                    <td style="width: 40%;">Perubahan yang Dilakukan</td>
                    <td style="width: 15%;">Paraf dan Tanggal</td>
                    <td colspan="2">Bukti Dokumen</td>
                </tr>
                <tr style="font-weight: bold; text-align: center; font-size: 9px;">
                    <td colspan="3"></td>
                    <td style="width: 15%;">No. Dokumen</td>
                    <td style="width: 15%;">Tanggal Berlaku</td>
                </tr>
            </thead>
            <tbody>
                @php
                    $impls = $qa3['implementations'] ?? [];
                @endphp
                @if(count($impls) > 0)
                    @foreach($impls as $imp)
                        <tr style="height: 35px;">
                            <td>{{ $imp['pic'] ?? '-' }}</td>
                            <td>{{ $imp['perubahan'] ?? '-' }}</td>
                            <td style="text-align: center; font-size: 8px;">
                                Paraf: Ya<br>
                                {{ !empty($imp['tanggal_dilakukan']) ? \Carbon\Carbon::parse($imp['tanggal_dilakukan'])->format('d/m/Y') : '-' }}
                            </td>
                            <td>{{ $imp['bukti_dokumen_path'] ? basename($imp['bukti_dokumen_path']) : '-' }}</td>
                            <td style="text-align: center;">
                                {{ !empty($imp['tanggal_berlaku']) ? \Carbon\Carbon::parse($imp['tanggal_berlaku'])->format('d/m/Y') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <!-- Blank rows as template -->
                    @for($i=1; $i<=5; $i++)
                        <tr style="height: 35px;">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>

        <!-- Verification QA Section -->
        <table class="form-table" style="margin-top: 10px;">
            <tr>
                <td class="bg-light" colspan="3">
                    <span class="label-bold">VERIFIKASI (Diisi Oleh QA)</span>
                </td>
            </tr>
            <tr>
                <td style="width: 70%; padding: 6px;">1 Implementasi perubahan sudah dilakukan</td>
                <td style="width: 15%; text-align: center;"><span class="chk-box">{{ ($qa3['verifikasi_completed'] ?? false) ? '✓' : '' }}</span> Sudah</td>
                <td style="width: 15%; text-align: center;"><span class="chk-box">{{ !($qa3['verifikasi_completed'] ?? false) ? '✓' : '' }}</span> Belum</td>
            </tr>
            <tr>
                <td style="padding: 6px;">2 Semua dokumen yang diperlukan sudah dibuat/ direvisi</td>
                <td style="text-align: center;"><span class="chk-box">{{ ($qa3['verifikasi_completed'] ?? false) ? '✓' : '' }}</span> Sudah</td>
                <td style="text-align: center;"><span class="chk-box">{{ !($qa3['verifikasi_completed'] ?? false) ? '✓' : '' }}</span> Belum</td>
            </tr>
            <tr>
                <td style="padding: 6px;">3 Surat persetujuan dari Badan POM sudah diterima *</td>
                <td style="text-align: center;"><span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option3') ? '✓' : '' }}</span> Sudah</td>
                <td style="text-align: center;"><span class="chk-box">{{ (($qa2['pom_status'] ?? '') !== 'option3') ? '✓' : '' }}</span> Belum</td>
            </tr>
            <tr>
                <td style="padding: 6px;">4 Surat pemberitahuan kepada Badan POM sudah dikirim *</td>
                <td style="text-align: center;"><span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option1') ? '✓' : '' }}</span> Sudah</td>
                <td style="text-align: center;"><span class="chk-box">{{ (($qa2['pom_status'] ?? '') !== 'option1') ? '✓' : '' }}</span> Belum</td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 6px;">
                    <div style="display: flex; justify-content: space-between;">
                        <div>DIKAJI OLEH: <strong>{{ $qa1['diulas_oleh'] ?? '-' }}</strong></div>
                        <div>Paraf: <span class="chk-box">✓</span></div>
                        <div>Tanggal: {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Kesimpulan -->
        <table class="form-table" style="margin-top: 10px;">
            <tr>
                <td class="bg-light">
                    <span class="label-bold">KESIMPULAN (Diisi Oleh QA)</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; line-height: 1.5;">
                    <div style="margin-bottom: 6px;">
                        <span class="chk-box">{{ ($changeRequest->status === 'COMPLETE') ? '✓' : '' }}</span> 
                        Segala aspek yang menyangkut perubahan dipenuhi sesuai prosedur yang berlaku dan menyatakan bahwa semua dokumen yang diperlukan telah selesai disiapkan serta sistem yang terkena dampak perubahan telah disesuaikan dan semuanya telah memenuhi kriteria yang berlaku. Perubahan dapat dilaksanakan sejak <u>{{ ($changeRequest->status === 'COMPLETE' && !empty($changeRequest->updated_at)) ? $changeRequest->updated_at->format('d/m/Y') : '........................' }}</u>
                    </div>
                    <div style="margin-bottom: 6px;">
                        <span class="chk-box">{{ ($changeRequest->status === 'IN PROGRESS' || $changeRequest->status === 'IN REVIEW') ? '✓' : '' }}</span> 
                        Perubahan belum dapat dilakukan, Change Request ditutup dan diperlukan yang baru<br>
                        &nbsp; &nbsp; &nbsp; No. : <u>..................................................</u>
                    </div>
                    <div>
                        <span class="chk-box">{{ ($changeRequest->status === 'REJECT') ? '✓' : '' }}</span> 
                        Perubahan tidak dapat dilakukan, Change Request selesai
                    </div>
                </td>
            </tr>
        </table>

        <!-- Approval Final -->
        <div style="margin-top: 15px; display: flex; justify-content: space-between; font-size: 10px;">
            <div></div> <!-- push to right side -->
            <div style="width: 220px; border: 1px solid #000; padding: 8px; background-color: #fff; text-align: left;">
                Purbalingga, {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : $changeRequest->updated_at->format('d/m/Y') }}<br>
                Disetujui Oleh:<br>
                @if(($qa1['om_approved'] ?? '') === 'APPROVED')
                    @if($omUser && $omUser->signature_path)
                        <div style="text-align: center; margin-top: 5px;">
                            <img src="{{ asset('storage/' . $omUser->signature_path) }}" style="max-height: 45px; max-width: 180px; object-fit: contain; display: block; margin: 0 auto 4px;" /><br>
                            <span style="font-weight: bold; text-decoration: underline; font-size: 10px;">{{ $omUser->name }}</span>
                        </div>
                    @else
                        <div style="text-align: center; border: 1px dashed #22c55e; padding: 4px; border-radius: 4px; background-color: #f0fdf4; margin-top: 5px;">
                            <span style="color: #16a34a; font-weight: bold; display: block; font-size: 8px;">✓ DISETUJUI ELEKTRONIK</span>
                            <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block; font-size: 10px; margin: 2px 0;">{{ $omUser->name ?? 'Operational Manager' }}</span>
                            <span style="color: #6b7280; display: block; font-size: 7px;">Tgl: {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}</span>
                        </div>
                    @endif
                @else
                    <div class="sig-space" style="display: flex; align-items: flex-end; justify-content: center;">
                        <span style="font-style: italic; color: #6b7280;">(Tanda Tangan)</span>
                    </div>
                @endif
                <strong style="display: block; margin-top: 5px; border-top: 1px solid #000; padding-top: 3px; text-align: center;">Operational Manager</strong>
            </div>
        </div>
        
        <div style="font-size: 8px; margin-top: 4px; font-style: italic;">
            *) bila diperlukan
        </div>

        <!-- Footer -->
        <div class="page-footer">
            <span class="master-copy-stamp">MASTER COPY</span>
            <span class="doc-code">LAMP. B PR-01/QA/001.00</span>
            <span class="master-stamp">MASTER</span>
            <span>Hal. 4 dari 4</span>
        </div>
    </div>

</body>
</html>
