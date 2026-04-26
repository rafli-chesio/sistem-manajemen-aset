<script setup>
/**
 * InlineCreator — Dropdown select dengan kemampuan tambah item baru inline.
 * Digunakan untuk Kategori dan Lokasi di form aset.
 *
 * Props:
 *  - modelValue   : ID yang dipilih (v-model)
 *  - items        : Array { id, name } dari server (reactive)
 *  - label        : Label field (cth. "Kategori")
 *  - placeholder  : Teks placeholder option kosong
 *  - createUrl    : Route name untuk POST (cth. 'categories.store')
 *  - canCreate    : Boolean — apakah user boleh tambah baru
 */
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue:  { type: [Number, String], default: '' },
    items:       { type: Array, required: true },
    label:       { type: String, required: true },
    placeholder: { type: String, default: '— Pilih —' },
    createUrl:   { type: String, required: true },
    canCreate:   { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'item-created']);

// ── Local state ──────────────────────────────────────────────────────────────
const showForm = ref(false);
const newName  = ref('');
const saving   = ref(false);
const error    = ref('');
const inputRef = ref(null);

function openForm() {
    showForm.value = true;
    newName.value  = '';
    error.value    = '';
    // Focus the input on next tick
    setTimeout(() => inputRef.value?.focus(), 50);
}

function cancelForm() {
    showForm.value = false;
    newName.value  = '';
    error.value    = '';
}

async function saveNew() {
    if (!newName.value.trim()) {
        error.value = 'Nama tidak boleh kosong.';
        return;
    }

    saving.value = true;
    error.value  = '';

    try {
        // Get CSRF token from meta tag (Laravel Breeze sets this)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await axios.post(
            route(props.createUrl),
            { name: newName.value.trim() },
            { headers: { 'X-CSRF-TOKEN': csrfToken } }
        );

        const created = response.data; // { id, name }

        // Emit the new item to parent so it can add to its local list
        emit('item-created', created);

        // Auto-select the newly created item
        emit('update:modelValue', created.id);

        cancelForm();
    } catch (err) {
        if (err.response?.status === 422) {
            const errors = err.response.data?.errors;
            error.value = errors?.name?.[0] ?? err.response.data?.message ?? 'Validasi gagal.';
        } else {
            error.value = 'Terjadi kesalahan. Coba lagi.';
        }
    } finally {
        saving.value = false;
    }
}

function handleKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        saveNew();
    }
    if (e.key === 'Escape') {
        cancelForm();
    }
}
</script>

<template>
    <div class="space-y-1.5">
        <!-- Label -->
        <label class="block text-sm font-medium text-slate-700">{{ label }}</label>

        <!-- Select + Tambah Baru button -->
        <div class="flex gap-2">
            <select
                :value="modelValue"
                @change="emit('update:modelValue', $event.target.value ? Number($event.target.value) : '')"
                class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none bg-white"
            >
                <option value="">{{ placeholder }}</option>
                <option v-for="item in items" :key="item.id" :value="item.id">
                    {{ item.name }}
                </option>
            </select>

            <!-- + Baru button — hanya tampil jika canCreate -->
            <button
                v-if="canCreate && !showForm"
                type="button"
                @click="openForm"
                title="Tambah baru"
                class="flex items-center gap-1 px-3 py-2 rounded-xl border border-dashed border-indigo-300 text-indigo-600 text-sm font-medium hover:bg-indigo-50 hover:border-indigo-400 transition-all whitespace-nowrap"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Baru
            </button>
        </div>

        <!-- Inline mini form — animasi slide-down -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-if="showForm"
                class="mt-1 p-3 rounded-xl border border-indigo-200 bg-indigo-50 space-y-2"
            >
                <p class="text-xs font-semibold text-indigo-700">Tambah {{ label }} Baru</p>
                <div class="flex gap-2">
                    <input
                        ref="inputRef"
                        v-model="newName"
                        type="text"
                        :placeholder="`Nama ${label}...`"
                        @keydown="handleKeydown"
                        class="flex-1 px-3 py-1.5 rounded-lg border border-indigo-300 text-sm focus:ring-2 focus:ring-indigo-300 focus:border-indigo-500 outline-none bg-white"
                    />
                    <button
                        type="button"
                        @click="saveNew"
                        :disabled="saving"
                        class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 disabled:opacity-60 transition-colors flex items-center gap-1"
                    >
                        <svg v-if="saving" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ saving ? '' : 'Simpan' }}
                    </button>
                    <button
                        type="button"
                        @click="cancelForm"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-500 text-xs hover:bg-white transition-colors"
                    >
                        Batal
                    </button>
                </div>
                <!-- Error message -->
                <p v-if="error" class="text-xs text-red-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ error }}
                </p>
                <p class="text-xs text-indigo-400">Tekan Enter untuk simpan, Esc untuk batal</p>
            </div>
        </Transition>
    </div>
</template>
