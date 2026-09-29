<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, MapPin, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import FeedbackButton from '@/components/game/FeedbackButton.vue';
import DataQuality from '@/components/public/DataQuality.vue';
import { Button } from '@/components/ui/button';
import { useFeedback } from '@/composables/useFeedback';
import { show as showItem } from '@/routes/items';
import { show as showMap } from '@/routes/maps';
import { index as objectivesIndex } from '@/routes/objectives';
import type { Option } from '@/types/game';

const props = defineProps<{
    objective: {
        id: number;
        slug: string;
        name: string;
        kind: string;
        description: string | null;
        giver: string | null;
        rewards: string | null;
        map: { slug: string; name: string } | null;
        confidence: { score: number; label: string };
        source?: { name: string | null; url: string | null } | null;
        source_url?: string | null;
        last_verified_at: string | null;
        verified_version: string | null;
    };
    markers: {
        id: number;
        name: string;
        type: string;
        role: string | null;
        map: { slug: string; name: string };
    }[];
    items: {
        slug: string;
        name: string;
        role: string | null;
        quantity: number;
    }[];
    reportTypes: Option[];
}>();

const { openFeedback } = useFeedback();

const required = computed(() => props.items.filter((i) => i.role !== 'reward'));
const rewardItems = computed(() =>
    props.items.filter((i) => i.role === 'reward'),
);

const metaDescription = computed(
    () =>
        props.objective.description?.slice(0, 155) ??
        `${props.objective.name}: ${props.objective.kind} in Active Matter${props.objective.map ? ` on ${props.objective.map.name}` : ''} — locations, required items and rewards.`,
);
</script>

<template>
    <Head :title="`${objective.name} — ${objective.kind}`">
        <meta name="description" :content="metaDescription" />
    </Head>

    <div class="mx-auto max-w-7xl px-4 py-6 md:py-8">
        <Link
            :href="objectivesIndex()"
            class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ChevronLeft class="size-4" /> All objectives
        </Link>

        <header class="mb-6">
            <p class="mb-1 text-sm text-muted-foreground">
                {{ objective.kind }}
            </p>
            <h1
                class="font-display text-3xl leading-tight font-semibold tracking-wide uppercase md:text-4xl"
            >
                {{ objective.name }}
            </h1>
            <dl class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:flex sm:flex-wrap">
                <div>
                    <dt class="inline text-muted-foreground">Map</dt>
                    <dd class="inline">
                        <Link
                            v-if="objective.map"
                            :href="showMap(objective.map.slug)"
                            class="text-primary hover:underline"
                        >
                            {{ objective.map.name }}
                        </Link>
                        <span v-else class="text-muted-foreground"
                            >Unknown</span
                        >
                    </dd>
                </div>
                <div>
                    <dt class="inline text-muted-foreground">Giver</dt>
                    <dd
                        class="inline"
                        :class="{ 'text-muted-foreground': !objective.giver }"
                    >
                        {{ objective.giver ?? 'Unknown' }}
                    </dd>
                </div>
                <div>
                    <dt class="inline text-muted-foreground">Rewards</dt>
                    <dd
                        class="inline"
                        :class="{ 'text-muted-foreground': !objective.rewards }"
                    >
                        {{ objective.rewards ?? 'Unknown' }}
                    </dd>
                </div>
            </dl>
        </header>

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-6">
                <section aria-labelledby="description-title">
                    <h2
                        id="description-title"
                        class="mb-2 font-display text-xl font-semibold tracking-wide uppercase"
                    >
                        Description
                    </h2>
                    <p
                        v-if="objective.description"
                        class="max-w-3xl whitespace-pre-line"
                    >
                        {{ objective.description }}
                    </p>
                    <p v-else class="text-muted-foreground italic">
                        No description yet.
                    </p>
                </section>

                <section aria-labelledby="locations-title">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            id="locations-title"
                            class="font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            Related locations
                        </h2>
                        <FeedbackButton
                            :options="{
                                subject: {
                                    type: 'objective',
                                    id: objective.id,
                                    name: objective.name,
                                },
                                context: 'Related locations',
                            }"
                        />
                    </div>
                    <ul
                        v-if="markers.length"
                        class="divide-y rounded-lg border bg-card"
                    >
                        <li v-for="marker in markers" :key="marker.id">
                            <Link
                                :href="
                                    showMap(marker.map.slug, {
                                        query: { marker: marker.id },
                                    })
                                "
                                class="flex items-center gap-3 px-4 py-3 hover:bg-accent/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <MapPin class="size-4 shrink-0 text-primary" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate">{{
                                        marker.name
                                    }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ marker.type }} · {{ marker.map.name
                                        }}<template v-if="marker.role">
                                            · {{ marker.role }}</template
                                        >
                                    </span>
                                </span>
                                <span class="text-xs text-primary"
                                    >View on map</span
                                >
                            </Link>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        No locations for this objective have been mapped yet.
                    </p>
                </section>

                <section aria-labelledby="required-title">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            id="required-title"
                            class="font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            Required items
                        </h2>
                        <FeedbackButton
                            :options="{
                                subject: {
                                    type: 'objective',
                                    id: objective.id,
                                    name: objective.name,
                                },
                                context: 'Required items',
                            }"
                        />
                    </div>
                    <ul v-if="required.length" class="space-y-1">
                        <li v-for="item in required" :key="item.slug">
                            <span class="text-muted-foreground tabular-nums"
                                >{{ item.quantity }}×</span
                            >
                            <Link
                                :href="showItem(item.slug)"
                                class="text-primary hover:underline"
                                >{{ item.name }}</Link
                            >
                            <span
                                v-if="item.role && item.role !== 'required'"
                                class="text-xs text-muted-foreground"
                            >
                                · {{ item.role }}</span
                            >
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        None recorded.
                    </p>
                </section>

                <section
                    v-if="rewardItems.length"
                    aria-labelledby="reward-title"
                >
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            id="reward-title"
                            class="font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            Reward items
                        </h2>
                        <FeedbackButton
                            :options="{
                                subject: {
                                    type: 'objective',
                                    id: objective.id,
                                    name: objective.name,
                                },
                                context: 'Reward items',
                            }"
                        />
                    </div>
                    <ul class="space-y-1">
                        <li v-for="item in rewardItems" :key="item.slug">
                            <span class="text-muted-foreground tabular-nums"
                                >{{ item.quantity }}×</span
                            >
                            <Link
                                :href="showItem(item.slug)"
                                class="text-primary hover:underline"
                                >{{ item.name }}</Link
                            >
                        </li>
                    </ul>
                </section>
            </div>

            <aside>
                <DataQuality
                    :confidence="objective.confidence"
                    :source="objective.source"
                    :last-verified-at="objective.last_verified_at"
                    :verified-version="objective.verified_version"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        class="w-full text-muted-foreground"
                        @click="
                            openFeedback({
                                subject: {
                                    type: 'objective',
                                    id: objective.id,
                                    name: objective.name,
                                },
                            })
                        "
                    >
                        <TriangleAlert class="size-4" /> Report a problem
                    </Button>
                </DataQuality>
            </aside>
        </div>
    </div>
</template>
