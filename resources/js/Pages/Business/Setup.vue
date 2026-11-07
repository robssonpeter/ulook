<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Field from '@/Components/Public/Field.vue';
import { categoryPhoto } from '@/yuluk';

const props = defineProps({
    services: { type: Array, required: true },
});

const form = useForm({
    location: '',
    bio: '',
    years_experience: '',
    services: [],
});

const selected = computed(() => new Set(form.services.map((s) => s.service_id)));

function toggle(service) {
    if (selected.value.has(service.id)) {
        form.services = form.services.filter((s) => s.service_id !== service.id);
    } else {
        form.services.push({ service_id: service.id, name: service.name, price: '', duration_minutes: 60 });
    }
}

// Errors for nested rows arrive as "services.0.price".
const rowError = (i, field) => form.errors[`services.${i}.${field}`];

function submit() {
    form.transform((data) => ({
        ...data,
        services: data.services.map(({ name, ...rest }) => rest),
    })).post(route('business.setup.store'));
}
</script>

<template>
    <Head title="Set up your business" />

    <PublicLayout>
        <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            <p class="text-sm font-medium tracking-[0.25em] text-gold">YULUK BUSINESS</p>
            <h1 class="mt-2 text-3xl font-semibold">Set up your business</h1>
            <p class="mt-2 text-muted">A few details and your profile goes live for clients to book.</p>

            <form class="mt-8 space-y-8" @submit.prevent="submit">
                <div class="space-y-5 rounded-2xl bg-white p-6 ring-1 ring-plum/5">
                    <h2 class="font-semibold">1. About you</h2>
                    <Field id="location" label="Where do you work?" :error="form.errors.location" hint="Area or salon address, e.g. “Mbezi Beach, Dar es Salaam”.">
                        <input id="location" v-model="form.location" type="text" class="yl-input" required>
                    </Field>
                    <Field id="years" label="Years of experience" :error="form.errors.years_experience">
                        <input id="years" v-model="form.years_experience" type="number" min="0" max="60" class="yl-input max-w-[10rem]">
                    </Field>
                    <Field id="bio" label="Short bio" :error="form.errors.bio" hint="What you’re great at. Clients see this on your profile.">
                        <textarea id="bio" v-model="form.bio" rows="3" class="yl-input" />
                    </Field>
                </div>

                <div class="rounded-2xl bg-white p-6 ring-1 ring-plum/5">
                    <h2 class="font-semibold">2. What do you offer?</h2>
                    <p class="mt-1 text-sm text-muted">Pick your services, then set a price and how long each takes.</p>
                    <p v-if="form.errors.services" class="mt-2 text-sm text-red-600">{{ form.errors.services }}</p>

                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <button
                            v-for="s in services"
                            :key="s.id"
                            type="button"
                            :aria-pressed="selected.has(s.id)"
                            :class="selected.has(s.id) ? 'ring-2 ring-gold' : 'ring-1 ring-plum/10 opacity-80 hover:opacity-100'"
                            class="relative h-24 overflow-hidden rounded-xl text-left transition"
                            @click="toggle(s)"
                        >
                            <img :src="categoryPhoto(s.name, 300)" alt="" class="absolute inset-0 h-full w-full object-cover">
                            <span class="absolute inset-0 bg-gradient-to-t from-plum-dark/85 to-plum-dark/10" />
                            <span class="absolute bottom-2 left-3 text-sm font-semibold text-white">{{ s.name }}</span>
                            <span v-if="selected.has(s.id)" class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-gold text-xs font-bold text-plum-dark">✓</span>
                        </button>
                    </div>

                    <div v-if="form.services.length" class="mt-6 space-y-3">
                        <div v-for="(row, i) in form.services" :key="row.service_id" class="grid items-start gap-3 rounded-xl bg-cream p-4 sm:grid-cols-[1fr_10rem_9rem]">
                            <p class="pt-3 font-medium">{{ row.name }}</p>
                            <Field :id="`price-${i}`" label="Price (TZS)" :error="rowError(i, 'price')">
                                <input :id="`price-${i}`" v-model="row.price" type="number" min="0" step="500" class="yl-input" required>
                            </Field>
                            <Field :id="`dur-${i}`" label="Minutes" :error="rowError(i, 'duration_minutes')">
                                <input :id="`dur-${i}`" v-model="row.duration_minutes" type="number" min="5" max="720" step="5" class="yl-input" required>
                            </Field>
                        </div>
                    </div>
                </div>

                <button type="submit" class="yl-btn w-full sm:w-auto" :disabled="form.processing">Publish my profile</button>
            </form>
        </section>
    </PublicLayout>
</template>
