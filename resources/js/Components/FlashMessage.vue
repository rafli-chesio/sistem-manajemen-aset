<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();

const flash = computed(() => page.props.flash ?? {});

const visible = ref(false);
const type    = ref('');
const message = ref('');

watch(flash, (val) => {
    if (val.success) { type.value = 'success'; message.value = val.success; visible.value = true; }
    else if (val.error) { type.value = 'error'; message.value = val.error; visible.value = true; }
    else if (val.warning) { type.value = 'warning'; message.value = val.warning; visible.value = true; }
    if (visible.value) setTimeout(() => visible.value = false, 4500);
}, { immediate: true, deep: true });

const colors = {
    success: 'bg-emerald-50 border-emerald-400 text-emerald-800',
    error:   'bg-red-50 border-red-400 text-red-800',
    warning: 'bg-amber-50 border-amber-400 text-amber-800',
};
const icons = {
    success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    error:   'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="transform opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="transform opacity-0 -translate-y-2"
    >
        <div v-if="visible && message"
             class="fixed top-4 right-4 z-[9999] max-w-sm w-full">
            <div class="flex items-start gap-3 px-4 py-3 rounded-xl border shadow-lg"
                 :class="colors[type]">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons[type]"/>
                </svg>
                <p class="text-sm font-medium flex-1">{{ message }}</p>
                <button @click="visible = false" class="opacity-60 hover:opacity-100 transition-opacity">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>
