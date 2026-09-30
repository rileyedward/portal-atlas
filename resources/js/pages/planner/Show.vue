<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    DoorOpen,
    Info,
    Map as MapIcon,
    Route,
    Search,
    ShieldAlert,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { selectClass } from '@/components/public/options';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { http, HttpError } from '@/lib/http';
import { plan } from '@/routes/api/planner';
import { show as showItem } from '@/routes/items';
import { show as showMap } from '@/routes/maps';
import { show as showObjective } from '@/routes/objectives';

type PlannedMarker = {
    id: number;
    name: string;
    x: number | null;
    y: number | null;
    type: string;
};

type PlanResult = {
    stops: {
        marker: PlannedMarker;
        items: string[];
        objectives: string[];
        risks: string[];
    }[];
    extract: PlannedMarker | null;
    extract_options: PlannedMarker[];
    missing_items: number[];
    missing_objectives: number[];
};

const props = defineProps<{
    maps: {
        id: number;
        slug: string;
        name: string;
        variants: { key: string; label: string; count: number }[];
    }[];
    items: { id: number; slug: string; name: string }[];
    objectives: {
        id: number;
        slug: string;
        name: string;
        map_id: number | null;
    }[];
    neededItemIds: number[];
}>();

const MAX_ITEMS = 30;
const MAX_OBJECTIVES = 20;

const page = usePage();
const signedIn = computed(() => page.props.auth.user !== null);

const mapSlug = ref(props.maps[0]?.slug ?? '');
const availableItemIds = new Set(props.items.map((i) => i.id));
const selectedItems = ref<number[]>(
    props.neededItemIds
        .filter((id) => availableItemIds.has(id))
        .slice(0, MAX_ITEMS),
);
const selectedObjectives = ref<number[]>([]);
const itemQuery = ref('');
const objectiveQuery = ref('');

const result = ref<PlanResult | null>(null);
const resultMap = ref<{ slug: string; name: string } | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

const currentMap = computed(
    () => props.maps.find((m) => m.slug === mapSlug.value) ?? null,
);

const variantKey = ref<string | null>(null);

watch(
    currentMap,
    (map) => {
        const keys = map?.variants.map((v) => v.key) ?? [];
        variantKey.value = keys.includes('regular')
            ? 'regular'
            : (keys[0] ?? null);
    },
    { immediate: true },
);

const itemsById = computed(() => new Map(props.items.map((i) => [i.id, i])));
const objectivesById = computed(
    () => new Map(props.objectives.map((o) => [o.id, o])),
);

const filteredItems = computed(() => {
    const needle = itemQuery.value.trim().toLowerCase();

    return needle
        ? props.items.filter((i) => i.name.toLowerCase().includes(needle))
        : props.items;
});

const mapObjectives = computed(() =>
    props.objectives.filter(
        (o) => o.map_id === null || o.map_id === currentMap.value?.id,
    ),
);

const filteredObjectives = computed(() => {
    const needle = objectiveQuery.value.trim().toLowerCase();

    return needle
        ? mapObjectives.value.filter((o) =>
              o.name.toLowerCase().includes(needle),
          )
        : mapObjectives.value;
});

// Objectives tied to another map can't be planned on this one.
watch(mapSlug, () => {
    const allowed = new Set(mapObjectives.value.map((o) => o.id));
    selectedObjectives.value = selectedObjectives.value.filter((id) =>
        allowed.has(id),
    );
    result.value = null;
});

function toggle(list: number[], id: number, max: number): void {
    const index = list.indexOf(id);

    if (index === -1) {
        if (list.length < max) {
            list.push(id);
        }
    } else {
        list.splice(index, 1);
    }
}

const canPlan = computed(
    () =>
        !!currentMap.value &&
        (selectedItems.value.length > 0 || selectedObjectives.value.length > 0),
);

async function submit(): Promise<void> {
    if (!canPlan.value || !currentMap.value) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        result.value = await http<PlanResult>('post', plan().url, {
            map: currentMap.value.slug,
            variant: variantKey.value,
            items: selectedItems.value,
            objectives: selectedObjectives.value,
        });
        resultMap.value = {
            slug: currentMap.value.slug,
            name: currentMap.value.name,
        };
    } catch (e) {
        result.value = null;
        error.value =
            e instanceof HttpError
                ? e.firstError()
                : 'Could not plan the raid.';
    } finally {
        loading.value = false;
    }
}

function distance(
    a: PlannedMarker | null | undefined,
    b: PlannedMarker | null | undefined,
): number | null {
    if (
        !a ||
        !b ||
        a.x === null ||
        a.y === null ||
        b.x === null ||
        b.y === null
    ) {
        return null;
    }

    return Math.hypot(a.x - b.x, a.y - b.y);
}

const extractDistance = computed(() => {
    const stops = result.value?.stops ?? [];

    return distance(stops[stops.length - 1]?.marker, result.value?.extract);
});

const otherExtracts = computed(() =>
    (result.value?.extract_options ?? []).filter(
        (e) => e.id !== result.value?.extract?.id,
    ),
);
</script>

<template>
    <SeoHead />

    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-6">
            <h1
                class="font-display text-3xl font-semibold tracking-wide uppercase"
            >
                Raid planner
            </h1>
            <p class="mt-1 max-w-2xl text-muted-foreground">
                Pick a map and what you want out of the raid. The planner orders
                documented locations nearest-first and ends at the closest known
                extraction.
            </p>
        </header>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,26rem)_1fr]">
            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <label for="planner-map" class="text-sm font-medium"
                        >Map</label
                    >
                    <select
                        id="planner-map"
                        v-model="mapSlug"
                        :class="selectClass"
                        required
                    >
                        <option
                            v-for="map in maps"
                            :key="map.slug"
                            :value="map.slug"
                        >
                            {{ map.name }}
                        </option>
                    </select>
                    <p
                        v-if="!maps.length"
                        class="text-sm text-muted-foreground"
                    >
                        No maps have been published yet.
                    </p>
                </div>

                <div
                    v-if="(currentMap?.variants.length ?? 0) > 1"
                    class="space-y-1.5"
                >
                    <label for="planner-variant" class="text-sm font-medium"
                        >Raid variant</label
                    >
                    <select
                        id="planner-variant"
                        v-model="variantKey"
                        :class="selectClass"
                    >
                        <option
                            v-for="v in currentMap?.variants ?? []"
                            :key="v.key"
                            :value="v.key"
                        >
                            {{ v.label }}
                        </option>
                    </select>
                </div>

                <fieldset class="space-y-2">
                    <legend
                        class="flex w-full items-baseline justify-between text-sm font-medium"
                    >
                        Items
                        <span class="text-xs font-normal text-muted-foreground">
                            {{ selectedItems.length }}/{{ MAX_ITEMS }} selected
                        </span>
                    </legend>
                    <p
                        v-if="signedIn && neededItemIds.length"
                        class="text-xs text-muted-foreground"
                    >
                        Items you marked as “Need” are preselected.
                    </p>
                    <ul
                        v-if="selectedItems.length"
                        class="flex flex-wrap gap-1.5"
                        aria-label="Selected items"
                    >
                        <li v-for="id in selectedItems" :key="id">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full border border-primary/40 bg-primary/10 px-2 py-0.5 text-xs hover:bg-primary/20 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                :aria-label="`Remove ${itemsById.get(id)?.name}`"
                                @click="toggle(selectedItems, id, MAX_ITEMS)"
                            >
                                {{ itemsById.get(id)?.name }}
                                <X class="size-3" />
                            </button>
                        </li>
                    </ul>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="itemQuery"
                            type="search"
                            placeholder="Search items…"
                            class="pl-9"
                            aria-label="Search items"
                            autocomplete="off"
                        />
                    </div>
                    <div
                        class="max-h-64 overflow-y-auto rounded-md border bg-card"
                    >
                        <label
                            v-for="item in filteredItems"
                            :key="item.id"
                            class="flex cursor-pointer items-center gap-2 px-3 py-1.5 text-sm hover:bg-accent/30"
                        >
                            <input
                                type="checkbox"
                                class="accent-[var(--primary)]"
                                :checked="selectedItems.includes(item.id)"
                                :disabled="
                                    !selectedItems.includes(item.id) &&
                                    selectedItems.length >= MAX_ITEMS
                                "
                                @change="
                                    toggle(selectedItems, item.id, MAX_ITEMS)
                                "
                            />
                            {{ item.name }}
                        </label>
                        <p
                            v-if="!filteredItems.length"
                            class="p-3 text-sm text-muted-foreground"
                        >
                            {{
                                items.length
                                    ? 'No items match.'
                                    : 'No items have mapped locations yet.'
                            }}
                        </p>
                    </div>
                </fieldset>

                <fieldset class="space-y-2">
                    <legend
                        class="flex w-full items-baseline justify-between text-sm font-medium"
                    >
                        Objectives
                        <span class="text-xs font-normal text-muted-foreground">
                            {{ selectedObjectives.length }}/{{ MAX_OBJECTIVES }}
                            selected
                        </span>
                    </legend>
                    <div v-if="mapObjectives.length > 8" class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="objectiveQuery"
                            type="search"
                            placeholder="Search objectives…"
                            class="pl-9"
                            aria-label="Search objectives"
                            autocomplete="off"
                        />
                    </div>
                    <div
                        class="max-h-52 overflow-y-auto rounded-md border bg-card"
                    >
                        <label
                            v-for="objective in filteredObjectives"
                            :key="objective.id"
                            class="flex cursor-pointer items-center gap-2 px-3 py-1.5 text-sm hover:bg-accent/30"
                        >
                            <input
                                type="checkbox"
                                class="accent-[var(--primary)]"
                                :checked="
                                    selectedObjectives.includes(objective.id)
                                "
                                :disabled="
                                    !selectedObjectives.includes(
                                        objective.id,
                                    ) &&
                                    selectedObjectives.length >= MAX_OBJECTIVES
                                "
                                @change="
                                    toggle(
                                        selectedObjectives,
                                        objective.id,
                                        MAX_OBJECTIVES,
                                    )
                                "
                            />
                            {{ objective.name }}
                        </label>
                        <p
                            v-if="!filteredObjectives.length"
                            class="p-3 text-sm text-muted-foreground"
                        >
                            No objectives with mapped locations for this map.
                        </p>
                    </div>
                </fieldset>

                <p v-if="error" class="text-sm text-destructive" role="alert">
                    {{ error }}
                </p>

                <Button
                    type="submit"
                    class="w-full"
                    :disabled="!canPlan || loading"
                >
                    <Spinner v-if="loading" />
                    <Route v-else class="size-4" />
                    Plan raid
                </Button>
            </form>

            <section aria-labelledby="plan-title" aria-live="polite">
                <h2 id="plan-title" class="sr-only">Planned route</h2>

                <div
                    v-if="!result"
                    class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                >
                    <Route class="mx-auto mb-2 size-8 opacity-50" />
                    Choose items or objectives and press “Plan raid”.
                </div>

                <div v-else-if="resultMap" class="space-y-5">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <h3
                            class="font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            {{ resultMap.name }} plan
                        </h3>
                        <Button as-child variant="secondary">
                            <Link :href="showMap(resultMap.slug)"
                                ><MapIcon class="size-4" /> Open on map</Link
                            >
                        </Button>
                    </div>

                    <p
                        class="flex gap-2 rounded-md border bg-card p-3 text-sm text-muted-foreground"
                    >
                        <Info class="mt-0.5 size-4 shrink-0" />
                        Distances are straight lines on the map, measured as a
                        percentage of the map’s size — not real walking paths.
                        Walls, elevation and hazards are not accounted for.
                    </p>

                    <ol v-if="result.stops.length" class="space-y-3">
                        <li
                            v-for="(stop, index) in result.stops"
                            :key="stop.marker.id"
                            class="flex gap-3 rounded-lg border bg-card p-4"
                        >
                            <span
                                class="grid size-7 shrink-0 place-items-center rounded-full bg-primary text-sm font-semibold text-primary-foreground"
                            >
                                {{ index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1 space-y-1.5">
                                <div
                                    class="flex flex-wrap items-baseline justify-between gap-x-3"
                                >
                                    <Link
                                        :href="
                                            showMap(resultMap.slug, {
                                                query: {
                                                    marker: stop.marker.id,
                                                },
                                            })
                                        "
                                        class="font-medium text-primary hover:underline"
                                    >
                                        {{ stop.marker.name }}
                                    </Link>
                                    <span class="text-xs text-muted-foreground">
                                        {{ stop.marker.type }}
                                        <template
                                            v-if="
                                                index > 0 &&
                                                distance(
                                                    result.stops[index - 1]
                                                        .marker,
                                                    stop.marker,
                                                ) !== null
                                            "
                                        >
                                            ·
                                            {{
                                                distance(
                                                    result.stops[index - 1]
                                                        .marker,
                                                    stop.marker,
                                                )!.toFixed(1)
                                            }}% from previous
                                        </template>
                                    </span>
                                </div>
                                <p v-if="stop.items.length" class="text-sm">
                                    <span class="text-muted-foreground"
                                        >Items: </span
                                    >{{ stop.items.join(', ') }}
                                </p>
                                <p
                                    v-if="stop.objectives.length"
                                    class="text-sm"
                                >
                                    <span class="text-muted-foreground"
                                        >Objectives: </span
                                    >{{ stop.objectives.join(', ') }}
                                </p>
                                <p
                                    v-if="stop.risks.length"
                                    class="flex gap-1.5 text-sm text-destructive"
                                >
                                    <ShieldAlert
                                        class="mt-0.5 size-4 shrink-0"
                                    />
                                    <span
                                        >Nearby:
                                        {{ stop.risks.join(', ') }}</span
                                    >
                                </p>
                            </div>
                        </li>
                    </ol>
                    <p
                        v-else
                        class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        None of your picks have a mapped location on
                        {{ resultMap.name }}.
                    </p>

                    <div
                        class="rounded-lg border border-success/40 bg-success/10 p-4"
                    >
                        <p
                            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Extract
                        </p>
                        <template v-if="result.extract">
                            <Link
                                :href="
                                    showMap(resultMap.slug, {
                                        query: { marker: result.extract.id },
                                    })
                                "
                                class="mt-1 flex items-center gap-2 font-medium text-success hover:underline"
                            >
                                <DoorOpen class="size-5" />
                                {{ result.extract.name }}
                            </Link>
                            <p class="text-xs text-muted-foreground">
                                {{ result.extract.type }}
                                <template v-if="extractDistance !== null">
                                    · {{ extractDistance.toFixed(1) }}% from the
                                    last stop</template
                                >
                            </p>
                        </template>
                        <p v-else class="mt-1 text-sm text-muted-foreground">
                            No extraction point on this map has a documented
                            position yet.
                        </p>
                    </div>

                    <div v-if="otherExtracts.length">
                        <h4
                            class="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Alternate extracts
                        </h4>
                        <ul class="space-y-1 text-sm">
                            <li
                                v-for="extract in otherExtracts"
                                :key="extract.id"
                            >
                                <Link
                                    :href="
                                        showMap(resultMap.slug, {
                                            query: { marker: extract.id },
                                        })
                                    "
                                    class="text-primary hover:underline"
                                >
                                    {{ extract.name }}
                                </Link>
                                <span class="text-xs text-muted-foreground">
                                    · {{ extract.type }}</span
                                >
                            </li>
                        </ul>
                    </div>

                    <div
                        v-if="
                            result.missing_items.length ||
                            result.missing_objectives.length
                        "
                        class="rounded-lg border border-warning/40 bg-warning/10 p-4 text-sm"
                    >
                        <p class="font-medium text-warning">
                            Not mapped on {{ resultMap.name }} yet
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            These have no documented location on this map, so
                            they are not part of the route. They may exist here
                            — they just haven’t been mapped — or be found on
                            another map.
                        </p>
                        <ul class="mt-2 space-y-0.5">
                            <li
                                v-for="id in result.missing_items"
                                :key="`i${id}`"
                            >
                                <Link
                                    v-if="itemsById.get(id)"
                                    :href="showItem(itemsById.get(id)!.slug)"
                                    class="text-primary hover:underline"
                                >
                                    {{ itemsById.get(id)!.name }}
                                </Link>
                            </li>
                            <li
                                v-for="id in result.missing_objectives"
                                :key="`o${id}`"
                            >
                                <Link
                                    v-if="objectivesById.get(id)"
                                    :href="
                                        showObjective(
                                            objectivesById.get(id)!.slug,
                                        )
                                    "
                                    class="text-primary hover:underline"
                                >
                                    {{ objectivesById.get(id)!.name }}
                                </Link>
                                <span class="text-xs text-muted-foreground">
                                    · objective</span
                                >
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
