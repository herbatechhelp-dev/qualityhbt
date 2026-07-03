<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const currentUser = page.props.auth.user;

const props = defineProps({
    deviation: { type: Object, required: true },
    users: { type: Array, default: () => [] },
});

// ─── Static options ─────────────────────────────────────────────────────────
const jenisPenyimpanganOptions = [
    'Raw Material / Bahan Baku',
    'Packaging Material / Bahan Kemas',
    'Finished Good / Produk Jadi',
    'In-Process / Proses Produksi',
    'Peralatan / Mesin',
    'Fasilitas / Lingkungan',
    'Metode Analisa',
    'Sistem / Dokumentasi',
    'Pemasok / Vendor',
    'Lainnya',
];

const identifikasiOptions = [
    'Inspeksi / Pemeriksaan Rutin',
    'Audit Internal',
    'Audit Eksternal / BPOM',
    'Keluhan Pelanggan',
    'Review Data / Trend Analisis',
    'Self Inspection',
    'Pelaporan Karyawan',
    'Investigasi Insiden',
    'Monitoring Lingkungan',
    'Lainnya',
];

// ─── Risk Analysis helpers ────────────────────────────────────────────────
const showSodGuide = ref(false);

const sodGuide = {
    severity: [
        { level: 'Rendah', nilai: '1–3', kondisi: 'Tidak ada dampak pada produk/proses. Temuan minor.' },
        { level: 'Sedang', nilai: '4–6', kondisi: 'Dampak terbatas, dapat dimitigasi tanpa mempengaruhi kualitas produk.' },
        { level: 'Tinggi', nilai: '7–10', kondisi: 'Dampak serius terhadap keamanan, kualitas, atau kepatuhan regulasi.' },
    ],
    occurrence: [
        { level: 'Rendah', nilai: '1–3', kondisi: 'Kurang dari 1% produk dapat mengalami ketidaksesuaian. Jarang terjadi.' },
        { level: 'Sedang', nilai: '4–6', kondisi: '1%–5% produk; 2–5 kali dalam 1 tahun periode kejadian.' },
        { level: 'Tinggi', nilai: '7–10', kondisi: 'Lebih dari 5% kejadian; berulang lebih dari 10 kali. Hampir pasti terjadi.' },
    ],
    detection: [
        { level: 'Rendah', nilai: '1–3', kondisi: 'Potensi risiko tidak dapat dideteksi.' },
        { level: 'Sedang', nilai: '4–6', kondisi: 'Potensi risiko dapat dideteksi namun tidak selalu oleh prosedur yang ada.' },
        { level: 'Tinggi', nilai: '7–10', kondisi: 'Potensi dapat berpotensi dideteksi sebelum produk dikirim.' },
    ],
};

// ─── Form state ──────────────────────────────────────────────────────────────
const form = useForm({
    department: props.deviation.department || '',
    pic: props.deviation.pic || '',
    tanggal_temuan: props.deviation.tanggal_temuan || '',
    description: props.deviation.description || '',
    jenis_penyimpangan: props.deviation.jenis_penyimpangan || [],
    identifikasi_penyimpangan: props.deviation.identifikasi_penyimpangan || [],
    kepala_departemen: props.deviation.kepala_departemen || '',
    new_attachments: [],
    new_attachment_descriptions: [],
    risk_analysis: props.deviation.risk_analysis || [],
    submit_type: 'submit',
});

// ─── Attachment helpers ───────────────────────────────────────────────────────
const attachmentFiles = ref([]);   // raw File objects
const attachmentDescs = ref([]);   // matching descriptions

const addAttachmentRow = () => {
    attachmentFiles.value.push(null);
    attachmentDescs.value.push('');
};

const removeAttachmentRow = (idx) => {
    attachmentFiles.value.splice(idx, 1);
    attachmentDescs.value.splice(idx, 1);
};

const handleAttachmentFile = (e, idx) => {
    attachmentFiles.value[idx] = e.target.files[0] || null;
};

// ─── Risk Analysis helpers ────────────────────────────────────────────────────
const addRiskRow = () => {
    form.risk_analysis.push({
        risk_identification: '',
        potensiasi_cause:    '',
        s: 1,
        o: 1,
        d: 1,
        rpn: 1,
        risk_control: '',
        action: '',
    });
};

const removeRiskRow = (idx) => {
    form.risk_analysis.splice(idx, 1);
};

const updateRpn = (row) => {
    row.rpn = (parseInt(row.s) || 1) * (parseInt(row.o) || 1) * (parseInt(row.d) || 1);
};

const getRpnClass = (rpn) => {
    if (rpn <= 50)  return 'rpn-low';
    if (rpn <= 200) return 'rpn-medium';
    return 'rpn-high';
};

// ─── Submit ──────────────────────────────────────────────────────────────────
const submitForm = (submitType) => {
    form.submit_type = submitType;
    form.new_attachments = attachmentFiles.value;
    form.new_attachment_descriptions = attachmentDescs.value;

    form.post(route('deviations.update', props.deviation.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="'Edit Deviasi: ' + deviation.deviation_number + ' - QMS'" />

    <AuthenticatedLayout>
        <template #header>✍️ Edit &amp; Revisi Laporan Deviasi</template>

        <div style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
            <div class="flex-between">
                <Link :href="route('deviations.show', deviation.id)" class="btn btn-secondary">
                    ← Kembali ke Detail
                </Link>
                <div v-if="deviation.status === 'REJECTED'"
                    style="font-size: 0.85rem; color: #ef4444; font-weight: bold; background: rgba(239,68,68,0.08); padding: 8px 16px; border-radius: 8px; border: 1px solid rgba(239,68,68,0.3);">
                    ⚠️ Laporan ini ditolak oleh QA. Alasan: "{{ deviation.reject_reason || '-' }}"
                </div>
            </div>

            <form @submit.prevent style="display:contents;">

                <!-- ─── SECTION A: Info Umum ─────────────────────────────────── -->
                <div class="qms-card">
                    <h3 style="font-size:1.1rem;font-weight:800;color:var(--accent-color);border-bottom:1px solid var(--border-color);padding-bottom:12px;margin-bottom:20px;">
                        A. Identifikasi &amp; Detail Penyimpangan
                    </h3>

                    <!-- Row 1: Inisiator (read-only) + PIC -->
                    <div class="grid-2" style="margin-bottom:16px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Inisiator (Pelapor)</label>
                            <input type="text" :value="deviation.initiator?.name || currentUser.name" class="form-input" disabled
                                style="opacity:0.6;cursor:not-allowed;" />
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">PIC / Penanggung Jawab</label>
                            <input id="pic" type="text" v-model="form.pic" class="form-input"
                                placeholder="Nama penanggung jawab..." />
                        </div>
                    </div>

                    <!-- Row 2: Departemen + Tanggal Temuan -->
                    <div class="grid-2" style="margin-bottom:16px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Departemen Pengaju <span style="color:#ef4444;">*</span></label>
                            <input id="department" type="text" v-model="form.department" class="form-input"
                                placeholder="e.g. Produksi, QC, R&D" required />
                            <div v-if="form.errors.department" style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ form.errors.department }}</div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Tanggal Temuan</label>
                            <input id="tanggal_temuan" type="date" v-model="form.tanggal_temuan" class="form-input" />
                        </div>
                    </div>

                    <!-- Deskripsi / Rincian Penyimpangan -->
                    <div class="form-group">
                        <label class="form-label">Deviasi Terkait / Rincian Penyimpangan <span style="color:#ef4444;">*</span></label>
                        <textarea id="description" v-model="form.description" class="form-textarea" rows="5"
                            placeholder="Uraikan detail temuan ketidaksesuaian, waktu kejadian, dan dampak awal jika ada..."
                            required></textarea>
                        <div v-if="form.errors.description" style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ form.errors.description }}</div>
                    </div>
                </div>

                <!-- ─── SECTION B: Klasifikasi Penyimpangan ──────────────────── -->
                <div class="qms-card">
                    <h3 style="font-size:1.1rem;font-weight:800;color:var(--accent-color);border-bottom:1px solid var(--border-color);padding-bottom:12px;margin-bottom:20px;">
                        B. Identifikasi &amp; Klasifikasi Penyimpangan
                    </h3>

                    <div class="grid-2" style="margin-bottom:20px;">
                        <!-- Jenis Penyimpangan -->
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Jenis Penyimpangan <span style="font-size:0.75rem;color:var(--text-muted);font-weight:normal;">(pilih semua yang sesuai)</span></label>
                            <div style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:8px;padding:12px;display:flex;flex-direction:column;gap:8px;max-height:240px;overflow-y:auto;">
                                <label v-for="opt in jenisPenyimpanganOptions" :key="opt"
                                    style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.875rem;color:var(--text-primary);">
                                    <input type="checkbox" :value="opt" v-model="form.jenis_penyimpangan"
                                        style="width:16px;height:16px;accent-color:var(--accent-color);" />
                                    {{ opt }}
                                </label>
                            </div>
                        </div>

                        <!-- Identifikasi / Cara Ditemukan -->
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Cara Identifikasi Penyimpangan <span style="font-size:0.75rem;color:var(--text-muted);font-weight:normal;">(pilih semua yang sesuai)</span></label>
                            <div style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:8px;padding:12px;display:flex;flex-direction:column;gap:8px;max-height:240px;overflow-y:auto;">
                                <label v-for="opt in identifikasiOptions" :key="opt"
                                    style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.875rem;color:var(--text-primary);">
                                    <input type="checkbox" :value="opt" v-model="form.identifikasi_penyimpangan"
                                        style="width:16px;height:16px;accent-color:var(--accent-color);" />
                                    {{ opt }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Kepala Departemen TTD -->
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Kepala Departemen Terkait (TTD / Tanda Tangan)</label>
                        <select id="kepala_departemen" v-model="form.kepala_departemen" class="form-select">
                            <option value="">-- Pilih Kepala Departemen --</option>
                            <option v-for="user in users" :key="user.id" :value="user.name">
                                {{ user.name }} ({{ user.role }})
                            </option>
                        </select>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">TTD hanya sampai level Kepala Departemen terkait.</div>
                    </div>
                </div>

                <!-- Existing & New Attachments -->
                <div class="qms-card">
                    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border-color);padding-bottom:12px;margin-bottom:20px;">
                        <h3 style="font-size:1.1rem;font-weight:800;color:var(--accent-color);margin:0;">
                            C. File Lampiran
                        </h3>
                        <button type="button" @click="addAttachmentRow" class="btn btn-secondary"
                            style="padding:6px 14px;font-size:0.8rem;font-weight:600;">
                            + Tambah File
                        </button>
                    </div>

                    <!-- Existing attachments display -->
                    <div v-if="deviation.attachments && deviation.attachments.length" style="margin-bottom: 20px;">
                        <label class="form-label">Lampiran Saat Ini</label>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <div v-for="(att, idx) in deviation.attachments" :key="idx"
                                style="display:flex;align-items:center;justify-content:space-between;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:8px 12px;font-size:0.85rem;">
                                <span style="font-weight:600;color:var(--text-primary);">
                                    {{ att.description || 'Lampiran ' + (idx + 1) }}
                                </span>
                                <span style="font-size:0.75rem;color:var(--text-muted);">
                                    {{ att.path }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="deviation.attachment_path" style="margin-bottom: 20px;">
                        <label class="form-label">Lampiran Saat Ini</label>
                        <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:8px;padding:8px 12px;font-size:0.85rem;">
                            <span style="font-weight:600;color:var(--text-primary);">
                                {{ deviation.attachment_description || 'Berkas Bukti' }}
                            </span>
                            <span style="font-size:0.75rem;color:var(--text-muted);">
                                {{ deviation.attachment_path }}
                            </span>
                        </div>
                    </div>

                    <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:14px;">
                        Unggah bukti penyimpangan tambahan (foto, laporan), investigasi (fish bone diagram), atau dokumen pendukung lainnya. Maks. 10 MB per file.
                    </div>

                    <!-- Attachment rows -->
                    <div v-for="(file, idx) in attachmentFiles" :key="idx"
                        style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:10px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:14px;">
                        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);min-width:28px;text-align:center;">
                            {{ idx + 1 }}
                        </div>
                        <div style="flex:1;display:flex;flex-direction:column;gap:8px;">
                            <input type="file" @change="handleAttachmentFile($event, idx)"
                                class="form-input" style="padding:8px;font-size:0.85rem;" />
                            <input type="text" v-model="attachmentDescs[idx]" class="form-input"
                                style="font-size:0.85rem;" placeholder="Keterangan: e.g. Bukti Penyimpangan, Fish Bone Diagram..." />
                        </div>
                        <button type="button" @click="removeAttachmentRow(idx)" class="btn btn-danger"
                            style="padding:6px 10px;font-size:0.8rem;flex-shrink:0;">
                            🗑️
                        </button>
                    </div>
                </div>

                <!-- ─── SECTION D: Risk Analysis ──────────────────────────────── -->
                <div class="qms-card">
                    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border-color);padding-bottom:12px;margin-bottom:20px;">
                        <h3 style="font-size:1.1rem;font-weight:800;color:var(--accent-color);margin:0;">
                            D. Risk Analysis (FMEA)
                        </h3>
                        <div style="display:flex;gap:8px;">
                            <button type="button" @click="showSodGuide = true" class="btn btn-secondary"
                                style="padding:6px 12px;font-size:0.8rem;font-weight:600;">
                                ℹ️ Panduan SOD
                            </button>
                            <button type="button" @click="addRiskRow" class="btn btn-secondary"
                                style="padding:6px 14px;font-size:0.8rem;font-weight:600;">
                                + Tambah Baris
                            </button>
                        </div>
                    </div>

                    <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:14px;">
                        Isi analisis risiko penyimpangan. RPN dihitung otomatis: <strong>RPN = S × O × D</strong>.
                        Klik "ℹ️ Panduan SOD" untuk melihat tabel acuan nilai.
                    </div>

                    <!-- Empty state -->
                    <div v-if="form.risk_analysis.length === 0"
                        style="text-align:center;color:var(--text-muted);font-size:0.85rem;padding:24px;background:var(--bg-primary);border:1px dashed var(--border-color);border-radius:8px;">
                        📊 Belum ada baris analisis risiko. Klik "+ Tambah Baris" untuk menambahkan.
                    </div>

                    <!-- Risk rows as cards -->
                    <div v-for="(row, idx) in form.risk_analysis" :key="idx"
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
                                <label class="form-label">Risk Identification</label>
                                <input type="text" v-model="row.risk_identification" class="form-input"
                                    placeholder="Identifikasi potensi risiko..." />
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Potensiasi Cause (Akar Masalah)</label>
                                <input type="text" v-model="row.potensiasi_cause" class="form-input"
                                    placeholder="Penyebab potensial risiko..." />
                            </div>
                        </div>

                        <!-- SOD values -->
                        <div style="display:flex;gap:10px;margin-bottom:12px;align-items:flex-end;flex-wrap:wrap;">
                            <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                <label class="form-label">Severity (S)</label>
                                <select v-model.number="row.s" class="form-select" @change="updateRpn(row)">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                <label class="form-label">Occurrence (O)</label>
                                <select v-model.number="row.o" class="form-select" @change="updateRpn(row)">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                <label class="form-label">Detection (D)</label>
                                <select v-model.number="row.d" class="form-select" @change="updateRpn(row)">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div style="min-width:100px;flex:1;text-align:center;padding-bottom:2px;">
                                <div style="font-size:0.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:4px;">RPN (S×O×D)</div>
                                <div style="font-size:1.6rem;font-weight:900;line-height:1;" :class="getRpnClass(row.rpn)">
                                    {{ row.rpn }}
                                </div>
                            </div>
                        </div>

                        <div class="grid-2" style="gap:12px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Risk Control</label>
                                <input type="text" v-model="row.risk_control" class="form-input"
                                    placeholder="Pengendalian risiko yang dilakukan..." />
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Action (Tindakan)</label>
                                <input type="text" v-model="row.action" class="form-input"
                                    placeholder="Tindakan korektif / pencegahan..." />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── Footer: Save as Draft / Submit ───────────────────────── -->
                <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 24px;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:12px;">
                    <button type="button" @click="submitForm('draft')" class="btn btn-secondary"
                        :disabled="form.processing"
                        style="padding:10px 24px;font-weight:700;font-size:0.95rem;">
                        📁 Simpan Draf
                    </button>
                    <div style="font-size:0.8rem;color:var(--text-muted);text-align:center;max-width:320px;line-height:1.4;">
                        Nomor Deviasi permanen akan didapatkan ketika <strong>Kirim &amp; Ajukan</strong>.
                    </div>
                    <button type="button" @click="submitForm('submit')" class="btn btn-primary"
                        :disabled="form.processing"
                        style="padding:10px 24px;font-weight:700;font-size:0.95rem;">
                        🚀 Kirim &amp; Ajukan Laporan
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.rpn-low    { color: #22c55e; }
.rpn-medium { color: #f59e0b; }
.rpn-high   { color: #ef4444; }
</style>
