<script setup>
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
const user = usePage().props.auth.user;
</script>
<template>
    <AppLayout title="Profil Saya">
        <div class="card max-w-2xl">
            <h2 class="text-lg font-bold">Data Akun</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div><p class="text-xs uppercase text-slate-500">Nama</p><p class="font-semibold">{{ user.name }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">Email</p><p class="font-semibold">{{ user.email }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">Role</p><p class="font-semibold">{{ user.role_label }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">Company</p><p class="font-semibold">{{ user.company?.name || '-' }}</p></div>
            </div>
        </div>
    </AppLayout>
</template>
