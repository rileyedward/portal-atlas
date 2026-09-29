<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    Flag,
    Map as MapIcon,
    MapPin,
    Package,
    Crosshair,
    ShieldQuestion,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { formatDate } from '@/components/admin/types';
import { index as items } from '@/routes/admin/items';
import { index as maps } from '@/routes/admin/maps';
import { index as objectives } from '@/routes/admin/objectives';
import { index as reports } from '@/routes/admin/reports';
import { index as versions } from '@/routes/admin/versions';

type Ranked = { name: string; total: number };
type TermCount = { term: string | null; total: number };

const props = defineProps<{
    counts: {
        maps: number;
        markers: number;
        items: number;
        objectives: number;
        open_reports: number;
        low_confidence: number;
        unverified_current: number | null;
    };
    currentVersion: string | null;
    recentReports: {
        id: number;
        type: string;
        subject: string | null;
        message: string | null;
        created_at: string;
    }[];
    analytics: {
        top_searches: TermCount[];
        failed_searches: TermCount[];
        top_maps: Ranked[];
        top_markers: Ranked[];
        top_items: Ranked[];
    };
}>();

const stats = computed(() => [
    {
        label: 'Maps',
        value: props.counts.maps,
        icon: MapIcon,
        href: maps().url,
    },
    {
        label: 'Markers',
        value: props.counts.markers,
        icon: MapPin,
        href: maps().url,
    },
    {
        label: 'Items',
        value: props.counts.items,
        icon: Package,
        href: items().url,
    },
    {
        label: 'Objectives',
        value: props.counts.objectives,
        icon: Crosshair,
        href: objectives().url,
    },
]);

const attention = computed(() => [
    {
        label: 'Open feedback',
        value: props.counts.open_reports,
        icon: Flag,
        href: reports().url,
        tone:
            props.counts.open_reports > 0
                ? 'text-warning'
                : 'text-muted-foreground',
    },
    {
        label: 'Low-confidence markers',
        hint: 'Score under 30, no manual override',
        value: props.counts.low_confidence,
        icon: AlertTriangle,
        href: maps().url,
        tone:
            props.counts.low_confidence > 0
                ? 'text-destructive'
                : 'text-muted-foreground',
    },
    {
        label: 'Not verified this version',
        hint: props.currentVersion
            ? `Markers not verified on ${props.currentVersion}`
            : 'No current version set',
        value: props.counts.unverified_current ?? '—',
        icon: ShieldQuestion,
        href: props.currentVersion ? maps().url : versions().url,
        tone:
            (props.counts.unverified_current ?? 0) > 0
                ? 'text-warning'
                : 'text-muted-foreground',
    },
]);

const toRanked = (rows: TermCount[]): Ranked[] =>
    rows.map((row) => ({
        name: row.term ?? '(empty)',
        total: Number(row.total),
    }));

const panels = computed(() => [
    { title: 'Top searches', rows: toRanked(props.analytics.top_searches) },
    {
        title: 'Searches with no results',
        rows: toRanked(props.analytics.failed_searches),
        warn: true,
    },
    { title: 'Most viewed maps', rows: props.analytics.top_maps },
    { title: 'Most opened markers', rows: props.analytics.top_markers },
    { title: 'Most viewed items', rows: props.analytics.top_items },
]);

function maxOf(rows: Ranked[]): number {
    return Math.max(1, ...rows.map((row) => row.total));
}
</script>

<template>
    <Head title="Admin dashboard" />

    <PageHeader
        title="Dashboard"
        :description="
            currentVersion
                ? `Tracking game version ${currentVersion}.`
                : 'No current game version is set — mark one as current under Game versions.'
        "
    />

    <section aria-labelledby="content-heading" class="mb-6">
        <h2 id="content-heading" class="sr-only">Content totals</h2>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <Link
                v-for="stat in stats"
                :key="stat.label"
                :href="stat.href"
                class="group rounded-lg border bg-card p-4 transition hover:border-primary/50"
            >
                <div
                    class="flex items-center justify-between text-xs tracking-wider text-muted-foreground uppercase"
                >
                    {{ stat.label }}
                    <component
                        :is="stat.icon"
                        class="size-4 transition group-hover:text-primary"
                    />
                </div>
                <p
                    class="mt-2 font-display text-3xl font-semibold tabular-nums"
                >
                    {{ stat.value }}
                </p>
            </Link>
        </div>
    </section>

    <section aria-labelledby="attention-heading" class="mb-8">
        <h2
            id="attention-heading"
            class="mb-3 text-sm font-semibold tracking-wider uppercase"
        >
            Needs attention
        </h2>
        <div class="grid gap-3 md:grid-cols-3">
            <Link
                v-for="card in attention"
                :key="card.label"
                :href="card.href"
                class="flex items-start gap-3 rounded-lg border bg-card p-4 transition hover:border-primary/50"
            >
                <component
                    :is="card.icon"
                    class="mt-0.5 size-5 shrink-0"
                    :class="card.tone"
                />
                <span class="min-w-0">
                    <span
                        class="block font-display text-2xl font-semibold tabular-nums"
                        :class="card.tone"
                    >
                        {{ card.value }}
                    </span>
                    <span class="block text-sm font-medium">{{
                        card.label
                    }}</span>
                    <span
                        v-if="card.hint"
                        class="block text-xs text-muted-foreground"
                        >{{ card.hint }}</span
                    >
                </span>
            </Link>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-5">
        <section aria-labelledby="reports-heading" class="lg:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h2
                    id="reports-heading"
                    class="text-sm font-semibold tracking-wider uppercase"
                >
                    Latest open feedback
                </h2>
                <Link
                    :href="reports()"
                    class="flex items-center gap-1 text-xs text-primary hover:underline"
                >
                    All feedback <ArrowRight class="size-3" />
                </Link>
            </div>
            <ul
                v-if="recentReports.length"
                class="divide-y rounded-lg border bg-card"
            >
                <li
                    v-for="report in recentReports"
                    :key="report.id"
                    class="p-3 text-sm"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium">{{
                            report.subject ?? 'Deleted subject'
                        }}</span>
                        <span class="shrink-0 text-xs text-muted-foreground">{{
                            formatDate(report.created_at)
                        }}</span>
                    </div>
                    <p class="text-xs text-warning">{{ report.type }}</p>
                    <p
                        v-if="report.message"
                        class="mt-1 line-clamp-2 text-muted-foreground"
                    >
                        {{ report.message }}
                    </p>
                </li>
            </ul>
            <EmptyState
                v-else
                title="No open feedback"
                description="Community reports will appear here."
            />
        </section>

        <section aria-labelledby="analytics-heading" class="lg:col-span-3">
            <h2
                id="analytics-heading"
                class="mb-3 text-sm font-semibold tracking-wider uppercase"
            >
                Usage · last 30 days
            </h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div
                    v-for="panel in panels"
                    :key="panel.title"
                    class="rounded-lg border bg-card p-3"
                >
                    <h3
                        class="mb-2 text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        {{ panel.title }}
                    </h3>
                    <ol v-if="panel.rows.length" class="space-y-1.5">
                        <li
                            v-for="row in panel.rows"
                            :key="row.name"
                            class="text-sm"
                        >
                            <div class="flex justify-between gap-2">
                                <span class="truncate">{{ row.name }}</span>
                                <span
                                    class="text-muted-foreground tabular-nums"
                                    >{{ row.total }}</span
                                >
                            </div>
                            <div
                                class="mt-0.5 h-1 overflow-hidden rounded-full bg-muted"
                                aria-hidden="true"
                            >
                                <div
                                    class="h-full rounded-full"
                                    :class="
                                        panel.warn
                                            ? 'bg-warning'
                                            : 'bg-primary/70'
                                    "
                                    :style="{
                                        width: `${(row.total / maxOf(panel.rows)) * 100}%`,
                                    }"
                                />
                            </div>
                        </li>
                    </ol>
                    <p v-else class="text-sm text-muted-foreground">
                        No data yet.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
