<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StoreButtons from '@/Components/Public/StoreButtons.vue';
import { avatarUrl, categoryPhoto, formatDuration, formatPrice } from '@/yuluk';

const props = defineProps({
    professional: { type: Object, required: true },
    storeLinks: { type: Object, required: true },
});

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
                    <div class="rounded-2xl bg-plum p-6 text-white">
                        <h2 class="text-lg font-semibold">Book {{ professional.name.split(' ')[0] }}</h2>
                        <p class="mt-2 text-sm text-white/70">
                            Booking happens in the YuLuk app: visit them, or request them to come to you.
                        </p>
                        <div class="mt-5">
                            <StoreButtons :links="storeLinks.customer" />
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
