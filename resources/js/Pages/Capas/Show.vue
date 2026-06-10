<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AttachmentViewer from '@/Components/AttachmentViewer.vue';

const showAttachment = ref(false);

const page = usePage();
const currentUser = page.props.auth.user;
// QA dan superadmin bisa verifikasi dan tutup CAPA
const canEvaluate = computed(() => currentUser.role === 'qa' || currentUser.role === 'superadmin');
// PIC, Initiator, QA, Superadmin bisa upload bukti
const canUploadProof = (capa) => {
    return canEvaluate.value
        || capa.initiator_id === currentUser.id
        || capa.pic_id === currentUser.id;
};

const props = defineProps({
    capa: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
});

// Form 1: Completing CAPA details (when DRAFT)
const detailForm = useForm({
    tindakan_capa: props.capa.tindakan_capa || '',
    pic_id: props.capa.pic_id || '',
    tanggal_mulai: props.capa.tanggal_mulai || '',
    tanggal_selesai: props.capa.tanggal_selesai || '',
    submit_type: 'submit', // draft or submit
});

// Form 2: Uploading Field Proof (when IN PROGRESS)
const proofForm = useForm({
    bukti_lapangan: null,
});

// Form 3: QA final verification (when APPROVED)
const verifyForm = useForm({
    action: 'CLOSE', // CLOSE or REJECTED
    hasil_verifikasi_qa: props.capa.hasil_verifikasi_qa || '',
});

const isEditingDraft = ref(props.capa.status === 'DRAFT');

const handleFileChange = (e) => {
    proofForm.bukti_lapangan = e.target.files[0];
};

const submitDetails = (submitType) => {
    detailForm.submit_type = submitType;
    
    // Front-end date validation
    if (detailForm.tanggal_mulai && detailForm.tanggal_selesai) {
        const start = new Date(detailForm.tanggal_mulai);
        const end = new Date(detailForm.tanggal_selesai);
        if (end < start) {
            window.dispatchEvent(new CustomEvent('qms-notification', {
                detail: {
                    type: 'error',
                    title: 'Validasi Gagal',
                    message: 'Tanggal Selesai tidak boleh lebih lampau dari Tanggal Mulai.'
                }
            }));
            return;
        }
    }
    
    detailForm.post(route('capas.update', props.capa.id), {
        onSuccess: () => {
            if (submitType === 'submit') {
                isEditingDraft.value = false;
            }
        }
    });
};

const submitProof = () => {
    if (!proofForm.bukti_lapangan) {
        window.dispatchEvent(new CustomEvent('qms-notification', {
            detail: {
                type: 'error',
                title: 'Pilih File',
                message: 'Silakan pilih berkas bukti terlebih dahulu.'
            }
        }));
        return;
    }
    proofForm.post(route('capas.upload-proof', props.capa.id));
};

const submitVerification = () => {
    if (!verifyForm.hasil_verifikasi_qa.trim()) {
        window.dispatchEvent(new CustomEvent('qms-notification', {
            detail: {
                type: 'error',
                title: 'Input Wajib',
                message: 'Hasil verifikasi QA wajib diisi.'
            }
        }));
        return;
    }
    verifyForm.post(route('capas.verify', props.capa.id));
};

const getStatusClass = (status) => {
    switch (status) {
        case 'DRAFT': return 'badge-draft';
        case 'IN PROGRESS': return 'badge-in_progress';
        case 'APPROVED': return 'badge-approved';
        case 'CLOSE': return 'badge-complete';
        default: return 'badge-draft';
    }
};
</script>

<template>
    <Head :title="'Detail CAPA: ' + capa.capa_number" />

    <AuthenticatedLayout>
        <template #header>
            🔍 Detail CAPA: {{ capa.capa_number }}
        </template>

        <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
            <div class="flex-between">
                <Link :href="route('capas.index')" class="btn btn-secondary">
                    ← Kembali ke Monitoring
                </Link>
                <div>
                    <span class="status-badge" :class="getStatusClass(capa.status)">
                        {{ capa.status }}
                    </span>
                </div>
            </div>

            <!-- Rejection Alert Banner -->
            <div v-if="capa.status === 'IN PROGRESS' && capa.hasil_verifikasi_qa" style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: start; gap: 12px;">
                <span style="font-size: 1.4rem;">❌</span>
                <div style="flex-grow: 1;">
                    <div style="font-weight: 600; color: #ef4444; margin-bottom: 4px;">Bukti Lapangan Ditolak oleh QA</div>
                    <div style="font-size: 0.875rem; color: var(--text-primary); margin-bottom: 8px;">Silakan tinjau catatan QA di bawah, perbaiki bukti pelaksanaan lapangan, lalu unggah kembali.</div>
                    <div style="font-size: 0.8rem; background: var(--bg-secondary); border-radius: 6px; padding: 8px 12px; border-left: 3px solid #ef4444; color: var(--text-muted); font-style: italic;">
                        Catatan Penolakan QA: "{{ capa.hasil_verifikasi_qa }}"
                    </div>
                </div>
            </div>

            <!-- Auto-generated reference header card -->
            <div class="qms-card" style="background-color: var(--bg-tertiary); border: 1px solid var(--border-color);">
                <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--accent-color);">🔗 Rujukan Dokumen Asal (Deviasi)</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 0.875rem;">
                    <div>No Sumber Deviasi: 
                        <Link :href="route('deviations.show', capa.deviation_id)" style="font-weight: 700; color: var(--accent-color); font-family: monospace;">
                            {{ capa.deviation_number_ref }}
                        </Link>
                    </div>
                    <div>Nomor Tindakan CAPA (Sub): <strong style="font-family: monospace;">{{ capa.sub_capa_number }}</strong></div>
                    <div>Tanggal Penyimpangan: <strong>{{ capa.tanggal_penyimpangan }}</strong></div>
                    <div>Tipe CAPA: <strong>{{ capa.type_capa }}</strong></div>
                </div>
            </div>

            <div class="qms-show-grid">
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    
                    <!-- 1. DRAFT STATE: EDIT FORM -->
                    <div v-if="capa.status === 'DRAFT'" class="qms-card">
                        <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px;">
                            ✍️ Lengkapi Lembar Rencana Tindakan CAPA
                        </h3>

                        <form @submit.prevent>
                            <div class="form-group">
                                <label for="tindakan_capa" class="form-label">Rincian Tindakan CAPA</label>
                                <textarea 
                                    id="tindakan_capa" 
                                    v-model="detailForm.tindakan_capa" 
                                    class="form-textarea" 
                                    rows="4" 
                                    placeholder="Tuliskan detail rencana tindakan koreksi dan pencegahan secara sistemik..."
                                    required
                                ></textarea>
                                <div v-if="detailForm.errors.tindakan_capa" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                                    {{ detailForm.errors.tindakan_capa }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="pic_id" class="form-label">Pelaksana Tindakan (PIC)</label>
                                <select id="pic_id" v-model="detailForm.pic_id" class="form-select" required>
                                    <option value="">Pilih User PIC...</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.role }})
                                    </option>
                                </select>
                                <div v-if="detailForm.errors.pic_id" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                                    {{ detailForm.errors.pic_id }}
                                </div>
                            </div>

                            <div class="grid-2">
                                <div class="form-group">
                                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                    <input id="tanggal_mulai" type="date" v-model="detailForm.tanggal_mulai" class="form-input" required />
                                    <div v-if="detailForm.errors.tanggal_mulai" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                                        {{ detailForm.errors.tanggal_mulai }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="tanggal_selesai" class="form-label">Tanggal Selesai (Deadline)</label>
                                    <input id="tanggal_selesai" type="date" v-model="detailForm.tanggal_selesai" class="form-input" required />
                                    <div v-if="detailForm.errors.tanggal_selesai" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                                        {{ detailForm.errors.tanggal_selesai }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex-between" style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 24px;">
                                <button type="button" @click="submitDetails('draft')" class="btn btn-secondary" :disabled="detailForm.processing">
                                    📁 Simpan Draf
                                </button>
                                <button type="button" @click="submitDetails('submit')" class="btn btn-primary" :disabled="detailForm.processing">
                                    🚀 Kirim & Jalankan Tindakan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- 2. OTHER STATES: DISPLAY FILLED DETAILS -->
                    <div v-else class="qms-card">
                        <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px; color: var(--text-primary);">
                            📝 Rincian Tindakan CAPA
                        </h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 20px; font-size: 0.95rem;">
                            <div>
                                <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase;">Uraian Tindakan CAPA</span>
                                <p style="color: var(--text-primary); margin-top: 4px; line-height: 1.6; font-weight: 500;">{{ capa.tindakan_capa }}</p>
                            </div>

                            <div class="grid-2">
                                <div>
                                    <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase;">PIC Pelaksana</span>
                                    <span style="color: var(--text-primary); font-weight: 600;">{{ capa.pic ? capa.pic.name : '-' }}</span>
                                </div>
                                <div>
                                    <span style="font-size: 0.775rem; color: var(--text-muted); display: block; text-transform: uppercase;">Target Pelaksanaan</span>
                                    <span style="color: var(--text-primary); font-weight: 600;">{{ capa.tanggal_mulai }} s/d {{ capa.tanggal_selesai }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Proof / Display Proof Section -->
                        <div style="border-top: 1px solid var(--border-color); margin-top: 24px; padding-top: 20px;">
                            <h4 style="font-size: 1rem; margin-bottom: 12px; font-weight: bold; color: var(--text-primary);">📎 Bukti Pelaksanaan Lapangan</h4>
                            
                            <!-- If IN PROGRESS: File Input Upload Form -->
                            <div v-if="capa.status === 'IN PROGRESS'">
                                <form @submit.prevent="submitProof" style="display: flex; flex-direction: column; gap: 12px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="bukti_lapangan" class="form-label" style="font-size: 0.8rem;">Unggah File Bukti (PDF, Gambar, Dokumentasi)</label>
                                        <input 
                                            id="bukti_lapangan" 
                                            type="file" 
                                            @change="handleFileChange" 
                                            class="form-input" 
                                            style="padding: 6px 12px; font-size: 0.85rem;" 
                                            required
                                        />
                                        <div v-if="proofForm.errors.bukti_lapangan" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                                            {{ proofForm.errors.bukti_lapangan }}
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width: fit-content; padding: 10px 18px;" :disabled="proofForm.processing">
                                        📤 Upload & Ajukan Verifikasi
                                    </button>
                                </form>
                            </div>

                            <!-- If Proof Uploaded already -->
                            <div v-else-if="capa.bukti_lapangan_path" style="background-color: var(--bg-tertiary); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span style="font-size: 0.875rem; font-weight: 600;">Berkas Bukti Lapangan</span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); word-break: break-all;">
                                        {{ capa.bukti_lapangan_path }}
                                    </span>
                                </div>
                                <button @click="showAttachment = true" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem; font-weight: 600;">
                                    👁️ Lihat Bukti
                                </button>
                            </div>

                            <!-- Else (None) -->
                            <div v-else style="color: var(--text-muted); font-size: 0.9rem;">
                                Belum ada bukti pelaksanaan lapangan yang diunggah.
                            </div>
                        </div>
                    </div>

                    <!-- Final Verification details (if CLOSED) -->
                    <div v-if="capa.status === 'CLOSE'" class="qms-card">
                        <h4 style="font-weight: 700; color: var(--accent-color); margin-bottom: 12px;">📋 Verifikasi Akhir QA</h4>
                        <div class="table-wrapper" style="margin-top: 12px;">
                            <table class="qms-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal Verifikasi</th>
                                        <th>Hasil Verifikasi / Resume QA</th>
                                        <th>Status CAPA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>{{ new Date(capa.updated_at).toLocaleDateString('id-ID') }}</td>
                                        <td style="font-style: italic;">"{{ capa.hasil_verifikasi_qa }}"</td>
                                        <td>
                                            <span class="status-badge badge-complete">CLOSE</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RIGHT PANEL: QA VERIFICATION ACTIONS OR GENERAL INFO -->
                <div>
                    <!-- QA Verification Panel (when APPROVED) -->
                    <div v-if="canEvaluate && capa.status === 'APPROVED'" class="qms-card" style="border-top: 4px solid var(--accent-color); position: sticky; top: 20px;">
                        <h3 style="font-size: 1.15rem; margin-bottom: 16px;">🔍 Verifikasi Penutupan CAPA</h3>
                        
                        <form @submit.prevent="submitVerification" style="display: flex; flex-direction: column; gap: 16px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Tindakan Verifikasi</label>
                                <div class="btn-toggle-group">
                                    <button 
                                        type="button" 
                                        @click="verifyForm.action = 'CLOSE'" 
                                        class="btn-toggle" 
                                        :class="{ active: verifyForm.action === 'CLOSE' }"
                                        style="font-size: 0.8rem; font-weight: 600;"
                                    >
                                        Sahkan & Tutup
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="verifyForm.action = 'REJECTED'" 
                                        class="btn-toggle" 
                                        :class="{ active: verifyForm.action === 'REJECTED' }"
                                        style="font-size: 0.8rem; font-weight: 600;"
                                    >
                                        Tolak Bukti
                                    </button>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="hasil_verifikasi_qa" class="form-label">
                                    {{ verifyForm.action === 'CLOSE' ? 'Resume Peninjauan Akhir (QA)' : 'Alasan Penolakan Bukti (Wajib)' }}
                                </label>
                                <textarea 
                                    id="hasil_verifikasi_qa" 
                                    v-model="verifyForm.hasil_verifikasi_qa" 
                                    class="form-textarea" 
                                    rows="4" 
                                    required 
                                    :placeholder="verifyForm.action === 'CLOSE' ? 'Tuliskan kesimpulan peninjauan lapangan, efektivitas mitigasi, dan rekomendasi...' : 'Jelaskan mengapa bukti lapangan ditolak...'"
                                ></textarea>
                                <div v-if="verifyForm.errors.hasil_verifikasi_qa" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                                    {{ verifyForm.errors.hasil_verifikasi_qa }}
                                </div>
                            </div>

                            <button type="submit" class="btn" :class="verifyForm.action === 'CLOSE' ? 'btn-success' : 'btn-danger'" style="width: 100%;" :disabled="verifyForm.processing">
                                {{ verifyForm.processing ? 'Memproses...' : (verifyForm.action === 'CLOSE' ? '✅ Sahkan & Tutup CAPA (Close)' : '❌ Tolak Bukti Lapangan') }}
                            </button>
                        </form>
                    </div>

                    <!-- General Info Card -->
                    <div v-else class="qms-card" style="position: sticky; top: 20px;">
                        <h3 style="font-size: 1.1rem; margin-bottom: 12px;">ℹ️ Siklus CAPA</h3>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6; display: flex; flex-direction: column; gap: 10px;">
                            <p><strong>1. DRAFT:</strong> Inisiator melengkapi detail rencana tindakan, PIC, dan deadline.</p>
                            <p><strong>2. IN PROGRESS:</strong> Tindakan sedang dilaksanakan di lapangan. PIC wajib mengunggah bukti lapangan setelah selesai.</p>
                            <p><strong>3. APPROVED:</strong> Bukti telah diunggah. QA akan meninjau bukti tersebut.</p>
                            <p><strong>4. CLOSE:</strong> QA melakukan verifikasi akhir dan menutup siklus CAPA ini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AttachmentViewer 
            :show="showAttachment" 
            :file-path="capa.bukti_lapangan_path" 
            :title="'Bukti Lapangan CAPA: ' + capa.capa_number" 
            @close="showAttachment = false" 
        />
    </AuthenticatedLayout>
</template>
