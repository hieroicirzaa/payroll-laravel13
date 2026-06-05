<script setup>
import { computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
const props = defineProps({ reports: Array, companies: Array, employees: Array });
const user = usePage().props.auth.user; const canManage = computed(()=>['super_admin','admin_company'].includes(user.role));
const form = useForm({ company_id:'', employee_id:'', year:new Date().getFullYear(), status:'draft' });
const rupiah = (v) => new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', maximumFractionDigits:0 }).format(Number(v||0));
function submit(){ form.post('/spt-reports', { onSuccess:()=>form.reset('employee_id') }); }
function setStatus(r, status){ router.patch(`/spt-reports/${r.id}`, { status }); }
function destroy(r){ if(confirm('Hapus laporan SPT?')) router.delete(`/spt-reports/${r.id}`); }
</script>
<template><AppLayout title="SPT / Pajak">
    <div v-if="canManage" class="card mb-6"><h2 class="font-bold">Generate Laporan SPT dari Payroll Paid</h2><form class="mt-4 grid gap-3 md:grid-cols-5" @submit.prevent="submit"><select v-if="user.role==='super_admin'" class="input" v-model="form.company_id"><option value="">Company</option><option v-for="c in companies" :value="c.id">{{ c.name }}</option></select><select class="input" v-model="form.employee_id"><option value="">Semua karyawan</option><option v-for="e in employees" :value="e.id">{{ e.employee_number }} - {{ e.user?.name }}</option></select><input class="input" type="number" v-model="form.year"><select class="input" v-model="form.status"><option>draft</option><option>final</option></select><button class="btn-primary">Generate</button></form></div>
    <div class="card overflow-hidden p-0"><div class="border-b p-5"><h2 class="font-bold">List Laporan SPT</h2></div><div class="overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">Karyawan</th><th class="table-th">Company</th><th class="table-th">Tahun</th><th class="table-th">Gross</th><th class="table-th">Pajak</th><th class="table-th">Status</th><th class="table-th">Aksi</th></tr></thead><tbody>
        <tr v-for="r in reports" :key="r.id"><td class="table-td">{{ r.employee?.user?.name }}</td><td class="table-td">{{ r.company?.name }}</td><td class="table-td">{{ r.year }}</td><td class="table-td">{{ rupiah(r.total_gross_amount) }}</td><td class="table-td">{{ rupiah(r.total_tax_amount) }}</td><td class="table-td"><StatusBadge :value="r.status" /></td><td class="table-td"><div v-if="canManage" class="flex gap-2"><button class="btn-secondary" @click="setStatus(r, 'final')">Final</button><button class="btn-secondary" @click="setStatus(r, 'submitted')">Submitted</button><button class="btn-danger" @click="destroy(r)">Hapus</button></div></td></tr>
    </tbody></table></div></div>
</AppLayout></template>
