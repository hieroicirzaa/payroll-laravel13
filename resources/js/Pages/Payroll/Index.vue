<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Download, Mail } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormError from '@/Components/FormError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    periods: { type: Array, default: () => [] },
    payrolls: { type: Array, default: () => [] },
    companies: { type: Array, default: () => [] },
});

const user = usePage().props.auth.user;
const canManage = computed(() => ['super_admin', 'admin_company'].includes(user.role));
const editPeriodId = ref(null);

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

const periodForm = useForm({
    company_id: '',
    name: '',
    period_month: new Date().getMonth() + 1,
    period_year: new Date().getFullYear(),
    start_date: '',
    end_date: '',
    status: 'open',
});

const failForm = useForm({ failure_reason: '' });

const rupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value || 0));

function monthLabel(month) {
    return `Bulan ke-${month} (${monthNames[Number(month)] || month})`;
}

function resetPeriod() {
    editPeriodId.value = null;
    periodForm.reset();
    periodForm.status = 'open';
}

function editPeriod(period) {
    editPeriodId.value = period.id;
    periodForm.company_id = period.company_id;
    periodForm.name = period.name;
    periodForm.period_month = period.period_month;
    periodForm.period_year = period.period_year;
    periodForm.start_date = (period.start_date || '').slice(0, 10);
    periodForm.end_date = (period.end_date || '').slice(0, 10);
    periodForm.status = period.status;
}

function savePeriod() {
    if (editPeriodId.value) {
        periodForm.put(`/payroll-periods/${editPeriodId.value}`, { onSuccess: resetPeriod });
        return;
    }

    periodForm.post('/payroll-periods', { onSuccess: resetPeriod });
}

function generate(period) {
    if (confirm(`Generate payroll periode ${period.name}?`)) {
        router.post(`/payroll-periods/${period.id}/generate`);
    }
}

function destroyPeriod(period) {
    if (confirm('Hapus periode ini?')) {
        router.delete(`/payroll-periods/${period.id}`);
    }
}

function paid(payroll) {
    router.patch(`/payrolls/${payroll.id}/paid`);
}

function failed(payroll) {
    const reason = prompt('Alasan gagal payroll:');
    if (reason) {
        failForm.failure_reason = reason;
        failForm.patch(`/payrolls/${payroll.id}/failed`, { preserveScroll: true });
    }
}

function destroyPayroll(payroll) {
    if (confirm('Hapus payroll draft/gagal ini?')) {
        router.delete(`/payrolls/${payroll.id}`);
    }
}

function sendSlipEmail(payroll) {
    if (confirm(`Kirim slip gaji ke email ${payroll.employee?.user?.email}?`)) {
        router.post(`/payrolls/${payroll.id}/slip/email`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout title="Payroll">
        <div v-if="canManage" class="grid gap-6 xl:grid-cols-3">
            <div class="card">
                <h2 class="font-bold">{{ editPeriodId ? 'Edit Periode' : 'Buat Periode Payroll' }}</h2>
                <form class="mt-5 space-y-4" @submit.prevent="savePeriod">
                    <div v-if="user.role === 'super_admin'">
                        <label class="label">Company</label>
                        <select class="input" v-model="periodForm.company_id">
                            <option value="">Pilih</option>
                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                {{ company.name }}
                            </option>
                        </select>
                        <FormError :message="periodForm.errors.company_id" />
                    </div>

                    <div>
                        <label class="label">Nama Periode</label>
                        <input class="input" v-model="periodForm.name" />
                        <FormError :message="periodForm.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Bulan Ke</label>
                            <select class="input" v-model="periodForm.period_month">
                                <option v-for="month in 12" :key="month" :value="month">
                                    {{ monthLabel(month) }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Tahun</label>
                            <input class="input" type="number" v-model="periodForm.period_year" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Mulai</label>
                            <input class="input" type="date" v-model="periodForm.start_date" />
                        </div>
                        <div>
                            <label class="label">Selesai</label>
                            <input class="input" type="date" v-model="periodForm.end_date" />
                        </div>
                    </div>

                    <div>
                        <label class="label">Status</label>
                        <select class="input" v-model="periodForm.status">
                            <option>open</option>
                            <option>generated</option>
                            <option>closed</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button class="btn-primary">Simpan</button>
                        <button class="btn-secondary" type="button" @click="resetPeriod">Reset</button>
                    </div>
                </form>
            </div>

            <div class="card overflow-hidden p-0 xl:col-span-2">
                <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                    <h2 class="font-bold">List Periode</h2>
                </div>
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="table-th">Periode</th>
                            <th class="table-th">Bulan</th>
                            <th class="table-th">Company</th>
                            <th class="table-th">Payroll</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="period in periods" :key="period.id">
                            <td class="table-td">
                                <b>{{ period.name }}</b><br>
                                <span class="text-xs">{{ period.start_date?.slice(0, 10) }} - {{ period.end_date?.slice(0, 10) }}</span>
                            </td>
                            <td class="table-td">{{ monthLabel(period.period_month) }}<br><span class="text-xs text-slate-500">{{ period.period_year }}</span></td>
                            <td class="table-td">{{ period.company?.name }}</td>
                            <td class="table-td">{{ period.payrolls_count }}</td>
                            <td class="table-td"><StatusBadge :value="period.status" /></td>
                            <td class="table-td">
                                <div class="flex flex-wrap gap-2">
                                    <button class="btn-secondary" @click="editPeriod(period)">Edit</button>
                                    <button class="btn-primary" @click="generate(period)">Generate</button>
                                    <button class="btn-danger" @click="destroyPeriod(period)">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-6 overflow-hidden p-0">
            <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                <h2 class="font-bold">List Payroll dan Slip Gaji</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="table-th">Karyawan</th>
                            <th class="table-th">Periode</th>
                            <th class="table-th">Bulan</th>
                            <th class="table-th">Gross</th>
                            <th class="table-th">Potongan</th>
                            <th class="table-th">Pajak</th>
                            <th class="table-th">Net</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payroll in payrolls" :key="payroll.id">
                            <td class="table-td">
                                {{ payroll.employee?.user?.name }}<br>
                                <span class="text-xs">{{ payroll.company?.name }}</span>
                            </td>
                            <td class="table-td">{{ payroll.period?.name }}</td>
                            <td class="table-td">{{ monthLabel(payroll.period?.period_month) }}<br><span class="text-xs text-slate-500">{{ payroll.period?.period_year }}</span></td>
                            <td class="table-td">{{ rupiah(payroll.gross_amount) }}</td>
                            <td class="table-td">{{ rupiah(payroll.deduction_amount) }}</td>
                            <td class="table-td">{{ rupiah(payroll.tax_amount) }}</td>
                            <td class="table-td font-bold">{{ rupiah(payroll.net_amount) }}</td>
                            <td class="table-td"><StatusBadge :value="payroll.status" /></td>
                            <td class="table-td">
                                <div class="flex flex-wrap gap-2">
                                    <Link class="btn-secondary" :href="`/payrolls/${payroll.id}/slip`">Slip</Link>
                                    <a class="btn-secondary" :href="`/payrolls/${payroll.id}/slip/download`">
                                        <Download class="mr-1 h-4 w-4" /> PDF
                                    </a>
                                    <button class="btn-secondary" @click="sendSlipEmail(payroll)">
                                        <Mail class="mr-1 h-4 w-4" /> Email
                                    </button>
                                    <button v-if="canManage" class="btn-primary" @click="paid(payroll)">Paid</button>
                                    <button v-if="canManage" class="btn-secondary" @click="failed(payroll)">Failed</button>
                                    <button v-if="canManage && payroll.status !== 'paid'" class="btn-danger" @click="destroyPayroll(payroll)">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="payrolls.length === 0">
                            <td colspan="9" class="table-td text-center text-slate-500">Belum ada data payroll.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
