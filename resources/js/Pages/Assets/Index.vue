<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
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

const page      = usePage();
const canCreate = page.props.auth.permissions.includes('asset.create');
const canEdit   = page.props.auth.permissions.includes('asset.edit');
const canDelete = page.props.auth.permissions.includes('asset.delete');

const search     = ref(props.filters.search ?? '');
const status     = ref(props.filters.status ?? '');

const deleteTarget  = ref(null);
const deleteLoading = ref(false);

const applyFilters = debounce(() => {
    router.get(route('assets.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
}, 350);

watch([search, status], applyFilters);

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
    <Head title="Aset"/>
    <AuthenticatedLayout>
        <!-- Konten Utama (Tanpa extra padding karena AuthenticatedLayout sudah ada padding) -->
        <div class="w-full">
            
            <!-- Toolbar Atas: Clean & Flat -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
                <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Aset</h1>
                
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
                    <!-- Search Bar abu-abu terang, rounded-full -->
                    <div class="relative w-full sm:w-64 flex-shrink-0">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input v-model="search" type="text" placeholder="Search assets..." 
                               class="w-full pl-10 pr-4 py-2 bg-gray-100 border-transparent rounded-full text-sm text-gray-700 placeholder-gray-400 focus:border-gray-300 focus:bg-white focus:ring-0 transition-colors outline-none"/>
                    </div>
                    
                    <!-- Filter Status Outline Flat -->
                    <div class="flex items-center gap-2 bg-transparent border border-gray-200 rounded-md px-3 py-1.5 w-full sm:w-auto">
                        <span class="text-sm text-gray-500 whitespace-nowrap">Status :</span>
                        <select v-model="status" class="bg-transparent border-none text-sm font-medium text-gray-700 py-0 pl-1 pr-6 focus:ring-0 cursor-pointer outline-none w-full">
                            <option value="">All</option>
                            <option value="AVAILABLE">Tersedia</option>
                            <option value="BORROWED">Dipinjam</option>
                            <option value="DAMAGED">Rusak</option>
                            <option value="LOST">Hilang</option>
                        </select>
                    </div>

                    <!-- Tombol Coral/Merah Solid -->
                    <Link v-if="canCreate" :href="route('assets.create')"
                          class="flex items-center justify-center gap-2 px-5 py-2 bg-[#f46c6c] hover:bg-[#e85b5b] text-white rounded-md text-sm font-medium transition-colors whitespace-nowrap w-full sm:w-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create Asset
                    </Link>
                </div>
            </div>

            <!-- Modern Borderless Table (Super Flat) -->
            <div class="bg-white rounded-xl overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="pl-6 py-5 w-12 text-center">
                                    <input type="checkbox" class="w-4 h-4 rounded text-indigo-500 border-gray-300 focus:ring-0 cursor-pointer"/>
                                </th>
                                <th class="px-4 py-5 text-[10px] uppercase text-gray-400 tracking-wider font-semibold">Asset Name</th>
                                <th class="px-4 py-5 text-[10px] uppercase text-gray-400 tracking-wider font-semibold">Type & Category</th>
                                <th class="px-4 py-5 text-[10px] uppercase text-gray-400 tracking-wider font-semibold text-center">Status</th>
                                <th class="px-6 py-5 text-[10px] uppercase text-gray-400 tracking-wider font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70">
                            <!-- Empty State Minimalis -->
                            <tr v-if="!assets.data?.length">
                                <td colspan="5" class="py-16 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-3">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-400">Tidak ada aset yang ditemukan.</p>
                                </td>
                            </tr>

                            <!-- Data Rows -->
                            <tr v-for="asset in assets.data" :key="asset.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="pl-6 py-4 text-center">
                                    <input type="checkbox" class="w-4 h-4 rounded text-indigo-500 border-gray-300 focus:ring-0 cursor-pointer"/>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-4">
                                        <!-- Thumbnail Bulat (Circle) -->
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 flex items-center justify-center">
                                            <img v-if="asset.images?.length" :src="'/storage/' + asset.images[0].path" class="w-full h-full object-cover" :alt="asset.name"/>
                                            <svg v-else class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M4 4h16v16H4V4zm2 2v12h12V6H6zm10 10H8v-2h8v2zm0-4H8v-2h8v2z"/></svg>
                                        </div>
                                        <div>
                                            <Link :href="route('assets.show', asset.id)" class="text-[13px] font-bold text-gray-800 hover:text-indigo-600 transition-colors">
                                                {{ asset.name }}
                                            </Link>
                                            <p class="text-[11px] text-gray-400 mt-0.5">{{ asset.asset_code || '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-[13px] font-medium text-gray-700">{{ asset.type === 'UNIQUE' ? 'Unik' : 'Habis Pakai' }}</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">{{ asset.category?.name ?? 'Umum' }}</p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <!-- Status Outline Badges -->
                                    <span v-if="asset.type === 'UNIQUE'" class="inline-flex items-center justify-center px-3 py-1 text-[11px] font-semibold rounded-full border bg-transparent"
                                        :class="{
                                            'border-[#3cb371] text-[#3cb371]': asset.status === 'AVAILABLE',
                                            'border-[#f6a623] text-[#f6a623]': asset.status === 'BORROWED',
                                            'border-[#e74c3c] text-[#e74c3c]': asset.status === 'DAMAGED',
                                            'border-gray-500 text-gray-500': asset.status === 'LOST'
                                        }">
                                        {{ asset.status === 'AVAILABLE' ? 'Tersedia' : asset.status === 'BORROWED' ? 'Dipinjam' : asset.status === 'DAMAGED' ? 'Rusak' : 'Hilang' }}
                                    </span>
                                    <span v-else class="inline-flex items-center justify-center px-3 py-1 text-[11px] font-semibold rounded-full border border-indigo-400 text-indigo-500 bg-transparent">
                                        {{ asset.stock }} Unit
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <!-- Aksi: Icon Berjajar -->
                                    <div class="flex items-center justify-end gap-3 text-gray-400">
                                        <Link v-if="canEdit" :href="route('assets.edit', asset.id)" class="hover:text-gray-800 transition-colors" title="Edit">
                                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </Link>
                                        <button v-if="canDelete" @click="confirmDelete(asset)" class="hover:text-red-500 transition-colors" title="Delete">
                                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer & Pagination -->
            <div class="flex flex-col sm:flex-row items-center justify-between text-sm text-gray-400 gap-4">
                <div>
                    Displaying <span class="font-bold text-gray-700">{{ assets.meta?.to ?? assets.data.length }}</span> Out of <span class="font-bold text-gray-700">{{ assets.meta?.total ?? assets.data.length }}</span>
                </div>
                
                <div v-if="assets.links && assets.links.length > 3" class="flex items-center gap-1">
                    <Link v-for="(link, idx) in assets.links" :key="idx"
                          :href="link.url ?? '#'"
                          v-html="link.label.replace('Previous', '&lt;').replace('Next', '&gt;')"
                          class="px-3 py-1.5 rounded-md border text-xs font-semibold transition-colors"
                          :class="[
                              link.active ? 'bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-white border-transparent hover:border-gray-200 hover:bg-gray-50 text-gray-500',
                              !link.url ? 'opacity-50 cursor-not-allowed' : ''
                          ]">
                    </Link>
                </div>
            </div>

        </div>

        <!-- Dialog Hapus -->
        <ConfirmDialog
            :show="!!deleteTarget"
            title="Delete Asset"
            :message="`Are you sure you want to delete asset &quot;${deleteTarget?.name}&quot;?`"
            :loading="deleteLoading"
            @confirm="doDelete"
            @cancel="cancelDelete"
        />
    </AuthenticatedLayout>
</template>

