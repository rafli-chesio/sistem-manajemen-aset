<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    borrow: { type: Object, required: true },
});

const form = useForm({
    condition_after: 'GOOD',
    notes:           '',
    images:          [],
});

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

            <!-- Warning banner -->
            <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 flex gap-3">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-amber-800">Pengembalian Tidak Bisa Diproses Sebagian</p>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Semua barang unik dalam permintaan ini akan dikembalikan sekaligus.
                        Minimal 1 foto wajib diunggah sebagai dokumentasi.
                    </p>
                </div>
            </div>

            <!-- Items being returned -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-700">Barang yang Dikembalikan</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="item in borrow.items?.filter(i => i.asset?.type === 'UNIQUE')" :key="item.id"
                         class="flex items-center gap-4 px-6 py-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0">
                            <img v-if="item.asset?.images?.length"
                                 :src="'/storage/' + item.asset.images[0].path" class="w-full h-full object-cover"/>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">{{ item.asset?.name }}</p>
                            <p class="text-xs font-mono text-slate-400">{{ item.asset?.asset_code }}</p>
                        </div>
                        <StatusBadge v-if="item.condition_before" :status="item.condition_before"/>
                    </div>
                </div>
            </div>

            <!-- Return form -->
            <form @submit.prevent="submit" class="space-y-5">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-4">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Data Pengembalian</h2>

                    <!-- Condition -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Kondisi Akhir Barang <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <label v-for="opt in [
                                { val: 'GOOD',    label: 'Baik',    color: 'emerald' },
                                { val: 'FAIR',    label: 'Cukup',   color: 'yellow' },
                                { val: 'POOR',    label: 'Buruk',   color: 'orange' },
                                { val: 'DAMAGED', label: 'Rusak',   color: 'red' },
                            ]" :key="opt.val"
                                class="flex items-center justify-center gap-2 px-3 py-2 rounded-xl border-2 cursor-pointer text-sm font-semibold transition-all"
                                :class="form.condition_after === opt.val
                                    ? `border-${opt.color}-500 bg-${opt.color}-50 text-${opt.color}-700`
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300'">
                                <input type="radio" v-model="form.condition_after" :value="opt.val" class="hidden"/>
                                {{ opt.label }}
                            </label>
                        </div>
                        <p v-if="form.errors.condition_after" class="text-red-500 text-xs mt-1">{{ form.errors.condition_after }}</p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Catatan (opsional)</label>
                        <textarea v-model="form.notes" rows="3"
                                  class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none"
                                  placeholder="Kondisi khusus, catatan kerusakan, dll."/>
                    </div>
                </div>

                <!-- Photo upload — MANDATORY -->
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
                        Konfirmasi Pengembalian
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
