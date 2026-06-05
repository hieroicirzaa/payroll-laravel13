<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Download, Mail } from 'lucide-vue-next';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    document: { type: Object, required: true },
    canPreview: { type: Boolean, default: false },
});

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

const rupiahSize = (bytes) => `${(Number(bytes || 0) / 1024).toFixed(1)} KB`;

function monthLabel(dateValue) {
    if (!dateValue) return '-';
    const date = new Date(dateValue);
    if (Number.isNaN(date.getTime())) return '-';
    const month = date.getMonth() + 1;
    return `Bulan ke-${month} (${monthNames[month]}) ${date.getFullYear()}`;
}

function sendEmail() {
    const email = props.document.employee?.user?.email || 'email karyawan';
    if (confirm(`Kirim dokumen ${props.document.title} ke ${email}?`)) {
        router.post(`/documents/${props.document.id}/email`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout :title="`Detail Dokumen - ${document.title}`">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <Link class="btn-secondary" href="/documents">
                <ArrowLeft class="mr-1 h-4 w-4" /> Kembali
            </Link>
            <div class="flex flex-wrap gap-2">
                <a class="btn-secondary" :href="`/documents/${document.id}/download`">
                    <Download class="mr-1 h-4 w-4" /> Download
                </a>
                <button class="btn-primary" type="button" @click="sendEmail">
                    <Mail class="mr-1 h-4 w-4" /> Kirim ke Email
                </button>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="card xl:col-span-1">
                <h2 class="text-lg font-bold text-slate-950 dark:text-slate-100">Informasi Dokumen</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Judul</dt>
                        <dd class="font-semibold text-slate-950 dark:text-slate-100">{{ document.title }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Jenis</dt>
                        <dd class="font-semibold uppercase text-slate-950 dark:text-slate-100">{{ document.type }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Bulan Upload</dt>
                        <dd class="font-semibold text-slate-950 dark:text-slate-100">{{ monthLabel(document.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Tanggal Upload</dt>
                        <dd class="font-semibold text-slate-950 dark:text-slate-100">{{ document.created_at?.slice(0, 10) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Nama File</dt>
                        <dd class="font-semibold text-slate-950 dark:text-slate-100">{{ document.original_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Ukuran</dt>
                        <dd class="font-semibold text-slate-950 dark:text-slate-100">{{ rupiahSize(document.size_bytes) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">MIME</dt>
                        <dd class="font-semibold text-slate-950 dark:text-slate-100">{{ document.mime_type || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Checksum SHA-256</dt>
                        <dd class="break-all text-xs font-mono text-slate-700 dark:text-slate-300">{{ document.checksum }}</dd>
                    </div>
                </dl>
            </div>

            <div class="card xl:col-span-2">
                <h2 class="text-lg font-bold text-slate-950 dark:text-slate-100">Detail Karyawan</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Nama</p>
                        <p class="font-bold text-slate-950 dark:text-slate-100">{{ document.employee?.user?.name }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Email Tujuan</p>
                        <p class="font-bold text-slate-950 dark:text-slate-100">{{ document.employee?.user?.email }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Nomor Karyawan</p>
                        <p class="font-bold text-slate-950 dark:text-slate-100">{{ document.employee?.employee_number }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Company</p>
                        <p class="font-bold text-slate-950 dark:text-slate-100">{{ document.company?.name }}</p>
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-100 pt-5 dark:border-slate-800">
                    <h3 class="font-bold text-slate-950 dark:text-slate-100">Preview Dokumen</h3>
                    <div v-if="canPreview" class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                        <iframe
                            class="h-[640px] w-full"
                            :src="`/documents/${document.id}/preview`"
                            title="Preview dokumen"
                        />
                    </div>
                    <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-300 p-6 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        File ini tidak bisa ditampilkan langsung di browser. Silakan gunakan tombol download untuk membuka file di aplikasi yang sesuai.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
