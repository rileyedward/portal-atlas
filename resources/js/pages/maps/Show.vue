<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    ExternalLink,
    Layers,
    LocateFixed,
    MapPinned,
    Minus,
    PanelLeftClose,
    PanelLeftOpen,
    Plus,
    Route as RouteIcon,
    StickyNote,
    Tag,
    TriangleAlert,
    X,
} from '@lucide/vue';
import { useSessionStorage } from '@vueuse/core';
import { computed, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import BrandMark from '@/components/game/BrandMark.vue';
import FeedbackButton from '@/components/game/FeedbackButton.vue';
import FeedbackDialog from '@/components/game/FeedbackDialog.vue';
import GlobalSearch from '@/components/game/GlobalSearch.vue';
import GameMap from '@/components/map/GameMap.vue';
import LayerPanel from '@/components/map/LayerPanel.vue';
import MarkerDetailPanel from '@/components/map/MarkerDetailPanel.vue';
import NoteDialog from '@/components/map/NoteDialog.vue';
import RoutePanel from '@/components/map/RoutePanel.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import { useFeedback } from '@/composables/useFeedback';
import { useLayerFilters } from '@/composables/useLayerFilters';
import { http, HttpError } from '@/lib/http';
import { home, login } from '@/routes';
import { state as markerState } from '@/routes/api/markers';
import {
    destroy as destroyNote,
    store as storeNote,
    update as updateNote,
} from '@/routes/api/notes';
import { editor } from '@/routes/admin/maps';
import { show as showExtractions } from '@/routes/extractions';
import { show as showMap } from '@/routes/maps';
import type {
    MapData,
    MapMarker,
    MapNote,
    MapSummary,
    MarkerCategoryData,
    MarkerTypeData,
    Option,
    RaidRoute,
    RoutePoint,
} from '@/types/game';

const props = defineProps<{
    map: MapData;
    maps: MapSummary[];
    categories: MarkerCategoryData[];
    markers: MapMarker[];
    reportTypes: Option[];
    focus: number | null;
    personal: {
        notes: MapNote[];
        favorites: number[];
        discovered: number[];
        routes: RaidRoute[];
    } | null;
    sharedRoute: (RaidRoute & { author: string }) | null;
    canEdit: boolean;
}>();

const page = usePage();
const signedIn = computed(() => page.props.auth.user !== null);

const categories = computed(() => props.categories);
const filters = useLayerFilters(categories);

type TypeInfo = MarkerTypeData & { category: string };

const types = computed<Record<number, TypeInfo>>(() => {
    const out: Record<number, TypeInfo> = {};

    for (const category of props.categories) {
        for (const type of category.types) {
            out[type.id] = { ...type, category: category.slug };
        }
    }

    return out;
});

// Raid variants (Regular, Overgrowth, Unstable Zone...). Markers without a
// variant exist in every variant of the raid.
const variantOptions = computed(() => props.map.metadata.variant_options ?? []);

function defaultVariant(): string | null {
    const fromUrl =
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('variant')
            : null;
    const keys = variantOptions.value.map((v) => v.key);

    if (fromUrl && keys.includes(fromUrl)) {
        return fromUrl;
    }

    return keys.includes('regular') ? 'regular' : (keys[0] ?? null);
}

const variant = ref<string | null>(defaultVariant());

function setVariant(key: string): void {
    variant.value = key;
    const url = new URL(window.location.href);
    url.searchParams.set('variant', key);
    window.history.replaceState(window.history.state, '', url);
}

const markers = computed(() =>
    props.markers.filter(
        (m) => !variant.value || !m.variant || m.variant === variant.value,
    ),
);

const counts = computed(() => {
    const out: Record<number, number> = {};

    for (const marker of markers.value) {
        out[marker.type_id] = (out[marker.type_id] ?? 0) + 1;
    }

    return out;
});

const unplaced = computed(() => markers.value.filter((m) => m.x === null));
const placedCount = computed(
    () => markers.value.length - unplaced.value.length,
);

// Selection & map controls
const gameMap = ref<InstanceType<typeof GameMap> | null>(null);
const selectedId = ref<number | null>(null);
const sidebarOpen = ref(true);
const tab = ref<'layers' | 'intel' | 'mine'>('layers');
const cursor = ref<{ x: number; y: number } | null>(null);
const schematicDismissed = useSessionStorage('map.schematicDismissed', false);

const selected = computed(
    () => props.markers.find((m) => m.id === selectedId.value) ?? null,
);

function select(id: number, fly = false): void {
    const marker = props.markers.find((m) => m.id === id);

    if (!marker) {
        return;
    }

    selectedId.value = id;

    // Switch to the marker's raid variant if it isn't in the current one.
    if (marker.variant && variant.value && marker.variant !== variant.value) {
        setVariant(marker.variant);
    }

    // Make sure the selected marker's layer is visible.
    if (!filters.isTypeVisible(marker.type_id)) {
        filters.setType(marker.type_id, true);
    }

    if (fly && marker.x !== null && marker.y !== null) {
        gameMap.value?.flyTo(marker.x, marker.y, 2);
    }

    const url = new URL(window.location.href);
    url.searchParams.set('marker', String(id));
    window.history.replaceState(window.history.state, '', url);
}

function closeDetails(): void {
    selectedId.value = null;
    const url = new URL(window.location.href);
    url.searchParams.delete('marker');
    window.history.replaceState(window.history.state, '', url);
}

const nearbyThreats = computed(() => {
    const current = selected.value;

    if (!current || current.x === null || current.y === null) {
        return [];
    }

    return markers.value
        .filter(
            (m) =>
                m.id !== current.id &&
                types.value[m.type_id]?.category === 'threats' &&
                m.x !== null &&
                m.y !== null &&
                Math.hypot(m.x - current.x!, m.y - current.y!) <= 8,
        )
        .sort(
            (a, b) =>
                Math.hypot(a.x! - current.x!, a.y! - current.y!) -
                Math.hypot(b.x! - current.x!, b.y! - current.y!),
        )
        .reduce<{ id: number; name: string; type: string; count: number }[]>(
            (groups, m) => {
                // Group repeats ("Gravitational anomaly ×10"), nearest first.
                const existing = groups.find((g) => g.name === m.name);

                if (existing) {
                    existing.count++;
                } else {
                    groups.push({
                        id: m.id,
                        name: m.name,
                        type: types.value[m.type_id].name,
                        count: 1,
                    });
                }

                return groups;
            },
            [],
        )
        .slice(0, 8);
});

onMounted(() => {
    if (window.matchMedia('(max-width: 767px)').matches) {
        sidebarOpen.value = false;
    }

    if (props.focus) {
        setTimeout(() => select(props.focus!, true), 150);
    }

    if (props.sharedRoute) {
        shownRoute.value = props.sharedRoute;
    }
});

// Personal state
const favorites = ref<number[]>(props.personal?.favorites ?? []);
const discovered = ref<number[]>(props.personal?.discovered ?? []);
const notes = ref<MapNote[]>(props.personal?.notes ?? []);
const routes = ref<RaidRoute[]>(props.personal?.routes ?? []);

async function toggleState(kind: 'is_favorite' | 'discovered'): Promise<void> {
    const id = selectedId.value;

    if (!id) {
        return;
    }

    const list = kind === 'is_favorite' ? favorites : discovered;
    const next = !list.value.includes(id);

    try {
        await http('put', markerState(id).url, { [kind]: next });
        list.value = next
            ? [...list.value, id]
            : list.value.filter((x) => x !== id);
    } catch {
        toast.error('Could not save. Are you still logged in?');
    }
}

// Modes: browse, placing a note, drawing a route
const mode = ref<'browse' | 'note' | 'route' | 'suggest'>('browse');
const { openFeedback } = useFeedback();

// "Suggest correct position": the next map click is sent as feedback.
function startSuggest(): void {
    mode.value = 'suggest';
}
const noteDialogOpen = ref(false);
const noteDraft = ref<Partial<MapNote> | null>(null);
const noteError = ref<string | null>(null);
const noteSaving = ref(false);
const routePoints = ref<RoutePoint[]>([]);
const shownRoute = ref<RaidRoute | (RaidRoute & { author: string }) | null>(
    null,
);

const drawing = computed({
    get: () => mode.value === 'route',
    set: (value: boolean) => {
        mode.value = value ? 'route' : 'browse';

        if (value) {
            shownRoute.value = null;
        }
    },
});

const displayedRoute = computed<RoutePoint[]>(() =>
    mode.value === 'route'
        ? routePoints.value
        : (shownRoute.value?.points ?? []),
);

function onMapClick(point: { x: number; y: number }): void {
    if (mode.value === 'suggest') {
        mode.value = 'browse';
        const marker = selected.value;

        openFeedback({
            subject: marker
                ? { type: 'marker', id: marker.id, name: marker.name }
                : { type: 'map', id: props.map.id, name: props.map.name },
            context: 'Position on map',
            type: 'incorrect_location',
            suggested: point,
        });

        return;
    }

    if (mode.value === 'note') {
        noteDraft.value = { x: point.x, y: point.y };
        noteError.value = null;
        noteDialogOpen.value = true;
        mode.value = 'browse';

        return;
    }

    if (mode.value === 'route') {
        // Snap to a nearby visible marker so routes follow real places.
        const visible = new Set(filters.visibleTypeIds.value);
        const snap = markers.value
            .filter(
                (m) => m.x !== null && m.y !== null && visible.has(m.type_id),
            )
            .map((m) => ({ m, d: Math.hypot(m.x! - point.x, m.y! - point.y) }))
            .filter(({ d }) => d < 1.5)
            .sort((a, b) => a.d - b.d)[0]?.m;

        routePoints.value = [
            ...routePoints.value,
            snap
                ? {
                      x: snap.x!,
                      y: snap.y!,
                      marker_id: snap.id,
                      label: snap.name,
                  }
                : { x: point.x, y: point.y, marker_id: null, label: null },
        ];
    }
}

function onMarkerSelect(id: number): void {
    if (mode.value === 'route' || mode.value === 'suggest') {
        const marker = props.markers.find((m) => m.id === id);

        if (marker && marker.x !== null && marker.y !== null) {
            onMapClick({ x: marker.x, y: marker.y });
        }

        return;
    }

    select(id);
}

function openNote(id: number): void {
    noteDraft.value = notes.value.find((n) => n.id === id) ?? null;
    noteError.value = null;
    noteDialogOpen.value = true;
}

function addNoteAt(x: number, y: number): void {
    noteDraft.value = { x, y };
    noteError.value = null;
    noteDialogOpen.value = true;
}

async function saveNote(note: Partial<MapNote>): Promise<void> {
    noteSaving.value = true;
    noteError.value = null;

    try {
        if (note.id) {
            const saved = await http<MapNote>(
                'patch',
                updateNote(note.id).url,
                note,
            );
            notes.value = notes.value.map((n) =>
                n.id === saved.id ? saved : n,
            );
        } else {
            const saved = await http<MapNote>('post', storeNote().url, {
                ...note,
                map_id: props.map.id,
            });
            notes.value = [saved, ...notes.value];
        }

        noteDialogOpen.value = false;
        toast.success('Note saved');
    } catch (e) {
        noteError.value =
            e instanceof HttpError ? e.firstError() : 'Could not save note.';
    } finally {
        noteSaving.value = false;
    }
}

async function deleteNote(id: number): Promise<void> {
    await http('delete', destroyNote(id).url);
    notes.value = notes.value.filter((n) => n.id !== id);
    noteDialogOpen.value = false;
}

function startNoteMode(): void {
    if (!signedIn.value) {
        router.visit(login().url);

        return;
    }

    mode.value = mode.value === 'note' ? 'browse' : 'note';
}

function showRoute(route: RaidRoute | null): void {
    shownRoute.value = route;
    const first = route?.points[0];

    if (first) {
        gameMap.value?.flyTo(first.x, first.y, 1);
    }
}

const facts = computed(() => props.map.metadata.facts ?? []);
const variants = computed(() => props.map.metadata.variants ?? []);

watch(
    () => props.map.slug,
    () => {
        selectedId.value = null;
        mode.value = 'browse';
        shownRoute.value = null;
        variant.value = defaultVariant();
        notes.value = props.personal?.notes ?? [];
        routes.value = props.personal?.routes ?? [];
        favorites.value = props.personal?.favorites ?? [];
        discovered.value = props.personal?.discovered ?? [];
    },
);
</script>

<template>
    <SeoHead />

    <div
        class="dark flex h-svh flex-col overflow-hidden bg-background text-foreground"
    >
        <!-- Top bar -->
        <header
            class="z-30 flex h-12 shrink-0 items-center gap-2 border-b bg-card px-2 sm:px-3"
        >
            <Button
                variant="ghost"
                size="icon"
                class="size-8"
                :aria-label="sidebarOpen ? 'Hide sidebar' : 'Show sidebar'"
                :aria-expanded="sidebarOpen"
                @click="sidebarOpen = !sidebarOpen"
            >
                <component
                    :is="sidebarOpen ? PanelLeftClose : PanelLeftOpen"
                    class="size-4"
                />
            </Button>
            <Link :href="home()" aria-label="Home" class="hidden sm:block">
                <BrandMark />
            </Link>
            <Link :href="home()" aria-label="Home" class="sm:hidden">
                <BrandMark compact />
            </Link>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="secondary"
                        size="sm"
                        class="ml-1 gap-1 font-display tracking-wide uppercase"
                    >
                        {{ map.name }}
                        <ChevronDown class="size-3.5" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="start"
                    class="max-h-[70vh] w-56 overflow-y-auto"
                >
                    <DropdownMenuItem
                        v-for="other in maps"
                        :key="other.slug"
                        as-child
                    >
                        <Link
                            :href="showMap(other.slug)"
                            :class="{
                                'font-semibold text-primary':
                                    other.slug === map.slug,
                            }"
                        >
                            {{ other.name }}
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <div class="ml-auto flex flex-1 items-center justify-end gap-2">
                <div class="hidden w-full max-w-sm sm:block">
                    <GlobalSearch
                        :current-map="map.slug"
                        @focus-marker="(id) => select(id, true)"
                    />
                </div>
                <div class="sm:hidden">
                    <GlobalSearch
                        compact
                        :current-map="map.slug"
                        @focus-marker="(id) => select(id, true)"
                    />
                </div>
                <FeedbackButton
                    :options="{
                        subject: { type: 'map', id: map.id, name: map.name },
                    }"
                    label="Feedback"
                    class="hidden md:inline-flex"
                />
                <Button
                    v-if="canEdit"
                    as-child
                    variant="outline"
                    size="sm"
                    class="hidden md:inline-flex"
                >
                    <Link :href="editor(map.slug)">Edit map</Link>
                </Button>
            </div>
        </header>

        <div class="relative flex min-h-0 flex-1">
            <!-- Sidebar -->
            <aside
                v-show="sidebarOpen"
                class="absolute inset-y-0 left-0 z-20 flex w-[85vw] max-w-80 flex-col border-r bg-card shadow-xl md:static md:w-80 md:shadow-none"
                aria-label="Map sidebar"
            >
                <div class="border-b p-3">
                    <h1
                        class="text-lg leading-tight font-semibold tracking-wide uppercase"
                    >
                        {{ map.name }}
                    </h1>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        <template v-if="map.metadata.region"
                            >{{ map.metadata.region }} ·
                        </template>
                        {{ placedCount }} mapped ·
                        {{ unplaced.length }} awaiting position
                    </p>
                </div>

                <div class="flex border-b text-sm" role="tablist">
                    <button
                        v-for="t in [
                            { key: 'layers', label: 'Layers' },
                            { key: 'intel', label: 'Intel' },
                            { key: 'mine', label: 'Mine' },
                        ] as const"
                        :key="t.key"
                        type="button"
                        role="tab"
                        :aria-selected="tab === t.key"
                        class="flex-1 border-b-2 py-2 transition"
                        :class="
                            tab === t.key
                                ? 'border-primary text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        "
                        @click="tab = t.key"
                    >
                        {{ t.label }}
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-2">
                    <!-- Layers -->
                    <div
                        v-if="tab === 'layers'"
                        class="space-y-3"
                        role="tabpanel"
                    >
                        <div v-if="variantOptions.length > 1" class="px-2">
                            <h2
                                class="mb-1.5 font-sans text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Raid variant
                            </h2>
                            <div
                                class="flex flex-wrap gap-1"
                                role="radiogroup"
                                aria-label="Raid variant"
                            >
                                <button
                                    v-for="option in variantOptions"
                                    :key="option.key"
                                    type="button"
                                    role="radio"
                                    :aria-checked="variant === option.key"
                                    class="rounded-full border px-2.5 py-1 text-xs transition"
                                    :class="
                                        variant === option.key
                                            ? 'border-primary bg-primary/15 text-foreground'
                                            : 'text-muted-foreground hover:border-primary/50 hover:text-foreground'
                                    "
                                    @click="setVariant(option.key)"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>
                        <LayerPanel
                            :categories="categories"
                            :counts="counts"
                            :is-type-visible="filters.isTypeVisible"
                            :category-state="filters.categoryState"
                            @set-type="filters.setType"
                            @set-category="filters.setCategory"
                        />
                        <div class="flex gap-2 px-2 text-xs">
                            <button
                                type="button"
                                class="text-primary hover:underline"
                                @click="filters.showAll()"
                            >
                                Show all
                            </button>
                            <span class="text-muted-foreground">·</span>
                            <button
                                type="button"
                                class="text-primary hover:underline"
                                @click="filters.hideAll()"
                            >
                                Hide all
                            </button>
                        </div>
                        <div class="space-y-1 border-t px-2 pt-3">
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="filters.showLabels.value"
                                    type="checkbox"
                                />
                                <Tag class="size-3.5 text-muted-foreground" />
                                Show labels
                            </label>
                            <label
                                v-if="signedIn"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="filters.showNotes.value"
                                    type="checkbox"
                                />
                                <StickyNote
                                    class="size-3.5 text-muted-foreground"
                                />
                                Show my notes
                            </label>
                        </div>

                        <details
                            v-if="unplaced.length"
                            class="border-t px-2 pt-3"
                            :open="placedCount === 0"
                        >
                            <summary
                                class="mb-1 cursor-pointer font-sans text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Known, not yet positioned ({{
                                    unplaced.length
                                }})
                            </summary>
                            <p class="mb-2 text-xs text-muted-foreground">
                                These are known to exist on {{ map.name }}, but
                                nobody has mapped their exact location yet.
                            </p>
                            <ul class="space-y-0.5">
                                <li v-for="marker in unplaced" :key="marker.id">
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-2 rounded px-2 py-1 text-left text-sm hover:bg-accent/50"
                                        :class="{
                                            'bg-accent':
                                                selectedId === marker.id,
                                        }"
                                        @click="select(marker.id)"
                                    >
                                        <span
                                            class="size-2 shrink-0 rounded-full"
                                            :style="{
                                                background:
                                                    types[marker.type_id]
                                                        ?.color,
                                            }"
                                        />
                                        <span class="truncate">{{
                                            marker.name
                                        }}</span>
                                        <span
                                            class="ml-auto shrink-0 text-[10px] text-muted-foreground"
                                        >
                                            {{ types[marker.type_id]?.name }}
                                        </span>
                                    </button>
                                </li>
                            </ul>
                        </details>
                    </div>

                    <!-- Intel -->
                    <div
                        v-else-if="tab === 'intel'"
                        class="space-y-4 p-2 text-sm"
                        role="tabpanel"
                    >
                        <p v-if="map.description" class="whitespace-pre-line">
                            {{ map.description }}
                        </p>
                        <p v-else-if="map.summary">{{ map.summary }}</p>
                        <dl
                            class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-xs"
                        >
                            <dt class="text-muted-foreground">Region</dt>
                            <dd>{{ map.metadata.region ?? 'Unknown' }}</dd>
                            <dt class="text-muted-foreground">Data version</dt>
                            <dd>{{ map.game_version ?? 'Unknown' }}</dd>
                        </dl>
                        <p
                            v-if="map.metadata.region_note"
                            class="text-xs text-muted-foreground italic"
                        >
                            {{ map.metadata.region_note }}
                        </p>
                        <div v-if="variants.length">
                            <h2
                                class="mb-1 font-sans text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Raid variants
                            </h2>
                            <ul class="flex flex-wrap gap-1">
                                <li
                                    v-for="variant in variants"
                                    :key="variant"
                                    class="rounded border px-2 py-0.5 text-xs"
                                >
                                    {{ variant }}
                                </li>
                            </ul>
                        </div>
                        <div v-if="facts.length">
                            <div class="flex items-center justify-between">
                                <h2
                                    class="mb-1 font-sans text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Field notes
                                </h2>
                                <FeedbackButton
                                    :options="{
                                        subject: {
                                            type: 'map',
                                            id: map.id,
                                            name: map.name,
                                        },
                                        context: 'Field notes',
                                    }"
                                />
                            </div>
                            <ul class="space-y-2">
                                <li
                                    v-for="(fact, i) in facts"
                                    :key="i"
                                    class="text-xs"
                                >
                                    {{ fact.text }}
                                    <a
                                        v-if="fact.source_url"
                                        :href="fact.source_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="ml-1 inline-flex items-center gap-0.5 text-primary hover:underline"
                                        >source<ExternalLink class="size-3"
                                    /></a>
                                    <span
                                        v-if="fact.confidence"
                                        class="ml-1 text-muted-foreground"
                                        >({{ fact.confidence }})</span
                                    >
                                </li>
                            </ul>
                        </div>
                        <Link
                            :href="showExtractions(map.slug)"
                            class="block text-xs text-primary hover:underline"
                        >
                            Extraction guide for {{ map.name }} →
                        </Link>
                    </div>

                    <!-- Mine -->
                    <div v-else class="space-y-4 p-2" role="tabpanel">
                        <template v-if="signedIn">
                            <section>
                                <h2
                                    class="mb-2 font-sans text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Raid routes
                                </h2>
                                <RoutePanel
                                    v-model:points="routePoints"
                                    v-model:drawing="drawing"
                                    v-model:routes="routes"
                                    :map-id="map.id"
                                    @show="showRoute"
                                />
                            </section>
                            <section>
                                <h2
                                    class="mb-2 font-sans text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                                >
                                    Notes
                                </h2>
                                <Button
                                    size="sm"
                                    variant="secondary"
                                    class="w-full"
                                    @click="startNoteMode"
                                >
                                    <StickyNote class="size-4" /> Place a note
                                </Button>
                                <ul class="mt-2 space-y-0.5">
                                    <li v-for="note in notes" :key="note.id">
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2 rounded px-2 py-1 text-left text-sm hover:bg-accent/50"
                                            @click="
                                                gameMap?.flyTo(
                                                    note.x,
                                                    note.y,
                                                    2,
                                                );
                                                openNote(note.id);
                                            "
                                        >
                                            <span
                                                class="size-2 shrink-0 rounded-sm"
                                                :style="{
                                                    background:
                                                        note.color ?? '#facc15',
                                                }"
                                            />
                                            <span class="truncate">{{
                                                note.title
                                            }}</span>
                                        </button>
                                    </li>
                                </ul>
                            </section>
                            <section class="text-xs text-muted-foreground">
                                {{ favorites.length }} favorites ·
                                {{ discovered.length }} discovered on this map
                            </section>
                        </template>
                        <div v-else class="space-y-3 p-2 text-sm">
                            <p>
                                Create a free account to save private notes,
                                favorites, discovered locations and raid routes.
                            </p>
                            <Button as-child size="sm"
                                ><Link :href="login()"
                                    >Log in or sign up</Link
                                ></Button
                            >
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Map -->
            <div class="relative isolate min-w-0 flex-1">
                <GameMap
                    ref="gameMap"
                    :map="map"
                    :markers="markers"
                    :types="types"
                    :visible-type-ids="filters.visibleTypeIds.value"
                    :selected-id="selectedId"
                    :show-labels="filters.showLabels.value"
                    :favorites="favorites"
                    :discovered="discovered"
                    :notes="filters.showNotes.value ? notes : []"
                    :route-points="displayedRoute"
                    :crosshair="mode !== 'browse'"
                    @select="onMarkerSelect"
                    @map-click="onMapClick"
                    @note-select="openNote"
                    @cursor="cursor = $event"
                />

                <!-- Empty-state banner -->
                <div
                    v-if="!map.image_url && !schematicDismissed"
                    class="absolute top-3 left-1/2 z-[500] w-[min(92%,34rem)] -translate-x-1/2 rounded-md border border-warning/30 bg-card/95 p-3 pr-8 text-xs shadow-lg"
                >
                    <button
                        type="button"
                        class="absolute top-2 right-2 text-muted-foreground hover:text-foreground"
                        aria-label="Dismiss notice"
                        @click="schematicDismissed = true"
                    >
                        <X class="size-4" />
                    </button>
                    <p class="flex items-start gap-2">
                        <TriangleAlert
                            class="mt-0.5 size-4 shrink-0 text-warning"
                        />
                        <span>
                            <strong class="font-semibold"
                                >Schematic grid.</strong
                            >
                            No base map has been traced for {{ map.name }} yet,
                            so positions are shown on a lettered grid. Every
                            marker shows a confidence score; places we know of
                            but can’t position are listed separately.
                        </span>
                    </p>
                </div>

                <!-- Mode banner -->
                <div
                    v-if="mode !== 'browse'"
                    class="absolute bottom-16 left-1/2 z-[500] flex -translate-x-1/2 items-center gap-3 rounded-full border bg-card px-4 py-2 text-sm shadow-lg"
                >
                    <component
                        :is="
                            mode === 'note'
                                ? StickyNote
                                : mode === 'suggest'
                                  ? MapPinned
                                  : RouteIcon
                        "
                        class="size-4 text-warning"
                    />
                    {{
                        mode === 'note'
                            ? 'Click the map to place your note'
                            : mode === 'suggest'
                              ? `Click where ${selected?.name ?? 'it'} really is`
                              : `Click the map to add stops (${routePoints.length})`
                    }}
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        aria-label="Cancel"
                        @click="mode = 'browse'"
                    >
                        <X class="size-4" />
                    </button>
                </div>

                <!-- Shown route banner -->
                <div
                    v-if="mode === 'browse' && shownRoute"
                    class="absolute bottom-16 left-1/2 z-[500] flex max-w-[90%] -translate-x-1/2 items-center gap-3 rounded-full border bg-card px-4 py-2 text-sm shadow-lg"
                >
                    <RouteIcon class="size-4 shrink-0 text-warning" />
                    <span class="truncate">
                        {{ shownRoute.name }}
                        <span
                            v-if="'author' in shownRoute"
                            class="text-muted-foreground"
                            >by {{ shownRoute.author }}</span
                        >
                    </span>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        aria-label="Hide route"
                        @click="shownRoute = null"
                    >
                        <X class="size-4" />
                    </button>
                </div>

                <!-- Controls -->
                <div
                    class="absolute right-3 bottom-4 z-[500] flex flex-col gap-1.5"
                    :class="{ 'md:right-[25rem]': selectedId }"
                >
                    <Button
                        variant="secondary"
                        size="icon"
                        class="size-9 shadow"
                        aria-label="Zoom in"
                        @click="gameMap?.zoomBy(0.5)"
                    >
                        <Plus class="size-4" />
                    </Button>
                    <Button
                        variant="secondary"
                        size="icon"
                        class="size-9 shadow"
                        aria-label="Zoom out"
                        @click="gameMap?.zoomBy(-0.5)"
                    >
                        <Minus class="size-4" />
                    </Button>
                    <Button
                        variant="secondary"
                        size="icon"
                        class="size-9 shadow"
                        aria-label="Reset view"
                        @click="gameMap?.resetView()"
                    >
                        <LocateFixed class="size-4" />
                    </Button>
                    <Button
                        variant="secondary"
                        size="icon"
                        class="size-9 shadow md:hidden"
                        aria-label="Layers"
                        @click="
                            sidebarOpen = true;
                            tab = 'layers';
                        "
                    >
                        <Layers class="size-4" />
                    </Button>
                </div>

                <div
                    v-if="cursor"
                    class="pointer-events-none absolute bottom-4 left-3 z-[500] hidden rounded bg-card/90 px-2 py-1 font-mono text-[11px] text-muted-foreground md:block"
                >
                    {{
                        String.fromCharCode(
                            65 + Math.min(9, Math.floor(cursor.x / 10)),
                        )
                    }}{{ Math.min(10, Math.floor(cursor.y / 10) + 1) }} · x
                    {{ cursor.x.toFixed(1) }} · y {{ cursor.y.toFixed(1) }}
                </div>
            </div>

            <!-- Detail panel: right column on desktop, bottom sheet on mobile -->
            <div
                v-if="selectedId"
                class="absolute inset-x-0 bottom-0 z-30 max-h-[65svh] overflow-hidden rounded-t-xl border-t shadow-2xl md:inset-y-0 md:right-0 md:left-auto md:max-h-none md:w-96 md:rounded-none md:border-t-0 md:border-l"
            >
                <MarkerDetailPanel
                    :marker-id="selectedId"
                    :signed-in="signedIn"
                    :is-favorite="favorites.includes(selectedId)"
                    :is-discovered="discovered.includes(selectedId)"
                    :nearby-threats="nearbyThreats"
                    @close="closeDetails"
                    @focus="(x, y) => gameMap?.flyTo(x, y, 2)"
                    @select-marker="(id) => select(id, true)"
                    @toggle-favorite="toggleState('is_favorite')"
                    @toggle-discovered="toggleState('discovered')"
                    @add-note="addNoteAt"
                    @suggest-position="startSuggest"
                />
            </div>
        </div>

        <NoteDialog
            v-model:open="noteDialogOpen"
            :note="noteDraft"
            :error="noteError"
            :saving="noteSaving"
            @save="saveNote"
            @delete="deleteNote"
        />
        <FeedbackDialog />
        <Toaster />
    </div>
</template>
