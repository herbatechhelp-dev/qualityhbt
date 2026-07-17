<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AttachmentViewer from '@/Components/AttachmentViewer.vue';

const showPrintPreview = ref(false);
const previewUrl = ref('');
const previewTitle = ref('');

const openPrintPreview = (url, title) => {
    previewUrl.value = url;
    previewTitle.value = title;
    showPrintPreview.value = true;
};

const printIframe = () => {
    const iframe = document.getElementById('print-iframe');
    if (iframe) {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }
};

watch(
    () => showPrintPreview.value,
    (newVal) => {
        if (newVal) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

const page = usePage();
const currentUser = page.props.auth.user;

const canEvaluate = computed(() => currentUser.role === 'qa' || currentUser.role === 'superadmin');
const isOwner = computed(() => currentUser.id === props.deviation.initiator_id);

const props = defineProps({
    deviation: { type: Object, required: true },
    users:     { type: Array, default: () => [] },
});

// ─── Attachment viewer ────────────────────────────────────────────────────────
const showAttachment      = ref(false);
const activeAttachment    = ref({ path: '', title: '' });
const openAttachment = (path, title) => {
    activeAttachment.value = { path, title };
    showAttachment.value   = true;
};

// ─── SOD Guide Modal ─────────────────────────────────────────────────────────
const showSodGuide = ref(false);
const sodGuide = {
    severity:   [
        { level: 'Rendah', nilai: '1', kondisi: 'Tidak ada dampak pada produk/proses. Temuan minor.' },
        { level: 'Sedang', nilai: '3', kondisi: 'Dampak terbatas, dapat dimitigasi tanpa mempengaruhi kualitas produk.' },
        { level: 'Tinggi', nilai: '9', kondisi: 'Dampak serius terhadap keamanan, kualitas, atau kepatuhan regulasi.' },
    ],
    occurrence: [
        { level: 'Rendah', nilai: '1', kondisi: 'Kurang dari 1% produk dapat mengalami ketidaksesuaian. Jarang terjadi.' },
        { level: 'Sedang', nilai: '3', kondisi: '1%–5% produk; 2–5 kali dalam 1 tahun periode kejadian.' },
        { level: 'Tinggi', nilai: '9', kondisi: 'Lebih dari 5% kejadian; berulang lebih dari 10 kali. Hampir pasti terjadi.' },
    ],
    detection:  [
        { level: 'Rendah', nilai: '1', kondisi: 'Potensi risiko tidak dapat dideteksi.' },
        { level: 'Sedang', nilai: '3', kondisi: 'Potensi risiko dapat dideteksi namun tidak selalu oleh prosedur yang ada.' },
        { level: 'Tinggi', nilai: '9', kondisi: 'Potensi dapat berpotensi dideteksi sebelum produk dikirim.' },
    ],
};
const getRpnClass = (rpn) => {
    if (rpn <= 3)  return 'rpn-low';
    if (rpn <= 80) return 'rpn-medium';
    return 'rpn-high';
};

const getRpnLabel = (rpn) => {
    if (rpn <= 3)  return 'Minor';
    if (rpn <= 80) return 'Mayor';
    return 'Kritikal';
};

// ─── QA Decision form ────────────────────────────────────────────────────────
const decisionForm = useForm({
    action:        'APPROVED',
    reject_reason: '',
});

const submitDecision = () => {
    if (decisionForm.action === 'REJECTED' && !decisionForm.reject_reason.trim()) {
        window.dispatchEvent(new CustomEvent('qms-notification', {
            detail: { type: 'error', title: 'Validasi Gagal', message: 'Alasan Reject wajib diisi.' }
        }));
        return;
    }
    const msg = decisionForm.action === 'APPROVED'
        ? 'Menyetujui deviasi ini akan otomatis membuat lembar CAPA. Lanjutkan?'
        : 'Anda yakin ingin menolak deviasi ini?';
    if (confirm(msg)) {
        decisionForm.post(route('deviations.decide', props.deviation.id));
    }
};

// ─── FMEA Inline Editing Form ───────────────────────────────────────────────
const isEditingFmea = ref(false);
const fmeaForm = useForm({
    risk_analysis: JSON.parse(JSON.stringify(props.deviation.risk_analysis || [])),
    pic: props.deviation.pic || '',
    department: props.deviation.department || '',
    evaluasi_tindakan: {
        jenis_penyimpangan: props.deviation.evaluasi_tindakan?.jenis_penyimpangan || 'Non Bets',
        tindakan_diusulkan: props.deviation.evaluasi_tindakan?.tindakan_diusulkan || [],
        no_form_olah_ulang: props.deviation.evaluasi_tindakan?.no_form_olah_ulang || '',
        fus_stability_choice: props.deviation.evaluasi_tindakan?.fus_stability_choice || '',
    }
});

const startEditingFmea = () => {
    fmeaForm.risk_analysis = JSON.parse(JSON.stringify(props.deviation.risk_analysis || []));
    fmeaForm.pic = props.deviation.pic || '';
    fmeaForm.department = props.deviation.department || '';
    fmeaForm.evaluasi_tindakan = {
        jenis_penyimpangan: props.deviation.evaluasi_tindakan?.jenis_penyimpangan || 'Non Bets',
        tindakan_diusulkan: props.deviation.evaluasi_tindakan?.tindakan_diusulkan || [],
        no_form_olah_ulang: props.deviation.evaluasi_tindakan?.no_form_olah_ulang || '',
        fus_stability_choice: props.deviation.evaluasi_tindakan?.fus_stability_choice || '',
    };
    isEditingFmea.value = true;
};

const cancelEditingFmea = () => {
    isEditingFmea.value = false;
};

const addRiskRow = () => {
    fmeaForm.risk_analysis.push({
        risk_identification: '',
        potensiasi_cause:    '',
        s: 1,
        o: 1,
        d: 1,
        rpn: 1,
        risk_control: '',
        action: '',
        s_after: 1,
        o_after: 1,
        d_after: 1,
        rpn_after: 1,
    });
};

const removeRiskRow = (idx) => {
    fmeaForm.risk_analysis.splice(idx, 1);
};

const updateRpn = (row) => {
    row.rpn = (parseInt(row.s) || 1) * (parseInt(row.o) || 1) * (parseInt(row.d) || 1);
    row.rpn_after = (parseInt(row.s_after) || 1) * (parseInt(row.o_after) || 1) * (parseInt(row.d_after) || 1);
};

const saveFmea = () => {
    fmeaForm.post(route('deviations.update-fmea', props.deviation.id), {
        onSuccess: () => {
            isEditingFmea.value = false;
            window.dispatchEvent(new CustomEvent('qms-notification', {
                detail: { type: 'success', title: 'Berhasil', message: 'Analisis Risiko FMEA berhasil diperbarui!' }
            }));
        },
        onError: (errors) => {
            window.dispatchEvent(new CustomEvent('qms-notification', {
                detail: { type: 'error', title: 'Gagal Menyimpan', message: 'Terjadi kesalahan saat menyimpan FMEA.' }
            }));
        }
    });
};

// ─── Status badge ─────────────────────────────────────────────────────────────
const getStatusClass = (status) => {
    switch (status) {
        case 'DRAFT':    return 'badge-draft';
        case 'OPEN':     return 'badge-open';
        case 'IN REVIEW': return 'badge-in_review';
        case 'APPROVED': return 'badge-approved';
        case 'REJECTED': return 'badge-reject';
        default:         return 'badge-draft';
    }
};
</script>

<template>
    <Head :title="'Detail Deviasi: ' + deviation.deviation_number" />

    <AuthenticatedLayout>
        <template #header>
            🔍 Detail Deviation Report: {{ deviation.deviation_number }}
        </template>

        <div style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">

            <!-- Top nav -->
            <div class="flex-between">
                <Link :href="route('deviations.index')" class="btn btn-secondary">← Kembali ke Logbook</Link>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="status-badge" :class="getStatusClass(deviation.status)" style="font-size:0.9rem;padding:6px 16px;">
                        {{ deviation.status }}
                    </span>
                    <!-- Edit button for DRAFT/REJECTED -->
                    <template v-if="(deviation.status === 'DRAFT' || deviation.status === 'REJECTED') && isOwner">
                        <Link :href="route('deviations.edit', deviation.id)" class="btn btn-primary" style="font-size:0.85rem;">
                            ✏️ Edit Deviasi
                        </Link>
                    </template>
                    <!-- Print buttons (for non-DRAFT) -->
                    <template v-if="deviation.status !== 'DRAFT'">
                        <Link 
                            v-if="isOwner || currentUser.role === 'qa' || currentUser.role === 'superadmin'"
                            :href="route('deviations.investigations.edit', deviation.id)" 
                            class="btn btn-primary" 
                            style="font-size:0.85rem; display:inline-flex; align-items:center; gap:6px; background-color: #6366f1; border-color: #6366f1; color: white;"
                        >
                            ✍️ Input Penyelidikan
                        </Link>
                        <button type="button" @click="openPrintPreview(route('deviations.print-dr', deviation.id), 'Form Deviation Report (DR)')" class="btn btn-secondary" style="font-size:0.85rem; display:inline-flex; align-items:center; gap:6px; background-color: #10b981; border-color: #10b981; color: white;">
                            🖨️ Cetak DR
                        </button>
                        <button type="button" @click="openPrintPreview(route('deviations.print-investigation', deviation.id), 'Form Penyelidikan Ketidaksesuaian')" class="btn btn-secondary" style="font-size:0.85rem; display:inline-flex; align-items:center; gap:6px; background-color: #3b82f6; border-color: #3b82f6; color: white;">
                            🔍 Cetak Penyelidikan
                        </button>
                    </template>
                </div>
            </div>

            <!-- SOD Guide Modal -->
            <Teleport to="body">
                <Transition name="fade">
                    <div v-if="showSodGuide" @click.self="showSodGuide = false"
                        style="position:fixed;inset:0;background:rgba(15,23,42,0.75);display:flex;align-items:center;justify-content:center;z-index:10000;backdrop-filter:blur(8px);">
                        <div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:16px;width:92%;max-width:800px;max-height:88vh;overflow:auto;padding:28px;box-shadow:var(--hover-shadow);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                                <h3 style="font-size:1.15rem;font-weight:800;color:var(--text-primary);margin:0;">📊 Panduan Penilaian SOD</h3>
                                <button type="button" @click="showSodGuide = false" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-muted);">&times;</button>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:20px;">
                                <div v-for="(rows, key) in sodGuide" :key="key">
                                    <h4 style="font-weight:700;color:var(--accent-color);margin-bottom:10px;">
                                        {{ key === 'severity' ? '5.4.1 Severity (Keparahan)' : key === 'occurrence' ? '5.4.2 Occurrence (Kejadian)' : '5.4.3 Detection (Deteksi)' }}
                                    </h4>
                                    <table class="qms-table" style="width:100%;">
                                        <thead><tr><th style="width:90px;">Tingkat</th><th style="width:80px;">Nilai</th><th>Kondisi</th></tr></thead>
                                        <tbody>
                                            <tr v-for="row in rows" :key="row.level">
                                                <td style="font-weight:600;">{{ row.level }}</td>
                                                <td><span class="status-badge" :class="row.nilai.startsWith('7') ? 'badge-reject' : row.nilai.startsWith('4') ? 'badge-in_review' : 'badge-approved'">{{ row.nilai }}</span></td>
                                                <td style="font-size:0.85rem;line-height:1.5;">{{ row.kondisi }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div style="margin-top:20px;padding:12px 16px;background:var(--bg-primary);border-radius:8px;border:1px solid var(--border-color);font-size:0.85rem;color:var(--text-muted);">
                                <strong>RPN = Severity × Occurrence × Detection</strong><br>
                                ≤ 3: Minor (hijau) &nbsp;|&nbsp; 4–80: Mayor (kuning) &nbsp;|&nbsp; &gt;80: Kritikal (merah)
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <!-- Rejection Banner -->
            <div v-if="deviation.status === 'REJECTED'"
                style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:16px 20px;display:flex;align-items:start;gap:12px;">
                <span style="font-size:1.4rem;">❌</span>
                <div>
                    <div style="font-weight:600;color:#ef4444;margin-bottom:4px;">Laporan Deviasi Ditolak oleh QA</div>
                    <div style="font-size:0.875rem;color:var(--text-primary);margin-bottom:8px;">Silakan tinjau alasan di bawah, lakukan revisi, lalu kirim kembali.</div>
                    <div style="font-size:0.8rem;background:var(--bg-secondary);border-radius:6px;padding:8px 12px;border-left:3px solid #ef4444;color:var(--text-muted);font-style:italic;">
                        Catatan QA: "{{ deviation.reject_reason || '-' }}"
                    </div>
                </div>
            </div>

            <!-- CAPA Auto-generated banner -->
            <div v-if="deviation.status === 'APPROVED' && deviation.capa"
                style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.3);border-radius:12px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div>
                    <div style="font-weight:700;color:#10b981;margin-bottom:4px;">✅ Lembar CAPA Terkait Telah Terbit</div>
                    <div style="font-size:0.85rem;color:var(--text-secondary);">Sistem otomatis membuka lembar CAPA setelah deviasi ini disetujui.</div>
                </div>
                <Link :href="route('capas.show', deviation.capa.id)" class="btn btn-success" style="white-space:nowrap;padding:8px 16px;font-size:0.85rem;">
                    👁️ Lihat Lembar CAPA
                </Link>
            </div>

            <div class="qms-show-grid">
                <!-- ═══ LEFT COLUMN ═══════════════════════════════════════════ -->
                <div style="display:flex;flex-direction:column;gap:20px;">

                    <!-- SECTION A: Identifikasi & Detail -->
                    <div class="qms-card">
                        <h3 style="font-size:1.05rem;font-weight:800;color:var(--accent-color);border-bottom:1px solid var(--border-color);padding-bottom:10px;margin-bottom:16px;">
                            A. Identifikasi &amp; Detail Penyimpangan
                        </h3>

                        <div class="grid-2" style="margin-bottom:16px;">
                            <div>
                                <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Inisiator (Pelapor)</span>
                                <span style="font-weight:600;color:var(--text-primary);">{{ deviation.initiator?.name ?? '-' }}</span>
                            </div>
                            <div>
                                <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">No. Bets / Alat / Dokumen / Identitas lainnya</span>
                                <span style="font-weight:600;color:var(--text-primary);">{{ deviation.department }}</span>
                            </div>
                        </div>

                        <div class="grid-2" style="margin-bottom:16px;">
                            <div>
                                <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Nama Produk / Proses / RM / PM / Sistem / Alat</span>
                                <span style="font-weight:600;color:var(--text-primary);">{{ deviation.pic || '-' }}</span>
                            </div>
                            <div>
                                <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Tanggal Temuan</span>
                                <span style="font-weight:600;color:var(--text-primary);">
                                    {{ deviation.tanggal_temuan ? new Date(deviation.tanggal_temuan).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'}) : '-' }}
                                </span>
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:6px;">II. Apakah ada bets / Produk lain yang terkena imbasnya?</span>
                            <div style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:8px;padding:12px 16px;font-size:0.9rem;">
                                <div style="font-weight:600;color:var(--text-primary);margin-bottom:4px;">
                                    {{ deviation.is_other_batch_affected ? '✅ Ya' : '❌ Tidak' }}
                                </div>
                                <div v-if="deviation.is_other_batch_affected" style="color:var(--text-muted);font-size:0.85rem;border-top:1px dashed var(--border-color);padding-top:4px;margin-top:4px;">
                                    Detail: {{ deviation.other_batch_affected_details }}
                                </div>
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:6px;">III. Uraian Detail (Uraian Penyimpangan)</span>
                            <div style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:8px;padding:16px;line-height:1.7;color:var(--text-primary);white-space:pre-wrap;font-size:0.9rem;">{{ deviation.description }}</div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:6px;">IV. Frekuensi Penyimpangan</span>
                            <div style="font-weight:600;color:var(--text-primary);font-size:0.9rem;">
                                📊 {{ deviation.deviation_frequency || 'Tidak Pernah sebelumnya' }}
                            </div>
                        </div>

                        <div style="margin-top:8px;font-size:0.78rem;color:var(--text-muted);text-align:right;">
                            Dilaporkan: {{ new Date(deviation.created_at).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'}) }}
                        </div>
                    </div>

                    <!-- B. Rincian Tindakan Sementara (Immediate Action) -->
                    <div class="qms-card">
                        <h3 style="font-size:1.05rem;font-weight:800;color:var(--accent-color);border-bottom:1px solid var(--border-color);padding-bottom:10px;margin-bottom:16px;">
                            B. Rincian Tindakan Sementara yang Diambil (Immediate Action)
                        </h3>

                        <div style="margin-bottom:16px;">
                            <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">I. Penghentian Proses Produksi</span>
                            <span style="font-weight:600;color:var(--text-primary);">
                                {{ deviation.is_production_stopped ? '⚠️ Ya (Dihentikan)' : '✅ Tidak (Tetap Berjalan)' }}
                            </span>
                        </div>

                        <div>
                            <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:6px;">II. Penanganan Cepat Lain Terhadap Produk</span>
                            <div style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:8px;padding:12px 16px;line-height:1.6;color:var(--text-primary);white-space:pre-wrap;font-size:0.9rem;">{{ deviation.immediate_action_details || '—' }}</div>
                        </div>
                    </div>

                    <!-- SECTION B: Klasifikasi -->
                    <div class="qms-card">
                        <h3 style="font-size:1.05rem;font-weight:800;color:var(--accent-color);border-bottom:1px solid var(--border-color);padding-bottom:10px;margin-bottom:16px;">
                            B. Identifikasi &amp; Klasifikasi Penyimpangan
                        </h3>

                        <div class="grid-2" style="margin-bottom:16px;">
                            <div>
                                <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:8px;">Jenis Penyimpangan</span>
                                <div v-if="deviation.jenis_penyimpangan && deviation.jenis_penyimpangan.length" style="display:flex;flex-wrap:wrap;gap:6px;">
                                    <span v-for="item in deviation.jenis_penyimpangan" :key="item"
                                        style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:20px;padding:4px 12px;font-size:0.78rem;color:var(--text-primary);">
                                        {{ item }}
                                    </span>
                                </div>
                                <span v-else style="color:var(--text-muted);font-size:0.85rem;">—</span>
                            </div>
                            <div>
                                <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:8px;">Cara Identifikasi</span>
                                <div v-if="deviation.identifikasi_penyimpangan && deviation.identifikasi_penyimpangan.length" style="display:flex;flex-wrap:wrap;gap:6px;">
                                    <span v-for="item in deviation.identifikasi_penyimpangan" :key="item"
                                        style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:20px;padding:4px 12px;font-size:0.78rem;color:var(--text-primary);">
                                        {{ item }}
                                    </span>
                                </div>
                                <span v-else style="color:var(--text-muted);font-size:0.85rem;">—</span>
                            </div>
                        </div>

                        <div>
                            <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Kepala Departemen (TTD)</span>
                            <span style="font-weight:600;color:var(--text-primary);">{{ deviation.kepala_departemen || '—' }}</span>
                        </div>
                    </div>

                    <!-- SECTION C: Lampiran -->
                    <div class="qms-card">
                        <h3 style="font-size:1.05rem;font-weight:800;color:var(--accent-color);border-bottom:1px solid var(--border-color);padding-bottom:10px;margin-bottom:16px;">
                            C. File Lampiran
                        </h3>

                        <!-- Multi-file attachments (new) -->
                        <div v-if="deviation.attachments && deviation.attachments.length" style="display:flex;flex-direction:column;gap:10px;">
                            <div v-for="(att, idx) in deviation.attachments" :key="idx"
                                style="display:flex;align-items:center;justify-content:space-between;background:var(--bg-primary);border:1px solid var(--border-color);border-radius:8px;padding:12px 16px;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <span style="font-size:1.2rem;">📄</span>
                                    <div>
                                        <div style="font-size:0.875rem;font-weight:600;color:var(--text-primary);">
                                            {{ att.description || 'Lampiran ' + (idx + 1) }}
                                        </div>
                                        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">{{ att.path }}</div>
                                    </div>
                                </div>
                                <button type="button" @click="openAttachment(att.path, att.description || 'Lampiran ' + (idx + 1))"
                                    class="btn btn-primary" style="padding:5px 12px;font-size:0.78rem;font-weight:600;flex-shrink:0;">
                                    👁️ Lihat
                                </button>
                            </div>
                        </div>

                        <!-- Legacy single attachment -->
                        <div v-else-if="deviation.attachment_path"
                            style="display:flex;align-items:center;justify-content:space-between;background:var(--bg-primary);border:1px solid var(--border-color);border-radius:8px;padding:12px 16px;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <span style="font-size:1.2rem;">📄</span>
                                <div>
                                    <div style="font-size:0.875rem;font-weight:600;">{{ deviation.attachment_description || 'Berkas Bukti' }}</div>
                                    <div style="font-size:0.72rem;color:var(--text-muted);">{{ deviation.attachment_path }}</div>
                                </div>
                            </div>
                            <button type="button" @click="openAttachment(deviation.attachment_path, deviation.attachment_description || 'Lampiran')"
                                class="btn btn-primary" style="padding:5px 12px;font-size:0.78rem;font-weight:600;">
                                👁️ Lihat
                            </button>
                        </div>

                        <div v-else style="color:var(--text-muted);font-size:0.875rem;padding:12px 0;">📂 Tidak ada lampiran yang diunggah.</div>
                    </div>

                    <!-- SECTION D: Risk Analysis -->
                    <div class="qms-card">
                        <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border-color);padding-bottom:10px;margin-bottom:16px;">
                            <h3 style="font-size:1.05rem;font-weight:800;color:var(--accent-color);margin:0;">
                                D. Risk Analysis (FMEA)
                            </h3>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <button type="button" @click="showSodGuide = true" class="btn btn-secondary"
                                    style="padding:4px 12px;font-size:0.78rem;font-weight:600;">
                                    ℹ️ Panduan SOD
                                </button>
                                <!-- Edit FMEA Button for QA when deviation status is OPEN -->
                                <template v-if="canEvaluate && deviation.status === 'OPEN'">
                                    <button v-if="!isEditingFmea" type="button" @click="startEditingFmea" class="btn btn-primary"
                                        style="padding:4px 12px;font-size:0.78rem;font-weight:600;background-color:#6366f1;border-color:#6366f1;">
                                        ✏️ Edit FMEA
                                    </button>
                                    <button v-if="isEditingFmea" type="button" @click="addRiskRow" class="btn btn-secondary"
                                        style="padding:4px 12px;font-size:0.78rem;font-weight:600;">
                                        + Tambah Baris
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- EDITING FMEA MODE -->
                        <div v-if="isEditingFmea" style="display:flex;flex-direction:column;gap:12px;">
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:10px;">
                                Mengedit analisis risiko FMEA untuk laporan ini. Klik "💾 Simpan FMEA" untuk memperbarui.
                            </div>

                            <!-- Metadata inputs for QA -->
                            <div class="grid-2" style="gap:16px; margin-bottom:16px; background:var(--bg-secondary); padding:16px; border-radius:10px; border:1px solid var(--border-color);">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label">Nama Produk / Proses / RM / PM / Sistem / Alat <span style="color:#ef4444;">*</span></label>
                                    <input type="text" v-model="fmeaForm.pic" class="form-input" placeholder="Nama produk, proses, mesin, atau alat..." required />
                                    <div v-if="fmeaForm.errors.pic" style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ fmeaForm.errors.pic }}</div>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label">No. Bets / Alat / Dokumen / Identitas lainnya <span style="color:#ef4444;">*</span></label>
                                    <input type="text" v-model="fmeaForm.department" class="form-input" placeholder="Nomor bets, kode alat, atau identitas dokumen..." required />
                                    <div v-if="fmeaForm.errors.department" style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ fmeaForm.errors.department }}</div>
                                </div>
                            </div>

                            <!-- Section D: Evaluasi Tindakan (diisi oleh QA) -->
                            <div style="margin-bottom: 16px; background:var(--bg-secondary); padding:16px; border-radius:10px; border:1px solid var(--border-color); display:flex; flex-direction:column; gap:16px;">
                                <h4 style="margin:0; font-size:0.95rem; font-weight:700; color:var(--text-primary); border-bottom:1px dashed var(--border-color); padding-bottom:8px;">
                                    D. Evaluasi terhadap Laporan, Tindakan dan Risiko (diisi oleh QA)
                                </h4>

                                <!-- I. Jenis Penyimpangan -->
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label" style="font-weight:700;">I. Jenis Penyimpangan</label>
                                    <div style="display: flex; gap: 20px; align-items: center; margin-top: 6px;">
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; color: var(--text-primary);">
                                            <input type="radio" value="Bets" v-model="fmeaForm.evaluasi_tindakan.jenis_penyimpangan" style="width: 16px; height: 16px; accent-color: var(--accent-color);" />
                                            Bets
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; color: var(--text-primary);">
                                            <input type="radio" value="Non Bets" v-model="fmeaForm.evaluasi_tindakan.jenis_penyimpangan" style="width: 16px; height: 16px; accent-color: var(--accent-color);" />
                                            Non Bets
                                        </label>
                                    </div>
                                </div>

                                <!-- II. Tindakan yang Diusulkan -->
                                <div class="form-group" style="margin-bottom:0; display:flex; flex-direction:column; gap:12px;">
                                    <label class="form-label" style="font-weight:700;">II. Tindakan yang diusulkan (dapat diusulkan lebih dari satu)</label>
                                    
                                    <!-- A. Produk diolah ulang -->
                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; color: var(--text-primary);">
                                            <input type="checkbox" value="olah_ulang" v-model="fmeaForm.evaluasi_tindakan.tindakan_diusulkan" style="width: 16px; height: 16px; accent-color: var(--accent-color);" />
                                            Produk diolah ulang
                                        </label>
                                        <div v-if="fmeaForm.evaluasi_tindakan.tindakan_diusulkan.includes('olah_ulang')" style="margin-left:24px;">
                                            <input type="text" v-model="fmeaForm.evaluasi_tindakan.no_form_olah_ulang" class="form-input" placeholder="No. form olah ulang..." style="font-size:0.85rem; padding:6px 12px;" />
                                        </div>
                                    </div>

                                    <!-- B. FUS Stability -->
                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; color: var(--text-primary);">
                                            <input type="checkbox" value="fus_stability" v-model="fmeaForm.evaluasi_tindakan.tindakan_diusulkan" style="width: 16px; height: 16px; accent-color: var(--accent-color);" />
                                            FUS Stability
                                        </label>
                                        <div v-if="fmeaForm.evaluasi_tindakan.tindakan_diusulkan.includes('fus_stability')" style="margin-left:24px; display:flex; gap:16px; align-items:center;">
                                            <span style="font-size:0.85rem; color:var(--text-muted);">Pilihan:</span>
                                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.85rem; color: var(--text-primary);">
                                                <input type="radio" value="Ya" v-model="fmeaForm.evaluasi_tindakan.fus_stability_choice" style="width: 14px; height: 14px; accent-color: var(--accent-color);" />
                                                Ya
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.85rem; color: var(--text-primary);">
                                                <input type="radio" value="Tidak" v-model="fmeaForm.evaluasi_tindakan.fus_stability_choice" style="width: 14px; height: 14px; accent-color: var(--accent-color);" />
                                                Tidak
                                            </label>
                                        </div>
                                    </div>

                                    <!-- C. Lain-lain -->
                                    <div>
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; color: var(--text-primary);">
                                            <input type="checkbox" value="lain_lain" v-model="fmeaForm.evaluasi_tindakan.tindakan_diusulkan" style="width: 16px; height: 16px; accent-color: var(--accent-color);" />
                                            Lain-lain (No. form CAPA otomatis disematkan jika disetujui)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty state in Edit Mode -->
                            <div v-if="fmeaForm.risk_analysis.length === 0"
                                style="text-align:center;color:var(--text-muted);font-size:0.85rem;padding:24px;background:var(--bg-primary);border:1px dashed var(--border-color);border-radius:8px;">
                                📊 Belum ada baris analisis risiko. Klik "+ Tambah Baris" untuk menambahkan.
                            </div>

                            <!-- Edit Rows -->
                            <div v-for="(row, idx) in fmeaForm.risk_analysis" :key="idx"
                                style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:10px;padding:16px;margin-bottom:12px;position:relative;">
                                <div style="position:absolute;top:12px;right:12px;">
                                    <button type="button" @click="removeRiskRow(idx)" class="btn btn-danger"
                                        style="padding:4px 10px;font-size:0.75rem;">🗑️ Hapus</button>
                                </div>
                                <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:12px;">
                                    Risiko #{{ idx + 1 }}
                                    <span style="margin-left:12px;font-size:0.8rem;" :class="getRpnClass(row.rpn)">
                                        RPN = {{ row.rpn }}
                                    </span>
                                </div>

                                <div class="grid-2" style="gap:12px;margin-bottom:12px;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Failure Mode</label>
                                        <input type="text" v-model="row.risk_identification" class="form-input"
                                            placeholder="Mode kegagalan (Failure Mode)..." />
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Failure Effect</label>
                                        <input type="text" v-model="row.potensiasi_cause" class="form-input"
                                            placeholder="Efek kegagalan (Failure Effect)..." />
                                    </div>
                                </div>

                                <!-- SOD values -->
                                <div style="display:flex;gap:10px;margin-bottom:12px;align-items:flex-end;flex-wrap:wrap;">
                                    <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                        <label class="form-label">Severity (S)</label>
                                        <select v-model.number="row.s" class="form-select" @change="updateRpn(row)">
                                            <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                        <label class="form-label">Occurrence (O)</label>
                                        <select v-model.number="row.o" class="form-select" @change="updateRpn(row)">
                                            <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                        <label class="form-label">Detection (D)</label>
                                        <select v-model.number="row.d" class="form-select" @change="updateRpn(row)">
                                            <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </div>
                                    <div style="min-width:100px;flex:1;text-align:center;padding-bottom:2px;">
                                        <div style="font-size:0.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:4px;">RPN (S×O×D)</div>
                                        <div style="font-size:1.6rem;font-weight:900;line-height:1;" :class="getRpnClass(row.rpn)">
                                            {{ row.rpn }}
                                        </div>
                                    </div>
                                </div>

                                <div class="grid-2" style="gap:12px; margin-bottom:12px;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Corrective (Tindakan Korektif)</label>
                                        <input type="text" v-model="row.risk_control" class="form-input"
                                            placeholder="Tindakan perbaikan segera..." />
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Preventive (Tindakan Pencegahan)</label>
                                        <input type="text" v-model="row.action" class="form-input"
                                            placeholder="Tindakan pencegahan agar tidak terulang..." />
                                    </div>
                                </div>

                                <!-- Expected / Residual Risk after corrective/preventive action -->
                                <div style="margin-top: 16px; border-top: 1px dashed var(--border-color); padding-top: 16px;">
                                    <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:12px;">
                                        Penilaian Risiko Setelah Tindakan (Residual Risk)
                                        <span style="margin-left:12px;font-size:0.8rem;" :class="getRpnClass(row.rpn_after || 1)">
                                            Expected RPN = {{ row.rpn_after || 1 }}
                                        </span>
                                    </div>
                                    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                                        <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                            <label class="form-label">Expected Severity (S)</label>
                                            <select v-model.number="row.s_after" class="form-select" @change="updateRpn(row)">
                                                <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                            <label class="form-label">Expected Occurrence (O)</label>
                                            <select v-model.number="row.o_after" class="form-select" @change="updateRpn(row)">
                                                <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                            <label class="form-label">Expected Detection (D)</label>
                                            <select v-model.number="row.d_after" class="form-select" @change="updateRpn(row)">
                                                <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                            </select>
                                        </div>
                                        <div style="min-width:100px;flex:1;text-align:center;padding-bottom:2px;">
                                            <div style="font-size:0.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:4px;">Expected RPN (S×O×D)</div>
                                            <div style="font-size:1.6rem;font-weight:900;line-height:1;" :class="getRpnClass(row.rpn_after || 1)">
                                                {{ row.rpn_after || 1 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Mode Buttons -->
                            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:16px;">
                                <button type="button" @click="cancelEditingFmea" class="btn btn-secondary"
                                    :disabled="fmeaForm.processing" style="padding:8px 20px;font-weight:600;">
                                    Cancel
                                </button>
                                <button type="button" @click="saveFmea" class="btn btn-primary"
                                    :disabled="fmeaForm.processing" style="padding:8px 24px;font-weight:700;background-color:#10b981;border-color:#10b981;">
                                    {{ fmeaForm.processing ? 'Saving...' : '💾 Simpan FMEA' }}
                                </button>
                            </div>
                        </div>

                        <!-- READ ONLY DISPLAY MODE -->
                        <div v-else>
                            <div v-if="deviation.risk_analysis && deviation.risk_analysis.length" style="display:flex;flex-direction:column;gap:12px;">
                                <div v-for="(row, idx) in deviation.risk_analysis" :key="idx"
                                style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:10px;padding:16px;">
                                <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;">
                                    <span>Risiko #{{ idx + 1 }}</span>
                                    <span style="font-size:1rem;font-weight:900;" :class="getRpnClass(parseInt(row.rpn))">
                                        RPN = {{ row.rpn }}
                                        <span style="font-size:0.7rem;font-weight:600;margin-left:4px;">
                                            ({{ getRpnLabel(parseInt(row.rpn)) }})
                                        </span>
                                    </span>
                                </div>

                                <div class="grid-2" style="gap:12px;margin-bottom:12px;">
                                    <div>
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Failure Mode</span>
                                        <span style="font-size:0.875rem;color:var(--text-primary);">{{ row.risk_identification || '—' }}</span>
                                    </div>
                                    <div>
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Failure Effect</span>
                                        <span style="font-size:0.875rem;color:var(--text-primary);">{{ row.potensiasi_cause || '—' }}</span>
                                    </div>
                                </div>

                                <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
                                    <div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:8px 16px;text-align:center;min-width:70px;">
                                        <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;">Severity</div>
                                        <div style="font-size:1.4rem;font-weight:900;color:var(--text-primary);">{{ row.s }}</div>
                                    </div>
                                    <div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:8px 16px;text-align:center;min-width:70px;">
                                        <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;">Occurrence</div>
                                        <div style="font-size:1.4rem;font-weight:900;color:var(--text-primary);">{{ row.o }}</div>
                                    </div>
                                    <div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:8px 16px;text-align:center;min-width:70px;">
                                        <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;">Detection</div>
                                        <div style="font-size:1.4rem;font-weight:900;color:var(--text-primary);">{{ row.d }}</div>
                                    </div>
                                    <div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:8px 16px;text-align:center;min-width:70px;" :class="getRpnClass(parseInt(row.rpn))">
                                        <div style="font-size:0.7rem;text-transform:uppercase;opacity:0.8;">RPN</div>
                                        <div style="font-size:1.4rem;font-weight:900;">{{ row.rpn }}</div>
                                    </div>
                                </div>

                                <div class="grid-2" style="gap:12px;">
                                    <div>
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Corrective (Tindakan Korektif)</span>
                                        <span style="font-size:0.875rem;color:var(--text-primary);">{{ row.risk_control || '—' }}</span>
                                    </div>
                                    <div>
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Preventive (Tindakan Pencegahan)</span>
                                        <span style="font-size:0.875rem;color:var(--text-primary);">{{ row.action || '—' }}</span>
                                    </div>
                                </div>

                                <!-- Expected / Residual Risk after corrective/preventive action -->
                                <div style="margin-top:14px;border-top:1px dashed var(--border-color);padding-top:14px;">
                                    <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between;">
                                        <span>Setelah Tindakan (Residual Risk)</span>
                                        <span style="font-size:0.9rem;font-weight:900;" :class="getRpnClass(parseInt(row.rpn_after || 1))">
                                            Expected RPN = {{ row.rpn_after || 1 }}
                                            <span style="font-size:0.7rem;font-weight:600;margin-left:4px;">
                                                ({{ getRpnLabel(parseInt(row.rpn_after || 1)) }})
                                            </span>
                                        </span>
                                    </div>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:6px 12px;text-align:center;min-width:70px;">
                                            <div style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;">Severity</div>
                                            <div style="font-size:1.15rem;font-weight:900;color:var(--text-primary);">{{ row.s_after || 1 }}</div>
                                        </div>
                                        <div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:6px 12px;text-align:center;min-width:70px;">
                                            <div style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;">Occurrence</div>
                                            <div style="font-size:1.15rem;font-weight:900;color:var(--text-primary);">{{ row.o_after || 1 }}</div>
                                        </div>
                                        <div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:6px 12px;text-align:center;min-width:70px;">
                                            <div style="font-size:0.65rem;color:var(--text-muted);text-transform:uppercase;">Detection</div>
                                            <div style="font-size:1.15rem;font-weight:900;color:var(--text-primary);">{{ row.d_after || 1 }}</div>
                                        </div>
                                        <div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:6px 12px;text-align:center;min-width:70px;" :class="getRpnClass(parseInt(row.rpn_after || 1))">
                                            <div style="font-size:0.65rem;text-transform:uppercase;opacity:0.8;">RPN</div>
                                            <div style="font-size:1.15rem;font-weight:900;">{{ row.rpn_after || 1 }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else style="color:var(--text-muted);font-size:0.875rem;padding:12px 0;">📊 Tidak ada data analisis risiko.</div>

                            <!-- Section D Read-Only Display -->
                            <div style="margin-top:20px; border-top:1px dashed var(--border-color); padding-top:16px; display:flex; flex-direction:column; gap:12px;">
                                <h4 style="margin:0; font-size:0.95rem; font-weight:700; color:var(--text-primary);">
                                    D. Evaluasi terhadap Laporan, Tindakan dan Risiko (diisi oleh QA)
                                </h4>
                                <div class="grid-2" style="gap:12px;">
                                    <div>
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">I. Jenis Penyimpangan</span>
                                        <span style="font-size:0.875rem;font-weight:600;color:var(--text-primary);">
                                            {{ deviation.evaluasi_tindakan?.jenis_penyimpangan || 'Non Bets' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">II. Tindakan yang Diusulkan</span>
                                        <div v-if="deviation.evaluasi_tindakan?.tindakan_diusulkan && deviation.evaluasi_tindakan.tindakan_diusulkan.length" style="display:flex; flex-direction:column; gap:4px; font-size:0.85rem; color:var(--text-primary);">
                                            <div v-if="deviation.evaluasi_tindakan.tindakan_diusulkan.includes('olah_ulang')">
                                                🔄 Produk diolah ulang <span v-if="deviation.evaluasi_tindakan.no_form_olah_ulang" style="color:var(--text-muted);">({{ deviation.evaluasi_tindakan.no_form_olah_ulang }})</span>
                                            </div>
                                            <div v-if="deviation.evaluasi_tindakan.tindakan_diusulkan.includes('fus_stability')">
                                                🧪 FUS Stability <span v-if="deviation.evaluasi_tindakan.fus_stability_choice" style="color:var(--text-muted);">({{ deviation.evaluasi_tindakan.fus_stability_choice }})</span>
                                            </div>
                                            <div v-if="deviation.evaluasi_tindakan.tindakan_diusulkan.includes('lain_lain')">
                                                📋 Lain-lain <span v-if="deviation.capa" style="color:var(--text-muted);">(No. form CAPA: {{ deviation.capa.capa_number }})</span>
                                            </div>
                                        </div>
                                        <span v-else style="font-size:0.875rem;color:var(--text-muted);">—</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ RIGHT COLUMN ══════════════════════════════════════════ -->
                <div>
                    <!-- QA Decision Panel -->
                    <div v-if="canEvaluate && deviation.status === 'OPEN'" class="qms-card"
                        style="border-top:4px solid var(--accent-color);position:sticky;top:20px;">
                        <h3 style="font-size:1.15rem;margin-bottom:16px;">🛠️ Keputusan QA</h3>

                        <form @submit.prevent="submitDecision" style="display:flex;flex-direction:column;gap:16px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Tindakan Keputusan</label>
                                <div class="btn-toggle-group">
                                    <button type="button" @click="decisionForm.action = 'APPROVED'" class="btn-toggle"
                                        :class="{ active: decisionForm.action === 'APPROVED' }" style="font-size:0.8rem;font-weight:600;">
                                        Approve &amp; CAPA
                                    </button>
                                    <button type="button" @click="decisionForm.action = 'REJECTED'" class="btn-toggle"
                                        :class="{ active: decisionForm.action === 'REJECTED' }" style="font-size:0.8rem;font-weight:600;">
                                        Reject
                                    </button>
                                </div>
                            </div>

                            <div v-if="decisionForm.action === 'REJECTED'" class="form-group fade-in" style="margin-bottom:0;">
                                <label class="form-label">Alasan Reject (Wajib)</label>
                                <textarea v-model="decisionForm.reject_reason" class="form-textarea" rows="4"
                                    placeholder="Jelaskan alasan penolakan atau revisi yang harus dilakukan..."></textarea>
                                <div v-if="decisionForm.errors.reject_reason" style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ decisionForm.errors.reject_reason }}</div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%;" :disabled="decisionForm.processing">
                                {{ decisionForm.processing ? 'Menyimpan...' : 'Kirim Keputusan' }}
                            </button>
                        </form>
                    </div>

                    <!-- Info Panel for non-QA / non-OPEN -->
                    <div v-else class="qms-card" style="position:sticky;top:20px;">
                        <h3 style="font-size:1.1rem;margin-bottom:12px;">ℹ️ Info Deviasi</h3>
                        <div style="display:flex;flex-direction:column;gap:10px;font-size:0.85rem;color:var(--text-secondary);line-height:1.6;">
                            <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-color);padding-bottom:8px;">
                                <span style="color:var(--text-muted);">Nomor</span>
                                <strong style="font-family:monospace;color:var(--accent-color);">{{ deviation.deviation_number }}</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-color);padding-bottom:8px;">
                                <span style="color:var(--text-muted);">Status</span>
                                <span class="status-badge" :class="getStatusClass(deviation.status)">{{ deviation.status }}</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-color);padding-bottom:8px;">
                                <span style="color:var(--text-muted);">No. Bets / Identitas</span>
                                <strong>{{ deviation.department }}</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-color);padding-bottom:8px;">
                                <span style="color:var(--text-muted);">Nama Produk / Alat</span>
                                <strong>{{ deviation.pic || '—' }}</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;">
                                <span style="color:var(--text-muted);">Kepala Dept.</span>
                                <strong>{{ deviation.kepala_departemen || '—' }}</strong>
                            </div>
                        </div>

                        <div v-if="deviation.status === 'DRAFT'" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-color);">
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:10px;">
                                ⚠️ Status masih <strong>DRAFT</strong> — belum dikirim ke QA.
                            </div>
                            <Link :href="route('deviations.edit', deviation.id)" class="btn btn-primary" style="width:100%;font-size:0.85rem;">
                                ✏️ Lanjutkan Edit Deviasi
                            </Link>
                        </div>

                        <div v-if="deviation.status === 'REJECTED' && isOwner" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-color);">
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:10px;">
                                ❌ Laporan ini <strong>Ditolak</strong> oleh QA. Silakan perbaiki sesuai catatan:
                            </div>
                            <div v-if="deviation.reject_reason" style="background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.2);border-radius:6px;padding:10px;margin-bottom:12px;">
                                <div style="font-size:0.75rem;color:#ef4444;font-weight:600;margin-bottom:4px;">Catatan QA:</div>
                                <div style="font-size:0.8rem;color:var(--text-primary);">{{ deviation.reject_reason }}</div>
                            </div>
                            <Link :href="route('deviations.edit', deviation.id)" class="btn btn-primary" style="width:100%;font-size:0.85rem;">
                                ✏️ Revisi Deviasi
                            </Link>
                        </div>

                        <div v-if="deviation.status === 'OPEN'" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-color);">
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:10px;">
                                📤 Status <strong>OPEN</strong> — laporan sedang menunggu peninjauan QA.
                            </div>
                        </div>

                        <div v-if="deviation.status === 'APPROVED'" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-color);">
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:10px;">
                                ✅ Status <strong>APPROVED</strong> — laporan telah disetujui dan CAPA telah dibuat.
                            </div>
                            <Link v-if="deviation.capa" :href="route('capas.show', deviation.capa.id)" class="btn btn-success" style="width:100%;font-size:0.85rem;">
                                📋 Lihat Lembar CAPA
                            </Link>
                        </div>

                        <p style="margin-top:16px;font-size:0.82rem;color:var(--text-muted);line-height:1.6;border-top:1px solid var(--border-color);padding-top:12px;">
                            <strong>Proses Alur:</strong><br>
                            1. DRAFT → Belum dikirim ke QA<br>
                            2. OPEN → Menunggu peninjauan QA<br>
                            3. APPROVED → CAPA otomatis dibuat<br>
                            4. REJECTED → Inisiator dapat revisi
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <AttachmentViewer
            :show="showAttachment"
            :file-path="activeAttachment.path"
            :title="activeAttachment.title"
            @close="showAttachment = false"
        />

        <!-- Print Preview Popup Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showPrintPreview" style="position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.75); display: flex; align-items: center; justify-content: center; z-index: 10000; backdrop-filter: blur(10px);" @click.self="showPrintPreview = false">
                    <div class="scale-up-anim" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; width: 92%; max-width: 950px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: var(--hover-shadow); overflow: hidden; position: relative;">
                        <!-- Header -->
                        <div class="flex-between" style="border-bottom: 1px solid var(--border-color); padding: 20px 28px; background-color: rgba(15, 23, 42, 0.02); gap: 16px;">
                            <div style="display: flex; flex-direction: column; gap: 4px; text-align: left;">
                                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin: 0; font-family: var(--font-outfit);">
                                    🖨️ {{ previewTitle }}
                                </h3>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                    No. Deviasi: {{ deviation.deviation_number }}
                                </span>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                                <button @click="printIframe" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 6px; border-radius: 8px;">
                                    🖨️ Cetak / Simpan PDF
                                </button>
                                <button @click="showPrintPreview = false" style="background: rgba(15, 23, 42, 0.05); border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.backgroundColor='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444';" onmouseout="this.style.backgroundColor='rgba(15, 23, 42, 0.05)'; this.style.color='var(--text-secondary)';">&times;</button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div style="flex: 1; padding: 20px; background-color: var(--bg-primary); display: flex; justify-content: center;">
                            <iframe 
                                id="print-iframe" 
                                :src="previewUrl" 
                                style="width: 100%; height: 60vh; border: none; border-radius: 8px; background-color: #fff;"
                            ></iframe>
                        </div>

                        <!-- Footer -->
                        <div style="border-top: 1px solid var(--border-color); padding: 16px 28px; display: flex; justify-content: flex-end; background-color: rgba(15, 23, 42, 0.02); gap: 12px;">
                            <button @click="showPrintPreview = false" class="btn btn-secondary" style="padding: 10px 24px; font-weight: 600; border-radius: 8px;">
                                Tutup
                            </button>
                            <button @click="printIframe" class="btn btn-primary" style="padding: 10px 24px; font-weight: 600; border-radius: 8px;">
                                Cetak
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.rpn-low    { color: #22c55e; }
.rpn-medium { color: #f59e0b; }
.rpn-high   { color: #ef4444; }
</style>
