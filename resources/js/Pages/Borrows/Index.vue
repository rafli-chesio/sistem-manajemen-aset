<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    borrows:    { type: Object, required: true },
    filters:    { type: Object, default: () => ({}) },
    canApprove: { type: Boolean, default: false },
});

const page      = usePage();
const canCreate = page.props.auth.permissions.includes('borrow.create');

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const applyFilters = debounce(() => {
    router.get(route('borrows.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
}, 350);

watch([search, status], applyFilters);

const statusColors = {
    PENDING:  'bg-amber-50 border-amber-200',
    APPROVED: 'bg-emerald-50 border-emerald-200',
    REJECTED: 'bg-red-50 border-red-200',
    OVERDUE:  'bg-orange-50 border-orange-200',
    RETURNED: 'bg-slate-50 border-slate-200',
};

function formatDate(raw) {
    if (!raw) return '—';
    const d = new Date(raw);
    if (isNaN(d)) return raw;
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Peminjaman"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Daftar Peminjaman</h1>
        </template>

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input v-model="search" type="text" placeholder="Cari nama peminjam..."
                       class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
            </div>
            <select v-model="status" class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                <option value="">Semua Status</option>
                <option value="PENDING">Menunggu</option>
                <option value="APPROVED">Disetujui</option>
                <option value="REJECTED">Ditolak</option>
                <option value="OVERDUE">Terlambat</option>
                <option value="RETURNED">Dikembalikan</option>
            </select>
            <Link v-if="canCreate" :href="route('borrows.create')"
                  class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Permintaan
            </Link>
        </div>

        <div class="space-y-3">
            <div v-if="!borrows.data?.length" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-10 text-center text-slate-400">
                Tidak ada data peminjaman.
            </div>
            <Link v-for="b in borrows.data" :key="b.id" :href="route('borrows.show', b.id)"
                  class="block bg-white rounded-2xl border shadow-sm hover:shadow-md transition-all p-4 hover:border-indigo-200"
                  :class="statusColors[b.status] ?? 'border-slate-100'">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-indigo-600 font-bold text-sm">#{{ b.id }}</span>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">{{ b.user?.name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ b.items?.length ?? 0 }} barang
                                · Pinjam {{ formatDate(b.borrow_date) }}
                                · Kembali {{ formatDate(b.return_date) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <StatusBadge :status="b.status"/>
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </Link>
        </div>

        <Pagination :links="borrows.links" :meta="borrows.meta ?? borrows"/>
    </AuthenticatedLayout>
</template>

