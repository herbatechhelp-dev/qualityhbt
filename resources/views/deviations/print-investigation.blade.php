<!DOCTYPE html>
@php
    $logoType = \App\Models\Setting::getValue('print_logo_type', 'text');
    $logoText = \App\Models\Setting::getValue('print_company_name', 'HERBATECH');
    $logoPath = \App\Models\Setting::getValue('print_logo_path');

    $formatFishbone = function($text, $default = '') {
        $val = $text ?? $default;
        if (!$val) return '';
        $lines = explode("\n", $val);
        $formatted = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;
            if (preg_match('/^[\-\*\•\d+\.\)]/', $trimmed)) {
                $formatted[] = e($trimmed);
            } else {
                $formatted[] = '- ' . e($trimmed);
            }
        }
        return implode("<br />", $formatted);
    };
@endphp
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Penyelidikan Ketidaksesuaian - {{ $deviation->deviation_number }}</title>
    <style>
        /* CSS reset & variables */
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Page styling for printing */
        .page {
            box-sizing: border-box;
            background-color: #fff;
            position: relative;
        }
        
        .page-landscape {
            width: 297mm;
            height: 210mm;
            padding: 12mm;
            page-break-after: always;
        }
        
        .page-portrait {
            width: 210mm;
            height: 297mm;
            padding: 15mm;
            page-break-after: avoid;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }
            .page-landscape {
                width: 100%;
                height: 100%;
                page-break-after: always;
                page-break-inside: avoid;
                margin: 0;
                padding: 10mm;
            }
            .page-portrait {
                width: 100%;
                height: 100%;
                page-break-before: always;
                page-break-after: avoid;
                page-break-inside: avoid;
                margin: 0;
                padding: 10mm;
            }
            /* Change page size dynamically for portrait page */
            .page-portrait-parent {
                page: portrait-page;
            }
        }
        
        @page portrait-page {
            size: A4 portrait;
            margin: 0;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle;
        }
        .logo-section {
            width: 25%;
            text-align: center;
        }
        .logo-text {
            font-size: 8px;
            font-weight: bold;
            color: #15803d;
            margin-top: 2px;
            letter-spacing: 1px;
        }
        .title-section {
            width: 50%;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }
        .doc-no-section {
            width: 25%;
            text-align: center;
            font-weight: bold;
            font-size: 8px;
        }

        /* Metadata table */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .meta-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }
        .bg-light {
            background-color: #f3f4f6;
        }
        .label-bold {
            font-weight: bold;
        }
        
        /* Fishbone Diagram CSS */
        .fishbone-container {
            border: 1px solid #000;
            padding: 15px;
            height: 115mm;
            position: relative;
            box-sizing: border-box;
            background-color: #fff;
            margin-top: 5px;
        }
        .backbone {
            position: absolute;
            top: 52%;
            left: 5%;
            width: 80%;
            height: 0;
            border-top: 3px solid #000;
        }
        .backbone-arrow {
            position: absolute;
            top: 52%;
            left: 85%;
            width: 0;
            height: 0;
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            border-left: 15px solid #000;
            margin-top: -6px;
        }
        .effect-box {
            position: absolute;
            top: 52%;
            left: 87%;
            width: 11%;
            border: 2px solid #000;
            background-color: #f9fafb;
            padding: 4px;
            font-weight: bold;
            text-align: center;
            margin-top: -20px;
            font-size: 8px;
            line-height: 1.2;
        }
        
        /* Diagonal rib line helper */
        .rib {
            position: absolute;
            width: 0;
            height: 115px;
            border-left: 2px solid #000;
            transform-origin: top left;
        }
        
        /* Rib Positions */
        /* Rib Top 1 (Machine) */
        .rib-top-1 {
            top: 15%;
            left: 20%;
            height: 115px;
            transform: rotate(-45deg);
        }
        /* Rib Top 2 (Man) */
        .rib-top-2 {
            top: 15%;
            left: 45%;
            height: 115px;
            transform: rotate(-45deg);
        }
        /* Rib Top 3 (Method) */
        .rib-top-3 {
            top: 15%;
            left: 70%;
            height: 115px;
            transform: rotate(-45deg);
        }
        /* Rib Bottom 1 (Milieu) */
        .rib-bottom-1 {
            top: 53%;
            left: 11.5%;
            height: 115px;
            transform: rotate(45deg);
        }
        /* Rib Bottom 2 (Measurement) */
        .rib-bottom-2 {
            top: 53%;
            left: 36.5%;
            height: 115px;
            transform: rotate(45deg);
        }
        /* Rib Bottom 3 (Materials) */
        .rib-bottom-3 {
            top: 53%;
            left: 61.5%;
            height: 115px;
            transform: rotate(45deg);
        }
        
        /* Label boxes */
        .bone-label {
            position: absolute;
            border: 1px solid #000;
            padding: 3px 10px;
            font-weight: bold;
            background-color: #e5e7eb;
            font-size: 9px;
            min-width: 90px;
            text-align: center;
        }
        
        .label-machine    { top: 5%; left: 13%; }
        .label-man        { top: 5%; left: 38%; }
        .label-method     { top: 5%; left: 63%; }
        .label-milieu     { bottom: 5%; left: 13%; }
        .label-measurement{ bottom: 5%; left: 38%; }
        .label-materials  { bottom: 5%; left: 63%; }
        
        /* Text input boxes along bones */
        .bone-input-box {
            position: absolute;
            border: 1px solid #ccc;
            width: 100px;
            height: 38px;
            padding: 3px;
            font-size: 7.5px;
            background-color: #fafafa;
            border-radius: 4px;
            overflow: hidden;
            line-height: 1.1;
        }
        
        .box-machine     { top: 22%; left: 19%; }
        .box-man         { top: 22%; left: 44%; }
        .box-method      { top: 22%; left: 69%; }
        .box-milieu      { bottom: 22%; left: 19%; }
        .box-measurement  { bottom: 22%; left: 44%; }
        .box-materials   { bottom: 22%; left: 69%; }

        /* Tables Page 2 */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .form-table td, .form-table th {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }
        
        /* Signatures footer */
        .page-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8px;
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: auto;
        }
        .master-copy-stamp {
            border: 1px solid #000;
            padding: 1px 4px;
            font-weight: bold;
            font-size: 8px;
        }
        .master-stamp {
            border: 1px solid #16a34a;
            color: #16a34a;
            padding: 1px 4px;
            font-weight: bold;
            font-size: 8px;
        }
        
        .approval-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .approval-table td {
            border: 1px solid #000;
            width: 33.33%;
            padding: 6px;
            text-align: center;
        }
        .approval-sig-space {
            height: 55px;
            vertical-align: middle;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    <!-- ================= PAGE 1 (LANDSCAPE) ================= -->
    <div class="page page-landscape">
        <div style="display: flex; flex-direction: column; height: 100%;">
            <!-- Header -->
            <table class="header-table">
                <tr>
                    <td class="logo-section">
                        @if($logoType === 'image' && !empty($logoPath))
                            <img src="{{ asset('storage/' . $logoPath) }}" style="max-height: 25px; max-width: 120px; object-fit: contain; display: block; margin: 0 auto;" />
                        @else
                            <span class="logo-text">{{ strtoupper($logoText) }}</span>
                        @endif
                    </td>
                    <td class="title-section">
                        FORM PENYELIDIKAN KETIDAKSESUAIAN
                    </td>
                    <td class="doc-no-section">
                        No. CM-09/QA/003D.01
                    </td>
                </tr>
            </table>

            <!-- Metadata Box -->
            <table class="meta-table">
                <tr>
                    <td style="width: 25%;"><span class="label-bold">No. Form</span> (diisi oleh QA)</td>
                    <td style="width: 25%; font-family: monospace;">{{ $deviation->deviation_number }}</td>
                    <td style="width: 25%;"><span class="label-bold">Tanggal Penyelidikan</span></td>
                    <td style="width: 25%;">{{ $deviation->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td><span class="label-bold">Sumber Ketidaksesuaian</span></td>
                    <td colspan="3">Laporan Deviasi No. {{ $deviation->deviation_number }}</td>
                </tr>
            </table>

            <div class="label-bold" style="font-size: 10px; margin-top: 4px;">A. Uraian Penyelidikan/ Investigasi :</div>
            
            <!-- Fishbone Diagram SVG -->
            <div class="fishbone-container" style="border: 1px solid #000; padding: 10px; margin-top: 5px; height: auto;">
                <svg viewBox="0 0 970 400" style="width: 100%; height: auto; display: block; background-color: #fff;">
                    <!-- Backbone Line (Horizontal Spine) -->
                    <line x1="40" y1="200" x2="780" y2="200" stroke="#000" stroke-width="3" />
                    <!-- Backbone Arrow -->
                    <polygon points="780,194 795,200 780,206" fill="#000" />
                    
                    <!-- Effect Box (Sumber Ketidaksesuaian) -->
                    <foreignObject x="805" y="175" width="155" height="50">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-size: 8px; font-weight: bold; border: 2px solid #000; background-color: #f9fafb; padding: 6px; box-sizing: border-box; height: 100%; display: flex; align-items: center; justify-content: center; text-align: center; line-height: 1.2; font-family: Arial, sans-serif;">
                            SUMBER KETIDAKSESUAIAN
                        </div>
                    </foreignObject>

                    <!-- --- TOP BRANCHES --- -->
                    <!-- Machine Rib -->
                    <line x1="160" y1="40" x2="260" y2="200" stroke="#000" stroke-width="2" />
                    <foreignObject x="100" y="15" width="120" height="25">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 9px; font-weight: bold; border: 1px solid #000; background-color: #e5e7eb; border-radius: 2px; line-height: 23px; text-align: center; height: 100%; box-sizing: border-box;">
                            Machine
                        </div>
                    </foreignObject>
                    <foreignObject x="25" y="65" width="130" height="55">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 7.5px; border: 1px solid #cbd5e1; background-color: #fafafa; padding: 5px; border-radius: 4px; line-height: 1.25; box-sizing: border-box; height: 100%; overflow: hidden; text-align: left; white-space: pre-wrap;">
                            {!! $formatFishbone($deviation->fishbone_machine, 'Pengecekan mesin & alat penunjang operasional produksi.') !!}
                        </div>
                    </foreignObject>
                    <!-- Connector horizontal box to rib -->
                    <line x1="155" y1="92" x2="192" y2="92" stroke="#000" stroke-width="1" stroke-dasharray="2,2" />

                    <!-- Man Rib -->
                    <line x1="420" y1="40" x2="520" y2="200" stroke="#000" stroke-width="2" />
                    <foreignObject x="360" y="15" width="120" height="25">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 9px; font-weight: bold; border: 1px solid #000; background-color: #e5e7eb; border-radius: 2px; line-height: 23px; text-align: center; height: 100%; box-sizing: border-box;">
                            Man
                        </div>
                    </foreignObject>
                    <foreignObject x="285" y="65" width="130" height="55">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 7.5px; border: 1px solid #cbd5e1; background-color: #fafafa; padding: 5px; border-radius: 4px; line-height: 1.25; box-sizing: border-box; height: 100%; overflow: hidden; text-align: left; white-space: pre-wrap;">
                            {!! $formatFishbone($deviation->fishbone_man, 'Pemeriksaan kepatuhan personalia & pelatihan higienitas.') !!}
                        </div>
                    </foreignObject>
                    <line x1="415" y1="92" x2="452" y2="92" stroke="#000" stroke-width="1" stroke-dasharray="2,2" />

                    <!-- Method/Process Rib -->
                    <line x1="680" y1="40" x2="780" y2="200" stroke="#000" stroke-width="2" />
                    <foreignObject x="620" y="15" width="120" height="25">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 9px; font-weight: bold; border: 1px solid #000; background-color: #e5e7eb; border-radius: 2px; line-height: 23px; text-align: center; height: 100%; box-sizing: border-box;">
                            Method/Process
                        </div>
                    </foreignObject>
                    <foreignObject x="545" y="65" width="130" height="55">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 7.5px; border: 1px solid #cbd5e1; background-color: #fafafa; padding: 5px; border-radius: 4px; line-height: 1.25; box-sizing: border-box; height: 100%; overflow: hidden; text-align: left; white-space: pre-wrap;">
                            {!! $formatFishbone($deviation->fishbone_method, 'Evaluasi prosedur kerja standard (SOP) saat kejadian.') !!}
                        </div>
                    </foreignObject>
                    <line x1="675" y1="92" x2="712" y2="92" stroke="#000" stroke-width="1" stroke-dasharray="2,2" />

                    <!-- --- BOTTOM BRANCHES --- -->
                    <!-- Milieu Rib -->
                    <line x1="160" y1="360" x2="260" y2="200" stroke="#000" stroke-width="2" />
                    <foreignObject x="100" y="360" width="120" height="25">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 9px; font-weight: bold; border: 1px solid #000; background-color: #e5e7eb; border-radius: 2px; line-height: 23px; text-align: center; height: 100%; box-sizing: border-box;">
                            Milieu
                        </div>
                    </foreignObject>
                    <foreignObject x="25" y="280" width="130" height="55">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 7.5px; border: 1px solid #cbd5e1; background-color: #fafafa; padding: 5px; border-radius: 4px; line-height: 1.25; box-sizing: border-box; height: 100%; overflow: hidden; text-align: left; white-space: pre-wrap;">
                            {!! $formatFishbone($deviation->fishbone_milieu, 'Pemantauan kondisi lingkungan ruang pengolahan/kelas.') !!}
                        </div>
                    </foreignObject>
                    <line x1="155" y1="308" x2="192" y2="308" stroke="#000" stroke-width="1" stroke-dasharray="2,2" />

                    <!-- Measurement Rib -->
                    <line x1="420" y1="360" x2="520" y2="200" stroke="#000" stroke-width="2" />
                    <foreignObject x="360" y="360" width="120" height="25">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 9px; font-weight: bold; border: 1px solid #000; background-color: #e5e7eb; border-radius: 2px; line-height: 23px; text-align: center; height: 100%; box-sizing: border-box;">
                            Measurement
                        </div>
                    </foreignObject>
                    <foreignObject x="285" y="280" width="130" height="55">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 7.5px; border: 1px solid #cbd5e1; background-color: #fafafa; padding: 5px; border-radius: 4px; line-height: 1.25; box-sizing: border-box; height: 100%; overflow: hidden; text-align: left; white-space: pre-wrap;">
                            {!! $formatFishbone($deviation->fishbone_measurement, 'Verifikasi alat ukur, kalibrasi instrumen, dan IPC.') !!}
                        </div>
                    </foreignObject>
                    <line x1="415" y1="308" x2="452" y2="308" stroke="#000" stroke-width="1" stroke-dasharray="2,2" />

                    <!-- Materials Rib -->
                    <line x1="680" y1="360" x2="780" y2="200" stroke="#000" stroke-width="2" />
                    <foreignObject x="620" y="360" width="120" height="25">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 9px; font-weight: bold; border: 1px solid #000; background-color: #e5e7eb; border-radius: 2px; line-height: 23px; text-align: center; height: 100%; box-sizing: border-box;">
                            Materials
                        </div>
                    </foreignObject>
                    <foreignObject x="545" y="280" width="130" height="55">
                        <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; font-size: 7.5px; border: 1px solid #cbd5e1; background-color: #fafafa; padding: 5px; border-radius: 4px; line-height: 1.25; box-sizing: border-box; height: 100%; overflow: hidden; text-align: left; white-space: pre-wrap;">
                            {!! $formatFishbone($deviation->fishbone_materials, 'Analisis bahan awal, kemasan primer, & identitas bets.') !!}
                        </div>
                    </foreignObject>
                    <line x1="675" y1="308" x2="712" y2="308" stroke="#000" stroke-width="1" stroke-dasharray="2,2" />
                </svg>
            </div>

            <!-- Footer -->
            <div class="page-footer" style="margin-top: auto;">
                <span class="master-copy-stamp">MASTER COPY</span>
                <span class="doc-code">LAMP. D PR-09/QA/003.01</span>
                <span class="master-stamp">MASTER</span>
                <span>Halaman 1 dari 2</span>
            </div>
        </div>
    </div>

    <!-- ================= PAGE 2 (PORTRAIT) ================= -->
    <div class="page-portrait-parent">
        <div class="page page-portrait">
            <div style="display: flex; flex-direction: column; height: 100%;">
                <!-- Header -->
                <table class="header-table">
                    <tr>
                        <td class="logo-section">
                            @if($logoType === 'image' && !empty($logoPath))
                                <img src="{{ asset('storage/' . $logoPath) }}" style="max-height: 25px; max-width: 120px; object-fit: contain; display: block; margin: 0 auto;" />
                            @else
                                <span class="logo-text">{{ strtoupper($logoText) }}</span>
                            @endif
                        </td>
                        <td class="title-section">
                            FORM PENYELIDIKAN KETIDAKSESUAIAN
                        </td>
                        <td class="doc-no-section">
                            No. CM-09/QA/003D.01
                        </td>
                    </tr>
                </table>

                <!-- Content -->
                <div style="margin-bottom: 10px;">
                    <div class="label-bold" style="margin-bottom: 4px;">A. Akar Masalah/ Root Cause :</div>
                    <div style="min-height: 40px; border: 1px solid #000; padding: 6px; font-family: monospace; font-size: 9px; line-height: 1.4; background-color: #fafafa; border-radius: 4px;">
                        {!! nl2br(e($deviation->root_cause ?? "Berdasarkan investigasi fishbone, akar masalah disebabkan oleh:\n- Kriteria kesesuaian operasional alat/mesin yang belum terkalibrasi berkala.\n- Diperlukan peningkatan pengawasan In Process Control (IPC).")) !!}
                    </div>
                </div>

                <div style="margin-bottom: 10px;">
                    <div class="label-bold" style="margin-bottom: 4px;">B. Identifikasi Risiko :</div>
                    <div style="min-height: 35px; border: 1px solid #000; padding: 6px; font-family: monospace; font-size: 9px; line-height: 1.4; background-color: #fafafa; border-radius: 4px;">
                        {!! nl2br(e($deviation->risk_identification_details ?? "1. Potensi imbas pada bets produk terkait yang diproses pada hari yang sama.\n2. Risiko penurunan spesifikasi mutu atau stabilitas produk akhir.")) !!}
                    </div>
                </div>

                <div style="margin-bottom: 10px;">
                    <div class="label-bold" style="margin-bottom: 4px;">C. Analisa Risiko :</div>
                    <div style="min-height: 35px; border: 1px solid #000; padding: 6px; font-family: monospace; font-size: 9px; line-height: 1.4; background-color: #fafafa; border-radius: 4px;">
                        {!! nl2br(e($deviation->risk_analysis_details ?? "Kajian risiko dilakukan menggunakan Failure Mode and Effects Analysis (FMEA) untuk menghitung tingkat keparahan (S), frekuensi (O), dan kemampuan deteksi (D).")) !!}
                    </div>
                </div>

                <!-- D. Keberterimaan Risiko -->
                <div class="label-bold" style="margin-bottom: 4px;">D. Keberterimaan Risiko</div>
                <table class="form-table" style="font-size: 8.5px;">
                    <thead>
                        <tr style="text-align: center; font-weight: bold; background-color: #e5e7eb; height: 26px;">
                            <th rowspan="2" style="width: 4%;">No</th>
                            <th rowspan="2" style="width: 20%;">Failure Mode</th>
                            <th rowspan="2" style="width: 20%;">Failure Effect</th>
                            <th colspan="4">Risk Value</th>
                            <th rowspan="2" style="width: 24%;">Control Risk (Risk Accepted/ Risk Reduce)</th>
                            <th colspan="4">Expected Risk</th>
                        </tr>
                        <tr style="text-align: center; font-weight: bold; background-color: #f3f4f6;">
                            <th style="width: 4%;">S</th>
                            <th style="width: 4%;">O</th>
                            <th style="width: 4%;">D</th>
                            <th style="width: 8%;">RPN</th>
                            <th style="width: 4%;">S</th>
                            <th style="width: 4%;">O</th>
                            <th style="width: 4%;">D</th>
                            <th style="width: 8%;">RPN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $risks = $deviation->risk_analysis ?? [];
                        @endphp
                        @if(count($risks) > 0)
                            @foreach($risks as $index => $risk)
                                <tr>
                                    <td style="text-align: center;">{{ $index + 1 }}</td>
                                    <td>{{ $risk['risk_identification'] ?? 'Risk' }}</td>
                                    <td>{{ $risk['potensiasi_cause'] ?? 'Cause' }}</td>
                                    <td style="text-align: center;">{{ $risk['s'] ?? '1' }}</td>
                                    <td style="text-align: center;">{{ $risk['o'] ?? '1' }}</td>
                                    <td style="text-align: center;">{{ $risk['d'] ?? '1' }}</td>
                                    <td style="text-align: center; font-weight: bold;">{{ $risk['rpn'] ?? '1' }}</td>
                                    <td>{{ $risk['risk_control'] ?? 'Control' }}</td>
                                    <td style="text-align: center;">{{ $risk['s_after'] ?? '1' }}</td>
                                    <td style="text-align: center;">{{ $risk['o_after'] ?? '1' }}</td>
                                    <td style="text-align: center;">{{ $risk['d_after'] ?? '1' }}</td>
                                    <td style="text-align: center; font-weight: bold;">
                                        {{ $risk['rpn_after'] ?? '1' }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            @for($i = 1; $i <= 3; $i++)
                                <tr style="height: 25px;">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
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

                <div style="margin-top: 5px;">
                    <strong>E. Tindakan Perbaikan dan Pencegahan No.</strong> <u>{{ $deviation->capa->capa_number ?? '............................................' }}</u>
                </div>

                <!-- F. Persetujuan -->
                <div class="label-bold" style="margin-top: 10px; margin-bottom: 2px;">F. Persetujuan</div>
                <table class="approval-table">
                    <tr style="font-weight: bold; background-color: #e5e7eb;">
                        <td>Disusun Oleh:</td>
                        <td>Diperiksa Oleh:</td>
                        <td>Disetujui Oleh:</td>
                    </tr>
                    <tr>
                        <td style="font-size: 8px;"><strong>Inisiator (Pelapor)</strong></td>
                        <td style="font-size: 8px;"><strong>QA Supervisor / HU</strong></td>
                        <td style="font-size: 8px;"><strong>General Manager (GM)</strong></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="approval-sig-space">
                                @if($deviation->initiator && $deviation->initiator->signature_path)
                                    <img src="{{ asset('storage/' . $deviation->initiator->signature_path) }}" style="max-height: 40px; max-width: 100px; object-fit: contain;" />
                                @else
                                    <div style="border: 1px dashed #3b82f6; padding: 2px; background-color: #eff6ff; border-radius: 4px; font-size: 7px; line-height: 1;">
                                        <span style="color: #2563eb; font-weight: bold; display: block;">✓ DIAJUKAN</span>
                                        <span style="font-family: monospace;">{{ $deviation->initiator->name ?? 'Inisiator' }}</span>
                                    </div>
                                @endif
                            </div>
                            <div style="border-top: 1px solid #000; padding-top: 3px; font-weight: bold;">
                                {{ $deviation->initiator->name ?? 'Inisiator' }}
                            </div>
                        </td>
                        <td>
                            <div class="approval-sig-space">
                                @if($deviation->status === 'APPROVED')
                                    @if($huUser && $huUser->signature_path)
                                        <img src="{{ asset('storage/' . $huUser->signature_path) }}" style="max-height: 40px; max-width: 100px; object-fit: contain;" />
                                    @else
                                        <div style="border: 1px dashed #22c55e; padding: 2px; background-color: #f0fdf4; border-radius: 4px; font-size: 7px; line-height: 1;">
                                            <span style="color: #16a34a; font-weight: bold; display: block;">✓ DISETUJUI</span>
                                            <span style="font-family: monospace;">{{ $huUser->name ?? 'Head of Quality' }}</span>
                                        </div>
                                    @endif
                                @else
                                    <span style="color: #6b7280; font-style: italic;">PENDING</span>
                                @endif
                            </div>
                            <div style="border-top: 1px solid #000; padding-top: 3px; font-weight: bold;">
                                {{ $huUser->name ?? 'Head of Quality' }}
                            </div>
                        </td>
                        <td>
                            <div class="approval-sig-space">
                                @if($deviation->status === 'APPROVED')
                                    @if($gmUser && $gmUser->signature_path)
                                        <img src="{{ asset('storage/' . $gmUser->signature_path) }}" style="max-height: 40px; max-width: 100px; object-fit: contain;" />
                                    @else
                                        <div style="border: 1px dashed #22c55e; padding: 2px; background-color: #f0fdf4; border-radius: 4px; font-size: 7px; line-height: 1;">
                                            <span style="color: #16a34a; font-weight: bold; display: block;">✓ DISETUJUI</span>
                                            <span style="font-family: monospace;">{{ $gmUser->name ?? 'General Manager' }}</span>
                                        </div>
                                    @endif
                                @else
                                    <span style="color: #6b7280; font-style: italic;">(GM)</span>
                                @endif
                            </div>
                            <div style="border-top: 1px solid #000; padding-top: 3px; font-weight: bold;">
                                {{ $gmUser->name ?? 'General Manager' }}
                            </div>
                        </td>
                    </tr>
                    <tr style="font-size: 8.5px; font-weight: bold;">
                        <td>Tgl: {{ $deviation->created_at->format('d/m/Y') }}</td>
                        <td>Tgl: {{ $deviation->status === 'APPROVED' ? $deviation->updated_at->format('d/m/Y') : '...................' }}</td>
                        <td>Tgl: {{ $deviation->status === 'APPROVED' ? $deviation->updated_at->format('d/m/Y') : '...................' }}</td>
                    </tr>
                </table>

                <!-- Footer -->
                <div class="page-footer" style="margin-top: auto;">
                    <span class="master-copy-stamp">MASTER COPY</span>
                    <span class="doc-code">LAMP. D PR-09/QA/003.01</span>
                    <span class="master-stamp">MASTER</span>
                    <span>Halaman 2 dari 2</span>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
