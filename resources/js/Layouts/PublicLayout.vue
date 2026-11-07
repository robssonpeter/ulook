<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import YulukMark from '@/Components/Public/YulukMark.vue';

// "dark" puts the header over the plum hero; "light" is for content pages.
defineProps({
    tone: { type: String, default: 'light' },
});

const menuOpen = ref(false);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isPro = computed(() => user.value?.role === 'professional');

// Success banners come from the session flash; errors not tied to a field
// (e.g. booking as the wrong account type) are shown here too.
const banner = ref(null);
watch(
    () => [page.props.jetstream?.flash?.banner, page.props.errors],
    ([flash, errors]) => {
        const general = errors?.booking || errors?.status;
        banner.value = general ? { text: general, error: true } : flash ? { text: flash, error: false } : null;
    },
    { immediate: true },
);

function signOut() {
    router.post(route('logout'));
}
const year = new Date().getFullYear();
</script>

<template>
    <div class="min-h-screen bg-cream text-ink">
        <header
            :class="tone === 'dark' ? 'bg-plum-dark text-white' : 'bg-cream/90 text-ink border-b border-plum/10 backdrop-blur'"
            class="sticky top-0 z-40"
        >
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
                <Link :href="route('home')" class="flex items-center gap-2">
                    <YulukMark :size="30" />
                    <span class="text-lg font-semibold tracking-[0.3em]">YULUK</span>
                </Link>

                <nav class="hidden items-center gap-8 text-sm font-medium md:flex">
                    <Link :href="route('professionals.index')" class="opacity-80 transition hover:opacity-100">
                        Find a professional
                    </Link>
                    <a :href="route('home') + '#business'" class="opacity-80 transition hover:opacity-100">
                        For professionals
                    </a>
                    <template v-if="user">
                        <Link
                            :href="isPro ? route('business.dashboard') : route('account.bookings')"
                            class="opacity-80 transition hover:opacity-100"
                        >
                            {{ isPro ? 'My business' : 'My bookings' }}
                        </Link>
                        <button type="button" class="opacity-80 transition hover:opacity-100" @click="signOut">Sign out</button>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="opacity-80 transition hover:opacity-100">Sign in</Link>
                        <Link
                            :href="route('register')"
                            class="rounded-full bg-gold px-5 py-2.5 font-semibold text-plum-dark transition hover:brightness-110"
                        >
                            Join YuLuk
                        </Link>
                    </template>
                </nav>

                <button
                    type="button"
                    class="rounded-lg p-2 md:hidden"
                    :aria-expanded="menuOpen"
                    aria-label="Toggle menu"
                    @click="menuOpen = !menuOpen"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path v-if="!menuOpen" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        <path v-else stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <nav v-if="menuOpen" class="space-y-1 px-4 pb-4 text-sm font-medium md:hidden">
                <Link :href="route('professionals.index')" class="block rounded-lg px-3 py-2 hover:bg-white/10">
                    Find a professional
                </Link>
                <a :href="route('home') + '#business'" class="block rounded-lg px-3 py-2 hover:bg-white/10" @click="menuOpen = false">
                    For professionals
                </a>
                <template v-if="user">
                    <Link
                        :href="isPro ? route('business.dashboard') : route('account.bookings')"
                        class="block rounded-lg px-3 py-2 hover:bg-white/10"
                    >
                        {{ isPro ? 'My business' : 'My bookings' }}
                    </Link>
                    <button type="button" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-white/10" @click="signOut">Sign out</button>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="block rounded-lg px-3 py-2 hover:bg-white/10">Sign in</Link>
                    <Link :href="route('register')" class="block rounded-lg px-3 py-2 font-semibold text-gold">Join YuLuk</Link>
                </template>
            </nav>
        </header>

        <div v-if="banner" class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
            <div
                :class="banner.error ? 'bg-red-50 text-red-800 ring-red-200' : 'bg-gold-soft text-plum ring-gold/30'"
                class="flex items-center justify-between gap-4 rounded-xl px-4 py-3 text-sm font-medium ring-1"
                role="status"
            >
                <span>{{ banner.text }}</span>
                <button type="button" class="opacity-60 hover:opacity-100" aria-label="Dismiss" @click="banner = null">&times;</button>
            </div>
        </div>

        <main>
            <slot />
        </main>

        <footer class="bg-plum-dark text-white/70">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-3">
                <div>
                    <div class="flex items-center gap-2 text-white">
                        <YulukMark :size="28" />
                        <span class="text-lg font-semibold tracking-[0.3em]">YULUK</span>
                    </div>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed">
                        Discover trusted beauty and grooming professionals near you, and book in a few taps.
                    </p>
                </div>
                <div class="text-sm">
                    <p class="font-semibold text-white">Explore</p>
                    <ul class="mt-4 space-y-2">
                        <li><Link :href="route('professionals.index')" class="hover:text-gold">Find a professional</Link></li>
                        <li><a :href="route('home') + '#how-it-works'" class="hover:text-gold">How it works</a></li>
                        <li><a :href="route('home') + '#business'" class="hover:text-gold">YuLuk Business</a></li>
                    </ul>
                </div>
                <div class="text-sm">
                    <p class="font-semibold text-white">Get the app</p>
                    <p class="mt-4 leading-relaxed">
                        Booking, chat and reminders all happen in the YuLuk app for iPhone and Android.
                    </p>
                    <a :href="route('home') + '#get-the-app'" class="mt-3 inline-block font-semibold text-gold hover:underline">
                        Download YuLuk &rarr;
                    </a>
                </div>
            </div>
            <div class="border-t border-white/10">
                <p class="mx-auto max-w-6xl px-4 py-6 text-xs sm:px-6">&copy; {{ year }} YuLuk. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
