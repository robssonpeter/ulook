<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    profileId: { type: Number, default: null },
});

const tabs = [
    { label: 'Bookings', route: 'business.dashboard' },
    { label: 'Profile & services', route: 'business.profile' },
];
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-plum/10">
        <nav class="-mb-px flex gap-6 text-sm font-medium" aria-label="Business sections">
            <Link
                v-for="tab in tabs"
                :key="tab.route"
                :href="route(tab.route)"
                :class="route().current(tab.route) ? 'border-gold text-ink' : 'border-transparent text-muted hover:text-ink'"
                class="border-b-2 pb-3 transition"
            >
                {{ tab.label }}
            </Link>
        </nav>
        <Link v-if="profileId" :href="route('professionals.show', profileId)" class="pb-3 text-sm font-medium text-plum hover:text-gold">
            View public profile &rarr;
        </Link>
    </div>
</template>
