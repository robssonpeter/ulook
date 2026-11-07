<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import ProfessionalCard from '@/Components/Public/ProfessionalCard.vue';
import StoreButtons from '@/Components/Public/StoreButtons.vue';
import YulukMark from '@/Components/Public/YulukMark.vue';
import { categoryPhoto } from '@/yuluk';

const props = defineProps({
    categories: { type: Array, required: true },
    featured: { type: Array, required: true },
    storeLinks: { type: Object, required: true },
});

const search = ref('');

function submitSearch() {
    router.get(route('professionals.index'), search.value ? { q: search.value } : {});
}

const steps = [
    { title: 'Discover', body: 'Browse barbers, stylists, makeup artists and more. See their work, services and prices up front.' },
    { title: 'Connect', body: 'Follow the professionals you love, message them, and see their latest looks in your feed.' },
    { title: 'Book', body: 'Pick a service and a time. Visit their salon, or request them to come to you.' },
];

const businessPoints = [
    'Receive bookings and home-service requests from nearby clients',
    'Set your own services, prices and working hours',
    'Show off your portfolio and post updates to followers',
    'Build your YuLuk Passport: skills, certifications and work history in one profile',
];
</script>

<template>
    <Head title="Book trusted beauty & grooming professionals" />

    <PublicLayout tone="dark">
        <!-- Hero -->
        <section class="relative overflow-hidden bg-plum-dark text-white">
            <div class="pointer-events-none absolute -right-32 -top-32 h-[32rem] w-[32rem] rounded-full bg-gold/20 blur-3xl" />
            <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pb-20 pt-10 sm:px-6 lg:grid-cols-2 lg:pb-28 lg:pt-16">
                <div>
                    <p class="text-sm font-medium tracking-[0.25em] text-gold">DISCOVER. CONNECT. BOOK.</p>
                    <h1 class="mt-5 text-4xl font-semibold leading-tight sm:text-5xl">
                        Your next great look, <span class="text-gold">just around the corner.</span>
                    </h1>
                    <p class="mt-5 max-w-lg text-lg text-white/70">
                        Find trusted barbers, stylists, braiders and beauty therapists near you. Book at their place, or have them come to yours.
                    </p>

                    <form class="mt-8 flex max-w-lg flex-col gap-2 rounded-2xl bg-white p-2 sm:flex-row" @submit.prevent="submitSearch">
                        <label for="hero-search" class="sr-only">Search professionals</label>
                        <input
                            id="hero-search"
                            v-model="search"
                            type="search"
                            placeholder="Try “braiding” or “Mbezi Beach”"
                            class="min-w-0 flex-1 rounded-xl border-0 px-4 py-3 text-ink placeholder:text-muted focus:ring-2 focus:ring-gold"
                        >
                        <button type="submit" class="rounded-xl bg-plum px-6 py-3 font-semibold text-white transition hover:bg-plum-dark">
                            Search
                        </button>
                    </form>

                    <div v-if="categories.length" class="mt-5 flex flex-wrap gap-2">
                        <Link
                            v-for="c in categories.slice(0, 5)"
                            :key="c.id"
                            :href="route('professionals.index', { service: c.id })"
                            class="rounded-full border border-white/20 px-4 py-1.5 text-sm text-white/80 transition hover:border-gold hover:text-gold"
                        >
                            {{ c.name }}
                        </Link>
                    </div>
                </div>

                <!-- Photo collage -->
                <div class="relative hidden h-[28rem] lg:block">
                    <img :src="categoryPhoto('braid', 700)" alt="" class="absolute left-0 top-6 h-72 w-56 rounded-3xl object-cover shadow-2xl">
                    <img :src="categoryPhoto('barber', 700)" alt="" class="absolute right-4 top-0 h-60 w-60 rounded-3xl object-cover shadow-2xl">
                    <img :src="categoryPhoto('makeup', 700)" alt="" class="absolute bottom-0 left-28 h-56 w-64 rounded-3xl object-cover shadow-2xl">
                    <div class="absolute bottom-16 right-0 flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-ink shadow-xl">
                        <YulukMark :size="32" />
                        <div>
                            <p class="text-sm font-semibold">Book in a few taps</p>
                            <p class="text-xs text-muted">At the salon or at home</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Categories -->
        <section v-if="categories.length" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold sm:text-3xl">Browse by service</h2>
                    <p class="mt-2 text-muted">What are you looking for today?</p>
                </div>
                <Link :href="route('professionals.index')" class="shrink-0 text-sm font-semibold text-plum hover:text-gold">
                    View all &rarr;
                </Link>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <Link
                    v-for="c in categories"
                    :key="c.id"
                    :href="route('professionals.index', { service: c.id })"
                    class="group relative h-40 overflow-hidden rounded-2xl sm:h-48"
                >
                    <img :src="categoryPhoto(c.name, 600)" :alt="c.name" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-plum-dark/85 via-plum-dark/20 to-transparent" />
                    <div class="absolute bottom-0 p-4 text-white">
                        <p class="font-semibold">{{ c.name }}</p>
                        <p class="text-xs text-white/70">
                            {{ c.count ? `${c.count} professional${c.count === 1 ? '' : 's'}` : 'Coming soon' }}
                        </p>
                    </div>
                </Link>
            </div>
        </section>

        <!-- Featured professionals -->
        <section v-if="featured.length" class="bg-white">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold sm:text-3xl">Professionals on YuLuk</h2>
                        <p class="mt-2 text-muted">Talented people ready to take your booking.</p>
                    </div>
                    <Link :href="route('professionals.index')" class="shrink-0 text-sm font-semibold text-plum hover:text-gold">
                        See everyone &rarr;
                    </Link>
                </div>
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <ProfessionalCard v-for="p in featured" :key="p.id" :professional="p" />
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="how-it-works" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-16 sm:px-6">
            <h2 class="text-center text-2xl font-semibold sm:text-3xl">How YuLuk works</h2>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                <div v-for="(step, i) in steps" :key="step.title" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-plum/5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-plum text-sm font-semibold text-gold">
                        {{ i + 1 }}
                    </span>
                    <h3 class="mt-4 text-lg font-semibold">{{ step.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ step.body }}</p>
                </div>
            </div>
        </section>

        <!-- YuLuk Business -->
        <section id="business" class="scroll-mt-20 bg-[#0A0A0A] text-white">
            <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2">
                <div>
                    <div class="flex items-center gap-3">
                        <YulukMark :size="36" color="#D9A845" />
                        <span class="text-sm font-medium tracking-[0.3em] text-[#D9A845]">YULUK BUSINESS</span>
                    </div>
                    <h2 class="mt-6 text-3xl font-semibold sm:text-4xl">Own. Connect. Earn.</h2>
                    <p class="mt-4 max-w-lg text-white/70">
                        Are you a barber, stylist, makeup artist or beauty therapist? YuLuk Business puts your craft in front of clients nearby and keeps your bookings organised.
                    </p>
                    <ul class="mt-6 space-y-3">
                        <li v-for="point in businessPoints" :key="point" class="flex gap-3 text-sm text-white/80">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#D9A845]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd" />
                            </svg>
                            {{ point }}
                        </li>
                    </ul>
                    <div class="mt-8">
                        <StoreButtons :links="storeLinks.business" />
                    </div>
                </div>
                <div class="relative mx-auto hidden h-80 w-80 lg:block">
                    <div class="absolute inset-0 rounded-full bg-[#D9A845]/25 blur-3xl" />
                    <div class="relative flex h-full w-full items-center justify-center">
                        <YulukMark :size="200" color="#D9A845" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Get the app -->
        <section id="get-the-app" class="scroll-mt-20 mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div class="relative overflow-hidden rounded-3xl bg-plum px-6 py-12 text-white sm:px-12">
                <div class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-gold/25 blur-3xl" />
                <div class="relative max-w-xl">
                    <h2 class="text-2xl font-semibold sm:text-3xl">Book on the go with the YuLuk app</h2>
                    <p class="mt-3 text-white/70">
                        Booking, chat with professionals, reminders and your following feed, all in your pocket.
                    </p>
                    <div class="mt-8">
                        <StoreButtons :links="storeLinks.customer" />
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
