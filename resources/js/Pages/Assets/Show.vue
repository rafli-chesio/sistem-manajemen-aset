<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    asset: { type: Object, required: true },
});

const page     = usePage();
const userRole = page.props.auth.user?.role;
const canEdit  = userRole === 'ADMIN' || userRole === 'KAJUR';
const canAdmin = userRole === 'ADMIN';

const showLostModal   = ref(false);
const showDeleteModal = ref(false);
const lostLoading     = ref(false);
const lightboxImage   = ref(null);
const qrLoading       = ref(false);

function markLost() {
    lostLoading.value = true;
    router.post(route('assets.mark-lost', props.asset.id), {}, {
        onFinish: () => { lostLoading.value = false; showLostModal.value = false; },
    });
}

function doDelete() {
    router.delete(route('assets.destroy', props.asset.id), {
        onSuccess: () => router.visit(route('assets.index')),
    });
}

function assignQr() {
    qrLoading.value = true;
    router.post(route('assets.qr.assign', props.asset.id), {}, {
        onFinish: () => { qrLoading.value = false; },
    });
}

function printQr() {
    const w = window.open(route('assets.qr', props.asset.id), '_blank');
    w?.print();
}

const conditionInfo = computed(() => {
    const map = {
        BAIK:         { label: 'Baik',         cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        RUSAK_RINGAN: { label: 'Rusak Ringan',  cls: 'bg-amber-50 text-amber-700 border-amber-200' },
        RUSAK_BERAT:  { label: 'Rusak Berat',   cls: 'bg-red-50 text-red-700 border-red-200' },
    };
    return map[props.asset.condition] ?? { label: props.asset.condition, cls: 'bg-slate-50 text-slate-500 border-slate-200' };
});

const statusInfo = computed(() => {
    const map = {
        AVAILABLE:   { label: 'Tersedia',    cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        BORROWED:    { label: 'Dipinjam',    cls: 'bg-amber-50 text-amber-700 border-amber-200' },
        MAINTENANCE: { label: 'Maintenance', cls: 'bg-blue-50 text-blue-700 border-blue-200' },
        LOST:        { label: 'Hilang',      cls: 'bg-red-50 text-red-700 border-red-200' },
        ARCHIVED:    { label: 'Diarsipkan',  cls: 'bg-slate-50 text-slate-500 border-slate-200' },
    };
    return map[props.asset.status] ?? { label: props.asset.status, cls: 'bg-slate-50 text-slate-500 border-slate-200' };
});
</script>

<template>
    <Head :title="asset.name"/>
    <AuthenticatedLayout>

        <div class="max-w-4xl mx-auto space-y-5">

            <!-- Breadcrumb + Back -->
            <div class="flex items-center gap-2 text-sm">
                <Link :href="route('assets.index')" class="text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Aset
                </Link>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-medium truncate max-w-xs">{{ asset.name }}</span>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

                <!-- Image Gallery -->
                <div v-if="asset.images?.length" class="flex gap-2 p-4 overflow-x-auto bg-slate-50 border-b border-slate-100">
                    <img v-for="img in asset.images" :key="img.id"
                         :src="'/storage/' + img.path" :alt="asset.name"
                         @click="lightboxImage = '/storage/' + img.path"
                         class="h-36 w-auto rounded-xl object-cover cursor-pointer hover:scale-105 transition-transform shadow-sm flex-shrink-0"/>
                </div>

                <div class="p-6">
                    <!-- Title + Badges -->
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                        <div>
                            <h1 class="text-xl font-bold text-slate-900">{{ asset.name }}</h1>
                            <p v-if="asset.brand" class="text-slate-400 text-sm mt-0.5">
                                {{ asset.brand }}{{ asset.year ? ` · ${asset.year}` : '' }}
                            </p>
                            <p v-if="asset.asset_code" class="font-mono text-xs text-slate-400 mt-1 bg-slate-50 inline-block px-2 py-0.5 rounded-md">
                                {{ asset.asset_code }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <!-- Type -->
                            <span class="px-2.5 py-0.5 text-xs font-bold rounded-full border bg-indigo-50 text-indigo-700 border-indigo-200">
                                {{ asset.type === 'FIXED' ? 'Aset Tetap' : 'Habis Pakai' }}
                            </span>
                            <!-- Condition -->
                            <span class="px-2.5 py-0.5 text-xs font-bold rounded-full border" :class="conditionInfo.cls">
                                {{ conditionInfo.label }}
                            </span>
                            <!-- Status (only FIXED) -->
                            <span v-if="asset.type === 'FIXED'" class="px-2.5 py-0.5 text-xs font-bold rounded-full border" :class="statusInfo.cls">
                                {{ statusInfo.label }}
                            </span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 mb-5">
                        <div>
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Kategori</dt>
                            <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ asset.category?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Lokasi</dt>
                            <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ asset.location?.name ?? '—' }}</dd>
                        </div>
                        <div v-if="asset.department">
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Jurusan</dt>
                            <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ asset.department }}</dd>
                        </div>
                        <div v-if="asset.type === 'CONSUMABLE'">
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Stok Tersisa</dt>
                            <dd class="text-2xl font-bold text-slate-800 mt-0.5">{{ asset.stock }}
                                <span class="text-sm font-normal text-slate-400">unit</span>
                            </dd>
                        </div>
                        <div v-if="asset.qr_code">
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">QR Code</dt>
                            <dd class="text-sm font-mono font-medium text-slate-700 mt-0.5">{{ asset.qr_code }}</dd>
                        </div>
                        <div v-if="asset.description" class="col-span-full">
                            <dt class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Keterangan</dt>
                            <dd class="text-sm text-slate-600 mt-0.5 leading-relaxed">{{ asset.description }}</dd>
                        </div>
                    </dl>

                    <!-- Action Buttons -->
                    <div v-if="canEdit" class="flex flex-wrap gap-2 pt-5 border-t border-slate-100">
                        <!-- Edit -->
                        <Link :href="route('assets.edit', asset.id)"
                              class="flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </Link>

                        <!-- QR Code Actions (Admin only) -->
                        <template v-if="canAdmin">
                            <a :href="route('assets.qr', asset.id)" target="_blank"
                               class="flex items-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-sm font-semibold transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                                Lihat QR
                            </a>

                            <button v-if="!asset.qr_code" @click="assignQr" :disabled="qrLoading"
                                    class="flex items-center gap-2 px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 disabled:opacity-50 rounded-xl text-sm font-semibold transition-colors">
                                <svg class="w-4 h-4" :class="{'animate-spin': qrLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Generate QR
                            </button>
                        </template>

                        <!-- Mark Lost (FIXED only, not already LOST) -->
                        <button v-if="asset.type === 'FIXED' && asset.status !== 'LOST'"
                                @click="showLostModal = true"
                                class="flex items-center gap-2 px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl text-sm font-semibold transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Tandai Hilang
                        </button>

                        <!-- Delete (Admin only) -->
                        <button v-if="canAdmin" @click="showDeleteModal = true"
                                class="flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-sm font-semibold transition-colors ml-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus
                        </button>
                    </div>
                </div>
            </div>

            <!-- Borrow History (FIXED only) -->
            <div v-if="asset.type === 'FIXED' && asset.borrow_items?.length" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-800">Riwayat Peminjaman</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-50">
                                <th class="px-5 py-3 text-[10px] uppercase text-slate-400 font-semibold tracking-wide text-left">Peminjam</th>
                                <th class="px-4 py-3 text-[10px] uppercase text-slate-400 font-semibold tracking-wide text-left">Tanggal Pinjam</th>
                                <th class="px-4 py-3 text-[10px] uppercase text-slate-400 font-semibold tracking-wide text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="item in asset.borrow_items" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3 font-medium text-slate-700">{{ item.request?.user?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ item.request?.borrow_date ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                          :class="{
                                              'bg-emerald-50 text-emerald-700': item.request?.status === 'RETURNED',
                                              'bg-amber-50 text-amber-700': item.request?.status === 'APPROVED',
                                              'bg-slate-50 text-slate-500': !['RETURNED','APPROVED'].includes(item.request?.status),
                                          }">
                                        {{ item.request?.status ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div v-if="lightboxImage" @click="lightboxImage = null"
                 class="fixed inset-0 bg-black/80 z-[200] flex items-center justify-center p-4 cursor-pointer backdrop-blur-sm">
                <img :src="lightboxImage" class="max-w-full max-h-full rounded-xl shadow-2xl object-contain"/>
            </div>
        </Teleport>

        <!-- Mark Lost Modal -->
        <ConfirmModal
            :show="showLostModal"
            type="warning"
            title="Tandai Aset Hilang"
            :message="`Aset '${asset.name}' akan ditandai sebagai HILANG. Status tidak bisa diubah kembali ke AVAILABLE secara otomatis.`"
            confirm-label="Ya, Tandai Hilang"
            @confirm="markLost"
            @cancel="showLostModal = false"
        />

        <!-- Delete Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            type="danger"
            title="Hapus Aset"
            :message="`Aset '${asset.name}' akan dihapus secara permanen. Semua data terkait (riwayat pinjam, foto) juga akan dihapus.`"
            confirm-label="Ya, Hapus Permanen"
            @confirm="doDelete"
            @cancel="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
