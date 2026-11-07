<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StatusBadge from '@/Components/Public/StatusBadge.vue';
import { formatDate, formatPrice } from '@/yuluk';

const props = defineProps({
    bookings: { type: Array, required: true },
});

const upcoming = computed(() => props.bookings.filter((b) => ['pending', 'confirmed'].includes(b.status)));
const past = computed(() => props.bookings.filter((b) => !['pending', 'confirmed'].includes(b.status)));

function cancel(booking) {
    router.patch(route('account.bookings.cancel', booking.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="My bookings" />

    <PublicLayout>
        <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-3xl font-semibold">My bookings</h1>
                <Link :href="route('professionals.index')" class="yl-btn text-sm">Book a service</Link>
            </div>

            <div v-if="!bookings.length" class="mt-10 rounded-2xl bg-white p-10 text-center ring-1 ring-plum/5">
                <p class="text-lg font-semibold">No bookings yet</p>
                <p class="mt-2 text-muted">Find a professional you like and book your first appointment.</p>
                <Link :href="route('professionals.index')" class="yl-btn mt-5">Find a professional</Link>
            </div>

            <template v-else>
                <h2 v-if="upcoming.length" class="mt-10 text-lg font-semibold">Upcoming</h2>
                <ul class="mt-4 space-y-4">
                    <li v-for="b in upcoming" :key="b.id" class="rounded-2xl bg-white p-5 ring-1 ring-plum/5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ b.service }}</p>
                                <p class="text-sm text-muted">
                                    with
                                    <Link v-if="b.professional_profile_id" :href="route('professionals.show', b.professional_profile_id)" class="font-medium text-plum hover:text-gold">{{ b.professional }}</Link>
                                    <span v-else>{{ b.professional }}</span>
                                </p>
                            </div>
                            <StatusBadge :status="b.status" />
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            <div><dt class="text-muted">When</dt><dd class="font-medium">{{ formatDate(b.date) }} · {{ b.time }}</dd></div>
                            <div><dt class="text-muted">Where</dt><dd class="font-medium">{{ b.type === 'request' ? (b.address || 'Your place') : 'At their place' }}</dd></div>
                            <div><dt class="text-muted">Price</dt><dd class="font-medium">{{ formatPrice(b.price) }}</dd></div>
                        </dl>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-plum/5 pt-4">
                            <p v-if="b.completion_code" class="text-sm">
                                Completion code:
                                <span class="ml-1 rounded-lg bg-plum px-2.5 py-1 font-mono font-semibold tracking-widest text-gold">{{ b.completion_code }}</span>
                                <span class="mt-1 block text-xs text-muted">Read this to your professional once the service is done.</span>
                            </p>
                            <button v-if="b.status === 'pending'" type="button" class="yl-btn-ghost text-sm" @click="cancel(b)">
                                Cancel booking
                            </button>
                        </div>
                    </li>
                </ul>

                <h2 v-if="past.length" class="mt-10 text-lg font-semibold">Past</h2>
                <ul class="mt-4 divide-y divide-plum/5 rounded-2xl bg-white ring-1 ring-plum/5">
                    <li v-for="b in past" :key="b.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                        <div>
                            <p class="font-medium">{{ b.service }} · {{ b.professional }}</p>
                            <p class="text-muted">{{ formatDate(b.date) }} · {{ b.time }} · {{ formatPrice(b.price) }}</p>
                        </div>
                        <StatusBadge :status="b.status" />
                    </li>
                </ul>
            </template>
        </section>
    </PublicLayout>
</template>
