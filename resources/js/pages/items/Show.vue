<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChevronLeft,
    CircleHelp,
    Coins,
    MapPin,
    PackageCheck,
    Star,
    TriangleAlert,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import FeedbackButton from '@/components/game/FeedbackButton.vue';
import DataQuality from '@/components/public/DataQuality.vue';
import { ITEM_INTENTS, selectClass } from '@/components/public/options';
import QuantityStepper from '@/components/public/QuantityStepper.vue';
import RarityChip from '@/components/public/RarityChip.vue';
import { Button } from '@/components/ui/button';
import { useFeedback } from '@/composables/useFeedback';
import { http, HttpError } from '@/lib/http';
import { login } from '@/routes';
import { update as updateTracking } from '@/routes/api/tracked-items';
import { index as itemsIndex, show as showItem } from '@/routes/items';
import { show as showMap } from '@/routes/maps';
import { show as showObjective } from '@/routes/objectives';
import type { Option } from '@/types/game';

type Recipe = {
    slug: string;
    name: string;
    kind: string;
    station: string | null;
    level: number | null;
    quantity: number | null;
    ingredients: { slug: string; name: string; quantity: number }[];
};

type Advice = {
    verdict: 'keep' | 'sell' | 'unknown';
    priority: 'high' | 'medium' | 'low' | null;
    reasons: string[];
    needed: number;
    owned: number;
    uses: number;
};

type Tracking = {
    intent: string | null;
    quantity_needed: number;
    quantity_owned: number;
    is_favorite: boolean;
};

const props = defineProps<{
    item: {
        id: number;
        slug: string;
        name: string;
        description: string | null;
        category: string | null;
        rarity: string | null;
        value: number | string | null;
        weight: number | string | null;
        metadata: Record<string, unknown>;
        confidence: { score: number; label: string };
        source?: { name: string | null; url: string | null } | null;
        source_url?: string | null;
        last_verified_at: string | null;
        verified_version: string | null;
    };
    foundAt: {
        map: { slug: string; name: string };
        markers: {
            id: number;
            name: string;
            type: string;
            likelihood: string | null;
            chance?: number | null;
            count?: number;
        }[];
    }[];
    lootPools: {
        name: string;
        chance: number | null;
        map: { slug: string; name: string } | null;
        spots: number;
    }[];
    usedIn: Recipe[];
    producedBy: Recipe[];
    objectives: {
        slug: string;
        name: string;
        kind: string;
        map: string | null;
        role: string | null;
        quantity: number | string | null;
    }[];
    advice: Advice;
    tracking: Tracking | null;
    reportTypes: Option[];
}>();

const { openFeedback } = useFeedback();

const page = usePage();
const signedIn = computed(() => page.props.auth.user !== null);

const advice = ref<Advice>(props.advice);
const tracking = reactive<Tracking>({
    intent: props.tracking?.intent ?? null,
    quantity_needed: props.tracking?.quantity_needed ?? 0,
    quantity_owned: props.tracking?.quantity_owned ?? 0,
    is_favorite: props.tracking?.is_favorite ?? false,
});
const saving = ref(false);

const locationCount = computed(() =>
    props.foundAt.reduce(
        (sum, group) =>
            sum + group.markers.reduce((n, m) => n + (m.count ?? 1), 0),
        0,
    ),
);

const verdict = computed(() => {
    switch (advice.value.verdict) {
        case 'keep':
            return {
                title: 'Keep it',
                tone: 'border-success/40 bg-success/10',
                text: 'text-success',
                icon: PackageCheck,
            };
        case 'sell':
            return {
                title: 'Not needed by you',
                tone: 'border-warning/40 bg-warning/10',
                text: 'text-warning',
                icon: Coins,
            };
        default:
            return {
                title: 'No known uses recorded yet',
                tone: 'border-border bg-card',
                text: 'text-muted-foreground',
                icon: CircleHelp,
            };
    }
});

async function save(): Promise<void> {
    saving.value = true;

    try {
        const response = await http<{ advice: Advice }>(
            'put',
            updateTracking(props.item.id).url,
            { ...tracking },
        );
        advice.value = response.advice;
    } catch (e) {
        toast.error(
            e instanceof HttpError
                ? e.firstError()
                : 'Could not save tracking.',
        );
    } finally {
        saving.value = false;
    }
}

function toggleFavorite(): void {
    tracking.is_favorite = !tracking.is_favorite;
    void save();
}

function display(value: number | string | null): string {
    return value === null || value === '' ? 'Unknown' : String(value);
}
</script>

<template>
    <SeoHead />

    <div class="mx-auto max-w-7xl px-4 py-6 md:py-8">
        <Link
            :href="itemsIndex()"
            class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ChevronLeft class="size-4" /> All items
        </Link>

        <header class="mb-6">
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <RarityChip :rarity="item.rarity" />
                <span class="text-sm text-muted-foreground">{{
                    item.category ?? 'Unknown category'
                }}</span>
            </div>
            <h1
                class="font-display text-3xl leading-tight font-semibold tracking-wide uppercase md:text-4xl"
            >
                {{ item.name }}
            </h1>
            <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                <div>
                    <dt class="mr-1.5 inline text-muted-foreground">Value</dt>
                    <dd
                        class="inline font-medium tabular-nums"
                        :class="{
                            'text-muted-foreground': item.value === null,
                        }"
                    >
                        {{ display(item.value) }}
                    </dd>
                </div>
                <div>
                    <dt class="mr-1.5 inline text-muted-foreground">Weight</dt>
                    <dd
                        class="inline font-medium tabular-nums"
                        :class="{
                            'text-muted-foreground': item.weight === null,
                        }"
                    >
                        {{ display(item.weight) }}
                    </dd>
                </div>
                <div>
                    <dt class="mr-1.5 inline text-muted-foreground">
                        Known locations
                    </dt>
                    <dd class="inline font-medium tabular-nums">
                        {{ locationCount }}
                    </dd>
                </div>
            </dl>
            <p
                v-if="item.description"
                class="mt-4 max-w-3xl whitespace-pre-line"
            >
                {{ item.description }}
            </p>
        </header>

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <!-- Sidebar first on mobile: the verdict is what players need mid-raid. -->
            <aside class="space-y-4 lg:order-2">
                <section
                    class="rounded-lg border p-4"
                    :class="verdict.tone"
                    aria-labelledby="keep-title"
                    aria-live="polite"
                >
                    <p
                        class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Should I keep this?
                    </p>
                    <h2
                        id="keep-title"
                        class="mt-1 flex items-center gap-2 text-lg font-semibold"
                        :class="verdict.text"
                    >
                        <component :is="verdict.icon" class="size-5" />
                        {{ verdict.title }}
                        <span
                            v-if="advice.priority"
                            class="ml-auto rounded-full border px-2 py-0.5 text-xs font-medium text-foreground capitalize"
                        >
                            {{ advice.priority }} priority
                        </span>
                    </h2>
                    <p
                        v-if="advice.verdict === 'unknown'"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        No recipe, upgrade or objective using this item is
                        documented. That does not mean it is useless — only that
                        nothing is recorded.
                    </p>
                    <p
                        v-else-if="advice.verdict === 'sell'"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        Based on your own tracking (marked as not needed or to
                        sell).
                    </p>
                    <ul
                        v-if="advice.reasons.length"
                        class="mt-3 space-y-1 text-sm"
                    >
                        <li
                            v-for="reason in advice.reasons"
                            :key="reason"
                            class="flex gap-2"
                        >
                            <span class="text-muted-foreground">•</span
                            >{{ reason }}
                        </li>
                    </ul>
                    <p
                        v-if="advice.needed > 0 || advice.owned > 0"
                        class="mt-3 text-sm"
                    >
                        You need
                        <strong class="tabular-nums">{{ advice.needed }}</strong
                        >, own
                        <strong class="tabular-nums">{{ advice.owned }}</strong>
                        <template v-if="advice.needed > advice.owned">
                            —
                            <span class="text-warning"
                                >{{ advice.needed - advice.owned }} still to
                                find</span
                            >
                        </template>
                    </p>
                </section>

                <section
                    v-if="signedIn"
                    class="space-y-3 rounded-lg border bg-card p-4"
                    aria-labelledby="tracking-title"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h2
                            id="tracking-title"
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            My tracking
                        </h2>
                        <Button
                            variant="ghost"
                            size="sm"
                            :aria-pressed="tracking.is_favorite"
                            :disabled="saving"
                            @click="toggleFavorite"
                        >
                            <Star
                                class="size-4"
                                :class="{
                                    'fill-yellow-400 text-yellow-400':
                                        tracking.is_favorite,
                                }"
                            />
                            {{
                                tracking.is_favorite ? 'Favorited' : 'Favorite'
                            }}
                        </Button>
                    </div>
                    <div class="space-y-1">
                        <label for="tracking-intent" class="text-sm"
                            >Intent</label
                        >
                        <select
                            id="tracking-intent"
                            v-model="tracking.intent"
                            :class="selectClass"
                            :disabled="saving"
                            @change="save"
                        >
                            <option :value="null">Not set</option>
                            <option
                                v-for="option in ITEM_INTENTS"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <div class="space-y-1">
                            <span class="block text-sm">Need</span>
                            <QuantityStepper
                                v-model="tracking.quantity_needed"
                                label="quantity needed"
                                @commit="save"
                            />
                        </div>
                        <div class="space-y-1">
                            <span class="block text-sm">Own</span>
                            <QuantityStepper
                                v-model="tracking.quantity_owned"
                                label="quantity owned"
                                @commit="save"
                            />
                        </div>
                    </div>
                </section>
                <p
                    v-else
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    <Link :href="login()" class="text-primary hover:underline"
                        >Log in</Link
                    >
                    to track how many you need and own.
                </p>

                <DataQuality
                    :confidence="item.confidence"
                    :source="item.source"
                    :last-verified-at="item.last_verified_at"
                    :verified-version="item.verified_version"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        class="w-full text-muted-foreground"
                        @click="
                            openFeedback({
                                subject: {
                                    type: 'item',
                                    id: item.id,
                                    name: item.name,
                                },
                            })
                        "
                    >
                        <TriangleAlert class="size-4" /> Report a problem
                    </Button>
                </DataQuality>
            </aside>

            <div class="space-y-6 lg:order-1">
                <section aria-labelledby="found-title">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            id="found-title"
                            class="font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            Found at
                        </h2>
                        <FeedbackButton
                            :options="{
                                subject: {
                                    type: 'item',
                                    id: item.id,
                                    name: item.name,
                                },
                                context: 'Found at',
                                type: 'incorrect_location',
                            }"
                        />
                    </div>
                    <div v-if="foundAt.length" class="space-y-3">
                        <div
                            v-for="group in foundAt"
                            :key="group.map.slug"
                            class="rounded-lg border bg-card"
                        >
                            <h3
                                class="flex items-center justify-between border-b px-4 py-2 font-medium"
                            >
                                {{ group.map.name }}
                                <span class="text-xs text-muted-foreground"
                                    >{{ group.markers.length }} location{{
                                        group.markers.length === 1 ? '' : 's'
                                    }}</span
                                >
                            </h3>
                            <ul class="divide-y">
                                <li
                                    v-for="marker in group.markers"
                                    :key="marker.id"
                                >
                                    <Link
                                        :href="
                                            showMap(group.map.slug, {
                                                query: { marker: marker.id },
                                            })
                                        "
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-accent/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    >
                                        <MapPin
                                            class="size-4 shrink-0 text-primary"
                                        />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate">{{
                                                marker.name
                                            }}</span>
                                            <span
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ marker.type
                                                }}<template
                                                    v-if="marker.likelihood"
                                                >
                                                    ·
                                                    {{
                                                        marker.likelihood
                                                    }}</template
                                                ><template
                                                    v-if="
                                                        (marker.count ?? 1) > 1
                                                    "
                                                >
                                                    · {{ marker.count }} spots
                                                    on this map</template
                                                >
                                            </span>
                                        </span>
                                        <span class="text-xs text-primary"
                                            >View on map</span
                                        >
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p
                        v-else
                        class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        No mapped locations yet. If you know where it spawns,
                        report it from the map so an editor can add it.
                    </p>
                </section>

                <section v-if="lootPools.length" aria-labelledby="pools-title">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            id="pools-title"
                            class="font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            Loot pools
                        </h2>
                        <FeedbackButton
                            :options="{
                                subject: {
                                    type: 'item',
                                    id: item.id,
                                    name: item.name,
                                },
                                context: 'Loot pools',
                                type: 'wrong_loot',
                            }"
                        />
                    </div>
                    <p class="mb-2 text-sm text-muted-foreground">
                        Containers and spawns that can drop this item, with the
                        drop chance per roll.
                    </p>
                    <ul class="divide-y rounded-lg border bg-card">
                        <li
                            v-for="pool in lootPools"
                            :key="pool.name"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm"
                        >
                            <span class="min-w-0 flex-1">
                                <span class="block truncate">{{
                                    pool.name
                                }}</span>
                                <span class="text-xs text-muted-foreground">
                                    <template v-if="pool.map">
                                        <Link
                                            :href="showMap(pool.map.slug)"
                                            class="text-primary hover:underline"
                                            >{{ pool.map.name }}</Link
                                        >
                                        ·
                                    </template>
                                    {{
                                        pool.spots
                                            ? `${pool.spots} mapped spot${pool.spots === 1 ? '' : 's'}`
                                            : 'no mapped spots'
                                    }}
                                </span>
                            </span>
                            <span
                                class="text-sm font-medium whitespace-nowrap tabular-nums"
                                >{{
                                    pool.chance !== null
                                        ? `${pool.chance}%`
                                        : '—'
                                }}</span
                            >
                        </li>
                    </ul>
                </section>

                <section aria-labelledby="used-title">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            id="used-title"
                            class="font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            Used in
                        </h2>
                        <FeedbackButton
                            :options="{
                                subject: {
                                    type: 'item',
                                    id: item.id,
                                    name: item.name,
                                },
                                context: 'Used in',
                                type: 'missing_data',
                            }"
                        />
                    </div>
                    <ul v-if="usedIn.length" class="grid gap-3 sm:grid-cols-2">
                        <li
                            v-for="recipe in usedIn"
                            :key="recipe.slug"
                            class="rounded-lg border bg-card p-4"
                        >
                            <p class="text-xs text-muted-foreground uppercase">
                                {{ recipe.kind
                                }}<template v-if="recipe.station">
                                    · {{ recipe.station }}</template
                                ><template v-if="recipe.level !== null">
                                    · Level {{ recipe.level }}</template
                                >
                            </p>
                            <h3 class="font-medium">
                                {{ recipe.name }}
                                <span
                                    v-if="recipe.quantity"
                                    class="text-sm text-warning"
                                    >×{{ recipe.quantity }} needed</span
                                >
                            </h3>
                            <ul class="mt-2 space-y-0.5 text-sm">
                                <li
                                    v-for="ingredient in recipe.ingredients"
                                    :key="ingredient.slug"
                                >
                                    <span
                                        class="text-muted-foreground tabular-nums"
                                        >{{ ingredient.quantity }}×</span
                                    >
                                    <span
                                        v-if="ingredient.slug === item.slug"
                                        class="font-medium"
                                        >{{ ingredient.name }}</span
                                    >
                                    <Link
                                        v-else
                                        :href="showItem(ingredient.slug)"
                                        class="text-primary hover:underline"
                                        >{{ ingredient.name }}</Link
                                    >
                                </li>
                            </ul>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        No recipes or upgrades using this item are recorded.
                    </p>
                </section>

                <section
                    v-if="producedBy.length"
                    aria-labelledby="produced-title"
                >
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            id="produced-title"
                            class="font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            Produced by
                        </h2>
                        <FeedbackButton
                            :options="{
                                subject: {
                                    type: 'item',
                                    id: item.id,
                                    name: item.name,
                                },
                                context: 'Produced by',
                                type: 'missing_data',
                            }"
                        />
                    </div>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        <li
                            v-for="recipe in producedBy"
                            :key="recipe.slug"
                            class="rounded-lg border bg-card p-4"
                        >
                            <p class="text-xs text-muted-foreground uppercase">
                                {{ recipe.kind
                                }}<template v-if="recipe.station">
                                    · {{ recipe.station }}</template
                                ><template v-if="recipe.level !== null">
                                    · Level {{ recipe.level }}</template
                                >
                            </p>
                            <h3 class="font-medium">{{ recipe.name }}</h3>
                            <ul
                                v-if="recipe.ingredients.length"
                                class="mt-2 space-y-0.5 text-sm"
                            >
                                <li
                                    v-for="ingredient in recipe.ingredients"
                                    :key="ingredient.slug"
                                >
                                    <span
                                        class="text-muted-foreground tabular-nums"
                                        >{{ ingredient.quantity }}×</span
                                    >
                                    <Link
                                        :href="showItem(ingredient.slug)"
                                        class="text-primary hover:underline"
                                        >{{ ingredient.name }}</Link
                                    >
                                </li>
                            </ul>
                            <p
                                v-else
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                Ingredients unknown.
                            </p>
                        </li>
                    </ul>
                </section>

                <section
                    v-if="objectives.length"
                    aria-labelledby="objectives-title"
                >
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            id="objectives-title"
                            class="font-display text-xl font-semibold tracking-wide uppercase"
                        >
                            Objectives
                        </h2>
                        <FeedbackButton
                            :options="{
                                subject: {
                                    type: 'item',
                                    id: item.id,
                                    name: item.name,
                                },
                                context: 'Objectives',
                                type: 'wrong_objective',
                            }"
                        />
                    </div>
                    <ul class="divide-y rounded-lg border bg-card">
                        <li
                            v-for="objective in objectives"
                            :key="objective.slug"
                        >
                            <Link
                                :href="showObjective(objective.slug)"
                                class="block px-4 py-3 hover:bg-accent/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <span class="font-medium text-primary">{{
                                    objective.name
                                }}</span>
                                <span
                                    class="block text-xs text-muted-foreground"
                                >
                                    {{ objective.kind }} ·
                                    {{ objective.map ?? 'Map unknown'
                                    }}<template v-if="objective.role">
                                        · {{ objective.role }}</template
                                    ><template v-if="objective.quantity">
                                        · ×{{ objective.quantity }}</template
                                    >
                                </span>
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</template>
