<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    borrow:     { type: Object, required: true },
    canApprove: { type: Boolean, default: false },
    canReturn:  { type: Boolean, default: false },
});

const approveForm      = useForm({});
const rejectForm       = useForm({ rejection_reason: '' });
const showRejectDialog  = ref(false);
const showApproveDialog = ref(false);

function doApprove() {
    approveForm.post(route('borrows.approve', props.borrow.id), {
        onSuccess: () => { showApproveDialog.value = false; },
        onError:   () => { showApproveDialog.value = false; },
    });
}

function submitReject() {
    rejectForm.post(route('borrows.reject', props.borrow.id), {
        onSuccess: () => { showRejectDialog.value = false; rejectForm.reset(); },
    });
}

const isReturnable = computed(() =>
    ['APPROVED', 'OVERDUE'].includes(props.borrow.status) &&
    props.borrow.items?.some(i => i.asset?.type === 'FIXED')
);

// Format tanggal ISO → bahasa Indonesia
function formatDate(raw) {
    if (!raw) return '—';
    const d = new Date(raw);
    if (isNaN(d)) return raw;
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

function formatDatetime(raw) {
    if (!raw) return '—';
    const d = new Date(raw);
    if (isNaN(d)) return raw;
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head :title="`Peminjaman #${borrow.id}`"/>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('borrows.index')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <h1 class="text-slate-800 font-bold text-lg">Detail Peminjaman #{{ borrow.id }}</h1>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-5">
            <!-- Header card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                    <div>
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Peminjam</p>
                        <p class="font-bold text-slate-800 mt-0.5">{{ borrow.user?.name }}</p>
                        <p class="text-sm text-slate-500">{{ borrow.user?.email }}</p>
                    </div>
                    <StatusBadge :status="borrow.status"/>
                </div>

                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 border-t border-slate-100 pt-4">
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Tgl Pinjam</dt>
                        <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ formatDate(borrow.borrow_date) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Batas Kembali</dt>
                        <dd class="text-sm font-medium text-slate-700 mt-0.5">
                            <span v-if="borrow.items?.every(i => i.asset?.type === 'CONSUMABLE')" class="text-slate-400 italic text-xs">
                                Tidak ada (habis pakai)
                            </span>
                            <span v-else>{{ formatDate(borrow.return_date) }}</span>
                        </dd>
                    </div>
                    <div v-if="borrow.approver">
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Diproses Oleh</dt>
                        <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ borrow.approver.name }}</dd>
                    </div>
                    <div v-if="borrow.notes" class="col-span-full">
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Catatan</dt>
                        <dd class="text-sm text-slate-600 mt-0.5">{{ borrow.notes }}</dd>
                    </div>
                    <div v-if="borrow.rejection_reason" class="col-span-full">
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Alasan Penolakan</dt>
                        <dd class="text-sm text-red-600 mt-0.5">{{ borrow.rejection_reason }}</dd>
                    </div>
                </dl>

                <div v-if="canApprove && borrow.status === 'PENDING'" class="flex gap-3 mt-5 pt-5 border-t border-slate-100">
                    <button @click="showApproveDialog = true"
                            :disabled="approveForm.processing"
                            class="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-colors disabled:opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Setujui
                    </button>
                    <button @click="showRejectDialog = true"
                            class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-xl text-sm font-semibold hover:bg-red-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Tolak
                    </button>
                </div>

                <div v-if="canReturn && isReturnable" class="mt-5 pt-5 border-t border-slate-100">
                    <Link :href="route('returns.create', borrow.id)"
                          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors w-fit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Proses Pengembalian
                    </Link>
                </div>

                <div v-if="canReturn && ['APPROVED','OVERDUE'].includes(borrow.status) && !isReturnable && !borrow.return_record"
                     class="mt-5 pt-5 border-t border-slate-100">
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-sky-50 border border-sky-100">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-sky-700">Barang Habis Pakai</p>
                            <p class="text-xs text-sky-600 mt-0.5">Barang habis pakai tidak perlu diproses pengembaliannya. Peminjaman ini dianggap selesai setelah disetujui.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 font-bold text-slate-700">Barang Dipinjam</div>
                <div class="divide-y divide-slate-100">
                    <div v-for="item in borrow.items" :key="item.id" class="flex items-center gap-4 px-6 py-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0">
                            <img v-if="item.asset?.images?.length" :src="'/storage/' + item.asset.images[0].path" class="w-full h-full object-cover"/>
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">{{ item.asset?.name }}</p>
                            <p class="text-xs font-mono text-slate-400">{{ item.asset?.asset_code }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ item.asset?.type === 'FIXED' ? '📦 Aset Tetap' : '🧴 Habis Pakai' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-700">× {{ item.quantity }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="borrow.return_record" class="bg-emerald-50 rounded-2xl border border-emerald-200 p-6">
                <h2 class="font-bold text-emerald-700 mb-4">Catatan Pengembalian</h2>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Dikembalikan</dt>
                        <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ formatDatetime(borrow.return_record.returned_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Diproses Oleh</dt>
                        <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ borrow.return_record.processor?.name ?? '—' }}</dd>
                    </div>
                    <div v-if="borrow.return_record.notes" class="col-span-full">
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Catatan Umum</dt>
                        <dd class="text-sm text-slate-600 mt-0.5">{{ borrow.return_record.notes }}</dd>
                    </div>
                </dl>

                <!-- Per-item conditions dari return_items -->
                <div v-if="borrow.return_record.return_items?.length" class="mt-4">
                    <p class="text-xs text-slate-400 font-semibold uppercase mb-2">Kondisi Per Barang</p>
                    <div class="space-y-2">
                        <div v-for="ri in borrow.return_record.return_items" :key="ri.id"
                             class="flex items-center gap-3 p-3 bg-white rounded-xl border border-emerald-100">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ ri.borrow_item?.asset?.name }}</p>
                                <p class="text-xs font-mono text-slate-400">{{ ri.borrow_item?.asset?.asset_code }}</p>
                                <p v-if="ri.notes" class="text-xs text-slate-500 mt-0.5 italic">{{ ri.notes }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <StatusBadge :status="ri.borrow_item?.condition_before" />
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <StatusBadge :status="ri.condition_after" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Foto bukti -->
                <div v-if="borrow.return_record.images?.length" class="flex flex-wrap gap-2 mt-4">
                    <img v-for="img in borrow.return_record.images" :key="img.id"
                         :src="'/storage/' + img.path" class="h-20 w-20 rounded-xl object-cover border border-emerald-100"/>
                </div>
            </div>
        </div>

        <!-- ── Modal: Konfirmasi Setujui ─────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showApproveDialog" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showApproveDialog = false"/>
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">Setujui Peminjaman?</h3>
                        </div>
                        <p class="text-sm text-slate-600 mb-5">
                            Permintaan dari <strong>{{ borrow.user?.name }}</strong> akan disetujui dan status berubah menjadi
                            <strong class="text-emerald-700">Disetujui</strong>.
                        </p>
                        <div class="flex gap-3 justify-end">
                            <button type="button"
                                    @click="showApproveDialog = false"
                                    class="px-4 py-2 text-sm rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                                Batal
                            </button>
                            <button type="button"
                                    @click="doApprove"
                                    :disabled="approveForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 text-sm rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition-colors disabled:opacity-60 font-semibold">
                                <svg v-if="approveForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ approveForm.processing ? 'Memproses...' : 'Ya, Setujui' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Modal: Tolak Peminjaman ────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showRejectDialog" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showRejectDialog = false"/>
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 z-10">
                        <h3 class="text-lg font-bold text-slate-800 mb-4">Tolak Permintaan</h3>
                        <form @submit.prevent="submitReject" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Alasan <span class="text-red-500">*</span></label>
                                <textarea v-model="rejectForm.rejection_reason" rows="3"
                                          class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-red-300 outline-none resize-none"
                                          placeholder="Tuliskan alasan penolakan..."/>
                                <p v-if="rejectForm.errors.rejection_reason" class="text-red-500 text-xs mt-1">{{ rejectForm.errors.rejection_reason }}</p>
                            </div>
                            <div class="flex gap-3 justify-end">
                                <button type="button" @click="showRejectDialog = false"
                                        class="px-4 py-2 text-sm rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">Batal</button>
                                <button type="submit" :disabled="rejectForm.processing"
                                        class="px-4 py-2 text-sm rounded-xl bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-60 font-semibold">
                                    {{ rejectForm.processing ? 'Memproses...' : 'Tolak' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
