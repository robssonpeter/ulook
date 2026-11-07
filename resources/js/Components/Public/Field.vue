<script setup>
// Labelled form field with error text, styled for the public site.
defineProps({
    label: { type: String, required: true },
    id: { type: String, required: true },
    error: { type: String, default: null },
    hint: { type: String, default: null },
});
</script>

<template>
    <div>
        <label :for="id" class="block text-sm font-medium text-ink">{{ label }}</label>
        <div class="mt-1.5">
            <slot />
        </div>
        <p v-if="error" class="mt-1.5 text-sm text-red-600">{{ error }}</p>
        <p v-else-if="hint" class="mt-1.5 text-xs text-muted">{{ hint }}</p>
    </div>
</template>
