<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    users:   { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roles:   { type: Array,  default: () => [] },
});

const search = ref(props.filters.search ?? '');
const role   = ref(props.filters.role ?? '');

const applyFilters = debounce(() => {
    router.get(route('users.index'), {
        search: search.value || undefined,
        role:   role.value   || undefined,
    }, { preserveState: true, replace: true });
}, 350);

watch([search, role], applyFilters);

const deleteTarget  = ref(null);
const deleteLoading = ref(false);

const roleLabels = {
    super_admin: { label: 'Super Admin', class: 'bg-purple-100 text-purple-700 ring-purple-300' },
    viewer:      { label: 'Viewer',      class: 'bg-sky-100 text-sky-700 ring-sky-300' },
    kajur:       { label: 'Kajur',       class: 'bg-emerald-100 text-emerald-700 ring-emerald-300' },
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
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Manajemen Pengguna</h1>
        </template>

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input v-model="search" type="text" placeholder="Cari nama atau email..."
                       class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
            </div>
            <select v-model="role" class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                <option value="">Semua Peran</option>
                <option v-for="r in roles" :key="r" :value="r">{{ roleLabels[r]?.label ?? r }}</option>
            </select>
            <Link :href="route('users.create')"
                  class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Pengguna
            </Link>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            <th class="px-6 py-3">Pengguna</th>
                            <th class="px-6 py-3">NIP</th>
                            <th class="px-6 py-3">Jurusan</th>
                            <th class="px-6 py-3">Peran</th>
                            <th class="px-6 py-3">Dibuat</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="!users.data?.length">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">Tidak ada pengguna.</td>
                        </tr>
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-indigo-600 text-xs font-bold">{{ u.name?.charAt(0)?.toUpperCase() }}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ u.name }}</p>
                                        <p class="text-xs text-slate-400">{{ u.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ u.nip ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <div v-if="Array.isArray(u.department) && u.department.length" class="flex flex-wrap gap-1">
                                    <span v-for="d in u.department" :key="d" class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[11px] font-semibold border border-slate-200">
                                        {{ d }}
                                    </span>
                                </div>
                                <span v-else-if="typeof u.department === 'string' && u.department && u.department !== '[]'" class="text-slate-600 text-xs">{{ u.department }}</span>
                                <span v-else class="text-slate-400 text-xs">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="u.roles?.[0]"
                                      class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold ring-1 ring-inset"
                                      :class="roleLabels[u.roles[0]?.name]?.class ?? 'bg-gray-100 text-gray-600 ring-gray-200'">
                                    {{ roleLabels[u.roles[0]?.name]?.label ?? u.roles[0]?.name }}
                                </span>
                                <span v-else class="text-slate-400 text-xs">—</span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 text-xs">{{ u.created_at?.slice(0,10) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('users.edit', u.id)"
                                          class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </Link>
                                    <button @click="deleteTarget = u"
                                            class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
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

        <Pagination :links="users.links" :meta="users.meta ?? users"/>

        <ConfirmDialog
            :show="!!deleteTarget"
            title="Hapus Pengguna"
            :message="`Hapus pengguna &quot;${deleteTarget?.name}&quot;? Tindakan ini tidak dapat dibatalkan.`"
            :loading="deleteLoading"
            @confirm="doDelete"
            @cancel="deleteTarget = null"
        />
    </AuthenticatedLayout>
</template>

