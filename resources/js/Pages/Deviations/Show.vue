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
        { level: 'Rendah', nilai: '1–3', kondisi: 'Tidak ada dampak pada produk/proses. Temuan minor.' },
        { level: 'Sedang', nilai: '4–6', kondisi: 'Dampak terbatas, dapat dimitigasi tanpa mempengaruhi kualitas produk.' },
        { level: 'Tinggi', nilai: '7–10', kondisi: 'Dampak serius terhadap keamanan, kualitas, atau kepatuhan regulasi.' },
    ],
    occurrence: [
        { level: 'Rendah', nilai: '1–3', kondisi: 'Kurang dari 1% produk dapat mengalami ketidaksesuaian. Jarang terjadi.' },
        { level: 'Sedang', nilai: '4–6', kondisi: '1%–5% produk; 2–5 kali dalam 1 tahun periode kejadian.' },
        { level: 'Tinggi', nilai: '7–10', kondisi: 'Lebih dari 5% kejadian; berulang lebih dari 10 kali. Hampir pasti terjadi.' },
    ],
    detection:  [
        { level: 'Rendah', nilai: '1–3', kondisi: 'Potensi risiko tidak dapat dideteksi.' },
        { level: 'Sedang', nilai: '4–6', kondisi: 'Potensi risiko dapat dideteksi namun tidak selalu oleh prosedur yang ada.' },
        { level: 'Tinggi', nilai: '7–10', kondisi: 'Potensi dapat berpotensi dideteksi sebelum produk dikirim.' },
    ],
};
const getRpnClass = (rpn) => {
    if (rpn <= 50)  return 'rpn-low';
    if (rpn <= 200) return 'rpn-medium';
    return 'rpn-high';
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
                                ≤ 50: Rendah (hijau) &nbsp;|&nbsp; 51–200: Sedang (kuning) &nbsp;|&nbsp; &gt;200: Tinggi (merah)
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
                                <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Departemen Pengaju</span>
                                <span style="font-weight:600;color:var(--text-primary);">{{ deviation.department }}</span>
                            </div>
                        </div>

                        <div class="grid-2" style="margin-bottom:16px;">
                            <div>
                                <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">PIC / Penanggung Jawab</span>
                                <span style="font-weight:600;color:var(--text-primary);">{{ deviation.pic || '-' }}</span>
                            </div>
                            <div>
                                <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Tanggal Temuan</span>
                                <span style="font-weight:600;color:var(--text-primary);">
                                    {{ deviation.tanggal_temuan ? new Date(deviation.tanggal_temuan).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'}) : '-' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span style="font-size:0.75rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:6px;">Deviasi Terkait / Rincian Penyimpangan</span>
                            <div style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:8px;padding:16px;line-height:1.7;color:var(--text-primary);white-space:pre-wrap;font-size:0.9rem;">{{ deviation.description }}</div>
                        </div>

                        <div style="margin-top:8px;font-size:0.78rem;color:var(--text-muted);text-align:right;">
                            Dilaporkan: {{ new Date(deviation.created_at).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'}) }}
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
                            <button type="button" @click="showSodGuide = true" class="btn btn-secondary"
                                style="padding:4px 12px;font-size:0.78rem;font-weight:600;">
                                ℹ️ Panduan SOD
                            </button>
                        </div>

                        <div v-if="deviation.risk_analysis && deviation.risk_analysis.length" style="display:flex;flex-direction:column;gap:12px;">
                            <div v-for="(row, idx) in deviation.risk_analysis" :key="idx"
                                style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:10px;padding:16px;">
                                <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;">
                                    <span>Risiko #{{ idx + 1 }}</span>
                                    <span style="font-size:1rem;font-weight:900;" :class="getRpnClass(parseInt(row.rpn))">
                                        RPN = {{ row.rpn }}
                                        <span style="font-size:0.7rem;font-weight:600;margin-left:4px;">
                                            ({{ parseInt(row.rpn) <= 50 ? 'Rendah' : parseInt(row.rpn) <= 200 ? 'Sedang' : 'Tinggi' }})
                                        </span>
                                    </span>
                                </div>

                                <div class="grid-2" style="gap:12px;margin-bottom:12px;">
                                    <div>
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Risk Identification</span>
                                        <span style="font-size:0.875rem;color:var(--text-primary);">{{ row.risk_identification || '—' }}</span>
                                    </div>
                                    <div>
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Potensiasi Cause</span>
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
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Risk Control</span>
                                        <span style="font-size:0.875rem;color:var(--text-primary);">{{ row.risk_control || '—' }}</span>
                                    </div>
                                    <div>
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;text-transform:uppercase;margin-bottom:4px;">Action (Tindakan)</span>
                                        <span style="font-size:0.875rem;color:var(--text-primary);">{{ row.action || '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else style="color:var(--text-muted);font-size:0.875rem;padding:12px 0;">📊 Tidak ada data analisis risiko.</div>
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
                                <span style="color:var(--text-muted);">Departemen</span>
                                <strong>{{ deviation.department }}</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-color);padding-bottom:8px;">
                                <span style="color:var(--text-muted);">PIC</span>
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
