<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight, Minus } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { index as analytics } from '@/routes/admin/analytics';

type Day = { date: string; visitors: number; pageviews: number };

const props = defineProps<{
    range: number;
    ranges: number[];
    summary: {
        visitors: number;
        pageviews: number;
        views_per_visitor: number;
        visitors_today: number;
        change: { visitors: number | null; pageviews: number | null };
    };
    daily: Day[];
    top_pages: { path: string; views: number; visitors: number }[];
    referrers: { host: string; total: number }[];
    devices: { device: string; total: number }[];
    retentionDays: number;
}>();

const number = new Intl.NumberFormat();

const tiles = computed(() => [
    {
        label: 'Visitors',
        value: number.format(props.summary.visitors),
        change: props.summary.change.visitors,
    },
    {
        label: 'Page views',
        value: number.format(props.summary.pageviews),
        change: props.summary.change.pageviews,
    },
    {
        label: 'Views per visitor',
        value: props.summary.views_per_visitor.toFixed(1),
        change: undefined,
    },
    {
        label: 'Visitors today',
        value: number.format(props.summary.visitors_today),
        change: undefined,
    },
]);

const peak = computed(() =>
    Math.max(1, ...props.daily.map((day) => day.visitors)),
);
const hovered = ref<Day | null>(null);

const dayLabel = (date: string, style: 'short' | 'long' = 'short') =>
    new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        ...(style === 'long' ? { weekday: 'short' } : {}),
    });

const panels = computed(() => [
    {
        title: 'Top pages',
        rows: props.top_pages.map((row) => ({
            label: row.path,
            total: row.views,
            hint: `${number.format(row.visitors)} visitors`,
        })),
    },
    {
        title: 'Referrers',
        rows: props.referrers.map((row) => ({
            label: row.host,
            total: row.total,
            hint: null,
        })),
    },
    {
        title: 'Devices',
        rows: props.devices.map((row) => ({
            label: row.device.charAt(0).toUpperCase() + row.device.slice(1),
            total: row.total,
            hint: null,
        })),
    },
]);

const maxOf = (rows: { total: number }[]) =>
    Math.max(1, ...rows.map((row) => row.total));
</script>

<template>
    <Head title="Analytics" />

    <PageHeader
        title="Analytics"
        description="Cookieless visit counts for the public site. Staff and bots are not counted."
    >
        <template #actions>
            <nav
                aria-label="Date range"
                class="flex rounded-md border bg-card p-0.5 text-sm"
            >
                <Link
                    v-for="days in ranges"
                    :key="days"
                    :href="analytics({ query: { range: days } })"
                    preserve-scroll
                    class="rounded px-3 py-1 tabular-nums transition"
                    :class="
                        days === range
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    :aria-current="days === range ? 'page' : undefined"
                >
                    {{ days }}d
                </Link>
            </nav>
        </template>
    </PageHeader>

    <section
        aria-label="Summary"
        class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4"
    >
        <div
            v-for="tile in tiles"
            :key="tile.label"
            class="rounded-lg border bg-card p-4"
        >
            <span
                class="block text-xs tracking-wider text-muted-foreground uppercase"
            >
                {{ tile.label }}
            </span>
            <span
                class="mt-1 block font-display text-2xl font-semibold tabular-nums"
            >
                {{ tile.value }}
            </span>
            <span
                v-if="tile.change !== undefined"
                class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
            >
                <template v-if="tile.change === null">
                    <Minus class="size-3" /> No previous data
                </template>
                <template v-else>
                    <component
                        :is="tile.change >= 0 ? ArrowUpRight : ArrowDownRight"
                        class="size-3"
                    />
                    {{ tile.change >= 0 ? '+' : '' }}{{ tile.change }}% vs
                    previous {{ range }}d
                </template>
            </span>
        </div>
    </section>

    <section
        aria-labelledby="daily-heading"
        class="mb-6 rounded-lg border bg-card p-4"
    >
        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
            <h2
                id="daily-heading"
                class="text-sm font-semibold tracking-wider uppercase"
            >
                Visitors per day
            </h2>
            <p
                class="text-xs text-muted-foreground tabular-nums"
                aria-live="polite"
            >
                <template v-if="hovered">
                    {{ dayLabel(hovered.date, 'long') }} ·
                    {{ number.format(hovered.visitors) }} visitors ·
                    {{ number.format(hovered.pageviews) }} views
                </template>
                <template v-else
                    >Peak {{ number.format(peak) }} visitors</template
                >
            </p>
        </div>

        <div class="relative h-40" @mouseleave="hovered = null">
            <div
                class="pointer-events-none absolute inset-x-0 top-0 border-t border-dashed border-border"
                aria-hidden="true"
            />
            <div
                class="flex h-full items-end"
                :class="range > 30 ? 'gap-px' : 'gap-0.5'"
            >
                <button
                    v-for="day in daily"
                    :key="day.date"
                    type="button"
                    class="group flex h-full min-w-0 flex-1 items-end focus:outline-none"
                    :aria-label="`${dayLabel(day.date, 'long')}: ${day.visitors} visitors, ${day.pageviews} page views`"
                    @mouseenter="hovered = day"
                    @focus="hovered = day"
                    @blur="hovered = null"
                >
                    <span
                        class="block w-full rounded-t-sm transition-colors"
                        :class="
                            hovered?.date === day.date
                                ? 'bg-primary'
                                : 'bg-primary/60 group-focus-visible:bg-primary'
                        "
                        :style="{
                            height: day.visitors
                                ? `max(2px, ${(day.visitors / peak) * 100}%)`
                                : '0',
                        }"
                    />
                </button>
            </div>
            <div
                class="absolute inset-x-0 bottom-0 border-t border-border"
                aria-hidden="true"
            />
        </div>
        <div
            class="mt-1 flex justify-between text-xs text-muted-foreground tabular-nums"
        >
            <span>{{ dayLabel(daily[0].date) }}</span>
            <span>{{ dayLabel(daily[daily.length - 1].date) }}</span>
        </div>
    </section>

    <div class="grid gap-3 md:grid-cols-3">
        <section
            v-for="panel in panels"
            :key="panel.title"
            class="rounded-lg border bg-card p-3"
            :aria-label="panel.title"
        >
            <h2
                class="mb-2 text-xs tracking-wider text-muted-foreground uppercase"
            >
                {{ panel.title }}
            </h2>
            <ol v-if="panel.rows.length" class="space-y-1.5">
                <li v-for="row in panel.rows" :key="row.label" class="text-sm">
                    <div class="flex justify-between gap-2">
                        <span class="truncate" :title="row.label">{{
                            row.label
                        }}</span>
                        <span
                            class="shrink-0 text-muted-foreground tabular-nums"
                        >
                            {{ number.format(row.total) }}
                        </span>
                    </div>
                    <div
                        class="mt-0.5 h-1 overflow-hidden rounded-full bg-muted"
                        aria-hidden="true"
                    >
                        <div
                            class="h-full rounded-full bg-primary/70"
                            :style="{
                                width: `${(row.total / maxOf(panel.rows)) * 100}%`,
                            }"
                        />
                    </div>
                    <p v-if="row.hint" class="text-xs text-muted-foreground">
                        {{ row.hint }}
                    </p>
                </li>
            </ol>
            <p v-else class="text-sm text-muted-foreground">No data yet.</p>
        </section>
    </div>

    <p class="mt-6 text-xs text-muted-foreground">
        Visitors are identified by an anonymous hash that changes every day, so
        someone who visits on three days counts as three visitors. No cookies,
        IPs or user agents are stored, and raw data is deleted after
        {{ retentionDays }} days. Content-level stats (top maps, items,
        searches) are on the dashboard.
    </p>
</template>
