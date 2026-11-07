<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import ProfessionalCard from '@/Components/Public/ProfessionalCard.vue';

const props = defineProps({
    professionals: { type: Array, required: true },
    pagination: { type: Object, required: true },
    categories: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.q);

const activeCategory = computed(() => props.categories.find((c) => c.id === props.filters.service));

function applyFilters(overrides = {}) {
    const params = { service: props.filters.service, q: search.value, ...overrides };
    Object.keys(params).forEach((k) => (params[k] === null || params[k] === '') && delete params[k]);
    router.get(route('professionals.index'), params, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <Head :title="activeCategory ? `${activeCategory.name} professionals` : 'Find a professional'" />

    <PublicLayout>
        <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
            <h1 class="text-3xl font-semibold">
                {{ activeCategory ? `${activeCategory.name} professionals` : 'Find a professional' }}
            </h1>
            <p class="mt-2 text-muted">
                {{ pagination.total }} {{ pagination.total === 1 ? 'professional' : 'professionals' }}
                <template v-if="filters.q"> matching “{{ filters.q }}”</template>
            </p>

            <form class="mt-6 flex max-w-xl gap-2" @submit.prevent="applyFilters()">
                <label for="browse-search" class="sr-only">Search by name, service or area</label>
                <input
                    id="browse-search"
                    v-model="search"
                    type="search"
                    placeholder="Search by name, service or area"
                    class="min-w-0 flex-1 rounded-xl border-plum/15 bg-white px-4 py-3 placeholder:text-muted focus:border-gold focus:ring-gold"
                >
                <button type="submit" class="rounded-xl bg-plum px-5 py-3 font-semibold text-white transition hover:bg-plum-dark">
                    Search
                </button>
            </form>

            <div class="mt-5 flex gap-2 overflow-x-auto pb-2">
                <button
                    type="button"
                    :class="!filters.service ? 'bg-plum text-white' : 'bg-white text-ink ring-1 ring-plum/10 hover:ring-gold'"
                    class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition"
                    @click="applyFilters({ service: null })"
                >
                    All
                </button>
                <button
                    v-for="c in categories"
                    :key="c.id"
                    type="button"
                    :class="filters.service === c.id ? 'bg-plum text-white' : 'bg-white text-ink ring-1 ring-plum/10 hover:ring-gold'"
                    class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition"
                    @click="applyFilters({ service: c.id })"
                >
                    {{ c.name }}
                </button>
            </div>

            <div v-if="professionals.length" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <ProfessionalCard v-for="p in professionals" :key="p.id" :professional="p" />
            </div>

            <div v-else class="mt-12 rounded-2xl bg-white p-10 text-center ring-1 ring-plum/5">
                <p class="text-lg font-semibold">No professionals found</p>
                <p class="mt-2 text-muted">Try another service or a different search.</p>
                <button
                    type="button"
                    class="mt-5 rounded-full bg-plum px-5 py-2 text-sm font-semibold text-white"
                    @click="search = ''; applyFilters({ service: null, q: null })"
                >
                    Clear filters
                </button>
            </div>

            <nav v-if="pagination.last > 1" class="mt-10 flex items-center justify-center gap-4 text-sm" aria-label="Pagination">
                <Link
                    v-if="pagination.prev"
                    :href="pagination.prev"
                    preserve-scroll
                    class="rounded-full bg-white px-4 py-2 font-medium ring-1 ring-plum/10 hover:ring-gold"
                >
                    &larr; Previous
                </Link>
                <span class="text-muted">Page {{ pagination.current }} of {{ pagination.last }}</span>
                <Link
                    v-if="pagination.next"
                    :href="pagination.next"
                    preserve-scroll
                    class="rounded-full bg-white px-4 py-2 font-medium ring-1 ring-plum/10 hover:ring-gold"
                >
                    Next &rarr;
                </Link>
            </nav>
        </section>
    </PublicLayout>
</template>
