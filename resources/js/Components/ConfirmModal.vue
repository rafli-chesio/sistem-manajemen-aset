<template>
  <!-- Backdrop -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           @click.self="$emit('cancel')">

        <!-- Dimmer -->
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

        <!-- Modal -->
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
        >
          <div v-if="show"
               class="relative bg-white rounded-2xl shadow-2xl shadow-slate-200 w-full max-w-sm p-6 z-10">

            <!-- Icon -->
            <div class="flex items-center justify-center w-12 h-12 rounded-xl mb-4 mx-auto"
                 :class="iconBgClass">
              <svg class="w-6 h-6" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="type === 'danger'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                <path v-else-if="type === 'warning'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>

            <!-- Content -->
            <h3 class="text-base font-bold text-slate-900 text-center mb-1">{{ title }}</h3>
            <p class="text-sm text-slate-500 text-center mb-6 leading-relaxed">{{ message }}</p>

            <!-- Actions -->
            <div class="flex gap-3">
              <button type="button"
                      @click="$emit('cancel')"
                      class="flex-1 py-2.5 px-4 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                {{ cancelLabel }}
              </button>
              <button type="button"
                      @click="$emit('confirm')"
                      :class="confirmBtnClass"
                      class="flex-1 py-2.5 px-4 text-sm font-medium text-white rounded-xl transition-colors">
                {{ confirmLabel }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show:         { type: Boolean, default: false },
  type:         { type: String,  default: 'danger' }, // danger | warning | info
  title:        { type: String,  default: 'Konfirmasi' },
  message:      { type: String,  default: 'Apakah Anda yakin?' },
  confirmLabel: { type: String,  default: 'Ya, Lanjutkan' },
  cancelLabel:  { type: String,  default: 'Batal' },
})

defineEmits(['confirm', 'cancel'])

const styleMap = {
  danger:  { bg: 'bg-red-50',    icon: 'text-red-500',    btn: 'bg-red-600 hover:bg-red-700' },
  warning: { bg: 'bg-amber-50',  icon: 'text-amber-500',  btn: 'bg-amber-600 hover:bg-amber-700' },
  info:    { bg: 'bg-indigo-50', icon: 'text-indigo-500', btn: 'bg-indigo-600 hover:bg-indigo-700' },
}

const iconBgClass     = computed(() => styleMap[props.type]?.bg  ?? styleMap.danger.bg)
const iconClass       = computed(() => styleMap[props.type]?.icon ?? styleMap.danger.icon)
const confirmBtnClass = computed(() => styleMap[props.type]?.btn  ?? styleMap.danger.btn)
</script>
