<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import FormError from '@/Components/FormError.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
const props = defineProps({ token: String, email: String });
const form = useForm({ token: props.token, email: props.email || '', password: '', password_confirmation: '' });
function submit() { form.post('/reset-password'); }
</script>
<template>
    <Head title="Reset Password" />
    <div class="relative flex min-h-screen items-center justify-center bg-slate-100 px-4 transition-colors duration-200 dark:bg-slate-950">
        <div class="absolute right-4 top-4">
            <ThemeToggle />
        </div>
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
            <h1 class="text-2xl font-bold">Buat Password Baru</h1>
            <form class="mt-5 space-y-4" @submit.prevent="submit">
                <div><label class="label">Email</label><input v-model="form.email" class="input" type="email"><FormError :message="form.errors.email" /></div>
                <div><label class="label">Password Baru</label><input v-model="form.password" class="input" type="password"><FormError :message="form.errors.password" /></div>
                <div><label class="label">Konfirmasi Password</label><input v-model="form.password_confirmation" class="input" type="password"><FormError :message="form.errors.password_confirmation" /></div>
                <button class="btn-primary w-full" :disabled="form.processing">Reset Password</button>
            </form>
        </div>
    </div>
</template>
