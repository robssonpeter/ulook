<script setup>
// App Store / Google Play buttons; a missing URL renders as "Coming soon".
defineProps({
    links: { type: Object, required: true },
});

const stores = [
    {
        key: 'ios',
        top: 'Download on the',
        name: 'App Store',
        icon: 'M16.4 12.6c0-2.6 2.1-3.8 2.2-3.9-1.2-1.8-3.1-2-3.7-2-1.6-.2-3.1.9-3.9.9-.8 0-2-.9-3.4-.9-1.7 0-3.3 1-4.2 2.6-1.8 3.1-.5 7.7 1.3 10.2.9 1.2 1.9 2.6 3.2 2.6 1.3-.1 1.8-.8 3.3-.8 1.6 0 2 .8 3.4.8 1.4 0 2.3-1.3 3.1-2.5 1-1.4 1.4-2.8 1.4-2.9-.1 0-2.7-1-2.7-4.1zM13.9 5c.7-.9 1.2-2 1.1-3.2-1 0-2.3.7-3 1.6-.7.8-1.2 2-1.1 3.1 1.1.1 2.3-.6 3-1.5z',
    },
    {
        key: 'android',
        top: 'Get it on',
        name: 'Google Play',
        icon: 'M3.6 1.8c-.3.3-.4.7-.4 1.3v17.8c0 .6.1 1 .4 1.3l10-10.2-10-10.2zm11.1 11.3l2.9 2.9-11.6 6.6 8.7-9.5zm0-2.2L6 1.4 17.6 8l-2.9 2.9zm4.1-2.1l3 1.7c.9.5.9 1.4 0 1.9l-3 1.7-3.2-2.7 3.2-2.6z',
    },
];
</script>

<template>
    <div class="flex flex-wrap gap-3">
        <component
            :is="links[store.key] ? 'a' : 'span'"
            v-for="store in stores"
            :key="store.key"
            :href="links[store.key] || undefined"
            :target="links[store.key] ? '_blank' : undefined"
            :rel="links[store.key] ? 'noopener' : undefined"
            :class="links[store.key] ? 'hover:bg-black' : 'cursor-default opacity-80'"
            class="flex items-center gap-3 rounded-xl bg-black/90 px-4 py-2.5 text-white ring-1 ring-white/15 transition"
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path :d="store.icon" />
            </svg>
            <span class="leading-tight">
                <span class="block text-[10px] uppercase tracking-wide text-white/70">
                    {{ links[store.key] ? store.top : 'Coming soon to' }}
                </span>
                <span class="block text-base font-semibold">{{ store.name }}</span>
            </span>
        </component>
    </div>
</template>
