<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    capas: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const currentUser = page.props.auth.user;
const isQA = computed(() => currentUser.role === 'qa' || currentUser.role === 'superadmin');
const isInitiator = computed(() => currentUser.role === 'initiator');

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(route('capas.index'), {
        search: search.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    applyFilters();
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

// Row highlight logic
const getRowStyle = (capa) => {
    if (isQA.value && capa.status === 'APPROVED') return 'background-color: rgba(99,102,241,0.05);';
    if (isInitiator.value && capa.status === 'DRAFT') return 'background-color: rgba(245,158,11,0.05);';
    return '';
};

// Smart action label
const getActionLabel = (capa) => {
    if (isQA.value && capa.status === 'APPROVED') return '🔍 Verifikasi';
    if (isInitiator.value && capa.status === 'DRAFT') return '✍️ Lengkapi';
    if (isInitiator.value && capa.status === 'IN PROGRESS') return '📤 Upload Bukti';
    return '👁️ Lihat Detail';
};
</script>

<template>
    <Head title="Monitoring CAPA - QMS" />

    <AuthenticatedLayout>
        <template #header>
            ✅ {{ isQA ? 'QA Dashboard — Verifikasi & Monitoring CAPA' : 'Users Dashboard — CAPA Saya' }}
        </template>

        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- ====== QA BANNER ====== -->
            <div v-if="isQA" style="background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(16,185,129,0.03)); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.4rem;">🛡️</span>
                <div>
                    <div style="font-weight: 600; color: var(--text-primary);">QA Dashboard — Monitoring CAPA</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Tinjau CAPA berstatus <strong>APPROVED</strong> (bukti telah diunggah) untuk memberikan verifikasi akhir dan menutup siklus.</div>
                </div>
            </div>

            <!-- ====== INITIATOR DRAFT BANNER ====== -->
            <div
                v-if="isInitiator && capas.data.some(c => c.status === 'DRAFT')"
                style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.3); border-radius: 12px; padding: 14px 20px; display: flex; align-items: center; gap: 12px;"
            >
                <span style="font-size: 1.3rem;">📋</span>
                <div>
                    <div style="font-weight: 600; color: #d97706;">Ada CAPA yang Belum Dilengkapi</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Segera lengkapi detail rencana tindakan, PIC, dan tanggal pelaksanaan untuk CAPA berstatus DRAFT.</div>
                </div>
            </div>

            <!-- ====== PAGE HEADER ====== -->
            <div class="flex-between">
                <h3 style="font-size: 1.15rem; color: var(--text-primary);">
                    {{ isQA ? 'Daftar Semua Tindakan CAPA' : 'Daftar Pemantauan CAPA Saya' }}
                </h3>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    Total: <strong style="color:var(--text-primary)">{{ capas.total }}</strong> CAPA
                </div>
            </div>

            <!-- ====== FILTERS ====== -->
            <div class="qms-card" style="padding: 20px;">
                <div class="qms-filter-row">
                    <div class="qms-filter-item">
                        <label class="form-label" style="font-size: 0.775rem;">Cari CAPA (No CAPA / No Referensi Deviasi / Inisiator)</label>
                        <input
                            type="text"
                            v-model="search"
                            placeholder="Cari..."
                            class="form-input"
                            style="padding: 8px 12px; font-size: 0.85rem;"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="qms-filter-item-fixed" style="width: 220px;">
                        <label class="form-label" style="font-size: 0.775rem;">Status Tindakan</label>
                        <select v-model="status" class="form-select" style="padding: 8px 12px; font-size: 0.85rem;">
                            <option value="">Semua Status</option>
                            <option value="DRAFT">DRAFT (Lengkapi Form)</option>
                            <option value="IN PROGRESS">IN PROGRESS (Pelaksanaan)</option>
                            <option value="APPROVED">APPROVED (Bukti Terupload)</option>
                            <option value="CLOSE">CLOSE (Siklus Selesai)</option>
                        </select>
                    </div>

                    <div class="flex-gap-10 qms-filter-item-fixed">
                        <button @click="applyFilters" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem;">
                            Filter
                        </button>
                        <button @click="resetFilters" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.85rem; background-color: var(--bg-tertiary);">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- ====== TABLE ====== -->
            <div class="table-wrapper">
                <!-- QA Table -->
                <table v-if="isQA" class="qms-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No CAPA</th>
                            <th>Sumber No CAPA</th>
                            <th>Sumber CAPA</th>
                            <th>CAPA (Sub)</th>
                            <th>Inisiator</th>
                            <th>Departemen</th>
                            <th>Rencana Tindakan</th>
                            <th>Target</th>
                            <th>Tgl Selesai</th>
                            <th>Status CAPA</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="capas.data.length === 0">
                            <td colspan="12" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                Tidak ada data CAPA.
                            </td>
                        </tr>
                        <tr
                            v-for="(capa, idx) in capas.data"
                            :key="capa.id"
                            :style="getRowStyle(capa)"
                        >
                            <td>{{ (capas.current_page - 1) * capas.per_page + idx + 1 }}</td>
                            <td style="font-family: monospace; font-size: 0.85rem;">{{ capa.capa_number }}</td>
                            <td style="font-family: monospace; font-size: 0.85rem;">{{ capa.deviation_number_ref }}</td>
                            <td>{{ capa.type_capa }}</td>
                            <td style="font-weight: 600; font-family: monospace; color: var(--accent-color);">
                                {{ capa.sub_capa_number }}
                                <span v-if="capa.status === 'APPROVED'" style="display: block; font-size: 0.65rem; color: #6366f1; font-weight: 700; font-family: sans-serif; margin-top: 2px;">🔍 Perlu Verifikasi</span>
                            </td>
                            <td>{{ capa.initiator ? capa.initiator.name : '-' }}</td>
                            <td>{{ capa.deviation ? capa.deviation.department : '-' }}</td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ capa.tindakan_capa || '-' }}
                            </td>
                            <td>{{ capa.tanggal_mulai || '-' }}</td>
                            <td style="font-weight: 600;">
                                <span v-if="capa.tanggal_selesai">
                                    {{ capa.tanggal_selesai }}
                                    <span
                                        v-if="capa.status !== 'CLOSE' && new Date(capa.tanggal_selesai) < new Date()"
                                        style="display:block; font-size:0.65rem; color:#ef4444; font-weight:700;"
                                    >⏰ Overdue</span>
                                </span>
                                <span v-else style="color: var(--text-muted);">-</span>
                            </td>
                            <td>
                                <span class="status-badge" :class="getStatusClass(capa.status)">
                                    {{ capa.status }}
                                </span>
                            </td>
                            <td>
                                <Link
                                    :href="route('capas.show', capa.id)"
                                    class="btn"
                                    :class="capa.status === 'APPROVED' ? 'btn-primary' : 'btn-secondary'"
                                    style="padding: 6px 12px; font-size: 0.8rem; font-weight: 500;"
                                >
                                    {{ getActionLabel(capa) }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Users Table -->
                <table v-else class="qms-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal Request</th>
                            <th>No Deviasi</th>
                            <th>Sumber No CAPA</th>
                            <th>CAPA (Sub)</th>
                            <th>Inisiator</th>
                            <th>Departemen</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="capas.data.length === 0">
                            <td colspan="11" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                Tidak ada CAPA yang ditugaskan kepada Anda.
                            </td>
                        </tr>
                        <tr
                            v-for="(capa, idx) in capas.data"
                            :key="capa.id"
                            :style="getRowStyle(capa)"
                        >
                            <td>{{ (capas.current_page - 1) * capas.per_page + idx + 1 }}</td>
                            <td>{{ new Date(capa.created_at).toLocaleDateString('id-ID') }}</td>
                            <td style="font-family: monospace; font-size: 0.85rem;">{{ capa.deviation_number_ref }}</td>
                            <td style="font-family: monospace; font-size: 0.85rem;">{{ capa.capa_number }}</td>
                            <td style="font-weight: 600; font-family: monospace; color: var(--accent-color);">
                                {{ capa.sub_capa_number }}
                                <span v-if="capa.status === 'DRAFT'" style="display: block; font-size: 0.65rem; color: #d97706; font-weight: 700; font-family: sans-serif; margin-top: 2px;">⚡ Perlu Dilengkapi</span>
                                <span v-if="capa.status === 'IN PROGRESS'" style="display: block; font-size: 0.65rem; color: #3b82f6; font-weight: 700; font-family: sans-serif; margin-top: 2px;">📤 Upload Bukti</span>
                            </td>
                            <td>{{ capa.initiator ? capa.initiator.name : '-' }}</td>
                            <td>{{ capa.deviation ? capa.deviation.department : '-' }}</td>
                            <td>{{ capa.tanggal_mulai || '-' }}</td>
                            <td style="font-weight: 600;">
                                <span v-if="capa.tanggal_selesai">
                                    {{ capa.tanggal_selesai }}
                                    <span
                                        v-if="capa.status !== 'CLOSE' && new Date(capa.tanggal_selesai) < new Date()"
                                        style="display:block; font-size:0.65rem; color:#ef4444; font-weight:700;"
                                    >⏰ Overdue</span>
                                </span>
                                <span v-else style="color: var(--text-muted);">-</span>
                            </td>
                            <td>
                                <span class="status-badge" :class="getStatusClass(capa.status)">
                                    {{ capa.status }}
                                </span>
                            </td>
                            <td>
                                <Link
                                    :href="route('capas.show', capa.id)"
                                    class="btn"
                                    :class="capa.status === 'DRAFT' || capa.status === 'IN PROGRESS' ? 'btn-primary' : 'btn-secondary'"
                                    style="padding: 6px 12px; font-size: 0.8rem; font-weight: 500;"
                                >
                                    {{ getActionLabel(capa) }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="capas.links.length > 3" style="display: flex; justify-content: center; gap: 8px; margin-top: 10px;">
                <Link
                    v-for="link in capas.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="btn"
                    :class="link.active ? 'btn-primary' : 'btn-secondary'"
                    :style="{ opacity: link.url ? 1 : 0.5, pointerEvents: link.url ? 'auto' : 'none', padding: '6px 12px', fontSize: '0.8rem' }"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
