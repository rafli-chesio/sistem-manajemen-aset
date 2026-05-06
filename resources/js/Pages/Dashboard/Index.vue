<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';
import { computed } from 'vue';

const props = defineProps({
    stats:         { type: Object, required: true },
    recentBorrows: { type: Array,  required: true },
    auditLogs:     { type: Array,  default: () => [] },
    quickResults:  { type: Array,  default: () => [] },
    searchQuery:   { type: String, default: '' },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const isAdmin  = computed(() => user.value.role === 'ADMIN');
const isKajur  = computed(() => user.value.role === 'KAJUR');
const isViewer = computed(() => user.value.role === 'VIEWER');

const roleLabelMap = { ADMIN: 'Administrator', KAJUR: 'Kepala Jurusan', VIEWER: 'Viewer' };
const roleLabel = computed(() => roleLabelMap[user.value.role] ?? user.value.role);

const adminCards = [
    { label: 'Total Aset',      key: 'total_assets',     iconColor: 'text-blue-500', iconBg: 'bg-blue-50', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { label: 'Tersedia',        key: 'available_assets', iconColor: 'text-emerald-500', iconBg: 'bg-emerald-50', icon: 'M5 13l4 4L19 7' },
    { label: 'Menunggu Persetujuan', key: 'pending_borrows', iconColor: 'text-amber-500', iconBg: 'bg-amber-50', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Terlambat',       key: 'overdue_borrows',  iconColor: 'text-rose-500', iconBg: 'bg-rose-50', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
];

const kajurCards = [
    { label: 'Menunggu Persetujuan', key: 'my_pending', iconColor: 'text-amber-500', iconBg: 'bg-amber-50', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Disetujui',            key: 'my_approved',iconColor: 'text-emerald-500', iconBg: 'bg-emerald-50', icon: 'M5 13l4 4L19 7' },
    { label: 'Ditolak',              key: 'my_rejected',iconColor: 'text-rose-500', iconBg: 'bg-rose-50', icon: 'M6 18L18 6M6 6l12 12' },
];

const currentCards = computed(() => (isAdmin.value || isViewer.value) ? adminCards : kajurCards);

// Quick Search
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
const searchInput = ref(props.searchQuery);
let searchTimer = null;
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('dashboard'), { q: searchInput.value }, { preserveState: true, replace: true });
    }, 350);
}

const conditionBadge = {
    BAIK: 'bg-emerald-50 text-emerald-700',
    RUSAK_RINGAN: 'bg-amber-50 text-amber-700',
    RUSAK_BERAT: 'bg-red-50 text-red-700',
};
const statusBadge = {
    AVAILABLE: 'bg-emerald-100 text-emerald-700',
    BORROWED: 'bg-blue-100 text-blue-700',
    MAINTENANCE: 'bg-amber-100 text-amber-700',
    LOST: 'bg-red-100 text-red-700',
    ARCHIVED: 'bg-slate-100 text-slate-500',
};

// Static calendar generation
const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const dates = Array.from({length: 31}, (_, i) => i + 1);

</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        
        <!-- MIDDLE CONTENT -->
        <!-- Search & Date Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-8 lg:mt-0">
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input v-model="searchInput" @input="onSearchInput"
                       type="text"
                       class="w-full pl-10 pr-4 py-2.5 bg-white border-none rounded-2xl text-sm focus:ring-2 focus:ring-indigo-100 shadow-[0_2px_10px_rgba(0,0,0,0.02)] text-slate-600 placeholder-slate-400"
                       placeholder="Cari aset... (contoh: Proyektor, PRY-001)">

                <!-- Quick Results Dropdown -->
                <div v-if="quickResults.length > 0"
                     class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 overflow-hidden">
                    <div class="p-2">
                        <div v-for="a in quickResults" :key="a.id"
                             class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 cursor-pointer group transition-colors"
                             @click="router.visit(route('assets.show', a.id))">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                                 :class="a.available ? 'bg-emerald-50' : 'bg-slate-100'">
                                <svg class="w-4 h-4" :class="a.available ? 'text-emerald-500' : 'text-slate-400'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ a.name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ a.code }} · {{ a.category }}</p>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                                  :class="statusBadge[a.status]">{{ a.status }}</span>
                        </div>
                    </div>
                    <div class="border-t border-slate-50 px-4 py-2.5">
                        <Link :href="route('assets.index', { search: searchInput })" class="text-xs text-indigo-600 font-semibold hover:underline">
                            Lihat semua hasil di halaman Aset →
                        </Link>
                    </div>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 whitespace-nowrap">
                {{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', weekday: 'long' }) }}
            </div>
        </div>

        <!-- Summary Cards Section -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-slate-800 tracking-tight">Ringkasan</h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
                <div v-for="card in currentCards" :key="card.key"
                     class="bg-white border border-slate-100 rounded-2xl p-5 flex flex-col shadow-sm relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                    
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] sm:text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 leading-snug">{{ card.label }}</p>
                            <p class="text-2xl sm:text-3xl font-extrabold text-gray-800 tracking-tight">{{ stats[card.key] ?? 0 }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" :class="card.iconBg">
                            <svg class="w-5 h-5" :class="card.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kajur Specific Shortcut -->
        <div v-if="isKajur" class="mt-2">
             <Link :href="route('borrows.create')" class="inline-flex items-center gap-2 px-5 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Pinjam Aset Baru
            </Link>
        </div>

        <!-- Table Section -->
        <div class="mt-2 bg-white rounded-[24px] shadow-[0_4px_24px_rgba(0,0,0,0.02)] overflow-hidden">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-50">
                <h2 class="text-lg font-bold text-slate-800 tracking-tight">{{ isKajur ? 'Riwayat Peminjaman Saya' : 'Peminjaman Terbaru' }}</h2>
                <Link :href="route('borrows.index')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg transition-colors">
                    View All &rsaquo;
                </Link>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="text-xs font-semibold text-slate-400 border-b border-slate-50 uppercase tracking-wider">
                            <th class="px-6 py-4 font-medium">#</th>
                            <th v-if="!isKajur" class="px-6 py-4 font-medium">Peminjam</th>
                            <th class="px-6 py-4 font-medium">Jml Barang</th>
                            <th class="px-6 py-4 font-medium">Tgl Pinjam</th>
                            <th class="px-6 py-4 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50/50">
                        <tr v-if="recentBorrows.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">Belum ada peminjaman.</td>
                        </tr>
                        <tr v-for="b in recentBorrows" :key="b.id" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs text-slate-400 bg-slate-100 px-2 py-1 rounded-md">#{{ b.id }}</span>
                            </td>
                            <td v-if="!isKajur" class="px-6 py-4 font-medium text-slate-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                        {{ b.user_name.charAt(0) }}
                                    </div>
                                    {{ b.user_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">{{ b.item_count }} Item</td>
                            <td class="px-6 py-4 text-slate-500">{{ b.borrow_date }}</td>
                            <td class="px-6 py-4"><StatusBadge :status="b.status"/></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIGHT PANEL (Injected into AuthenticatedLayout via slot) -->
        <template #right>
            <div class="p-6 flex flex-col h-full gap-8">
                
                <!-- Profile Header -->
                <div class="flex flex-col items-center mt-2">
                    <div class="relative mb-3">
                        <img v-if="user.avatar" :src="user.avatar" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md">
                        <div v-else class="w-20 h-20 rounded-full bg-indigo-600 text-white flex items-center justify-center text-2xl font-bold border-4 border-white shadow-md">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="absolute bottom-1 right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></div>
                    </div>
                    <h3 class="font-bold text-slate-800 text-lg">{{ user.name }}</h3>
                    <p class="text-xs font-medium text-slate-400 mt-0.5 uppercase tracking-wider">{{ roleLabel }}</p>
                    <Link :href="route('profile.edit')" class="mt-4 px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-full transition-colors">
                        Edit Profile
                    </Link>
                </div>

                <!-- Static Calendar Widget -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <button class="p-1 text-slate-400 hover:bg-slate-100 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                        <h4 class="font-bold text-slate-700 text-sm">Mei 2026</h4>
                        <button class="p-1 text-slate-400 hover:bg-slate-100 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center mb-2">
                        <div v-for="d in days" :key="d" class="text-[10px] font-bold text-slate-400 uppercase">{{ d }}</div>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center">
                        <!-- Empty slots for alignment -->
                        <div v-for="i in 5" :key="'e'+i" class="p-2 text-xs text-slate-300"></div>
                        <div v-for="date in dates" :key="date" class="p-2 text-xs font-medium text-slate-600 relative flex justify-center items-center">
                            <span v-if="date === 15" class="w-7 h-7 bg-indigo-600 text-white rounded-full flex items-center justify-center">{{ date }}</span>
                            <span v-else-if="date === 20 || date === 25" class="w-7 h-7 bg-indigo-50 text-indigo-600 font-bold rounded-full flex items-center justify-center">{{ date }}</span>
                            <span v-else>{{ date }}</span>
                        </div>
                    </div>
                </div>

                <!-- Timeline / Reminders -->
                <div class="flex-1">
                    <h4 class="font-bold text-slate-800 text-sm mb-4">{{ isAdmin ? 'Aktivitas Terkini' : 'Reminders' }}</h4>
                    
                    <!-- Super Admin: Audit Logs Timeline -->
                    <div v-if="isAdmin" class="relative pl-4 border-l-2 border-slate-100 space-y-6">
                        <div v-for="log in auditLogs" :key="log.id" class="relative">
                            <div class="absolute -left-[21px] w-2.5 h-2.5 rounded-full bg-white border-2 border-indigo-500"></div>
                            <p class="text-xs font-medium text-slate-800">{{ log.user }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ log.action }}</p>
                            <p class="text-[10px] text-slate-400 mt-1 font-medium">{{ log.created_at }}</p>
                        </div>
                        <div v-if="auditLogs.length === 0" class="text-xs text-slate-400">Belum ada aktivitas.</div>
                    </div>

                    <!-- Kajur: Static Reminders -->
                    <div v-else class="space-y-4">
                        <div class="flex gap-3 items-start p-3 rounded-xl bg-slate-50/80 hover:bg-slate-100 transition-colors border border-slate-100/50">
                            <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center text-rose-500 flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-700">Pengembalian Aset</p>
                                <p class="text-[10px] text-slate-400 mt-0.5 font-medium">15 Mei 2026, Jumat</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </AuthenticatedLayout>
</template>
