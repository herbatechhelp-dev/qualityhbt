<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    filePath: {
        type: String,
        required: true,
        default: '',
    },
    title: {
        type: String,
        default: 'Lampiran Berkas',
    }
});

const emit = defineEmits(['close']);

const fileUrl = computed(() => {
    if (!props.filePath) return '';
    return '/storage/' + props.filePath;
});

const isPdf = computed(() => {
    if (!props.filePath) return false;
    return props.filePath.toLowerCase().endsWith('.pdf');
});

const isImage = computed(() => {
    if (!props.filePath) return false;
    const path = props.filePath.toLowerCase();
    return path.endsWith('.jpg') || path.endsWith('.jpeg') || path.endsWith('.png') || path.endsWith('.gif') || path.endsWith('.svg') || path.endsWith('.webp');
});

const close = () => {
    emit('close');
};

const handleEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="show" style="position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.75); display: flex; align-items: center; justify-content: center; z-index: 10000; backdrop-filter: blur(10px);" @click.self="close">
                <div class="scale-up-anim" style="background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 20px; width: 92%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: var(--hover-shadow); overflow: hidden; position: relative;">
                    <!-- Header -->
                    <div class="flex-between" style="border-bottom: 1px solid var(--border-color); padding: 20px 28px; background-color: rgba(15, 23, 42, 0.02); gap: 16px;">
                        <div style="display: flex; flex-direction: column; gap: 4px; max-width: 70%;">
                            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin: 0; font-family: var(--font-outfit); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                📎 {{ title }}
                            </h3>
                            <span style="font-size: 0.75rem; color: var(--text-muted); word-break: break-all; font-family: monospace;">
                                {{ filePath }}
                            </span>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                            <a :href="fileUrl" download class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 6px; border-radius: 8px; text-decoration: none;">
                                📥 Unduh File
                            </a>
                            <button @click="close" style="background: rgba(15, 23, 42, 0.05); border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.backgroundColor='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444';" onmouseout="this.style.backgroundColor='rgba(15, 23, 42, 0.05)'; this.style.color='var(--text-secondary)';">&times;</button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div style="flex: 1; padding: 24px; display: flex; align-items: center; justify-content: center; background-color: var(--bg-primary); overflow: auto; min-height: 400px;">
                        <iframe v-if="isPdf" :src="fileUrl" style="width: 100%; height: 60vh; border: none; border-radius: 8px; background-color: var(--bg-secondary);"></iframe>
                        
                        <div v-else-if="isImage" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; max-height: 60vh; overflow: hidden;">
                            <img :src="fileUrl" :alt="title" style="max-width: 100%; max-height: 60vh; object-fit: contain; border-radius: 8px; box-shadow: var(--card-shadow);" />
                        </div>
                        
                        <div v-else style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 16px; padding: 48px 24px; color: var(--text-secondary);">
                            <span style="font-size: 3.5rem;">📁</span>
                            <h4 style="margin: 0; font-weight: 700; font-size: 1.15rem; color: var(--text-primary);">Pratinjau Tidak Tersedia</h4>
                            <p style="margin: 0; font-size: 0.875rem; max-width: 380px; line-height: 1.5; color: var(--text-muted);">
                                Tipe berkas ini tidak didukung untuk peninjauan langsung di browser. Silakan unduh berkas untuk membukanya secara lokal.
                            </p>
                            <a :href="fileUrl" download class="btn btn-primary" style="padding: 10px 24px; font-weight: 600; margin-top: 8px; border-radius: 8px; text-decoration: none;">
                                📥 Unduh Sekarang
                            </a>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style="border-top: 1px solid var(--border-color); padding: 16px 28px; display: flex; justify-content: flex-end; background-color: rgba(15, 23, 42, 0.02);">
                        <button @click="close" class="btn btn-secondary" style="padding: 10px 24px; font-weight: 600; border-radius: 8px;">
                            Tutup Pratinjau
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
