<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import InlineCreator from '@/Components/InlineCreator.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    locations:  { type: Array, default: () => [] },
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
    condition:   'GOOD',
    category_id: '',
    location_id: '',
    type:        'UNIQUE',
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

        <div class="max-w-3xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Informasi Dasar</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Name -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Aset <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none" placeholder="cth. Laptop Dell Inspiron"/>
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <!-- Brand -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Merek</label>
                            <input v-model="form.brand" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none" placeholder="cth. Dell"/>
                        </div>
                        <!-- Year -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tahun Pengadaan</label>
                            <input v-model="form.year" type="number" min="1990" :max="new Date().getFullYear()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                        </div>
                        <!-- Asset Code -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Kode Aset <span class="text-slate-400 text-xs">(opsional, auto-generate)</span></label>
                            <input v-model="form.asset_code" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none font-mono" placeholder="cth. UNQ-2024-00001"/>
                            <p v-if="form.errors.asset_code" class="text-red-500 text-xs mt-1">{{ form.errors.asset_code }}</p>
                        </div>
                        <!-- Condition -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Kondisi Awal <span class="text-red-500">*</span></label>
                            <select v-model="form.condition" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                                <option value="GOOD">Baik</option>
                                <option value="FAIR">Cukup</option>
                                <option value="POOR">Buruk</option>
                                <option value="DAMAGED">Rusak</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Classification Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Klasifikasi</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Type -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Tipe Aset <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label v-for="opt in [{val:'UNIQUE', label:'🔖 Barang Unik', desc:'1 unit = 1 record'}, {val:'CONSUMABLE', label:'📦 Habis Pakai', desc:'Manajemen berbasis stok'}]"
                                       :key="opt.val"
                                       class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                                       :class="form.type === opt.val ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" v-model="form.type" :value="opt.val" class="accent-indigo-600"/>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700">{{ opt.label }}</p>
                                        <p class="text-xs text-slate-400">{{ opt.desc }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <!-- Stock (consumable only) -->
                        <div v-if="form.type === 'CONSUMABLE'" class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Jumlah Stok <span class="text-red-500">*</span></label>
                            <input v-model="form.stock" type="number" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                            <p v-if="form.errors.stock" class="text-red-500 text-xs mt-1">{{ form.errors.stock }}</p>
                        </div>
                        <!-- Status (unique only) -->
                        <div v-if="form.type === 'UNIQUE'">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Status Awal</label>
                            <select v-model="form.status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                                <option value="AVAILABLE">Tersedia</option>
                                <option value="DAMAGED">Rusak</option>
                                <option value="LOST">Hilang</option>
                            </select>
                        </div>

                        <!-- ── Kategori — dengan InlineCreator ───────────────── -->
                        <InlineCreator
                            v-model="form.category_id"
                            :items="localCategories"
                            label="Kategori"
                            placeholder="— Pilih Kategori —"
                            create-url="categories.store"
                            :can-create="canManage"
                            @item-created="onCategoryCreated"
                        />

                        <!-- ── Lokasi — dengan InlineCreator ─────────────────── -->
                        <InlineCreator
                            v-model="form.location_id"
                            :items="localLocations"
                            label="Lokasi"
                            placeholder="— Pilih Lokasi —"
                            create-url="locations.store"
                            :can-create="canManage"
                            @item-created="onLocationCreated"
                        />

                        <!-- Description -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Keterangan</label>
                            <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none" placeholder="Deskripsi tambahan..."/>
                        </div>
                    </div>
                </div>

                <!-- Photo Upload Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Foto Dokumentasi</h2>
                    <ImageUploader v-model="form.images" :max-files="10"/>
                    <p v-if="form.errors.images" class="text-red-500 text-xs mt-2">{{ form.errors.images }}</p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <a :href="route('assets.index')" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit" :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-60 flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Simpan Aset
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
