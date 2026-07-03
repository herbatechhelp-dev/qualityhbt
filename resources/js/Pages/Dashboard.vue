<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recentCr: {
        type: Array,
        default: () => [],
    },
    recentDeviations: {
        type: Array,
        default: () => [],
    },
    recentCapas: {
        type: Array,
        default: () => [],
    },
    recentDocuments: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUser = page.props.auth.user;
const isQA = computed(() => currentUser.role === 'qa' || currentUser.role === 'superadmin');
const isInitiator = computed(() => currentUser.role === 'initiator');

const activeTab = ref('documents'); // documents, cr, deviations, capas

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'DRAFT': return 'badge-draft';
        case 'OPEN': return 'badge-open';
        case 'IN REVIEW': return 'badge-in_review';
        case 'APPROVED': return 'badge-approved';
        case 'IN PROGRESS': return 'badge-in_progress';
        case 'COMPLETE': return 'badge-complete';
        case 'REJECT': return 'badge-reject';
        case 'REJECTED': return 'badge-reject';
        case 'CLOSE': return 'badge-close';
        default: return 'badge-draft';
    }
};
</script>

<template>
    <Head title="Dashboard - QMS Terintegrasi" />

    <AuthenticatedLayout>
        <template #header>
            📊 Ringkasan Pemastian Mutu (QMS)
        </template>

        <div style="display: flex; flex-direction: column; gap: 32px;">
            <!-- Welcome Banner -->
            <div class="qms-card" style="background: linear-gradient(135deg, var(--accent-color) 0%, #1d4ed8 100%); color: white; border: none;">
                <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 8px;">Selamat Datang di QMS Terintegrasi</h2>
                <p style="opacity: 0.9; font-size: 1rem; max-width: 600px;">
                    Platform terpadu pengelolaan dokumen mutu, Change Request (CR), Deviation Report, dan Corrective & Preventive Action (CAPA).
                </p>
                <div style="margin-top: 12px; font-size: 0.85rem; background: rgba(255,255,255,0.15); display: inline-block; padding: 4px 12px; border-radius: 9999px;">
                    Logged in as: <strong>{{ currentUser.name }}</strong> ({{ currentUser.role.toUpperCase() }})
                </div>
            </div>

            <!-- Stats Grid (Role-adjusted) -->
            <div class="stats-grid">
                <div class="qms-card interactive">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">📄 Master List Dokumen</div>
                    <div class="stat-val">{{ stats.documents_count }}</div>
                    <Link :href="route('documents.index')" style="color: var(--accent-color); font-size: 0.85rem; font-weight: 600; text-decoration: none;">Lihat Logbook →</Link>
                </div>

                <div class="qms-card interactive">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">
                        🔄 Change Request {{ isInitiator ? 'Saya' : '(Open)' }}
                    </div>
                    <div class="stat-val">
                        {{ stats.cr_open_count }} 
                        <span style="font-size: 1rem; font-weight: 400; color: var(--text-muted);">/ {{ stats.cr_total_count }} total</span>
                    </div>
                    <Link :href="route('change-requests.index')" style="color: var(--accent-color); font-size: 0.85rem; font-weight: 600; text-decoration: none;">Lihat Daftar CR →</Link>
                </div>

                <div class="qms-card interactive">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">
                        ⚠️ Deviasi Mutu {{ isInitiator ? 'Saya' : '(Pending)' }}
                    </div>
                    <div class="stat-val">
                        {{ stats.deviations_pending_count }} 
                        <span style="font-size: 1rem; font-weight: 400; color: var(--text-muted);">/ {{ stats.deviations_total_count }} total</span>
                    </div>
                    <Link :href="route('deviations.index')" style="color: var(--accent-color); font-size: 0.85rem; font-weight: 600; text-decoration: none;">Lihat Laporan Deviasi →</Link>
                </div>

                <div class="qms-card interactive">
                    <div style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 500;">
                        ✅ CAPA {{ isInitiator ? 'Tugasan Saya' : '(In Progress)' }}
                    </div>
                    <div class="stat-val">
                        {{ stats.capa_in_progress_count }} 
                        <span style="font-size: 1rem; font-weight: 400; color: var(--text-muted);">/ {{ stats.capa_total_count }} total</span>
                    </div>
                    <Link :href="route('capas.index')" style="color: var(--accent-color); font-size: 0.85rem; font-weight: 600; text-decoration: none;">Pantau CAPA →</Link>
                </div>
            </div>

            <!-- Tabbed Summary lists per menu (Role-adjusted content) -->
            <div class="qms-card" style="padding: 24px;">
                <h3 style="font-size: 1.25rem; margin-bottom: 20px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                    📋 Ringkasan Item Terkini (Tampilan: {{ isQA ? 'Semua Berkas QA' : 'Milik Saya / PIC' }})
                </h3>

                <!-- Tab Buttons -->
                <div style="display: flex; gap: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 20px; flex-wrap: wrap;">
                    <button
                        @click="activeTab = 'documents'"
                        class="btn"
                        :style="{
                            backgroundColor: activeTab === 'documents' ? 'var(--accent-color)' : 'var(--bg-tertiary)',
                            color: activeTab === 'documents' ? '#ffffff' : 'var(--text-secondary)',
                            padding: '8px 16px',
                            fontSize: '0.85rem'
                        }"
                    >
                        📄 Master List Dokumen
                    </button>
                    <button
                        @click="activeTab = 'cr'"
                        class="btn"
                        :style="{
                            backgroundColor: activeTab === 'cr' ? 'var(--accent-color)' : 'var(--bg-tertiary)',
                            color: activeTab === 'cr' ? '#ffffff' : 'var(--text-secondary)',
                            padding: '8px 16px',
                            fontSize: '0.85rem'
                        }"
                    >
                        🔄 Change Requests ({{ recentCr.length }})
                    </button>
                    <button
                        @click="activeTab = 'deviations'"
                        class="btn"
                        :style="{
                            backgroundColor: activeTab === 'deviations' ? 'var(--accent-color)' : 'var(--bg-tertiary)',
                            color: activeTab === 'deviations' ? '#ffffff' : 'var(--text-secondary)',
                            padding: '8px 16px',
                            fontSize: '0.85rem'
                        }"
                    >
                        ⚠️ Deviasi Mutu ({{ recentDeviations.length }})
                    </button>
                    <button
                        @click="activeTab = 'capas'"
                        class="btn"
                        :style="{
                            backgroundColor: activeTab === 'capas' ? 'var(--accent-color)' : 'var(--bg-tertiary)',
                            color: activeTab === 'capas' ? '#ffffff' : 'var(--text-secondary)',
                            padding: '8px 16px',
                            fontSize: '0.85rem'
                        }"
                    >
                        ✅ Tindakan CAPA ({{ recentCapas.length }})
                    </button>
                </div>

                <!-- Tab Contents -->
                <div class="fade-in">
                    <!-- Documents Tab -->
                    <div v-if="activeTab === 'documents'">
                        <div v-if="recentDocuments.length === 0" style="text-align: center; color: var(--text-muted); padding: 20px 0;">
                            Tidak ada dokumen master terbaru.
                        </div>
                        <div v-else class="table-wrapper">
                            <table class="qms-table">
                                <thead>
                                    <tr>
                                        <th>No Dokumen</th>
                                        <th>Judul Dokumen</th>
                                        <th>Tipe</th>
                                        <th>Revisi</th>
                                        <th>Tgl Efektif</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="doc in recentDocuments" :key="doc.id">
                                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 600; color: var(--accent-color);">{{ doc.document_number }}</td>
                                        <td>{{ doc.title }}</td>
                                        <td>{{ doc.type }}</td>
                                        <td>{{ doc.revision }}</td>
                                        <td>{{ doc.effective_date }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 16px; text-align: right;">
                            <Link :href="route('documents.index')" class="btn btn-secondary" style="font-size: 0.8rem; padding: 6px 12px;">
                                Lihat Semua Dokumen Master →
                            </Link>
                        </div>
                    </div>

                    <!-- Change Requests Tab -->
                    <div v-if="activeTab === 'cr'">
                        <div v-if="recentCr.length === 0" style="text-align: center; color: var(--text-muted); padding: 20px 0;">
                            Tidak ada Change Request terbaru.
                        </div>
                        <div v-else class="table-wrapper">
                            <table class="qms-table">
                                <thead>
                                    <tr>
                                        <th>No CR</th>
                                        <th>Inisiator</th>
                                        <th>Tipe</th>
                                        <th>Jenis</th>
                                        <th>Departemen</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="cr in recentCr" :key="cr.id">
                                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 600; color: var(--accent-color);">{{ cr.cr_number }}</td>
                                        <td>{{ cr.initiator ? cr.initiator.name : '-' }}</td>
                                        <td>{{ cr.type }}</td>
                                        <td>{{ cr.sifat_perubahan }}</td>
                                        <td>{{ cr.department }}</td>
                                        <td>
                                            <span class="status-badge" :class="getStatusBadgeClass(cr.status)">
                                                {{ cr.status }}
                                            </span>
                                        </td>
                                        <td>
                                            <Link :href="route('change-requests.show', cr.id)" class="btn btn-secondary" style="font-size: 0.75rem; padding: 4px 8px;">
                                                Detail
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 16px; text-align: right;">
                            <Link :href="route('change-requests.index')" class="btn btn-secondary" style="font-size: 0.8rem; padding: 6px 12px;">
                                Lihat Semua Change Request →
                            </Link>
                        </div>
                    </div>

                    <!-- Deviations Tab -->
                    <div v-if="activeTab === 'deviations'">
                        <div v-if="recentDeviations.length === 0" style="text-align: center; color: var(--text-muted); padding: 20px 0;">
                            Tidak ada laporan deviasi terbaru.
                        </div>
                        <div v-else class="table-wrapper">
                            <table class="qms-table">
                                <thead>
                                    <tr>
                                        <th>No Deviasi</th>
                                        <th>Inisiator</th>
                                        <th>Departemen</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="dev in recentDeviations" :key="dev.id">
                                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 600; color: var(--accent-color);">{{ dev.deviation_number }}</td>
                                        <td>{{ dev.initiator ? dev.initiator.name : '-' }}</td>
                                        <td>{{ dev.department }}</td>
                                        <td>
                                            <span class="status-badge" :class="getStatusBadgeClass(dev.status)">
                                                {{ dev.status }}
                                            </span>
                                        </td>
                                        <td>
                                            <Link :href="route('deviations.show', dev.id)" class="btn btn-secondary" style="font-size: 0.75rem; padding: 4px 8px;">
                                                Detail
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 16px; text-align: right;">
                            <Link :href="route('deviations.index')" class="btn btn-secondary" style="font-size: 0.8rem; padding: 6px 12px;">
                                Lihat Semua Deviasi →
                            </Link>
                        </div>
                    </div>

                    <!-- CAPAs Tab -->
                    <div v-if="activeTab === 'capas'">
                        <div v-if="recentCapas.length === 0" style="text-align: center; color: var(--text-muted); padding: 20px 0;">
                            Tidak ada tindakan CAPA terbaru.
                        </div>
                        <div v-else class="table-wrapper">
                            <table class="qms-table">
                                <thead>
                                    <tr>
                                        <th>No CAPA</th>
                                        <th>Sumber No CAPA</th>
                                        <th>CAPA (Sub)</th>
                                        <th>PIC</th>
                                        <th>Tgl Selesai</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="capa in recentCapas" :key="capa.id">
                                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 600; color: var(--accent-color);">{{ capa.capa_number }}</td>
                                        <td style="font-family: monospace; font-size: 0.85rem;">{{ capa.deviation_number_ref }}</td>
                                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 600; color: var(--accent-color);">{{ capa.sub_capa_number }}</td>
                                        <td>{{ capa.pic ? capa.pic.name : '-' }}</td>
                                        <td>{{ capa.tanggal_selesai || '-' }}</td>
                                        <td>
                                            <span class="status-badge" :class="getStatusBadgeClass(capa.status)">
                                                {{ capa.status }}
                                            </span>
                                        </td>
                                        <td>
                                            <Link :href="route('capas.show', capa.id)" class="btn btn-secondary" style="font-size: 0.75rem; padding: 4px 8px;">
                                                Detail
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 16px; text-align: right;">
                            <Link :href="route('capas.index')" class="btn btn-secondary" style="font-size: 0.8rem; padding: 6px 12px;">
                                Lihat Semua CAPA →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action & Info Grid -->
            <div class="grid-2">
                <!-- Inisiator Actions -->
                <div v-if="isInitiator" class="qms-card">
                    <h3 style="font-size: 1.2rem; margin-bottom: 20px; color: var(--text-primary);">⚡ Tindakan Cepat (Inisiator)</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <Link :href="route('change-requests.create')" class="btn btn-primary" style="justify-content: flex-start;">
                            ➕ Buat Change Request Baru (CRA/CRB)
                        </Link>
                        <Link :href="route('deviations.create')" class="btn btn-secondary" style="justify-content: flex-start; background-color: var(--bg-tertiary);">
                            ⚠️ Laporkan Deviasi Mutu Baru
                        </Link>
                    </div>
                </div>

                <!-- QA & Superadmin Actions -->
                <div v-else class="qms-card">
                    <h3 style="font-size: 1.2rem; margin-bottom: 20px; color: var(--text-primary);">⚡ Tindakan Cepat (QA / Admin)</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <Link :href="route('documents.index')" class="btn btn-primary" style="justify-content: flex-start;">
                            📥 Impor / Sinkronisasi Master Dokumen
                        </Link>
                        <Link v-if="currentUser.role === 'superadmin'" :href="route('superadmin.users')" class="btn btn-secondary" style="justify-content: flex-start; background-color: var(--bg-tertiary);">
                            👥 Kelola Akun & Hak Akses Pengguna
                        </Link>
                        <Link v-if="currentUser.role === 'superadmin'" :href="route('superadmin.settings')" class="btn btn-secondary" style="justify-content: flex-start; background-color: var(--bg-tertiary);">
                            ⚙️ Pengaturan Pengenal & API Branding
                        </Link>
                    </div>
                </div>

                <div class="qms-card">
                    <h3 style="font-size: 1.2rem; margin-bottom: 16px; color: var(--text-primary);">ℹ️ Alur Kerja QMS</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.9rem; color: var(--text-secondary);">
                        <div style="display: flex; gap: 12px;">
                            <span style="font-weight: bold; color: var(--accent-color);">1.</span>
                            <span><strong>Master List:</strong> QA mengimpor spreadsheet untuk mendaftarkan dokumen mutu aktif.</span>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <span style="font-weight: bold; color: var(--accent-color);">2.</span>
                            <span><strong>Change Request:</strong> Inisiator membuat draf CRA (dengan RPN) / CRB. QA mengevaluasi tindakan & PIC.</span>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <span style="font-weight: bold; color: var(--accent-color);">3.</span>
                            <span><strong>Deviasi & CAPA:</strong> Laporkan penyimpangan mutu. Persetujuan QA otomatis membuka siklus baru di Modul CAPA.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
