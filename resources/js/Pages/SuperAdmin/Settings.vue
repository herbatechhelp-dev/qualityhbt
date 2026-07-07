<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    app_name: props.settings.app_name || '',
    app_logo_type: props.settings.app_logo_type || 'text',
    app_logo: props.settings.app_logo || '',
    app_logo_image: null,
    app_favicon_image: null,
    google_spreadsheet_id: props.settings.google_spreadsheet_id || '',
    google_service_account_json: props.settings.google_service_account_json || '',
    // Print custom values
    print_company_name: props.settings.print_company_name || 'HERBATECH',
    print_logo_type: props.settings.print_logo_type || 'text',
    print_logo_image: null,
});

const previewUrl = ref(null);
const fileInput = ref(null);
const faviconPreviewUrl = ref(null);
const faviconInput = ref(null);
const printPreviewUrl = ref(null);
const printLogoInput = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.app_logo_image = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const handleFaviconChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.app_favicon_image = file;
        faviconPreviewUrl.value = URL.createObjectURL(file);
    }
};

const handlePrintLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.print_logo_image = file;
        printPreviewUrl.value = URL.createObjectURL(file);
    }
};

const submitSettings = () => {
    // We must submit via Inertia post because of multipart form data (logo file upload)
    form.post(route('superadmin.settings.update'), {
        onSuccess: () => {
            previewUrl.value = null;
            faviconPreviewUrl.value = null;
            printPreviewUrl.value = null;
            if (fileInput.value) fileInput.value.value = '';
            if (faviconInput.value) faviconInput.value.value = '';
            if (printLogoInput.value) printLogoInput.value.value = '';
        },
        onError: () => {
            window.dispatchEvent(new CustomEvent('qms-notification', {
                detail: {
                    type: 'error',
                    title: 'Kesalahan Sistem',
                    message: 'Terjadi kesalahan saat menyimpan pengaturan. Periksa input Anda.'
                }
            }));
        }
    });
};
</script>

<template>
    <Head title="Pengaturan Sistem - QMS Portal" />

    <AuthenticatedLayout>
        <template #header>
            ⚙️ Pengaturan Sistem & API
        </template>

        <div class="fade-in" style="max-width: 800px; margin: 0 auto;">
            <form @submit.prevent="submitSettings" style="display: flex; flex-direction: column; gap: 24px;">
                
                <!-- General branding card -->
                <div class="qms-card" style="border-top: 4px solid var(--accent-color);">
                    <h3 style="font-size: 1.15rem; margin-top: 0; margin-bottom: 20px; color: var(--text-primary);">
                        🎨 Kustomisasi Branding & Logo
                    </h3>

                    <div class="form-group">
                        <label class="form-label">Nama Aplikasi Portal</label>
                        <input type="text" class="form-input" v-model="form.app_name" required placeholder="Contoh: QMS Portal" />
                        <div v-if="form.errors.app_name" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.app_name }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipe Logo Aplikasi</label>
                        <select class="form-select" v-model="form.app_logo_type">
                            <option value="text">Teks / Emoji (Default)</option>
                            <option value="image">Unggah Gambar Logo</option>
                        </select>
                        <div v-if="form.errors.app_logo_type" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.app_logo_type }}</div>
                    </div>

                    <!-- Text Logo -->
                    <div class="form-group">
                        <label class="form-label">Teks / Emoji Logo</label>
                        <input type="text" class="form-input" v-model="form.app_logo" placeholder="Contoh: ✨ QMS Portal" />
                        <div v-if="form.errors.app_logo" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.app_logo }}</div>
                    </div>

                    <!-- File Upload Logo -->
                    <div v-if="form.app_logo_type === 'image'" class="form-group fade-in" style="display: flex; flex-direction: column; gap: 12px;">
                        <label class="form-label">Unggah Gambar Logo</label>
                        
                        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                            <!-- Current logo preview -->
                            <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; background-color: var(--bg-tertiary); min-width: 100px; min-height: 60px; display: flex; align-items: center; justify-content: center;">
                                <template v-if="previewUrl">
                                    <img :src="previewUrl" alt="Preview" style="max-height: 50px; max-width: 150px; object-fit: contain;" />
                                </template>
                                <template v-else-if="settings.app_logo_path">
                                    <img :src="'/storage/' + settings.app_logo_path" alt="Current Logo" style="max-height: 50px; max-width: 150px; object-fit: contain;" />
                                </template>
                                <template v-else>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">Belum ada file</span>
                                </template>
                            </div>

                            <!-- File input button -->
                            <div style="flex-grow: 1;">
                                <input
                                    type="file"
                                    ref="fileInput"
                                    accept="image/*"
                                    @change="handleFileChange"
                                    style="display: none;"
                                />
                                <button type="button" @click="$refs.fileInput.click()" class="btn btn-secondary">
                                    📁 Pilih Berkas Logo
                                </button>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">Format gambar (PNG, JPG, SVG). Maksimal 2MB.</div>
                            </div>
                        </div>
                        <div v-if="form.errors.app_logo_image" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.app_logo_image }}</div>
                    </div>
                </div>

                <!-- Favicon Settings Card -->
                <div class="qms-card" style="border-top: 4px solid #8b5cf6;">
                    <h3 style="font-size: 1.15rem; margin-top: 0; margin-bottom: 8px; color: var(--text-primary);">
                        🌐 Favicon Aplikasi
                    </h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0; margin-bottom: 20px;">
                        Icon kecil yang muncul di tab browser. Disarankan format PNG atau ICO ukuran 32×32 atau 64×64 piksel, maksimal 512KB.
                    </p>

                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <!-- Current favicon preview -->
                        <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; background-color: var(--bg-tertiary); min-width: 80px; min-height: 80px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                            <template v-if="faviconPreviewUrl">
                                <img :src="faviconPreviewUrl" alt="Preview Favicon" style="width: 48px; height: 48px; object-fit: contain; image-rendering: pixelated;" />
                                <span style="font-size: 0.7rem; color: var(--text-muted);">Preview baru</span>
                            </template>
                            <template v-else-if="settings.app_favicon_path">
                                <img :src="'/storage/' + settings.app_favicon_path" alt="Favicon saat ini" style="width: 48px; height: 48px; object-fit: contain; image-rendering: pixelated;" />
                                <span style="font-size: 0.7rem; color: var(--text-muted);">Aktif</span>
                            </template>
                            <template v-else>
                                <div style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">🌐</div>
                                <span style="font-size: 0.7rem; color: var(--text-muted);">Default</span>
                            </template>
                        </div>

                        <!-- File input button -->
                        <div style="flex-grow: 1;">
                            <input
                                type="file"
                                ref="faviconInput"
                                accept="image/png,image/jpeg,image/x-icon,image/svg+xml"
                                @change="handleFaviconChange"
                                style="display: none;"
                            />
                            <button type="button" @click="$refs.faviconInput.click()" class="btn btn-secondary">
                                🖼️ Pilih Berkas Favicon
                            </button>
                            <div v-if="faviconPreviewUrl" style="margin-top: 8px; font-size: 0.8rem; color: #10b981; display: flex; align-items: center; gap: 4px;">
                                ✅ Berkas baru siap diunggah — klik Simpan untuk menerapkan.
                            </div>
                            <div v-if="form.errors.app_favicon_image" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.app_favicon_image }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">Format: PNG, JPG, ICO, SVG. Maks. 512KB. Disarankan 32×32px atau 64×64px.</div>
                        </div>
                    </div>
                </div>

                <!-- Print Document Settings Card -->
                <div class="qms-card" style="border-top: 4px solid #16a34a;">
                    <h3 style="font-size: 1.15rem; margin-top: 0; margin-bottom: 8px; color: var(--text-primary);">
                        🖨️ Kustomisasi Cetakan Dokumen (PDF/Print)
                    </h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0; margin-bottom: 20px;">
                        Pengaturan logo khusus yang digunakan saat mencetak dokumen formulir Change Request, Deviation Report, dan Form Penyelidikan.
                    </p>

                    <div class="form-group">
                        <label class="form-label">Nama Perusahaan / Teks Logo Cetakan</label>
                        <input type="text" class="form-input" v-model="form.print_company_name" :required="form.print_logo_type === 'text'" placeholder="Contoh: HERBATECH" />
                        <div v-if="form.errors.print_company_name" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.print_company_name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">Teks nama perusahaan yang akan dicetak di sudut kiri atas formulir jika tipe logo adalah teks.</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipe Logo pada Cetakan</label>
                        <select class="form-select" v-model="form.print_logo_type">
                            <option value="text">Teks Perusahaan (Default)</option>
                            <option value="image">Unggah Gambar Logo Khusus</option>
                        </select>
                        <div v-if="form.errors.print_logo_type" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.print_logo_type }}</div>
                    </div>

                    <!-- Print File Upload Logo -->
                    <div v-if="form.print_logo_type === 'image'" class="form-group fade-in" style="display: flex; flex-direction: column; gap: 12px; margin-top: 16px;">
                        <label class="form-label">Unggah Gambar Logo Cetakan</label>
                        
                        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                            <!-- Current print logo preview -->
                            <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; background-color: var(--bg-tertiary); min-width: 100px; min-height: 60px; display: flex; align-items: center; justify-content: center;">
                                <template v-if="printPreviewUrl">
                                    <img :src="printPreviewUrl" alt="Preview" style="max-height: 40px; max-width: 150px; object-fit: contain;" />
                                </template>
                                <template v-else-if="settings.print_logo_path">
                                    <img :src="'/storage/' + settings.print_logo_path" alt="Current Print Logo" style="max-height: 40px; max-width: 150px; object-fit: contain;" />
                                </template>
                                <template v-else>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">Belum ada file</span>
                                </template>
                            </div>

                            <!-- File input button -->
                            <div style="flex-grow: 1;">
                                <input
                                    type="file"
                                    ref="printLogoInput"
                                    accept="image/*"
                                    @change="handlePrintLogoChange"
                                    style="display: none;"
                                />
                                <button type="button" @click="$refs.printLogoInput.click()" class="btn btn-secondary">
                                    📁 Pilih Gambar Logo Cetakan
                                </button>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">Format gambar (PNG, JPG, SVG). Maksimal 2MB.</div>
                            </div>
                        </div>
                        <div v-if="form.errors.print_logo_image" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.print_logo_image }}</div>
                    </div>
                </div>

                <!-- API Settings Card -->
                <div class="qms-card" style="border-top: 4px solid #10b981;">
                    <h3 style="font-size: 1.15rem; margin-top: 0; margin-bottom: 20px; color: var(--text-primary);">
                        🔐 Konfigurasi Google Sheets API
                    </h3>

                    <div class="form-group">
                        <label class="form-label">Google Spreadsheet ID</label>
                        <input type="text" class="form-input" v-model="form.google_spreadsheet_id" placeholder="Masukkan ID spreadsheet Google..." />
                        <div v-if="form.errors.google_spreadsheet_id" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.google_spreadsheet_id }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">ID unik dari Spreadsheet Google (diambil dari URL browser).</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Google Service Account JSON Credentials</label>
                        <textarea
                            class="form-input"
                            v-model="form.google_service_account_json"
                            rows="10"
                            placeholder='Paste isi berkas kredensial service account JSON di sini, contoh:
{
  "type": "service_account",
  "project_id": "quality-498907",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "quality@quality-498907.iam.gserviceaccount.com",
  ...
}'
                            style="font-family: monospace; font-size: 0.85rem;"
                        ></textarea>
                        <div v-if="form.errors.google_service_account_json" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.google_service_account_json }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">Pastikan email Service Account di atas sudah di-invite sebagai Viewer di Spreadsheet Google Anda.</div>
                    </div>
                </div>

                <!-- Action Button -->
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 32px;" :disabled="form.processing">
                        💾 {{ form.processing ? 'Menyimpan...' : 'Simpan Semua Pengaturan' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
