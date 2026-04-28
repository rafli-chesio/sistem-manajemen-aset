<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    notifications: { type: Object, required: true },
});

const typeIcons = {
    'borrow.approved': { icon: 'check', color: 'text-emerald-500 bg-emerald-50' },
    'borrow.rejected': { icon: 'x',     color: 'text-red-500 bg-red-50' },
    'borrow.overdue':  { icon: 'clock', color: 'text-orange-500 bg-orange-50' },
    'borrow.expired':  { icon: 'clock', color: 'text-slate-500 bg-slate-50' },
};

function getIconConfig(type) {
    return typeIcons[type] ?? { icon: 'bell', color: 'text-indigo-500 bg-indigo-50' };
}

function markAllRead() {
    router.post(route('notifications.read'), {}, { preserveScroll: true });
}

function markRead(id, url) {
    router.post(route('notifications.read'), { id }, {
        preserveScroll: true,
        onSuccess: () => {
            if (url) router.visit(url);
        },
    });
}
</script>

<template>
    <Head title="Notifikasi"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Notifikasi</h1>
        </template>

        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-4">
                <p class="text-sm text-slate-500">
                    {{ notifications.total ?? 0 }} notifikasi
                </p>
                <button @click="markAllRead"
                        class="text-sm text-indigo-600 hover:text-indigo-700 font-medium transition-colors">
                    Tandai semua dibaca
                </button>
            </div>

            <div class="space-y-2">
                <div v-if="!notifications.data?.length"
                     class="bg-white rounded-2xl border border-slate-100 shadow-sm p-10 text-center text-slate-400">
                    <svg class="w-10 h-10 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Tidak ada notifikasi.
                </div>

                <div v-for="notif in notifications.data" :key="notif.id"
                     @click="markRead(notif.id, notif.data?.url)"
                     class="flex items-start gap-4 p-4 rounded-2xl border transition-all cursor-pointer"
                     :class="notif.read_at
                         ? 'bg-white border-slate-100 shadow-sm hover:shadow-md'
                         : 'bg-indigo-50 border-indigo-200 shadow-sm hover:shadow-md'">

                    <!-- Icon -->
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         :class="getIconConfig(notif.data?.type).color">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="getIconConfig(notif.data?.type).icon === 'check'"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            <path v-else-if="getIconConfig(notif.data?.type).icon === 'x'"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            <path v-else-if="getIconConfig(notif.data?.type).icon === 'clock'"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            <path v-else
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 leading-snug">
                            {{ notif.data?.message }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">{{ notif.created_at?.slice(0,16).replace('T', ' ') }}</p>
                    </div>

                    <div v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0 mt-2"/>
                </div>
            </div>

            <Pagination :links="notifications.links" :meta="notifications.meta ?? notifications"/>
        </div>
    </AuthenticatedLayout>
</template>

