<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AttachmentViewer from '@/Components/AttachmentViewer.vue';

const showAttachment = ref(false);

const page = usePage();
const currentUser = page.props.auth.user;
// QA dan superadmin bisa memberikan keputusan
const canEvaluate = computed(() => currentUser.role === 'qa' || currentUser.role === 'superadmin');

const props = defineProps({
    deviation: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    action: 'APPROVED', // APPROVED or REJECTED
    reject_reason: '',
});

const submitDecision = () => {
    if (form.action === 'REJECTED' && !form.reject_reason.trim()) {
        window.dispatchEvent(new CustomEvent('qms-notification', {
            detail: {
                type: 'error',
                title: 'Validasi Gagal',
                message: 'Alasan Reject wajib diisi jika Anda menolak deviasi ini.'
            }
        }));
        return;
    }
    
    const confirmMsg = form.action === 'APPROVED' 
        ? 'Menyetujui deviasi ini akan secara otomatis membuat lembar baru di Modul CAPA. Lanjutkan?'
        : 'Anda yakin ingin menolak deviasi ini?';
        
    if (confirm(confirmMsg)) {
        form.post(route('deviations.decide', props.deviation.id));
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'OPEN': return 'badge-open';
        case 'IN REVIEW': return 'badge-in_review';
        case 'APPROVED': return 'badge-approved';
        case 'REJECTED': return 'badge-reject';
        default: return 'badge-draft';
    }
};

const editForm = useForm({
    description: props.deviation.description || '',
    department: props.deviation.department || '',
    attachment: null,
    attachment_description: props.deviation.attachment_description || '',
});

const isEditingDraft = ref(false);

const toggleEditDraft = () => {
    isEditingDraft.value = !isEditingDraft.value;
};

const handleAttachmentChange = (e) => {
    editForm.attachment = e.target.files[0];
};

const submitDeviationUpdate = () => {
    editForm.post(route('deviations.update', props.deviation.id), {
        onSuccess: () => {
            isEditingDraft.value = false;
        }
    });
};
</script>

<template>
    <Head :title="'Detail Deviasi: ' + deviation.deviation_number" />

    <AuthenticatedLayout>
        <template #header>
            🔍 Detail Deviation Report: {{ deviation.deviation_number }}
        </template>

        <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
            <div class="flex-between">
                <Link :href="route('deviations.index')" class="btn btn-secondary">
                    ← Kembali ke Logbook
                </Link>
                <div v-if="deviation.status === 'REJECTED' && $page.props.auth.user.id === deviation.initiator_id">
                    <button @click="toggleEditDraft" class="btn btn-primary">
                        {{ isEditingDraft ? 'Batal Edit' : '✍️ Edit & Revisi' }}
                    </button>
                </div>
            </div>

            <!-- REVISION EDIT MODE -->
            <div v-if="isEditingDraft" class="qms-card fade-in">
                <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px;">
                    Revisi Laporan Deviasi
                </h3>

                <form @submit.prevent="submitDeviationUpdate">
                    <div class="form-group">
                        <label class="form-label">Departemen</label>
                        <input type="text" v-model="editForm.department" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi Deviasi</label>
                        <textarea v-model="editForm.description" class="form-textarea" rows="5" required placeholder="Tuliskan deskripsi deviasi secara rinci..."></textarea>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">File Lampiran Baru (Opsional)</label>
                            <input type="file" @change="handleAttachmentChange" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Keterangan Lampiran</label>
                            <input type="text" v-model="editForm.attachment_description" class="form-input" />
                        </div>
                    </div>

                    <div class="flex-between" style="margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                        <span></span>
                        <button type="submit" class="btn btn-primary" :disabled="editForm.processing">
                            🚀 Kirim & Ajukan Kembali
                        </button>
                    </div>
                </form>
            </div>

            <!-- VIEW MODE -->
            <div v-else style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Rejection Alert Banner -->
                <div v-if="deviation.status === 'REJECTED'" style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: start; gap: 12px;">
                    <span style="font-size: 1.4rem;">❌</span>
                    <div style="flex-grow: 1;">
                        <div style="font-weight: 600; color: #ef4444; margin-bottom: 4px;">Laporan Deviasi Ditolak oleh QA</div>
                        <div style="font-size: 0.875rem; color: var(--text-primary); margin-bottom: 8px;">Silakan tinjau alasan penolakan di bawah, lakukan revisi, lalu kirim kembali laporan ini.</div>
                        <div style="font-size: 0.8rem; background: var(--bg-secondary); border-radius: 6px; padding: 8px 12px; border-left: 3px solid #ef4444; color: var(--text-muted); font-style: italic;">
                            Alasan Penolakan QA: "{{ deviation.reject_reason || '-' }}"
                        </div>
                    </div>
                </div>

                <div class="qms-show-grid">
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <!-- Details Card -->
                    <div class="qms-card">
                        <div class="flex-between" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px;">
                            <div>
                                <span class="status-badge" :class="getStatusClass(deviation.status)">
                                    {{ deviation.status }}
                                </span>
                            </div>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">
                                Dilaporkan pada: {{ new Date(deviation.created_at).toLocaleDateString('id-ID') }}
                            </span>
                        </div>

                        <div class="grid-2" style="margin-bottom: 24px;">
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase;">Inisiator Pelapor</span>
                                <span style="font-weight: 600; color: var(--text-primary);">{{ deviation.initiator.name }}</span>
                            </div>
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase;">Departemen</span>
                                <span style="font-weight: 600; color: var(--text-primary);">{{ deviation.department }}</span>
                            </div>
                        </div>

                        <div>
                            <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase; margin-bottom: 6px;">Deskripsi Deviasi Terkait</span>
                            <div style="background-color: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; line-height: 1.6; color: var(--text-primary); white-space: pre-wrap;">
                                {{ deviation.description }}
                            </div>
                        </div>

                        <!-- Attachment -->
                        <div style="margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                            <h4 style="font-size: 0.95rem; margin-bottom: 10px; font-weight: bold;">📎 Lampiran Bukti</h4>
                            <div v-if="deviation.attachment_path" style="background-color: var(--bg-tertiary); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span style="font-size: 0.875rem; font-weight: 600;">
                                        {{ deviation.attachment_description || 'Berkas Bukti' }}
                                    </span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); word-break: break-all;">
                                        {{ deviation.attachment_path }}
                                    </span>
                                </div>
                                <button @click="showAttachment = true" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem; font-weight: 600;">
                                    👁️ Lihat Lampiran
                                </button>
                            </div>
                            <div v-else style="color: var(--text-muted); font-size: 0.9rem;">
                                Tidak ada bukti dilampirkan.
                            </div>
                        </div>
                    </div>

                    <!-- Reject Reason Card -->
                    <div v-if="deviation.status === 'REJECTED'" class="qms-card" style="border-left: 4px solid #ef4444; background-color: var(--status-reject-bg);">
                        <h4 style="font-weight: 700; color: var(--status-reject-text); margin-bottom: 8px;">❌ Alasan Penolakan QA</h4>
                        <p style="color: var(--status-reject-text); font-size: 0.95rem; line-height: 1.5;">{{ deviation.reject_reason }}</p>
                    </div>

                    <!-- Auto generated CAPA Info Card -->
                    <div v-if="deviation.status === 'APPROVED' && deviation.capa" class="qms-card" style="border-left: 4px solid #10b981;">
                        <h4 style="font-weight: 700; color: #065f46; margin-bottom: 8px;">✅ Lembar CAPA Terkait Telah Terbit</h4>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 16px;">
                            Sistem secara otomatis membuka lembar CAPA untuk tindakan perbaikan setelah deviasi ini disetujui.
                        </p>
                        <Link :href="route('capas.show', deviation.capa.id)" class="btn btn-success" style="padding: 8px 16px; font-size: 0.85rem;">
                            👁️ Lihat Lembar Tindakan CAPA
                        </Link>
                    </div>
                </div>

                <!-- QA DECISION CARD PANEL (Right Column) -->
                <div>
                    <div v-if="canEvaluate && deviation.status === 'OPEN'" class="qms-card" style="border-top: 4px solid var(--accent-color); position: sticky; top: 20px;">
                        <h3 style="font-size: 1.15rem; margin-bottom: 16px;">🛠️ Keputusan QA</h3>
                        
                        <form @submit.prevent="submitDecision" style="display: flex; flex-direction: column; gap: 16px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Tindakan Keputusan</label>
                                <div class="btn-toggle-group">
                                    <button 
                                        type="button" 
                                        @click="form.action = 'APPROVED'" 
                                        class="btn-toggle" 
                                        :class="{ active: form.action === 'APPROVED' }"
                                        style="font-size: 0.8rem; font-weight: 600;"
                                    >
                                        Approve & CAPA
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="form.action = 'REJECTED'" 
                                        class="btn-toggle" 
                                        :class="{ active: form.action === 'REJECTED' }"
                                        style="font-size: 0.8rem; font-weight: 600;"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </div>

                            <div v-if="form.action === 'REJECTED'" class="form-group fade-in" style="margin-bottom: 0;">
                                <label for="reject_reason" class="form-label">Alasan Reject (Wajib)</label>
                                <textarea 
                                    id="reject_reason" 
                                    v-model="form.reject_reason" 
                                    class="form-textarea" 
                                    rows="4" 
                                    required 
                                    placeholder="Jelaskan alasan penolakan atau revisi yang harus dilakukan inisiator..."
                                ></textarea>
                                <div v-if="form.errors.reject_reason" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                                    {{ form.errors.reject_reason }}
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan...' : 'Kirim Keputusan' }}
                            </button>
                        </form>
                    </div>

                    <!-- Information sidebar for initiators -->
                    <div v-else class="qms-card" style="position: sticky; top: 20px;">
                        <h3 style="font-size: 1.1rem; margin-bottom: 12px;">ℹ️ Otoritas</h3>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.6;">
                            Ketika laporan deviasi disetujui (Approved) oleh QA, sistem akan otomatis membuat draf lembar CAPA baru untuk tindak lanjut penyelesaian. Jika ditolak (Rejected), Anda dapat merevisi berkas di bawah bimbingan QA.
                        </p>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <AttachmentViewer 
            :show="showAttachment" 
            :file-path="deviation.attachment_path" 
            :title="deviation.attachment_description || 'Lampiran Bukti Deviasi'" 
            @close="showAttachment = false" 
        />
    </AuthenticatedLayout>
</template>
