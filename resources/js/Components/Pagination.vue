<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: { type: Array, required: true }, // from Laravel paginator
    meta:  { type: Object, default: () => ({}) },
});
</script>

<template>
    <div v-if="links && links.length > 3"
         class="flex flex-wrap items-center justify-between gap-2 mt-4">
        <p class="text-sm text-slate-500">
            Menampilkan
            <span class="font-medium text-slate-700">{{ meta?.from ?? '?' }}</span>
            –
            <span class="font-medium text-slate-700">{{ meta?.to ?? '?' }}</span>
            dari
            <span class="font-medium text-slate-700">{{ meta?.total ?? '?' }}</span>
            data
        </p>
        <div class="flex flex-wrap gap-1">
            <template v-for="link in links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="px-3 py-1.5 text-sm rounded-lg border transition-colors"
                    :class="link.active
                        ? 'bg-indigo-600 text-white border-indigo-600 font-semibold'
                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="px-3 py-1.5 text-sm rounded-lg border bg-slate-50 text-slate-400 border-slate-200 cursor-default"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
