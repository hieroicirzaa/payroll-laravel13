<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);
</script>

<template>
    <div v-if="success || error" class="space-y-2">
        <div v-if="success" class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800 dark:border-teal-900/70 dark:bg-teal-950/50 dark:text-teal-200">
            {{ success }}
        </div>
        <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/50 dark:text-rose-200">
            {{ error }}
        </div>
    </div>
</template>
