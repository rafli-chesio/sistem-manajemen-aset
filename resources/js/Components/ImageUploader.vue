<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    maxFiles:   { type: Number, default: 10 },
    accept:     { type: String, default: 'image/jpeg,image/png,image/webp' },
    existingImages: { type: Array, default: () => [] }, // [{id, url}]
});

const emit = defineEmits(['update:modelValue', 'delete-existing']);

const previews      = ref([]);
const dragOver      = ref(false);
const deleteImageId = ref(null);

const totalCount = computed(() => previews.value.length + props.existingImages.length);

function onFileChange(e) {
    addFiles(Array.from(e.target.files));
}

function onDrop(e) {
    dragOver.value = false;
    addFiles(Array.from(e.dataTransfer.files));
}

function addFiles(files) {
    const allowed = props.maxFiles - totalCount.value;
    files.slice(0, allowed).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        previews.value.push({ file, url: URL.createObjectURL(file) });
    });
    emit('update:modelValue', previews.value.map(p => p.file));
}

function removeNew(index) {
    URL.revokeObjectURL(previews.value[index].url);
    previews.value.splice(index, 1);
    emit('update:modelValue', previews.value.map(p => p.file));
}
</script>

<template>
    <div class="space-y-3">
        <!-- Existing images -->
        <div v-if="existingImages.length" class="flex flex-wrap gap-3">
            <div v-for="img in existingImages" :key="img.id"
                 class="relative group w-24 h-24 rounded-xl overflow-hidden border border-slate-200 shadow-sm">
                <img :src="img.url" class="w-full h-full object-cover"/>
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <button @click="emit('delete-existing', img.id)" type="button"
                            class="p-1.5 rounded-full bg-red-500 text-white hover:bg-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- New file previews -->
        <div v-if="previews.length" class="flex flex-wrap gap-3">
            <div v-for="(prev, i) in previews" :key="i"
                 class="relative group w-24 h-24 rounded-xl overflow-hidden border border-indigo-200 shadow-sm">
                <img :src="prev.url" class="w-full h-full object-cover"/>
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <button @click="removeNew(i)" type="button"
                            class="p-1.5 rounded-full bg-red-500 text-white hover:bg-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <span class="absolute bottom-1 left-1 bg-indigo-600 text-white text-xs px-1 rounded">Baru</span>
            </div>
        </div>

        <!-- Dropzone -->
        <label v-if="totalCount < maxFiles"
               @dragover.prevent="dragOver = true"
               @dragleave="dragOver = false"
               @drop.prevent="onDrop"
               class="flex flex-col items-center justify-center w-full h-32 rounded-xl border-2 border-dashed cursor-pointer transition-colors"
               :class="dragOver ? 'border-indigo-400 bg-indigo-50' : 'border-slate-300 bg-slate-50 hover:bg-slate-100'">
            <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm text-slate-500">
                <span class="font-medium text-indigo-600">Klik untuk upload</span> atau seret file di sini
            </p>
            <p class="text-xs text-slate-400 mt-1">JPG, PNG, WEBP • Maks 5MB per file</p>
            <input type="file" class="hidden" multiple :accept="accept" @change="onFileChange"/>
        </label>
        <p v-if="totalCount >= maxFiles" class="text-xs text-slate-500">
            Batas maksimal {{ maxFiles }} foto tercapai.
        </p>
    </div>
</template>
