<script setup>
defineProps({ message: String });
</script>

<template>
    <p v-if="message" class="mt-1 text-xs font-medium text-rose-600">{{ message }}</p>
</template>
