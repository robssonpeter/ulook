<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
});

// Same status colours as the apps: orange pending, green confirmed, blue completed, red cancelled.
const styles = {
    pending: ['Awaiting confirmation', 'bg-orange-50 text-orange-700 ring-orange-200'],
    confirmed: ['Confirmed', 'bg-green-50 text-green-700 ring-green-200'],
    completed: ['Completed', 'bg-blue-50 text-blue-700 ring-blue-200'],
    cancelled: ['Cancelled', 'bg-red-50 text-red-700 ring-red-200'],
};

const style = computed(() => styles[props.status] ?? [props.status, 'bg-plum-soft text-plum ring-plum/10']);
</script>

<template>
    <span :class="style[1]" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1">{{ style[0] }}</span>
</template>
