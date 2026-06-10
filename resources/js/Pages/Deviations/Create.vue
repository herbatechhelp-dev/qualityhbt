<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    description: '',
    department: '',
    attachment: null,
    attachment_description: '',
});

const handleAttachmentChange = (e) => {
    form.attachment = e.target.files[0];
};

const submitForm = () => {
    form.post(route('deviations.store'));
};
</script>

<template>
    <Head title="Laporkan Deviasi - QMS" />

    <AuthenticatedLayout>
        <template #header>
            ➕ Laporkan Deviasi Baru
        </template>

        <div style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
            <div>
                <Link :href="route('deviations.index')" class="btn btn-secondary">
                    ← Kembali ke Logbook
                </Link>
            </div>

            <form @submit.prevent="submitForm" class="qms-card">
                <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 24px; color: var(--text-primary);">
                    Form Laporan Penyimpangan Mutu (Deviasi)
                </h3>

                <div class="form-group">
                    <label for="department" class="form-label">Departemen Pengaju</label>
                    <input 
                        id="department" 
                        type="text" 
                        v-model="form.department" 
                        class="form-input" 
                        placeholder="e.g. Produksi, QC, R&D" 
                        required
                    />
                    <div v-if="form.errors.department" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                        {{ form.errors.department }}
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Deviasi Terkait (Rincian Penyimpangan)</label>
                    <textarea 
                        id="description" 
                        v-model="form.description" 
                        class="form-textarea" 
                        rows="5" 
                        placeholder="Uraikan detail temuan ketidaksesuaian, waktu kejadian, dan dampak awal jika ada..."
                        required
                    ></textarea>
                    <div v-if="form.errors.description" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                        {{ form.errors.description }}
                    </div>
                </div>

                <h3 style="font-size: 1.1rem; border-top: 1px solid var(--border-color); padding-top: 24px; margin-top: 24px; margin-bottom: 20px; color: var(--text-primary);">
                    📎 Lampiran Bukti Penyimpangan (Opsional)
                </h3>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="attachment" class="form-label">File Bukti/Lampiran</label>
                        <input 
                            id="attachment" 
                            type="file" 
                            @change="handleAttachmentChange" 
                            class="form-input" 
                            style="padding: 6px 12px; font-size: 0.875rem;" 
                        />
                        <div v-if="form.errors.attachment" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.attachment }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="attachment_description" class="form-label">Keterangan Lampiran</label>
                        <input 
                            id="attachment_description" 
                            type="text" 
                            v-model="form.attachment_description" 
                            class="form-input" 
                            placeholder="e.g. Foto Temuan, Laporan Investigasi Awal" 
                        />
                        <div v-if="form.errors.attachment_description" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">
                            {{ form.errors.attachment_description }}
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 24px; margin-top: 32px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        🚀 Kirim Laporan Deviasi
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
