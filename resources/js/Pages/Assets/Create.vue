<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import InlineCreator from '@/Components/InlineCreator.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    categories:  { type: Array, default: () => [] },
    locations:   { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
});

// Local reactive copies so we can push new items without page reload
const localCategories = ref([...props.categories]);
const localLocations  = ref([...props.locations]);

// Check if current user can create categories/locations
const page = usePage();
const canManage = page.props.auth.roles.includes('super_admin') ||
                  page.props.auth.roles.includes('kajur');

const form = useForm({
    name:        '',
    brand:       '',
    year:        new Date().getFullYear(),
    condition:   'BAIK',
    category_id: '',
    location_id: '',
    department:  '',
    type:        'FIXED',
    status:      'AVAILABLE',   // default; untuk CONSUMABLE selalu AVAILABLE
    stock:       null,
    description: '',
    asset_code:  '',
    images:      [],
});

function onCategoryCreated(item) {
    localCategories.value.push(item);
}

function onLocationCreated(item) {
    localLocations.value.push(item);
}

function submit() {
    form.post(route('assets.store'), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Tambah Aset"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Tambah Aset Baru</h1>
        </template>

        <div class="max-w-7xl mx-auto">
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                <!-- Col 1: Basic Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col h-full">
                    <h2 class="text-sm font-extrabold text-slate-700 uppercase tracking-wide border-b border-slate-100 pb-3 mb-5">Informasi Dasar</h2>
                    <div class="space-y-4">
                        <!-- Nama -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Nama Aset <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none" placeholder="cth. Laptop ASUS"/>
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        
                        <!-- Merek & Tahun -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Merek</label>
                                <input v-model="form.brand" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none" placeholder="cth. ASUS"/>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Tahun</label>
                                <input v-model="form.year" type="number" min="1990" :max="new Date().getFullYear()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                            </div>
                        </div>

                        <!-- Asset Code -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Kode Aset</label>
                            <input v-model="form.asset_code" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none font-mono text-slate-600" placeholder="cth. UNQ-2024-00001"/>
                            <p v-if="form.errors.asset_code" class="text-red-500 text-xs mt-1">{{ form.errors.asset_code }}</p>
                        </div>

                        <!-- Condition -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Kondisi Awal <span class="text-red-500">*</span></label>
                            <select v-model="form.condition" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                                <option value="BAIK">✨ Baik (Layak Pakai)</option>
                                <option value="RUSAK_RINGAN">⚠ Rusak Ringan (Bisa Diperbaiki)</option>
                                <option value="RUSAK_BERAT">❌ Rusak Berat (Tidak Layak)</option>
                            </select>
                        </div>
                        
                        <div v-if="form.type === 'FIXED'">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Status Awal</label>
                            <select v-model="form.status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                                <option value="AVAILABLE">Tersedia</option>
                                <option value="MAINTENANCE">Maintenance</option>
                                <option value="LOST">Hilang</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Col 2: Klasifikasi Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col h-full">
                    <h2 class="text-sm font-extrabold text-slate-700 uppercase tracking-wide border-b border-slate-100 pb-3 mb-5">Klasifikasi & Lokasi</h2>
                    <div class="space-y-4">
                        <!-- Type -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Tipe Aset <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label v-for="opt in [{val:'FIXED', label:'📦 Aset Tetap', desc:'1 unit = 1 record'}, {val:'CONSUMABLE', label:'🧴 Habis Pakai', desc:'Berbasis stok'}]"
                                       :key="opt.val"
                                       class="flex items-start gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all"
                                       :class="form.type === opt.val ? 'border-indigo-500 bg-indigo-50/50 shadow-inner' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'">
                                    <input type="radio" v-model="form.type" :value="opt.val" class="accent-indigo-600 mt-0.5"/>
                                    <div>
                                        <p class="text-[13px] font-bold text-slate-700 leading-none">{{ opt.label }}</p>
                                        <p class="text-[10px] font-medium text-slate-400 mt-1">{{ opt.desc }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div v-if="form.type === 'CONSUMABLE'">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Jumlah Stok <span class="text-red-500">*</span></label>
                            <input v-model="form.stock" type="number" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                            <p v-if="form.errors.stock" class="text-red-500 text-xs mt-1">{{ form.errors.stock }}</p>
                        </div>

                        <InlineCreator
                            v-model="form.category_id"
                            :items="localCategories"
                            label="Kategori"
                            placeholder="— Pilih Kategori —"
                            create-url="categories.store"
                            :can-create="canManage"
                            @item-created="onCategoryCreated"
                        />

                        <InlineCreator
                            v-model="form.location_id"
                            :items="localLocations"
                            label="Lokasi Penyimpanan"
                            placeholder="— Pilih Ruangan/Lokasi —"
                            create-url="locations.store"
                            :can-create="canManage"
                            @item-created="onLocationCreated"
                        />

                        <!-- Department Field for Super Admin -->
                        <div v-if="page.props.auth.roles.includes('super_admin')">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Alokasi Jurusan</label>
                            <select v-model="form.department" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                                <option value="">— Fasilitas Umum (Non-Jurusan) —</option>
                                <option v-for="d in props.departments" :key="d" :value="d">{{ d }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Col 3: Media & Action -->
                <div class="flex flex-col gap-6 h-full">
                    <!-- Photo & Desc Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex-1 flex flex-col">
                        <h2 class="text-sm font-extrabold text-slate-700 uppercase tracking-wide border-b border-slate-100 pb-3 mb-5">Media & Keterangan</h2>
                        
                        <div class="mb-5 flex-1">
                            <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Foto Dokumentasi</label>
                            <ImageUploader v-model="form.images" :max-files="4"/>
                            <p v-if="form.errors.images" class="text-red-500 text-xs mt-2">{{ form.errors.images }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Keterangan Tambahan</label>
                            <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none" placeholder="Spesifikasi kelengkapan, garansi, dll..."/>
                        </div>
                    </div>

                    <!-- Sticky-like Action Buttons -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex justify-end gap-3 items-center">
                        <a :href="route('assets.index')" class="px-5 py-2.5 rounded-xl border border-transparent text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                            Batal
                        </a>
                        <button type="submit" :disabled="form.processing"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-extrabold hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all disabled:opacity-60 flex items-center gap-2">
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan Data
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
