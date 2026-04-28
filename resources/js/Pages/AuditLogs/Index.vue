<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    logs:    { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const action  = ref(props.filters.action ?? '');
const userId  = ref(props.filters.user_id ?? '');

const applyFilters = debounce(() => {
    router.get(route('audit-logs.index'), {
        action:  action.value  || undefined,
        user_id: userId.value  || undefined,
    }, { preserveState: true, replace: true });
}, 400);

watch([action, userId], applyFilters);

const actionColors = {
    'asset.created':   'bg-emerald-100 text-emerald-700',
    'asset.updated':   'bg-blue-100 text-blue-700',
    'asset.deleted':   'bg-red-100 text-red-700',
    'asset.marked_lost': 'bg-gray-100 text-gray-700',
    'borrow.created':  'bg-violet-100 text-violet-700',
    'borrow.approved': 'bg-emerald-100 text-emerald-700',
    'borrow.rejected': 'bg-red-100 text-red-700',
    'borrow.overdue':  'bg-orange-100 text-orange-700',
    'borrow.expired':  'bg-slate-100 text-slate-600',
    'return.processed':'bg-indigo-100 text-indigo-700',
};

function actionColor(act) {
    return actionColors[act] ?? 'bg-gray-100 text-gray-600';
}

function formatAction(act) {
    return act?.replace('.', ': ').replace(/_/g, ' ');
}
</script>

<template>
    <Head title="Log Audit"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Log Audit Sistem</h1>
        </template>

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1">
                <input v-model="action" type="text" placeholder="Filter aksi (cth. asset.created)..."
                       class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Pengguna</th>
                            <th class="px-5 py-3">Aksi</th>
                            <th class="px-5 py-3">Entitas</th>
                            <th class="px-5 py-3">IP</th>
                            <th class="px-5 py-3">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="!logs.data?.length">
                            <td colspan="6" class="px-5 py-10 text-center text-slate-400">Tidak ada log audit.</td>
                        </tr>
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50 transition-colors align-top">
                            <td class="px-5 py-3 text-xs text-slate-400 font-mono whitespace-nowrap">
                                {{ log.created_at?.slice(0,19).replace('T', ' ') }}
                            </td>
                            <td class="px-5 py-3">
                                <p v-if="log.user" class="font-medium text-slate-700 text-xs">{{ log.user.name }}</p>
                                <p v-else class="text-slate-400 text-xs italic">Sistem</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold capitalize"
                                      :class="actionColor(log.action)">
                                    {{ formatAction(log.action) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                <span v-if="log.entity_type" class="font-mono">
                                    {{ log.entity_type?.split('\\').pop() }} #{{ log.entity_id }}
                                </span>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="px-5 py-3 text-xs font-mono text-slate-400">{{ log.ip_address ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <details v-if="log.metadata && Object.keys(log.metadata).length" class="text-xs">
                                    <summary class="cursor-pointer text-indigo-500 hover:text-indigo-700 font-medium">Lihat</summary>
                                    <pre class="mt-1 text-xs text-slate-500 bg-slate-50 rounded-lg p-2 max-w-xs overflow-x-auto">{{ JSON.stringify(log.metadata, null, 2) }}</pre>
                                </details>
                                <span v-else class="text-slate-300 text-xs">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Pagination :links="logs.links" :meta="logs.meta ?? logs"/>
    </AuthenticatedLayout>
</template>

