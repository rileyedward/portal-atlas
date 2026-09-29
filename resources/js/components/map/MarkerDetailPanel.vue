<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Check,
    Copy,
    Crosshair,
    ExternalLink,
    Eye,
    MapPinned,
    EyeOff,
    Star,
    StickyNote,
    TriangleAlert,
    X,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import FeedbackButton from '@/components/game/FeedbackButton.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useFeedback } from '@/composables/useFeedback';
import { http, HttpError } from '@/lib/http';
import { login } from '@/routes';
import { show as showMarker } from '@/routes/api/markers';
import { confirm } from '@/routes/api/markers';
import { show as showItem } from '@/routes/items';
import { show as showObjective } from '@/routes/objectives';
import type { MarkerDetail } from '@/types/game';

const props = defineProps<{
    markerId: number;
    signedIn: boolean;
    isFavorite: boolean;
    isDiscovered: boolean;
    nearbyThreats: { id: number; name: string; type: string; count: number }[];
}>();

const emit = defineEmits<{
    close: [];
    focus: [x: number, y: number];
    selectMarker: [id: number];
    toggleFavorite: [];
    toggleDiscovered: [];
    addNote: [x: number, y: number];
    suggestPosition: [];
}>();

const { openFeedback } = useFeedback();

function subjectFor(d: MarkerDetail) {
    return { type: 'marker' as const, id: d.id, name: d.name };
}

const detail = ref<MarkerDetail | null>(null);
const loading = ref(false);
const failed = ref(false);
const confirming = ref(false);
const showAllLoot = ref(false);

async function load(id: number): Promise<void> {
    loading.value = true;
    failed.value = false;

    try {
        const response = await http<{ data: MarkerDetail }>(
            'get',
            showMarker(id).url,
        );
        detail.value = response.data;
    } catch {
        failed.value = true;
        detail.value = null;
    } finally {
        loading.value = false;
    }
}

watch(
    () => props.markerId,
    (id) => {
        showAllLoot.value = false;
        void load(id);
    },
    { immediate: true },
);

async function confirmAccuracy(): Promise<void> {
    if (!detail.value) {
        return;
    }

    confirming.value = true;

    try {
        const result = await http<{
            message: string;
            confidence: number;
            confirmations: number;
        }>('post', confirm(detail.value.id).url);
        detail.value.confidence.score = result.confidence;
        detail.value.confidence.confirmations = result.confirmations;
        toast.success(result.message);
    } catch (e) {
        toast.error(
            e instanceof HttpError ? e.firstError() : 'Could not confirm.',
        );
    } finally {
        confirming.value = false;
    }
}

function copyLink(): void {
    if (!detail.value) {
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('marker', String(detail.value.id));
    void navigator.clipboard.writeText(url.toString());
    toast.success('Link copied');
}

function formatDate(value: string | null): string {
    return value
        ? new Date(value).toLocaleDateString(undefined, {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
          })
        : 'Never';
}

const categoryTone: Record<string, string> = {
    extraction: 'bg-success/15 text-success border-success/30',
    threats: 'bg-destructive/15 text-destructive border-destructive/30',
    loot: 'bg-warning/15 text-warning border-warning/30',
    objectives: 'bg-sky-500/15 text-sky-400 border-sky-500/30',
    navigation: 'bg-muted text-muted-foreground',
};
</script>

<template>
    <aside
        class="flex h-full flex-col overflow-hidden bg-card"
        aria-live="polite"
        aria-label="Marker details"
    >
        <div class="flex items-start gap-2 border-b p-4">
            <div class="min-w-0 flex-1">
                <template v-if="detail">
                    <div class="mb-1 flex flex-wrap items-center gap-1.5">
                        <Badge
                            variant="outline"
                            :class="categoryTone[detail.type.category_slug]"
                        >
                            {{ detail.type.name }}
                        </Badge>
                        <Badge
                            v-if="detail.status !== 'published'"
                            variant="outline"
                            class="border-warning/40 text-warning"
                        >
                            {{ detail.status }}
                        </Badge>
                    </div>
                    <h2
                        class="text-xl leading-tight font-semibold tracking-wide uppercase"
                    >
                        {{ detail.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ detail.map.name
                        }}<template v-if="detail.floor">
                            · Floor {{ detail.floor }}</template
                        >
                    </p>
                </template>
                <Skeleton v-else-if="loading" class="h-12 w-3/4" />
            </div>
            <Button
                variant="ghost"
                size="icon"
                aria-label="Close details"
                @click="emit('close')"
            >
                <X class="size-4" />
            </Button>
        </div>

        <div v-if="failed" class="p-4 text-sm text-muted-foreground">
            This marker could not be loaded. It may have been removed.
        </div>

        <div v-else-if="loading && !detail" class="space-y-3 p-4">
            <Skeleton class="h-4 w-full" />
            <Skeleton class="h-4 w-5/6" />
            <Skeleton class="h-24 w-full" />
        </div>

        <div
            v-else-if="detail"
            class="flex-1 space-y-5 overflow-y-auto p-4 text-sm"
        >
            <div
                v-if="detail.x === null"
                class="flex gap-2 rounded-md border border-warning/30 bg-warning/10 p-3 text-warning"
            >
                <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                <div>
                    <p>
                        This place is known to exist, but its exact position has
                        not been mapped yet.
                    </p>
                    <button
                        type="button"
                        class="mt-1 font-medium underline underline-offset-2 hover:text-foreground"
                        @click="emit('suggestPosition')"
                    >
                        Know where it is? Click to suggest its location
                    </button>
                </div>
            </div>

            <section>
                <div class="flex items-start justify-between gap-2">
                    <h3 class="section-title">Description</h3>
                    <FeedbackButton
                        :options="{
                            subject: subjectFor(detail),
                            context: 'Description',
                        }"
                    />
                </div>
                <p v-if="detail.description" class="whitespace-pre-line">
                    {{ detail.description }}
                </p>
                <p v-else class="text-muted-foreground italic">
                    No description yet.
                </p>
            </section>

            <section v-if="detail.metadata.conditions">
                <h3 class="section-title">Conditions</h3>
                <p>{{ detail.metadata.conditions }}</p>
            </section>

            <section
                v-if="
                    detail.type.category_slug !== 'threats' || detail.loot_table
                "
            >
                <div class="flex items-start justify-between gap-2">
                    <h3 class="section-title">
                        {{
                            detail.type.category_slug === 'threats'
                                ? 'Drops'
                                : 'Known loot'
                        }}
                    </h3>
                    <FeedbackButton
                        :options="{
                            subject: subjectFor(detail),
                            context: 'Loot',
                            type: 'wrong_loot',
                        }"
                    />
                </div>
                <ul v-if="detail.items.length" class="mb-2 space-y-1">
                    <li
                        v-for="item in detail.items"
                        :key="item.slug"
                        class="flex items-baseline gap-2"
                    >
                        <span class="text-muted-foreground">•</span>
                        <Link
                            :href="showItem(item.slug)"
                            class="text-primary hover:underline"
                            >{{ item.name }}</Link
                        >
                        <span
                            v-if="item.likelihood"
                            class="text-xs text-muted-foreground"
                            >({{ item.likelihood }})</span
                        >
                    </li>
                </ul>
                <div v-if="detail.loot_table">
                    <p class="mb-1 text-xs text-muted-foreground">
                        Loot pool: {{ detail.loot_table.name }}
                    </p>
                    <ul class="space-y-1">
                        <li
                            v-for="item in showAllLoot
                                ? detail.loot_table.items
                                : detail.loot_table.items.slice(0, 12)"
                            :key="item.slug"
                            class="flex items-baseline gap-2"
                        >
                            <Link
                                :href="showItem(item.slug)"
                                class="min-w-0 flex-1 truncate text-primary hover:underline"
                                >{{ item.name }}</Link
                            >
                            <span
                                v-if="item.rarity"
                                class="text-[11px] text-muted-foreground"
                                >{{ item.rarity }}</span
                            >
                            <span
                                class="w-14 text-right text-xs text-muted-foreground tabular-nums"
                                >{{
                                    item.chance !== null
                                        ? `${item.chance}%`
                                        : '—'
                                }}</span
                            >
                        </li>
                    </ul>
                    <button
                        v-if="detail.loot_table.items.length > 12"
                        type="button"
                        class="mt-1 text-xs text-primary hover:underline"
                        @click="showAllLoot = !showAllLoot"
                    >
                        {{
                            showAllLoot
                                ? 'Show less'
                                : `Show all ${detail.loot_table.items.length}`
                        }}
                    </button>
                </div>
                <p
                    v-if="!detail.items.length && !detail.loot_table"
                    class="text-muted-foreground italic"
                >
                    Unknown
                </p>
            </section>

            <section v-if="detail.objectives.length">
                <h3 class="section-title">Objectives</h3>
                <ul class="space-y-1">
                    <li
                        v-for="objective in detail.objectives"
                        :key="objective.slug"
                    >
                        <Link
                            :href="showObjective(objective.slug)"
                            class="text-primary hover:underline"
                            >{{ objective.name }}</Link
                        >
                        <span class="text-xs text-muted-foreground">
                            · {{ objective.kind
                            }}<template v-if="objective.role">
                                · {{ objective.role }}</template
                            ></span
                        >
                    </li>
                </ul>
            </section>

            <section v-if="detail.x !== null">
                <h3 class="section-title">Known threats nearby</h3>
                <ul v-if="nearbyThreats.length" class="space-y-1">
                    <li v-for="threat in nearbyThreats" :key="threat.id">
                        <button
                            type="button"
                            class="text-left hover:text-primary"
                            @click="emit('selectMarker', threat.id)"
                        >
                            <span class="text-destructive">▲</span>
                            {{ threat.name
                            }}<template v-if="threat.count > 1">
                                ×{{ threat.count }}</template
                            >
                            <span class="text-xs text-muted-foreground"
                                >· {{ threat.type }}</span
                            >
                        </button>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground italic">
                    None recorded (that does not mean it is safe).
                </p>
            </section>

            <section class="space-y-2 rounded-md border bg-background/40 p-3">
                <h3 class="section-title">Data quality</h3>
                <ConfidenceMeter
                    :score="detail.confidence.score"
                    :label="detail.confidence.label"
                />
                <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-xs">
                    <dt class="text-muted-foreground">Last verified</dt>
                    <dd>{{ formatDate(detail.last_verified_at) }}</dd>
                    <dt class="text-muted-foreground">Verified for</dt>
                    <dd>{{ detail.verified_version ?? 'Unknown version' }}</dd>
                    <dt class="text-muted-foreground">Confirmations</dt>
                    <dd>{{ detail.confidence.confirmations }}</dd>
                    <dt class="text-muted-foreground">Open reports</dt>
                    <dd>{{ detail.confidence.open_reports }}</dd>
                    <template v-if="detail.source !== undefined">
                        <dt class="text-muted-foreground">Source</dt>
                        <dd class="min-w-0">
                            <a
                                v-if="detail.source?.url"
                                :href="detail.source.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 text-primary hover:underline"
                            >
                                <span class="truncate">{{
                                    detail.source.name ?? 'Source link'
                                }}</span>
                                <ExternalLink class="size-3 shrink-0" />
                            </a>
                            <span v-else-if="detail.source?.name">{{
                                detail.source.name
                            }}</span>
                            <span v-else class="text-muted-foreground"
                                >Unknown</span
                            >
                            <span
                                v-if="detail.source?.kind"
                                class="text-muted-foreground"
                            >
                                ({{ detail.source.kind }})</span
                            >
                        </dd>
                    </template>
                </dl>
                <p
                    v-if="detail.source_note"
                    class="text-xs text-muted-foreground italic"
                >
                    “{{ detail.source_note }}”
                </p>
            </section>
        </div>

        <div v-if="detail" class="grid grid-cols-2 gap-2 border-t p-3">
            <Button
                v-if="detail.x !== null && detail.y !== null"
                variant="secondary"
                size="sm"
                @click="emit('focus', detail.x, detail.y)"
            >
                <Crosshair class="size-4" /> Center
            </Button>
            <Button variant="secondary" size="sm" @click="copyLink">
                <Copy class="size-4" /> Copy link
            </Button>
            <template v-if="signedIn">
                <Button
                    variant="secondary"
                    size="sm"
                    :aria-pressed="isFavorite"
                    @click="emit('toggleFavorite')"
                >
                    <Star
                        class="size-4"
                        :class="{
                            'fill-yellow-400 text-yellow-400': isFavorite,
                        }"
                    />
                    {{ isFavorite ? 'Favorited' : 'Favorite' }}
                </Button>
                <Button
                    variant="secondary"
                    size="sm"
                    :aria-pressed="isDiscovered"
                    @click="emit('toggleDiscovered')"
                >
                    <component
                        :is="isDiscovered ? EyeOff : Eye"
                        class="size-4"
                    />
                    {{ isDiscovered ? 'Discovered' : 'Mark found' }}
                </Button>
                <Button
                    v-if="detail.x !== null && detail.y !== null"
                    variant="secondary"
                    size="sm"
                    @click="emit('addNote', detail.x, detail.y)"
                >
                    <StickyNote class="size-4" /> Add note
                </Button>
                <Button
                    variant="secondary"
                    size="sm"
                    :disabled="confirming"
                    @click="confirmAccuracy"
                >
                    <BadgeCheck class="size-4" /> Still accurate
                </Button>
            </template>
            <Button
                v-else
                as-child
                variant="secondary"
                size="sm"
                class="col-span-2"
            >
                <Link :href="login()"
                    ><Check class="size-4" /> Log in to save, confirm &amp; take
                    notes</Link
                >
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="text-muted-foreground"
                @click="emit('suggestPosition')"
            >
                <MapPinned class="size-4" />
                {{ detail.x === null ? 'Suggest position' : 'Wrong spot?' }}
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="text-muted-foreground"
                @click="openFeedback({ subject: subjectFor(detail) })"
            >
                <TriangleAlert class="size-4" /> Report a problem
            </Button>
        </div>
    </aside>
</template>

<style scoped>
.section-title {
    margin-bottom: 0.375rem;
    font-family: var(--font-sans);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--muted-foreground);
}
</style>
