<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import InlineCreator from '@/Components/InlineCreator.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    asset:       { type: Object, required: true },
    categories:  { type: Array,  default: () => [] },
    locations:   { type: Array,  default: () => [] },
    departments: { type: Array,  default: () => [] },
});

const localCategories = ref([...props.categories]);
const localLocations  = ref([...props.locations]);

const page = usePage();
const canManage = page.props.auth.roles.includes('super_admin') ||
                  page.props.auth.roles.includes('kajur');

const form = useForm({
    name:        props.asset.name,
    brand:       props.asset.brand ?? '',
    year:        props.asset.year ?? new Date().getFullYear(),
    condition:   props.asset.condition,
    category_id: props.asset.category_id ?? '',
    location_id: props.asset.location_id ?? '',
    department:  props.asset.department ?? '',
    type:        props.asset.type,
    stock:       props.asset.stock,
    status:      props.asset.status ?? 'AVAILABLE',
    description: props.asset.description ?? '',
    asset_code:  props.asset.asset_code ?? '',
    images:      [],
    _method:     'PUT',
});

function onCategoryCreated(item) {
    localCategories.value.push(item);
}

function onLocationCreated(item) {
    localLocations.value.push(item);
}

function submit() {
    form.post(route('assets.update', props.asset.id), { forceFormData: true });
}

function deleteExistingImage(imageId) {
    router.delete(route('asset-images.destroy', imageId), { preserveScroll: true });
}
</script>

<template>
    <Head title="Edit Aset"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Edit Aset — {{ asset.name }}</h1>
        </template>

        <div class="max-w-3xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Informasi Dasar</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Aset <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Merek</label>
                            <input v-model="form.brand" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tahun</label>
                            <input v-model="form.year" type="number" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Kode Aset</label>
                            <input v-model="form.asset_code" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none font-mono"/>
                            <p v-if="form.errors.asset_code" class="text-red-500 text-xs mt-1">{{ form.errors.asset_code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Kondisi <span class="text-red-500">*</span></label>
                            <select v-model="form.condition" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                                <option value="BAIK">Baik</option>
                                <option value="RUSAK_RINGAN">Rusak Ringan</option>
                                <option value="RUSAK_BERAT">Rusak Berat</option>
                            </select>
                        </div>
                    </div>
                </div>


                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Klasifikasi</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Tipe Aset</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.type" value="FIXED" class="accent-indigo-600"/>
                                    <span class="text-sm">Aset Tetap</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.type" value="CONSUMABLE" class="accent-indigo-600"/>
                                    <span class="text-sm">Habis Pakai</span>
                                </label>
                            </div>
                        </div>
                        <div v-if="form.type === 'FIXED'">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                            <select v-model="form.status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                                <option value="AVAILABLE">Tersedia</option>
                                <option value="BORROWED">Dipinjam</option>
                                <option value="MAINTENANCE">Maintenance</option>
                                <option value="LOST">Hilang</option>
                                <option value="ARCHIVED">Diarsipkan</option>
                            </select>
                        </div>
                        <div v-if="form.type === 'CONSUMABLE'">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Jumlah Stok</label>
                            <input v-model="form.stock" type="number" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                            <p v-if="form.errors.stock" class="text-red-500 text-xs mt-1">{{ form.errors.stock }}</p>
                        </div>

                        <InlineCreator
                            v-model="form.category_id"
                            :items="localCategories"
                            label="Kategori"
                            placeholder="— Pilih —"
                            create-url="categories.store"
                            :can-create="canManage"
                            @item-created="onCategoryCreated"
                        />

                        <InlineCreator
                            v-model="form.location_id"
                            :items="localLocations"
                            label="Lokasi"
                            placeholder="— Pilih —"
                            create-url="locations.store"
                            :can-create="canManage"
                            @item-created="onLocationCreated"
                        />

                        <!-- Department Field for Super Admin -->
                        <div v-if="page.props.auth.roles.includes('super_admin')">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Jurusan / Departemen</label>
                            <select v-model="form.department" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none bg-white">
                                <option value="">— Tidak Spesifik (Umum) —</option>
                                <option v-for="d in props.departments" :key="d" :value="d">{{ d }}</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 mt-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Keterangan</label>
                            <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none"/>
                        </div>
                    </div>
                </div>

                <!-- Foto -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Foto Dokumentasi</h2>
                    <ImageUploader
                        v-model="form.images"
                        :existing-images="asset.images?.map(i => ({ id: i.id, url: '/storage/' + i.path })) ?? []"
                        @delete-existing="deleteExistingImage"
                    />
                </div>

                <div class="flex justify-end gap-3">
                    <a :href="route('assets.show', asset.id)" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit" :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-60 flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
