<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormError from '@/Components/FormError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
const props = defineProps({ users: Array, companies: Array, roles: Array });
const editId = ref(null);
const form = useForm({ name:'', email:'', role:'admin_company', company_id:'', password:'', password_confirmation:'', is_active:true });
function reset(){ editId.value=null; form.reset(); form.clearErrors(); form.role='admin_company'; form.is_active=true; }
function edit(u){ editId.value=u.id; form.name=u.name; form.email=u.email; form.role=u.role; form.company_id=u.company_id || ''; form.password=''; form.password_confirmation=''; form.is_active=!!u.is_active; }
function submit(){ editId.value ? form.put(`/users/${editId.value}`, { onSuccess: reset }) : form.post('/users', { onSuccess: reset }); }
function deactivate(u){ if(confirm(`Nonaktifkan ${u.name}?`)) router.delete(`/users/${u.id}`); }
function restore(u){ router.patch(`/users/${u.id}/restore`); }
</script>
<template><AppLayout title="User Admin">
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card"><h2 class="text-lg font-bold">{{ editId ? 'Edit User' : 'Tambah User' }}</h2>
            <form class="mt-5 space-y-4" @submit.prevent="submit">
                <div><label class="label">Nama</label><input class="input" v-model="form.name"><FormError :message="form.errors.name" /></div>
                <div><label class="label">Email</label><input class="input" type="email" v-model="form.email"><FormError :message="form.errors.email" /></div>
                <div><label class="label">Role</label><select class="input" v-model="form.role"><option v-for="r in roles" :value="r.value">{{ r.label }}</option></select><FormError :message="form.errors.role" /></div>
                <div v-if="form.role !== 'super_admin'"><label class="label">Company</label><select class="input" v-model="form.company_id"><option value="">Pilih company</option><option v-for="c in companies" :value="c.id">{{ c.name }}</option></select><FormError :message="form.errors.company_id" /></div>
                <div><label class="label">Password {{ editId ? '(kosongkan jika tidak diganti)' : '' }}</label><input class="input" type="password" v-model="form.password"><FormError :message="form.errors.password" /></div>
                <div><label class="label">Konfirmasi Password</label><input class="input" type="password" v-model="form.password_confirmation"><FormError :message="form.errors.password_confirmation" /></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active"> Aktif</label>
                <div class="flex gap-2"><button class="btn-primary">Simpan</button><button type="button" class="btn-secondary" @click="reset">Reset</button></div>
            </form>
        </div>
        <div class="card overflow-hidden p-0 xl:col-span-2"><div class="border-b p-5"><h2 class="font-bold">List Super Admin, Admin Company, dan Karyawan</h2></div><div class="overflow-x-auto"><table class="w-full"><thead><tr><th class="table-th">User</th><th class="table-th">Role</th><th class="table-th">Company</th><th class="table-th">Status</th><th class="table-th">Aksi</th></tr></thead><tbody>
            <tr v-for="u in users" :key="u.id"><td class="table-td"><b>{{ u.name }}</b><br><span class="text-xs">{{ u.email }}</span></td><td class="table-td">{{ u.role }}</td><td class="table-td">{{ u.company?.name || '-' }}</td><td class="table-td"><StatusBadge :value="u.is_active" /></td><td class="table-td"><div class="flex gap-2"><button class="btn-secondary" @click="edit(u)">Edit</button><button v-if="u.is_active" class="btn-danger" @click="deactivate(u)">Nonaktifkan</button><button v-else class="btn-primary" @click="restore(u)">Aktifkan</button></div></td></tr>
        </tbody></table></div></div>
    </div>
</AppLayout></template>
