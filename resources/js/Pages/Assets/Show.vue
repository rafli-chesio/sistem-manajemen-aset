<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    asset: { type: Object, required: true },
});

const page    = usePage();
const canEdit = page.props.auth.permissions.includes('asset.edit');

const showLostDialog   = ref(false);
const lostLoading      = ref(false);
const lightboxImage    = ref(null);

function markLost() {
    lostLoading.value = true;
    router.post(route('assets.mark-lost', props.asset.id), {}, {
        onFinish: () => { lostLoading.value = false; showLostDialog.value = false; },
    });
}
</script>

<template>
    <Head :title="asset.name"/>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('assets.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <h1 class="text-slate-800 font-bold text-lg">{{ asset.name }}</h1>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Main Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <!-- Image gallery -->
                <div v-if="asset.images?.length" class="flex gap-2 p-4 overflow-x-auto bg-slate-50 border-b border-slate-100">
                    <img v-for="img in asset.images" :key="img.id"
                         :src="'/storage/' + img.path" :alt="asset.name"
                         @click="lightboxImage = '/storage/' + img.path"
                         class="h-40 w-auto rounded-xl object-cover cursor-pointer hover:scale-105 transition-transform shadow-sm flex-shrink-0"/>
                </div>

                <!-- Details -->
                <div class="p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">{{ asset.name }}</h2>
                            <p v-if="asset.brand" class="text-slate-500 text-sm mt-0.5">{{ asset.brand }} {{ asset.year ? `(${asset.year})` : '' }}</p>
                            <p v-if="asset.asset_code" class="font-mono text-xs text-slate-400 mt-1">{{ asset.asset_code }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <StatusBadge :status="asset.type"/>
                            <StatusBadge :status="asset.condition"/>
                            <StatusBadge v-if="asset.type === 'UNIQUE'" :status="asset.status"/>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Kategori</dt>
                            <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ asset.category?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Lokasi</dt>
                            <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ asset.location?.name ?? '—' }}</dd>
                        </div>
                        <div v-if="asset.type === 'CONSUMABLE'">
                            <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Stok Tersisa</dt>
                            <dd class="text-sm font-bold text-slate-700 mt-0.5">{{ asset.stock }} unit</dd>
                        </div>
                        <div v-if="asset.description" class="col-span-full">
                            <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Keterangan</dt>
                            <dd class="text-sm text-slate-600 mt-0.5">{{ asset.description }}</dd>
                        </div>
                    </dl>

                    <!-- Actions -->
                    <div v-if="canEdit" class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-slate-100">
                        <Link :href="route('assets.edit', asset.id)"
                              class="flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 border border-amber-200 rounded-xl text-sm font-semibold hover:bg-amber-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Aset
                        </Link>
                        <button v-if="asset.type === 'UNIQUE' && asset.status !== 'LOST'"
                                @click="showLostDialog = true"
                                class="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-xl text-sm font-semibold hover:bg-red-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Tandai Hilang
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Lightbox -->
        <Teleport to="body">
            <div v-if="lightboxImage" @click="lightboxImage = null"
                 class="fixed inset-0 bg-black/80 z-[200] flex items-center justify-center p-4 cursor-pointer">
                <img :src="lightboxImage" class="max-w-full max-h-full rounded-xl shadow-2xl object-contain"/>
            </div>
        </Teleport>

        <ConfirmDialog
            :show="showLostDialog"
            title="Tandai Aset Hilang"
            :message="`Apakah Anda yakin aset &quot;${asset.name}&quot; telah hilang? Status akan diperbarui menjadi LOST.`"
            confirm-text="Ya, Tandai Hilang"
            :loading="lostLoading"
            @confirm="markLost"
            @cancel="showLostDialog = false"
        />
    </AuthenticatedLayout>
</template>
