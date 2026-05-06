<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    roles: { type: Object, default: () => ({}) }, // { ADMIN: 'Administrator', KAJUR: '...', VIEWER: '...' }
});

const form = useForm({
    name:       '',
    email:      '',
    password:   '',
    password_confirmation: '',
    nip:        '',
    department: [],
    role:       'KAJUR',
});

const newDepartment = ref('');
function addDepartment() {
    const val = newDepartment.value.trim();
    if (val && !form.department.includes(val)) {
        form.department.push(val);
    }
    newDepartment.value = '';
}
function removeDepartment(idx) {
    form.department.splice(idx, 1);
}
</script>

<template>
    <Head title="Tambah Pengguna"/>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-slate-800 font-bold text-lg">Tambah Pengguna Baru</h1>
        </template>

        <div class="max-w-xl mx-auto">
            <form @submit.prevent="form.post(route('users.store'))" class="space-y-5">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-4">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Pengguna</h2>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input v-model="form.email" type="email" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
                            <input v-model="form.password" type="password" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                            <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi <span class="text-red-500">*</span></label>
                            <input v-model="form.password_confirmation" type="password" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">NIP</label>
                            <input v-model="form.nip" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none font-mono"/>
                        </div>
                        <div v-if="form.role === 'KAJUR'">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Departemen / Jurusan</label>
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="(dep, idx) in form.department" :key="idx"
                                          class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-full border border-indigo-100">
                                        {{ dep }}
                                        <button type="button" @click="removeDepartment(idx)" class="hover:text-indigo-900 focus:outline-none">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </span>
                                </div>
                                <input v-model="newDepartment" @keydown.enter.prevent="addDepartment" type="text" placeholder="Ketik lalu Enter..."
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none"/>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Peran <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            <label v-for="(label, key) in roles" :key="key"
                                   class="flex flex-col items-center p-3 rounded-xl border-2 cursor-pointer transition-all text-center"
                                   :class="form.role === key ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" v-model="form.role" :value="key" class="hidden"/>
                                <span class="text-sm font-semibold text-slate-700">{{ label }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a :href="route('users.index')" class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit" :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-60 flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Buat Pengguna
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
