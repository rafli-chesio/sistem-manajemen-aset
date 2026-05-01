<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    assetsByCategory:  { type: Array,  default: () => [] },
    assetsByCondition: { type: Object, default: () => ({}) },
    borrowsByStatus:   { type: Object, default: () => ({}) },
    borrowTrend:       { type: Array,  default: () => [] },
    topAssets:         { type: Array,  default: () => [] },
});

const conditionColors = {
    GOOD: { bg: 'bg-emerald-500' },
    FAIR: { bg: 'bg-amber-500' },
    POOR: { bg: 'bg-orange-500' },
    DAMAGED: { bg: 'bg-red-500' },
};

const statusColors = {
    PENDING: { bg: 'bg-amber-500' },
    APPROVED: { bg: 'bg-emerald-500' },
    REJECTED: { bg: 'bg-red-500' },
    OVERDUE: { bg: 'bg-rose-500' },
    RETURNED: { bg: 'bg-slate-500' },
};

const conditionEntries  = Object.entries(props.assetsByCondition);
const statusEntries     = Object.entries(props.borrowsByStatus);
const totalAssets  = conditionEntries.reduce((s, [, v]) => s + Number(v), 0);
const totalBorrows = statusEntries.reduce((s, [, v]) => s + Number(v), 0);

const borrowedAssetsCount = Number(props.borrowsByStatus['APPROVED'] || 0) + Number(props.borrowsByStatus['OVERDUE'] || 0);
const damagedAssetsCount = Number(props.assetsByCondition['POOR'] || 0) + Number(props.assetsByCondition['DAMAGED'] || 0);

const conditionLabels = { GOOD: 'Baik', FAIR: 'Cukup', POOR: 'Buruk', DAMAGED: 'Rusak' };
const statusLabels    = { PENDING: 'Menunggu', APPROVED: 'Disetujui', REJECTED: 'Ditolak', OVERDUE: 'Terlambat', RETURNED: 'Dikembalikan' };

function pct(val, total) {
    return total ? Math.round((Number(val) / total) * 100) : 0;
}

// SVG Area Chart Calculations
const maxTrend = computed(() => Math.max(...props.borrowTrend.map(b => b.total), 1));

const svgViewBoxWidth = 1000;
const svgViewBoxHeight = 120;

const trendPoints = computed(() => {
    if(!props.borrowTrend.length) return '';
    const points = [];
    const step = svgViewBoxWidth / (props.borrowTrend.length > 1 ? props.borrowTrend.length - 1 : 1);
    
    props.borrowTrend.forEach((month, idx) => {
        const x = idx * step;
        // Map total to Y coordinate (0 at top, height at bottom). Padding top 20px
        const y = svgViewBoxHeight - ((month.total / maxTrend.value) * (svgViewBoxHeight - 30)); 
        points.push(`${x},${y}`);
    });
    
    return points.join(' ');
});

const areaPoints = computed(() => {
    if(!props.borrowTrend.length) return '';
    return `0,${svgViewBoxHeight} ${trendPoints.value} ${svgViewBoxWidth},${svgViewBoxHeight}`;
});
</script>

<template>
    <Head title="Laporan"/>
    <AuthenticatedLayout>
        <!-- Compact Header -->
        <template #header>
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <h1 class="text-slate-800 font-extrabold text-xl tracking-tight">Laporan Eksekutif</h1>
                <div class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                    Sistem Manajemen Aset
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-4 pb-8 pt-2">
            
            <!-- ROW 1: 4 Top Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total Aset</p>
                        <p class="text-xl font-black text-slate-800">{{ totalAssets }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Sedang Dipinjam</p>
                        <p class="text-xl font-black text-slate-800">{{ borrowedAssetsCount }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-red-50 text-red-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Rusak/Perbaikan</p>
                        <p class="text-xl font-black text-slate-800">{{ damagedAssetsCount }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total Transaksi</p>
                        <p class="text-xl font-black text-slate-800">{{ totalBorrows }}</p>
                    </div>
                </div>
            </div>

            <!-- ROW 2: Area Chart (2/3) & Status/Condition Combined (1/3) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- SVG Area Chart -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between relative overflow-hidden">
                    <div class="flex items-center justify-between z-10 relative">
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wide">Tren Peminjaman Bulanan</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Volume transaksi 12 bulan terakhir</p>
                        </div>
                    </div>
                    
                    <div v-if="borrowTrend.length" class="mt-8 mb-4 relative w-full flex-1 min-h-[140px] z-10">
                        <svg class="w-full h-full overflow-visible" :viewBox="`0 0 ${svgViewBoxWidth} ${svgViewBoxHeight}`" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#6366f1" stop-opacity="0.3" />
                                    <stop offset="100%" stop-color="#6366f1" stop-opacity="0.0" />
                                </linearGradient>
                            </defs>
                            <!-- Grid lines -->
                            <line x1="0" :y1="svgViewBoxHeight/2" :x2="svgViewBoxWidth" :y2="svgViewBoxHeight/2" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="5,5"/>
                            <line x1="0" y1="20" :x2="svgViewBoxWidth" y2="20" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="5,5"/>
                            
                            <!-- Area -->
                            <polygon :points="areaPoints" fill="url(#areaGradient)" class="transition-all duration-1000 ease-out"/>
                            <!-- Line -->
                            <polyline :points="trendPoints" fill="none" stroke="#6366f1" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-all duration-1000 ease-out drop-shadow-sm"/>
                            
                            <!-- Points/Nodes -->
                            <circle v-for="(month, idx) in borrowTrend" :key="'p'+idx"
                                    :cx="idx * (svgViewBoxWidth / (borrowTrend.length > 1 ? borrowTrend.length - 1 : 1))"
                                    :cy="svgViewBoxHeight - ((month.total / maxTrend) * (svgViewBoxHeight - 30))"
                                    r="4" fill="#ffffff" stroke="#6366f1" stroke-width="2.5" class="cursor-pointer hover:r-6 transition-all duration-300">
                                <title>{{ month.month }}: {{ month.total }} Trx</title>
                            </circle>
                        </svg>

                        <!-- X-Axis Labels -->
                        <div class="flex justify-between w-full absolute -bottom-6 px-1">
                            <div v-for="month in borrowTrend" :key="month.month" class="text-[10px] font-bold text-slate-400 uppercase">
                                {{ month.month.slice(0, 3) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Combined Progress Bars -->
                <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col gap-6">
                    <!-- Kondisi Aset Compact -->
                    <div>
                        <h2 class="text-xs font-extrabold text-slate-800 uppercase tracking-wide border-b border-slate-100 pb-2 mb-3">Distribusi Kondisi Fisik</h2>
                        <div class="space-y-3">
                            <div v-for="[cond, count] in conditionEntries" :key="cond">
                                <div class="flex justify-between text-[11px] font-bold mb-1">
                                    <span class="text-slate-600">{{ conditionLabels[cond] ?? cond }}</span>
                                    <span class="text-slate-800">{{ count }} <span class="text-slate-400 font-medium">({{ pct(count, totalAssets) }}%)</span></span>
                                </div>
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700" :class="conditionColors[cond]?.bg || 'bg-slate-400'" :style="{ width: pct(count, totalAssets) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Peminjaman Compact -->
                    <div>
                        <h2 class="text-xs font-extrabold text-slate-800 uppercase tracking-wide border-b border-slate-100 pb-2 mb-3">Statistik Transaksi</h2>
                        <div class="space-y-3">
                            <div v-for="[stat, count] in statusEntries" :key="stat">
                                <div class="flex justify-between text-[11px] font-bold mb-1">
                                    <span class="text-slate-600">{{ statusLabels[stat] ?? stat }}</span>
                                    <span class="text-slate-800">{{ count }} <span class="text-slate-400 font-medium">({{ pct(count, totalBorrows) }}%)</span></span>
                                </div>
                                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700" :class="statusColors[stat]?.bg || 'bg-slate-400'" :style="{ width: pct(count, totalBorrows) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ROW 3: Top Assets & Categories -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                
                <!-- Top Assets Compact List -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 h-[220px] flex flex-col">
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wide border-b border-slate-100 pb-3 mb-3">Top Aset Terfavorit</h2>
                    <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 space-y-2">
                        <div v-for="(asset, idx) in topAssets" :key="asset.code" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-black w-5 text-center text-slate-400" :class="{'text-amber-500': idx===0, 'text-slate-600': idx===1, 'text-orange-500': idx===2}">#{{ idx+1 }}</span>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 leading-tight">{{ asset.name }}</p>
                                    <p class="text-[10px] font-mono text-slate-500 mt-0.5">{{ asset.code }}</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-black bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg border border-indigo-100">{{ asset.count }}x Dipinjam</span>
                        </div>
                        <div v-if="!topAssets.length" class="text-center text-xs text-slate-400 py-6">Belum ada transaksi aset.</div>
                    </div>
                </div>

                <!-- Categories Compact Grid -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 h-[220px] flex flex-col">
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wide border-b border-slate-100 pb-3 mb-3">Distribusi Kategori</h2>
                    <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 grid grid-cols-2 gap-2 content-start">
                        <div v-for="cat in assetsByCategory" :key="cat.name" class="flex items-center justify-between p-3 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-colors">
                            <span class="text-xs font-bold text-slate-700 truncate mr-2">{{ cat.name }}</span>
                            <span class="text-[11px] font-black text-slate-800 bg-white px-2 py-0.5 rounded shadow-sm">{{ cat.count }}</span>
                        </div>
                        <div v-if="!assetsByCategory.length" class="col-span-2 text-center text-xs text-slate-400 py-6">Kategori aset kosong.</div>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 10px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
  background-color: #94a3b8;
}
</style>
