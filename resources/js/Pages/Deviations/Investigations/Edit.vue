<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    deviation: {
        type: Object,
        required: true,
    },
});

const showPrintPreview = ref(false);
const previewUrl = ref('');
const previewTitle = ref('');

const openPrintPreview = (url, title) => {
    previewUrl.value = url;
    previewTitle.value = title;
    showPrintPreview.value = true;
};

const printIframe = () => {
    const iframe = document.getElementById('print-iframe');
    if (iframe) {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }
};

watch(
    () => showPrintPreview.value,
    (newVal) => {
        if (newVal) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

// Define form state
const form = useForm({
    fishbone_machine: props.deviation.fishbone_machine || 'Pengecekan mesin & alat penunjang operasional produksi.',
    fishbone_man: props.deviation.fishbone_man || 'Pemeriksaan kepatuhan personalia & pelatihan higienitas.',
    fishbone_method: props.deviation.fishbone_method || 'Evaluasi prosedur kerja standard (SOP) saat kejadian.',
    fishbone_milieu: props.deviation.fishbone_milieu || 'Pemantauan kondisi lingkungan ruang pengolahan/kelas.',
    fishbone_measurement: props.deviation.fishbone_measurement || 'Verifikasi alat ukur, kalibrasi instrumen, dan IPC.',
    fishbone_materials: props.deviation.fishbone_materials || 'Analisis bahan awal, kemasan primer, & identitas bets.',
    root_cause: props.deviation.root_cause || 'Berdasarkan investigasi fishbone, akar masalah disebabkan oleh:\n- Kriteria kesesuaian operasional alat/mesin yang belum terkalibrasi berkala.\n- Diperlukan peningkatan pengawasan In Process Control (IPC).',
    risk_identification_details: props.deviation.risk_identification_details || '1. Potensi imbas pada bets produk terkait yang diproses pada hari yang sama.\n2. Risiko penurunan spesifikasi mutu atau stabilitas produk akhir.',
    risk_analysis_details: props.deviation.risk_analysis_details || 'Kajian risiko dilakukan menggunakan Failure Mode and Effects Analysis (FMEA) untuk menghitung tingkat keparahan (S), frekuensi (O), dan kemampuan deteksi (D).',
    risk_analysis: props.deviation.risk_analysis || [],
});

const addRiskRow = () => {
    form.risk_analysis.push({
        risk_identification: '',
        potensiasi_cause:    '',
        s: 1,
        o: 1,
        d: 1,
        rpn: 1,
        risk_control: '',
        action: '',
        s_after: 1,
        o_after: 1,
        d_after: 1,
        rpn_after: 1,
    });
};

const removeRiskRow = (idx) => {
    form.risk_analysis.splice(idx, 1);
};

const updateRpn = (row) => {
    row.rpn = (parseInt(row.s) || 1) * (parseInt(row.o) || 1) * (parseInt(row.d) || 1);
    row.rpn_after = (parseInt(row.s_after) || 1) * (parseInt(row.o_after) || 1) * (parseInt(row.d_after) || 1);
};

const getRpnClass = (rpn) => {
    if (rpn <= 50)  return 'rpn-low';
    if (rpn <= 200) return 'rpn-medium';
    return 'rpn-high';
};

const submitForm = () => {
    form.post(route('deviations.investigations.update', props.deviation.id), {
        onSuccess: () => {
            // optional success hook
        }
    });
};
</script>

<template>
    <Head :title="'Edit Penyelidikan: ' + deviation.deviation_number" />

    <AuthenticatedLayout>
        <template #header>
            🔍 Edit Penyelidikan: {{ deviation.deviation_number }}
        </template>

        <div style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">

            <!-- Top Nav & Actions -->
            <div class="flex-between">
                <Link :href="route('deviations.investigations.index')" class="btn btn-secondary">
                    ← Kembali ke Daftar
                </Link>
                <div style="display: flex; gap: 10px;">
                    <button type="button" @click="openPrintPreview(route('deviations.print-investigation', deviation.id), 'Form Penyelidikan Ketidaksesuaian')" class="btn btn-secondary" style="font-size:0.85rem; display:inline-flex; align-items:center; gap:6px; background-color: #3b82f6; border-color: #3b82f6; color: white;">
                        🖨️ Cetak Form Penyelidikan
                    </button>
                    <button @click="submitForm" class="btn btn-primary" :disabled="form.processing">
                        💾 Simpan Penyelidikan
                    </button>
                </div>
            </div>

            <form @submit.prevent="submitForm" style="display: contents;">

                <!-- ====== SECTION 1: Fishbone Diagram (6M) ====== -->
                <div class="qms-card">
                    <h3 style="font-size: 1.1rem; margin-bottom: 8px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                        🐟 Diagram Fishbone (Analisis Penyebab 6M)
                    </h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 20px;">
                        Isi analisis penyebab untuk masing-masing parameter 6M di bawah ini.
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div class="grid-2" style="gap: 16px;">
                            <div class="form-group">
                                <label class="form-label" style="font-weight: 600;">⚙️ Machine (Mesin/Peralatan)</label>
                                <textarea v-model="form.fishbone_machine" class="form-textarea" rows="2" placeholder="Detail pengecekan mesin..."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-weight: 600;">👥 Man (Personel/Manusia)</label>
                                <textarea v-model="form.fishbone_man" class="form-textarea" rows="2" placeholder="Detail pemeriksaan personel..."></textarea>
                            </div>
                        </div>

                        <div class="grid-2" style="gap: 16px;">
                            <div class="form-group">
                                <label class="form-label" style="font-weight: 600;">📋 Method/Process (Metode/Proses)</label>
                                <textarea v-model="form.fishbone_method" class="form-textarea" rows="2" placeholder="Evaluasi SOP/prosedur..."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-weight: 600;">🌍 Milieu (Lingkungan)</label>
                                <textarea v-model="form.fishbone_milieu" class="form-textarea" rows="2" placeholder="Pemantauan kondisi lingkungan..."></textarea>
                            </div>
                        </div>

                        <div class="grid-2" style="gap: 16px;">
                            <div class="form-group">
                                <label class="form-label" style="font-weight: 600;">📐 Measurement (Pengukuran/IPC)</label>
                                <textarea v-model="form.fishbone_measurement" class="form-textarea" rows="2" placeholder="Verifikasi alat ukur/IPC..."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-weight: 600;">📦 Materials (Bahan Awal/Kemasan)</label>
                                <textarea v-model="form.fishbone_materials" class="form-textarea" rows="2" placeholder="Analisis bahan baku/bets..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ====== SECTION 2: Akar Masalah & Identifikasi Risiko ====== -->
                <div class="qms-card">
                    <h3 style="font-size: 1.1rem; margin-bottom: 20px; color: var(--text-primary);">
                        📝 Kesimpulan &amp; Identifikasi Risiko
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600;">🔥 Akar Masalah (Root Cause)</label>
                            <textarea v-model="form.root_cause" class="form-textarea" rows="3" placeholder="Jelaskan akar masalah utama berdasarkan analisis fishbone..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600;">⚠️ Identifikasi Risiko</label>
                            <textarea v-model="form.risk_identification_details" class="form-textarea" rows="2" placeholder="Tuliskan poin-poin risiko yang teridentifikasi..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600;">📊 Pengantar Analisa Risiko</label>
                            <textarea v-model="form.risk_analysis_details" class="form-textarea" rows="2" placeholder="Kajian risiko FMEA..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- ====== SECTION 3: FMEA Risk Analysis Table ====== -->
                <div class="qms-card">
                    <div class="flex-between" style="margin-bottom: 20px;">
                        <div>
                            <h3 style="font-size: 1.1rem; color: var(--text-primary); margin: 0;">
                                🛡️ Kajian Risiko FMEA (Failure Mode and Effects Analysis)
                            </h3>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                Tentukan nilai Severity (S), Occurrence (O), dan Detection (D) dengan skala [1, 3, 9].
                            </span>
                        </div>
                        <button type="button" @click="addRiskRow" class="btn btn-secondary" style="font-size: 0.8rem; padding: 6px 12px;">
                            ➕ Tambah Baris
                        </button>
                    </div>

                    <div v-if="form.risk_analysis.length === 0" style="text-align: center; color: var(--text-muted); padding: 24px; border: 1px dashed var(--border-color); border-radius: 8px;">
                        Belum ada baris analisis risiko. Klik "+ Tambah Baris" untuk menambahkan.
                    </div>

                    <div v-for="(row, idx) in form.risk_analysis" :key="idx"
                        style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:10px;padding:16px;margin-bottom:12px;position:relative;">
                        
                        <div style="position:absolute;top:12px;right:12px;">
                            <button type="button" @click="removeRiskRow(idx)" class="btn btn-danger"
                                style="padding:4px 10px;font-size:0.75rem;">🗑️ Hapus</button>
                        </div>

                        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:12px;">
                            Risiko #{{ idx + 1 }}
                            <span style="margin-left:12px;font-size:0.8rem;" :class="getRpnClass(row.rpn)">
                                RPN = {{ row.rpn }}
                            </span>
                        </div>

                        <div class="grid-2" style="gap:12px;margin-bottom:12px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Risk Identification</label>
                                <input type="text" v-model="row.risk_identification" class="form-input" placeholder="Identifikasi potensi risiko..." />
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Potensiasi Cause (Akar Masalah)</label>
                                <input type="text" v-model="row.potensiasi_cause" class="form-input" placeholder="Penyebab potensial risiko..." />
                            </div>
                        </div>

                        <!-- SOD values -->
                        <div style="display:flex;gap:10px;margin-bottom:12px;align-items:flex-end;flex-wrap:wrap;">
                            <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                <label class="form-label">Severity (S)</label>
                                <select v-model.number="row.s" class="form-select" @change="updateRpn(row)">
                                    <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                <label class="form-label">Occurrence (O)</label>
                                <select v-model.number="row.o" class="form-select" @change="updateRpn(row)">
                                    <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                <label class="form-label">Detection (D)</label>
                                <select v-model.number="row.d" class="form-select" @change="updateRpn(row)">
                                    <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <div style="min-width:100px;flex:1;text-align:center;padding-bottom:2px;">
                                <div style="font-size:0.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:4px;">RPN (S×O×D)</div>
                                <div style="font-size:1.6rem;font-weight:900;line-height:1;" :class="getRpnClass(row.rpn)">
                                    {{ row.rpn }}
                                </div>
                            </div>
                        </div>

                        <div class="grid-2" style="gap:12px; margin-bottom:12px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Corrective (Tindakan Korektif)</label>
                                <input type="text" v-model="row.risk_control" class="form-input" placeholder="Tindakan perbaikan segera..." />
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Preventive (Tindakan Pencegahan)</label>
                                <input type="text" v-model="row.action" class="form-input" placeholder="Tindakan pencegahan agar tidak terulang..." />
                            </div>
                        </div>

                        <!-- Expected / Residual Risk -->
                        <div style="margin-top: 16px; border-top: 1px dashed var(--border-color); padding-top: 16px;">
                            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:12px;">
                                Penilaian Risiko Setelah Tindakan (Expected Risk)
                                <span style="margin-left:12px;font-size:0.8rem;" :class="getRpnClass(row.rpn_after || 1)">
                                    Expected RPN = {{ row.rpn_after || 1 }}
                                </span>
                            </div>
                            <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                                <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                    <label class="form-label">Expected Severity (S)</label>
                                    <select v-model.number="row.s_after" class="form-select" @change="updateRpn(row)">
                                        <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                    <label class="form-label">Expected Occurrence (O)</label>
                                    <select v-model.number="row.o_after" class="form-select" @change="updateRpn(row)">
                                        <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom:0;min-width:90px;flex:1;">
                                    <label class="form-label">Expected Detection (D)</label>
                                    <select v-model.number="row.d_after" class="form-select" @change="updateRpn(row)">
                                        <option v-for="n in [1, 3, 9]" :key="n" :value="n">{{ n }}</option>
                                    </select>
                                </div>
                                <div style="min-width:100px;flex:1;text-align:center;padding-bottom:2px;">
                                    <div style="font-size:0.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:4px;">Expected RPN (S×O×D)</div>
                                    <div style="font-size:1.6rem;font-weight:900;line-height:1;" :class="getRpnClass(row.rpn_after || 1)">
                                        {{ row.rpn_after || 1 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ====== Footer ====== -->
                <div style="display:flex;justify-content:flex-end;gap:12px;padding:20px 24px;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:12px;">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        💾 Simpan Penyelidikan
                    </button>
                </div>
            </form>
        </div>

        <!-- ====== PRINT PREVIEW OVERLAY ====== -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showPrintPreview" style="position:fixed;inset:0;background:rgba(15,23,42,0.6);display:flex;flex-direction:column;z-index:99999;backdrop-filter:blur(8px);padding:24px;">
                    <!-- Preview header -->
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 24px;background:var(--bg-secondary);border-top-left-radius:12px;border-top-right-radius:12px;border-bottom:1px solid var(--border-color);max-width:1200px;width:100%;margin:0 auto;">
                        <h3 style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin:0;">🖨️ {{ previewTitle }}</h3>
                        <div style="display:flex;gap:10px;">
                            <button type="button" @click="printIframe" class="btn btn-primary" style="padding:6px 16px;font-size:0.85rem;">Cetak</button>
                            <button type="button" @click="showPrintPreview = false" class="btn btn-secondary" style="padding:6px 16px;font-size:0.85rem;">Tutup</button>
                        </div>
                    </div>
                    <!-- Preview body -->
                    <div style="flex:1;background:var(--bg-secondary);border-bottom-left-radius:12px;border-bottom-right-radius:12px;max-width:1200px;width:100%;margin:0 auto;overflow:hidden;position:relative;">
                        <iframe id="print-iframe" :src="previewUrl" style="width:100%;height:100%;border:none;"></iframe>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>
