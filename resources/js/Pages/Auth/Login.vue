<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import FormError from '@/Components/FormError.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const form = useForm({ email: '', password: '', remember: false });
function submit() { form.post('/login'); }
</script>

<template>
    <Head title="Login" />
    <div class="relative flex min-h-screen items-center justify-center bg-slate-100 px-4 transition-colors duration-200 dark:bg-slate-950">
        <div class="absolute right-4 top-4">
            <ThemeToggle />
        </div>
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-950 dark:text-slate-100">Payroll Secure</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Masuk menggunakan akun payroll Anda.</p>
            </div>
            <FlashMessage class="mb-4" />
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="label">Email</label>
                    <input v-model="form.email" class="input" type="email" autocomplete="username">
                    <FormError :message="form.errors.email" />
                </div>
                <div>
                    <label class="label">Password</label>
                    <input v-model="form.password" class="input" type="password" autocomplete="current-password">
                    <FormError :message="form.errors.password" />
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-teal-700">
                    Ingat saya
                </label>
                <button class="btn-primary w-full" :disabled="form.processing">Login</button>
            </form>
            <div class="mt-5 text-center text-sm">
                <Link href="/forgot-password" class="font-semibold text-teal-700 hover:text-teal-900 dark:text-teal-300 dark:hover:text-teal-200">Lupa password?</Link>
            </div>
            <div class="mt-6 rounded-xl bg-slate-50 dark:bg-slate-800 p-3 text-xs text-slate-600 dark:text-slate-300">
                Dummy: superadmin@payroll.local, admin.alpha@payroll.local, employee.alpha@payroll.local. Password: Password123!
            </div>
        </div>
    </div>
</template>
