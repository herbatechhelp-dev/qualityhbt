<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    documents: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const activeType = ref(props.filters.type || '');
const importFile = ref(null);

// Modal details state
const selectedDoc = ref(null);
const showDetailModal = ref(false);
const activeImportTab = ref('manual');
const activeDetailTab = ref('core');


const documentTypes = [
    { value: '', label: 'Semua Tipe' },
    { value: 'PROTAP', label: 'PROTAP' },
    { value: 'QMS', label: 'QMS' },
    { value: 'IK', label: 'Instruksi Kerja (IK)' },
    { value: 'SPESIFIKASI', label: 'Spesifikasi' },
    { value: 'CRF_CRE', label: 'Scan CRF / CRE' },
    { value: 'PROTOKOL', label: 'Protokol' },
    { value: 'LAPORAN_TAHUNAN', label: 'Laporan Tahunan' },
];

const applyFilters = () => {
    router.get(route('documents.index'), {
        search: search.value,
        type: activeType.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const filterByType = (type) => {
    activeType.value = type;
    applyFilters();
};

const uploadForm = useForm({
    file: null,
    type: 'PROTAP', // Default to PROTAP
});

const handleFileChange = (e) => {
    uploadForm.file = e.target.files[0];
};

const submitImport = () => {
    if (!uploadForm.file) {
        alert('Silakan pilih file terlebih dahulu.');
        return;
    }
    if (!uploadForm.type) {
        alert('Silakan pilih tipe dokumen untuk import data.');
        return;
    }
    uploadForm.post(route('documents.import'), {
        onSuccess: () => {
            uploadForm.reset('file');
            if (importFile.value) importFile.value.value = '';
            alert('Data master list dokumen berhasil diimpor!');
        }
    });
};

const syncForm = useForm({
    type: 'PROTAP',
});

const submitSync = () => {
    if (confirm('Apakah Anda yakin ingin menyinkronkan data Master List langsung dari Google Sheets?')) {
        syncForm.post(route('documents.sync-sheets'), {
            onSuccess: () => {
                alert('Data master list berhasil disinkronisasi dari Google Sheets!');
            },
            onError: (errors) => {
                if (errors.sync) {
                    alert('Gagal Sinkronisasi: ' + errors.sync);
                }
            }
        });
    }
};

const openDetails = (doc) => {
    selectedDoc.value = doc;
    activeDetailTab.value = 'core';
    showDetailModal.value = true;
};

const closeDetails = () => {
    showDetailModal.value = false;
    selectedDoc.value = null;
};
</script>

<template>
    <Head title="Master List Dokumen - QMS" />

    <AuthenticatedLayout>
        <template #header>
            📄 Master List Dokumen Mutu
        </template>

        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Bulk Import Section (QA only) -->
            <div v-if="$page.props.auth.user.role === 'qa'" class="qms-card" style="border-left: 4px solid var(--accent-color);">
                <h3 style="font-size: 1.15rem; margin-bottom: 8px;">📤 Pengelolaan & Sinkronisasi Master List Dokumen</h3>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 20px;">
                    Pilih opsi metode import data dokumen master list di bawah ini. Anda dapat mengunggah file spreadsheet lokal secara manual atau menyinkronkan data langsung dari Google Sheets.
                </p>

                <!-- Tab Switcher -->
                <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <button 
                        @click="activeImportTab = 'manual'" 
                        class="btn" 
                        :class="activeImportTab === 'manual' ? 'btn-primary' : 'btn-secondary'"
                        style="padding: 8px 16px; font-size: 0.825rem; font-weight: 600;"
                    >
                        📤 Impor File Manual
                    </button>
                    <button 
                        @click="activeImportTab = 'google-sheets'" 
                        class="btn" 
                        :class="activeImportTab === 'google-sheets' ? 'btn-primary' : 'btn-secondary'"
                        style="padding: 8px 16px; font-size: 0.825rem; font-weight: 600;"
                    >
                        ☁️ Google Sheets API Sync
                    </button>
                </div>

                <!-- Tab Content 1: Manual File Upload -->
                <div v-if="activeImportTab === 'manual'">
                    <form @submit.prevent="submitImport" style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                            <div style="display: flex; flex-direction: column; gap: 6px; width: 220px;">
                                <label class="form-label">Tipe Dokumen di Spreadsheet</label>
                                <select v-model="uploadForm.type" class="form-select" style="padding: 8px 12px; font-size: 0.85rem;" required>
                                    <option value="PROTAP">PROTAP</option>
                                    <option value="QMS">QMS</option>
                                    <option value="IK">Instruksi Kerja (IK)</option>
                                    <option value="SPESIFIKASI">Spesifikasi</option>
                                    <option value="CRF_CRE">Scan CRF / CRE</option>
                                    <option value="PROTOKOL">Protokol</option>
                                    <option value="LAPORAN_TAHUNAN">Laporan Tahunan</option>
                                </select>
                                <div v-if="uploadForm.errors.type" style="color: #ef4444; font-size: 0.8rem;">
                                    {{ uploadForm.errors.type }}
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px; flex-grow: 1; min-width: 250px;">
                                <label class="form-label">File Spreadsheet</label>
                                <input 
                                    ref="importFile"
                                    type="file" 
                                    accept=".csv, .xlsx, .xls"
                                    @change="handleFileChange"
                                    class="form-input"
                                    style="padding: 6px 12px; font-size: 0.875rem;"
                                    required
                                />
                                <div v-if="uploadForm.errors.file" style="color: #ef4444; font-size: 0.8rem;">
                                    {{ uploadForm.errors.file }}
                                </div>
                            </div>

                            <button 
                                type="submit" 
                                class="btn btn-primary" 
                                style="padding: 10px 20px;" 
                                :disabled="uploadForm.processing"
                            >
                                {{ uploadForm.processing ? 'Mengimpor...' : 'Mulai Impor' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab Content 2: Google Sheets API Sync -->
                <div v-else-if="activeImportTab === 'google-sheets'">
                    <form @submit.prevent="submitSync" style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                            <div style="display: flex; flex-direction: column; gap: 6px; width: 220px;">
                                <label class="form-label">Tipe Dokumen di Google Sheet</label>
                                <select v-model="syncForm.type" class="form-select" style="padding: 8px 12px; font-size: 0.85rem;" required>
                                    <option value="PROTAP">PROTAP</option>
                                    <option value="QMS">QMS</option>
                                    <option value="IK">Instruksi Kerja (IK)</option>
                                    <option value="SPESIFIKASI">Spesifikasi</option>
                                    <option value="CRF_CRE">Scan CRF / CRE</option>
                                    <option value="PROTOKOL">Protokol</option>
                                    <option value="LAPORAN_TAHUNAN">Laporan Tahunan</option>
                                </select>
                                <div v-if="syncForm.errors.type" style="color: #ef4444; font-size: 0.8rem;">
                                    {{ syncForm.errors.type }}
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px; flex-grow: 1; min-width: 250px; justify-content: center;">
                                <span style="font-size: 0.85rem; color: var(--text-secondary);">
                                    Menghubungkan langsung ke spreadsheet yang terkonfigurasi di server melalui Google API. Pastikan Service Account telah diberikan izin akses sebagai Viewer.
                                </span>
                                <div v-if="syncForm.errors.sync" style="color: #ef4444; font-size: 0.85rem; margin-top: 4px; font-weight: 500;">
                                    ❌ {{ syncForm.errors.sync }}
                                </div>
                            </div>

                            <button 
                                type="submit" 
                                class="btn btn-primary" 
                                style="padding: 10px 20px; display: flex; align-items: center; gap: 8px;" 
                                :disabled="syncForm.processing"
                            >
                                <span>{{ syncForm.processing ? 'Menyinkronkan...' : '🔄 Mulai Sinkronisasi' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div style="margin-top: 16px; padding: 12px; border-radius: 8px; background-color: var(--bg-primary); border: 1px solid var(--border-color); font-size: 0.775rem; color: var(--text-secondary); line-height: 1.5;">
                    📋 <strong>Pemetaan 19 Kolom Spreadsheet (Mulai Baris 6 / Range A6:S):</strong><br />
                    A: NO | B: JUDUL | C: NO DOKUMEN | D: REV | E: PERUBAHAN | F: NO PERUBAHAN / CR | G: TGL BERLAKU | H: TGL REVIEW | I: TGL REVIEW I | J: TGL REVIEW II | K: PENGGANTI | L: LAMPIRAN | M: NO. CATATAN MUTU YANG TERLAMPIR | N: DOKUMEN TERKAIT | O: TGL SOSIALISASI | P: DISTRIBUSI | Q: NO PEMUSNAHAN | R: TGL PEMUSNAHAN | S: TEMPAT PENYIMPANAN<br />
                    <span style="color: var(--accent-color); font-weight: 500;">* Kolom D & E digabungkan sebagai Revisi Perubahan. Kolom K & L digabungkan sebagai Pengganti Lampiran. Kolom H, I, J digabungkan untuk tanggal review.</span>
                </div>
            </div>

            <!-- Filters & Search Bar -->
            <div class="qms-card" style="padding: 20px;">
                <div class="qms-filter-row" style="align-items: center; justify-content: space-between;">
                    <div class="scrollable-filter-bar" style="flex-grow: 1;">
                        <button 
                            v-for="type in documentTypes" 
                            :key="type.value"
                            @click="filterByType(type.value)"
                            class="btn"
                            :class="activeType === type.value ? 'btn-primary' : 'btn-secondary'"
                            style="padding: 8px 14px; font-size: 0.825rem; font-weight: 500;"
                        >
                            {{ type.label }}
                        </button>
                    </div>

                    <div class="qms-filter-item-fixed" style="display: flex; gap: 8px; width: 100%; max-width: 320px;">
                        <input 
                            type="text" 
                            v-model="search" 
                            @keyup.enter="applyFilters"
                            placeholder="Cari nomor, judul, tempat..."
                            class="form-input"
                            style="padding: 8px 12px; font-size: 0.875rem;"
                        />
                        <button @click="applyFilters" class="btn btn-primary" style="padding: 8px 14px;">
                            Cari
                        </button>
                    </div>
                </div>
            </div>

            <!-- Logbook Table -->
            <div class="table-wrapper">
                <table class="qms-table">
                    <thead>
                        <tr>
                            <th>No (Row)</th>
                            <th>No Dokumen</th>
                            <th>Judul Dokumen</th>
                            <th>Tipe</th>
                            <th>Revisi</th>
                            <th>Tgl Berlaku</th>
                            <th>Penyimpanan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="documents.data.length === 0">
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                Tidak ada dokumen ditemukan.
                            </td>
                        </tr>
                        <tr v-for="doc in documents.data" :key="doc.id">
                            <td style="color: var(--text-secondary); font-size: 0.8rem;">
                                {{ doc.excel_no || '-' }}
                            </td>
                            <td style="font-weight: 600; font-family: monospace; color: var(--accent-color);">
                                {{ doc.document_number }}
                            </td>
                            <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" :title="doc.title">
                                {{ doc.title }}
                            </td>
                            <td>
                                <span class="status-badge badge-draft" style="font-size: 0.7rem;">
                                    {{ doc.type }}
                                </span>
                            </td>
                            <td>{{ doc.revision || '-' }}</td>
                            <td>{{ doc.effective_date || '-' }}</td>
                            <td>{{ doc.tempat_penyimpanan || '-' }}</td>
                            <td>
                                <button @click="openDetails(doc)" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; font-weight: 500;">
                                    👁️ Detail
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="documents.links.length > 3" style="display: flex; justify-content: center; gap: 8px; margin-top: 10px;">
                <Link 
                    v-for="link in documents.links" 
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="btn"
                    :class="link.active ? 'btn-primary' : 'btn-secondary'"
                    :style="{ opacity: link.url ? 1 : 0.5, pointerEvents: link.url ? 'auto' : 'none', padding: '6px 12px', fontSize: '0.8rem' }"
                />
            </div>
        </div>

        <!-- 16 Fields Detail Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showDetailModal" style="position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.65); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(8px);" @click.self="closeDetails">
                    <div class="scale-up-anim" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; width: 92%; max-width: 760px; max-height: 85vh; overflow-y: auto; padding: 32px; box-shadow: var(--hover-shadow); position: relative;">
                        <!-- Header -->
                        <div class="flex-between" style="border-bottom: 1px solid var(--border-color); padding-bottom: 18px; margin-bottom: 24px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 10px; margin: 0; font-family: var(--font-outfit);">
                                    📄 Detail Dokumen Mutu
                                </h3>
                                <span v-if="selectedDoc" style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">
                                    ID: {{ selectedDoc.id }} | Terakhir Diperbarui: {{ new Date(selectedDoc.updated_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}
                                </span>
                            </div>
                            <button @click="closeDetails" style="background: rgba(15, 23, 42, 0.05); border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.backgroundColor='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444';" onmouseout="this.style.backgroundColor='rgba(15, 23, 42, 0.05)'; this.style.color='var(--text-secondary)';">&times;</button>
                        </div>

                        <div v-if="selectedDoc" style="display: flex; flex-direction: column; gap: 20px;">
                            <!-- Document Title Card (Always Visible) -->
                            <div style="display: flex; flex-direction: column; gap: 6px; background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-tertiary) 100%); padding: 20px 24px; border-radius: 12px; border: 1px solid var(--border-color); border-left: 4px solid var(--accent-color);">
                                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Judul Dokumen</span>
                                <span style="font-weight: 800; color: var(--text-primary); font-size: 1.15rem; line-height: 1.5; font-family: var(--font-outfit);">{{ selectedDoc.title }}</span>
                            </div>

                            <!-- Modern Tabs Navigation -->
                            <div class="modal-tab-container">
                                <button @click="activeDetailTab = 'core'" class="modal-tab-btn" :class="{ active: activeDetailTab === 'core' }">
                                    📋 Utama
                                </button>
                                <button @click="activeDetailTab = 'review'" class="modal-tab-btn" :class="{ active: activeDetailTab === 'review' }">
                                    📅 Jadwal & Riwayat
                                </button>
                                <button @click="activeDetailTab = 'storage'" class="modal-tab-btn" :class="{ active: activeDetailTab === 'storage' }">
                                    📦 Distribusi & Simpan
                                </button>
                                <button @click="activeDetailTab = 'disposal'" class="modal-tab-btn" :class="{ active: activeDetailTab === 'disposal' }">
                                    🗑️ Pemusnahan
                                </button>
                            </div>

                            <!-- Tab 1: Informasi Utama -->
                            <div v-if="activeDetailTab === 'core'" class="detail-grid">
                                <div class="detail-card">
                                    <div class="detail-card-label">🔢 No Dokumen</div>
                                    <div class="detail-card-value detail-card-value-mono">{{ selectedDoc.document_number }}</div>
                                </div>
                                <div class="detail-card">
                                    <div class="detail-card-label">🔖 Tipe Dokumen</div>
                                    <div style="margin-top: 4px;">
                                        <span class="status-badge badge-draft" style="font-weight: 700; font-size: 0.75rem; padding: 4px 10px;">
                                            {{ selectedDoc.type }}
                                        </span>
                                    </div>
                                </div>
                                <div class="detail-card">
                                    <div class="detail-card-label">🔢 No Baris Excel</div>
                                    <div class="detail-card-value">{{ selectedDoc.excel_no || '-' }}</div>
                                </div>
                                <div class="detail-card">
                                    <div class="detail-card-label">🔄 Revisi Perubahan</div>
                                    <div class="detail-card-value">{{ selectedDoc.revision || '-' }}</div>
                                </div>
                                <div class="detail-card">
                                    <div class="detail-card-label">💡 No Perubahan / CC</div>
                                    <div class="detail-card-value">{{ selectedDoc.no_perubahan_cc || '-' }}</div>
                                </div>
                                <div class="detail-card">
                                    <div class="detail-card-label">🗓️ Tanggal Berlaku</div>
                                    <div class="detail-card-value">{{ selectedDoc.effective_date || '-' }}</div>
                                </div>
                            </div>

                            <!-- Tab 2: Jadwal & Riwayat -->
                            <div v-if="activeDetailTab === 'review'" class="detail-grid">
                                <div class="detail-card">
                                    <div class="detail-card-label">🗓️ Tanggal Tinjauan 1</div>
                                    <div class="detail-card-value">{{ selectedDoc.tgl_review || '-' }}</div>
                                </div>
                                <div class="detail-card">
                                    <div class="detail-card-label">🗓️ Tanggal Tinjauan 2</div>
                                    <div class="detail-card-value">{{ selectedDoc.tgl_review_2 || '-' }}</div>
                                </div>
                                <div class="detail-card" style="grid-column: span 2;">
                                    <div class="detail-card-label">👥 Tanggal Sosialisasi</div>
                                    <div class="detail-card-value">{{ selectedDoc.tgl_sosialisasi || '-' }}</div>
                                </div>
                            </div>

                            <!-- Tab 3: Distribusi & Simpan -->
                            <div v-if="activeDetailTab === 'storage'" style="display: flex; flex-direction: column; gap: 16px;">
                                <div class="detail-grid">
                                    <div class="detail-card">
                                        <div class="detail-card-label">🏢 Tempat Penyimpanan</div>
                                        <div class="detail-card-value">{{ selectedDoc.tempat_penyimpanan || '-' }}</div>
                                    </div>
                                    <div class="detail-card">
                                        <div class="detail-card-label">📄 Pengganti Lampiran</div>
                                        <div class="detail-card-value">{{ selectedDoc.pengganti_lampiran || '-' }}</div>
                                    </div>
                                </div>
                                <div class="detail-card">
                                    <div class="detail-card-label">🔖 No. Catatan Mutu Terlampir</div>
                                    <div class="detail-card-value">{{ selectedDoc.no_catatan_mutu || '-' }}</div>
                                </div>
                                <div class="detail-text-block">
                                    <div class="detail-card-label">🔗 Dokumen Terkait</div>
                                    <p style="color: var(--text-primary); margin: 6px 0 0 0; white-space: pre-wrap; font-size: 0.9rem; line-height: 1.6; font-weight: 500;">
                                        {{ selectedDoc.dokumen_terkait || '-' }}
                                    </p>
                                </div>
                                <div class="detail-text-block">
                                    <div class="detail-card-label">👥 Distribusi</div>
                                    <p style="color: var(--text-primary); margin: 6px 0 0 0; white-space: pre-wrap; font-size: 0.9rem; line-height: 1.6; font-weight: 500;">
                                        {{ selectedDoc.distribusi || '-' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Tab 4: Pemusnahan -->
                            <div v-if="activeDetailTab === 'disposal'">
                                <div v-if="selectedDoc.no_pemusnahan || selectedDoc.tgl_pemusnahan" class="detail-grid">
                                    <div class="detail-card">
                                        <div class="detail-card-label">🗑️ No Pemusnahan</div>
                                        <div class="detail-card-value detail-card-value-mono">{{ selectedDoc.no_pemusnahan || '-' }}</div>
                                    </div>
                                    <div class="detail-card">
                                        <div class="detail-card-label">📅 Tanggal Pemusnahan</div>
                                        <div class="detail-card-value">{{ selectedDoc.tgl_pemusnahan || '-' }}</div>
                                    </div>
                                </div>
                                <div v-else style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 24px; border: 1.5px dashed var(--border-color); border-radius: 16px; background-color: rgba(16, 185, 129, 0.03); color: var(--text-secondary); text-align: center; gap: 12px;">
                                    <span style="font-size: 2.5rem;">🛡️</span>
                                    <h4 style="margin: 0; color: #10b981; font-weight: 700; font-size: 1.1rem;">Dokumen Masih Aktif</h4>
                                    <p style="margin: 0; font-size: 0.85rem; max-width: 380px; line-height: 1.5; color: var(--text-muted);">
                                        Dokumen ini belum masuk dalam proses pemusnahan (disposal) dan masih berlaku di sistem QMS.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
                            <button @click="closeDetails" class="btn btn-secondary" style="padding: 10px 24px; font-weight: 600; border-radius: 8px;">
                                Tutup Detail
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
