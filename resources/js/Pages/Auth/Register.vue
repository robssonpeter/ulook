<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Field from '@/Components/Public/Field.vue';

// ?as=professional preselects the business option (used by "For professionals" links).
const params = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');

const form = useForm({
    account_type: params.get('as') === 'professional' ? 'professional' : 'customer',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const types = [
    { value: 'customer', title: 'I want to book', body: 'Find and book beauty & grooming pros.' },
    { value: 'professional', title: "I'm a professional", body: 'Get clients and manage bookings.' },
];

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Join YuLuk" />

    <PublicLayout>
        <div class="mx-auto max-w-lg px-4 py-16">
            <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-plum/5">
                <h1 class="text-2xl font-semibold">Join YuLuk</h1>
                <p class="mt-1 text-sm text-muted">One account works on the website and in the apps.</p>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <fieldset>
                        <legend class="sr-only">Account type</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                v-for="t in types"
                                :key="t.value"
                                :class="form.account_type === t.value ? 'bg-gold-soft/50 ring-2 ring-gold' : 'ring-1 ring-plum/10 hover:ring-gold/60'"
                                class="cursor-pointer rounded-2xl p-4 transition"
                            >
                                <input v-model="form.account_type" type="radio" name="account_type" :value="t.value" class="sr-only">
                                <span class="block font-semibold">{{ t.title }}</span>
                                <span class="mt-1 block text-xs text-muted">{{ t.body }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <Field id="name" :label="form.account_type === 'professional' ? 'Your name or business name' : 'Full name'" :error="form.errors.name">
                        <input id="name" v-model="form.name" type="text" class="yl-input" required autofocus autocomplete="name">
                    </Field>

                    <Field id="email" label="Email" :error="form.errors.email">
                        <input id="email" v-model="form.email" type="email" class="yl-input" required autocomplete="email">
                    </Field>

                    <Field id="phone" label="Phone number" :error="form.errors.phone" hint="Optional. You can also sign in with it.">
                        <input id="phone" v-model="form.phone" type="tel" class="yl-input" placeholder="+255 7XX XXX XXX" autocomplete="tel">
                    </Field>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <Field id="password" label="Password" :error="form.errors.password">
                            <input id="password" v-model="form.password" type="password" class="yl-input" required autocomplete="new-password">
                        </Field>
                        <Field id="password_confirmation" label="Confirm password" :error="form.errors.password_confirmation">
                            <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="yl-input" required autocomplete="new-password">
                        </Field>
                    </div>

                    <button type="submit" class="yl-btn w-full" :disabled="form.processing">
                        {{ form.account_type === 'professional' ? 'Continue to business setup' : 'Create account' }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-muted">
                    Already have an account?
                    <Link :href="route('login')" class="font-semibold text-plum hover:text-gold">Sign in</Link>
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
