<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    assetsByCategory:  { type: Array,  default: () => [] },
    assetsByCondition: { type: Object, default: () => ({}) },
    borrowsByStatus:   { type: Object, default: () => ({}) },
    borrowTrend:       { type: Array,  default: () => [] },
    topAssets:         { type: Array,  default: () => [] },
});

const conditionColors = {
    GOOD: '#10b981', FAIR: '#f59e0b', POOR: '#f97316', DAMAGED: '#ef4444',
};

const statusColors = {
    PENDING: '#f59e0b', APPROVED: '#10b981', REJECTED: '#ef4444',
    OVERDUE: '#f97316', RETURNED: '#94a3b8',
};

const conditionEntries  = Object.entries(props.assetsByCondition);
const statusEntries     = Object.entries(props.borrowsByStatus);
const totalAssets  = conditionEntries.reduce((s, [, v]) => s + Number(v), 0);
const totalBorrows = statusEntries.reduce((s, [, v]) => s + Number(v), 0);

const conditionLabels = { GOOD: 'Baik', FAIR: 'Cukup', POOR: 'Buruk', DAMAGED: 'Rusak' };
const statusLabels    = { PENDING: 'Menunggu', APPROVED: 'Disetujui', REJECTED: 'Ditolak', OVERDUE: 'Terlambat', RETURNED: 'Dikembalikan' };

function pct(val, total) {
    return total ? Math.round((Number(val) / total) * 100) : 0;
}
</script>

<template>
    <Head title="Laporan"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Laporan &amp; Statistik</h1>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- Condition Distribution -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-bold text-slate-700 mb-4">Kondisi Aset</h2>
                <div class="space-y-3">
                    <div v-for="[cond, count] in conditionEntries" :key="cond">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-slate-700">{{ conditionLabels[cond] ?? cond }}</span>
                            <span class="text-slate-500">{{ count }} ({{ pct(count, totalAssets) }}%)</span>
                        </div>
                        <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                 :style="{ width: pct(count, totalAssets) + '%', background: conditionColors[cond] ?? '#94a3b8' }"/>
                        </div>
                    </div>
                    <p v-if="!conditionEntries.length" class="text-slate-400 text-sm text-center py-4">Tidak ada data.</p>
                </div>
            </div>

            <!-- Borrow Status Distribution -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-bold text-slate-700 mb-4">Status Peminjaman</h2>
                <div class="space-y-3">
                    <div v-for="[stat, count] in statusEntries" :key="stat">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-slate-700">{{ statusLabels[stat] ?? stat }}</span>
                            <span class="text-slate-500">{{ count }} ({{ pct(count, totalBorrows) }}%)</span>
                        </div>
                        <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                 :style="{ width: pct(count, totalBorrows) + '%', background: statusColors[stat] ?? '#94a3b8' }"/>
                        </div>
                    </div>
                    <p v-if="!statusEntries.length" class="text-slate-400 text-sm text-center py-4">Tidak ada data.</p>
                </div>
            </div>

            <!-- Assets by Category -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-bold text-slate-700 mb-4">Aset per Kategori</h2>
                <div class="space-y-2">
                    <div v-for="cat in assetsByCategory" :key="cat.name"
                         class="flex items-center justify-between px-4 py-2.5 rounded-xl bg-slate-50">
                        <span class="text-sm font-medium text-slate-700">{{ cat.name }}</span>
                        <span class="text-sm font-bold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-lg">{{ cat.count }}</span>
                    </div>
                    <p v-if="!assetsByCategory.length" class="text-slate-400 text-sm text-center py-4">Tidak ada data.</p>
                </div>
            </div>

            <!-- Top Borrowed Assets -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-bold text-slate-700 mb-4">Aset Paling Sering Dipinjam</h2>
                <div class="space-y-2">
                    <div v-for="(asset, idx) in topAssets" :key="asset.code"
                         class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-slate-50">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center flex-shrink-0">
                            {{ idx + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-700 truncate">{{ asset.name }}</p>
                            <p class="text-xs font-mono text-slate-400">{{ asset.code }}</p>
                        </div>
                        <span class="text-sm font-bold text-slate-600">{{ asset.count }}×</span>
                    </div>
                    <p v-if="!topAssets.length" class="text-slate-400 text-sm text-center py-4">Belum ada peminjaman.</p>
                </div>
            </div>

            <!-- Borrow Trend -->
            <div v-if="borrowTrend.length" class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h2 class="font-bold text-slate-700 mb-4">Tren Peminjaman (12 Bulan Terakhir)</h2>
                <div class="flex items-end gap-2 h-32 overflow-x-auto pb-2">
                    <div v-for="month in borrowTrend" :key="month.month"
                         class="flex flex-col items-center gap-1 flex-shrink-0 min-w-10">
                        <span class="text-xs font-semibold text-slate-600">{{ month.total }}</span>
                        <div class="w-10 bg-indigo-500 rounded-t-md transition-all hover:bg-indigo-600"
                             :style="{ height: Math.max(4, (month.total / Math.max(...borrowTrend.map(b => b.total))) * 96) + 'px' }"/>
                        <span class="text-xs text-slate-400 whitespace-nowrap">{{ month.month.slice(0, 3) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
