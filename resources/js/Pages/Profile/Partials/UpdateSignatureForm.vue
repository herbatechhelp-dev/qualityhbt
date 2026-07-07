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

const handleFileChange = (e) => {
    form.signature = e.target.files[0];
};

const submitSignature = () => {
    form.post(route('profile.signature.update'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
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
// Separate block for script setup or standard script tags
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
