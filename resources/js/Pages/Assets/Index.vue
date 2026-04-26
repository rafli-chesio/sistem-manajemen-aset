<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    assets:     { type: Object, required: true },
    categories: { type: Array,  default: () => [] },
    locations:  { type: Array,  default: () => [] },
    filters:    { type: Object, default: () => ({}) },
});

const page      = usePage();
const canCreate = page.props.auth.permissions.includes('asset.create');
const canEdit   = page.props.auth.permissions.includes('asset.edit');
const canDelete = page.props.auth.permissions.includes('asset.delete');

const search   = ref(props.filters.search ?? '');
const type     = ref(props.filters.type ?? '');
const status   = ref(props.filters.status ?? '');
const category = ref(props.filters.category ?? '');
const location = ref(props.filters.location ?? '');

const deleteTarget  = ref(null);
const deleteLoading = ref(false);

const applyFilters = debounce(() => {
    router.get(route('assets.index'), {
        search: search.value || undefined,
        type:   type.value   || undefined,
        status: status.value || undefined,
        category: category.value || undefined,
        location: location.value || undefined,
    }, { preserveState: true, replace: true });
}, 350);

watch([search, type, status, category, location], applyFilters);

function confirmDelete(asset) { deleteTarget.value = asset; }
function cancelDelete()       { deleteTarget.value = null; }

async function doDelete() {
    deleteLoading.value = true;
    router.delete(route('assets.destroy', deleteTarget.value.id), {
        onFinish: () => { deleteLoading.value = false; deleteTarget.value = null; },
    });
}
</script>

<template>
    <Head title="Daftar Aset"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Manajemen Aset</h1>
        </template>

        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input v-model="search" type="text" placeholder="Cari nama, kode, merek..."
                       class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none"/>
            </div>
            <div class="flex flex-wrap gap-2">
                <select v-model="type" class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                    <option value="">Semua Tipe</option>
                    <option value="UNIQUE">Unik</option>
                    <option value="CONSUMABLE">Habis Pakai</option>
                </select>
                <select v-model="status" class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                    <option value="">Semua Status</option>
                    <option value="AVAILABLE">Tersedia</option>
                    <option value="BORROWED">Dipinjam</option>
                    <option value="DAMAGED">Rusak</option>
                    <option value="LOST">Hilang</option>
                </select>
                <select v-model="category" class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                    <option value="">Semua Kategori</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>
            <Link v-if="canCreate" :href="route('assets.create')"
                  class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Aset
            </Link>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            <th class="px-4 py-3 w-16">Foto</th>
                            <th class="px-4 py-3">Nama / Kode</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Kondisi</th>
                            <th class="px-4 py-3">Status / Stok</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Lokasi</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="!assets.data?.length">
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400">Tidak ada aset ditemukan.</td>
                        </tr>
                        <tr v-for="asset in assets.data" :key="asset.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="w-12 h-12 rounded-lg overflow-hidden bg-slate-100 flex-shrink-0">
                                    <img v-if="asset.images?.length"
                                         :src="'/storage/' + asset.images[0].path"
                                         class="w-full h-full object-cover"
                                         :alt="asset.name"/>
                                    <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="route('assets.show', asset.id)" class="font-semibold text-slate-800 hover:text-indigo-600 transition-colors">
                                    {{ asset.name }}
                                </Link>
                                <p v-if="asset.asset_code" class="text-xs font-mono text-slate-400 mt-0.5">{{ asset.asset_code }}</p>
                                <p v-if="asset.brand" class="text-xs text-slate-400">{{ asset.brand }}</p>
                            </td>
                            <td class="px-4 py-3"><StatusBadge :status="asset.type"/></td>
                            <td class="px-4 py-3"><StatusBadge :status="asset.condition"/></td>
                            <td class="px-4 py-3">
                                <StatusBadge v-if="asset.type === 'UNIQUE'" :status="asset.status"/>
                                <span v-else class="text-sm font-semibold text-slate-700">{{ asset.stock }} unit</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ asset.category?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ asset.location?.name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('assets.show', asset.id)"
                                          class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </Link>
                                    <Link v-if="canEdit" :href="route('assets.edit', asset.id)"
                                          class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </Link>
                                    <button v-if="canDelete" @click="confirmDelete(asset)"
                                            class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
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

        <Pagination :links="assets.links" :meta="assets.meta ?? assets"/>

        <ConfirmDialog
            :show="!!deleteTarget"
            title="Hapus Aset"
            :message="`Hapus aset &quot;${deleteTarget?.name}&quot;? Tindakan ini tidak dapat dibatalkan.`"
            :loading="deleteLoading"
            @confirm="doDelete"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>

