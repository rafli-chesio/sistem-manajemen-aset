<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    stats:         { type: Object, required: true },
    recentBorrows: { type: Array,  required: true },
});

const statCards = [
    { label: 'Total Aset',      key: 'total_assets',     icon: 'cube',      color: 'from-violet-500 to-purple-600' },
    { label: 'Tersedia',        key: 'available_assets',  icon: 'check',     color: 'from-emerald-500 to-green-600' },
    { label: 'Dipinjam',        key: 'borrowed_assets',   icon: 'arrow-out', color: 'from-blue-500 to-indigo-600' },
    { label: 'Menunggu Perset.', key: 'pending_borrows',  icon: 'clock',     color: 'from-amber-500 to-orange-500' },
    { label: 'Terlambat',       key: 'overdue_borrows',   icon: 'exclamation', color: 'from-red-500 to-rose-600' },
    { label: 'Pengguna',        key: 'total_users',       icon: 'users',     color: 'from-sky-500 to-cyan-600' },
];
</script>

<template>
    <Head title="Dashboard"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Dashboard</h1>
        </template>

        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
            <div v-for="card in statCards" :key="card.key"
                 class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-col gap-3 hover:shadow-md transition-shadow">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br flex items-center justify-center shadow"
                     :class="card.color">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="card.icon==='cube'"       stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        <path v-if="card.icon==='check'"      stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        <path v-if="card.icon==='arrow-out'"  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        <path v-if="card.icon==='clock'"      stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        <path v-if="card.icon==='exclamation'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        <path v-if="card.icon==='users'"      stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ stats[card.key] ?? 0 }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ card.label }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-700">Peminjaman Terbaru</h2>
                <Link :href="route('borrows.index')" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    Lihat Semua →
                </Link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            <th class="px-6 py-3">#</th>
                            <th class="px-6 py-3">Peminjam</th>
                            <th class="px-6 py-3">Jml Barang</th>
                            <th class="px-6 py-3">Tgl Pinjam</th>
                            <th class="px-6 py-3">Tgl Kembali</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="recentBorrows.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                Belum ada peminjaman.
                            </td>
                        </tr>
                        <tr v-for="b in recentBorrows" :key="b.id"
                            class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3 font-mono text-slate-400 text-xs">#{{ b.id }}</td>
                            <td class="px-6 py-3 font-medium text-slate-700">{{ b.user_name }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ b.item_count }} barang</td>
                            <td class="px-6 py-3 text-slate-600">{{ b.borrow_date }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ b.return_date }}</td>
                            <td class="px-6 py-3"><StatusBadge :status="b.status"/></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
