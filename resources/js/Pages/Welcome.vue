<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    canLogin: Boolean,
});

const isDark = ref(false);

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
</script>

<template>
    <Head title="Selamat Datang - QMS Terintegrasi" />

    <div style="min-height: 100vh; background-color: var(--bg-primary); color: var(--text-primary); transition: background-color 0.3s, color 0.3s; display: flex; flex-direction: column;">
        <!-- Header -->
        <header class="qms-header" style="padding: 0 40px; display: flex; align-items: center;">
            <div class="qms-sidebar-logo" style="padding: 0; border: none; font-size: 1.35rem; display: flex; align-items: center; gap: 10px; min-height: 50px;">
                <template v-if="$page.props.settings.app_logo_type === 'image' && $page.props.settings.app_logo_path">
                    <img :src="'/storage/' + $page.props.settings.app_logo_path" alt="Logo" style="max-height: 40px; max-width: 50px; object-fit: contain;" />
                    <span style="font-weight: 800; font-family: var(--font-outfit); letter-spacing: -0.02em; background: linear-gradient(135deg, var(--text-primary) 30%, var(--accent-color) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        {{ $page.props.settings.app_logo }}
                    </span>
                </template>
                <template v-else>
                    <span style="font-weight: 800; font-family: var(--font-outfit); letter-spacing: -0.02em; background: linear-gradient(135deg, var(--text-primary) 30%, var(--accent-color) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        {{ $page.props.settings.app_logo }}
                    </span>
                </template>
            </div>
            
            <div class="qms-header-actions">
                <!-- Theme Toggle -->
                <button @click="toggleTheme" class="theme-toggle-btn">
                    <span v-if="isDark">☀️ Light Mode</span>
                    <span v-else>🌙 Night Mode</span>
                </button>

                <!-- Navigation link -->
                <div v-if="canLogin">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-primary">
                        Masuk Dashboard →
                    </Link>
                    <Link v-else :href="route('login')" class="btn btn-primary">
                        Masuk Portal QMS
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main style="flex-grow: 1; display: flex; flex-direction: column; gap: 48px; padding: 60px 40px; max-width: 1100px; margin: 0 auto; width: 100%;" class="fade-in">
            <div style="text-align: center; display: flex; flex-direction: column; align-items: center; gap: 16px; margin-top: 20px;">
                <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--accent-color); letter-spacing: 0.1em; background-color: var(--status-open-bg); padding: 6px 16px; border-radius: 9999px;">
                    Confidential & Secure
                </span>
                <h1 style="font-size: 3rem; font-weight: 800; font-family: var(--font-outfit); line-height: 1.15; background: linear-gradient(135deg, var(--text-primary) 30%, var(--accent-color) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    Integrated Quality Management System
                </h1>
                <p style="color: var(--text-secondary); font-size: 1.15rem; max-width: 650px; line-height: 1.6;">
                    Digitalisasi dan integrasi pemastian mutu terpadu (Quality Assurance) untuk efisiensi operasional dan kepatuhan regulasi secara menyeluruh.
                </p>
                <div style="margin-top: 16px;">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-primary" style="padding: 14px 28px; font-size: 1rem;">
                        Masuk Dashboard Utama →
                    </Link>
                    <Link v-else :href="route('login')" class="btn btn-primary" style="padding: 14px 28px; font-size: 1rem;">
                        Mulai Masuk ke Portal QMS →
                    </Link>
                </div>
            </div>

            <!-- 4 Pillars Section -->
            <div style="display: flex; flex-direction: column; gap: 20px; margin-top: 20px;">
                <h2 style="font-size: 1.5rem; text-align: center; margin-bottom: 12px; font-weight: 700;">
                    4 Pilar Utama Pemastian Mutu
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px;">
                    <div class="qms-card">
                        <div style="font-size: 2rem; margin-bottom: 12px;">📄</div>
                        <h3 style="font-size: 1.15rem; margin-bottom: 10px; color: var(--text-primary);">Master List Dokumen</h3>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5;">
                            Repositori terpusat seluruh dokumen mutu aktif. Pengunggahan file spreadsheet (.xlsx/.csv) massal mempercepat import data logbook dokumen.
                        </p>
                    </div>

                    <div class="qms-card">
                        <div style="font-size: 2rem; margin-bottom: 12px;">🔄</div>
                        <h3 style="font-size: 1.15rem; margin-bottom: 10px; color: var(--text-primary);">Change Request (CR)</h3>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5;">
                            Pengusulan perubahan dokumen/proses melalui jalur CRA (analisis FMEA dengan RPN otomatis) atau CRB (tanpa risiko), dievaluasi langsung oleh QA.
                        </p>
                    </div>

                    <div class="qms-card">
                        <div style="font-size: 2rem; margin-bottom: 12px;">⚠️</div>
                        <h3 style="font-size: 1.15rem; margin-bottom: 10px; color: var(--text-primary);">Deviation Report</h3>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5;">
                            Pencatatan temuan ketidaksesuaian mutu di lapangan lengkap dengan berkas bukti untuk menjaga ketertelusuran berkas audit log.
                        </p>
                    </div>

                    <div class="qms-card">
                        <div style="font-size: 2rem; margin-bottom: 12px;">✅</div>
                        <h3 style="font-size: 1.15rem; margin-bottom: 10px; color: var(--text-primary);">CAPA Management</h3>
                        <p style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5;">
                            Tindakan korektif dan preventif lanjutan yang dibuat otomatis saat laporan deviasi disetujui. Membantu PIC memantau batas penyelesaian.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer style="padding: 24px; text-align: center; border-top: 1px solid var(--border-color); font-size: 0.825rem; color: var(--text-muted); background-color: var(--bg-secondary);">
            QMS Portal v1.0 · Confidential Document System · Hak Cipta Dilindungi
        </footer>
    </div>
</template>
