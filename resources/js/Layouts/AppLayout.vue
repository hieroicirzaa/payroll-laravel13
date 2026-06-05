<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Building2, FileText, LayoutDashboard, LogOut, Menu, Receipt, ShieldCheck, Users, WalletCards, UserRound, Landmark, X } from 'lucide-vue-next';

const props = defineProps({ title: { type: String, default: 'Payroll System' } });
const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role);
const sidebarOpen = ref(true);
const mobileSidebarOpen = ref(false);

const canSuper = computed(() => role.value === 'super_admin');
const canAdmin = computed(() => ['super_admin', 'admin_company'].includes(role.value));

const links = computed(() => [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, show: true },
    { label: 'Company', href: '/companies', icon: Building2, show: canSuper.value },
    { label: 'User Admin', href: '/users', icon: ShieldCheck, show: canSuper.value },
    { label: 'Karyawan', href: '/employees', icon: Users, show: canAdmin.value },
    { label: 'Komponen Gaji', href: '/salary-components', icon: WalletCards, show: canSuper.value },
    { label: 'Payroll', href: '/payroll', icon: Receipt, show: true },
    { label: 'Dokumen', href: '/documents', icon: FileText, show: true },
    { label: 'SPT', href: '/spt-reports', icon: Landmark, show: true },
    { label: 'Profil', href: '/profile', icon: UserRound, show: true },
]);

onMounted(() => {
    const saved = localStorage.getItem('payroll_sidebar_open');
    if (saved !== null) {
        sidebarOpen.value = saved === 'true';
    }
});

watch(sidebarOpen, (value) => {
    localStorage.setItem('payroll_sidebar_open', String(value));
});

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}

function openMobileSidebar() {
    mobileSidebarOpen.value = true;
}

function closeSidebar() {
    sidebarOpen.value = false;
    mobileSidebarOpen.value = false;
}

function closeMobileSidebar() {
    mobileSidebarOpen.value = false;
}

function logout() {
    router.post('/logout');
}
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-slate-100 transition-colors duration-200 dark:bg-slate-950">
        <button
            v-if="mobileSidebarOpen"
            type="button"
            class="fixed inset-0 z-20 bg-slate-950/40 backdrop-blur-sm lg:hidden"
            aria-label="Tutup area sidebar"
            @click="closeMobileSidebar"
        />

        <aside
            class="fixed inset-y-0 left-0 z-30 w-72 border-r border-slate-200 bg-white p-5 shadow-xl transition duration-200 ease-in-out dark:border-slate-800 dark:bg-slate-900 lg:shadow-none"
            :class="[
                mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarOpen ? 'lg:translate-x-0' : 'lg:-translate-x-full'
            ]"
        >
            <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-950 p-4 text-white dark:bg-slate-800">
                <div>
                    <div class="text-lg font-bold">Payroll Secure</div>
                    <div class="mt-1 text-xs text-slate-300">Laravel + Inertia + Vue</div>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white transition hover:bg-white/20"
                    aria-label="Tutup sidebar"
                    title="Tutup sidebar"
                    @click="closeSidebar"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <nav class="mt-6 space-y-1">
                <Link
                    v-for="item in links.filter(i => i.show)"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                    :class="{ 'bg-teal-50 text-teal-800 dark:bg-teal-950/60 dark:text-teal-200': page.url.startsWith(item.href) }"
                    @click="closeMobileSidebar"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                    {{ item.label }}
                </Link>
            </nav>
        </aside>

        <div class="transition-all duration-200" :class="sidebarOpen ? 'lg:pl-72' : 'lg:pl-0'">
            <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/90 px-5 py-4 backdrop-blur transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900/90">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            :aria-label="sidebarOpen ? 'Tutup sidebar' : 'Buka sidebar'"
                            :title="sidebarOpen ? 'Tutup sidebar' : 'Buka sidebar'"
                            @click="toggleSidebar"
                        >
                            <X v-if="sidebarOpen" class="hidden h-5 w-5 lg:block" />
                            <Menu v-else class="hidden h-5 w-5 lg:block" />
                            <Menu class="h-5 w-5 lg:hidden" @click.stop="openMobileSidebar" />
                        </button>
                        <div class="min-w-0">
                            <h1 class="truncate text-xl font-bold text-slate-950 dark:text-slate-100">{{ title }}</h1>
                            <p class="truncate text-sm text-slate-500 dark:text-slate-400">
                                {{ user?.name }} · {{ user?.role_label }}<span v-if="user?.company"> · {{ user.company.name }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <ThemeToggle />
                        <button type="button" class="btn-secondary" @click="logout">
                            <LogOut class="mr-2 h-4 w-4" /> Logout
                        </button>
                    </div>
                </div>
            </header>

            <main class="p-5 lg:p-8">
                <FlashMessage class="mb-5" />
                <slot />
            </main>
        </div>
    </div>
</template>
