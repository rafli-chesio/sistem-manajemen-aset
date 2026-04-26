<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    availableAssets: { type: Array, default: () => [] },
});

// ── Auto-compute dates ────────────────────────────────────────────────────────
const todayDate = new Date();
const today = todayDate.toISOString().split('T')[0];

const returnDateObj = new Date(todayDate);
returnDateObj.setDate(returnDateObj.getDate() + 7);
const returnDate = returnDateObj.toISOString().split('T')[0];

// Format tanggal ke bahasa Indonesia untuk ditampilkan
function formatDate(isoDate) {
    return new Date(isoDate).toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
}

// ── Form ──────────────────────────────────────────────────────────────────────
const form = useForm({
    borrow_date: today,
    return_date: returnDate, // 7 hari dari sekarang (untuk UNIQUE)
    notes:       '',
    items:       [],
});

// ── Asset search & selection ──────────────────────────────────────────────────
const search      = ref('');
const selectedIds = computed(() => form.items.map(i => i.asset_id));

const filteredAssets = computed(() =>
    props.availableAssets.filter(a =>
        a.name.toLowerCase().includes(search.value.toLowerCase()) ||
        (a.asset_code ?? '').toLowerCase().includes(search.value.toLowerCase())
    )
);

// Apakah cart mengandung minimal 1 UNIQUE item?
const hasUniqueItem = computed(() =>
    form.items.some(i => i._asset?.type === 'UNIQUE')
);

function toggleAsset(asset) {
    const idx = form.items.findIndex(i => i.asset_id === asset.id);
    if (idx >= 0) {
        form.items.splice(idx, 1);
    } else {
        form.items.push({
            asset_id: asset.id,
            quantity: 1,
            _asset:   asset,
        });
    }
}

function isSelected(assetId) {
    return selectedIds.value.includes(assetId);
}

function submit() {
    // Kirim return_date hanya jika ada UNIQUE item di cart
    // Untuk request yang hanya berisi CONSUMABLE, return_date = borrow_date (atau null)
    const payload = {
        borrow_date: form.borrow_date,
        return_date: hasUniqueItem.value ? form.return_date : form.borrow_date,
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

                <!-- Info tanggal otomatis -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Informasi Peminjaman</h2>

                    <!-- Tampilkan tanggal sebagai info card, bukan input -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <!-- Tanggal Pinjam -->
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-indigo-50 border border-indigo-100">
                            <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wide">Tanggal Pinjam</p>
                                <p class="text-sm font-bold text-slate-800 mt-0.5">{{ formatDate(today) }}</p>
                                <p class="text-xs text-indigo-400 mt-0.5">Hari ini (otomatis)</p>
                            </div>
                        </div>

                        <!-- Tanggal Kembali — hanya tampil jika ada UNIQUE item -->
                        <div v-if="hasUniqueItem"
                             class="flex items-start gap-3 p-3 rounded-xl bg-amber-50 border border-amber-100">
                            <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-amber-500 uppercase tracking-wide">Batas Pengembalian</p>
                                <p class="text-sm font-bold text-slate-800 mt-0.5">{{ formatDate(returnDate) }}</p>
                                <p class="text-xs text-amber-400 mt-0.5">7 hari sejak peminjaman (otomatis)</p>
                            </div>
                        </div>

                        <!-- Info untuk CONSUMABLE only cart -->
                        <div v-if="!hasUniqueItem && form.items.length > 0"
                             class="flex items-start gap-3 p-3 rounded-xl bg-sky-50 border border-sky-100">
                            <div class="w-9 h-9 rounded-lg bg-sky-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-sky-500 uppercase tracking-wide">Barang Habis Pakai</p>
                                <p class="text-sm text-slate-700 mt-0.5">Tidak ada batas pengembalian untuk barang habis pakai.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Catatan (opsional)</label>
                        <textarea v-model="form.notes" rows="2"
                                  class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none"
                                  placeholder="Keperluan peminjaman..."/>
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
                                <p class="text-xs text-slate-400">
                                    {{ item._asset.type === 'UNIQUE' ? '🔖 Barang Unik — ada batas kembali' : '📦 Habis Pakai — tanpa batas kembali' }}
                                </p>
                            </div>
                            <!-- Qty adjuster hanya untuk CONSUMABLE -->
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
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ asset.name }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ asset.category?.name ?? '—' }}
                                    <span class="mx-1">·</span>
                                    <span :class="asset.type === 'UNIQUE' ? 'text-violet-600' : 'text-sky-600'" class="font-medium">
                                        {{ asset.type === 'UNIQUE' ? '🔖 Unik' : '📦 Habis Pakai' }}
                                    </span>
                                    <span v-if="asset.type === 'CONSUMABLE'" class="ml-2 font-medium text-sky-600">Stok: {{ asset.stock }}</span>
                                </p>
                            </div>
                            <!-- Checkbox -->
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
