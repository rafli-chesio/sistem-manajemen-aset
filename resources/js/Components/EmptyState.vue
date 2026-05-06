<template>
  <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
         :class="iconBgClass">
      <svg class="w-8 h-8" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path v-if="icon === 'box'" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        <path v-else-if="icon === 'search'" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        <path v-else-if="icon === 'clipboard'" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        <path v-else-if="icon === 'users'" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
    </div>

    <h3 class="text-base font-semibold text-slate-800 mb-1">{{ title }}</h3>
    <p class="text-sm text-slate-500 max-w-xs mb-6">{{ description }}</p>

    <Link v-if="actionRoute && actionLabel"
          :href="actionRoute"
          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      {{ actionLabel }}
    </Link>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  icon:        { type: String, default: 'box' },     // box | search | clipboard | users
  title:       { type: String, default: 'Tidak ada data' },
  description: { type: String, default: 'Belum ada data yang tersedia.' },
  actionLabel: { type: String, default: null },
  actionRoute: { type: String, default: null },
  color:       { type: String, default: 'indigo' },  // indigo | slate | amber | red
})

const colorMap = {
  indigo: { bg: 'bg-indigo-50', icon: 'text-indigo-400' },
  slate:  { bg: 'bg-slate-100', icon: 'text-slate-400' },
  amber:  { bg: 'bg-amber-50',  icon: 'text-amber-400' },
  red:    { bg: 'bg-red-50',    icon: 'text-red-400' },
}

const iconBgClass = computed(() => colorMap[props.color]?.bg ?? colorMap.indigo.bg)
const iconClass   = computed(() => colorMap[props.color]?.icon ?? colorMap.indigo.icon)
</script>
