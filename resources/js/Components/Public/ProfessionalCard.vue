<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { avatarUrl, categoryPhoto, formatPrice, startingPrice } from '@/yuluk';

const props = defineProps({
    professional: { type: Object, required: true },
});

const fromPrice = computed(() => startingPrice(props.professional.services));
</script>

<template>
    <Link
        :href="route('professionals.show', professional.id)"
        class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-plum/5 transition hover:-translate-y-0.5 hover:shadow-lg"
    >
        <div class="relative h-36 overflow-hidden">
            <img
                :src="categoryPhoto(professional.category, 600)"
                :alt="professional.category"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                loading="lazy"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-plum-dark/70 to-transparent" />
            <span class="absolute bottom-3 left-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-plum">
                {{ professional.category }}
            </span>
        </div>

        <div class="flex flex-1 flex-col p-4">
            <div class="flex items-center gap-3">
                <img
                    :src="avatarUrl(professional.profile_photo_url)"
                    :alt="professional.name"
                    class="-mt-10 h-14 w-14 rounded-full border-4 border-white object-cover shadow"
                    loading="lazy"
                >
                <div class="min-w-0">
                    <p class="flex items-center gap-1 truncate font-semibold">
                        {{ professional.name }}
                        <svg v-if="professional.is_verified" class="h-4 w-4 shrink-0 text-gold" viewBox="0 0 20 20" fill="currentColor" aria-label="Verified">
                            <path fill-rule="evenodd" d="M10 1.5l2.4 1.7 2.9-.2.9 2.8 2.4 1.7-.9 2.8.9 2.8-2.4 1.7-.9 2.8-2.9-.2L10 18.5l-2.4-1.7-2.9.2-.9-2.8L1.4 12.5l.9-2.8-.9-2.8 2.4-1.7.9-2.8 2.9.2L10 1.5zm3.7 6.2a.75.75 0 00-1.1-1l-3.6 3.9-1.6-1.6a.75.75 0 10-1.1 1.1l2.2 2.2c.3.3.8.3 1.1 0l4.1-4.6z" clip-rule="evenodd" />
                        </svg>
                    </p>
                    <p v-if="professional.location" class="truncate text-sm text-muted">{{ professional.location }}</p>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-plum/5 pt-3 text-sm">
                <span v-if="professional.reviews_count" class="flex items-center gap-1 font-medium">
                    <svg class="h-4 w-4 text-gold" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z" />
                    </svg>
                    {{ professional.average_rating.toFixed(1) }}
                    <span class="text-muted">({{ professional.reviews_count }})</span>
                </span>
                <span v-else class="rounded-full bg-gold-soft px-2.5 py-0.5 text-xs font-semibold text-plum">New on YuLuk</span>

                <span v-if="fromPrice" class="text-muted">
                    from <span class="font-semibold text-ink">{{ formatPrice(fromPrice) }}</span>
                </span>
            </div>
        </div>
    </Link>
</template>
