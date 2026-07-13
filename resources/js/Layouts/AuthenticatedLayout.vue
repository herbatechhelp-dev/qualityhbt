<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import FeedbackOverlay from '@/Components/FeedbackOverlay.vue';

const isDark = ref(false);
const showProfileDropdown = ref(false);
const showSidebar = ref(false);

const toggleTheme = () => {
    if (isDark.value) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        isDark.value = false;
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
        isDark.value = true;
    }
};

onMounted(() => {
    isDark.value = localStorage.getItem('theme') === 'dark' || 
        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});

// Auto close sidebar when route changes on mobile
router.on('navigate', () => {
    showSidebar.value = false;
});

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <div class="qms-layout">
        <!-- Sidebar Backdrop for Mobile -->
        <div 
            v-if="showSidebar" 
            class="qms-sidebar-backdrop" 
            @click="showSidebar = false"
        ></div>

        <!-- Sidebar -->
        <aside class="qms-sidebar" :class="{ 'mobile-open': showSidebar }">
            <div class="qms-sidebar-logo" style="display: flex; align-items: center; justify-content: flex-start; gap: 10px; min-height: 70px; padding: 0 20px;">
                <template v-if="$page.props.settings.app_logo_type === 'image' && $page.props.settings.app_logo_path">
                    <img :src="'/storage/' + $page.props.settings.app_logo_path" alt="Logo" style="max-height: 38px; max-width: 50px; object-fit: contain;" />
                    <span style="font-weight: 800; font-size: 1.1rem; letter-spacing: -0.02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; background: linear-gradient(135deg, var(--text-primary) 30%, var(--accent-color) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        {{ $page.props.settings.app_logo }}
                    </span>
                </template>
                <template v-else>
                    <span style="font-weight: 800; font-size: 1.1rem; letter-spacing: -0.02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; background: linear-gradient(135deg, var(--text-primary) 30%, var(--accent-color) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        {{ $page.props.settings.app_logo }}
                    </span>
                </template>
            </div>
            
            <nav class="qms-sidebar-menu">
                <Link :href="route('dashboard')" class="qms-sidebar-item" :class="{ active: route().current('dashboard') }">
                    📊 Dashboard
                </Link>
                <Link :href="route('documents.index')" class="qms-sidebar-item" :class="{ active: route().current('documents.*') }">
                    📄 Master List Dokumen
                </Link>
                <Link :href="route('change-requests.index')" class="qms-sidebar-item" :class="{ active: route().current('change-requests.*') }">
                    🔄 Change Request (CR)
                </Link>
                <div>
                    <div class="qms-sidebar-item" :class="{ active: route().current('deviations.*') || route().current('deviations.investigations.*') }" style="font-weight: 600; cursor: default;">
                        ⚠️ Deviation Report
                    </div>
                    <div style="padding-left: 12px; display: flex; flex-direction: column; gap: 4px; margin-top: 4px; border-left: 2px solid var(--border-color); margin-left: 12px;">
                        <Link :href="route('deviations.index')" class="qms-sidebar-item" style="padding: 8px 12px; font-size: 0.85rem;" :class="{ active: route().current('deviations.*') && !route().current('deviations.investigations.*') }">
                            📋 Logbook Deviasi
                        </Link>
                        <Link :href="route('deviations.investigations.index')" class="qms-sidebar-item" style="padding: 8px 12px; font-size: 0.85rem;" :class="{ active: route().current('deviations.investigations.*') }">
                            🔍 Penyelidikan
                        </Link>
                    </div>
                </div>
                <Link :href="route('capas.index')" class="qms-sidebar-item" :class="{ active: route().current('capas.*') }">
                    ✅ CAPA Management
                </Link>
                <template v-if="$page.props.auth.user.role === 'superadmin'">
                    <div style="margin: 16px 0 8px 0; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; padding-left: 16px;">
                        Administrasi
                    </div>
                    <Link :href="route('superadmin.users')" class="qms-sidebar-item" :class="{ active: route().current('superadmin.users') }">
                        👥 Kelola Akun
                    </Link>
                    <Link :href="route('superadmin.settings')" class="qms-sidebar-item" :class="{ active: route().current('superadmin.settings') }">
                        ⚙️ Pengaturan Sistem
                    </Link>
                </template>
            </nav>

            <div style="padding: 24px; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px;">
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Logged in as:</div>
                <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">{{ user.name }}</div>
                <span class="status-badge" :class="user.role === 'superadmin' ? 'badge-in_progress' : (['qa', 'head_of_quality', 'operational_manager', 'general_manager'].includes(user.role) ? 'badge-approved' : 'badge-open')" style="width: fit-content; margin-top: 4px;">
                    {{ 
                        user.role === 'superadmin' ? 'Super Admin' : 
                        (user.role === 'qa' ? 'QA / Reviewer' : 
                        (user.role === 'head_of_quality' ? 'Head of Quality' : 
                        (user.role === 'operational_manager' ? 'Operational Manager' : 
                        (user.role === 'general_manager' ? 'General Manager' : 'Initiator')))) 
                    }}
                </span>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="qms-main">
            <!-- Header -->
            <header class="qms-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button 
                        @click="showSidebar = !showSidebar" 
                        class="mobile-menu-toggle"
                        aria-label="Toggle Sidebar"
                    >
                        ☰
                    </button>
                    <div class="qms-header-title">
                        <slot name="header" />
                    </div>
                </div>
                
                <div class="qms-header-actions">
                    <!-- Theme Toggle -->
                    <button @click="toggleTheme" class="theme-toggle-btn" title="Toggle Light/Dark Mode">
                        <span v-if="isDark">☀️ Light Mode</span>
                        <span v-else>🌙 Night Mode</span>
                    </button>

                    <!-- User Actions -->
                    <div style="position: relative;">
                        <button @click="showProfileDropdown = !showProfileDropdown" class="btn btn-secondary flex-gap-10">
                            👤 Menu User
                        </button>
                        <div v-if="showProfileDropdown" style="position: absolute; right: 0; top: 120%; background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--card-shadow); display: flex; flex-direction: column; width: 160px; z-index: 50;">
                            <Link :href="route('profile.edit')" style="padding: 12px 16px; text-decoration: none; color: var(--text-primary); font-size: 0.9rem;" class="qms-sidebar-item">
                                Profil Saya
                            </Link>
                            <Link :href="route('logout')" method="post" as="button" style="padding: 12px 16px; text-decoration: none; color: #ef4444; font-size: 0.9rem; text-align: left; background: none; border: none; cursor: pointer; width: 100%;" class="qms-sidebar-item">
                                Log Out
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="qms-content fade-in">
                <slot />
            </main>
        </div>

        <!-- Global Loading & Validation Alert Overlay -->
        <FeedbackOverlay />
    </div>
</template>
