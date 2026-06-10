<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    type: 'CRA', // Default CRA
    sifat_perubahan: 'Permanen',
    department: '',
    risk_identification: '',
    potential_cause: '',
    severity: 1,
    occurrence: 1,
    detection: 1,
    risk_control: '',
    action: '',
    attachment: null,
    attachment_description: '',
    submit_type: 'submit',
});

// Auto-calculate RPN
const rpn = computed(() => {
    if (form.type !== 'CRA') return null;
    return form.severity * form.occurrence * form.detection;
});

// Risk Severity classification
const rpnClass = computed(() => {
    if (rpn.value === null) return '';
    if (rpn.value <= 100) return 'status-badge badge-approved'; // Low
    if (rpn.value <= 250) return 'status-badge badge-in_review'; // Medium
    return 'status-badge badge-reject'; // High
});

const rpnText = computed(() => {
    if (rpn.value === null) return '';
    if (rpn.value <= 100) return 'Low Risk';
    if (rpn.value <= 250) return 'Medium Risk';
    return 'High Risk';
});

const setType = (type) => {
    form.type = type;
};

const handleAttachmentChange = (e) => {
    form.attachment = e.target.files[0];
};

const submitForm = (submitType) => {
    form.submit_type = submitType;
    
    // Inertia file uploads must be POST requests
    form.post(route('change-requests.store'), {
        onError: (errors) => {
            console.log('Validation errors:', errors);
        }
    });
};
</script>

<template>
    <Head title="Buat Change Request - QMS" />

    <AuthenticatedLayout>
        <template #header>
            ➕ Buat Change Request Baru
        </template>

        <div style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
            <div class="flex-between">
                <Link :href="route('change-requests.index')" class="btn btn-secondary">
                    ← Kembali ke Logbook
                </Link>
                <div class="btn-toggle-group">
                    <button type="button" @click="setType('CRA')" class="btn-toggle" :class="{ active: form.type === 'CRA' }">
                        Jalur CRA (Dengan Risiko)
                    </button>
                    <button type="button" @click="setType('CRB')" class="btn-toggle" :class="{ active: form.type === 'CRB' }">
                        Jalur CRB (Tanpa Risiko)
                    </button>
                </div>
            </div>

            <form @submit.prevent class="qms-card">
                <!-- Common fields -->
                <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px; color: var(--text-primary);">
                    Form Identifikasi & Detail Perubahan ({{ form.type }})
                </h3>

                <div class="grid-2" style="margin-bottom: 20px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="sifat_perubahan" class="form-label">Sifat Perubahan</label>
                        <select id="sifat_perubahan" v-model="form.sifat_perubahan" class="form-select">
                            <option value="Permanen">Permanen</option>
                            <option value="Sementara">Sementara</option>
                        </select>
                        <div v-if="form.errors.sifat_perubahan" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.sifat_perubahan }}
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="department" class="form-label">Departemen Pengusul</label>
                        <input id="department" type="text" v-model="form.department" class="form-input" placeholder="e.g. Quality Assurance, Produksi, R&D" />
                        <div v-if="form.errors.department" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.department }}
                        </div>
                    </div>
                </div>

                <!-- CRA Specific Fields -->
                <div v-if="form.type === 'CRA'" class="fade-in" style="background-color: var(--bg-primary); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; flex-direction: column; gap: 20px;">
                    <h4 style="font-size: 1rem; color: var(--accent-color); font-weight: 700; margin-bottom: 4px;">⚠️ Analisis Risiko FMEA (Failure Mode & Effects Analysis)</h4>
                    
                    <div class="form-group">
                        <label for="risk_identification" class="form-label">Uraian Identifikasi Risiko</label>
                        <textarea id="risk_identification" v-model="form.risk_identification" class="form-textarea" rows="3" placeholder="Uraikan potensi kegagalan / risiko dari perubahan dokumen ini..."></textarea>
                        <div v-if="form.errors.risk_identification" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.risk_identification }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="potential_cause" class="form-label">Penyebab Potensial Risiko</label>
                        <textarea id="potential_cause" v-model="form.potential_cause" class="form-textarea" rows="2" placeholder="Apa penyebab utama terjadinya kegagalan tersebut..."></textarea>
                        <div v-if="form.errors.potential_cause" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.potential_cause }}
                        </div>
                    </div>

                    <!-- FMEA parameters -->
                    <div>
                        <label class="form-label" style="font-weight: 600;">Penilaian Parameter Risiko (Skala 1 - 10)</label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-top: 8px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="severity" class="form-label" style="font-size: 0.775rem;">Severity (Keparahan)</label>
                                <select id="severity" v-model.number="form.severity" class="form-select">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="occurrence" class="form-label" style="font-size: 0.775rem;">Occurrence (Keterjadian)</label>
                                <select id="occurrence" v-model.number="form.occurrence" class="form-select">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="detection" class="form-label" style="font-size: 0.775rem;">Detection (Deteksi)</label>
                                <select id="detection" v-model.number="form.detection" class="form-select">
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <!-- RPN Result Card -->
                            <div class="qms-card" style="padding: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; background-color: var(--bg-secondary); border: 2px dashed var(--accent-color);">
                                <span style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-secondary); font-weight: bold;">Calculated RPN</span>
                                <span style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);">{{ rpn }}</span>
                                <span :class="rpnClass" style="margin-top: 4px; font-size: 0.65rem;">{{ rpnText }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="risk_control" class="form-label">Kontrol yang Diterapkan</label>
                        <textarea id="risk_control" v-model="form.risk_control" class="form-textarea" rows="2" placeholder="Kontrol saat ini yang mencegah risiko tersebut..."></textarea>
                        <div v-if="form.errors.risk_control" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.risk_control }}
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="action" class="form-label">Tindakan Mitigasi</label>
                        <textarea id="action" v-model="form.action" class="form-textarea" rows="2" placeholder="Tindakan koreksi tambahan untuk menurunkan RPN..."></textarea>
                        <div v-if="form.errors.action" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.action }}
                        </div>
                    </div>
                </div>

                <!-- Attachment Files -->
                <h3 style="font-size: 1.1rem; border-top: 1px solid var(--border-color); padding-top: 24px; margin-top: 24px; margin-bottom: 20px; color: var(--text-primary);">
                    📎 Unggah File Lampiran (Opsional)
                </h3>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="attachment" class="form-label">File Lampiran</label>
                        <input id="attachment" type="file" @change="handleAttachmentChange" class="form-input" style="padding: 6px 12px; font-size: 0.875rem;" />
                        <div v-if="form.errors.attachment" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.attachment }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="attachment_description" class="form-label">Keterangan Lampiran</label>
                        <input id="attachment_description" type="text" v-model="form.attachment_description" class="form-input" placeholder="e.g. Draf SOP Baru, Laporan Analisis" />
                        <div v-if="form.errors.attachment_description" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.attachment_description }}
                        </div>
                    </div>
                </div>

                <!-- Submit buttons -->
                <div class="flex-between" style="border-top: 1px solid var(--border-color); padding-top: 24px; margin-top: 32px;">
                    <button type="button" @click="submitForm('draft')" class="btn btn-secondary" :disabled="form.processing">
                        📁 Save as Draft
                    </button>
                    <button type="button" @click="submitForm('submit')" class="btn btn-primary" :disabled="form.processing">
                        🚀 Save and Submit
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
