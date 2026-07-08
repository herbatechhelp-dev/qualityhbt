<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();

// Loading states
const isLoading = ref(false);
const loadingMessage = ref('Memproses Permintaan...');

// Animated Alert States
const alertVisible = ref(false);
const alertType = ref('success'); // 'success' or 'error'
const alertTitle = ref('');
const alertMessage = ref('');

let autoCloseTimeout = null;

// Listen to Inertia router events for global loading state
let unsubscribeStart = null;
let unsubscribeFinish = null;

onMounted(() => {
    unsubscribeStart = router.on('start', (event) => {
        isLoading.value = true;
        const url = event.detail.visit.url.pathname;

        if (url.includes('/sync-sheets')) {
            loadingMessage.value = 'Sinkronisasi Google Sheets...';
        } else if (url.includes('/import')) {
            loadingMessage.value = 'Mengimpor Berkas...';
        } else if (url.includes('/evaluate') || url.includes('/decide') || url.includes('/verify')) {
            loadingMessage.value = 'Menyimpan Keputusan QA...';
        } else if (url.includes('/login') || url.includes('/register')) {
            loadingMessage.value = 'Autentikasi Akun...';
        } else {
            loadingMessage.value = 'Memproses Permintaan...';
        }
    });

    unsubscribeFinish = router.on('finish', () => {
        isLoading.value = false;
    });

    // Initial check for flash messages on mount
    checkFlashMessages();
});

onUnmounted(() => {
    if (unsubscribeStart) unsubscribeStart();
    if (unsubscribeFinish) unsubscribeFinish();
    if (autoCloseTimeout) clearTimeout(autoCloseTimeout);
});

// Check flash messages from props
const checkFlashMessages = () => {
    const flash = page.props.flash;
    if (!flash) return;

    if (flash.success) {
        triggerAlert('success', 'Berhasil!', flash.success);
        // Clear flash success to avoid re-triggering on page refresh
        flash.success = null;
    } else if (flash.error) {
        triggerAlert('error', 'Terjadi Kesalahan', flash.error);
        // Clear flash error
        flash.error = null;
    }
};

// Watch for page prop updates to capture flash messages reactively
watch(() => page.props.flash, () => {
    checkFlashMessages();
}, { deep: true });

// Listen to custom window events for manual triggers
const handleManualNotification = (e) => {
    const { type, title, message } = e.detail;
    triggerAlert(type, title, message);
};

onMounted(() => {
    window.addEventListener('qms-notification', handleManualNotification);
});

onUnmounted(() => {
    window.removeEventListener('qms-notification', handleManualNotification);
});

const triggerAlert = (type, title, message) => {
    if (autoCloseTimeout) clearTimeout(autoCloseTimeout);

    alertType.value = type;
    alertTitle.value = title;
    alertMessage.value = message;
    alertVisible.value = true;

    // Automatically close after 3 seconds
    autoCloseTimeout = setTimeout(() => {
        closeAlert();
    }, 3200);
};

const closeAlert = () => {
    alertVisible.value = false;
};
</script>

<template>
    <div class="feedback-root">
        <!-- ================= 1. GLOBAL LOADING OVERLAY ================= -->
        <Transition name="fade">
            <div v-if="isLoading" class="loading-overlay">
                <div class="loading-card">
                    <div class="loader-container">
                        <!-- Main spinning ring -->
                        <div class="loading-ring"></div>
                        <!-- Secondary pulsing core -->
                        <div class="loading-core"></div>
                    </div>
                    <p class="loading-text">{{ loadingMessage }}</p>
                    <span class="loading-subtext">Mohon tunggu sebentar</span>
                </div>
            </div>
        </Transition>

        <!-- ================= 2. ANIMATED VALIDATION ALERT MODAL ================= -->
        <Transition name="scale-fade">
            <div v-if="alertVisible" class="alert-overlay" @click.self="closeAlert">
                <div class="alert-card" :class="alertType">
                    <!-- Close button -->
                    <button class="alert-close-btn" @click="closeAlert">&times;</button>

                    <!-- Success Animation (Drawing Checkmark) -->
                    <div v-if="alertType === 'success'" class="icon-wrapper">
                        <svg class="checkmark-svg" viewBox="0 0 52 52">
                            <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" />
                            <path class="checkmark-check" fill="none" d="M14.1 27.2 l7.1 7.2 16.7 -16.8" />
                        </svg>
                    </div>

                    <!-- Error Animation (Drawing Cross) -->
                    <div v-else class="icon-wrapper">
                        <svg class="cross-svg" viewBox="0 0 52 52">
                            <circle class="cross-circle" cx="26" cy="26" r="25" fill="none" />
                            <path class="cross-line-1" fill="none" d="M16 16 l20 20" />
                            <path class="cross-line-2" fill="none" d="M36 16 l-20 20" />
                        </svg>
                    </div>

                    <!-- Alert message texts -->
                    <div class="alert-content">
                        <h4 class="alert-title">{{ alertTitle }}</h4>
                        <p class="alert-message">{{ alertMessage }}</p>
                    </div>

                    <!-- Bottom glow bar -->
                    <div class="alert-glow-bar"></div>
                </div>
            </div>
        </Transition>
    </div>
</template>


