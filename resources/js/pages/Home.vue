<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Crosshair,
    Map as MapIcon,
    Package,
    Route,
} from '@lucide/vue';
import GlobalSearch from '@/components/game/GlobalSearch.vue';
import { index as items } from '@/routes/items';
import { show as showMap } from '@/routes/maps';
import { index as objectives } from '@/routes/objectives';
import { show as planner } from '@/routes/planner';
import type { MapSummary } from '@/types/game';

defineProps<{
    maps: MapSummary[];
    currentVersion: string | null;
    stats: { maps: number; markers: number; items: number; objectives: number };
}>();

const shortcuts = [
    {
        title: 'Item database',
        text: 'Where to find it and why to keep it.',
        href: items().url,
        icon: Package,
    },
    {
        title: 'Objectives',
        text: 'Investigations, contracts and targets.',
        href: objectives().url,
        icon: Crosshair,
    },
    {
        title: 'Raid planner',
        text: 'Turn a shopping list into a route.',
        href: planner().url,
        icon: Route,
    },
];
</script>

<template>
    <SeoHead />

    <section class="relative overflow-hidden border-b">
        <div
            class="pointer-events-none absolute inset-0 opacity-40 [background:radial-gradient(60rem_30rem_at_20%_-10%,hsl(172_70%_50%/0.18),transparent),radial-gradient(40rem_20rem_at_90%_10%,hsl(38_92%_55%/0.10),transparent)]"
        />
        <div class="relative mx-auto max-w-7xl px-4 py-14 md:py-20">
            <p
                class="mb-3 font-display text-xs tracking-[0.25em] text-anomaly uppercase"
            >
                Unofficial Active Matter interactive map & raid companion
            </p>
            <h1
                class="max-w-3xl text-4xl leading-[1.05] font-semibold tracking-wide uppercase md:text-6xl"
            >
                Know the zone before you step through the portal.
            </h1>
            <p class="mt-4 max-w-2xl text-muted-foreground">
                Interactive maps, extraction info, loot and objectives — every
                entry versioned by patch and scored for confidence. Unknown
                means unknown: nothing here is guessed.
            </p>
            <div class="mt-6 max-w-lg">
                <GlobalSearch />
            </div>
            <dl class="mt-8 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                <div>
                    <dt class="inline text-muted-foreground">Maps</dt>
                    <dd class="inline font-semibold tabular-nums">
                        {{ stats.maps }}
                    </dd>
                </div>
                <div>
                    <dt class="inline text-muted-foreground">Mapped places</dt>
                    <dd class="inline font-semibold tabular-nums">
                        {{ stats.markers }}
                    </dd>
                </div>
                <div>
                    <dt class="inline text-muted-foreground">Items</dt>
                    <dd class="inline font-semibold tabular-nums">
                        {{ stats.items }}
                    </dd>
                </div>
                <div>
                    <dt class="inline text-muted-foreground">Objectives</dt>
                    <dd class="inline font-semibold tabular-nums">
                        {{ stats.objectives }}
                    </dd>
                </div>
                <div v-if="currentVersion">
                    <dt class="inline text-muted-foreground">Tracking build</dt>
                    <dd class="inline font-semibold">{{ currentVersion }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10">
        <h2 class="mb-4 text-xl font-semibold tracking-wide uppercase">Maps</h2>
        <div
            v-if="maps.length"
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
        >
            <Link
                v-for="map in maps"
                :key="map.slug"
                :href="showMap(map.slug)"
                class="group relative flex flex-col overflow-hidden rounded-lg border bg-card p-4 transition hover:border-primary/50 hover:bg-accent/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <div class="flex items-start justify-between gap-3">
                    <h3 class="text-lg font-semibold tracking-wide uppercase">
                        {{ map.name }}
                    </h3>
                    <MapIcon
                        class="size-5 shrink-0 text-muted-foreground transition group-hover:text-primary"
                    />
                </div>
                <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                    {{ map.summary ?? 'No summary yet.' }}
                </p>
                <p
                    class="mt-3 flex items-center gap-1 text-xs text-muted-foreground"
                >
                    {{ map.markers_count ?? 0 }} documented places
                    <ArrowRight
                        class="ml-auto size-4 text-primary opacity-0 transition group-hover:opacity-100"
                    />
                </p>
            </Link>
        </div>
        <p
            v-else
            class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
        >
            No maps have been published yet.
        </p>
    </section>

    <section class="mx-auto grid max-w-7xl gap-3 px-4 pb-14 sm:grid-cols-3">
        <Link
            v-for="shortcut in shortcuts"
            :key="shortcut.href"
            :href="shortcut.href"
            class="flex items-start gap-3 rounded-lg border bg-card p-4 transition hover:border-primary/50"
        >
            <component :is="shortcut.icon" class="mt-0.5 size-5 text-primary" />
            <span>
                <span class="block font-medium">{{ shortcut.title }}</span>
                <span class="text-sm text-muted-foreground">{{
                    shortcut.text
                }}</span>
            </span>
        </Link>
    </section>
</template>
