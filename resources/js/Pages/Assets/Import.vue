<template>
  <AuthenticatedLayout title="Import Aset dari Excel">
    <div class="max-w-2xl mx-auto py-8 px-4">

      <!-- Header -->
      <div class="flex items-center gap-3 mb-6">
        <Link :href="route('assets.index')" class="p-2 rounded-xl hover:bg-slate-100 text-slate-500 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </Link>
        <div>
          <h1 class="text-lg font-bold text-slate-900">Import Aset dari Excel</h1>
          <p class="text-sm text-slate-500">Upload file Excel untuk menambahkan banyak aset sekaligus</p>
        </div>
      </div>

      <!-- Download Template Card -->
      <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5 mb-6 flex items-start gap-4">
        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <div class="flex-1">
          <h3 class="text-sm font-semibold text-indigo-900 mb-0.5">Download Template Excel</h3>
          <p class="text-xs text-indigo-700 mb-3">Gunakan template ini agar format data sesuai dengan sistem. Jangan ubah nama kolom.</p>
          <a :href="route('assets.import.template')"
             class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-700 hover:text-indigo-900 underline underline-offset-2">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            template_import_aset.xlsx
          </a>
        </div>
      </div>

      <!-- Upload Form -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Upload File Excel</h2>

        <form @submit.prevent="submit">
          <!-- Drop Zone -->
          <div @dragover.prevent @drop.prevent="handleDrop"
               @click="$refs.fileInput.click()"
               class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors"
               :class="file ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50'">

            <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="handleFileChange">

            <div v-if="!file">
              <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <p class="text-sm font-medium text-slate-600">Klik atau seret file ke sini</p>
              <p class="text-xs text-slate-400 mt-1">Format: .xlsx, .xls, atau .csv — Maks 5MB</p>
            </div>

            <div v-else class="flex items-center justify-center gap-3">
              <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              <div class="text-left">
                <p class="text-sm font-semibold text-slate-800">{{ file.name }}</p>
                <p class="text-xs text-slate-400">{{ (file.size / 1024).toFixed(1) }} KB</p>
              </div>
              <button type="button" @click.stop="file = null" class="ml-2 text-slate-400 hover:text-red-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>

          <p v-if="form.errors.file" class="mt-2 text-xs text-red-500">{{ form.errors.file }}</p>

          <!-- Info boxes -->
          <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div v-for="info in infoItems" :key="info.label"
                 class="flex items-start gap-2 p-3 bg-slate-50 rounded-xl">
              <span class="text-base">{{ info.icon }}</span>
              <div>
                <p class="text-xs font-semibold text-slate-700">{{ info.label }}</p>
                <p class="text-xs text-slate-500">{{ info.value }}</p>
              </div>
            </div>
          </div>

          <!-- Submit -->
          <div class="mt-6 flex gap-3">
            <Link :href="route('assets.index')"
                  class="flex-1 py-2.5 text-center text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
              Batal
            </Link>
            <button type="submit" :disabled="!file || form.processing"
                    class="flex-1 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-colors flex items-center justify-center gap-2">
              <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ form.processing ? 'Mengimpor...' : 'Import Sekarang' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const file     = ref(null)
const form     = useForm({ file: null })
const fileInput = ref(null)

const infoItems = [
  { icon: '📋', label: 'Kolom wajib',  value: 'nama_barang' },
  { icon: '🔤', label: 'Tipe',         value: 'FIXED atau CONSUMABLE' },
  { icon: '✅', label: 'Kondisi',      value: 'BAIK, RUSAK RINGAN, RUSAK BERAT' },
]

function handleFileChange(e) {
  file.value = e.target.files[0] ?? null
}

function handleDrop(e) {
  file.value = e.dataTransfer.files[0] ?? null
}

function submit() {
  if (!file.value) return
  form.file = file.value
  form.post(route('assets.import.store'), {
    forceFormData: true,
    onSuccess: () => { file.value = null },
  })
}
</script>
