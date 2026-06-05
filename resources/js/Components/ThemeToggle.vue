<script setup>
import { computed } from 'vue';
import { Moon, Sun } from 'lucide-vue-next';
import { useTheme } from '@/Composables/useTheme';

const { isDark, toggleTheme } = useTheme();
const label = computed(() => isDark.value ? 'Ganti ke tema cerah' : 'Ganti ke tema gelap');
</script>

<template>
    <button type="button" class="btn-secondary gap-2" :aria-label="label" :title="label" @click="toggleTheme">
        <Sun v-if="isDark" class="h-4 w-4" />
        <Moon v-else class="h-4 w-4" />
        <span class="hidden sm:inline">{{ isDark ? 'Cerah' : 'Gelap' }}</span>
    </button>
</template>
