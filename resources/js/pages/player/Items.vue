<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PackageSearch, Plus, Star, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { selectClass } from '@/components/public/options';
import QuantityStepper from '@/components/public/QuantityStepper.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { http, HttpError } from '@/lib/http';
import { store as storeGoal } from '@/routes/api/goals';
import { destroy, update } from '@/routes/api/tracked-items';
import { index as itemsIndex, show as showItem } from '@/routes/items';
import type { Option } from '@/types/game';

type TrackedRow = {
    id: number;
    slug: string;
    name: string;
    category: string | null;
    intent: string | null;
    quantity_needed: number;
    quantity_owned: number;
    is_favorite: boolean;
    note: string | null;
};

const props = defineProps<{
    tracked: TrackedRow[];
    goals: {
        id: number;
        name: string;
        kind: string;
        ingredients: { id: number; name: string; quantity: number }[];
    }[];
    intents: Option[];
}>();

const rows = ref<TrackedRow[]>(props.tracked.map((r) => ({ ...r })));
watch(
    () => props.tracked,
    (tracked) => {
        rows.value = tracked.map((r) => ({ ...r }));
    },
);

const busy = ref(new Set<number>());
const addingGoal = ref<number | null>(null);
const goalQuery = ref('');

const filteredGoals = computed(() => {
    const needle = goalQuery.value.trim().toLowerCase();

    return needle
        ? props.goals.filter(
              (g) =>
                  g.name.toLowerCase().includes(needle) ||
                  g.kind.toLowerCase().includes(needle),
          )
        : props.goals;
});

function remaining(row: TrackedRow): number {
    return Math.max(row.quantity_needed - row.quantity_owned, 0);
}

async function save(row: TrackedRow): Promise<void> {
    busy.value.add(row.id);

    try {
        await http('put', update(row.id).url, {
            intent: row.intent,
            quantity_needed: row.quantity_needed,
            quantity_owned: row.quantity_owned,
            is_favorite: row.is_favorite,
        });
    } catch (e) {
        toast.error(
            e instanceof HttpError
                ? e.firstError()
                : `Could not save ${row.name}.`,
        );
    } finally {
        busy.value.delete(row.id);
    }
}

function toggleFavorite(row: TrackedRow): void {
    row.is_favorite = !row.is_favorite;
    void save(row);
}

async function remove(row: TrackedRow): Promise<void> {
    busy.value.add(row.id);

    try {
        await http('delete', destroy(row.id).url);
        rows.value = rows.value.filter((r) => r.id !== row.id);
        toast.success(`Stopped tracking ${row.name}.`);
    } catch (e) {
        toast.error(
            e instanceof HttpError
                ? e.firstError()
                : `Could not remove ${row.name}.`,
        );
    } finally {
        busy.value.delete(row.id);
    }
}

async function addGoal(id: number): Promise<void> {
    addingGoal.value = id;

    try {
        const response = await http<{ message: string }>(
            'post',
            storeGoal(id).url,
        );
        toast.success(response.message);
        router.reload({ only: ['tracked'] });
    } catch (e) {
        toast.error(
            e instanceof HttpError ? e.firstError() : 'Could not add goal.',
        );
    } finally {
        addingGoal.value = null;
    }
}
</script>

<template>
    <Head title="What should I keep?">
        <meta
            name="description"
            content="Track the Active Matter items you need, own and want to keep."
        />
    </Head>

    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-6">
            <h1
                class="font-display text-3xl font-semibold tracking-wide uppercase"
            >
                What should I keep?
            </h1>
            <p class="mt-1 max-w-2xl text-muted-foreground">
                Your tracked items. Set how many you need and own; anything
                still missing shows as remaining.
            </p>
        </header>

        <section aria-labelledby="tracked-title" class="mb-10">
            <h2 id="tracked-title" class="sr-only">Tracked items</h2>

            <div
                v-if="rows.length"
                class="overflow-x-auto rounded-lg border bg-card"
            >
                <table class="w-full min-w-[40rem] text-sm">
                    <thead
                        class="border-b bg-muted/30 text-left text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        <tr>
                            <th scope="col" class="px-3 py-2 font-medium">
                                Item
                            </th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                Intent
                            </th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                Need
                            </th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                Own
                            </th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                Remaining
                            </th>
                            <th scope="col" class="px-3 py-2 font-medium">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="row in rows" :key="row.id">
                            <td class="px-3 py-2">
                                <Link
                                    :href="showItem(row.slug)"
                                    class="font-medium text-primary hover:underline"
                                    >{{ row.name }}</Link
                                >
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{
                                        row.category ?? 'Unknown category'
                                    }}</span
                                >
                            </td>
                            <td class="px-3 py-2">
                                <select
                                    v-model="row.intent"
                                    :class="selectClass"
                                    class="w-36"
                                    :aria-label="`Intent for ${row.name}`"
                                    @change="save(row)"
                                >
                                    <option :value="null">Not set</option>
                                    <option
                                        v-for="option in intents"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <QuantityStepper
                                    v-model="row.quantity_needed"
                                    :label="`quantity of ${row.name} needed`"
                                    @commit="save(row)"
                                />
                            </td>
                            <td class="px-3 py-2">
                                <QuantityStepper
                                    v-model="row.quantity_owned"
                                    :label="`quantity of ${row.name} owned`"
                                    @commit="save(row)"
                                />
                            </td>
                            <td
                                class="px-3 py-2 font-semibold tabular-nums"
                                :class="
                                    remaining(row) > 0
                                        ? 'text-warning'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ remaining(row) }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        :aria-pressed="row.is_favorite"
                                        :aria-label="
                                            row.is_favorite
                                                ? `Unfavorite ${row.name}`
                                                : `Favorite ${row.name}`
                                        "
                                        @click="toggleFavorite(row)"
                                    >
                                        <Star
                                            class="size-4"
                                            :class="{
                                                'fill-yellow-400 text-yellow-400':
                                                    row.is_favorite,
                                            }"
                                        />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        :aria-label="`Stop tracking ${row.name}`"
                                        :disabled="busy.has(row.id)"
                                        @click="remove(row)"
                                    >
                                        <Trash2
                                            class="size-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="rounded-lg border border-dashed p-8 text-center">
                <PackageSearch
                    class="mx-auto mb-2 size-8 text-muted-foreground"
                />
                <p class="font-medium">You aren’t tracking any items yet.</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                    Open any item page and use “My tracking” to mark it as
                    needed, or add a whole recipe or upgrade below.
                </p>
                <Button as-child variant="secondary" class="mt-4">
                    <Link :href="itemsIndex()">Browse items</Link>
                </Button>
            </div>
        </section>

        <section aria-labelledby="goals-title">
            <h2
                id="goals-title"
                class="mb-1 font-display text-xl font-semibold tracking-wide uppercase"
            >
                Add goal
            </h2>
            <p class="mb-3 text-sm text-muted-foreground">
                Adds every ingredient of a recipe or upgrade to your needs.
            </p>
            <template v-if="goals.length">
                <Input
                    v-model="goalQuery"
                    type="search"
                    placeholder="Filter recipes and upgrades…"
                    aria-label="Filter recipes and upgrades"
                    class="mb-3 max-w-sm"
                    autocomplete="off"
                />
                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <li
                        v-for="goal in filteredGoals"
                        :key="goal.id"
                        class="flex flex-col rounded-lg border bg-card p-4"
                    >
                        <p class="text-xs text-muted-foreground uppercase">
                            {{ goal.kind }}
                        </p>
                        <h3 class="font-medium">{{ goal.name }}</h3>
                        <ul
                            v-if="goal.ingredients.length"
                            class="mt-2 mb-3 space-y-0.5 text-sm"
                        >
                            <li
                                v-for="ingredient in goal.ingredients"
                                :key="ingredient.id"
                            >
                                <span class="text-muted-foreground tabular-nums"
                                    >{{ ingredient.quantity }}×</span
                                >
                                {{ ingredient.name }}
                            </li>
                        </ul>
                        <p
                            v-else
                            class="mt-2 mb-3 text-sm text-muted-foreground"
                        >
                            Ingredients unknown.
                        </p>
                        <Button
                            variant="secondary"
                            size="sm"
                            class="mt-auto"
                            :disabled="
                                addingGoal !== null || !goal.ingredients.length
                            "
                            @click="addGoal(goal.id)"
                        >
                            <Plus class="size-4" /> Add ingredients to needs
                        </Button>
                    </li>
                </ul>
                <p
                    v-if="!filteredGoals.length"
                    class="text-sm text-muted-foreground"
                >
                    No goals match.
                </p>
            </template>
            <p
                v-else
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                No recipes or upgrades have been documented yet.
            </p>
        </section>
    </div>
</template>
