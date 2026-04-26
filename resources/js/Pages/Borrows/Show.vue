<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    borrow:     { type: Object, required: true },
    canApprove: { type: Boolean, default: false },
    canReturn:  { type: Boolean, default: false },
});

const showRejectDialog = ref(false);
const rejectForm = useForm({ rejection_reason: '' });

function approve() {
    if (!confirm('Setujui permintaan peminjaman ini?')) return;
    useForm({}).post(route('borrows.approve', props.borrow.id));
}

function submitReject() {
    rejectForm.post(route('borrows.reject', props.borrow.id), {
        onSuccess: () => { showRejectDialog.value = false; rejectForm.reset(); },
    });
}

const isReturnable = computed(() =>
    ['APPROVED', 'OVERDUE'].includes(props.borrow.status) &&
    props.borrow.items?.some(i => i.asset?.type === 'UNIQUE')
);
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
                        <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ borrow.borrow_date }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Tgl Kembali</dt>
                        <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ borrow.return_date }}</dd>
                    </div>
                    <div v-if="borrow.approver">
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Diproses Oleh</dt>
                        <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ borrow.approver.name }}</dd>
                    </div>
                    <div v-if="borrow.rejection_reason" class="col-span-full">
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Alasan Penolakan</dt>
                        <dd class="text-sm text-red-600 mt-0.5">{{ borrow.rejection_reason }}</dd>
                    </div>
                </dl>

                <!-- Approve / Reject -->
                <div v-if="canApprove && borrow.status === 'PENDING'" class="flex gap-3 mt-5 pt-5 border-t border-slate-100">
                    <button @click="approve"
                            class="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Setujui
                    </button>
                    <button @click="showRejectDialog = true"
                            class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-xl text-sm font-semibold hover:bg-red-100 transition-colors">
                        Tolak
                    </button>
                </div>

                <!-- Return button -->
                <div v-if="canReturn && isReturnable" class="mt-5 pt-5 border-t border-slate-100">
                    <Link :href="route('returns.create', borrow.id)"
                          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors w-fit">
                        Proses Pengembalian
                    </Link>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 font-bold text-slate-700">Barang Dipinjam</div>
                <div class="divide-y divide-slate-100">
                    <div v-for="item in borrow.items" :key="item.id" class="flex items-center gap-4 px-6 py-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0">
                            <img v-if="item.asset?.images?.length" :src="'/storage/' + item.asset.images[0].path" class="w-full h-full object-cover"/>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">{{ item.asset?.name }}</p>
                            <p class="text-xs font-mono text-slate-400">{{ item.asset?.asset_code }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-700">× {{ item.quantity }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Return record -->
            <div v-if="borrow.return_record" class="bg-emerald-50 rounded-2xl border border-emerald-200 p-6">
                <h2 class="font-bold text-emerald-700 mb-4">Catatan Pengembalian</h2>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Dikembalikan</dt>
                        <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ borrow.return_record.returned_at }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold uppercase">Kondisi Akhir</dt>
                        <dd class="mt-0.5"><StatusBadge :status="borrow.return_record.condition_after"/></dd>
                    </div>
                </dl>
                <div v-if="borrow.return_record.images?.length" class="flex flex-wrap gap-2 mt-4">
                    <img v-for="img in borrow.return_record.images" :key="img.id"
                         :src="'/storage/' + img.path" class="h-20 w-20 rounded-xl object-cover border border-emerald-100"/>
                </div>
            </div>
        </div>

        <!-- Reject dialog -->
        <Teleport to="body">
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
                                    class="px-4 py-2 text-sm rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">Batal</button>
                            <button type="submit" :disabled="rejectForm.processing"
                                    class="px-4 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-60">
                                Tolak
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
