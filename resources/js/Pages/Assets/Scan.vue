<template>
  <AuthenticatedLayout title="Scan QR Code Aset">
    <div class="max-w-lg mx-auto py-10 px-4">

      <!-- Header -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
          <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
          </svg>
        </div>
        <h1 class="text-xl font-bold text-slate-900">Scan QR Code Aset</h1>
        <p class="text-sm text-slate-500 mt-1">Arahkan kamera ke QR Code pada label barang untuk melihat detailnya.</p>
      </div>

      <!-- Scanner Area -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div v-if="!scannerReady" class="p-6 text-center">
          <button @click="startScanner"
                  class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Aktifkan Kamera
          </button>
          <p class="text-xs text-slate-400 mt-3">Izinkan akses kamera saat diminta browser</p>
        </div>

        <!-- QR Reader container -->
        <div id="qr-reader" class="w-full" :class="{ hidden: !scannerReady }"></div>

        <!-- Result -->
        <div v-if="result" class="p-4 border-t border-slate-100 bg-emerald-50">
          <p class="text-xs font-semibold text-emerald-700 mb-1">✓ QR Berhasil Terbaca</p>
          <p class="text-sm text-slate-700 truncate">{{ result }}</p>
          <a :href="result" class="mt-2 inline-flex items-center text-xs text-indigo-600 font-medium hover:underline">
            Buka Halaman Aset →
          </a>
        </div>

        <!-- Error -->
        <div v-if="error" class="p-4 border-t border-slate-100 bg-red-50">
          <p class="text-xs font-semibold text-red-600 mb-1">Kamera tidak dapat diakses</p>
          <p class="text-xs text-slate-500">{{ error }}</p>
        </div>
      </div>

      <!-- Manual Search Fallback -->
      <div class="mt-6 bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Atau cari manual</p>
        <form @submit.prevent="searchManual" class="flex gap-2">
          <input v-model="manualCode"
                 type="text"
                 placeholder="Masukkan kode aset (contoh: PRY-001)"
                 class="flex-1 px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-300 outline-none"/>
          <button type="submit"
                  class="px-4 py-2.5 bg-slate-800 text-white text-sm font-medium rounded-xl hover:bg-slate-700 transition-colors">
            Cari
          </button>
        </form>
      </div>

      <!-- Back Link -->
      <div class="mt-4 text-center">
        <Link :href="route('assets.index')" class="text-sm text-slate-500 hover:text-slate-700">
          ← Kembali ke Daftar Aset
        </Link>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const scannerReady = ref(false)
const result       = ref(null)
const error        = ref(null)
const manualCode   = ref('')
let html5QrCode    = null

async function startScanner() {
  try {
    const { Html5QrcodeScanner } = await import('html5-qrcode')
    html5QrCode = new Html5QrcodeScanner('qr-reader', {
      fps: 10,
      qrbox: { width: 250, height: 250 },
      rememberLastUsedCamera: true,
    })

    html5QrCode.render(
      (decodedText) => {
        result.value = decodedText
        html5QrCode.clear()
        scannerReady.value = false
        // Auto-redirect if it looks like our asset URL
        if (decodedText.includes('/assets/')) {
          setTimeout(() => { window.location.href = decodedText }, 1000)
        }
      },
      (errorMessage) => {
        // Scanning errors are normal, ignore them
      }
    )
    scannerReady.value = true
  } catch (e) {
    error.value = 'Gagal memuat scanner. Pastikan browser mendukung akses kamera.'
  }
}

function searchManual() {
  if (!manualCode.value.trim()) return
  router.get(route('assets.index'), { search: manualCode.value.trim() })
}

onUnmounted(() => {
  if (html5QrCode) html5QrCode.clear().catch(() => {})
})
</script>
