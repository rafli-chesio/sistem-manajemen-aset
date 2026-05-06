<script setup>
defineProps({
    status: { type: String, required: true },
});

const config = {
    // Asset Status
    AVAILABLE:    { label: 'Tersedia',      class: 'bg-emerald-100 text-emerald-700 ring-emerald-300' },
    BORROWED:     { label: 'Dipinjam',      class: 'bg-blue-100 text-blue-700 ring-blue-300' },
    MAINTENANCE:  { label: 'Maintenance',   class: 'bg-amber-100 text-amber-700 ring-amber-300' },
    LOST:         { label: 'Hilang',        class: 'bg-red-100 text-red-700 ring-red-300' },
    ARCHIVED:     { label: 'Diarsipkan',    class: 'bg-slate-100 text-slate-500 ring-slate-200' },
    // Borrow Status
    PENDING:      { label: 'Menunggu',      class: 'bg-amber-100 text-amber-700 ring-amber-300' },
    APPROVED:     { label: 'Disetujui',     class: 'bg-emerald-100 text-emerald-700 ring-emerald-300' },
    REJECTED:     { label: 'Ditolak',       class: 'bg-red-100 text-red-700 ring-red-300' },
    OVERDUE:      { label: 'Terlambat',     class: 'bg-orange-100 text-orange-700 ring-orange-300' },
    RETURNED:     { label: 'Dikembalikan',  class: 'bg-slate-100 text-slate-600 ring-slate-300' },
    // Asset Condition
    BAIK:         { label: 'Baik',          class: 'bg-emerald-100 text-emerald-700 ring-emerald-300' },
    RUSAK_RINGAN: { label: 'Rusak Ringan',  class: 'bg-amber-100 text-amber-700 ring-amber-300' },
    RUSAK_BERAT:  { label: 'Rusak Berat',   class: 'bg-red-100 text-red-700 ring-red-300' },
    // Asset Type
    FIXED:        { label: 'Aset Tetap',    class: 'bg-indigo-100 text-indigo-700 ring-indigo-300' },
    CONSUMABLE:   { label: 'Habis Pakai',   class: 'bg-sky-100 text-sky-700 ring-sky-300' },
};

const item = (status) => config[status] ?? { label: status, class: 'bg-gray-100 text-gray-600 ring-gray-200' };
</script>

<template>
    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold ring-1 ring-inset"
          :class="item(status).class">
        {{ item(status).label }}
    </span>
</template>
