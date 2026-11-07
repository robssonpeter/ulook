<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import BusinessTabs from '@/Components/Public/BusinessTabs.vue';
import StatusBadge from '@/Components/Public/StatusBadge.vue';
import { formatDate, formatPrice } from '@/yuluk';

const props = defineProps({
    profileId: { type: Number, required: true },
    bookings: { type: Array, required: true },
});

const sections = computed(() => [
    { key: 'pending', title: 'New requests', empty: 'No new requests right now.', items: props.bookings.filter((b) => b.status === 'pending') },
    { key: 'confirmed', title: 'Upcoming', empty: 'Nothing scheduled yet.', items: props.bookings.filter((b) => b.status === 'confirmed') },
]);
const history = computed(() => props.bookings.filter((b) => ['completed', 'cancelled'].includes(b.status)).reverse());

const form = useForm({ status: '', completion_code: '' });
const completing = ref(null);

function update(booking, status) {
    form.status = status;
    form.patch(route('business.bookings.update', booking.id), {
        preserveScroll: true,
        onSuccess: () => {
            completing.value = null;
            form.reset();
        },
    });
}

function startCompleting(booking) {
    completing.value = booking.id;
    form.clearErrors();
    form.completion_code = '';
}
</script>

<template>
    <Head title="My business" />

    <PublicLayout>
        <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
            <h1 class="text-3xl font-semibold">My business</h1>
            <div class="mt-6">
                <BusinessTabs :profile-id="profileId" />
            </div>

            <div v-for="section in sections" :key="section.key" class="mt-8">
                <h2 class="text-lg font-semibold">
                    {{ section.title }}
                    <span v-if="section.items.length" class="ml-2 rounded-full bg-gold-soft px-2.5 py-0.5 text-sm text-plum">{{ section.items.length }}</span>
                </h2>
                <p v-if="!section.items.length" class="mt-3 rounded-2xl bg-white p-5 text-sm text-muted ring-1 ring-plum/5">{{ section.empty }}</p>

                <ul v-else class="mt-4 space-y-4">
                    <li v-for="b in section.items" :key="b.id" class="rounded-2xl bg-white p-5 ring-1 ring-plum/5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ b.service }}</p>
                                <p class="text-sm text-muted">
                                    {{ b.customer }}
                                    <a v-if="b.customer_phone" :href="`tel:${b.customer_phone}`" class="ml-1 font-medium text-plum hover:text-gold">{{ b.customer_phone }}</a>
                                </p>
                            </div>
                            <StatusBadge :status="b.status" />
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            <div><dt class="text-muted">When</dt><dd class="font-medium">{{ formatDate(b.date) }} · {{ b.time }}</dd></div>
                            <div><dt class="text-muted">Where</dt><dd class="font-medium">{{ b.type === 'request' ? `Client’s place: ${b.address}` : 'At your place' }}</dd></div>
                            <div><dt class="text-muted">Price</dt><dd class="font-medium">{{ formatPrice(b.price) }}</dd></div>
                        </dl>

                        <div class="mt-4 border-t border-plum/5 pt-4">
                            <div v-if="b.status === 'pending'" class="flex flex-wrap gap-3">
                                <button type="button" class="yl-btn py-2.5 text-sm" :disabled="form.processing" @click="update(b, 'confirmed')">Accept</button>
                                <button type="button" class="yl-btn-ghost text-sm" :disabled="form.processing" @click="update(b, 'cancelled')">Decline</button>
                            </div>

                            <template v-else-if="b.status === 'confirmed'">
                                <form v-if="completing === b.id" class="flex flex-wrap items-start gap-3" @submit.prevent="update(b, 'completed')">
                                    <div v-if="b.needs_code">
                                        <label :for="`code-${b.id}`" class="sr-only">Completion code</label>
                                        <input
                                            :id="`code-${b.id}`"
                                            v-model="form.completion_code"
                                            inputmode="numeric"
                                            maxlength="10"
                                            placeholder="Customer’s 4-digit code"
                                            class="yl-input w-56 py-2.5 font-mono tracking-widest"
                                            required
                                        >
                                        <p v-if="form.errors.completion_code" class="mt-1.5 text-sm text-red-600">{{ form.errors.completion_code }}</p>
                                    </div>
                                    <button type="submit" class="yl-btn py-2.5 text-sm" :disabled="form.processing">Confirm completed</button>
                                    <button type="button" class="yl-btn-ghost text-sm" @click="completing = null">Back</button>
                                </form>
                                <div v-else class="flex flex-wrap gap-3">
                                    <button type="button" class="yl-btn py-2.5 text-sm" @click="startCompleting(b)">Mark as completed</button>
                                    <button type="button" class="yl-btn-ghost text-sm" :disabled="form.processing" @click="update(b, 'cancelled')">Cancel</button>
                                </div>
                            </template>
                        </div>
                    </li>
                </ul>
            </div>

            <div v-if="history.length" class="mt-10">
                <h2 class="text-lg font-semibold">History</h2>
                <ul class="mt-4 divide-y divide-plum/5 rounded-2xl bg-white ring-1 ring-plum/5">
                    <li v-for="b in history" :key="b.id" class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                        <div>
                            <p class="font-medium">{{ b.service }} · {{ b.customer }}</p>
                            <p class="text-muted">{{ formatDate(b.date) }} · {{ b.time }} · {{ formatPrice(b.price) }}</p>
                        </div>
                        <StatusBadge :status="b.status" />
                    </li>
                </ul>
            </div>
        </section>
    </PublicLayout>
</template>
