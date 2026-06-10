<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditMode = ref(false);
const editingUserId = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'initiator',
});

// Debounce/watch search to filter users
let searchTimeout;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('superadmin.users'), { search: val }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const openCreateModal = () => {
    isEditMode.value = false;
    editingUserId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditMode.value = true;
    editingUserId.value = user.id;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.password = ''; // leave blank by default
    form.role = user.role;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditMode.value) {
        form.put(route('superadmin.users.update', editingUserId.value), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('superadmin.users.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    }
};

const deleteUser = (user) => {
    if (confirm(`Apakah Anda yakin ingin menghapus akun ${user.name}?`)) {
        router.delete(route('superadmin.users.destroy', user.id), {
            onError: (errors) => {
                window.dispatchEvent(new CustomEvent('qms-notification', {
                    detail: {
                        type: 'error',
                        title: 'Gagal Menghapus',
                        message: errors.error || 'Gagal menghapus user.'
                    }
                }));
            }
        });
    }
};
</script>

<template>
    <Head title="Kelola Akun - QMS Portal" />

    <AuthenticatedLayout>
        <template #header>
            👥 Kelola Akun Pengguna
        </template>

        <div class="fade-in" style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Actions & Search -->
            <div class="flex-between">
                <div style="position: relative; width: 320px;">
                    <input
                        type="text"
                        placeholder="Cari user (nama, email, role)..."
                        class="form-input"
                        v-model="search"
                        style="padding-left: 36px;"
                    />
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">🔍</span>
                </div>

                <button @click="openCreateModal" class="btn btn-primary">
                    ➕ Tambah Pengguna Baru
                </button>
            </div>

            <!-- Users Table -->
            <div class="table-wrapper">
                <table class="qms-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Lengkap</th>
                            <th>Alamat Email</th>
                            <th>Role Pengguna</th>
                            <th>Terdaftar Pada</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id">
                            <td>#{{ user.id }}</td>
                            <td style="font-weight: 600; color: var(--text-primary);">{{ user.name }}</td>
                            <td>{{ user.email }}</td>
                            <td>
                                <span class="status-badge" :class="user.role === 'superadmin' ? 'badge-in_progress' : (user.role === 'qa' ? 'badge-approved' : 'badge-open')">
                                    {{ user.role === 'superadmin' ? 'Super Admin' : (user.role === 'qa' ? 'QA / Reviewer' : 'Initiator') }}
                                </span>
                            </td>
                            <td>{{ new Date(user.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}</td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button @click="openEditModal(user)" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                                        ✏️ Edit
                                    </button>
                                    <button @click="deleteUser(user)" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem;" :disabled="user.id === $page.props.auth.user.id">
                                        🗑️ Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                Tidak ada data pengguna ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.links && users.links.length > 3" style="display: flex; justify-content: center; gap: 6px; margin-top: 10px;">
                <button
                    v-for="(link, i) in users.links"
                    :key="i"
                    @click="link.url ? router.get(link.url) : null"
                    v-html="link.label"
                    class="btn btn-secondary"
                    :class="{ active: link.active }"
                    style="padding: 8px 14px; font-size: 0.850rem;"
                    :disabled="!link.url"
                />
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showModal" style="position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.65); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(8px);" @click.self="showModal = false">
                    <div class="scale-up-anim qms-card" style="width: 100%; max-width: 480px; box-shadow: var(--hover-shadow); border-radius: 16px; padding: 28px;">
                        <div class="flex-between" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px;">
                            <h3 style="font-size: 1.25rem; margin: 0; color: var(--text-primary); font-family: var(--font-outfit); font-weight: 700;">
                                {{ isEditMode ? '✍️ Edit Pengguna' : '👥 Tambah Pengguna Baru' }}
                            </h3>
                            <button @click="showModal = false" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
                        </div>

                        <form @submit.prevent="submitForm">
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-input" v-model="form.name" required placeholder="Masukkan nama lengkap..." />
                                <div v-if="form.errors.name" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.name }}</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Alamat Email</label>
                                <input type="email" class="form-input" v-model="form.email" required placeholder="contoh@qms.com" />
                                <div v-if="form.errors.email" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.email }}</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Password
                                    <span v-if="isEditMode" style="font-weight: normal; font-size: 0.8rem; color: var(--text-muted);"> (kosongkan jika tidak ingin diubah)</span>
                                </label>
                                <input type="password" class="form-input" v-model="form.password" :required="!isEditMode" placeholder="••••••••" />
                                <div v-if="form.errors.password" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.password }}</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Role Pengguna</label>
                                <select class="form-select" v-model="form.role">
                                    <option value="initiator">Initiator</option>
                                    <option value="qa">QA / Reviewer</option>
                                    <option value="superadmin">Super Admin</option>
                                </select>
                                <div v-if="form.errors.role" style="color: #ef4444; font-size: 0.8rem; margin-top: 4px;">{{ form.errors.role }}</div>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                                <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    {{ form.processing ? 'Menyimpan...' : (isEditMode ? 'Simpan Perubahan' : 'Tambah Pengguna') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
