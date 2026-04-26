<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    availableAssets: { type: Array, default: () => [] },
});

const form = useForm({
    borrow_date: '',
    return_date: '',
    notes:       '',
    items:       [],
});

const search    = ref('');
const selectedIds = computed(() => form.items.map(i => i.asset_id));

const filteredAssets = computed(() =>
    props.availableAssets.filter(a =>
        a.name.toLowerCase().includes(search.value.toLowerCase()) ||
        (a.asset_code ?? '').toLowerCase().includes(search.value.toLowerCase())
    )
);

function toggleAsset(asset) {
    const idx = form.items.findIndex(i => i.asset_id === asset.id);
    if (idx >= 0) {
        form.items.splice(idx, 1);
    } else {
        form.items.push({
            asset_id: asset.id,
            quantity: asset.type === 'UNIQUE' ? 1 : 1,
            _asset:   asset,
        });
    }
}

function isSelected(assetId) {
    return selectedIds.value.includes(assetId);
}

function getItem(assetId) {
    return form.items.find(i => i.asset_id === assetId);
}

const today = new Date().toISOString().split('T')[0];

function submit() {
    // Strip _asset helper before submit
    const payload = {
        borrow_date: form.borrow_date,
        return_date: form.return_date,
        notes:       form.notes,
        items:       form.items.map(({ asset_id, quantity }) => ({ asset_id, quantity })),
    };
    form.transform(() => payload).post(route('borrows.store'));
}
</script>

<template>
    <Head title="Buat Permintaan Peminjaman"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Buat Permintaan Peminjaman</h1>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="space-y-5">

                <!-- Dates & Notes -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Informasi Peminjaman</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Tanggal Pinjam <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.borrow_date" type="date" :min="today"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                            <p v-if="form.errors.borrow_date" class="text-red-500 text-xs mt-1">{{ form.errors.borrow_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Tanggal Kembali <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.return_date" type="date" :min="form.borrow_date || today"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                            <p v-if="form.errors.return_date" class="text-red-500 text-xs mt-1">{{ form.errors.return_date }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Catatan (opsional)</label>
                            <textarea v-model="form.notes" rows="2"
                                      class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none"
                                      placeholder="Keperluan peminjaman..."/>
                        </div>
                    </div>
                </div>

                <!-- Cart summary -->
                <div v-if="form.items.length" class="bg-indigo-50 rounded-2xl border border-indigo-200 p-4">
                    <h2 class="text-sm font-bold text-indigo-700 mb-3">
                        Barang Dipilih ({{ form.items.length }})
                    </h2>
                    <div class="space-y-2">
                        <div v-for="(item, idx) in form.items" :key="item.asset_id"
                             class="flex items-center gap-3 bg-white rounded-xl px-4 py-2.5 shadow-sm">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ item._asset.name }}</p>
                                <p class="text-xs text-slate-400">{{ item._asset.type === 'UNIQUE' ? 'Barang Unik' : 'Habis Pakai' }}</p>
                            </div>
                            <div v-if="item._asset.type === 'CONSUMABLE'" class="flex items-center gap-2">
                                <button type="button" @click="item.quantity = Math.max(1, item.quantity - 1)"
                                        class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold hover:bg-slate-200">−</button>
                                <span class="text-sm font-semibold w-6 text-center">{{ item.quantity }}</span>
                                <button type="button" @click="item.quantity = Math.min(item._asset.stock, item.quantity + 1)"
                                        class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold hover:bg-slate-200">+</button>
                            </div>
                            <span v-else class="text-xs text-slate-400">× 1</span>
                            <button type="button" @click="form.items.splice(idx, 1)"
                                    class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p v-if="form.errors.items" class="text-red-500 text-xs mt-2">{{ form.errors.items }}</p>
                </div>

                <!-- Asset picker -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-4 border-b border-slate-100">
                        <h2 class="text-sm font-bold text-slate-700 mb-3">Pilih Barang</h2>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input v-model="search" type="text" placeholder="Cari nama atau kode aset..."
                                   class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                        </div>
                    </div>
                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                        <div v-if="!filteredAssets.length" class="py-10 text-center text-slate-400 text-sm">
                            Tidak ada aset tersedia.
                        </div>
                        <button v-for="asset in filteredAssets" :key="asset.id" type="button"
                                @click="toggleAsset(asset)"
                                class="w-full flex items-center gap-4 px-4 py-3 text-left transition-colors"
                                :class="isSelected(asset.id) ? 'bg-indigo-50' : 'hover:bg-slate-50'">
                            <!-- Thumbnail -->
                            <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0">
                                <img v-if="asset.images?.length" :src="'/storage/' + asset.images[0].path" class="w-full h-full object-cover"/>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ asset.name }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ asset.category?.name ?? '—' }}
                                    <span v-if="asset.type === 'CONSUMABLE'" class="ml-2 font-medium text-sky-600">Stok: {{ asset.stock }}</span>
                                </p>
                            </div>
                            <!-- Check -->
                            <div class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center border-2 transition-all"
                                 :class="isSelected(asset.id) ? 'bg-indigo-600 border-indigo-600' : 'border-slate-300'">
                                <svg v-if="isSelected(asset.id)" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <a :href="route('borrows.index')" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit" :disabled="form.processing || form.items.length === 0"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-60 flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Ajukan Permintaan
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
