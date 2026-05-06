<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    assets:      { type: Object, required: true },
    categories:  { type: Array,  default: () => [] },
    locations:   { type: Array,  default: () => [] },
    departments: { type: Array,  default: () => [] },
    filters:     { type: Object, default: () => ({}) },
});

const page     = usePage();
const userRole = page.props.auth.user?.role;
const canCreate = userRole === 'ADMIN';
const canEdit   = userRole === 'ADMIN' || userRole === 'KAJUR';
const canDelete = userRole === 'ADMIN';

const search    = ref(props.filters.search    ?? '');
const status    = ref(props.filters.status    ?? '');
const condition = ref(props.filters.condition ?? '');
const type      = ref(props.filters.type      ?? '');
const category  = ref(props.filters.category  ?? '');

const showFilters   = ref(false);
const deleteTarget  = ref(null);
const deleteLoading = ref(false);

const applyFilters = debounce(() => {
    router.get(route('assets.index'), {
        search:    search.value    || undefined,
        status:    status.value    || undefined,
        condition: condition.value || undefined,
        type:      type.value      || undefined,
        category:  category.value  || undefined,
    }, { preserveState: true, replace: true });
}, 350);

watch([search, status, condition, type, category], applyFilters);

const activeFilterCount = [status, condition, type, category]
    .filter(r => r.value !== '').length;

function confirmDelete(asset) { deleteTarget.value = asset; }
function cancelDelete()       { deleteTarget.value = null;  }

function doDelete() {
    deleteLoading.value = true;
    router.delete(route('assets.destroy', deleteTarget.value.id), {
        onFinish: () => { deleteLoading.value = false; deleteTarget.value = null; },
    });
}

const conditionLabel = {
    BAIK:         'Baik',
    RUSAK_RINGAN: 'Rusak Ringan',
    RUSAK_BERAT:  'Rusak Berat',
};
const conditionBadge = {
    BAIK:         'bg-emerald-50 text-emerald-700 border-emerald-200',
    RUSAK_RINGAN: 'bg-amber-50 text-amber-700 border-amber-200',
    RUSAK_BERAT:  'bg-red-50 text-red-700 border-red-200',
};
const statusBadge = {
    AVAILABLE:   'border-emerald-400 text-emerald-600',
    BORROWED:    'border-amber-400 text-amber-600',
    MAINTENANCE: 'border-blue-400 text-blue-600',
    LOST:        'border-red-400 text-red-500',
    ARCHIVED:    'border-slate-400 text-slate-500',
};
const statusLabel = {
    AVAILABLE:   'Tersedia',
    BORROWED:    'Dipinjam',
    MAINTENANCE: 'Maintenance',
    LOST:        'Hilang',
    ARCHIVED:    'Diarsipkan',
};
</script>

<template>
    <Head title="Aset"/>
    <AuthenticatedLayout>
        <div class="w-full">

            <!-- Toolbar -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Daftar Aset</h1>
                    <p class="text-sm text-slate-400 mt-0.5">
                        {{ assets.meta?.total ?? assets.data.length }} aset terdaftar
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search -->
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="search" type="text" placeholder="Cari nama, kode, merk..."
                               class="pl-9 pr-4 py-2 bg-slate-100 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-200 outline-none transition-all w-56"/>
                    </div>

                    <!-- Filter Toggle -->
                    <button @click="showFilters = !showFilters"
                            class="flex items-center gap-2 px-3 py-2 rounded-xl border text-sm font-medium transition-colors"
                            :class="activeFilterCount > 0 ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-white border-slate-200 text-slate-600 hover:border-indigo-200'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter
                        <span v-if="activeFilterCount > 0" class="w-4 h-4 bg-indigo-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                            {{ activeFilterCount }}
                        </span>
                    </button>

                    <!-- QR Scan -->
                    <Link :href="route('assets.scan')"
                          class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:border-indigo-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        Scan QR
                    </Link>

                    <!-- Import (Admin only) -->
                    <Link v-if="canCreate" :href="route('assets.import')"
                          class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:border-indigo-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Import
                    </Link>

                    <!-- Tambah Aset (Admin only) -->
                    <Link v-if="canCreate" :href="route('assets.create')"
                          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm shadow-indigo-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Aset
                    </Link>
                </div>
            </div>

            <!-- Filter Panel (collapsible) -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showFilters"
                     class="bg-white border border-slate-100 rounded-2xl p-4 mb-5 grid grid-cols-2 sm:grid-cols-4 gap-3 shadow-sm">
                    <!-- Status -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block mb-1">Status</label>
                        <select v-model="status" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-200 outline-none bg-white">
                            <option value="">Semua</option>
                            <option value="AVAILABLE">Tersedia</option>
                            <option value="BORROWED">Dipinjam</option>
                            <option value="MAINTENANCE">Maintenance</option>
                            <option value="LOST">Hilang</option>
                            <option value="ARCHIVED">Diarsipkan</option>
                        </select>
                    </div>
                    <!-- Kondisi -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block mb-1">Kondisi</label>
                        <select v-model="condition" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-200 outline-none bg-white">
                            <option value="">Semua</option>
                            <option value="BAIK">Baik</option>
                            <option value="RUSAK_RINGAN">Rusak Ringan</option>
                            <option value="RUSAK_BERAT">Rusak Berat</option>
                        </select>
                    </div>
                    <!-- Tipe -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block mb-1">Tipe</label>
                        <select v-model="type" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-200 outline-none bg-white">
                            <option value="">Semua</option>
                            <option value="FIXED">Aset Tetap</option>
                            <option value="CONSUMABLE">Habis Pakai</option>
                        </select>
                    </div>
                    <!-- Kategori -->
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide block mb-1">Kategori</label>
                        <select v-model="category" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-200 outline-none bg-white">
                            <option value="">Semua</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                </div>
            </Transition>

            <!-- Table -->
            <div class="bg-white/80 backdrop-blur-md rounded-3xl border border-slate-100 shadow-glass overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[820px]">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-6 py-5 text-[10px] uppercase text-slate-500 tracking-widest font-extrabold">Nama Aset</th>
                                <th class="px-5 py-5 text-[10px] uppercase text-slate-500 tracking-widest font-extrabold">Tipe & Kategori</th>
                                <th class="px-5 py-5 text-[10px] uppercase text-slate-500 tracking-widest font-extrabold text-center">Kondisi</th>
                                <th class="px-5 py-5 text-[10px] uppercase text-slate-500 tracking-widest font-extrabold text-center">Status / Stok</th>
                                <th class="px-6 py-5 text-[10px] uppercase text-slate-500 tracking-widest font-extrabold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50/80">
                            <!-- Empty State -->
                            <tr v-if="!assets.data?.length">
                                <td colspan="5" class="py-2">
                                    <EmptyState
                                        icon="search"
                                        :title="filters.search ? 'Aset tidak ditemukan' : 'Belum ada aset'"
                                        :description="filters.search ? `Tidak ada aset yang cocok dengan '${filters.search}'.` : 'Belum ada aset yang ditambahkan ke sistem.'"
                                        :action-label="canCreate ? 'Tambah Aset Pertama' : null"
                                        :action-route="canCreate ? route('assets.create') : null"
                                    />
                                </td>
                            </tr>

                            <!-- Rows -->
                            <tr v-for="asset in assets.data" :key="asset.id"
                                class="hover:bg-slate-50/80 hover:shadow-sm transition-all duration-300 relative group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0 flex items-center justify-center">
                                            <img v-if="asset.images?.length" :src="'/storage/' + asset.images[0].path"
                                                 class="w-full h-full object-cover" :alt="asset.name"/>
                                            <svg v-else class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <Link :href="route('assets.show', asset.id)"
                                                  class="text-sm font-semibold text-slate-800 hover:text-indigo-600 transition-colors">
                                                {{ asset.name }}
                                            </Link>
                                            <p class="text-xs text-slate-400 mt-0.5 font-mono">{{ asset.asset_code || '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="text-sm font-medium text-slate-700">
                                        {{ asset.type === 'FIXED' ? 'Aset Tetap' : 'Habis Pakai' }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ asset.category?.name ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-block px-2.5 py-0.5 text-[11px] font-semibold rounded-full border"
                                          :class="conditionBadge[asset.condition] ?? 'bg-slate-50 text-slate-500 border-slate-200'">
                                        {{ conditionLabel[asset.condition] ?? asset.condition }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <!-- FIXED → status badge -->
                                    <span v-if="asset.type === 'FIXED'"
                                          class="inline-block px-2.5 py-0.5 text-[11px] font-semibold rounded-full border bg-transparent"
                                          :class="statusBadge[asset.status] ?? 'border-slate-300 text-slate-500'">
                                        {{ statusLabel[asset.status] ?? asset.status }}
                                    </span>
                                    <!-- CONSUMABLE → stok -->
                                    <span v-else class="inline-block px-2.5 py-0.5 text-[11px] font-semibold rounded-full border border-indigo-200 text-indigo-600">
                                        {{ asset.stock ?? 0 }} unit
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-end gap-2 text-slate-400">
                                        <Link :href="route('assets.show', asset.id)"
                                              class="p-1.5 rounded-lg hover:bg-slate-100 hover:text-slate-700 transition-colors" title="Detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </Link>
                                        <Link v-if="canEdit" :href="route('assets.edit', asset.id)"
                                              class="p-1.5 rounded-lg hover:bg-amber-50 hover:text-amber-600 transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </Link>
                                        <button v-if="canDelete" @click="confirmDelete(asset)"
                                                class="p-1.5 rounded-lg hover:bg-red-50 hover:text-red-500 transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row items-center justify-between text-sm text-slate-400 gap-3">
                <p>
                    Menampilkan <span class="font-semibold text-slate-700">{{ assets.meta?.from ?? 1 }}</span>–<span class="font-semibold text-slate-700">{{ assets.meta?.to ?? assets.data.length }}</span>
                    dari <span class="font-semibold text-slate-700">{{ assets.meta?.total ?? assets.data.length }}</span> aset
                </p>
                <div v-if="assets.links?.length > 3" class="flex items-center gap-1">
                    <Link v-for="(link, idx) in assets.links" :key="idx"
                          :href="link.url ?? '#'"
                          v-html="link.label.replace('&laquo; Previous', '←').replace('Next &raquo;', '→')"
                          class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
                          :class="[
                              link.active ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-100 hover:border-indigo-200 text-slate-500',
                              !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                          ]"/>
                </div>
            </div>
        </div>

        <!-- Confirm Delete Modal -->
        <ConfirmModal
            :show="!!deleteTarget"
            type="danger"
            title="Hapus Aset"
            :message="`Aset '${deleteTarget?.name}' akan dihapus secara permanen. Tindakan ini tidak bisa dibatalkan.`"
            confirm-label="Ya, Hapus"
            cancel-label="Batal"
            @confirm="doDelete"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>
