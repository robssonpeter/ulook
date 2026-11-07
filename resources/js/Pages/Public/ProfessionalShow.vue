<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Field from '@/Components/Public/Field.vue';
import StoreButtons from '@/Components/Public/StoreButtons.vue';
import { avatarUrl, categoryPhoto, formatDuration, formatPrice, todayIso } from '@/yuluk';

const props = defineProps({
    professional: { type: Object, required: true },
    bookable: { type: Array, required: true },
    isOwnProfile: { type: Boolean, default: false },
    storeLinks: { type: Object, required: true },
});

const user = computed(() => usePage().props.auth?.user);
const firstName = computed(() => props.professional.name.split(' ')[0]);

const booking = useForm({
    professional_service_id: props.bookable[0]?.id ?? '',
    booking_date: '',
    booking_time: '',
    type: 'booking',
    customer_address: '',
});

const chosen = computed(() => props.bookable.find((s) => s.id === Number(booking.professional_service_id)));

function book() {
    booking.post(route('bookings.store'), { preserveScroll: true });
}

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Monday-first, like a printed timetable.
const hours = computed(() => [...(props.professional.working_hours || [])]
    .sort((a, b) => ((a.day_of_week + 6) % 7) - ((b.day_of_week + 6) % 7))
    .map((h) => ({
        day: DAYS[h.day_of_week],
        text: h.is_closed || !h.open_time ? 'Closed' : `${h.open_time.slice(0, 5)} – ${h.close_time?.slice(0, 5) ?? ''}`,
    })));

const services = computed(() => props.professional.services || []);
const photos = computed(() => props.professional.portfolio_photos || []);
</script>

<template>
    <Head :title="`${professional.name} · ${professional.category}`" />

    <PublicLayout>
        <!-- Cover -->
        <section class="relative h-56 overflow-hidden sm:h-72">
            <img :src="categoryPhoto(professional.category, 1400)" alt="" class="h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-plum-dark/90 via-plum-dark/40 to-plum-dark/10" />
        </section>

        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <!-- Only the avatar overlaps the cover; the name stays on the cream background. -->
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-6">
                <img
                    :src="avatarUrl(professional.profile_photo_url)"
                    :alt="professional.name"
                    class="-mt-16 h-32 w-32 shrink-0 rounded-full border-4 border-cream object-cover shadow-lg"
                >
                <div class="sm:pt-4">
                    <Link :href="route('professionals.index')" class="text-sm text-muted hover:text-gold">&larr; All professionals</Link>
                    <h1 class="mt-1 flex items-center gap-2 text-3xl font-semibold">
                        {{ professional.name }}
                        <span v-if="professional.is_verified" class="rounded-full bg-gold-soft px-3 py-1 text-xs font-semibold text-plum">Verified</span>
                    </h1>
                    <p class="mt-1 text-muted">
                        {{ professional.category }}<template v-if="professional.location"> · {{ professional.location }}</template>
                    </p>
                </div>
            </div>

            <div class="mt-10 grid gap-10 pb-20 lg:grid-cols-3">
                <div class="space-y-10 lg:col-span-2">
                    <!-- Quick facts -->
                    <div class="grid grid-cols-3 gap-3 rounded-2xl bg-white p-5 text-center ring-1 ring-plum/5">
                        <div>
                            <p class="text-xl font-semibold">
                                {{ professional.reviews_count ? professional.average_rating.toFixed(1) : '–' }}
                            </p>
                            <p class="text-xs text-muted">{{ professional.reviews_count }} reviews</p>
                        </div>
                        <div>
                            <p class="text-xl font-semibold">{{ professional.years_experience ?? '–' }}</p>
                            <p class="text-xs text-muted">Years experience</p>
                        </div>
                        <div>
                            <p class="text-xl font-semibold">{{ professional.followers_count }}</p>
                            <p class="text-xs text-muted">Followers</p>
                        </div>
                    </div>

                    <section v-if="professional.bio">
                        <h2 class="text-xl font-semibold">About</h2>
                        <p class="mt-3 whitespace-pre-line leading-relaxed text-ink/80">{{ professional.bio }}</p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold">Services</h2>
                        <ul v-if="services.length" class="mt-4 divide-y divide-plum/5 rounded-2xl bg-white ring-1 ring-plum/5">
                            <li v-for="s in services" :key="s.id + s.name" class="flex items-start justify-between gap-4 p-4">
                                <div>
                                    <p class="font-medium">{{ s.name }}</p>
                                    <p v-if="s.description" class="mt-0.5 text-sm text-muted">{{ s.description }}</p>
                                    <p v-if="formatDuration(s.duration)" class="mt-0.5 text-xs text-muted">{{ formatDuration(s.duration) }}</p>
                                </div>
                                <p class="shrink-0 font-semibold">{{ s.price > 0 ? formatPrice(s.price) : 'Ask for price' }}</p>
                            </li>
                        </ul>
                        <p v-else class="mt-3 text-muted">This professional hasn’t listed services yet.</p>
                    </section>

                    <section v-if="photos.length">
                        <h2 class="text-xl font-semibold">Portfolio</h2>
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <figure v-for="photo in photos" :key="photo.id" class="overflow-hidden rounded-xl bg-plum-soft">
                                <img :src="photo.photo_url" :alt="photo.caption || `Work by ${professional.name}`" class="aspect-square w-full object-cover" loading="lazy">
                                <figcaption v-if="photo.caption" class="px-3 py-2 text-xs text-muted">{{ photo.caption }}</figcaption>
                            </figure>
                        </div>
                    </section>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                    <div id="book" class="scroll-mt-24 rounded-2xl bg-plum p-6 text-white">
                        <h2 class="text-lg font-semibold">Book {{ firstName }}</h2>

                        <p v-if="!bookable.length" class="mt-2 text-sm text-white/70">
                            {{ firstName }} hasn’t opened bookings yet. Check back soon.
                        </p>

                        <template v-else-if="isOwnProfile">
                            <p class="mt-2 text-sm text-white/70">This is how clients see your profile.</p>
                            <Link :href="route('business.profile')" class="mt-4 inline-block rounded-xl bg-gold px-4 py-2.5 text-sm font-semibold text-plum-dark">
                                Edit services &amp; prices
                            </Link>
                        </template>

                        <template v-else-if="!user">
                            <p class="mt-2 text-sm text-white/70">Pick a time, at their place or yours. Sign in or create a free account to book.</p>
                            <Link :href="route('book.start', professional.id)" class="mt-4 block rounded-xl bg-gold px-4 py-3 text-center font-semibold text-plum-dark transition hover:brightness-110">
                                Book now
                            </Link>
                        </template>

                        <p v-else-if="user.role !== 'customer'" class="mt-2 text-sm text-white/70">
                            You’re signed in with a business account. Use a customer account to make bookings.
                        </p>

                        <form v-else class="mt-4 space-y-4 text-ink [&_label]:text-white/90" @submit.prevent="book">
                            <Field id="bk-service" label="Service" :error="booking.errors.professional_service_id">
                                <select id="bk-service" v-model="booking.professional_service_id" class="yl-input py-2.5" required>
                                    <option v-for="s in bookable" :key="s.id" :value="s.id">
                                        {{ s.name }} · {{ s.price > 0 ? formatPrice(s.price) : 'price on request' }}
                                    </option>
                                </select>
                            </Field>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                                <Field id="bk-date" label="Date" :error="booking.errors.booking_date">
                                    <input id="bk-date" v-model="booking.booking_date" type="date" :min="todayIso()" class="yl-input py-2.5" required>
                                </Field>
                                <Field id="bk-time" label="Time" :error="booking.errors.booking_time">
                                    <input id="bk-time" v-model="booking.booking_time" type="time" step="900" class="yl-input py-2.5" required>
                                </Field>
                            </div>
                            <fieldset>
                                <legend class="text-sm font-medium text-white/90">Where?</legend>
                                <div class="mt-1.5 grid grid-cols-2 gap-2 text-sm">
                                    <label
                                        v-for="opt in [{ v: 'booking', t: 'At their place' }, { v: 'request', t: 'Come to me' }]"
                                        :key="opt.v"
                                        :class="booking.type === opt.v ? 'bg-gold text-plum-dark' : 'bg-white/10 text-white hover:bg-white/20'"
                                        class="cursor-pointer rounded-xl px-3 py-2.5 text-center font-semibold transition"
                                    >
                                        <input v-model="booking.type" type="radio" :value="opt.v" class="sr-only">
                                        {{ opt.t }}
                                    </label>
                                </div>
                            </fieldset>
                            <Field v-if="booking.type === 'request'" id="bk-address" label="Your address" :error="booking.errors.customer_address">
                                <textarea id="bk-address" v-model="booking.customer_address" rows="2" class="yl-input py-2.5" placeholder="Street, area, landmark" required />
                            </Field>
                            <p v-if="booking.errors.booking" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ booking.errors.booking }}</p>
                            <button type="submit" class="w-full rounded-xl bg-gold px-4 py-3 font-semibold text-plum-dark transition hover:brightness-110 disabled:opacity-60" :disabled="booking.processing">
                                Request booking<template v-if="chosen?.price"> · {{ formatPrice(chosen.price) }}</template>
                            </button>
                            <p class="text-center text-xs text-white/60">{{ firstName }} will confirm your time. You can follow it under My bookings.</p>
                        </form>

                        <div class="mt-6 border-t border-white/10 pt-5">
                            <p class="text-xs text-white/60">Prefer the app?</p>
                            <div class="mt-3">
                                <StoreButtons :links="storeLinks.customer" />
                            </div>
                        </div>
                    </div>

                    <div v-if="hours.length" class="rounded-2xl bg-white p-6 ring-1 ring-plum/5">
                        <h2 class="font-semibold">Working hours</h2>
                        <dl class="mt-3 space-y-1.5 text-sm">
                            <div v-for="h in hours" :key="h.day" class="flex justify-between">
                                <dt class="text-muted">{{ h.day }}</dt>
                                <dd :class="h.text === 'Closed' ? 'text-muted' : 'font-medium'">{{ h.text }}</dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </div>
    </PublicLayout>
</template>
