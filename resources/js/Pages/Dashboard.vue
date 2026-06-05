<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
const props = defineProps({ stats: Object, recent_payrolls: Array });
const rupiah = (v) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(v || 0));
</script>
<template>
    <AppLayout title="Dashboard">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="card"><p class="text-sm text-slate-500">Company</p><p class="mt-2 text-3xl font-bold">{{ stats.companies }}</p></div>
            <div class="card"><p class="text-sm text-slate-500">User</p><p class="mt-2 text-3xl font-bold">{{ stats.users }}</p></div>
            <div class="card"><p class="text-sm text-slate-500">Karyawan</p><p class="mt-2 text-3xl font-bold">{{ stats.employees }}</p></div>
            <div class="card"><p class="text-sm text-slate-500">Net Payroll</p><p class="mt-2 text-2xl font-bold">{{ rupiah(stats.net_amount) }}</p></div>
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="card"><p class="text-sm text-slate-500">Payroll Total</p><p class="mt-2 text-2xl font-bold">{{ stats.payrolls }}</p></div>
            <div class="card"><p class="text-sm text-slate-500">Berhasil Dibayar</p><p class="mt-2 text-2xl font-bold text-teal-700">{{ stats.paid_payrolls }}</p></div>
            <div class="card"><p class="text-sm text-slate-500">Gagal</p><p class="mt-2 text-2xl font-bold text-rose-700">{{ stats.failed_payrolls }}</p></div>
        </div>
        <div class="card mt-6 overflow-hidden p-0">
            <div class="border-b border-slate-100 p-5 dark:border-slate-800"><h2 class="font-bold">Payroll Terbaru</h2></div>
            <table class="w-full">
                <thead><tr><th class="table-th">Karyawan</th><th class="table-th">Periode</th><th class="table-th">Net</th><th class="table-th">Status</th></tr></thead>
                <tbody>
                    <tr v-for="p in recent_payrolls" :key="p.id"><td class="table-td">{{ p.employee?.user?.name }}</td><td class="table-td">{{ p.period?.name }}</td><td class="table-td">{{ rupiah(p.net_amount) }}</td><td class="table-td"><StatusBadge :value="p.status" /></td></tr>
                    <tr v-if="!recent_payrolls.length"><td class="table-td" colspan="4">Belum ada data payroll.</td></tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
