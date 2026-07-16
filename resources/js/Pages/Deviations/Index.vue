<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    deviations: {
        type: Object,
        required: true,
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
const department = ref(props.filters.department || '');
const status = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(route('deviations.index'), {
        search: search.value,
        department: department.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    department.value = '';
    status.value = '';
    applyFilters();
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
</script>

<template>
    <Head title="Logbook Deviasi Mutu - QMS" />

    <AuthenticatedLayout>
        <template #header>
            ⚠️ {{ isQA ? 'QA Dashboard — Verifikasi Deviation Report' : 'Dashboard Inisiator — Laporan Deviasi Saya' }}
        </template>

        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- ====== QA BANNER ====== -->
            <div v-if="isQA" style="background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.03)); border: 1px solid rgba(245,158,11,0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.4rem;">🛡️</span>
                <div>
                    <div style="font-weight: 600; color: var(--text-primary);">QA Dashboard — Mode Review Deviasi</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Tinjau laporan berstatus <strong>OPEN</strong> untuk memberikan keputusan Approve (→ CAPA) atau Reject.</div>
                </div>
            </div>

            <!-- ====== INITIATOR REJECTED BANNER ====== -->
            <div
                v-if="isInitiator && deviations.data.some(d => d.status === 'REJECTED')"
                style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 14px 20px; display: flex; align-items: center; gap: 12px;"
            >
                <span style="font-size: 1.3rem;">⚠️</span>
                <div>
                    <div style="font-weight: 600; color: #ef4444;">Ada Laporan yang Dikembalikan (Rejected)</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">QA menolak beberapa laporan Anda. Tinjau alasan penolakan dan lakukan revisi jika diperlukan.</div>
                </div>
            </div>

            <!-- ====== PAGE HEADER ====== -->
            <div class="flex-between">
                <h3 style="font-size: 1.15rem; color: var(--text-primary);">
                    {{ isQA ? 'Daftar Semua Laporan Deviasi' : 'Laporan Deviasi Saya' }}
                </h3>
                <!-- Only initiator can submit new deviation -->
                <Link v-if="isInitiator" :href="route('deviations.create')" class="btn btn-primary">
                    ➕ Laporkan Deviasi Baru
                </Link>
                <div v-if="isQA" style="font-size: 0.8rem; color: var(--text-muted);">
                    Total: <strong style="color:var(--text-primary)">{{ deviations.total }}</strong> laporan
                </div>
            </div>

            <!-- ====== FILTERS ====== -->
            <div class="qms-card" style="padding: 20px;">
                <div class="qms-filter-row">
                    <div class="qms-filter-item">
                        <label class="form-label" style="font-size: 0.775rem;">
                            {{ isQA ? 'Cari No Deviasi / Inisiator / Deskripsi' : 'Cari Laporan Deviasi' }}
                        </label>
                        <input
                            type="text"
                            v-model="search"
                            placeholder="Cari..."
                            class="form-input"
                            style="padding: 8px 12px; font-size: 0.85rem;"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="qms-filter-item-fixed" style="width: 180px;">
                        <label class="form-label" style="font-size: 0.775rem;">No. Bets / Identitas</label>
                        <input
                            type="text"
                            v-model="department"
                            placeholder="e.g. Bets atau Kode Alat"
                            class="form-input"
                            style="padding: 8px 12px; font-size: 0.85rem;"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="qms-filter-item-fixed" style="width: 160px;">
                        <label class="form-label" style="font-size: 0.775rem;">Status</label>
                        <select v-model="status" class="form-select" style="padding: 8px 12px; font-size: 0.85rem;">
                            <option value="">Semua Status</option>
                            <option value="OPEN">OPEN</option>
                            <option value="IN REVIEW">IN REVIEW</option>
                            <option value="APPROVED">APPROVED</option>
                            <option value="REJECTED">REJECTED</option>
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

            <!-- ====== LOGBOOK TABLE ====== -->
            <div class="table-wrapper">
                <table class="qms-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal Request</th>
                            <th>No Deviasi</th>
                            <th v-if="isQA">Inisiator</th>
                            <th>No. Bets / Identitas</th>
                            <th>Deviasi Terkait (Deskripsi)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="deviations.data.length === 0">
                            <td :colspan="isQA ? 8 : 7" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                {{ isInitiator ? 'Anda belum memiliki laporan deviasi. Klik "Laporkan Deviasi Baru" untuk membuat.' : 'Tidak ada data Deviation Report.' }}
                            </td>
                        </tr>
                        <tr
                            v-for="(dev, idx) in deviations.data"
                            :key="dev.id"
                            :style="
                                (isQA && dev.status === 'OPEN') ? 'background-color: rgba(245,158,11,0.05);' :
                                (isInitiator && dev.status === 'REJECTED') ? 'background-color: rgba(239,68,68,0.05);' : ''
                            "
                        >
                            <td>{{ (deviations.current_page - 1) * deviations.per_page + idx + 1 }}</td>
                            <td>{{ new Date(dev.created_at).toLocaleDateString('id-ID') }}</td>
                            <td style="font-weight: 600; font-family: monospace; color: var(--accent-color);">
                                {{ dev.deviation_number }}
                                <!-- QA: perlu review indicator -->
                                <span v-if="isQA && dev.status === 'OPEN'" style="display: block; font-size: 0.65rem; color: #f59e0b; font-weight: 700; font-family: sans-serif; margin-top: 2px;">⚡ Perlu Review</span>
                                <!-- Initiator: dikembalikan indicator -->
                                <span v-if="isInitiator && dev.status === 'REJECTED'" style="display: block; font-size: 0.65rem; color: #ef4444; font-weight: 700; font-family: sans-serif; margin-top: 2px;">↩️ Dikembalikan QA</span>
                            </td>
                            <td v-if="isQA">{{ dev.initiator ? dev.initiator.name : '-' }}</td>
                            <td>{{ dev.department }}</td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ dev.description }}
                            </td>
                            <td>
                                <span class="status-badge" :class="getStatusClass(dev.status)">
                                    {{ dev.status }}
                                </span>
                            </td>
                            <td>
                                <Link
                                    :href="route('deviations.show', dev.id)"
                                    class="btn"
                                    :class="isInitiator && dev.status === 'REJECTED' ? 'btn-primary' : 'btn-secondary'"
                                    style="padding: 6px 12px; font-size: 0.8rem; font-weight: 500;"
                                >
                                    {{ isQA && dev.status === 'OPEN' ? '🛠️ Review' : (isInitiator && dev.status === 'REJECTED' ? '✍️ Revisi' : '🔍 View Detail') }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="deviations.links.length > 3" style="display: flex; justify-content: center; gap: 8px; margin-top: 10px;">
                <Link
                    v-for="link in deviations.links"
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
