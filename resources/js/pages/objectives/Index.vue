<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Link } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import { selectClass } from '@/components/public/options';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { show } from '@/routes/objectives';

const props = defineProps<{
    objectives: {
        slug: string;
        name: string;
        kind: string;
        map: string | null;
        confidence: number;
    }[];
}>();

const query = ref('');
const kind = ref('');
const map = ref('');

const kinds = computed(() =>
    [...new Set(props.objectives.map((o) => o.kind))].sort(),
);
const maps = computed(() =>
    [
        ...new Set(
            props.objectives
                .map((o) => o.map)
                .filter((m): m is string => m !== null),
        ),
    ].sort(),
);

const filtered = computed(() => {
    const needle = query.value.trim().toLowerCase();

    return props.objectives.filter(
        (o) =>
            (!needle || o.name.toLowerCase().includes(needle)) &&
            (!kind.value || o.kind === kind.value) &&
            (!map.value ||
                (map.value === '__none'
                    ? o.map === null
                    : o.map === map.value)),
    );
});

function reset(): void {
    query.value = '';
    kind.value = '';
    map.value = '';
}
</script>

<template>
    <SeoHead />

    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-6">
            <h1
                class="font-display text-3xl font-semibold tracking-wide uppercase"
            >
                Objectives
            </h1>
            <p class="mt-1 max-w-2xl text-muted-foreground">
                Documented objectives with where they happen and what they need.
                Unknown details are shown as unknown.
            </p>
        </header>

        <form
            class="mb-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]"
            role="search"
            @submit.prevent
        >
            <div class="relative">
                <label for="objective-filter" class="sr-only"
                    >Filter objectives by name</label
                >
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    id="objective-filter"
                    v-model="query"
                    type="search"
                    placeholder="Filter by name…"
                    class="pl-9"
                    autocomplete="off"
                />
            </div>
            <div>
                <label for="objective-kind" class="sr-only">Kind</label>
                <select
                    id="objective-kind"
                    v-model="kind"
                    :class="selectClass"
                    class="sm:w-44"
                >
                    <option value="">All kinds</option>
                    <option v-for="k in kinds" :key="k" :value="k">
                        {{ k }}
                    </option>
                </select>
            </div>
            <div>
                <label for="objective-map" class="sr-only">Map</label>
                <select
                    id="objective-map"
                    v-model="map"
                    :class="selectClass"
                    class="sm:w-44"
                >
                    <option value="">All maps</option>
                    <option v-for="m in maps" :key="m" :value="m">
                        {{ m }}
                    </option>
                    <option value="__none">Map unknown</option>
                </select>
            </div>
        </form>

        <p class="mb-2 text-sm text-muted-foreground" aria-live="polite">
            {{ filtered.length }} of {{ objectives.length }} objectives
        </p>

        <ul v-if="filtered.length" class="divide-y rounded-lg border bg-card">
            <li v-for="objective in filtered" :key="objective.slug">
                <Link
                    :href="show(objective.slug)"
                    class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 hover:bg-accent/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <span class="min-w-0 flex-1">
                        <span class="block font-medium text-primary">{{
                            objective.name
                        }}</span>
                        <span class="text-xs text-muted-foreground">
                            {{ objective.kind }} ·
                            {{ objective.map ?? 'Map unknown' }}
                        </span>
                    </span>
                    <ConfidenceMeter :score="objective.confidence" compact />
                </Link>
            </li>
        </ul>
        <div
            v-else
            class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
        >
            <template v-if="objectives.length">
                No objectives match these filters.
                <Button variant="link" @click="reset">Clear filters</Button>
            </template>
            <template v-else>No objectives have been published yet.</template>
        </div>
    </div>
</template>
