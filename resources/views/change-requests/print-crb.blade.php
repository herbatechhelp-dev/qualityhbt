<!DOCTYPE html>
@php
    $logoType = \App\Models\Setting::getValue('print_logo_type', 'text');
    $logoText = \App\Models\Setting::getValue('print_company_name', 'HERBATECH');
    $logoPath = \App\Models\Setting::getValue('print_logo_path');
@endphp
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak CRB - {{ $changeRequest->cr_number }}</title>
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
            padding-bottom: 22mm;
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
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 10px;
            display: inline-block;
            transform: rotate(-3deg);
            opacity: 0.85;
            position: absolute;
            bottom: 12px;
            left: 5px;
            z-index: 10;
        }
        .master-stamp {
            border: 3px double #ef4444;
            color: #ef4444;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 10px;
            display: inline-block;
            transform: rotate(2deg);
            opacity: 0.85;
            position: absolute;
            bottom: 12px;
            right: 110px;
            z-index: 10;
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
            min-height: 35px;
            font-size: 11px;
            white-space: pre-wrap;
            padding-top: 4px;
        }

        /* Signature layout */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .signature-table td {
            border: 1px solid #000;
            width: 50%;
            padding: 6px;
        }
        .sig-space {
            height: 70px;
        }

        /* Footer Layout */
        .page-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 9px;
            border-top: 1px solid #000;
            padding-top: 4px;
            padding-bottom: 2px;
        }
        .doc-code {
            font-weight: bold;
        }
    </style>
</head>
<body>

    @php
        $qa1 = $changeRequest->qa_verification_data['qa_1'] ?? [];
        $qa2 = $changeRequest->qa_verification_data['qa_2'] ?? [];
        $qa3 = $changeRequest->qa_verification_data['qa_3'] ?? [];
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
                    FORM CHANGE REQUEST<br>(CRB)
                </td>
                <td class="doc-no-cell">
                    No. CM- 01/QA/001C.00
                </td>
            </tr>
        </table>

        <!-- Info Umum -->
        <table class="form-table">
            <tr>
                <td colspan="2">
                    <span class="label-bold">I. PENGENDALIAN PERUBAHAN NO. :</span> <span class="label-gray">( Diisi Oleh QA )</span><br>
                    <strong>{{ $changeRequest->cr_number }}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">II. NAMA DOKUMEN / NOMOR DOKUMEN :</span><br>
                    <span>{{ $qa2['nama_produk'] ?? '-' }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">III. DIUSULKAN OLEH DEPARTEMEN :</span><br>
                    <span>{{ $changeRequest->department }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">IV. ISI DOKUMEN SEBELUM USULAN PERUBAHAN</span> <span class="label-gray">(tulis rincian isi dokumen yang akan diubah) :</span>
                    <div class="content-area">{{ $changeRequest->awal_sebelum_perubahan }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">V. USULAN PERUBAHAN</span> <span class="label-gray">(tulis rinciannya dalam lampiran, bila perlu) :</span>
                    <div class="content-area">{{ $changeRequest->usulan_perubahan }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">VI. ALASAN PERUBAHAN</span> <span class="label-gray">(jika perlu cantumkan acuan kompendia yang digunakan dan tambahan lampiran) :</span>
                    <div class="content-area">{{ $changeRequest->alasan_perubahan }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">VII. ANALISIS DAMPAK DAN PENILAIAN RESIKO</span> <span class="label-gray">(tulis kajian resiko, dan juga benefit/ manfaat dari perubahan yang terjadi serta kesesuaian perubahan dengan cGMP) :</span>
                    <div class="content-area">{{ $changeRequest->analisis_dampak }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="bg-light" style="padding: 4px 8px;">
                    <span class="label-bold">VIII. RENCANA PERUBAHAN DAPAT DILAKSANAKAN :</span>
                </td>
            </tr>
        </table>

        <!-- Signatures Inisiator & Kabag -->
        <table class="signature-table">
            <tr>
                <td>
                    <span class="label-bold">Nama & Tanda Tangan Inisiator :</span>
                    @if($changeRequest->initiator->signature_path)
                        <div style="text-align: center; margin-top: 4px;">
                            <img src="{{ asset('storage/' . $changeRequest->initiator->signature_path) }}" style="max-height: 70px; height: 70px; max-width: 170px; object-fit: contain; display: block; margin: 0 auto 4px;" /><br>
                            <span style="font-weight: bold; text-decoration: underline; font-size: 10px;">{{ $changeRequest->initiator->name }}</span>
                            <div style="font-size: 8px; color: #4b5563; margin-top: 2px;">Tanggal: {{ $changeRequest->created_at->format('d/m/Y') }}</div>
                        </div>
                    @else
                        <div style="text-align: center; border: 1px dashed #3b82f6; padding: 6px; border-radius: 4px; background-color: #eff6ff; margin: 4px auto 0; max-width: 220px;">
                            <span style="color: #2563eb; font-weight: bold; display: block; font-size: 8px;">✓ DIAJUKAN ELEKTRONIK</span>
                            <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block; font-size: 11px; margin: 3px 0;">{{ $changeRequest->initiator->name }}</span>
                            <span style="color: #6b7280; display: block; font-size: 7px;">Tgl: {{ $changeRequest->created_at->format('d/m/Y') }}</span>
                        </div>
                    @endif
                </td>
                <td>
                    <span class="label-bold">Nama & Tanda Tangan Kabag yang bersangkutan :</span>
                    @if(($qa1['hu_approved'] ?? '') === 'APPROVED')
                        @if($huUser && $huUser->signature_path)
                            <div style="text-align: center; margin-top: 4px;">
                                <img src="{{ asset('storage/' . $huUser->signature_path) }}" style="max-height: 70px; height: 70px; max-width: 170px; object-fit: contain; display: block; margin: 0 auto 4px;" /><br>
                                <span style="font-weight: bold; text-decoration: underline; font-size: 10px;">{{ $huUser->name }}</span>
                                <div style="font-size: 8px; color: #4b5563; margin-top: 2px;">Tanggal: {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}</div>
                            </div>
                        @else
                            <div style="text-align: center; border: 1px dashed #22c55e; padding: 6px; border-radius: 4px; background-color: #f0fdf4; margin: 4px auto 0; max-width: 220px;">
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

        <!-- IX. Pengkajian -->
        <table class="form-table">
            <tr>
                <td colspan="3" class="bg-light" style="font-weight: bold; font-size: 10px;">
                    IX. PENGKAJIAN: &nbsp; &nbsp; KATEGORI PERUBAHAN : &nbsp; &nbsp; 
                    <strong>
                        @if(($changeRequest->rpn ?? 0) > 100)
                            MAYOR
                        @else
                            MINOR
                        @endif
                    </strong>
                    &nbsp; &nbsp; <span class="label-gray">( DIISI OLEH QA )</span>
                </td>
            </tr>
            <tr style="text-align: center; font-weight: bold; font-size: 9px;">
                <td style="width: 25%;">DEPARTEMEN</td>
                <td style="width: 25%;">TANDATANGAN / TGL</td>
                <td>TANGGAPAN</td>
            </tr>
            <!-- Row 1: Head of Quality -->
            <tr style="height: 50px; font-size: 9px;">
                <td><strong>Head of Quality (HU)</strong></td>
                <td style="text-align: center; vertical-align: middle;">
                    @if(($qa1['hu_approved'] ?? '') === 'APPROVED')
                        @if($huUser && $huUser->signature_path)
                            <img src="{{ asset('storage/' . $huUser->signature_path) }}" style="max-height: 48px; max-width: 140px; object-fit: contain; display: block; margin: 0 auto;" />
                        @else
                            <div style="text-align: center; border: 1px dashed #22c55e; padding: 2px; border-radius: 4px; background-color: #f0fdf4; font-size: 8px; line-height: 1.1; display: inline-block; min-width: 110px;">
                                <span style="color: #16a34a; font-weight: bold; display: block; font-size: 7px;">✓ DISETUJUI</span>
                                <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block;">{{ $huUser->name ?? 'HU' }}</span>
                                <span style="color: #6b7280; display: block; font-size: 6px;">{{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}</span>
                            </div>
                        @endif
                    @elseif(($qa1['hu_approved'] ?? '') === 'REJECTED')
                        <span style="color: #ef4444; font-weight: bold;">REJECTED</span><br>
                        {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}
                    @else
                        <span style="color: #6b7280; font-style: italic;">PENDING</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 15px;">
                        <div><span class="chk-box">{{ ($qa1['hu_approved'] ?? '') === 'APPROVED' ? '✓' : '' }}</span> Setuju</div>
                        <div><span class="chk-box">{{ ($qa1['hu_approved'] ?? '') === 'REJECTED' ? '✓' : '' }}</span> tidak setuju</div>
                    </div>
                    <div style="margin-top: 4px; font-size: 8px;">Alasan : {{ ($qa1['hu_approved'] ?? '') === 'REJECTED' ? ($changeRequest->hasil_verifikasi ?? '-') : '-' }}</div>
                </td>
            </tr>
            <!-- Row 2: Operational Manager -->
            <tr style="height: 50px; font-size: 9px;">
                <td><strong>Operational Manager (OM)</strong></td>
                <td style="text-align: center; vertical-align: middle;">
                    @if(($qa1['om_approved'] ?? '') === 'APPROVED')
                        @if($omUser && $omUser->signature_path)
                            <img src="{{ asset('storage/' . $omUser->signature_path) }}" style="max-height: 48px; max-width: 140px; object-fit: contain; display: block; margin: 0 auto;" />
                        @else
                            <div style="text-align: center; border: 1px dashed #22c55e; padding: 2px; border-radius: 4px; background-color: #f0fdf4; font-size: 8px; line-height: 1.1; display: inline-block; min-width: 110px;">
                                <span style="color: #16a34a; font-weight: bold; display: block; font-size: 7px;">✓ DISETUJUI</span>
                                <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block;">{{ $omUser->name ?? 'OM' }}</span>
                                <span style="color: #6b7280; display: block; font-size: 6px;">{{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}</span>
                            </div>
                        @endif
                    @elseif(($qa1['om_approved'] ?? '') === 'REJECTED')
                        <span style="color: #ef4444; font-weight: bold;">REJECTED</span><br>
                        {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}
                    @else
                        <span style="color: #6b7280; font-style: italic;">PENDING</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 15px;">
                        <div><span class="chk-box">{{ ($qa1['om_approved'] ?? '') === 'APPROVED' ? '✓' : '' }}</span> Setuju</div>
                        <div><span class="chk-box">{{ ($qa1['om_approved'] ?? '') === 'REJECTED' ? '✓' : '' }}</span> tidak setuju</div>
                    </div>
                    <div style="margin-top: 4px; font-size: 8px;">Alasan : -</div>
                </td>
            </tr>
            <!-- Row 3: General Manager -->
            <tr style="height: 50px; font-size: 9px;">
                <td><strong>General Manager (GM)</strong></td>
                <td style="text-align: center; vertical-align: middle;">
                    @if(($qa1['gm_approved'] ?? '') === 'APPROVED')
                        @if($gmUser && $gmUser->signature_path)
                            <img src="{{ asset('storage/' . $gmUser->signature_path) }}" style="max-height: 48px; max-width: 140px; object-fit: contain; display: block; margin: 0 auto;" />
                        @else
                            <div style="text-align: center; border: 1px dashed #22c55e; padding: 2px; border-radius: 4px; background-color: #f0fdf4; font-size: 8px; line-height: 1.1; display: inline-block; min-width: 110px;">
                                <span style="color: #16a34a; font-weight: bold; display: block; font-size: 7px;">✓ DISETUJUI</span>
                                <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block;">{{ $gmUser->name ?? 'GM' }}</span>
                                <span style="color: #6b7280; display: block; font-size: 6px;">{{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}</span>
                            </div>
                        @endif
                    @elseif(($qa1['gm_approved'] ?? '') === 'REJECTED')
                        <span style="color: #ef4444; font-weight: bold;">REJECTED</span><br>
                        {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}
                    @else
                        <span style="color: #6b7280; font-style: italic;">PENDING</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 15px;">
                        <div><span class="chk-box">{{ ($qa1['gm_approved'] ?? '') === 'APPROVED' ? '✓' : '' }}</span> Setuju</div>
                        <div><span class="chk-box">{{ ($qa1['gm_approved'] ?? '') === 'REJECTED' ? '✓' : '' }}</span> tidak setuju</div>
                    </div>
                    <div style="margin-top: 4px; font-size: 8px;">Alasan : -</div>
                </td>
            </tr>
        </table>

        <!-- X. Keputusan -->
        <table class="form-table" style="margin-top: 5px;">
            <tr>
                <td class="bg-light">
                    <span class="label-bold">X. KEPUTUSAN TERHADAP USULAN PERUBAHAN : &nbsp; &nbsp; DISETUJUI / TIDAK DISETUJUI</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 6px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div><strong>QA Supervisor / Head of Quality :</strong></div>
                        @if(($qa1['hu_approved'] ?? '') === 'APPROVED')
                            @if($huUser && $huUser->signature_path)
                                <div style="text-align: center; margin-top: 2px;">
                                    <img src="{{ asset('storage/' . $huUser->signature_path) }}" style="max-height: 70px; height: 70px; max-width: 170px; object-fit: contain; display: block; margin: 0 auto;" />
                                </div>
                            @else
                                <div style="text-align: center; border: 1px dashed #22c55e; padding: 4px; border-radius: 4px; background-color: #f0fdf4; font-size: 8px; min-width: 150px;">
                                    <span style="color: #16a34a; font-weight: bold; display: block; font-size: 8px;">✓ DISETUJUI ELEKTRONIK</span>
                                    <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block; font-size: 10px;">{{ $huUser->name ?? 'Head of Quality' }}</span>
                                    <span style="color: #6b7280; display: block; font-size: 7px;">Tgl: {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}</span>
                                </div>
                            @endif
                        @else
                            <div style="font-weight: bold; color: #6b7280; font-style: italic;">PENDING / TIDAK DISETUJUI</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="page-footer">
            <span class="master-copy-stamp">MASTER COPY</span>
            <span class="doc-code">LAMP. C PR-01/QA/001.00</span>
            <span class="master-stamp">MASTER</span>
            <span>Hal. 1 dari 2</span>
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
                    FORM CHANGE REQUEST<br>(CRB)
                </td>
                <td class="doc-no-cell">
                    No. CM- 01/QA/001C.00
                </td>
            </tr>
        </table>

        <!-- Info Page 2 -->
        <table class="form-table">
            <tr>
                <td style="width: 50%;">
                    <span class="label-bold">I. PENGENDALIAN PERUBAHAN NO. :</span> <span class="label-gray">( diisi oleh QA )</span><br>
                    <strong>{{ $changeRequest->cr_number }}</strong>
                </td>
                <td>
                    <span class="label-bold">II. NAMA DOKUMEN / NOMOR DOKUMEN :</span><br>
                    <span>{{ $qa2['nama_produk'] ?? '-' }}</span>
                </td>
            </tr>
        </table>

        <!-- Tindak Lanjut -->
        <table class="form-table">
            <tr>
                <td class="bg-light">
                    <span class="label-bold">III. TINDAK LANJUT PERUBAHAN :</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; line-height: 1.5;">
                    <div>
                        <span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option1') ? '✓' : '' }}</span> 
                        Perubahan dapat langsung dilaksanakan tanpa menunggu izin dari Badan POM karena :
                        <div style="margin-left: 20px; margin-top: 4px;">
                            <span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option1') ? '✓' : '' }}</span> 
                            Pemberitahuan akan disampaikan bersama dengan perubahan dokumen yang bersangkutan oleh Departemen R&D secara bertahap.
                        </div>
                        <div style="margin-left: 20px; margin-top: 2px;">
                            <span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option2') ? '✓' : '' }}</span> 
                            Tidak diperlukan pemberitahuan perubahan.
                        </div>
                    </div>
                    <div style="border-top: 1px dashed #000; padding-top: 6px; margin-top: 6px;">
                        <span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option3') ? '✓' : '' }}</span> 
                        Perubahan memerlukan izin Badan POM terlebih dahulu :
                        <div style="margin-left: 20px; margin-top: 4px;">
                            a. Pemberitahuan perubahan akan dilaporkan oleh : <u>{{ $qa2['pom_pemberitahuan_dari'] ?? '........................' }}</u> tanggal <u>{{ $qa2['pom_pemberitahuan_sampai'] ?? '........................' }}</u><br>
                            b. Perubahan tidak dapat dilaksanakan sebelum persetujuan Badan POM diterima.<br>
                            c. Perubahan telah disetujui oleh Badan POM tanggal : <u>{{ $qa2['pom_rencana_disetujui_tanggal'] ?? '........................' }}</u>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Dokumen Lain yang Perlu Direvisi -->
        <table class="form-table" style="font-size: 10px;">
            <thead>
                <tr class="bg-light" style="font-weight: bold; text-align: center;">
                    <td colspan="3">IV. DOKUMEN LAIN YANG PERLU DIREVISI</td>
                </tr>
                <tr style="font-weight: bold; text-align: center;">
                    <td style="width: 40%;">NAMA DOKUMEN</td>
                    <td style="width: 30%;">NO. DOKUMEN</td>
                    <td style="width: 30%;">TANGGAL BERLAKU</td>
                </tr>
            </thead>
            <tbody>
                @php
                    $docs = $qa2['documents'] ?? [];
                @endphp
                @if(count($docs) > 0)
                    @foreach($docs as $doc)
                        <tr>
                            <td>{{ $doc['jenis'] ?? '-' }}</td>
                            <td>{{ $doc['no_dokumen'] ?? '-' }}</td>
                            <td style="text-align: center;">{{ $doc['tanggal_berlaku'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                @else
                    @for($i=1; $i<=4; $i++)
                        <tr style="height: 25px;">
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>

        <!-- Implementasi -->
        <table class="form-table" style="font-size: 10px; margin-top: 5px;">
            <thead>
                <tr class="bg-light" style="font-weight: bold; text-align: center;">
                    <td colspan="3">V. IMPLEMENTASI</td>
                </tr>
                <tr style="font-size: 9px;">
                    <td colspan="3" style="padding: 4px;">
                        Semua dokumen yang diperlukan telah selesai disiapkan. Dilaksanakan oleh :
                    </td>
                </tr>
                <tr style="font-weight: bold; text-align: center;">
                    <td style="width: 40%;">Nama / departemen</td>
                    <td style="width: 30%;">Tanda tangan</td>
                    <td style="width: 30%;">Tanggal</td>
                </tr>
            </thead>
            <tbody>
                @php
                    $impls = $qa3['implementations'] ?? [];
                @endphp
                @if(count($impls) > 0)
                    @foreach($impls as $imp)
                        <tr>
                            <td>{{ $imp['pic'] ?? '-' }}</td>
                            <td style="text-align: center; color: #6b7280; font-size: 8px;">(Tanda Tangan)</td>
                            <td style="text-align: center;">{{ !empty($imp['tanggal_dilakukan']) ? \Carbon\Carbon::parse($imp['tanggal_dilakukan'])->format('d/m/Y') : '-' }}</td>
                        </tr>
                    @endforeach
                @else
                    @for($i=1; $i<=2; $i++)
                        <tr style="height: 25px;">
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>

        <!-- Verifikasi -->
        <table class="form-table" style="margin-top: 5px;">
            <tr>
                <td colspan="3" class="bg-light">
                    <span class="label-bold">VI. VERIFIKASI</span>
                </td>
            </tr>
            <tr>
                <td style="width: 70%; padding: 4px;">1. Semua dokumen yang diperlukan sudah dibuat / direvisi</td>
                <td style="width: 15%; text-align: center;"><span class="chk-box">{{ ($qa3['verifikasi_completed'] ?? false) ? '✓' : '' }}</span> Sudah</td>
                <td style="width: 15%; text-align: center;"><span class="chk-box">{{ !($qa3['verifikasi_completed'] ?? false) ? '✓' : '' }}</span> Belum</td>
            </tr>
            <tr>
                <td style="padding: 4px;">2. Surat persetujuan dari Badan POM sudah diterima *</td>
                <td style="text-align: center;"><span class="chk-box">{{ (($qa2['pom_status'] ?? '') === 'option3') ? '✓' : '' }}</span> Sudah</td>
                <td style="text-align: center;"><span class="chk-box">{{ (($qa2['pom_status'] ?? '') !== 'option3') ? '✓' : '' }}</span> Belum</td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 6px;">
                    <div style="display: flex; justify-content: space-between;">
                        <div>Diverifikasi oleh: <u>{{ $qa1['diulas_oleh'] ?? '........................' }}</u></div>
                        <div>Paraf: <span class="chk-box">✓</span></div>
                        <div>Tanggal: {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '........................' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Kesimpulan -->
        <table class="form-table" style="margin-top: 5px;">
            <tr>
                <td class="bg-light">
                    <span class="label-bold">VII. KESIMPULAN</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px;">
                    Semua dokumen telah selesai disiapkan dan perubahan dapat diberlakukan sejak : 
                    <u>{{ ($changeRequest->status === 'COMPLETE' && !empty($changeRequest->updated_at)) ? $changeRequest->updated_at->format('d/m/Y') : '........................' }}</u>
                </td>
            </tr>
        </table>

        <!-- Approval Final -->
        <table class="signature-table" style="margin-top: 10px; width: 100%;">
            <tr>
                <td style="border: none; width: 60%;"></td>
                <td style="border: 1px solid #000; padding: 6px;">
                    <span class="label-bold">Disetujui oleh :</span><br>
                    <strong>QA Supervisor</strong>
                    @if(($qa1['hu_approved'] ?? '') === 'APPROVED')
                        @if($huUser && $huUser->signature_path)
                            <div style="text-align: center; margin-top: 5px;">
                                <img src="{{ asset('storage/' . $huUser->signature_path) }}" style="max-height: 70px; height: 70px; max-width: 170px; object-fit: contain; display: block; margin: 0 auto 4px;" /><br>
                                <span style="font-weight: bold; text-decoration: underline; font-size: 10px;">{{ $huUser->name }}</span>
                            </div>
                        @else
                            <div style="text-align: center; border: 1px dashed #22c55e; padding: 4px; border-radius: 4px; background-color: #f0fdf4; margin-top: 5px;">
                                <span style="color: #16a34a; font-weight: bold; display: block; font-size: 8px;">✓ DISETUJUI ELEKTRONIK</span>
                                <span style="font-family: 'Courier New', Courier, monospace; font-weight: bold; display: block; font-size: 10px; margin: 2px 0;">{{ $huUser->name ?? ($qa1['diulas_oleh'] ?? 'Head of Quality') }}</span>
                                <span style="color: #6b7280; display: block; font-size: 7px;">Tgl: {{ !empty($qa1['tanggal']) ? \Carbon\Carbon::parse($qa1['tanggal'])->format('d/m/Y') : '' }}</span>
                            </div>
                        @endif
                    @else
                        <div class="sig-space" style="display: flex; align-items: flex-end; justify-content: center;">
                            <span style="font-style: italic; color: #6b7280;">(Tanda Tangan)</span>
                        </div>
                        <div>Tanggal : ........................</div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="page-footer">
            <span class="master-copy-stamp">MASTER COPY</span>
            <span class="doc-code">LAMP. C PR-01/QA/001.00</span>
            <span class="master-stamp">MASTER</span>
            <span>Hal. 2 dari 2</span>
        </div>
    </div>

</body>
</html>
