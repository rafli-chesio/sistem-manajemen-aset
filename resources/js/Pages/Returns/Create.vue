<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onBeforeUnmount } from 'vue';

const props = defineProps({
    borrow: { type: Object, required: true },
});

const uniqueItems = computed(() =>
    props.borrow.items?.filter(i => i.asset?.type === 'FIXED') ?? []
);

const form = useForm({
    item_conditions: uniqueItems.value.map(item => ({
        borrow_item_id:  item.id,
        condition_after: item.condition_before ?? 'BAIK',
        notes:           '',
    })),
    notes:  '',
    images: [],
});

const conditionOptions = [
    { val: 'BAIK',         label: 'Baik',         color: 'emerald', icon: '✓' },
    { val: 'RUSAK_RINGAN', label: 'Rusak Ringan', color: 'yellow',  icon: '~' },
    { val: 'RUSAK_BERAT',  label: 'Rusak Berat',  color: 'red',     icon: '✕' },
];

function getConditionEntry(borrowItemId) {
    return form.item_conditions.find(ic => ic.borrow_item_id === borrowItemId);
}

const colorClasses = {
    emerald: { active: 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-sm', idle: 'border-slate-200 bg-white text-slate-500 hover:border-emerald-300 hover:bg-emerald-50/50' },
    yellow:  { active: 'border-yellow-500 bg-yellow-50 text-yellow-700 shadow-sm',   idle: 'border-slate-200 bg-white text-slate-500 hover:border-yellow-300 hover:bg-yellow-50/50' },
    orange:  { active: 'border-orange-500 bg-orange-50 text-orange-700 shadow-sm',   idle: 'border-slate-200 bg-white text-slate-500 hover:border-orange-300 hover:bg-orange-50/50' },
    red:     { active: 'border-red-500 bg-red-50 text-red-700 shadow-sm',            idle: 'border-slate-200 bg-white text-slate-500 hover:border-red-300 hover:bg-red-50/50' },
};

function conditionClass(entry, opt) {
    const isActive = entry.condition_after === opt.val;
    return isActive ? colorClasses[opt.color].active : colorClasses[opt.color].idle;
}

function formatDate(raw) {
    if (!raw) return '—';
    return new Date(raw).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

// Dropzone Logic
const isDragging = ref(false);
const fileInput = ref(null);
const previews = ref([]);

function handleDrop(e) {
    isDragging.value = false;
    addFiles(e.dataTransfer.files);
}
function handleFileSelect(e) {
    addFiles(e.target.files);
}
function addFiles(files) {
    for (let i = 0; i < files.length; i++) {
        if (files[i].type.startsWith('image/')) {
            form.images.push(files[i]);
            previews.value.push({
                file: files[i],
                url: URL.createObjectURL(files[i])
            });
        }
    }
    // reset input
    if (fileInput.value) fileInput.value.value = '';
}
function removeImage(idx) {
    URL.revokeObjectURL(previews.value[idx].url);
    previews.value.splice(idx, 1);
    form.images.splice(idx, 1);
}

// Cleanup URLs
onBeforeUnmount(() => {
    previews.value.forEach(p => URL.revokeObjectURL(p.url));
});

function submit() {
    form.post(route('returns.store', props.borrow.id), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head :title="`Pengembalian #${borrow.id}`"/>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('borrows.show', borrow.id)" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <h1 class="text-slate-800 font-bold text-xl">Formulir Pengembalian</h1>
                
                <!-- Pills -->
                <div class="ml-2 flex items-center gap-2">
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold uppercase tracking-wider rounded-full border border-indigo-100">
                        ID: #{{ borrow.id }}
                    </span>
                    <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full border border-slate-200">
                        Dipinjam: {{ formatDate(borrow.borrow_date) }}
                    </span>
                </div>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6 pb-12">

            <form @submit.prevent="submit" class="space-y-8">
                
                <!-- 1. Kondisi Barang (Cards) -->
                <div>
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-800">Evaluasi Kondisi Barang</h2>
                        <p class="text-sm text-slate-500">Nilai kondisi akhir dari setiap barang unik yang Anda kembalikan.</p>
                    </div>

                    <div class="space-y-4">
                        <div v-for="(item, idx) in uniqueItems" :key="item.id"
                             class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                            
                            <!-- Header Item -->
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-4 bg-slate-50/50">
                                <div class="w-12 h-12 rounded-xl bg-slate-200 overflow-hidden flex-shrink-0">
                                    <img v-if="item.asset?.images?.length" :src="'/storage/' + item.asset.images[0].path" class="w-full h-full object-cover"/>
                                    <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-slate-800 text-lg leading-tight">{{ item.asset?.name }} <span class="text-sm font-medium text-slate-400 ml-1">× 1</span></h3>
                                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                        Kondisi saat dipinjam: <StatusBadge :status="item.condition_before" class="scale-90 origin-left" />
                                    </p>
                                </div>
                            </div>

                            <!-- Body Evaluasi -->
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-3">Kondisi Saat Ini <span class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <label v-for="opt in conditionOptions" :key="opt.val"
                                               class="flex flex-col items-center justify-center gap-1.5 py-3 rounded-2xl border-2 cursor-pointer transition-all"
                                               :class="conditionClass(getConditionEntry(item.id), opt)">
                                            <input type="radio" v-model="getConditionEntry(item.id).condition_after" :value="opt.val" class="hidden"/>
                                            <span class="text-lg font-black">{{ opt.icon }}</span>
                                            <span class="text-sm font-bold">{{ opt.label }}</span>
                                        </label>
                                    </div>
                                    <p v-if="form.errors[`item_conditions.${idx}.condition_after`]" class="text-red-500 text-xs mt-2">{{ form.errors[`item_conditions.${idx}.condition_after`] }}</p>
                                </div>

                                <!-- Change indicator -->
                                <div v-if="getConditionEntry(item.id) && item.condition_before && getConditionEntry(item.id).condition_after !== item.condition_before"
                                     class="flex items-center gap-2 p-3 rounded-xl"
                                     :class="['RUSAK_RINGAN','RUSAK_BERAT'].includes(getConditionEntry(item.id).condition_after) && !['RUSAK_RINGAN','RUSAK_BERAT'].includes(item.condition_before) ? 'bg-red-50 border border-red-100' : 'bg-emerald-50 border border-emerald-100'">
                                    <span class="text-xs font-semibold" :class="['RUSAK_RINGAN','RUSAK_BERAT'].includes(getConditionEntry(item.id).condition_after) && !['RUSAK_RINGAN','RUSAK_BERAT'].includes(item.condition_before) ? 'text-red-700' : 'text-emerald-700'">
                                        {{ ['RUSAK_RINGAN','RUSAK_BERAT'].includes(getConditionEntry(item.id).condition_after) && !['RUSAK_RINGAN','RUSAK_BERAT'].includes(item.condition_before) ? '⚠ Perhatian: Kondisi barang menurun.' : '✨ Kondisi barang berubah.' }}
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Catatan Kerusakan / Keterangan (Opsional)</label>
                                    <textarea v-model="getConditionEntry(item.id).notes" rows="2"
                                              class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none bg-slate-50 focus:bg-white transition-colors"
                                              placeholder="Tuliskan detail jika ada kerusakan..."/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Catatan Umum -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Catatan Umum Pengembalian</label>
                    <textarea v-model="form.notes" rows="3"
                              class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none bg-slate-50 focus:bg-white transition-colors"
                              placeholder="Kondisi pengembalian secara keseluruhan..."/>
                </div>

                <!-- 3. Foto Bukti (Massive Dropzone) -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Foto Bukti Pengembalian</h2>
                            <p class="text-sm text-slate-500">Unggah minimal 1 foto barang secara fisik sebagai bukti sah.</p>
                        </div>
                        <span class="px-2.5 py-1 bg-red-100 text-red-700 text-[10px] font-black uppercase tracking-wider rounded-lg">WAJIB</span>
                    </div>

                    <!-- Dropzone Area -->
                    <div @dragover.prevent="isDragging = true" 
                         @dragleave.prevent="isDragging = false" 
                         @drop.prevent="handleDrop"
                         @click="$refs.fileInput.click()"
                         class="relative w-full h-48 rounded-3xl border-2 border-dashed flex flex-col items-center justify-center cursor-pointer transition-all duration-200 ease-in-out"
                         :class="isDragging ? 'border-indigo-500 bg-indigo-50 scale-[1.01]' : 'border-slate-300 bg-slate-50 hover:bg-slate-100 hover:border-slate-400'">
                        
                        <input type="file" ref="fileInput" multiple accept="image/*" class="hidden" @change="handleFileSelect"/>
                        
                        <div class="w-16 h-16 rounded-full bg-white shadow-sm flex items-center justify-center mb-4 text-indigo-500 transition-transform duration-300"
                             :class="isDragging ? 'scale-110' : ''">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-700">Tarik & Lepas foto bukti di sini</p>
                        <p class="text-xs text-slate-400 mt-1">atau klik untuk memilih file dari komputer</p>
                    </div>
                    
                    <p v-if="form.errors.images || form.errors['images.0']" class="text-red-500 text-sm font-semibold mt-3 text-center">
                        ⚠ {{ form.errors.images || 'Minimal 1 foto harus diunggah.' }}
                    </p>

                    <!-- Previews Grid -->
                    <div v-if="previews.length > 0" class="mt-6">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Foto Terpilih ({{ previews.length }})</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
                            <div v-for="(p, idx) in previews" :key="idx" class="relative group aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shadow-sm">
                                <img :src="p.url" class="w-full h-full object-cover"/>
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <button type="button" @click.stop="removeImage(idx)" class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 hover:scale-110 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-6 border-t border-slate-200">
                    <button type="submit" :disabled="form.processing || form.images.length === 0"
                            class="w-full py-4 rounded-2xl bg-indigo-600 text-white text-base font-bold shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                        <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ form.processing ? 'Memproses Pengembalian...' : 'Konfirmasi & Proses Pengembalian' }}
                    </button>
                    <p v-if="form.images.length === 0" class="text-center text-xs text-slate-400 mt-3 font-medium">Upload minimal 1 foto untuk mengaktifkan tombol ini.</p>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
