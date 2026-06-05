<script setup>
import { Link, router } from '@inertiajs/vue3';
import { Download, Mail } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({ payroll: Object });

const monthNames = {
    1: 'Januari',
    2: 'Februari',
    3: 'Maret',
    4: 'April',
    5: 'Mei',
    6: 'Juni',
    7: 'Juli',
    8: 'Agustus',
    9: 'September',
    10: 'Oktober',
    11: 'November',
    12: 'Desember',
};

const rupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value || 0));

function monthLabel(month) {
    return `Bulan ke-${month} (${monthNames[Number(month)] || month})`;
}

function sendSlipEmail() {
    if (confirm(`Kirim slip gaji ke email ${props.payroll.employee?.user?.email}?`)) {
        router.post(`/payrolls/${props.payroll.id}/slip/email`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout title="Slip Gaji">
        <div class="card mx-auto max-w-5xl">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 dark:border-slate-800 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Slip Gaji</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ payroll.period?.name }} · {{ monthLabel(payroll.period?.period_month) }} · {{ payroll.period?.period_year }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <StatusBadge :value="payroll.status" />
                    <a class="btn-primary" :href="`/payrolls/${payroll.id}/slip/download`">
                        <Download class="mr-2 h-4 w-4" /> Download PDF
                    </a>
                    <button class="btn-secondary" @click="sendSlipEmail">
                        <Mail class="mr-2 h-4 w-4" /> Kirim Email
                    </button>
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Karyawan</p>
                    <p class="font-bold">{{ payroll.employee?.user?.name }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ payroll.employee?.employee_number }} · {{ payroll.employee?.position }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Company</p>
                    <p class="font-bold">{{ payroll.company?.name }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ payroll.company?.code }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-500 dark:text-slate-400">Periode</p>
                    <p class="font-bold">{{ monthLabel(payroll.period?.period_month) }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ payroll.period?.start_date?.slice(0, 10) }} - {{ payroll.period?.end_date?.slice(0, 10) }}</p>
                </div>
            </div>

            <table class="mt-6 w-full">
                <thead>
                    <tr>
                        <th class="table-th">Komponen</th>
                        <th class="table-th">Tipe</th>
                        <th class="table-th text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in payroll.items" :key="item.id">
                        <td class="table-td">{{ item.name }}</td>
                        <td class="table-td">{{ item.type }}</td>
                        <td class="table-td text-right">{{ rupiah(item.amount) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-6 grid gap-3 md:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Gross</p>
                    <p class="font-bold">{{ rupiah(payroll.gross_amount) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Potongan</p>
                    <p class="font-bold">{{ rupiah(payroll.deduction_amount) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pajak</p>
                    <p class="font-bold">{{ rupiah(payroll.tax_amount) }}</p>
                </div>
                <div class="rounded-xl bg-teal-50 p-4 dark:bg-teal-950/50">
                    <p class="text-xs text-teal-700 dark:text-teal-300">Net</p>
                    <p class="text-xl font-bold text-teal-800 dark:text-teal-200">{{ rupiah(payroll.net_amount) }}</p>
                </div>
            </div>

            <Link href="/payroll" class="btn-secondary mt-6">Kembali</Link>
        </div>
    </AppLayout>
</template>
