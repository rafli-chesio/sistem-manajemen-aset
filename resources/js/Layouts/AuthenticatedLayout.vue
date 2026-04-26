<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => page.props.auth.roles);
const unread = computed(() => page.props.unreadNotifications ?? 0);

const isSuperAdmin = computed(() => roles.value.includes('super_admin'));
const isViewer     = computed(() => roles.value.includes('viewer'));
const isKajur      = computed(() => roles.value.includes('kajur'));

const sidebarOpen = ref(false);

const navItems = computed(() => {
    const items = [
        { name: 'Dashboard',        route: 'dashboard',       icon: 'home',       always: true },
        { name: 'Aset',             route: 'assets.index',    icon: 'cube',       always: true },
        { name: 'Peminjaman',       route: 'borrows.index',   icon: 'clipboard',  always: true },
        { name: 'Laporan',          route: 'reports.index',   icon: 'chart-bar',  roles: ['super_admin','viewer'] },
        { name: 'Pengguna',         route: 'users.index',     icon: 'users',      roles: ['super_admin'] },
        { name: 'Log Audit',        route: 'audit-logs.index',icon: 'shield',     roles: ['super_admin'] },
    ];
    return items.filter(item =>
        item.always || item.roles?.some(r => roles.value.includes(r))
    );
});

const roleLabel = computed(() => {
    if (isSuperAdmin.value) return 'Super Admin';
    if (isViewer.value)     return 'Viewer';
    if (isKajur.value)      return 'Kajur';
    return '';
});
</script>

<template>
    <div class="flex h-screen bg-slate-50 overflow-hidden font-inter">

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-900 to-slate-800 shadow-2xl flex flex-col
                   transform transition-transform duration-300 ease-in-out
                   lg:relative lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Brand -->
            <div class="px-6 py-5 border-b border-slate-700/50">
                <Link :href="route('dashboard')" class="flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-white font-bold text-sm leading-tight">Manajemen Aset</p>
                        <p class="text-slate-400 text-xs">Sistem Sekolah</p>
                    </div>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 group"
                    :class="route().current(item.route.replace('.index','.*'))
                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-900/40'
                        : 'text-slate-300 hover:bg-slate-700/60 hover:text-white'"
                >
                    <!-- Icons -->
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <!-- User Footer -->
            <div class="px-3 py-4 border-t border-slate-700/50">
                <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-700/40">
                    <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-xs font-bold">{{ user?.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-white text-xs font-semibold truncate">{{ user?.name }}</p>
                        <p class="text-slate-400 text-xs truncate">{{ roleLabel }}</p>
                    </div>
                    <Link :href="route('logout')" method="post" as="button"
                          class="text-slate-400 hover:text-red-400 transition-colors" title="Keluar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Sidebar overlay (mobile) -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-40 lg:hidden"/>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Top bar -->
            <header class="flex-shrink-0 h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <!-- Mobile hamburger -->
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <!-- Page heading slot -->
                    <slot name="header">
                        <h1 class="text-slate-800 font-semibold text-base"></h1>
                    </slot>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Notifications -->
                    <Link :href="route('notifications.index')" class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span v-if="unread > 0"
                              class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center leading-none font-bold">
                            {{ unread > 9 ? '9+' : unread }}
                        </span>
                    </Link>
                    <!-- Profile link -->
                    <Link :href="route('profile.edit')" class="flex items-center gap-2 text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                        <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-indigo-600 text-xs font-bold">{{ user?.name?.charAt(0)?.toUpperCase() }}</span>
                        </div>
                        <span class="hidden sm:block font-medium">{{ user?.name }}</span>
                    </Link>
                </div>
            </header>

            <!-- Flash Messages -->
            <FlashMessage />

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
