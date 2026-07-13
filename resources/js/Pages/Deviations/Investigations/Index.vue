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
    router.get(route('deviations.investigations.index'), {
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
    <Head title="Penyelidikan Ketidaksesuaian - QMS" />

    <AuthenticatedLayout>
        <template #header>
            🔍 Penyelidikan Ketidaksesuaian (Fishbone &amp; FMEA)
        </template>

        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- ====== INTRO BANNER ====== -->
            <div style="background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(59,130,246,0.03)); border: 1px solid rgba(59,130,246,0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.4rem;">📊</span>
                <div>
                    <div style="font-weight: 600; color: var(--text-primary);">Penyelidikan Ketidaksesuaian</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        Kelola Diagram Fishbone, Akar Masalah (Root Cause), dan kajian risiko FMEA untuk setiap laporan deviasi yang terdaftar.
                    </div>
                </div>
            </div>

            <!-- ====== PAGE HEADER ====== -->
            <div class="flex-between">
                <h3 style="font-size: 1.15rem; color: var(--text-primary);">
                    Daftar Form Penyelidikan Deviasi
                </h3>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    Total: <strong style="color:var(--text-primary)">{{ deviations.total }}</strong> laporan
                </div>
            </div>

            <!-- ====== FILTERS ====== -->
            <div class="qms-card" style="padding: 20px;">
                <div class="qms-filter-row">
                    <div class="qms-filter-item">
                        <label class="form-label" style="font-size: 0.775rem;">Cari Laporan Deviasi</label>
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
                        <label class="form-label" style="font-size: 0.775rem;">Departemen Pengaju</label>
                        <input
                            type="text"
                            v-model="department"
                            placeholder="e.g. Produksi"
                            class="form-input"
                            style="padding: 8px 12px; font-size: 0.85rem;"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="qms-filter-item-fixed" style="width: 160px;">
                        <label class="form-label" style="font-size: 0.775rem;">Status Deviasi</label>
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
                            <th>Inisiator</th>
                            <th>Departemen</th>
                            <th>Status Deviasi</th>
                            <th>Status Pengisian</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="deviations.data.length === 0">
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                Tidak ada data Penyelidikan Ketidaksesuaian.
                            </td>
                        </tr>
                        <tr
                            v-for="(dev, idx) in deviations.data"
                            :key="dev.id"
                        >
                            <td>{{ (deviations.current_page - 1) * deviations.per_page + idx + 1 }}</td>
                            <td>{{ new Date(dev.created_at).toLocaleDateString('id-ID') }}</td>
                            <td style="font-weight: 600; font-family: monospace; color: var(--accent-color);">
                                {{ dev.deviation_number }}
                            </td>
                            <td>{{ dev.initiator ? dev.initiator.name : '-' }}</td>
                            <td>{{ dev.department }}</td>
                            <td>
                                <span class="status-badge" :class="getStatusClass(dev.status)">
                                    {{ dev.status }}
                                </span>
                            </td>
                            <td>
                                <span 
                                    class="status-badge" 
                                    :class="dev.root_cause ? 'badge-approved' : 'badge-reject'"
                                >
                                    {{ dev.root_cause ? '✓ Terisi' : '✗ Default/Kosong' }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <Link
                                        :href="route('deviations.investigations.edit', dev.id)"
                                        class="btn btn-primary"
                                        style="padding: 6px 12px; font-size: 0.8rem; font-weight: 500;"
                                    >
                                        ✏️ Edit Penyelidikan
                                    </Link>
                                </div>
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
