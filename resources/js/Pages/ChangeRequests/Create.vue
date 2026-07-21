<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    type: 'CRA', // Default CRA
    nama_produk: '',
    sifat_perubahan: 'Formula',
    sifat_perubahan_custom: '',
    department: '',
    awal_sebelum_perubahan: '',
    usulan_perubahan: '',
    alasan_perubahan: '',
    analisis_dampak: '',
    attachment: null,
    attachment_description: '',
    submit_type: 'submit',
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
                        Jalur CRA (Dengan Kajian Risiko)
                    </button>
                    <button type="button" @click="setType('CRB')" class="btn-toggle" :class="{ active: form.type === 'CRB' }">
                        Jalur CRB (Tanpa Kajian Risiko)
                    </button>
                </div>
            </div>

            <form @submit.prevent class="qms-card">
                <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px; color: var(--text-primary);">
                    Form Identifikasi & Detail Perubahan ({{ form.type }})
                </h3>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="nama_produk" class="form-label">
                        Nama Produk / Proses / Pemeriksaan / Sistem / Alat <span style="color:#ef4444;">*</span>
                    </label>
                    <input id="nama_produk" type="text" v-model="form.nama_produk" class="form-input" placeholder="Nama produk, proses, pemeriksaan, sistem, atau alat..." required />
                    <div v-if="form.errors.nama_produk" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                        {{ form.errors.nama_produk }}
                    </div>
                </div>

                <div class="grid-2" style="margin-bottom: 20px;">
                    <div v-if="form.type === 'CRA'" class="form-group" style="margin-bottom: 0;">
                        <label for="sifat_perubahan" class="form-label">Jenis Perubahan</label>
                        <div style="display: flex; gap: 8px; flex-direction: column;">
                            <select id="sifat_perubahan" v-model="form.sifat_perubahan" class="form-select">
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
                            <input v-if="form.sifat_perubahan === 'Lain - lain'" type="text" v-model="form.sifat_perubahan_custom" class="form-input" placeholder="Tuliskan jenis perubahan manual..." />
                        </div>
                        <div v-if="form.errors.sifat_perubahan" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.sifat_perubahan }}
                        </div>
                        <div v-if="form.errors.sifat_perubahan_custom" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.sifat_perubahan_custom }}
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;" :style="form.type !== 'CRA' ? 'grid-column: span 2;' : ''">
                        <label for="department" class="form-label">Departemen Pengusul</label>
                        <input id="department" type="text" v-model="form.department" class="form-input" placeholder="e.g. Quality Assurance, Produksi, R&D" />
                        <div v-if="form.errors.department" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.department }}
                        </div>
                    </div>
                </div>

                <!-- Detail Perubahan Textareas (Common for CRA & CRB) -->
                <div class="fade-in" style="background-color: var(--bg-primary); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 24px; display: flex; flex-direction: column; gap: 20px;">
                    <h4 style="font-size: 1rem; color: var(--accent-color); font-weight: 700; margin-bottom: 4px;">
                        {{ form.type === 'CRA' ? '⚠️ Uraian Analisis Risiko (CRA)' : '📝 Uraian Detail Perubahan (CRB)' }}
                    </h4>
                    
                    <div class="form-group">
                        <label for="awal_sebelum_perubahan" class="form-label">
                            AWAL SEBELUM PERUBAHAN <span style="font-size: 0.775rem; color: var(--text-muted); font-weight: normal; text-transform: none;">(tulis rinciannya dalam lampiran, bila perlu)</span>
                        </label>
                        <textarea id="awal_sebelum_perubahan" v-model="form.awal_sebelum_perubahan" class="form-textarea" rows="3" placeholder="Jelaskan kondisi awal sebelum perubahan..."></textarea>
                        <div v-if="form.errors.awal_sebelum_perubahan" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.awal_sebelum_perubahan }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="usulan_perubahan" class="form-label">
                            USULAN PERUBAHAN <span style="font-size: 0.775rem; color: var(--text-muted); font-weight: normal; text-transform: none;">(tulis rinciannya dalam lampiran, bila perlu)</span>
                        </label>
                        <textarea id="usulan_perubahan" v-model="form.usulan_perubahan" class="form-textarea" rows="3" placeholder="Jelaskan usulan perubahan yang diajukan..."></textarea>
                        <div v-if="form.errors.usulan_perubahan" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.usulan_perubahan }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="alasan_perubahan" class="form-label">
                            ALASAN PERUBAHAN <span style="font-size: 0.775rem; color: var(--text-muted); font-weight: normal; text-transform: none;">(tulis rinciannya dalam lampiran, bila perlu)</span>
                        </label>
                        <textarea id="alasan_perubahan" v-model="form.alasan_perubahan" class="form-textarea" rows="3" placeholder="Jelaskan alasan dilakukannya perubahan ini..."></textarea>
                        <div v-if="form.errors.alasan_perubahan" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.alasan_perubahan }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="analisis_dampak" class="form-label">
                            ANALISIS DAMPAK DAN PENILAIAN RESIKO <span style="font-size: 0.775rem; color: var(--text-muted); font-weight: normal; text-transform: none;">(tulis kajian risiko, dan juga benefit/manfaat dari perubahan yang terjadi termasuk analisa biaya yang timbul untuk pelaksanaan perubahan dan dampak dari perubahan, bila perlu dapat dilampirkan form risk analisis)</span>
                        </label>
                        <textarea id="analisis_dampak" v-model="form.analisis_dampak" class="form-textarea" rows="4" placeholder="Tuliskan analisis dampak dan kajian risiko di sini..."></textarea>
                        <div v-if="form.errors.analisis_dampak" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.analisis_dampak }}
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
