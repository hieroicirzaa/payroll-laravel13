<script setup>
defineProps({ value: [String, Boolean] });
</script>

<template>
    <span
        class="badge"
        :class="{
            'bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300': value === true || value === 'active' || value === 'paid' || value === 'final' || value === 'submitted',
            'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300': value === 'draft' || value === 'open' || value === 'generated',
            'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300': value === false || value === 'inactive' || value === 'failed',
            'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300': value === 'closed'
        }"
    >
        {{ value === true ? 'active' : value === false ? 'inactive' : value }}
    </span>
</template>
