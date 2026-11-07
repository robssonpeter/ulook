<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Field from '@/Components/Public/Field.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

// Fortify's "email" field accepts an email or a phone number.
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign in" />

    <PublicLayout>
        <div class="mx-auto max-w-md px-4 py-16">
            <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-plum/5">
                <h1 class="text-2xl font-semibold">Welcome back</h1>
                <p class="mt-1 text-sm text-muted">Sign in to book or manage your business.</p>

                <div v-if="status" class="mt-4 rounded-xl bg-gold-soft px-4 py-3 text-sm text-plum">{{ status }}</div>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <Field id="email" label="Email or phone number" :error="form.errors.email">
                        <input id="email" v-model="form.email" type="text" class="yl-input" required autofocus autocomplete="username">
                    </Field>

                    <Field id="password" label="Password" :error="form.errors.password">
                        <input id="password" v-model="form.password" type="password" class="yl-input" required autocomplete="current-password">
                    </Field>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2">
                            <input v-model="form.remember" type="checkbox" class="rounded border-plum/30 text-plum focus:ring-gold">
                            <span class="text-muted">Remember me</span>
                        </label>
                        <Link v-if="canResetPassword" :href="route('password.request')" class="font-medium text-plum hover:text-gold">
                            Forgot password?
                        </Link>
                    </div>

                    <button type="submit" class="yl-btn w-full" :disabled="form.processing">Sign in</button>
                </form>

                <p class="mt-6 text-center text-sm text-muted">
                    New to YuLuk?
                    <Link :href="route('register')" class="font-semibold text-plum hover:text-gold">Create an account</Link>
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
