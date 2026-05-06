<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';

const page   = usePage();
const user   = computed(() => page.props.auth.user);
const role   = computed(() => user.value?.role ?? '');
const unread = computed(() => page.props.unreadNotifications ?? 0);

const isAdmin  = computed(() => role.value === 'ADMIN');
const isKajur  = computed(() => role.value === 'KAJUR');
const isViewer = computed(() => role.value === 'VIEWER');

const sidebarOpen = ref(false);

const navItems = computed(() => {
    const items = [
        { name: 'Dashboard',        route: 'dashboard',        icon: 'home',       always: true },
        { name: 'Aset',             route: 'assets.index',     icon: 'cube',       always: true },
        { name: 'Peminjaman',       route: 'borrows.index',    icon: 'clipboard',  always: true },
        { name: 'Laporan',          route: 'reports.index',    icon: 'chart-bar',  roles: ['ADMIN', 'VIEWER'] },
        { name: 'Pengguna',         route: 'users.index',      icon: 'users',      roles: ['ADMIN'] },
        { name: 'Log Audit',        route: 'audit-logs.index', icon: 'shield',     roles: ['ADMIN'] },
    ];
    return items.filter(item =>
        item.always || item.roles?.includes(role.value)
    );
});

const roleLabelMap = { ADMIN: 'Administrator', KAJUR: 'Kepala Jurusan', VIEWER: 'Viewer' };
const roleLabel = computed(() => roleLabelMap[role.value] ?? role.value);
</script>

<template>
    <div class="flex min-h-screen bg-slate-50 overflow-hidden font-inter text-slate-700">

        <!-- KIRI: Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-[4px_0_24px_rgba(0,0,0,0.02)] flex flex-col
                   transform transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0 lg:min-h-screen"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Brand -->
            <div class="px-6 pt-8 pb-6">
                <Link :href="route('dashboard')" class="flex items-center gap-3 group">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="font-bold text-lg text-slate-800 tracking-tight">Manajemen Aset</span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 space-y-2.5 overflow-y-auto mt-6">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200"
                    :class="route().current(item.route.replace('.index','.*'))
                        ? 'bg-indigo-50 text-indigo-700 font-semibold'
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                >
                    <svg class="w-5 h-5" :class="route().current(item.route.replace('.index','.*')) ? 'text-indigo-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="item.icon==='home'"      stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        <path v-if="item.icon==='cube'"      stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        <path v-if="item.icon==='clipboard'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        <path v-if="item.icon==='chart-bar'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        <path v-if="item.icon==='users'"     stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        <path v-if="item.icon==='shield'"    stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <div class="p-5 mt-auto border-t border-slate-100">
                <Link :href="route('logout')" method="post" as="button" class="flex items-center justify-center gap-2 w-full px-4 py-3 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </Link>
            </div>
        </aside>

        <!-- Sidebar overlay (mobile) -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"/>

        <!-- Main Content Area -->
        <div class="flex-1 flex min-w-0 overflow-hidden relative">
            
            <!-- Mobile Toggle -->
            <button @click="sidebarOpen = !sidebarOpen" class="absolute top-4 left-4 z-30 lg:hidden p-2 bg-white rounded-lg shadow-sm text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- MIDDLE: Scrollable -->
            <main class="flex-1 overflow-y-auto px-6 py-8 sm:px-10 lg:px-12 flex flex-col gap-8 relative">
                <!-- Flash Messages -->
                <div class="absolute top-4 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-md">
                    <FlashMessage />
                </div>

                <slot />
            </main>

            <!-- RIGHT: Optional Sidebar (Desktop Only) -->
            <aside v-if="$slots.right" class="hidden xl:flex w-[320px] bg-white shadow-[-4px_0_24px_rgba(0,0,0,0.02)] flex-col flex-shrink-0 overflow-y-auto z-10 border-l border-slate-50">
                <slot name="right" />
            </aside>

        </div>
    </div>
</template>
