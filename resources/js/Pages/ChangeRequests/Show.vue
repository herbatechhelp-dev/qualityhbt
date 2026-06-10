<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AttachmentViewer from '@/Components/AttachmentViewer.vue';

const showAttachment = ref(false);

const page = usePage();
const currentUser = page.props.auth.user;
// QA or superadmin can evaluate
const canEvaluate = computed(() => currentUser.role === 'qa' || currentUser.role === 'superadmin');

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

// Draft edits form (only if status === 'DRAFT')
const editForm = useForm({
    type: props.changeRequest.type,
    sifat_perubahan: props.changeRequest.sifat_perubahan,
    department: props.changeRequest.department,
    risk_identification: props.changeRequest.risk_identification || '',
    potential_cause: props.changeRequest.potential_cause || '',
    severity: props.changeRequest.severity || 1,
    occurrence: props.changeRequest.occurrence || 1,
    detection: props.changeRequest.detection || 1,
    risk_control: props.changeRequest.risk_control || '',
    action: props.changeRequest.action || '',
    attachment: null,
    attachment_description: props.changeRequest.attachment_description || '',
    submit_type: 'submit',
});

// QA evaluation form
const qaForm = useForm({
    rencana_tindakan: props.changeRequest.rencana_tindakan || '',
    pic_id: props.changeRequest.pic_id || '',
    timeline: props.changeRequest.timeline || '',
    hasil_verifikasi: props.changeRequest.hasil_verifikasi || '',
    status: props.changeRequest.status === 'DRAFT' || props.changeRequest.status === 'OPEN' ? 'IN REVIEW' : props.changeRequest.status,
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
                    <div class="form-group">
                        <label class="form-label">Sifat Perubahan</label>
                        <select v-model="editForm.sifat_perubahan" class="form-select">
                            <option value="Permanen">Permanen</option>
                            <option value="Sementara">Sementara</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Departemen</label>
                        <input type="text" v-model="editForm.department" class="form-input" />
                    </div>
                </div>

                <!-- CRA specific edit fields -->
                <div v-if="editForm.type === 'CRA'" style="background-color: var(--bg-primary); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 24px;">
                    <h4 style="font-weight: bold; color: var(--accent-color); margin-bottom: 12px;">Analisis Risiko FMEA</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Identifikasi Risiko</label>
                        <textarea v-model="editForm.risk_identification" class="form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Potential Cause</label>
                        <textarea v-model="editForm.potential_cause" class="form-textarea" rows="2"></textarea>
                    </div>

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

                    <div class="form-group">
                        <label class="form-label">Risk Control</label>
                        <textarea v-model="editForm.risk_control" class="form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Mitigation Action</label>
                        <textarea v-model="editForm.action" class="form-textarea" rows="2"></textarea>
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
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase;">Sifat Perubahan</span>
                                <span style="font-weight: 600; color: var(--text-primary);">{{ changeRequest.sifat_perubahan }}</span>
                            </div>
                        </div>

                        <!-- FMEA Details (CRA) -->
                        <div v-if="changeRequest.type === 'CRA'" style="background-color: var(--bg-primary); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 16px; margin-top: 20px;">
                            <h4 style="font-weight: 700; color: var(--accent-color);">⚠️ Uraian Analisis Risiko (CRA)</h4>
                            
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Identifikasi Risiko</span>
                                <p style="color: var(--text-primary); margin-top: 4px; line-height: 1.5;">{{ changeRequest.risk_identification }}</p>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Penyebab Potensial</span>
                                <p style="color: var(--text-primary); margin-top: 4px;">{{ changeRequest.potential_cause }}</p>
                            </div>

                            <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; border: 1px dashed var(--border-color); padding: 12px 16px; border-radius: 8px; background-color: var(--bg-secondary);">
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

                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Kontrol Terpasang</span>
                                <p style="color: var(--text-primary); margin-top: 4px;">{{ changeRequest.risk_control }}</p>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block;">Tindakan Mitigasi</span>
                                <p style="color: var(--text-primary); margin-top: 4px;">{{ changeRequest.action }}</p>
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
                                    <button @click="showAttachment = true" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem; font-weight: 600;">
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

                    <!-- Evaluation Details (If Filled) -->
                    <div class="qms-card" v-if="changeRequest.rencana_tindakan">
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
                    <div v-if="canEvaluate" class="qms-card" style="border-top: 4px solid var(--accent-color); position: sticky; top: 20px;">
                        <h3 style="font-size: 1.15rem; margin-bottom: 16px; color: var(--text-primary);">🛠️ Evaluasi QA</h3>
                        
                        <form @submit.prevent="submitQaEvaluation" style="display: flex; flex-direction: column; gap: 16px;">
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
                        </form>
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
