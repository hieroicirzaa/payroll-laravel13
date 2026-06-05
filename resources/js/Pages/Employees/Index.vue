<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Download, FileDown, UploadCloud, XCircle } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormError from '@/Components/FormError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    employees: { type: Array, default: () => [] },
    companies: { type: Array, default: () => [] },
    salaryComponents: { type: Array, default: () => [] },
    importReport: { type: Object, default: null },
});

const user = usePage().props.auth.user;
const editId = ref(null);
const selectedEmployee = ref(null);
const fileInput = ref(null);
const dragged = ref(false);
const importFileName = ref('');

const canChooseCompany = computed(() => user.role === 'super_admin');

const rupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value || 0));

const form = useForm({
    company_id: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    employee_number: '',
    position: '',
    department: '',
    join_date: '',
    employment_status: 'permanent',
    basic_salary: 0,
    nik: '',
    npwp: '',
    bank_name: '',
    bank_account_number: '',
    bank_account_name: '',
    address: '',
    phone: '',
    status: 'active',
});

const importForm = useForm({
    file: null,
});

const compForm = useForm({
    salary_component_id: '',
    amount: 0,
    is_recurring: true,
    effective_from: '',
    effective_until: '',
});

function reset() {
    editId.value = null;
    form.reset();
    form.clearErrors();
    form.employment_status = 'permanent';
    form.status = 'active';
}

function edit(employee) {
    editId.value = employee.id;
    form.company_id = employee.company_id;
    form.name = employee.user?.name || '';
    form.email = employee.user?.email || '';
    form.password = '';
    form.password_confirmation = '';
    form.employee_number = employee.employee_number;
    form.position = employee.position;
    form.department = employee.department || '';
    form.join_date = (employee.join_date || '').slice(0, 10);
    form.employment_status = employee.employment_status;
    form.basic_salary = employee.basic_salary;
    form.nik = employee.nik || '';
    form.npwp = employee.npwp || '';
    form.bank_name = employee.bank_name || '';
    form.bank_account_number = employee.bank_account_number || '';
    form.bank_account_name = employee.bank_account_name || '';
    form.address = employee.address || '';
    form.phone = employee.phone || '';
    form.status = employee.status;
}

function submit() {
    if (editId.value) {
        form.put(`/employees/${editId.value}`, { preserveScroll: true, onSuccess: reset });
        return;
    }

    form.post('/employees', { preserveScroll: true, onSuccess: reset });
}

function deactivate(employee) {
    if (confirm(`Nonaktifkan ${employee.user?.name}?`)) {
        router.delete(`/employees/${employee.id}`, { preserveScroll: true });
    }
}

function restore(employee) {
    router.patch(`/employees/${employee.id}/restore`, {}, { preserveScroll: true });
}

function selectForComponent(employee) {
    selectedEmployee.value = employee;
    compForm.reset();
    compForm.clearErrors();
}

function assignComponent() {
    if (! selectedEmployee.value) {
        return;
    }

    compForm.post(`/employees/${selectedEmployee.value.id}/salary-components`, {
        preserveScroll: true,
        onSuccess: () => compForm.reset(),
    });
}

function removeComponent(component) {
    router.delete(`/employee-salary-components/${component.id}`, { preserveScroll: true });
}

function setImportFile(file) {
    importForm.file = file;
    importFileName.value = file?.name || '';
    importForm.clearErrors('file');
}

function onFileChange(event) {
    setImportFile(event.target.files?.[0] || null);
}

function onDrop(event) {
    dragged.value = false;
    setImportFile(event.dataTransfer.files?.[0] || null);
}

function clearImportFile() {
    importForm.file = null;
    importFileName.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submitImport() {
    importForm.post('/employees-import', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: clearImportFile,
    });
}
</script>

<template>
    <AppLayout title="Karyawan">
        <div class="grid gap-6 xl:grid-cols-3">
            <section class="card xl:col-span-1">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">{{ editId ? 'Edit Karyawan' : 'Tambah Karyawan' }}</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Input manual untuk satu karyawan.</p>
                    </div>
                    <button v-if="editId" type="button" class="btn-secondary" @click="reset">Batal</button>
                </div>

                <form class="mt-5 space-y-3" @submit.prevent="submit">
                    <div v-if="canChooseCompany">
                        <label class="label">Company</label>
                        <select v-model="form.company_id" class="input">
                            <option value="">Pilih company</option>
                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                {{ company.name }} · {{ company.code }}
                            </option>
                        </select>
                        <FormError :message="form.errors.company_id" />
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label">Nama</label>
                            <input v-model="form.name" class="input" />
                            <FormError :message="form.errors.name" />
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input v-model="form.email" class="input" type="email" />
                            <FormError :message="form.errors.email" />
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label">Password {{ editId ? '(opsional)' : '' }}</label>
                            <input v-model="form.password" class="input" type="password" />
                            <FormError :message="form.errors.password" />
                        </div>
                        <div>
                            <label class="label">Konfirmasi Password</label>
                            <input v-model="form.password_confirmation" class="input" type="password" />
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label">Nomor Karyawan</label>
                            <input v-model="form.employee_number" class="input" />
                            <FormError :message="form.errors.employee_number" />
                        </div>
                        <div>
                            <label class="label">Tanggal Masuk</label>
                            <input v-model="form.join_date" class="input" type="date" />
                            <FormError :message="form.errors.join_date" />
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label">Jabatan</label>
                            <input v-model="form.position" class="input" />
                            <FormError :message="form.errors.position" />
                        </div>
                        <div>
                            <label class="label">Departemen</label>
                            <input v-model="form.department" class="input" />
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label">Status Kepegawaian</label>
                            <select v-model="form.employment_status" class="input">
                                <option value="permanent">Permanent</option>
                                <option value="contract">Contract</option>
                                <option value="probation">Probation</option>
                                <option value="intern">Intern</option>
                            </select>
                            <FormError :message="form.errors.employment_status" />
                        </div>
                        <div>
                            <label class="label">Gaji Pokok</label>
                            <input v-model="form.basic_salary" class="input" type="number" min="0" />
                            <FormError :message="form.errors.basic_salary" />
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label">NIK</label>
                            <input v-model="form.nik" class="input" />
                        </div>
                        <div>
                            <label class="label">NPWP</label>
                            <input v-model="form.npwp" class="input" />
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label">Bank</label>
                            <input v-model="form.bank_name" class="input" />
                        </div>
                        <div>
                            <label class="label">Nomor Rekening</label>
                            <input v-model="form.bank_account_number" class="input" />
                        </div>
                    </div>

                    <div>
                        <label class="label">Nama Rekening</label>
                        <input v-model="form.bank_account_name" class="input" />
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="label">Telepon</label>
                            <input v-model="form.phone" class="input" />
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select v-model="form.status" class="input">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <FormError :message="form.errors.status" />
                        </div>
                    </div>

                    <div>
                        <label class="label">Alamat</label>
                        <textarea v-model="form.address" class="input" rows="3" />
                    </div>

                    <button class="btn-primary w-full" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : (editId ? 'Update Karyawan' : 'Simpan Karyawan') }}
                    </button>
                </form>
            </section>

            <div class="space-y-6 xl:col-span-2">
                <section class="card">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-bold">Import dan Export Karyawan</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Download template untuk import massal, atau export seluruh data karyawan sesuai scope role login.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="/employees-import/template" class="btn-secondary">
                                <Download class="mr-2 h-4 w-4" /> Template Import
                            </a>
                            <a href="/employees-export" class="btn-primary">
                                <FileDown class="mr-2 h-4 w-4" /> Export Karyawan
                            </a>
                        </div>
                    </div>

                    <form class="mt-5" @submit.prevent="submitImport">
                        <label
                            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed p-8 text-center transition dark:border-slate-700"
                            :class="dragged ? 'border-teal-500 bg-teal-50 dark:bg-teal-950/40' : 'border-slate-300 hover:border-teal-500 hover:bg-slate-50 dark:hover:bg-slate-800'"
                            @dragover.prevent="dragged = true"
                            @dragleave.prevent="dragged = false"
                            @drop.prevent="onDrop"
                        >
                            <UploadCloud class="h-10 w-10 text-teal-700 dark:text-teal-300" />
                            <span class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                Drag file Excel ke sini atau klik untuk memilih file
                            </span>
                            <span class="mt-1 text-xs text-slate-500 dark:text-slate-400">Hanya .xlsx, maksimal 20 MB. Sistem membaca data per 500 baris.</span>
                            <input ref="fileInput" type="file" accept=".xlsx" class="hidden" @change="onFileChange" />
                        </label>

                        <div v-if="importFileName" class="mt-3 flex items-center justify-between gap-3 rounded-xl bg-slate-100 px-4 py-3 text-sm dark:bg-slate-800">
                            <span class="truncate font-medium">{{ importFileName }}</span>
                            <button type="button" class="text-rose-600 hover:text-rose-700" @click="clearImportFile">
                                <XCircle class="h-5 w-5" />
                            </button>
                        </div>

                        <FormError :message="importForm.errors.file" class="mt-2" />

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button class="btn-primary" :disabled="importForm.processing || !importForm.file">
                                {{ importForm.processing ? 'Mengimpor...' : 'Import Data Karyawan' }}
                            </button>
                            <p class="max-w-2xl text-xs text-slate-500 dark:text-slate-400">
                                Jika header wajib hilang, file langsung ditolak. Jika baris tertentu salah, baris tersebut ditolak dan laporan error ditampilkan di bawah.
                            </p>
                        </div>
                    </form>

                    <div v-if="importReport" class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/40">
                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="rounded-xl bg-white p-4 dark:bg-slate-900">
                                <div class="text-xs text-slate-500">Diproses</div>
                                <div class="mt-1 text-2xl font-bold">{{ importReport.processed }}</div>
                            </div>
                            <div class="rounded-xl bg-white p-4 dark:bg-slate-900">
                                <div class="text-xs text-slate-500">Berhasil</div>
                                <div class="mt-1 text-2xl font-bold text-teal-700 dark:text-teal-300">{{ importReport.inserted }}</div>
                            </div>
                            <div class="rounded-xl bg-white p-4 dark:bg-slate-900">
                                <div class="text-xs text-slate-500">Ditolak</div>
                                <div class="mt-1 text-2xl font-bold text-rose-700 dark:text-rose-300">{{ importReport.failed }}</div>
                            </div>
                        </div>

                        <div v-if="importReport.failures?.length" class="mt-4 overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr>
                                        <th class="table-th">Baris</th>
                                        <th class="table-th">Nomor Karyawan</th>
                                        <th class="table-th">Email</th>
                                        <th class="table-th">Error</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="failure in importReport.failures" :key="failure.row">
                                        <td class="table-td">{{ failure.row }}</td>
                                        <td class="table-td">{{ failure.employee_number || '-' }}</td>
                                        <td class="table-td">{{ failure.email || '-' }}</td>
                                        <td class="table-td">
                                            <ul class="list-disc pl-4">
                                                <li v-for="error in failure.errors" :key="error">{{ error }}</li>
                                            </ul>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Maksimal 100 error pertama ditampilkan agar halaman tetap ringan.</p>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-bold">List Karyawan</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kelola karyawan sesuai scope role login.</p>
                        </div>
                        <div class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold dark:bg-slate-800">
                            Total: {{ employees.length }}
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="table-th">Karyawan</th>
                                    <th class="table-th">Company</th>
                                    <th class="table-th">Jabatan</th>
                                    <th class="table-th">Gaji Pokok</th>
                                    <th class="table-th">Status</th>
                                    <th class="table-th">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="employee in employees" :key="employee.id">
                                    <td class="table-td">
                                        <div class="font-semibold text-slate-900 dark:text-slate-100">{{ employee.user?.name }}</div>
                                        <div class="text-xs text-slate-500">{{ employee.employee_number }} · {{ employee.user?.email }}</div>
                                    </td>
                                    <td class="table-td">{{ employee.company?.name || '-' }}</td>
                                    <td class="table-td">
                                        <div>{{ employee.position }}</div>
                                        <div class="text-xs text-slate-500">{{ employee.department || '-' }}</div>
                                    </td>
                                    <td class="table-td">{{ rupiah(employee.basic_salary) }}</td>
                                    <td class="table-td"><StatusBadge :value="employee.status" /></td>
                                    <td class="table-td">
                                        <div class="flex flex-wrap gap-2">
                                            <button class="btn-secondary" @click="edit(employee)">Edit</button>
                                            <button class="btn-secondary" @click="selectForComponent(employee)">Komponen</button>
                                            <button v-if="employee.status === 'active'" class="btn-danger" @click="deactivate(employee)">Nonaktifkan</button>
                                            <button v-else class="btn-primary" @click="restore(employee)">Aktifkan</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="employees.length === 0">
                                    <td colspan="6" class="table-td text-center text-slate-500">Belum ada data karyawan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section v-if="selectedEmployee" class="card">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold">Komponen Gaji: {{ selectedEmployee.user?.name }}</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tambahkan allowance, deduction, bonus, atau komponen lain.</p>
                        </div>
                        <button type="button" class="btn-secondary" @click="selectedEmployee = null">Tutup</button>
                    </div>

                    <form class="mt-5 grid gap-3 lg:grid-cols-5" @submit.prevent="assignComponent">
                        <div class="lg:col-span-2">
                            <label class="label">Komponen</label>
                            <select v-model="compForm.salary_component_id" class="input">
                                <option value="">Pilih komponen</option>
                                <option v-for="component in salaryComponents" :key="component.id" :value="component.id">
                                    {{ component.name }} · {{ component.type }}
                                </option>
                            </select>
                            <FormError :message="compForm.errors.salary_component_id" />
                        </div>
                        <div>
                            <label class="label">Nominal</label>
                            <input v-model="compForm.amount" class="input" type="number" min="0" />
                            <FormError :message="compForm.errors.amount" />
                        </div>
                        <div>
                            <label class="label">Mulai Berlaku</label>
                            <input v-model="compForm.effective_from" class="input" type="date" />
                        </div>
                        <div class="flex items-end">
                            <button class="btn-primary w-full" :disabled="compForm.processing">Simpan</button>
                        </div>
                    </form>

                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="table-th">Komponen</th>
                                    <th class="table-th">Nominal</th>
                                    <th class="table-th">Tipe</th>
                                    <th class="table-th">Recurring</th>
                                    <th class="table-th">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="component in selectedEmployee.salary_components" :key="component.id">
                                    <td class="table-td">{{ component.component?.name }}</td>
                                    <td class="table-td">{{ rupiah(component.amount) }}</td>
                                    <td class="table-td">{{ component.component?.type }}</td>
                                    <td class="table-td">{{ component.is_recurring ? 'Ya' : 'Tidak' }}</td>
                                    <td class="table-td"><button class="btn-danger" @click="removeComponent(component)">Hapus</button></td>
                                </tr>
                                <tr v-if="selectedEmployee.salary_components?.length === 0">
                                    <td colspan="5" class="table-td text-center text-slate-500">Belum ada komponen gaji.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
