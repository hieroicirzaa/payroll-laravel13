<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormError from '@/Components/FormError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
const props = defineProps({ components: Array, types: Array });
const editId = ref(null);
const form = useForm({ name:'', code:'', type:'earning', is_taxable:true, is_active:true });
function reset(){ editId.value=null; form.reset(); form.clearErrors(); form.type='earning'; form.is_taxable=true; form.is_active=true; }
function edit(c){ editId.value=c.id; form.name=c.name; form.code=c.code; form.type=c.type; form.is_taxable=!!c.is_taxable; form.is_active=!!c.is_active; }
function submit(){ editId.value ? form.put(`/salary-components/${editId.value}`, { onSuccess: reset }) : form.post('/salary-components', { onSuccess: reset }); }
function deactivate(c){ router.delete(`/salary-components/${c.id}`); }
function restore(c){ router.patch(`/salary-components/${c.id}/restore`); }
</script>
<template><AppLayout title="Komponen Gaji">
    <div class="grid gap-6 xl:grid-cols-3"><div class="card"><h2 class="font-bold">{{ editId ? 'Edit Komponen' : 'Tambah Komponen' }}</h2><form class="mt-5 space-y-4" @submit.prevent="submit">
        <div><label class="label">Nama</label><input class="input" v-model="form.name"><FormError :message="form.errors.name" /></div>
        <div><label class="label">Kode</label><input class="input" v-model="form.code"><FormError :message="form.errors.code" /></div>
        <div><label class="label">Tipe</label><select class="input" v-model="form.type"><option v-for="t in types" :value="t.value">{{ t.label }}</option></select></div>
        <label class="flex gap-2 text-sm"><input type="checkbox" v-model="form.is_taxable"> Kena pajak</label><label class="flex gap-2 text-sm"><input type="checkbox" v-model="form.is_active"> Aktif</label>
        <div class="flex gap-2"><button class="btn-primary">Simpan</button><button type="button" class="btn-secondary" @click="reset">Reset</button></div>
    </form></div><div class="card overflow-hidden p-0 xl:col-span-2"><div class="border-b p-5"><h2 class="font-bold">List Komponen</h2></div><table class="w-full"><thead><tr><th class="table-th">Komponen</th><th class="table-th">Tipe</th><th class="table-th">Pajak</th><th class="table-th">Status</th><th class="table-th">Aksi</th></tr></thead><tbody>
        <tr v-for="c in components" :key="c.id"><td class="table-td"><b>{{ c.name }}</b><br><span class="text-xs">{{ c.code }}</span></td><td class="table-td">{{ c.type }}</td><td class="table-td">{{ c.is_taxable ? 'Ya' : 'Tidak' }}</td><td class="table-td"><StatusBadge :value="c.is_active" /></td><td class="table-td"><div class="flex gap-2"><button class="btn-secondary" @click="edit(c)">Edit</button><button v-if="c.is_active" class="btn-danger" @click="deactivate(c)">Nonaktifkan</button><button v-else class="btn-primary" @click="restore(c)">Aktifkan</button></div></td></tr>
    </tbody></table></div></div>
</AppLayout></template>
