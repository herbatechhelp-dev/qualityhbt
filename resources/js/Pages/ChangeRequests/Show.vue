<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AttachmentViewer from '@/Components/AttachmentViewer.vue';

const showAttachment = ref(false);
const activeQaTab = ref('qa_1');

const page = usePage();
const currentUser = page.props.auth.user;

// QA or management roles can evaluate
const canEvaluate = computed(() => [
    'qa',
    'superadmin',
    'head_of_quality',
    'operational_manager',
    'general_manager'
].includes(currentUser.role));

const isHu = computed(() => currentUser.role === 'head_of_quality');
const isOm = computed(() => currentUser.role === 'operational_manager');
const isGm = computed(() => currentUser.role === 'general_manager');
const isSuperAdmin = computed(() => currentUser.role === 'superadmin');
const isQa = computed(() => currentUser.role === 'qa');
const canSubmitToManagement = computed(() => isQa.value || isSuperAdmin.value);
const showEvaluateCard = computed(() => {
    if (isQa.value || isSuperAdmin.value) {
        return true;
    }
    if (['head_of_quality', 'operational_manager', 'general_manager'].includes(currentUser.role)) {
        return !!props.changeRequest.qa_verification_data?.qa_1?.submitted;
    }
    return false;
});

const props = defineProps({
    changeRequest: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const predefinedTypes = [
    'Formula',
    'Proses Produksi',
    'Pemasok',
    'Bahan Baku',
    'Bahan kemas',
    'Fasilitas',
    'Peralatan',
    'Stabilitas',
    'Metode Analisa',
    'Permintaan BPOM'
];

const initialSifatPerubahan = predefinedTypes.includes(props.changeRequest.sifat_perubahan)
    ? props.changeRequest.sifat_perubahan
    : (props.changeRequest.sifat_perubahan ? 'Lain - lain' : 'Formula');

const initialSifatPerubahanCustom = initialSifatPerubahan === 'Lain - lain'
    ? props.changeRequest.sifat_perubahan
    : '';

// Draft edits form (only if status === 'DRAFT' or 'REJECT')
const editForm = useForm({
    type: props.changeRequest.type,
    sifat_perubahan: initialSifatPerubahan,
    sifat_perubahan_custom: initialSifatPerubahanCustom,
    department: props.changeRequest.department,
    awal_sebelum_perubahan: props.changeRequest.awal_sebelum_perubahan || '',
    usulan_perubahan: props.changeRequest.usulan_perubahan || '',
    alasan_perubahan: props.changeRequest.alasan_perubahan || '',
    analisis_dampak: props.changeRequest.analisis_dampak || '',
    severity: props.changeRequest.severity || 1,
    occurrence: props.changeRequest.occurrence || 1,
    detection: props.changeRequest.detection || 1,
    attachment: null,
    attachment_description: props.changeRequest.attachment_description || '',
    submit_type: 'submit',
});

// Default QA verification data structure
const defaultVerificationData = {
    qa_1: {
        pengerjaan_sesuai: false,
        ulasan: '',
        diulas_oleh: currentUser.name,
        tanggal: new Date().toISOString().substr(0, 10),
        paraf: true,
        submitted: false,
        hu_approved: 'PENDING',
        om_approved: 'PENDING',
        gm_approved: 'PENDING'
    },
    qa_2: {
        no_registrasi: 'REG/' + props.changeRequest.cr_number,
        nama_produk: '',
        pom_status: 'option1', // default to first option
        pom_pemberitahuan_dari: '',
        pom_pemberitahuan_sampai: '',
        pom_rencana_disetujui_tanggal: '',
        documents: []
    },
    qa_3: {
        no_pengendalian: 'CTRL/' + props.changeRequest.cr_number,
        implementations: [],
        verifikasi_completed: false
    }
};

const mapApprovals = (data) => {
    if (data === true || data === 'APPROVED') return 'APPROVED';
    if (data === 'REJECTED') return 'REJECTED';
    return 'PENDING';
};

// Map existing qa_verification_data if available
const initialVerificationData = props.changeRequest.qa_verification_data 
    ? {
        qa_1: { 
            ...defaultVerificationData.qa_1, 
            ...(props.changeRequest.qa_verification_data.qa_1 || {}),
            submitted: !!props.changeRequest.qa_verification_data.qa_1?.submitted,
            hu_approved: mapApprovals(props.changeRequest.qa_verification_data.qa_1?.hu_approved),
            om_approved: mapApprovals(props.changeRequest.qa_verification_data.qa_1?.om_approved),
            gm_approved: mapApprovals(props.changeRequest.qa_verification_data.qa_1?.gm_approved),
        },
        qa_2: { ...defaultVerificationData.qa_2, ...(props.changeRequest.qa_verification_data.qa_2 || {}) },
        qa_3: { ...defaultVerificationData.qa_3, ...(props.changeRequest.qa_verification_data.qa_3 || {}) }
      }
    : defaultVerificationData;

// QA evaluation form
const qaForm = useForm({
    rencana_tindakan: props.changeRequest.rencana_tindakan || '',
    pic_id: props.changeRequest.pic_id || '',
    timeline: props.changeRequest.timeline || '',
    hasil_verifikasi: props.changeRequest.hasil_verifikasi || '',
    status: props.changeRequest.status === 'DRAFT' || props.changeRequest.status === 'OPEN' ? 'IN REVIEW' : props.changeRequest.status,
    qa_verification_data: initialVerificationData,
    qa_3_files: Array(initialVerificationData.qa_3.implementations.length).fill(null)
});

const isEditingDraft = ref(false);

const toggleEditDraft = () => {
    isEditingDraft.value = !isEditingDraft.value;
};

const handleAttachmentChange = (e) => {
    editForm.attachment = e.target.files[0];
};

const submitDraftUpdate = (submitType) => {
    editForm.submit_type = submitType;
    // We send POST but with _method: 'POST' (Inertia uploads must be POST)
    editForm.post(route('change-requests.update', props.changeRequest.id), {
        onSuccess: () => {
            isEditingDraft.value = false;
        }
    });
};

const submitQaEvaluation = () => {
    qaForm.post(route('change-requests.evaluate', props.changeRequest.id));
};

const submitToManagement = () => {
    qaForm.qa_verification_data.qa_1.submitted = true;
    submitQaEvaluation();
};

// For HO/OM/GM: reset status to IN REVIEW so backend can recalculate
// based on the actual approval states (avoids stuck REJECT)
const submitManagementApproval = () => {
    if (qaForm.status === 'REJECT') {
        qaForm.status = 'IN REVIEW';
    }
    submitQaEvaluation();
};

const setApproval = (level, status) => {
    const key = `${level}_approved`;
    const currentVal = qaForm.qa_verification_data.qa_1[key];
    
    if (currentVal === status) {
        qaForm.qa_verification_data.qa_1[key] = 'PENDING';
    } else {
        qaForm.qa_verification_data.qa_1[key] = status;
        
        if (status === 'REJECTED') {
            qaForm.status = 'REJECT';
        }
    }
};

watch(() => qaForm.qa_verification_data.qa_1.hu_approved, (newVal) => {
    if (newVal !== 'APPROVED') {
        qaForm.qa_verification_data.qa_1.om_approved = 'PENDING';
        qaForm.qa_verification_data.qa_1.gm_approved = 'PENDING';
    }
});

watch(() => qaForm.qa_verification_data.qa_1.om_approved, (newVal) => {
    if (newVal !== 'APPROVED') {
        qaForm.qa_verification_data.qa_1.gm_approved = 'PENDING';
    }
});

const addDocumentRow = () => {
    qaForm.qa_verification_data.qa_2.documents.push({
        jenis: '',
        no_dokumen: '',
        tanggal_berlaku: '',
        pic: '',
        timeline: ''
    });
};

const removeDocumentRow = (index) => {
    qaForm.qa_verification_data.qa_2.documents.splice(index, 1);
};

const addImplementationRow = () => {
    qaForm.qa_verification_data.qa_3.implementations.push({
        pic: null,
        perubahan: '',
        tanggal_dilakukan: '',
        bukti_dokumen_path: '',
        tanggal_berlaku: ''
    });
    qaForm.qa_3_files.push(null);
};

const removeImplementationRow = (index) => {
    qaForm.qa_verification_data.qa_3.implementations.splice(index, 1);
    qaForm.qa_3_files.splice(index, 1);
};

const handleImplementationFileChange = (e, index) => {
    qaForm.qa_3_files[index] = e.target.files[0];
};

const deleteAttachment = () => {
    if (confirm('Apakah Anda yakin ingin menghapus lampiran ini?')) {
        router.delete(route('change-requests.destroy-attachment', props.changeRequest.id));
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'DRAFT': return 'badge-draft';
        case 'OPEN': return 'badge-open';
        case 'IN REVIEW': return 'badge-in_review';
        case 'APPROVED': return 'badge-approved';
        case 'IN PROGRESS': return 'badge-in_progress';
        case 'COMPLETE': return 'badge-complete';
        case 'REJECT': return 'badge-reject';
        default: return 'badge-draft';
    }
};
</script>

<template>
    <Head :title="'Detail CR: ' + changeRequest.cr_number" />

    <AuthenticatedLayout>
        <template #header>
            🔍 Detail Change Request: {{ changeRequest.cr_number }}
        </template>

        <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
            <div class="flex-between">
                <Link :href="route('change-requests.index')" class="btn btn-secondary">
                    ← Kembali ke Logbook
                </Link>
                <div v-if="(changeRequest.status === 'DRAFT' || changeRequest.status === 'REJECT') && $page.props.auth.user.id === changeRequest.initiator_id">
                    <button @click="toggleEditDraft" class="btn btn-primary">
                        {{ isEditingDraft ? 'Batal Edit' : (changeRequest.status === 'REJECT' ? '✍️ Edit & Revisi' : '✍️ Edit Draf') }}
                    </button>
                </div>
            </div>

            <!-- DRAF EDIT MODE -->
            <div v-if="isEditingDraft" class="qms-card fade-in">
                <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px;">
                    {{ changeRequest.status === 'REJECT' ? 'Revisi Pengajuan Change Request' : 'Edit Draf Change Request' }}
                </h3>
                <div class="grid-2" style="margin-bottom: 20px;">
                    <div v-if="editForm.type === 'CRA'" class="form-group">
                        <label class="form-label">Jenis Perubahan</label>
                        <div style="display: flex; gap: 8px; flex-direction: column;">
                            <select v-model="editForm.sifat_perubahan" class="form-select">
                                <option value="Formula">Formula</option>
                                <option value="Proses Produksi">Proses Produksi</option>
                                <option value="Pemasok">Pemasok</option>
                                <option value="Bahan Baku">Bahan Baku</option>
                                <option value="Bahan kemas">Bahan kemas</option>
                                <option value="Fasilitas">Fasilitas</option>
                                <option value="Peralatan">Peralatan</option>
                                <option value="Stabilitas">Stabilitas</option>
                                <option value="Metode Analisa">Metode Analisa</option>
                                <option value="Permintaan BPOM">Permintaan BPOM</option>
                                <option value="Lain - lain">Lain - lain</option>
                            </select>
                            <input v-if="editForm.sifat_perubahan === 'Lain - lain'" type="text" v-model="editForm.sifat_perubahan_custom" class="form-input" placeholder="Tuliskan jenis perubahan manual..." />
                        </div>
                    </div>
                    <div class="form-group" :style="editForm.type !== 'CRA' ? 'grid-column: span 2;' : ''">
                        <label class="form-label">Departemen</label>
                        <input type="text" v-model="editForm.department" class="form-input" />
                    </div>
                </div>

                <!-- Detail Perubahan Textareas (Common for CRA & CRB) -->
                <div style="background-color: var(--bg-primary); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; flex-direction: column; gap: 16px;">
                    <h4 style="font-weight: bold; color: var(--accent-color); margin-bottom: 4px;">
                        {{ editForm.type === 'CRA' ? '⚠️ Uraian Analisis Risiko FMEA (CRA)' : '📝 Uraian Detail Perubahan (CRB)' }}
                    </h4>
                    
                    <div class="form-group">
                        <label class="form-label">
                            AWAL SEBELUM PERUBAHAN <span style="font-size: 0.775rem; color: var(--text-muted); font-weight: normal; text-transform: none;">(tulis rinciannya dalam lampiran, bila perlu)</span>
                        </label>
                        <textarea v-model="editForm.awal_sebelum_perubahan" class="form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            USULAN PERUBAHAN <span style="font-size: 0.775rem; color: var(--text-muted); font-weight: normal; text-transform: none;">(tulis rinciannya dalam lampiran, bila perlu)</span>
                        </label>
                        <textarea v-model="editForm.usulan_perubahan" class="form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            ALASAN PERUBAHAN <span style="font-size: 0.775rem; color: var(--text-muted); font-weight: normal; text-transform: none;">(tulis rinciannya dalam lampiran, bila perlu)</span>
                        </label>
                        <textarea v-model="editForm.alasan_perubahan" class="form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            ANALISIS DAMPAK DAN PENILAIAN RESIKO <span style="font-size: 0.775rem; color: var(--text-muted); font-weight: normal; text-transform: none;">(tulis kajian risiko, dan juga benefit/manfaat dari perubahan yang terjadi termasuk analisa biaya yang timbul untuk pelaksanaan perubahan dan dampak dari perubahan, bila perlu dapat dilampirkan form risk analisis)</span>
                        </label>
                        <textarea v-model="editForm.analisis_dampak" class="form-textarea" rows="3"></textarea>
                    </div>

                    <!-- FMEA parameters (CRA Only) -->
                    <div v-if="editForm.type === 'CRA'" style="border-top: 1px solid var(--border-color); padding-top: 12px;">
                        <label class="form-label" style="font-weight: 600;">Penilaian Parameter Risiko FMEA (Skala 1 - 10)</label>
                        <div class="grid-3" style="margin-bottom: 16px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Severity (Keparahan)</label>
                                <select v-model.number="editForm.severity" class="form-select">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Occurrence (Keterjadian)</label>
                                <select v-model.number="editForm.occurrence" class="form-select">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Detection (Deteksi)</label>
                                <select v-model.number="editForm.detection" class="form-select">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                        </div>
                        <div style="font-size: 0.9rem; font-weight: bold; color: var(--text-primary); margin-bottom: 16px;">
                            Live Calculated RPN: {{ editForm.severity * editForm.occurrence * editForm.detection }}
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">File Lampiran</label>
                        <input type="file" @change="handleAttachmentChange" class="form-input" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan Lampiran</label>
                        <input type="text" v-model="editForm.attachment_description" class="form-input" />
                    </div>
                </div>

                <div class="flex-between" style="margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <button type="button" @click="submitDraftUpdate('draft')" class="btn btn-secondary" :disabled="editForm.processing">
                        📁 Perbarui Draf
                    </button>
                    <button type="button" @click="submitDraftUpdate('submit')" class="btn btn-primary" :disabled="editForm.processing">
                        🚀 Kirim & Ajukan Kembali
                    </button>
                </div>
            </div>

            <!-- VIEW MODE -->
            <div v-else style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Rejection Alert Banner -->
                <div v-if="changeRequest.status === 'REJECT'" style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: start; gap: 12px;">
                    <span style="font-size: 1.4rem;">❌</span>
                    <div style="flex-grow: 1;">
                        <div style="font-weight: 600; color: #ef4444; margin-bottom: 4px;">Usulan Change Request Ditolak oleh QA</div>
                        <div style="font-size: 0.875rem; color: var(--text-primary); margin-bottom: 8px;">Silakan tinjau catatan hasil verifikasi di bawah, lakukan revisi, lalu kirim kembali usulan ini.</div>
                        <div style="font-size: 0.8rem; background: var(--bg-secondary); border-radius: 6px; padding: 8px 12px; border-left: 3px solid #ef4444; color: var(--text-muted); font-style: italic;">
                            Catatan QA: "{{ changeRequest.hasil_verifikasi || '-' }}"
                        </div>
                    </div>
                </div>
                <form @submit.prevent="submitQaEvaluation" style="display: contents;">
                <div class="qms-show-grid">
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        <!-- Detail Card -->
                    <div class="qms-card">
                        <div class="flex-between" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px;">
                            <div>
                                <span class="status-badge" :class="changeRequest.type === 'CRA' ? 'badge-in_progress' : 'badge-draft'" style="margin-right: 8px;">
                                    {{ changeRequest.type }}
                                </span>
                                <span class="status-badge" :class="getStatusClass(changeRequest.status)">
                                    {{ changeRequest.status }}
                                </span>
                            </div>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">
                                Diajukan pada: {{ new Date(changeRequest.created_at).toLocaleDateString('id-ID') }}
                            </span>
                        </div>

                        <div class="grid-2" style="margin-bottom: 24px;">
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase;">Inisiator</span>
                                <span style="font-weight: 600; color: var(--text-primary);">{{ changeRequest.initiator.name }}</span>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase;">Departemen</span>
                                <span style="font-weight: 600; color: var(--text-primary);">{{ changeRequest.department }}</span>
                            </div>
                            <div v-if="changeRequest.type === 'CRA'">
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase;">Jenis Perubahan</span>
                                <span style="font-weight: 600; color: var(--text-primary);">{{ changeRequest.sifat_perubahan }}</span>
                            </div>
                        </div>

                        <!-- Uraian Detail Perubahan (Common for CRA & CRB) -->
                        <div style="background-color: var(--bg-primary); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 16px; margin-top: 20px;">
                            <h4 style="font-weight: 700; color: var(--accent-color);">
                                {{ changeRequest.type === 'CRA' ? '⚠️ Uraian Analisis Risiko (CRA)' : '📝 Uraian Detail Perubahan (CRB)' }}
                            </h4>
                            
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; font-weight: 600;">AWAL SEBELUM PERUBAHAN</span>
                                <p style="color: var(--text-primary); margin-top: 4px; line-height: 1.5; white-space: pre-line;">{{ changeRequest.awal_sebelum_perubahan }}</p>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; font-weight: 600;">USULAN PERUBAHAN</span>
                                <p style="color: var(--text-primary); margin-top: 4px; line-height: 1.5; white-space: pre-line;">{{ changeRequest.usulan_perubahan }}</p>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; font-weight: 600;">ALASAN PERUBAHAN</span>
                                <p style="color: var(--text-primary); margin-top: 4px; line-height: 1.5; white-space: pre-line;">{{ changeRequest.alasan_perubahan }}</p>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; font-weight: 600;">ANALISIS DAMPAK DAN PENILAIAN RESIKO</span>
                                <p style="color: var(--text-primary); margin-top: 4px; line-height: 1.5; white-space: pre-line;">{{ changeRequest.analisis_dampak }}</p>
                            </div>

                            <!-- FMEA Parameters & RPN Score (CRA Only) -->
                            <div v-if="changeRequest.type === 'CRA'" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; border: 1px dashed var(--border-color); padding: 12px 16px; border-radius: 8px; background-color: var(--bg-secondary); margin-top: 8px;">
                                <div class="grid-3" style="flex-grow: 1; font-size: 0.85rem;">
                                    <div>Keparahan (S): <strong>{{ changeRequest.severity }}</strong></div>
                                    <div>Keterjadian (O): <strong>{{ changeRequest.occurrence }}</strong></div>
                                    <div>Deteksi (D): <strong>{{ changeRequest.detection }}</strong></div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: bold;">Score RPN</div>
                                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent-color);">{{ changeRequest.rpn }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Attachment Section -->
                        <div style="border-top: 1px solid var(--border-color); margin-top: 24px; padding-top: 20px;">
                            <h4 style="font-size: 1rem; margin-bottom: 12px; font-weight: bold; color: var(--text-primary);">📎 Lampiran Berkas</h4>
                            <div v-if="changeRequest.attachment_path" style="background-color: var(--bg-tertiary); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">
                                        {{ changeRequest.attachment_description || 'File Lampiran' }}
                                    </span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); word-break: break-all;">
                                        {{ changeRequest.attachment_path }}
                                    </span>
                                </div>
                                <div class="flex-gap-10">
                                    <button type="button" @click="showAttachment = true" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem; font-weight: 600;">
                                        👁️ Lihat Lampiran
                                    </button>
                                    <button 
                                        v-if="changeRequest.status === 'DRAFT' && $page.props.auth.user.id === changeRequest.initiator_id"
                                        @click="deleteAttachment"
                                        class="btn btn-danger"
                                        style="padding: 6px 12px; font-size: 0.8rem;"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                            <div v-else style="color: var(--text-muted); font-size: 0.9rem;">
                                Tidak ada berkas dilampirkan.
                            </div>
                        </div>
                    </div>

                    <!-- Multi-Stage QA Verification Form (Editable for QA Role) -->
                    <div v-if="showEvaluateCard" class="qms-card" style="border-top: 4px solid var(--accent-color);">
                        <h3 style="font-size: 1.15rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px; color: var(--accent-color);">
                            📋 Alur Verifikasi QA (Tahap 1 - 3)
                        </h3>
                        
                        <!-- Tabs navigation -->
                        <div class="tabs-navigation" style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); padding-bottom: 8px; margin-bottom: 20px; flex-wrap: wrap;">
                            <button type="button" @click="activeQaTab = 'qa_1'" class="btn" :class="activeQaTab === 'qa_1' ? 'btn-primary' : 'btn-secondary'" style="font-size: 0.85rem; padding: 8px 16px;">
                                1. Verifikasi QA Ke-1
                            </button>
                            <button type="button" @click="activeQaTab = 'qa_2'" class="btn" :class="activeQaTab === 'qa_2' ? 'btn-primary' : 'btn-secondary'" style="font-size: 0.85rem; padding: 8px 16px;" :disabled="qaForm.qa_verification_data.qa_1.gm_approved !== 'APPROVED'">
                                2. Verifikasi QA Ke-2
                            </button>
                            <button type="button" @click="activeQaTab = 'qa_3'" class="btn" :class="activeQaTab === 'qa_3' ? 'btn-primary' : 'btn-secondary'" style="font-size: 0.85rem; padding: 8px 16px;" :disabled="qaForm.qa_verification_data.qa_1.gm_approved !== 'APPROVED'">
                                3. Verifikasi QA Ke-3
                            </button>
                        </div>

                        <!-- Tab 1 Panel -->
                        <div v-show="activeQaTab === 'qa_1'" style="display: flex; flex-direction: column; gap: 16px;">
                            <div style="font-size: 0.95rem; font-weight: bold; color: var(--accent-color); margin-bottom: 8px;">
                                VERIFIKASI QA KE 1 (Penilaian Awal Pengerjaan)
                            </div>
                            <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                <input type="checkbox" id="qa_1_pengerjaan_sesuai" v-model="qaForm.qa_verification_data.qa_1.pengerjaan_sesuai" />
                                <label for="qa_1_pengerjaan_sesuai" style="font-weight: 600; margin-bottom: 0;">Pengerjaan Dinilai Sesuai</label>
                            </div>
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label class="form-label">QA/Admin Departemen Terkait Menilai Pengerjaan</label>
                                <textarea v-model="qaForm.qa_verification_data.qa_1.ulasan" class="form-textarea" rows="3" placeholder="Tuliskan ulasan pengerjaan di sini..."></textarea>
                            </div>
                            
                            <!-- Table Ulasan Reviewer -->
                            <div style="margin-top: 12px;">
                                <label class="form-label" style="font-weight: 600;">Ulasan Reviewer</label>
                                <table class="qms-table" style="margin-top: 8px; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Diulas Oleh</th>
                                            <th>Tanggal</th>
                                            <th style="width: 150px; text-align: center;">Paraf / Konfirmasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <input type="text" v-model="qaForm.qa_verification_data.qa_1.diulas_oleh" class="form-input" />
                                            </td>
                                            <td>
                                                <input type="date" v-model="qaForm.qa_verification_data.qa_1.tanggal" class="form-input" />
                                            </td>
                                            <td style="text-align: center;">
                                                <input type="checkbox" v-model="qaForm.qa_verification_data.qa_1.paraf" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Submit to Management Button -->
                            <div style="margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 16px; display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <span style="font-size: 0.85rem; color: var(--text-muted);">
                                        Status Pengajuan ke Manajemen:
                                    </span>
                                    <strong style="margin-left: 8px;" :style="qaForm.qa_verification_data.qa_1.submitted ? 'color: var(--success-color);' : 'color: var(--text-muted);'">
                                        {{ qaForm.qa_verification_data.qa_1.submitted ? '✓ Sudah Diajukan' : 'Belum Diajukan' }}
                                    </strong>
                                </div>
                                <button v-if="!qaForm.qa_verification_data.qa_1.submitted" type="button" @click="submitToManagement" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; font-weight: 600;" :disabled="!canSubmitToManagement" :title="!canSubmitToManagement ? 'Hanya QA Officer yang dapat mengajukan persetujuan' : ''">
                                    🚀 Ajukan Persetujuan
                                </button>
                            </div>

                            <!-- Approvals HU, OM, GM with Approve & Reject Buttons -->
                            <div v-if="qaForm.qa_verification_data.qa_1.submitted" style="margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                                <label class="form-label" style="font-weight: 600; margin-bottom: 12px; display: block;">Persetujuan (Approvals)</label>
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <button v-if="isHu || isOm || isGm || isSuperAdmin" type="button" @click="submitManagementApproval" class="btn btn-primary" style="align-self: flex-end; padding: 6px 12px; font-size: 0.8rem;">💾 Save Approval</button>
                                    
                                    <!-- HU Approval Row -->
                                    <div style="display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.02);">
                                        <div>
                                            <strong style="display: block; font-size: 0.9rem; color: var(--text-primary);">Head of Quality (HU)</strong>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">Persetujuan awal ulasan pengerjaan</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <template v-if="isSuperAdmin || isHu">
                                                <button type="button" @click="setApproval('hu', 'APPROVED')" class="btn" :class="qaForm.qa_verification_data.qa_1.hu_approved === 'APPROVED' ? 'btn-success' : 'btn-secondary'" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 4px; font-weight: 600;">
                                                    ✓ Approve
                                                </button>
                                                <button type="button" @click="setApproval('hu', 'REJECTED')" class="btn" :class="qaForm.qa_verification_data.qa_1.hu_approved === 'REJECTED' ? 'btn-danger' : 'btn-secondary'" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 4px; font-weight: 600;">
                                                    ✗ Reject
                                                </button>
                                            </template>
                                            <template v-else>
                                                <span class="status-badge" :class="qaForm.qa_verification_data.qa_1.hu_approved === 'APPROVED' ? 'badge-approved' : (qaForm.qa_verification_data.qa_1.hu_approved === 'REJECTED' ? 'badge-rejected' : 'badge-open')">
                                                    {{ qaForm.qa_verification_data.qa_1.hu_approved === 'APPROVED' ? 'Disetujui' : (qaForm.qa_verification_data.qa_1.hu_approved === 'REJECTED' ? 'Ditolak' : 'Menunggu') }}
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- OM Approval Row -->
                                    <div style="display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.02);">
                                        <div>
                                            <strong style="display: block; font-size: 0.9rem; color: var(--text-primary);">Operational Manager (OM)</strong>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">Prasyarat: HU harus disetujui</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <template v-if="isSuperAdmin || isOm">
                                                <button type="button" @click="setApproval('om', 'APPROVED')" class="btn" :class="qaForm.qa_verification_data.qa_1.om_approved === 'APPROVED' ? 'btn-success' : 'btn-secondary'" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 4px; font-weight: 600;" :disabled="qaForm.qa_verification_data.qa_1.hu_approved !== 'APPROVED'">
                                                    ✓ Approve
                                                </button>
                                                <button type="button" @click="setApproval('om', 'REJECTED')" class="btn" :class="qaForm.qa_verification_data.qa_1.om_approved === 'REJECTED' ? 'btn-danger' : 'btn-secondary'" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 4px; font-weight: 600;" :disabled="qaForm.qa_verification_data.qa_1.hu_approved !== 'APPROVED'">
                                                    ✗ Reject
                                                </button>
                                            </template>
                                            <template v-else>
                                                <span class="status-badge" :class="qaForm.qa_verification_data.qa_1.om_approved === 'APPROVED' ? 'badge-approved' : (qaForm.qa_verification_data.qa_1.om_approved === 'REJECTED' ? 'badge-rejected' : 'badge-open')">
                                                    {{ qaForm.qa_verification_data.qa_1.om_approved === 'APPROVED' ? 'Disetujui' : (qaForm.qa_verification_data.qa_1.om_approved === 'REJECTED' ? 'Ditolak' : 'Menunggu') }}
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- GM Approval Row -->
                                    <div style="display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.02);">
                                        <div>
                                            <strong style="display: block; font-size: 0.9rem; color: var(--text-primary);">General Manager (GM)</strong>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">Prasyarat: OM harus disetujui</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <template v-if="isSuperAdmin || isGm">
                                                <button type="button" @click="setApproval('gm', 'APPROVED')" class="btn" :class="qaForm.qa_verification_data.qa_1.gm_approved === 'APPROVED' ? 'btn-success' : 'btn-secondary'" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 4px; font-weight: 600;" :disabled="qaForm.qa_verification_data.qa_1.om_approved !== 'APPROVED'">
                                                    ✓ Approve
                                                </button>
                                                <button type="button" @click="setApproval('gm', 'REJECTED')" class="btn" :class="qaForm.qa_verification_data.qa_1.gm_approved === 'REJECTED' ? 'btn-danger' : 'btn-secondary'" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 4px; font-weight: 600;" :disabled="qaForm.qa_verification_data.qa_1.om_approved !== 'APPROVED'">
                                                    ✗ Reject
                                                </button>
                                            </template>
                                            <template v-else>
                                                <span class="status-badge" :class="qaForm.qa_verification_data.qa_1.gm_approved === 'APPROVED' ? 'badge-approved' : (qaForm.qa_verification_data.qa_1.gm_approved === 'REJECTED' ? 'badge-rejected' : 'badge-open')">
                                                    {{ qaForm.qa_verification_data.qa_1.gm_approved === 'APPROVED' ? 'Disetujui' : (qaForm.qa_verification_data.qa_1.gm_approved === 'REJECTED' ? 'Ditolak' : 'Menunggu') }}
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Tab 2 Panel -->
                        <div v-show="activeQaTab === 'qa_2'" style="display: flex; flex-direction: column; gap: 16px;">
                            <div style="font-size: 0.95rem; font-weight: bold; color: var(--accent-color); margin-bottom: 8px;">
                                VERIFIKASI QA KE 2 (POM & Rencana Dokumen)
                            </div>
                            
                            <div class="grid-2">
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label class="form-label">Nomor Registrasi Perubahan (Auto-generated)</label>
                                    <input type="text" :value="qaForm.qa_verification_data.qa_2.no_registrasi" class="form-input" disabled />
                                </div>
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label class="form-label">Nama Produk / Proses / Sistem / Alat</label>
                                    <input type="text" v-model="qaForm.qa_verification_data.qa_2.nama_produk" class="form-input" placeholder="Masukkan nama produk/proses/sistem..." />
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label class="form-label">Perubahan dapat langsung dilaksanakan tanpa persetujuan Badan POM karena:</label>
                                <select v-model="qaForm.qa_verification_data.qa_2.pom_status" class="form-select">
                                    <option value="option1">Perubahan akan didokumentasikan bersama dengan pembaruan dokumen yang bersangkutan oleh Departemen R&D dan Departemen lain secara berkala.</option>
                                    <option value="option2">Tidak diperlukan pemberitahuan perubahan.</option>
                                    <option value="option3">Perubahan memerlukan persetujuan Badan POM terlebih dahulu.</option>
                                </select>
                            </div>

                            <!-- Conditional POM details -->
                            <div v-if="qaForm.qa_verification_data.qa_2.pom_status === 'option3'" class="fade-in" style="background-color: var(--bg-secondary); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 12px; margin-bottom: 12px;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label">Pemberitahuan akan dilaporkan oleh / sampai:</label>
                                    <input type="text" v-model="qaForm.qa_verification_data.qa_2.pom_pemberitahuan_dari" class="form-input" placeholder="e.g. R&D sampai Badan POM" />
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label">Rencana disetujui oleh Badan POM tanggal:</label>
                                    <input type="date" v-model="qaForm.qa_verification_data.qa_2.pom_rencana_disetujui_tanggal" class="form-input" />
                                </div>
                            </div>

                            <!-- Documents Table -->
                            <div style="margin-top: 12px;">
                                <div class="flex-between" style="align-items: center; margin-bottom: 8px;">
                                    <label class="form-label" style="font-weight: 600; margin-bottom: 0;">Dokumen yang Perlu Direvisi / Dialokasikan</label>
                                    <button type="button" @click="addDocumentRow" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">
                                        + Tambah Baris
                                    </button>
                                </div>
                                <!-- Empty state -->
                                <div v-if="qaForm.qa_verification_data.qa_2.documents.length === 0" style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 20px 12px; background: var(--bg-primary); border: 1px dashed var(--border-color); border-radius: 8px;">
                                    Tidak ada rencana dokumen yang ditambahkan. Klik "+ Tambah Baris" untuk menambahkan.
                                </div>

                                <!-- Document cards -->
                                <div v-for="(doc, idx) in qaForm.qa_verification_data.qa_2.documents" :key="idx"
                                    style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; margin-bottom: 12px; position: relative;">
                                    <div style="position: absolute; top: 12px; right: 12px;">
                                        <button type="button" @click="removeDocumentRow(idx)" class="btn btn-danger" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 6px;">
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">Baris #{{ idx + 1 }}</div>
                                    <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label">Jenis Dokumen</label>
                                            <input type="text" v-model="doc.jenis" class="form-input" placeholder="e.g. Spesifikasi, SOP, WI" />
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label">No Dokumen</label>
                                            <input type="text" v-model="doc.no_dokumen" class="form-input" placeholder="e.g. SP-05/RND/001-127.00" />
                                        </div>
                                    </div>
                                    <div class="grid-3" style="gap: 12px;">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label">Tanggal Berlaku</label>
                                            <input type="date" v-model="doc.tanggal_berlaku" class="form-input" />
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label">PIC (Person In Charge)</label>
                                            <select v-model="doc.pic" class="form-select">
                                                <option value="">-- Pilih PIC --</option>
                                                <option v-for="user in users" :key="user.id" :value="user.name">
                                                    {{ user.name }} ({{ user.role }})
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label">Timeline</label>
                                            <input type="text" v-model="doc.timeline" class="form-input" placeholder="e.g. Akhir Mei 2026" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3 Panel -->
                        <div v-show="activeQaTab === 'qa_3'" style="display: flex; flex-direction: column; gap: 16px;">
                            <div style="font-size: 0.95rem; font-weight: bold; color: var(--accent-color); margin-bottom: 8px;">
                                VERIFIKASI QA KE 3 (Pengendalian & Verifikasi Akhir)
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label class="form-label">Pengendalian Perubahan No (Auto-generated)</label>
                                <input type="text" :value="qaForm.qa_verification_data.qa_3.no_pengendalian" class="form-input" disabled />
                            </div>

                            <!-- Implementations Table -->
                            <div style="margin-top: 12px;">
                                <div class="flex-between" style="align-items: center; margin-bottom: 8px;">
                                    <label class="form-label" style="font-weight: 600; margin-bottom: 0;">Kondisi Perubahan yang Dilakukan</label>
                                    <button type="button" @click="addImplementationRow" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">
                                        + Tambah Baris
                                    </button>
                                </div>
                                <!-- Empty state -->
                                <div v-if="qaForm.qa_verification_data.qa_3.implementations.length === 0" style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 20px 12px; background: var(--bg-primary); border: 1px dashed var(--border-color); border-radius: 8px;">
                                    Tidak ada kondisi perubahan yang ditambahkan. Klik "+ Tambah Baris" untuk menambahkan.
                                </div>

                                <!-- Implementation cards -->
                                <div v-for="(imp, idx) in qaForm.qa_verification_data.qa_3.implementations" :key="idx"
                                    style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; margin-bottom: 12px; position: relative;">
                                    <div style="position: absolute; top: 12px; right: 12px;">
                                        <button type="button" @click="removeImplementationRow(idx)" class="btn btn-danger" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 6px;">
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">Kondisi #{{ idx + 1 }}</div>
                                    <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label">PIC (Person In Charge)</label>
                                            <select v-model="imp.pic" class="form-select">
                                                <option value="" disabled>Select PIC</option>
                                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label">Perubahan yang Dilakukan</label>
                                            <input type="text" v-model="imp.perubahan" class="form-input" placeholder="Deskripsikan perubahan..." />
                                        </div>
                                    </div>
                                    <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label">Tanggal Dilakukan</label>
                                            <input type="date" v-model="imp.tanggal_dilakukan" class="form-input" />
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label">Tanggal Berlaku</label>
                                            <input type="date" v-model="imp.tanggal_berlaku" class="form-input" />
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Bukti Dokumen</label>
                                        <div style="display: flex; flex-direction: column; gap: 6px;">
                                            <input type="file" @change="handleImplementationFileChange($event, idx)" class="form-input" style="padding: 8px;" />
                                            <span v-if="imp.bukti_dokumen_path" style="font-size: 0.8rem; color: var(--accent-color); display: flex; align-items: center; gap: 6px;">
                                                📄 <a :href="'/storage/' + imp.bukti_dokumen_path" target="_blank" style="text-decoration: underline; color: inherit;">Lihat / Unduh Berkas</a>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Verifikasi final checkbox -->
                            <div style="margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 16px; display: flex; flex-direction: column; gap: 12px;">
                                <div style="font-weight: 600; color: var(--text-primary);">VERIFIKASI</div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" id="verifikasi_completed" v-model="qaForm.qa_verification_data.qa_3.verifikasi_completed" />
                                    <label for="verifikasi_completed" style="margin-bottom: 0;">Implementasi perubahan sudah dilakukan</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Multi-Stage QA Verification Details (Read-only for Non-QA / Initiator) -->
                    <div v-else-if="changeRequest.qa_verification_data && changeRequest.qa_verification_data.qa_1?.submitted" class="qms-card">
                        <h3 style="font-size: 1.15rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px; color: var(--accent-color);">
                            📋 Riwayat Alur Verifikasi QA (Tahap 1 - 3)
                        </h3>
                        
                        <!-- Tabs navigation -->
                        <div class="tabs-navigation" style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); padding-bottom: 8px; margin-bottom: 20px; flex-wrap: wrap;">
                            <button type="button" @click="activeQaTab = 'qa_1'" class="btn" :class="activeQaTab === 'qa_1' ? 'btn-primary' : 'btn-secondary'" style="font-size: 0.85rem; padding: 6px 12px;">
                                Tahap 1
                            </button>
                            <button type="button" @click="activeQaTab = 'qa_2'" class="btn" :class="activeQaTab === 'qa_2' ? 'btn-primary' : 'btn-secondary'" style="font-size: 0.85rem; padding: 6px 12px;" :disabled="changeRequest.qa_verification_data.qa_1.gm_approved !== 'APPROVED'">
                                Tahap 2
                            </button>
                            <button type="button" @click="activeQaTab = 'qa_3'" class="btn" :class="activeQaTab === 'qa_3' ? 'btn-primary' : 'btn-secondary'" style="font-size: 0.85rem; padding: 6px 12px;" :disabled="changeRequest.qa_verification_data.qa_1.gm_approved !== 'APPROVED'">
                                Tahap 3
                            </button>
                        </div>
                        
                        <!-- Read-only Tab 1 -->
                        <div v-show="activeQaTab === 'qa_1'" style="display: flex; flex-direction: column; gap: 16px; font-size: 0.9rem;">
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Status Pengerjaan</span>
                                <strong>{{ changeRequest.qa_verification_data.qa_1.pengerjaan_sesuai ? 'Pengerjaan Sesuai ✓' : 'Pengerjaan Belum Sesuai / Belum Dinilai ✗' }}</strong>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Ulasan Pengerjaan</span>
                                <p style="color: var(--text-primary); margin-top: 4px; line-height: 1.5; white-space: pre-line;">{{ changeRequest.qa_verification_data.qa_1.ulasan || '-' }}</p>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Reviewer</span>
                                <p style="color: var(--text-primary); margin-top: 4px;">{{ changeRequest.qa_verification_data.qa_1.diulas_oleh || '-' }} ({{ new Date(changeRequest.qa_verification_data.qa_1.tanggal).toLocaleDateString('id-ID') }})</p>
                            </div>
                            <div v-if="changeRequest.qa_verification_data.qa_1.submitted" style="display: flex; gap: 24px; border-top: 1px solid var(--border-color); padding-top: 12px; flex-wrap: wrap; align-items: center;">
                                <div>HU Approval: 
                                    <span class="status-badge" :class="changeRequest.qa_verification_data.qa_1.hu_approved === 'APPROVED' ? 'badge-approved' : (changeRequest.qa_verification_data.qa_1.hu_approved === 'REJECTED' ? 'badge-rejected' : 'badge-open')">
                                        {{ changeRequest.qa_verification_data.qa_1.hu_approved === 'APPROVED' ? 'Disetujui ✓' : (changeRequest.qa_verification_data.qa_1.hu_approved === 'REJECTED' ? 'Ditolak ✗' : 'Menunggu ⏳') }}
                                    </span>
                                </div>
                                <div>OM Approval: 
                                    <span class="status-badge" :class="changeRequest.qa_verification_data.qa_1.om_approved === 'APPROVED' ? 'badge-approved' : (changeRequest.qa_verification_data.qa_1.om_approved === 'REJECTED' ? 'badge-rejected' : 'badge-open')">
                                        {{ changeRequest.qa_verification_data.qa_1.om_approved === 'APPROVED' ? 'Disetujui ✓' : (changeRequest.qa_verification_data.qa_1.om_approved === 'REJECTED' ? 'Ditolak ✗' : 'Menunggu ⏳') }}
                                    </span>
                                </div>
                                <div>GM Approval: 
                                    <span class="status-badge" :class="changeRequest.qa_verification_data.qa_1.gm_approved === 'APPROVED' ? 'badge-approved' : (changeRequest.qa_verification_data.qa_1.gm_approved === 'REJECTED' ? 'badge-rejected' : 'badge-open')">
                                        {{ changeRequest.qa_verification_data.qa_1.gm_approved === 'APPROVED' ? 'Disetujui ✓' : (changeRequest.qa_verification_data.qa_1.gm_approved === 'REJECTED' ? 'Ditolak ✗' : 'Menunggu ⏳') }}
                                    </span>
                                </div>
                            </div>
                            <div v-else style="border-top: 1px solid var(--border-color); padding-top: 12px; color: var(--text-muted); font-style: italic;">
                                ⏳ Belum diajukan ke manajemen oleh QA.
                            </div>
                        </div>
                        
                        <!-- Read-only Tab 2 -->
                        <div v-show="activeQaTab === 'qa_2'" style="display: flex; flex-direction: column; gap: 16px; font-size: 0.9rem;">
                            <div class="grid-2">
                                <div>
                                    <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Nomor Registrasi Perubahan</span>
                                    <strong>{{ changeRequest.qa_verification_data.qa_2.no_registrasi || '-' }}</strong>
                                </div>
                                <div>
                                    <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Nama Produk / Proses / Sistem / Alat</span>
                                    <strong>{{ changeRequest.qa_verification_data.qa_2.nama_produk || '-' }}</strong>
                                </div>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Persetujuan Badan POM</span>
                                <p style="color: var(--text-primary); margin-top: 4px;">
                                    <span v-if="changeRequest.qa_verification_data.qa_2.pom_status === 'option1'">Perubahan akan didokumentasikan bersama dengan pembaruan dokumen yang bersangkutan oleh Departemen R&D dan Departemen lain secara berkala.</span>
                                    <span v-else-if="changeRequest.qa_verification_data.qa_2.pom_status === 'option2'">Tidak diperlukan pemberitahuan perubahan.</span>
                                    <span v-else-if="changeRequest.qa_verification_data.qa_2.pom_status === 'option3'">
                                        Perubahan memerlukan persetujuan Badan POM terlebih dahulu.<br/>
                                        - Dilaporkan oleh: {{ changeRequest.qa_verification_data.qa_2.pom_pemberitahuan_dari || '-' }}<br/>
                                        - Rencana disetujui tanggal: {{ changeRequest.qa_verification_data.qa_2.pom_rencana_disetujui_tanggal || '-' }}
                                    </span>
                                    <span v-else>-</span>
                                </p>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; margin-bottom: 8px;">Rencana Dokumen Direvisi</span>
                                <div class="table-wrapper" style="overflow-x: auto;">
                                    <table class="qms-table" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Jenis Dokumen</th>
                                                <th>No Dokumen</th>
                                                <th>Tanggal Berlaku</th>
                                                <th>PIC</th>
                                                <th>Timeline</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="!changeRequest.qa_verification_data.qa_2.documents || changeRequest.qa_verification_data.qa_2.documents.length === 0">
                                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 12px;">Tidak ada rencana dokumen yang ditambahkan.</td>
                                            </tr>
                                            <tr v-for="(doc, idx) in changeRequest.qa_verification_data.qa_2.documents" :key="idx">
                                                <td>{{ doc.jenis }}</td>
                                                <td>{{ doc.no_dokumen }}</td>
                                                <td>{{ doc.tanggal_berlaku }}</td>
                                                <td>{{ doc.pic }}</td>
                                                <td>{{ doc.timeline }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Read-only Tab 3 -->
                        <div v-show="activeQaTab === 'qa_3'" style="display: flex; flex-direction: column; gap: 16px; font-size: 0.9rem;">
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Pengendalian Perubahan No</span>
                                <strong>{{ changeRequest.qa_verification_data.qa_3.no_pengendalian || '-' }}</strong>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; margin-bottom: 8px;">Kondisi Perubahan yang Dilakukan</span>
                                <div class="table-wrapper" style="overflow-x: auto;">
                                    <table class="qms-table" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>PIC</th>
                                                <th>Perubahan yang Dilakukan</th>
                                                <th>Tanggal Dilakukan</th>
                                                <th>Bukti Dokumen</th>
                                                <th>Tanggal Berlaku</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="!changeRequest.qa_verification_data.qa_3.implementations || changeRequest.qa_verification_data.qa_3.implementations.length === 0">
                                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 12px;">Tidak ada kondisi perubahan yang ditambahkan.</td>
                                            </tr>
                                            <tr v-for="(imp, idx) in changeRequest.qa_verification_data.qa_3.implementations" :key="idx">
                                                <td>{{ imp.pic }}</td>
                                                <td>{{ imp.perubahan }}</td>
                                                <td>{{ imp.tanggal_dilakukan }}</td>
                                                <td>
                                                    <span v-if="imp.bukti_dokumen_path">
                                                        <a :href="'/storage/' + imp.bukti_dokumen_path" target="_blank" style="text-decoration: underline; color: var(--accent-color);">Unduh Berkas</a>
                                                    </span>
                                                    <span v-else>-</span>
                                                </td>
                                                <td>{{ imp.tanggal_berlaku }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div style="border-top: 1px solid var(--border-color); padding-top: 12px;">
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Verifikasi Akhir</span>
                                <strong>{{ changeRequest.qa_verification_data.qa_3.verifikasi_completed ? 'Implementasi perubahan sudah dilakukan ✓' : 'Implementasi perubahan belum dilakukan ✗' }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Evaluation Details (Backward-compatible fallback if filled) -->
                    <div class="qms-card" v-else-if="changeRequest.rencana_tindakan">
                        <h3 style="font-size: 1.15rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px; color: var(--accent-color);">
                            📋 Rencana Evaluasi & Penugasan QA
                        </h3>
                        <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.925rem;">
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Rencana Tindakan QA</span>
                                <p style="color: var(--text-primary); margin-top: 4px; font-weight: 500;">{{ changeRequest.rencana_tindakan }}</p>
                            </div>
                            <div class="grid-2">
                                <div>
                                    <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Person In Charge (PIC)</span>
                                    <span style="color: var(--text-primary); font-weight: 600;">{{ changeRequest.pic ? changeRequest.pic.name : '-' }}</span>
                                </div>
                                <div>
                                    <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Timeline Penyelesaian</span>
                                    <span style="color: var(--text-primary); font-weight: 600;">{{ changeRequest.timeline }}</span>
                                </div>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Hasil Verifikasi QA</span>
                                <p style="color: var(--text-primary); margin-top: 4px; font-style: italic;">{{ changeRequest.hasil_verifikasi }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QA ACTIONS PANEL (Right Column) -->
                <div>
                    <!-- Editable form: QA & superadmin only -->
                    <div v-if="isQa || isSuperAdmin" class="qms-card" style="border-top: 4px solid var(--accent-color); position: sticky; top: 20px;">
                        <h3 style="font-size: 1.15rem; margin-bottom: 16px; color: var(--text-primary);">🛠️ Evaluasi QA</h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="qa_status" class="form-label">Keputusan Status</label>
                                <select id="qa_status" v-model="qaForm.status" class="form-select" required>
                                    <option value="IN REVIEW">IN REVIEW (Tinjau)</option>
                                    <option value="APPROVED">APPROVED (Setujui)</option>
                                    <option value="IN PROGRESS">IN PROGRESS (Tindakan)</option>
                                    <option value="COMPLETE">COMPLETE (Selesai)</option>
                                    <option value="REJECT">REJECT (Tolak)</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="rencana_tindakan" class="form-label">Rencana Tindakan</label>
                                <textarea id="rencana_tindakan" v-model="qaForm.rencana_tindakan" class="form-textarea" rows="3" required placeholder="Tuliskan rencana tindakan nyata..."></textarea>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="pic_id" class="form-label">Tetapkan PIC</label>
                                <select id="pic_id" v-model="qaForm.pic_id" class="form-select" required>
                                    <option value="">Pilih User PIC...</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.role }})
                                    </option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="timeline" class="form-label">Timeline Batas Waktu</label>
                                <input id="timeline" type="date" v-model="qaForm.timeline" class="form-input" required />
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="hasil_verifikasi" class="form-label">Hasil Verifikasi Akhir</label>
                                <textarea id="hasil_verifikasi" v-model="qaForm.hasil_verifikasi" class="form-textarea" rows="2" required placeholder="Catatan atau resume hasil verifikasi..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;" :disabled="qaForm.processing">
                                {{ qaForm.processing ? 'Menyimpan...' : '💾 Simpan Evaluasi' }}
                            </button>
                        </div>
                    </div>

                    <!-- Read-only info panel: HO, OM, GM -->
                    <div v-else-if="isHu || isOm || isGm" class="qms-card" style="border-top: 4px solid var(--border-color); position: sticky; top: 20px;">
                        <h3 style="font-size: 1.1rem; margin-bottom: 16px; color: var(--text-primary);">📋 Info Evaluasi QA</h3>
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 4px;">Status CR</span>
                                <span class="status-badge" :class="getStatusClass(changeRequest.status)">{{ changeRequest.status }}</span>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 4px;">Rencana Tindakan</span>
                                <p style="color: var(--text-primary); font-size: 0.875rem; line-height: 1.5; margin: 0; white-space: pre-line;">{{ changeRequest.rencana_tindakan || '-' }}</p>
                            </div>
                            <div class="grid-2" style="gap: 12px;">
                                <div>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 4px;">PIC</span>
                                    <span style="color: var(--text-primary); font-weight: 600; font-size: 0.875rem;">{{ changeRequest.pic ? changeRequest.pic.name : '-' }}</span>
                                </div>
                                <div>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 4px;">Timeline</span>
                                    <span style="color: var(--text-primary); font-weight: 600; font-size: 0.875rem;">{{ changeRequest.timeline || '-' }}</span>
                                </div>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 4px;">Hasil Verifikasi QA</span>
                                <p style="color: var(--text-primary); font-size: 0.875rem; line-height: 1.5; margin: 0; font-style: italic;">{{ changeRequest.hasil_verifikasi || '-' }}</p>
                            </div>

                            <!-- Approval status & save button (shown only if QA has submitted) -->
                            <div v-if="qaForm.qa_verification_data.qa_1.submitted" style="border-top: 1px solid var(--border-color); padding-top: 14px; display: flex; flex-direction: column; gap: 12px;">
                                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; display: block;">Persetujuan Anda</span>

                                <!-- HU approval status -->
                                <div v-if="isHu" style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">Head of Quality</span>
                                    <span class="status-badge" :class="qaForm.qa_verification_data.qa_1.hu_approved === 'APPROVED' ? 'badge-approved' : (qaForm.qa_verification_data.qa_1.hu_approved === 'REJECTED' ? 'badge-reject' : 'badge-open')">
                                        {{ qaForm.qa_verification_data.qa_1.hu_approved === 'APPROVED' ? '✓ Disetujui' : (qaForm.qa_verification_data.qa_1.hu_approved === 'REJECTED' ? '✗ Ditolak' : '⏳ Menunggu') }}
                                    </span>
                                </div>

                                <!-- OM approval status -->
                                <div v-if="isOm" style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">Operational Manager</span>
                                    <span class="status-badge" :class="qaForm.qa_verification_data.qa_1.om_approved === 'APPROVED' ? 'badge-approved' : (qaForm.qa_verification_data.qa_1.om_approved === 'REJECTED' ? 'badge-reject' : 'badge-open')">
                                        {{ qaForm.qa_verification_data.qa_1.om_approved === 'APPROVED' ? '✓ Disetujui' : (qaForm.qa_verification_data.qa_1.om_approved === 'REJECTED' ? '✗ Ditolak' : '⏳ Menunggu') }}
                                    </span>
                                </div>

                                <!-- GM approval status -->
                                <div v-if="isGm" style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px;">
                                    <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary);">General Manager</span>
                                    <span class="status-badge" :class="qaForm.qa_verification_data.qa_1.gm_approved === 'APPROVED' ? 'badge-approved' : (qaForm.qa_verification_data.qa_1.gm_approved === 'REJECTED' ? 'badge-reject' : 'badge-open')">
                                        {{ qaForm.qa_verification_data.qa_1.gm_approved === 'APPROVED' ? '✓ Disetujui' : (qaForm.qa_verification_data.qa_1.gm_approved === 'REJECTED' ? '✗ Ditolak' : '⏳ Menunggu') }}
                                    </span>
                                </div>

                                <button type="button" @click="submitManagementApproval" class="btn btn-primary" style="width: 100%; margin-top: 4px; font-weight: 700;" :disabled="qaForm.processing">
                                    {{ qaForm.processing ? 'Menyimpan...' : '✅ Simpan Persetujuan' }}
                                </button>
                            </div>

                            <!-- Not yet submitted notice -->
                            <div v-else style="border-top: 1px solid var(--border-color); padding-top: 14px;">
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0; font-style: italic;">
                                    ⏳ QA belum mengajukan persetujuan ke manajemen.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Information sidebar for initiators -->
                    <div v-else class="qms-card" style="position: sticky; top: 20px;">
                        <h3 style="font-size: 1.1rem; margin-bottom: 12px;">ℹ️ Informasi</h3>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">
                            Usulan perubahan (Change Request) yang Anda kirim akan ditinjau oleh Quality Assurance. QA memiliki otoritas penuh untuk menyetujui, menolak, menetapkan PIC, dan menambahkan rencana tindakan kerja pada usulan ini.
                        </p>
                    </div>
                </div>
                </div>
            </form>
            </div>
        </div>

        <AttachmentViewer 
            :show="showAttachment" 
            :file-path="changeRequest.attachment_path" 
            :title="changeRequest.attachment_description || 'Lampiran Usulan Perubahan (CR)'" 
            @close="showAttachment = false" 
        />
    </AuthenticatedLayout>
</template>
