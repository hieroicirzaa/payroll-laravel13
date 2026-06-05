<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormError from '@/Components/FormError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({ companies: Array });
const editId = ref(null);
const form = useForm({ name: '', code: '', email: '', phone: '', address: '', tax_number: '', is_active: true });
function reset() { editId.value = null; form.reset(); form.clearErrors(); form.is_active = true; }
function edit(c) { editId.value = c.id; form.name=c.name; form.code=c.code; form.email=c.email||''; form.phone=c.phone||''; form.address=c.address||''; form.tax_number=c.tax_number||''; form.is_active=!!c.is_active; }
function submit() { editId.value ? form.put(`/companies/${editId.value}`, { onSuccess: reset }) : form.post('/companies', { onSuccess: reset }); }
function deactivate(c) { if (confirm(`Nonaktifkan ${c.name}?`)) router.delete(`/companies/${c.id}`); }
function restore(c) { router.patch(`/companies/${c.id}/restore`); }
</script>
<template>
<AppLayout title="Company">
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-1">
            <h2 class="text-lg font-bold">{{ editId ? 'Edit Company' : 'Tambah Company' }}</h2>
            <form class="mt-5 space-y-4" @submit.prevent="submit">
                <div><label class="label">Nama</label><input v-model="form.name" class="input"><FormError :message="form.errors.name" /></div>
                <div><label class="label">Kode</label><input v-model="form.code" class="input"><FormError :message="form.errors.code" /></div>
                <div><label class="label">Email</label><input v-model="form.email" class="input"><FormError :message="form.errors.email" /></div>
                <div><label class="label">Telepon</label><input v-model="form.phone" class="input"><FormError :message="form.errors.phone" /></div>
                <div><label class="label">NPWP Perusahaan</label><input v-model="form.tax_number" class="input"><FormError :message="form.errors.tax_number" /></div>
                <div><label class="label">Alamat</label><textarea v-model="form.address" class="input" rows="3" /><FormError :message="form.errors.address" /></div>
                <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox"> Aktif</label>
                <div class="flex gap-2"><button class="btn-primary" :disabled="form.processing">Simpan</button><button type="button" class="btn-secondary" @click="reset">Reset</button></div>
            </form>
        </div>
        <div class="card overflow-hidden p-0 xl:col-span-2">
            <div class="border-b border-slate-100 p-5 dark:border-slate-800"><h2 class="font-bold">List Company</h2></div>
            <div class="overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">Company</th><th class="table-th">Kontak</th><th class="table-th">User/Karyawan</th><th class="table-th">Status</th><th class="table-th">Aksi</th></tr></thead><tbody>
                <tr v-for="c in companies" :key="c.id"><td class="table-td"><b>{{ c.name }}</b><br><span class="text-xs text-slate-500">{{ c.code }}</span></td><td class="table-td">{{ c.email || '-' }}<br>{{ c.phone || '-' }}</td><td class="table-td">{{ c.users_count }} / {{ c.employees_count }}</td><td class="table-td"><StatusBadge :value="c.is_active" /></td><td class="table-td"><div class="flex gap-2"><button class="btn-secondary" @click="edit(c)">Edit</button><button v-if="c.is_active" class="btn-danger" @click="deactivate(c)">Nonaktifkan</button><button v-else class="btn-primary" @click="restore(c)">Aktifkan</button></div></td></tr>
            </tbody></table></div>
        </div>
    </div>
</AppLayout>
</template>
