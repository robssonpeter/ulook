<script setup>
// The YuLuk swirl, same path as the mobile apps' henna mark (100x100 box).
defineProps({
    size: { type: Number, default: 40 },
    color: { type: String, default: '#C99A3F' },
});
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 100 100" fill="none" aria-hidden="true">
        <path
            d="M22 76C13 54 24 28 50 19C69 12 87 22 83 39C80.5 50 67 52 60 43C55.5 37 59 29 66 31C71 32.5 70 38 64.5 37.5"
            :stroke="color"
            stroke-width="7"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>
