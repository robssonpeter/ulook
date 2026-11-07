<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import BusinessTabs from '@/Components/Public/BusinessTabs.vue';
import Field from '@/Components/Public/Field.vue';
import { formatDuration, formatPrice } from '@/yuluk';

const props = defineProps({
    professional: { type: Object, required: true },
    catalogue: { type: Array, required: true },
    hours: { type: Array, required: true },
    services: { type: Array, required: true },
});

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const details = useForm({
    location: props.professional.location ?? '',
    bio: props.professional.bio ?? '',
    years_experience: props.professional.years_experience ?? '',
});

// Monday-first rows; the API stores 0 = Sunday.
const hoursForm = useForm({
    hours: [...props.hours].sort((a, b) => ((a.day_of_week + 6) % 7) - ((b.day_of_week + 6) % 7)),
});

const editing = ref(null);
const editForm = useForm({ name: '', price: '', duration_minutes: '', is_active: true });

function startEdit(s) {
    editing.value = s.id;
    editForm.defaults({ name: s.name, price: s.price, duration_minutes: s.duration_minutes, is_active: s.is_active });
    editForm.reset();
    editForm.clearErrors();
}

function saveEdit(s) {
    editForm.patch(route('business.services.update', s.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}

function toggleActive(s) {
    router.patch(
        route('business.services.update', s.id),
        { name: s.name, price: s.price, duration_minutes: s.duration_minutes, is_active: !s.is_active },
        { preserveScroll: true },
    );
}

const adding = ref(false);
const addForm = useForm({ service_id: '', name: '', price: '', duration_minutes: 60 });

function onCategoryPicked() {
    if (!addForm.name) addForm.name = props.services.find((s) => s.id === Number(addForm.service_id))?.name ?? '';
}

function addService() {
    addForm.post(route('business.services.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addForm.reset();
            adding.value = false;
        },
    });
}
</script>

<template>
    <Head title="Profile & services" />

    <PublicLayout>
        <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
            <h1 class="text-3xl font-semibold">My business</h1>
            <div class="mt-6">
                <BusinessTabs :profile-id="professional.id" />
            </div>

            <!-- Details -->
            <form class="mt-8 space-y-5 rounded-2xl bg-white p-6 ring-1 ring-plum/5" @submit.prevent="details.put(route('business.profile.update'), { preserveScroll: true })">
                <h2 class="text-lg font-semibold">Profile</h2>
                <div class="grid gap-5 sm:grid-cols-[1fr_12rem]">
                    <Field id="location" label="Location" :error="details.errors.location">
                        <input id="location" v-model="details.location" type="text" class="yl-input" required>
                    </Field>
                    <Field id="years" label="Years of experience" :error="details.errors.years_experience">
                        <input id="years" v-model="details.years_experience" type="number" min="0" max="60" class="yl-input">
                    </Field>
                </div>
                <Field id="bio" label="Bio" :error="details.errors.bio">
                    <textarea id="bio" v-model="details.bio" rows="3" class="yl-input" />
                </Field>
                <button type="submit" class="yl-btn text-sm" :disabled="details.processing || !details.isDirty">Save profile</button>
            </form>

            <!-- Services -->
            <div class="mt-8 rounded-2xl bg-white p-6 ring-1 ring-plum/5">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">Services & prices</h2>
                    <button v-if="!adding" type="button" class="yl-btn-ghost text-sm" @click="adding = true">+ Add service</button>
                </div>

                <form v-if="adding" class="mt-4 grid gap-4 rounded-xl bg-cream p-4 sm:grid-cols-2" @submit.prevent="addService">
                    <Field id="new-cat" label="Category" :error="addForm.errors.service_id">
                        <select id="new-cat" v-model="addForm.service_id" class="yl-input" required @change="onCategoryPicked">
                            <option value="" disabled>Choose…</option>
                            <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </Field>
                    <Field id="new-name" label="Name clients see" :error="addForm.errors.name">
                        <input id="new-name" v-model="addForm.name" type="text" class="yl-input" placeholder="e.g. Knotless braids" required>
                    </Field>
                    <Field id="new-price" label="Price (TZS)" :error="addForm.errors.price">
                        <input id="new-price" v-model="addForm.price" type="number" min="0" step="500" class="yl-input" required>
                    </Field>
                    <Field id="new-dur" label="Duration (minutes)" :error="addForm.errors.duration_minutes">
                        <input id="new-dur" v-model="addForm.duration_minutes" type="number" min="5" max="720" step="5" class="yl-input" required>
                    </Field>
                    <div class="flex gap-3 sm:col-span-2">
                        <button type="submit" class="yl-btn text-sm" :disabled="addForm.processing">Add service</button>
                        <button type="button" class="yl-btn-ghost text-sm" @click="adding = false">Cancel</button>
                    </div>
                </form>

                <p v-if="!catalogue.length" class="mt-4 text-sm text-muted">No services yet. Add your first one so clients can book.</p>

                <ul class="mt-4 divide-y divide-plum/5">
                    <li v-for="s in catalogue" :key="s.id" class="py-4">
                        <form v-if="editing === s.id" class="grid gap-3 sm:grid-cols-[1fr_9rem_8rem_auto]" @submit.prevent="saveEdit(s)">
                            <Field :id="`name-${s.id}`" label="Name" :error="editForm.errors.name">
                                <input :id="`name-${s.id}`" v-model="editForm.name" type="text" class="yl-input py-2.5" required>
                            </Field>
                            <Field :id="`price-${s.id}`" label="Price (TZS)" :error="editForm.errors.price">
                                <input :id="`price-${s.id}`" v-model="editForm.price" type="number" min="0" step="500" class="yl-input py-2.5" required>
                            </Field>
                            <Field :id="`dur-${s.id}`" label="Minutes" :error="editForm.errors.duration_minutes">
                                <input :id="`dur-${s.id}`" v-model="editForm.duration_minutes" type="number" min="5" max="720" step="5" class="yl-input py-2.5" required>
                            </Field>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="yl-btn py-2.5 text-sm" :disabled="editForm.processing">Save</button>
                                <button type="button" class="yl-btn-ghost text-sm" @click="editing = null">Cancel</button>
                            </div>
                        </form>
                        <div v-else class="flex flex-wrap items-center justify-between gap-3">
                            <div :class="{ 'opacity-50': !s.is_active }">
                                <p class="font-medium">{{ s.name }} <span class="text-xs text-muted">· {{ s.category }}</span></p>
                                <p class="text-sm text-muted">
                                    {{ s.price > 0 ? formatPrice(s.price) : 'No price set' }}<template v-if="formatDuration(s.duration_minutes)"> · {{ formatDuration(s.duration_minutes) }}</template>
                                    <template v-if="!s.is_active"> · hidden from clients</template>
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="yl-btn-ghost py-2 text-sm" @click="startEdit(s)">Edit</button>
                                <button type="button" class="yl-btn-ghost py-2 text-sm" @click="toggleActive(s)">{{ s.is_active ? 'Hide' : 'Show' }}</button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Hours -->
            <form class="mt-8 rounded-2xl bg-white p-6 ring-1 ring-plum/5" @submit.prevent="hoursForm.put(route('business.hours.update'), { preserveScroll: true })">
                <h2 class="text-lg font-semibold">Working hours</h2>
                <p v-if="Object.keys(hoursForm.errors).length" class="mt-2 text-sm text-red-600">Check the opening and closing times.</p>
                <div class="mt-4 space-y-3">
                    <div v-for="h in hoursForm.hours" :key="h.day_of_week" class="grid grid-cols-[6.5rem_1fr] items-center gap-3 sm:grid-cols-[8rem_7rem_1fr]">
                        <span class="text-sm font-medium">{{ DAYS[h.day_of_week] }}</span>
                        <label class="flex items-center gap-2 text-sm text-muted">
                            <input v-model="h.is_closed" type="checkbox" class="rounded border-plum/30 text-plum focus:ring-gold">
                            Closed
                        </label>
                        <div v-if="!h.is_closed" class="col-span-2 flex items-center gap-2 sm:col-span-1">
                            <input v-model="h.open_time" type="time" class="yl-input w-[8.5rem] px-3 py-2" :aria-label="`${DAYS[h.day_of_week]} opening time`" required>
                            <span class="text-muted">to</span>
                            <input v-model="h.close_time" type="time" class="yl-input w-[8.5rem] px-3 py-2" :aria-label="`${DAYS[h.day_of_week]} closing time`" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="yl-btn mt-6 text-sm" :disabled="hoursForm.processing">Save hours</button>
            </form>
        </section>
    </PublicLayout>
</template>
