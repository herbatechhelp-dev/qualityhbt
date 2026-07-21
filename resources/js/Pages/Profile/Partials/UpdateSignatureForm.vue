<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const signatureInput = ref(null);

const form = useForm({
    signature: null,
});

const rawImage = ref(null);
const rawFileName = ref('signature.png');
const previewUrl = ref(null);
const scaleRatio = ref(100); // 50% to 150%
const autoTrim = ref(true);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    rawFileName.value = file.name;
    const reader = new FileReader();
    reader.onload = (evt) => {
        const img = new Image();
        img.onload = () => {
            rawImage.value = img;
            processSignature();
        };
        img.src = evt.target.result;
    };
    reader.readAsDataURL(file);
};

const processSignature = () => {
    if (!rawImage.value) return;
    const img = rawImage.value;
    
    let sourceCanvas = document.createElement('canvas');
    sourceCanvas.width = img.width;
    sourceCanvas.height = img.height;
    let ctx = sourceCanvas.getContext('2d');
    ctx.drawImage(img, 0, 0);

    let finalCanvas = sourceCanvas;

    if (autoTrim.value) {
        const imgData = ctx.getImageData(0, 0, sourceCanvas.width, sourceCanvas.height);
        const data = imgData.data;

        let minX = sourceCanvas.width, minY = sourceCanvas.height, maxX = 0, maxY = 0;
        let found = false;

        for (let y = 0; y < sourceCanvas.height; y++) {
            for (let x = 0; x < sourceCanvas.width; x++) {
                const idx = (y * sourceCanvas.width + x) * 4;
                const r = data[idx];
                const g = data[idx + 1];
                const b = data[idx + 2];
                const a = data[idx + 3];

                // Detect signature strokes (non-white and non-transparent)
                if (a > 20 && (r < 240 || g < 240 || b < 240)) {
                    if (x < minX) minX = x;
                    if (x > maxX) maxX = x;
                    if (y < minY) minY = y;
                    if (y > maxY) maxY = y;
                    found = true;
                }
            }
        }

        if (found) {
            const pad = 8;
            minX = Math.max(0, minX - pad);
            minY = Math.max(0, minY - pad);
            maxX = Math.min(sourceCanvas.width, maxX + pad);
            maxY = Math.min(sourceCanvas.height, maxY + pad);

            const cropW = maxX - minX;
            const cropH = maxY - minY;

            const trimmed = document.createElement('canvas');
            trimmed.width = cropW;
            trimmed.height = cropH;
            const trimmedCtx = trimmed.getContext('2d');
            trimmedCtx.drawImage(sourceCanvas, minX, minY, cropW, cropH, 0, 0, cropW, cropH);
            finalCanvas = trimmed;
        }
    }

    // Apply scaling
    const scale = scaleRatio.value / 100;
    const scaledW = Math.round(finalCanvas.width * scale);
    const scaledH = Math.round(finalCanvas.height * scale);

    const scaledCanvas = document.createElement('canvas');
    scaledCanvas.width = scaledW;
    scaledCanvas.height = scaledH;
    const scaledCtx = scaledCanvas.getContext('2d');
    scaledCtx.drawImage(finalCanvas, 0, 0, scaledW, scaledH);

    scaledCanvas.toBlob((blob) => {
        if (!blob) return;
        const processedFile = new File([blob], rawFileName.value, { type: 'image/png' });
        form.signature = processedFile;
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }
        previewUrl.value = URL.createObjectURL(blob);
    }, 'image/png');
};

const submitSignature = () => {
    form.post(route('profile.signature.update'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            rawImage.value = null;
            previewUrl.value = null;
            if (signatureInput.value) {
                signatureInput.value.value = '';
            }
        },
    });
};

const deleteSignature = () => {
    if (confirm('Apakah Anda yakin ingin menghapus tanda tangan Anda?')) {
        router.delete(route('profile.signature.destroy'));
    }
};
</script>

<script>
export default {
    name: 'UpdateSignatureForm'
}
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tanda Tangan Digital</h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Unggah gambar tanda tangan fisik Anda (disarankan berformat PNG transparan) untuk disematkan otomatis pada form Change Request.
            </p>
        </header>

        <!-- Current Signature Preview -->
        <div v-if="user.signature_path" class="space-y-2">
            <InputLabel value="Tanda Tangan Saat Ini" />
            <div style="display: inline-flex; flex-direction: column; gap: 8px;">
                <div style="background-color: white; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 12px; display: inline-flex; align-items: center; justify-content: center; min-height: 100px;">
                    <img 
                        :src="'/storage/' + user.signature_path" 
                        alt="Tanda Tangan" 
                        style="max-height: 80px; max-width: 200px; object-fit: contain;" 
                    />
                </div>
                <button 
                    type="button" 
                    @click="deleteSignature" 
                    class="btn btn-secondary" 
                    style="background-color: #ef4444; border-color: #ef4444; color: white; padding: 6px 12px; font-size: 0.75rem; font-weight: 600; border-radius: 6px; cursor: pointer; align-self: flex-start;"
                >
                    🗑️ Hapus Tanda Tangan
                </button>
            </div>
        </div>

        <div v-else class="space-y-2">
            <span style="font-size: 0.85rem; color: #9ca3af; font-style: italic;">
                Belum ada tanda tangan yang diunggah. Tampilan form akan menggunakan verifikasi digital teks.
            </span>
        </div>

        <!-- Upload Form -->
        <form @submit.prevent="submitSignature" class="space-y-4 max-w-xl">
            <div>
                <InputLabel for="signature" value="Pilih Gambar Tanda Tangan" />
                <input 
                    id="signature" 
                    type="file" 
                    ref="signatureInput"
                    @change="handleFileChange"
                    accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                    required
                />
                <InputError class="mt-2" :message="form.errors.signature" />
            </div>

            <!-- Signature Cropper & Scaling Settings -->
            <div v-if="rawImage" class="p-4 border border-blue-200 bg-blue-50 dark:bg-gray-800 dark:border-gray-700 rounded-lg space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Pengaturan Tanda Tangan</span>
                    <label class="inline-flex items-center cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                        <input type="checkbox" v-model="autoTrim" @change="processSignature" class="sr-only peer">
                        <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="ms-2">Auto-Crop Margin Kosong</span>
                    </label>
                </div>

                <div>
                    <div class="flex justify-between items-center text-xs mb-1 text-gray-700 dark:text-gray-300">
                        <span>Skala / Ukuran Tanda Tangan:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ scaleRatio }}%</span>
                    </div>
                    <input 
                        type="range" 
                        min="50" 
                        max="180" 
                        step="5" 
                        v-model.number="scaleRatio" 
                        @input="processSignature"
                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700" 
                    />
                </div>

                <!-- Box Preview Simulasi Tanda Tangan Cetak -->
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Pratinjau Hasil Pada Kotak Dokumen Cetak:</span>
                    <div style="background-color: white; border: 1px solid #000; border-radius: 4px; padding: 8px; width: 220px; min-height: 110px; display: flex; flex-direction: column; justify-content: space-between; align-items: center; text-align: center;">
                        <span style="font-size: 10px; font-weight: bold; align-self: flex-start; color: #000;">Nama & Tanda Tangan Inisiator :</span>
                        <img 
                            v-if="previewUrl"
                            :src="previewUrl" 
                            alt="Pratinjau Hasil Crop" 
                            style="max-height: 70px; max-width: 160px; object-fit: contain; display: block; margin: 4px auto;" 
                        />
                        <div style="font-size: 10px; font-weight: bold; text-decoration: underline; color: #000;">{{ user.name }}</div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Mengunggah...' : 'Unggah Tanda Tangan' }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Berhasil diperbarui.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
