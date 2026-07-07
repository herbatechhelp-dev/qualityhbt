<!DOCTYPE html>
@php
    $logoType = \App\Models\Setting::getValue('app_logo_type', 'text');
    $logoText = \App\Models\Setting::getValue('app_name', 'HERBATECH');
    $logoPath = \App\Models\Setting::getValue('app_logo_path');
@endphp
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Deviation Report - {{ $deviation->deviation_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }
        .page {
            position: relative;
            width: 100%;
            height: 267mm; /* Approximate A4 height minus margins */
            box-sizing: border-box;
            page-break-after: always;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .page:last-child {
            page-break-after: avoid;
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
        .logo-img {
            max-height: 30px;
            display: block;
            margin: 0 auto;
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
            font-size: 12px;
        }
        .doc-no-section {
            width: 25%;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
        }

        /* Form Table styling */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .form-table td, .form-table th {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: top;
        }
        .bg-light {
            background-color: #f3f4f6;
        }
        .label-bold {
            font-weight: bold;
        }
        .label-gray {
            font-size: 8px;
            color: #4b5563;
        }
        
        /* Checkbox styling */
        .chk-box {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 1px solid #000;
            text-align: center;
            line-height: 9px;
            font-size: 8px;
            font-weight: bold;
            margin-right: 4px;
            background-color: #fff;
        }
        .chk-container {
            display: inline-block;
            width: 31%;
            margin-bottom: 5px;
            vertical-align: top;
        }
        
        /* Signature Area */
        .sig-container {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        .sig-box {
            width: 48%;
            border: 1px solid #000;
            padding: 6px;
            box-sizing: border-box;
        }
        .sig-space {
            height: 50px;
            margin: 4px 0;
            text-align: center;
            vertical-align: middle;
        }
        
        /* Footer */
        .page-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8px;
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: auto;
        }
        .master-stamp {
            border: 1px solid #16a34a;
            color: #16a34a;
            padding: 1px 4px;
            font-weight: bold;
            font-size: 8px;
        }
        .master-copy-stamp {
            border: 1px solid #000;
            padding: 1px 4px;
            font-weight: bold;
            font-size: 8px;
        }
    </style>
</head>
<body>

    <!-- ================= PAGE 1 ================= -->
    <div class="page">
        <div>
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
                        FORM DEVIATION REPORT
                    </td>
                    <td class="doc-no-section">
                        No. CM-09/QA/003B.01
                    </td>
                </tr>
            </table>

            <!-- Metadata Box -->
            <table class="form-table">
                <tr>
                    <td style="width: 35%;">No. Deviation Report <span class="label-gray">(diisi oleh QA)</span></td>
                    <td style="width: 2%;">:</td>
                    <td style="font-weight: bold; font-family: monospace;">{{ $deviation->deviation_number }}</td>
                </tr>
                <tr>
                    <td>Nama Produk/ Proses/ RM/ PM/ Sistem/ Alat*</td>
                    <td>:</td>
                    <td>{{ $deviation->pic ?? '-' }}</td>
                </tr>
                <tr>
                    <td>No. Bets / Alat / Dokumen / Identitas lainnya</td>
                    <td>:</td>
                    <td>{{ $deviation->department ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Tanggal Terjadi Penyimpangan</td>
                    <td>:</td>
                    <td>{{ $deviation->tanggal_temuan ? \Carbon\Carbon::parse($deviation->tanggal_temuan)->format('d/m/Y') : '-' }}</td>
                </tr>
            </table>
            <div style="font-size: 7px; margin-bottom: 10px; font-style: italic;">(*) Coret yang tidak perlu</div>

            <!-- A. Rincian Penyimpangan -->
            <table class="form-table">
                <tr>
                    <td class="bg-light label-bold">A. Rincian Penyimpangan yang Terjadi :</td>
                </tr>
                <tr>
                    <td>
                        <div class="label-bold" style="margin-bottom: 6px;">I. Identifikasi Penyimpangan</div>
                        <div style="margin-bottom: 4px; font-style: italic;">Penyimpangan terjadi pada proses / tahap :</div>
                        @php
                            $stages = $deviation->jenis_penyimpangan ?? [];
                            $allStages = ['Pengolahan', 'Pengemasan', 'In Process Control', 'Dokumentasi', 'Ruangan', 'Mesin', 'Fasilitas penunjang', 'Pemantauan lingkungan / hygiene', 'Spesifikasi', 'Lain-lain'];
                        @endphp
                        <div style="margin-bottom: 8px;">
                            @foreach($allStages as $stage)
                                <div class="chk-container">
                                    <span class="chk-box">{{ in_array($stage, $stages) ? '✓' : '' }}</span> {{ $stage }}
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-bottom: 4px; font-style: italic; border-top: 1px dashed #ccc; padding-top: 6px;">Penyimpangan mempengaruhi kualitas dari :</div>
                        @php
                            $affects = $deviation->identifikasi_penyimpangan ?? [];
                            $allAffects = ['Bahan awal', 'Produk antara', 'Produk ruahan', 'Produk dalam kemasan primer', 'Produk jadi', 'Bahan pengemas', 'Lain-lain'];
                        @endphp
                        <div>
                            @foreach($allAffects as $affect)
                                <div class="chk-container">
                                    <span class="chk-box">{{ in_array($affect, $affects) ? '✓' : '' }}</span> {{ $affect }}
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label-bold" style="margin-bottom: 4px;">II. Apakah ada bets/ Produk lain yang terkena imbasnya?</div>
                        <div style="margin-bottom: 4px;">
                            <span class="chk-box"></span> Ya, sebutkan : .........................................................................
                        </div>
                        <div>
                            <span class="chk-box">✓</span> Tidak
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label-bold" style="margin-bottom: 4px;">III. Uraian Penyimpangan</div>
                        <div style="min-height: 80px; font-family: monospace; font-size: 9.5px; white-space: pre-wrap; line-height: 1.4; padding: 4px; background-color: #fafafa; border: 1px solid #ddd; border-radius: 4px;">{{ $deviation->description }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label-bold" style="margin-bottom: 4px;">IV. Frekuensi Penyimpangan</div>
                        <div style="display: flex; gap: 40px;">
                            <div><span class="chk-box"></span> Sering</div>
                            <div><span class="chk-box"></span> Jarang</div>
                            <div><span class="chk-box">✓</span> Tidak Pernah sebelumnya</div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- B. Rincian Tindakan Sementara -->
            <table class="form-table">
                <tr>
                    <td class="bg-light label-bold">B. Rincian Tindakan Sementara yang Diambil (Immediate Action)</td>
                </tr>
                <tr>
                    <td>
                        <div class="label-bold" style="margin-bottom: 4px;">I. Penghentian Proses Produksi</div>
                        <div style="display: flex; gap: 40px; margin-bottom: 8px;">
                            <div><span class="chk-box"></span> Ya</div>
                            <div><span class="chk-box">✓</span> Tidak</div>
                        </div>
                        <div class="label-bold" style="margin-bottom: 4px; border-top: 1px dashed #ccc; padding-top: 6px;">II. Penanganan cepat lain terhadap produk</div>
                        <div style="font-style: italic; color: #4b5563; min-height: 30px;">
                            -
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Signatures Section -->
            <div class="sig-container">
                <div class="sig-box">
                    <span class="label-bold">Pelapor :</span><br>
                    <div style="margin-top: 4px;">Nama : <u>{{ $deviation->initiator->name ?? 'Initiator' }}</u></div>
                    <div class="sig-space">
                        @if($deviation->initiator && $deviation->initiator->signature_path)
                            <img src="{{ asset('storage/' . $deviation->initiator->signature_path) }}" style="max-height: 40px; max-width: 150px; object-fit: contain;" />
                        @else
                            <div style="margin-top: 15px; border: 1px dashed #3b82f6; padding: 4px; background-color: #eff6ff; display: inline-block; border-radius: 4px; font-size: 7px; line-height: 1;">
                                <span style="color: #2563eb; font-weight: bold; display: block; margin-bottom: 2px;">✓ DIAJUKAN ELEKTRONIK</span>
                                <span style="font-family: monospace;">{{ $deviation->initiator->name ?? 'Initiator' }}</span>
                            </div>
                        @endif
                    </div>
                    <div>Tanggal : {{ $deviation->created_at->format('d/m/Y') }}</div>
                </div>

                <div class="sig-box">
                    <span class="label-bold">Diketahui oleh : (Manager terkait)</span><br>
                    <div style="margin-top: 4px;">Nama : <u>{{ $deviation->kepala_departemen ?? '........................' }}</u></div>
                    <div class="sig-space">
                        @if($deviation->status === 'APPROVED')
                            @if($huUser && $huUser->signature_path)
                                <img src="{{ asset('storage/' . $huUser->signature_path) }}" style="max-height: 40px; max-width: 150px; object-fit: contain;" />
                            @else
                                <div style="margin-top: 15px; border: 1px dashed #22c55e; padding: 4px; background-color: #f0fdf4; display: inline-block; border-radius: 4px; font-size: 7px; line-height: 1;">
                                    <span style="color: #16a34a; font-weight: bold; display: block; margin-bottom: 2px;">✓ DISETUJUI ELEKTRONIK</span>
                                    <span style="font-family: monospace;">{{ $deviation->kepala_departemen ?? 'Manager' }}</span>
                                </div>
                            @endif
                        @else
                            <div style="margin-top: 15px; font-style: italic; color: #9ca3af;">(Tanda Tangan)</div>
                        @endif
                    </div>
                    <div>Tanggal : {{ $deviation->status === 'APPROVED' ? $deviation->updated_at->format('d/m/Y') : '........................' }}</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="page-footer">
            <span class="master-copy-stamp">MASTER COPY</span>
            <span class="doc-code">LAMP. B 09/QA/003.01</span>
            <span class="master-stamp">MASTER</span>
            <span>Halaman 1 dari 2</span>
        </div>
    </div>

    <!-- ================= PAGE 2 ================= -->
    <div class="page">
        <div>
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
                        FORM DEVIATION REPORT
                    </td>
                    <td class="doc-no-section">
                        No. CM-09/QA/003B.01
                    </td>
                </tr>
            </table>

            <!-- C. Kajian Risiko -->
            <table class="form-table">
                <tr>
                    <td class="bg-light label-bold">C. Kajian Risiko (Mutu, Keamanan, dan Efektivitas Produk) <span class="label-gray">(diisi oleh QA)</span></td>
                </tr>
                <tr>
                    <td>
                        <div class="label-bold" style="margin-bottom: 8px;">I. Tingkat Risiko <span style="font-weight: normal; font-size: 9px; font-style: italic;">(No. Form penyelidikan : {{ $deviation->deviation_number }})</span></div>
                        @php
                            // Calculate max RPN to determine Risk Level
                            $maxRpn = 0;
                            if (!empty($deviation->risk_analysis)) {
                                foreach ($deviation->risk_analysis as $risk) {
                                    $rpnVal = intval($risk['rpn'] ?? 0);
                                    if ($rpnVal > $maxRpn) {
                                        $maxRpn = $rpnVal;
                                    }
                                }
                            }
                        @endphp
                        <div style="display: flex; flex-direction: column; gap: 6px; margin-left: 10px;">
                            <div>
                                <span class="chk-box">{{ ($deviation->status === 'APPROVED' && $maxRpn > 200) ? '✓' : '' }}</span> Kritikal (C)
                            </div>
                            <div>
                                <span class="chk-box">{{ ($deviation->status === 'APPROVED' && $maxRpn > 50 && $maxRpn <= 200) ? '✓' : '' }}</span> Mayor (M)
                            </div>
                            <div>
                                <span class="chk-box">{{ ($deviation->status === 'APPROVED' && $maxRpn <= 50) ? '✓' : '' }}</span> minor (m)
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- D. Evaluasi terhadap Laporan, Tindakan dan Risiko -->
            <table class="form-table" style="margin-top: 10px;">
                <tr>
                    <td class="bg-light label-bold">D. Evaluasi terhadap Laporan, Tindakan dan Risiko</td>
                </tr>
                <tr>
                    <td>
                        <div class="label-bold" style="margin-bottom: 6px;">I. Jenis Penyimpangan :</div>
                        <div style="display: flex; gap: 40px; margin-bottom: 12px; margin-left: 10px;">
                            <div><span class="chk-box"></span> Bets</div>
                            <div><span class="chk-box">✓</span> Non Bets</div>
                        </div>

                        <div class="label-bold" style="margin-bottom: 6px; border-top: 1px dashed #ccc; padding-top: 6px;">II. Tindakan yang diusulkan (dapat diusulkan lebih dari satu) :</div>
                        <table style="width: 100%; border: none;">
                            <tr style="height: 22px;">
                                <td style="border: none; width: 40%; padding: 2px;"><span class="chk-box"></span> Produk diolah ulang</td>
                                <td style="border: none; padding: 2px;">: (No. form olah ulang .....................................................)</td>
                            </tr>
                            <tr style="height: 22px;">
                                <td style="border: none; padding: 2px;"><span class="chk-box"></span> FUS Stability</td>
                                <td style="border: none; padding: 2px;">: &nbsp; <span class="chk-box"></span> Ya &nbsp; &nbsp; &nbsp; <span class="chk-box"></span> Tidak</td>
                            </tr>
                            <tr style="height: 22px;">
                                <td style="border: none; padding: 2px;"><span class="chk-box">✓</span> Lain-lain</td>
                                <td style="border: none; padding: 2px;">: (No. form CAPA : {{ $deviation->capa->capa_number ?? '-' }} )</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- E. Keputusan Akhir -->
            <table class="form-table" style="margin-top: 10px;">
                <tr>
                    <td colspan="2" class="bg-light label-bold">E. Keputusan Akhir</td>
                </tr>
                <tr style="font-weight: bold; text-align: center;">
                    <td style="width: 35%;">KEPUTUSAN</td>
                    <td>PERTIMBANGAN</td>
                </tr>
                <tr style="height: 60px;">
                    <td style="vertical-align: middle;">
                        <span class="chk-box">{{ $deviation->status === 'APPROVED' ? '✓' : '' }}</span> RELEASE
                    </td>
                    <td style="font-size: 9px; line-height: 1.4;">
                        @if($deviation->status === 'APPROVED')
                            Semua tindakan mitigasi risiko dan FMEA telah dinilai memadai. Laporan CAPA telah diterbitkan untuk mencegah terulangnya penyimpangan serupa.
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr style="height: 60px;">
                    <td style="vertical-align: middle;">
                        <span class="chk-box">{{ $deviation->status === 'REJECTED' ? '✓' : '' }}</span> REJECT
                    </td>
                    <td style="font-size: 9px; line-height: 1.4; color: #ef4444;">
                        @if($deviation->status === 'REJECTED')
                            Ditolak oleh QA: {{ $deviation->reject_reason }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </table>
            <div style="font-size: 8px; margin-top: 4px; font-style: italic;">(*) keputusan REJECT atas sepengetahuan General Manager</div>

            <!-- Approval Box -->
            <div class="sig-container" style="margin-top: 25px;">
                <div class="sig-box" style="width: 48%;">
                    <span class="label-bold">Disetujui Oleh :</span><br>
                    <div style="font-weight: bold; margin-top: 4px;">Operational Manager</div>
                    <div class="sig-space">
                        @if($deviation->status === 'APPROVED')
                            @if($omUser && $omUser->signature_path)
                                <img src="{{ asset('storage/' . $omUser->signature_path) }}" style="max-height: 40px; max-width: 150px; object-fit: contain;" />
                            @else
                                <div style="margin-top: 15px; border: 1px dashed #22c55e; padding: 4px; background-color: #f0fdf4; display: inline-block; border-radius: 4px; font-size: 7px; line-height: 1;">
                                    <span style="color: #16a34a; font-weight: bold; display: block; margin-bottom: 2px;">✓ DISETUJUI ELEKTRONIK</span>
                                    <span style="font-family: monospace;">{{ $omUser->name ?? 'Operational Manager' }}</span>
                                </div>
                            @endif
                        @else
                            <div style="margin-top: 15px; font-style: italic; color: #9ca3af;">(Tanda Tangan)</div>
                        @endif
                    </div>
                    <div>Tanggal : {{ $deviation->status === 'APPROVED' ? $deviation->updated_at->format('d/m/Y') : '........................' }}</div>
                </div>

                <div class="sig-box" style="width: 48%;">
                    <span class="label-bold">Mengetahui :</span><br>
                    <div style="font-weight: bold; margin-top: 4px;">General Manager (*)</div>
                    <div class="sig-space">
                        @if($deviation->status === 'APPROVED')
                            @if($gmUser && $gmUser->signature_path)
                                <img src="{{ asset('storage/' . $gmUser->signature_path) }}" style="max-height: 40px; max-width: 150px; object-fit: contain;" />
                            @else
                                <div style="margin-top: 15px; border: 1px dashed #22c55e; padding: 4px; background-color: #f0fdf4; display: inline-block; border-radius: 4px; font-size: 7px; line-height: 1;">
                                    <span style="color: #16a34a; font-weight: bold; display: block; margin-bottom: 2px;">✓ DISETUJUI ELEKTRONIK</span>
                                    <span style="font-family: monospace;">{{ $gmUser->name ?? 'General Manager' }}</span>
                                </div>
                            @endif
                        @else
                            <div style="margin-top: 15px; font-style: italic; color: #9ca3af;">(Tanda Tangan)</div>
                        @endif
                    </div>
                    <div>Tanggal : {{ $deviation->status === 'APPROVED' ? $deviation->updated_at->format('d/m/Y') : '........................' }}</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="page-footer">
            <span class="master-copy-stamp">MASTER COPY</span>
            <span class="doc-code">LAMP. B 09/QA/003.01</span>
            <span class="master-stamp">MASTER</span>
            <span>Halaman 2 dari 2</span>
        </div>
    </div>

</body>
</html>
