<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import FormError from '@/Components/FormError.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
const form = useForm({ email: '' });
function submit() { form.post('/forgot-password'); }
</script>
<template>
    <Head title="Forgot Password" />
    <div class="relative flex min-h-screen items-center justify-center bg-slate-100 px-4 transition-colors duration-200 dark:bg-slate-950">
        <div class="absolute right-4 top-4">
            <ThemeToggle />
        </div>
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
            <h1 class="text-2xl font-bold">Reset Password</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Masukkan email akun payroll Anda.</p>
            <FlashMessage class="mt-4" />
            <form class="mt-5 space-y-4" @submit.prevent="submit">
                <div><label class="label">Email</label><input v-model="form.email" class="input" type="email"><FormError :message="form.errors.email" /></div>
                <button class="btn-primary w-full" :disabled="form.processing">Kirim Link Reset</button>
            </form>
            <Link href="/login" class="mt-5 inline-block text-sm font-semibold text-teal-700">Kembali login</Link>
        </div>
    </div>
</template>
