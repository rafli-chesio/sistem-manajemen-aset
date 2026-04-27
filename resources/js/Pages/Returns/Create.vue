<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    borrow: { type: Object, required: true },
});

// Hanya UNIQUE items yang bisa dikembalikan
const uniqueItems = computed(() =>
    props.borrow.items?.filter(i => i.asset?.type === 'UNIQUE') ?? []
);

// Bangun item_conditions awal: satu entry per UNIQUE borrow_item
const form = useForm({
    item_conditions: uniqueItems.value.map(item => ({
        borrow_item_id:  item.id,
        condition_after: item.condition_before ?? 'GOOD', // default = kondisi saat dipinjam
        notes:           '',
    })),
    notes:  '',
    images: [],
});

const conditionOptions = [
    { val: 'GOOD',    label: 'Baik',  color: 'emerald', icon: '✓' },
    { val: 'FAIR',    label: 'Cukup', color: 'yellow',  icon: '~' },
    { val: 'POOR',    label: 'Buruk', color: 'orange',  icon: '!' },
    { val: 'DAMAGED', label: 'Rusak', color: 'red',     icon: '✕' },
];

// Map borrow_item_id → form index untuk binding yang mudah
function getConditionEntry(borrowItemId) {
    return form.item_conditions.find(ic => ic.borrow_item_id === borrowItemId);
}

// Label warna Tailwind — harus full string agar tidak di-purge
const colorClasses = {
    emerald: { active: 'border-emerald-500 bg-emerald-50 text-emerald-700', idle: 'border-slate-200 text-slate-500 hover:border-emerald-300' },
    yellow:  { active: 'border-yellow-500 bg-yellow-50 text-yellow-700',   idle: 'border-slate-200 text-slate-500 hover:border-yellow-300' },
    orange:  { active: 'border-orange-500 bg-orange-50 text-orange-700',   idle: 'border-slate-200 text-slate-500 hover:border-orange-300' },
    red:     { active: 'border-red-500 bg-red-50 text-red-700',            idle: 'border-slate-200 text-slate-500 hover:border-red-300' },
};

function conditionClass(entry, opt) {
    const isActive = entry.condition_after === opt.val;
    return isActive ? colorClasses[opt.color].active : colorClasses[opt.color].idle;
}

function formatDate(raw) {
    if (!raw) return '—';
    return new Date(raw).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

function submit() {
    form.post(route('returns.store', props.borrow.id), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head :title="`Pengembalian - Peminjaman #${borrow.id}`"/>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('borrows.show', borrow.id)" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <h1 class="text-slate-800 font-bold text-lg">Proses Pengembalian #{{ borrow.id }}</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto space-y-5">

            <!-- Info banner -->
            <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 flex gap-3">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-amber-800">Isi kondisi setiap barang secara terpisah</p>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Pengembalian dilakukan sekaligus untuk semua barang unik.
                        Batas kembali: <strong>{{ formatDate(borrow.return_date) }}</strong>.
                        Minimal 1 foto wajib diunggah.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- ── Per-Item Condition ─────────────────────────────────── -->
                <div v-for="(item, idx) in uniqueItems" :key="item.id"
                     class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

                    <!-- Header item -->
                    <div class="flex items-center gap-4 px-6 py-4 border-b border-slate-100">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0">
                            <img v-if="item.asset?.images?.length"
                                 :src="'/storage/' + item.asset.images[0].path"
                                 class="w-full h-full object-cover"/>
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-800 truncate">{{ item.asset?.name }}</p>
                            <p class="text-xs font-mono text-slate-400">{{ item.asset?.asset_code }}</p>
                        </div>
                        <!-- Kondisi sebelum dipinjam -->
                        <div class="text-right">
                            <p class="text-xs text-slate-400 mb-1">Kondisi sebelum</p>
                            <StatusBadge v-if="item.condition_before" :status="item.condition_before"/>
                        </div>
                    </div>

                    <!-- Condition selector per item -->
                    <div class="px-6 py-4 space-y-3">
                        <label class="block text-sm font-semibold text-slate-700">
                            Kondisi Saat Dikembalikan
                            <span class="text-red-500">*</span>
                            <span class="ml-2 text-xs font-normal text-slate-400">
                                → akan digunakan untuk memperbarui status aset
                            </span>
                        </label>

                        <div class="grid grid-cols-4 gap-2">
                            <label v-for="opt in conditionOptions" :key="opt.val"
                                   class="flex flex-col items-center justify-center gap-1 py-2.5 rounded-xl border-2 cursor-pointer text-sm font-semibold transition-all"
                                   :class="conditionClass(getConditionEntry(item.id), opt)">
                                <input type="radio"
                                       v-model="getConditionEntry(item.id).condition_after"
                                       :value="opt.val"
                                       class="hidden"/>
                                <span class="text-base">{{ opt.icon }}</span>
                                <span>{{ opt.label }}</span>
                            </label>
                        </div>

                        <!-- Indikator perubahan kondisi -->
                        <div v-if="getConditionEntry(item.id) && item.condition_before"
                             class="flex items-center gap-2 text-xs">
                            <span class="text-slate-500">Perubahan:</span>
                            <StatusBadge :status="item.condition_before"/>
                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <StatusBadge :status="getConditionEntry(item.id).condition_after"/>
                            <span v-if="getConditionEntry(item.id).condition_after !== item.condition_before"
                                  class="ml-1 px-1.5 py-0.5 rounded text-xs font-medium"
                                  :class="['DAMAGED','POOR'].includes(getConditionEntry(item.id).condition_after)
                                      && !['DAMAGED','POOR'].includes(item.condition_before)
                                      ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-600'">
                                {{ ['DAMAGED','POOR'].includes(getConditionEntry(item.id).condition_after)
                                   && !['DAMAGED','POOR'].includes(item.condition_before)
                                   ? '⚠ Kondisi menurun' : '↑ Berubah' }}
                            </span>
                        </div>

                        <!-- Catatan per item (opsional) -->
                        <textarea v-model="getConditionEntry(item.id).notes"
                                  rows="2"
                                  class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none"
                                  :placeholder="`Catatan khusus untuk ${item.asset?.name} (opsional)...`"/>
                    </div>

                    <p v-if="form.errors[`item_conditions.${idx}.condition_after`]"
                       class="px-6 pb-3 text-red-500 text-xs">
                        {{ form.errors[`item_conditions.${idx}.condition_after`] }}
                    </p>
                </div>

                <!-- ── Catatan Umum ─────────────────────────────────────────── -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Catatan Umum Pengembalian</label>
                    <textarea v-model="form.notes" rows="2"
                              class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none"
                              placeholder="Kondisi umum, keterangan tambahan, dll."/>
                </div>

                <!-- ── Foto Bukti ───────────────────────────────────────────── -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Foto Bukti Pengembalian</h2>
                        <span class="text-xs font-semibold text-red-500 bg-red-50 px-2 py-0.5 rounded-lg">WAJIB</span>
                    </div>
                    <ImageUploader v-model="form.images" :max-files="8"/>
                    <p v-if="form.errors.images" class="text-red-500 text-xs mt-2 font-medium">
                        ⚠ {{ form.errors.images }}
                    </p>
                    <p v-if="form.errors['images.0']" class="text-red-500 text-xs mt-2 font-medium">
                        ⚠ Minimal 1 foto harus diunggah.
                    </p>
                </div>

                <!-- ── Submit ──────────────────────────────────────────────── -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('borrows.show', borrow.id)"
                          class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Batal
                    </Link>
                    <button type="submit"
                            :disabled="form.processing || form.images.length === 0"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-60 flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ form.processing ? 'Memproses...' : 'Konfirmasi Pengembalian' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
