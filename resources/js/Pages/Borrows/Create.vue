<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    availableAssets: { type: Array, default: () => [] },
});

const todayDate = new Date();
const today = todayDate.toISOString().split('T')[0];

const returnDateObj = new Date(todayDate);
returnDateObj.setDate(returnDateObj.getDate() + 7);
const returnDate = returnDateObj.toISOString().split('T')[0];

function formatDate(isoDate) {
    return new Date(isoDate).toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
}

const form = useForm({
    borrow_date: today,
    return_date: returnDate, // 7 hari dari sekarang (untuk UNIQUE)
    notes:       '',
    items:       [],
});

const search      = ref('');
const selectedIds = computed(() => form.items.map(i => i.asset_id));

const filteredAssets = computed(() =>
    props.availableAssets.filter(a =>
        a.name.toLowerCase().includes(search.value.toLowerCase()) ||
        (a.asset_code ?? '').toLowerCase().includes(search.value.toLowerCase())
    )
);

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

        <div class="max-w-7xl mx-auto pb-10">
            <!-- Top section: Search & Note -->
            <div class="mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <!-- Search bar -->
                <div class="relative w-full sm:w-1/2">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Cari nama atau kode aset..."
                           class="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none shadow-sm"/>
                </div>
            </div>

            <!-- 2-Column Layout -->
            <div class="flex flex-col lg:flex-row gap-6 items-start">
                
                <!-- Left: Catalog (70%) -->
                <div class="flex-1 w-full">
                    <div v-if="!filteredAssets.length" class="py-20 text-center text-slate-400 bg-white rounded-3xl border border-slate-100 shadow-sm">
                        <p class="text-base font-medium">Tidak ada aset tersedia.</p>
                    </div>

                    <transition-group name="list" tag="div" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                        <div v-for="asset in filteredAssets" :key="asset.id"
                             class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-md transition-shadow">
                            
                            <!-- Thumbnail -->
                            <div class="aspect-[4/3] bg-slate-100 relative overflow-hidden">
                                <img v-if="asset.images?.length" :src="'/storage/' + asset.images[0].path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <!-- Badge Type -->
                                <div class="absolute top-3 right-3 px-2.5 py-1 rounded-lg text-[10px] font-bold tracking-wide shadow-sm"
                                     :class="asset.type === 'UNIQUE' ? 'bg-violet-100 text-violet-700' : 'bg-sky-100 text-sky-700'">
                                    {{ asset.type === 'UNIQUE' ? '🔖 UNIK' : '📦 HABIS PAKAI' }}
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-4 flex flex-col flex-1">
                                <p class="text-xs text-slate-400 font-medium mb-1">{{ asset.category?.name ?? 'Tanpa Kategori' }}</p>
                                <h3 class="text-sm font-bold text-slate-800 leading-tight mb-3 line-clamp-2">{{ asset.name }}</h3>
                                
                                <div class="mt-auto flex items-center justify-between">
                                    <p v-if="asset.type === 'CONSUMABLE'" class="text-xs font-semibold text-sky-600">Stok: {{ asset.stock }}</p>
                                    <p v-else class="text-xs font-semibold text-slate-400">Tersedia</p>

                                    <!-- Add to Cart Button -->
                                    <button type="button" @click="toggleAsset(asset)"
                                            class="w-8 h-8 rounded-full flex items-center justify-center transition-colors"
                                            :class="isSelected(asset.id) ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                                        <svg v-if="isSelected(asset.id)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition-group>
                </div>

                <!-- Right: Shopping Cart (30%) -->
                <div class="w-full lg:w-[360px] flex-shrink-0">
                    <form @submit.prevent="submit" class="sticky top-6 bg-white rounded-3xl border border-slate-100 shadow-sm flex flex-col max-h-[calc(100vh-3rem)]">
                        <!-- Cart Header -->
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="text-base font-bold text-slate-800">Keranjang Pinjaman</h2>
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">{{ form.items.length }}</span>
                        </div>

                        <!-- Cart Items -->
                        <div class="flex-1 overflow-y-auto p-3 space-y-2" style="min-height: 150px;">
                            <div v-if="!form.items.length" class="h-full flex flex-col items-center justify-center text-slate-400 p-6 text-center">
                                <svg class="w-12 h-12 mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <p class="text-sm font-medium">Keranjang kosong</p>
                                <p class="text-xs mt-1">Pilih aset dari katalog di samping</p>
                            </div>
                            
                            <transition-group name="list">
                                <div v-for="(item, idx) in form.items" :key="item.asset_id"
                                     class="p-3 bg-slate-50 rounded-2xl border border-slate-100 relative group">
                                    <div class="pr-8">
                                        <p class="text-sm font-semibold text-slate-800 leading-tight mb-1">{{ item._asset.name }}</p>
                                        <p class="text-[10px] uppercase font-bold tracking-wide"
                                           :class="item._asset.type === 'UNIQUE' ? 'text-violet-600' : 'text-sky-600'">
                                            {{ item._asset.type === 'UNIQUE' ? '🔖 UNIK' : '📦 HABIS PAKAI' }}
                                        </p>
                                    </div>
                                    
                                    <div class="mt-3 flex items-center justify-between">
                                        <!-- Qty adjuster -->
                                        <div v-if="item._asset.type === 'CONSUMABLE'" class="flex items-center gap-1 bg-white border border-slate-200 rounded-lg p-0.5">
                                            <button type="button" @click="item.quantity = Math.max(1, item.quantity - 1)" class="w-6 h-6 rounded-md bg-white text-slate-600 flex items-center justify-center font-bold hover:bg-slate-50 shadow-sm">−</button>
                                            <span class="text-xs font-bold w-6 text-center">{{ item.quantity }}</span>
                                            <button type="button" @click="item.quantity = Math.min(item._asset.stock, item.quantity + 1)" class="w-6 h-6 rounded-md bg-white text-slate-600 flex items-center justify-center font-bold hover:bg-slate-50 shadow-sm">+</button>
                                        </div>
                                        <span v-else class="text-xs font-semibold text-slate-400 bg-white border border-slate-200 px-2 py-1 rounded-lg">Qty: 1 (Max)</span>
                                    </div>

                                    <!-- Remove Btn -->
                                    <button type="button" @click="form.items.splice(idx, 1)"
                                            class="absolute top-3 right-3 w-6 h-6 rounded-full bg-white border border-slate-200 text-slate-400 flex items-center justify-center hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-colors shadow-sm">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </transition-group>
                        </div>

                        <!-- Notes -->
                        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                            <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Catatan Peminjaman</label>
                            <textarea v-model="form.notes" rows="2"
                                      class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-300 outline-none resize-none shadow-sm"
                                      placeholder="Tulis keperluan meminjam..."/>
                        </div>

                        <!-- Cart Footer / Summary -->
                        <div class="p-5 border-t border-slate-100 bg-slate-50 rounded-b-3xl space-y-4">
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-semibold text-slate-500">Tgl Pinjam</span>
                                    <span class="text-xs font-bold text-slate-800">{{ formatDate(today) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-semibold text-slate-500">Batas Kembali</span>
                                    <span class="text-xs font-bold" :class="hasUniqueItem ? 'text-amber-600' : 'text-slate-400'">
                                        {{ hasUniqueItem ? formatDate(returnDate) : 'Tidak ada' }}
                                    </span>
                                </div>
                            </div>
                            
                            <p v-if="form.errors.items" class="text-red-500 text-xs text-center">{{ form.errors.items }}</p>

                            <button type="submit" :disabled="form.processing || form.items.length === 0"
                                    class="w-full py-3.5 rounded-2xl bg-indigo-600 text-white text-sm font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Checkout / Ajukan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.list-enter-active, .list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from, .list-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.95);
}
</style>
