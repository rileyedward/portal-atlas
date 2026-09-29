<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, DoorOpen, Map as MapIcon } from '@lucide/vue';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import FeedbackButton from '@/components/game/FeedbackButton.vue';
import { Button } from '@/components/ui/button';
import { show as showMap } from '@/routes/maps';

defineProps<{
    map: { slug: string; name: string };
    extracts: {
        id: number;
        name: string;
        type: string;
        description: string | null;
        conditions: string | null;
        confidence: { score: number; label: string };
        verified_version: string | null;
    }[];
}>();
</script>

<template>
    <Head :title="`${map.name} extraction points`">
        <meta
            name="description"
            :content="`Documented extraction points on ${map.name} in Active Matter: type, conditions, confidence and the game version each was verified for.`"
        />
    </Head>

    <div class="mx-auto max-w-7xl px-4 py-6 md:py-8">
        <Link
            :href="showMap(map.slug)"
            class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ChevronLeft class="size-4" /> {{ map.name }} map
        </Link>

        <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1
                    class="font-display text-3xl font-semibold tracking-wide uppercase"
                >
                    {{ map.name }} extraction points
                </h1>
                <p class="mt-1 max-w-2xl text-muted-foreground">
                    Only confirmed extractions are listed. Check the confidence
                    and verified version before relying on one.
                </p>
            </div>
            <Button as-child variant="secondary">
                <Link :href="showMap(map.slug)"
                    ><MapIcon class="size-4" /> Open map</Link
                >
            </Button>
        </header>

        <ul v-if="extracts.length" class="grid gap-3 md:grid-cols-2">
            <li
                v-for="extract in extracts"
                :key="extract.id"
                class="flex flex-col rounded-lg border bg-card p-4"
            >
                <div class="flex items-start gap-3">
                    <DoorOpen class="mt-0.5 size-5 shrink-0 text-success" />
                    <div class="min-w-0 flex-1">
                        <h2
                            class="text-lg font-semibold tracking-wide uppercase"
                        >
                            {{ extract.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ extract.type }}
                        </p>
                    </div>
                    <FeedbackButton
                        :options="{
                            subject: {
                                type: 'marker',
                                id: extract.id,
                                name: extract.name,
                            },
                            context: 'Extraction guide',
                            type: 'wrong_extraction',
                        }"
                    />
                </div>
                <dl
                    class="mt-3 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1.5 text-sm"
                >
                    <dt class="text-muted-foreground">Description</dt>
                    <dd
                        :class="{
                            'text-muted-foreground italic':
                                !extract.description,
                        }"
                        class="whitespace-pre-line"
                    >
                        {{ extract.description ?? 'Unknown' }}
                    </dd>
                    <dt class="text-muted-foreground">Conditions</dt>
                    <dd
                        :class="{
                            'text-muted-foreground italic': !extract.conditions,
                        }"
                    >
                        {{ extract.conditions ?? 'Unknown' }}
                    </dd>
                    <dt class="text-muted-foreground">Verified for</dt>
                    <dd
                        :class="{
                            'text-muted-foreground': !extract.verified_version,
                        }"
                    >
                        {{ extract.verified_version ?? 'Unknown version' }}
                    </dd>
                    <dt class="text-muted-foreground">Confidence</dt>
                    <dd>
                        <ConfidenceMeter
                            :score="extract.confidence.score"
                            :label="extract.confidence.label"
                        />
                    </dd>
                </dl>
                <div class="mt-auto pt-4">
                    <Button as-child variant="secondary" size="sm">
                        <Link
                            :href="
                                showMap(map.slug, {
                                    query: { marker: extract.id },
                                })
                            "
                        >
                            <MapIcon class="size-4" /> Show on map
                        </Link>
                    </Button>
                </div>
            </li>
        </ul>

        <div v-else class="rounded-lg border border-dashed p-8 text-center">
            <p class="font-medium">
                Extraction points for {{ map.name }} are not documented yet.
            </p>
            <p class="mx-auto mt-1 max-w-xl text-sm text-muted-foreground">
                We only list confirmed extractions. Nothing has been verified
                for this map so far — if you know one, report it from the map so
                an editor can add it.
            </p>
            <Button as-child variant="secondary" class="mt-4">
                <Link :href="showMap(map.slug)"
                    ><MapIcon class="size-4" /> Open the
                    {{ map.name }} map</Link
                >
            </Button>
        </div>
    </div>
</template>
