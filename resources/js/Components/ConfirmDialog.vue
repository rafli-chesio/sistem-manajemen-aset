<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show:          { type: Boolean, default: false },
    title:         { type: String, default: 'Konfirmasi' },
    message:       { type: String, default: 'Apakah Anda yakin?' },
    confirmText:   { type: String, default: 'Ya, Lanjutkan' },
    cancelText:    { type: String, default: 'Batal' },
    confirmClass:  { type: String, default: 'bg-red-600 hover:bg-red-700 text-white' },
    loading:       { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="emit('cancel')"/>

                <!-- Dialog -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div v-if="show"
                         class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 z-10">
                        <slot>
                            <h3 class="text-lg font-bold text-slate-800 mb-2">{{ title }}</h3>
                            <p class="text-slate-600 text-sm mb-6">{{ message }}</p>
                        </slot>
                        <div class="flex gap-3 justify-end">
                            <button @click="emit('cancel')"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                                {{ cancelText }}
                            </button>
                            <button @click="emit('confirm')" :disabled="loading"
                                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors flex items-center gap-2 disabled:opacity-60"
                                    :class="confirmClass">
                                <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
