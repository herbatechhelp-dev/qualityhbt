<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    changeRequests: {
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
const department = ref(props.filters.department || '');
const type = ref(props.filters.type || '');
const status = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(route('change-requests.index'), {
        search: search.value,
        department: department.value,
        type: type.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    department.value = '';
    type.value = '';
    status.value = '';
    applyFilters();
};

const getStatusClass = (status) => {
    switch (status) {
        case 'DRAFT': return 'badge-draft';
        case 'OPEN': return 'badge-open';
        case 'IN REVIEW': return 'badge-in_review';
        case 'APPROVED': return 'badge-approved';
        case 'IN PROGRESS': return 'badge-in_progress';
        case 'COMPLETE': return 'badge-complete';
        case 'REJECT': return 'badge-reject';
        default: return 'badge-draft';
    }
};
</script>

<template>
    <Head title="Logbook Change Request - QMS" />

    <AuthenticatedLayout>
        <template #header>
            🔄 {{ isQA ? 'QA Dashboard — Verifikasi Change Request' : 'Users Dashboard — Change Request (CRA & CRB)' }}
        </template>

        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- ====== QA INFO BANNER ====== -->
            <div v-if="isQA" style="background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(99,102,241,0.04)); border: 1px solid rgba(99,102,241,0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.4rem;">🛡️</span>
                <div>
                    <div style="font-weight: 600; color: var(--text-primary);">QA Dashboard — Mode Verifikasi</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Anda melihat seluruh daftar Change Request. Tinjau status OPEN & IN REVIEW untuk memberikan keputusan.</div>
                </div>
            </div>

            <!-- ====== INITIATOR HEADER ====== -->
            <div v-if="isInitiator" class="flex-between">
                <h3 style="font-size: 1.15rem; color: var(--text-primary);">Usulan Perubahan Saya</h3>
                <Link :href="route('change-requests.create')" class="btn btn-primary">
                    ➕ Tambah Data Baru
                </Link>
            </div>

            <!-- ====== QA HEADER ====== -->
            <div v-if="isQA" class="flex-between">
                <h3 style="font-size: 1.15rem; color: var(--text-primary);">Daftar Semua Change Request</h3>
                <div style="display: flex; gap: 8px; align-items: center; font-size: 0.8rem; color: var(--text-muted);">
                    <span>Total: <strong style="color:var(--text-primary)">{{ changeRequests.total }}</strong> CR</span>
                </div>
            </div>

            <!-- ====== FILTERS ====== -->
            <div class="qms-card" style="padding: 20px;">
                <div class="qms-filter-row">
                    <div class="qms-filter-item">
                        <label class="form-label" style="font-size: 0.775rem;">Cari No CR / Inisiator</label>
                        <input
                            type="text"
                            v-model="search"
                            placeholder="Cari..."
                            class="form-input"
                            style="padding: 8px 12px; font-size: 0.85rem;"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="qms-filter-item-fixed" style="width: 150px;">
                        <label class="form-label" style="font-size: 0.775rem;">Departemen</label>
                        <input
                            type="text"
                            v-model="department"
                            placeholder="e.g. Produksi"
                            class="form-input"
                            style="padding: 8px 12px; font-size: 0.85rem;"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="qms-filter-item-fixed" style="width: 130px;">
                        <label class="form-label" style="font-size: 0.775rem;">Jalur CR</label>
                        <select v-model="type" class="form-select" style="padding: 8px 12px; font-size: 0.85rem;">
                            <option value="">Semua Jalur</option>
                            <option value="CRA">CRA (Risiko)</option>
                            <option value="CRB">CRB (Tanpa Risiko)</option>
                        </select>
                    </div>

                    <div class="qms-filter-item-fixed" style="width: 150px;">
                        <label class="form-label" style="font-size: 0.775rem;">Status</label>
                        <select v-model="status" class="form-select" style="padding: 8px 12px; font-size: 0.85rem;">
                            <option value="">Semua Status</option>
                            <!-- Draft only visible for initiators -->
                            <option v-if="isInitiator" value="DRAFT">DRAFT</option>
                            <option value="OPEN">OPEN</option>
                            <option value="IN REVIEW">IN REVIEW</option>
                            <option value="APPROVED">APPROVED</option>
                            <option value="IN PROGRESS">IN PROGRESS</option>
                            <option value="COMPLETE">COMPLETE</option>
                            <option value="REJECT">REJECT</option>
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
                <table class="qms-table">
                    <thead>
                        <tr>
                            <th>No CR</th>
                            <th>Jalur</th>
                            <th>Sifat</th>
                            <th>Inisiator</th>
                            <th>PIC</th>
                            <th>Departemen</th>
                            <th>RPN</th>
                            <th>Status</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="changeRequests.data.length === 0">
                            <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                {{ isInitiator ? 'Anda belum memiliki Change Request. Klik "Tambah Data Baru" untuk membuat.' : 'Tidak ada data Change Request.' }}
                            </td>
                        </tr>
                        <tr
                            v-for="cr in changeRequests.data"
                            :key="cr.id"
                            :style="
                                isQA && (cr.status === 'OPEN' || cr.status === 'IN REVIEW') ? 'background-color: rgba(99,102,241,0.04);' :
                                isInitiator && cr.status === 'REJECT' ? 'background-color: rgba(239,68,68,0.05);' : ''
                            "
                        >
                            <td style="font-weight: 600; font-family: monospace; color: var(--accent-color);">
                                {{ cr.cr_number }}
                                <!-- Perlu tindakan QA indicator -->
                                <span v-if="isQA && cr.status === 'OPEN'" style="display: block; font-size: 0.65rem; color: #f59e0b; font-weight: 700; font-family: sans-serif; margin-top: 2px;">⚡ Perlu Review</span>
                                <span v-if="isQA && cr.status === 'IN REVIEW'" style="display: block; font-size: 0.65rem; color: #6366f1; font-weight: 700; font-family: sans-serif; margin-top: 2px;">👁️ Sedang Ditinjau</span>
                                <!-- Perlu revisi initiator indicator -->
                                <span v-if="isInitiator && cr.status === 'REJECT'" style="display: block; font-size: 0.65rem; color: #ef4444; font-weight: 700; font-family: sans-serif; margin-top: 2px;">⚡ Perlu Revisi</span>
                            </td>
                            <td>
                                <span class="status-badge" :class="cr.type === 'CRA' ? 'badge-in_progress' : 'badge-draft'" style="font-size: 0.7rem;">
                                    {{ cr.type }}
                                </span>
                            </td>
                            <td>{{ cr.sifat_perubahan }}</td>
                            <td>{{ cr.initiator ? cr.initiator.name : 'Unknown' }}</td>
                            <td>
                                <span :style="cr.pic && currentUser.id === cr.pic_id ? 'font-weight:700;color:var(--accent-color);' : ''">
                                    {{ cr.pic ? cr.pic.name : '-' }}
                                    <span v-if="cr.pic && currentUser.id === cr.pic_id" style="display:block;font-size:0.65rem;color:var(--accent-color);">👤 Anda</span>
                                </span>
                            </td>
                            <td>{{ cr.department }}</td>
                            <td style="font-weight: 600;">
                                {{ cr.type === 'CRA' ? cr.rpn : '-' }}
                            </td>
                            <td>
                                <span class="status-badge" :class="getStatusClass(cr.status)">
                                    {{ cr.status }}
                                </span>
                            </td>
                            <td>{{ new Date(cr.created_at).toLocaleDateString('id-ID') }}</td>
                            <td>
                                <Link
                                    :href="route('change-requests.show', cr.id)"
                                    class="btn"
                                    :class="isInitiator && cr.status === 'REJECT' ? 'btn-primary' : 'btn-secondary'"
                                    style="padding: 6px 12px; font-size: 0.8rem; font-weight: 500;"
                                >
                                    {{ isQA && (cr.status === 'OPEN' || cr.status === 'IN REVIEW') ? '🛠️ Review' : (isInitiator && cr.status === 'REJECT' ? '✍️ Revisi' : '🔍 Detail') }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="changeRequests.links.length > 3" style="display: flex; justify-content: center; gap: 8px; margin-top: 10px;">
                <Link
                    v-for="link in changeRequests.links"
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
