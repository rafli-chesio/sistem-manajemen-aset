<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    users:   { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roles:   { type: Object, default: () => ({}) }, // { ADMIN: 'Administrator', ... }
});

const search = ref(props.filters.search ?? '');
const role   = ref(props.filters.role   ?? '');

const applyFilters = debounce(() => {
    router.get(route('users.index'), {
        search: search.value || undefined,
        role:   role.value   || undefined,
    }, { preserveState: true, replace: true });
}, 350);

watch([search, role], applyFilters);

const deleteTarget  = ref(null);
const deleteLoading = ref(false);

const roleBadge = {
    ADMIN:  { label: 'Administrator', cls: 'bg-purple-100 text-purple-700 ring-purple-300' },
    KAJUR:  { label: 'Kepala Jurusan', cls: 'bg-emerald-100 text-emerald-700 ring-emerald-300' },
    VIEWER: { label: 'Viewer',         cls: 'bg-sky-100 text-sky-700 ring-sky-300' },
};

function doDelete() {
    deleteLoading.value = true;
    router.delete(route('users.destroy', deleteTarget.value.id), {
        onFinish: () => { deleteLoading.value = false; deleteTarget.value = null; },
    });
}
</script>

<template>
    <Head title="Manajemen Pengguna"/>
    <AuthenticatedLayout>
        <div class="w-full">

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Manajemen Pengguna</h1>
                    <p class="text-sm text-slate-400 mt-0.5">{{ users.meta?.total ?? users.data.length }} pengguna terdaftar</p>
                </div>
                <Link :href="route('users.create')"
                      class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Pengguna
                </Link>
            </div>

            <!-- Search + Filter -->
            <div class="flex flex-col sm:flex-row gap-3 mb-5">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Cari nama, email, atau NIP..."
                           class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-200 outline-none"/>
                </div>
                <select v-model="role" class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-200 outline-none bg-white">
                    <option value="">Semua Peran</option>
                    <option v-for="(label, key) in roles" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="px-5 py-4 text-[10px] uppercase text-slate-400 tracking-wider font-semibold text-left">Pengguna</th>
                                <th class="px-4 py-4 text-[10px] uppercase text-slate-400 tracking-wider font-semibold text-left">NIP</th>
                                <th class="px-4 py-4 text-[10px] uppercase text-slate-400 tracking-wider font-semibold text-left">Jurusan</th>
                                <th class="px-4 py-4 text-[10px] uppercase text-slate-400 tracking-wider font-semibold text-left">Peran</th>
                                <th class="px-5 py-4 text-[10px] uppercase text-slate-400 tracking-wider font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <!-- Empty State -->
                            <tr v-if="!users.data?.length">
                                <td colspan="5" class="py-2">
                                    <EmptyState
                                        icon="users"
                                        :title="filters.search ? 'Pengguna tidak ditemukan' : 'Belum ada pengguna'"
                                        :description="filters.search ? `Tidak ada pengguna yang cocok dengan '${filters.search}'.` : 'Belum ada pengguna yang didaftarkan.'"
                                        action-label="Tambah Pengguna"
                                        :action-route="route('users.create')"
                                    />
                                </td>
                            </tr>

                            <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50/60 transition-colors">
                                <!-- Nama + Email -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                             :class="roleBadge[u.role]?.cls ?? 'bg-slate-100 text-slate-600 ring-slate-200'">
                                            {{ u.name?.charAt(0)?.toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ u.name }}</p>
                                            <p class="text-xs text-slate-400">{{ u.email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- NIP -->
                                <td class="px-4 py-3.5 font-mono text-xs text-slate-500">{{ u.nip ?? '—' }}</td>

                                <!-- Jurusan -->
                                <td class="px-4 py-3.5">
                                    <div v-if="Array.isArray(u.department) && u.department.length" class="flex flex-wrap gap-1">
                                        <span v-for="d in u.department" :key="d"
                                              class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[11px] font-semibold border border-slate-200">
                                            {{ d }}
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">—</span>
                                </td>

                                <!-- Role Badge -->
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset"
                                          :class="roleBadge[u.role]?.cls ?? 'bg-slate-100 text-slate-600 ring-slate-200'">
                                        {{ roleBadge[u.role]?.label ?? u.role }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('users.edit', u.id)"
                                              class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>
                                        <button @click="deleteTarget = u"
                                                class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
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
            <div v-if="users.links?.length > 3" class="flex items-center justify-end gap-1 mt-4">
                <Link v-for="(link, idx) in users.links" :key="idx"
                      :href="link.url ?? '#'"
                      v-html="link.label.replace('&laquo; Previous', '←').replace('Next &raquo;', '→')"
                      class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
                      :class="[
                          link.active ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-100 hover:border-indigo-200 text-slate-500',
                          !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                      ]"/>
            </div>
        </div>

        <!-- Confirm Delete -->
        <ConfirmModal
            :show="!!deleteTarget"
            type="danger"
            title="Hapus Pengguna"
            :message="`Pengguna '${deleteTarget?.name}' akan dihapus dari sistem. Tindakan ini tidak dapat dibatalkan.`"
            confirm-label="Ya, Hapus"
            @confirm="doDelete"
            @cancel="deleteTarget = null"
        />
    </AuthenticatedLayout>
</template>
