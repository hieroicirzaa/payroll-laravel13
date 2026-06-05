<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Download, Eye, Mail } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormError from '@/Components/FormError.vue';

const props = defineProps({
    documents: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
});

const user = usePage().props.auth.user;
const form = useForm({ employee_id: '', type: 'ktp', title: '', file: null });
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

const size = (b) => `${(Number(b || 0) / 1024).toFixed(1)} KB`;

function monthLabel(dateValue) {
    if (!dateValue) return '-';
    const date = new Date(dateValue);
    if (Number.isNaN(date.getTime())) return '-';
    const month = date.getMonth() + 1;
    return `Bulan ke-${month} (${monthNames[month]}) ${date.getFullYear()}`;
}

function submit() {
    form.post('/documents', { forceFormData: true, onSuccess: () => form.reset() });
}

function sendEmail(document) {
    const email = document.employee?.user?.email || 'email karyawan';
    if (confirm(`Kirim dokumen ${document.title} ke ${email}?`)) {
        router.post(`/documents/${document.id}/email`, {}, { preserveScroll: true });
    }
}

function destroy(document) {
    if (confirm(`Hapus dokumen ${document.title}?`)) {
        router.delete(`/documents/${document.id}`);
    }
}
</script>

<template>
    <AppLayout title="Dokumen Karyawan">
        <div class="grid gap-6 xl:grid-cols-3">
            <div class="card">
                <h2 class="font-bold">Upload Dokumen</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Dokumen disimpan di storage private. Karyawan hanya bisa melihat dokumen miliknya sendiri.
                </p>

                <form class="mt-5 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="label">Karyawan</label>
                        <select class="input" v-model="form.employee_id">
                            <option value="">Pilih</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                {{ employee.employee_number }} - {{ employee.user?.name }}
                            </option>
                        </select>
                        <FormError :message="form.errors.employee_id" />
                    </div>

                    <div>
                        <label class="label">Jenis</label>
                        <select class="input" v-model="form.type">
                            <option v-for="type in types" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Judul</label>
                        <input class="input" v-model="form.title" />
                        <FormError :message="form.errors.title" />
                    </div>

                    <div>
                        <label class="label">File</label>
                        <input class="input" type="file" @input="form.file = $event.target.files[0]" />
                        <FormError :message="form.errors.file" />
                    </div>

                    <button class="btn-primary" :disabled="form.processing">Upload</button>
                </form>
            </div>

            <div class="card overflow-hidden p-0 xl:col-span-2">
                <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                    <h2 class="font-bold">List Dokumen</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Role karyawan dapat melihat detail, download, dan mengirim dokumen miliknya ke email.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="table-th">Dokumen</th>
                                <th class="table-th">Karyawan</th>
                                <th class="table-th">Bulan</th>
                                <th class="table-th">File</th>
                                <th class="table-th">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="document in documents" :key="document.id">
                                <td class="table-td">
                                    <b>{{ document.title }}</b><br>
                                    <span class="text-xs uppercase text-slate-500">{{ document.type }}</span>
                                </td>
                                <td class="table-td">
                                    {{ document.employee?.user?.name }}<br>
                                    <span class="text-xs text-slate-500">{{ document.company?.name }}</span>
                                </td>
                                <td class="table-td">
                                    {{ monthLabel(document.created_at) }}<br>
                                    <span class="text-xs text-slate-500">Tanggal unggah: {{ document.created_at?.slice(0, 10) }}</span>
                                </td>
                                <td class="table-td">
                                    {{ document.original_name }}<br>
                                    <span class="text-xs text-slate-500">{{ size(document.size_bytes) }}</span>
                                </td>
                                <td class="table-td">
                                    <div class="flex flex-wrap gap-2">
                                        <Link class="btn-secondary" :href="`/documents/${document.id}`">
                                            <Eye class="mr-1 h-4 w-4" /> Detail
                                        </Link>
                                        <a class="btn-secondary" :href="`/documents/${document.id}/download`">
                                            <Download class="mr-1 h-4 w-4" /> Download
                                        </a>
                                        <button class="btn-secondary" type="button" @click="sendEmail(document)">
                                            <Mail class="mr-1 h-4 w-4" /> Email
                                        </button>
                                        <button v-if="user.role !== 'employee'" class="btn-danger" type="button" @click="destroy(document)">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="documents.length === 0">
                                <td class="table-td text-center text-slate-500" colspan="5">Belum ada dokumen.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
