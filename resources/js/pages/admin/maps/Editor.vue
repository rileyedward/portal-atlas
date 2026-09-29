<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    Copy,
    ExternalLink,
    MapPinOff,
    MousePointerClick,
    Pencil,
    Plus,
    Save,
    Search,
    Shapes,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import BrandMark from '@/components/game/BrandMark.vue';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import InputError from '@/components/InputError.vue';
import GameMap from '@/components/map/GameMap.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import { http, HttpError } from '@/lib/http';
import { iconSvg } from '@/lib/markerIcons';
import { edit as editMap, index as mapsIndex } from '@/routes/admin/maps';
import {
    destroy,
    duplicate,
    show as showMarker,
    store,
    update,
    verify,
} from '@/routes/admin/maps/markers';
import { show as publicMap } from '@/routes/maps';
import type {
    MapData,
    MapMarker,
    MarkerCategoryData,
    MarkerTypeData,
    Option,
    Point,
} from '@/types/game';

type EditorMarker = MapMarker & {
    marker_type_id: number;
    is_visible: boolean;
    open_reports_count: number;
};

type MarkerForm = {
    id?: number;
    marker_type_id: number | null;
    name: string;
    description: string | null;
    x: number | null;
    y: number | null;
    geometry: Point[] | null;
    floor: string | null;
    variant: string | null;
    loot_table_id: number | null;
    status: string;
    is_visible: boolean;
    source_id: number | null;
    source_url: string | null;
    source_note: string | null;
    confidence_override: number | null;
    introduced_version_id: number | null;
    verified_version_id: number | null;
    metadata: { conditions?: string | null; [key: string]: unknown };
    items: {
        id: number;
        name?: string;
        likelihood: string | null;
        note: string | null;
    }[];
    objectives: { id: number; name?: string; role: string | null }[];
    confidence?: number;
    confirmations_count?: number;
    open_reports_count?: number;
    last_verified_at?: string | null;
};

const props = defineProps<{
    map: MapData;
    categories: MarkerCategoryData[];
    markers: (Omit<EditorMarker, 'type_id'> & { type_id?: number })[];
    options: {
        items: { id: number; name: string }[];
        objectives: { id: number; name: string }[];
        sources: { id: number; name: string }[];
        versions: { id: number; version: string }[];
        statuses: Option[];
        lootTables: { id: number; name: string }[];
    };
}>();

const variantOptions = computed(() => props.map.metadata.variant_options ?? []);
// 'all' shows every marker; otherwise shared markers plus that variant's own.
const editorVariant = ref<string>('all');

const inVariant = (m: { variant?: string | null }) =>
    editorVariant.value === 'all' ||
    !m.variant ||
    m.variant === editorVariant.value;

// Normalise to the MapMarker shape GameMap expects.
const markers = ref<EditorMarker[]>(
    props.markers.map(
        (m) => ({ ...m, type_id: m.marker_type_id }) as EditorMarker,
    ),
);

const types = computed<Record<number, MarkerTypeData>>(() => {
    const out: Record<number, MarkerTypeData> = {};

    for (const category of props.categories) {
        for (const type of category.types) {
            out[type.id] = type;
        }
    }

    return out;
});
const allTypeIds = computed(() => Object.keys(types.value).map(Number));

const gameMap = ref<InstanceType<typeof GameMap> | null>(null);
const mode = ref<'select' | 'add' | 'place' | 'shape'>('select');
const addTypeId = ref<number | null>(props.categories[0]?.types[0]?.id ?? null);
const form = ref<MarkerForm | null>(null);
const errors = ref<Record<string, string[]>>({});
const saving = ref(false);
const filter = ref('');
const showUnplacedOnly = ref(false);

const selectedId = computed(() => form.value?.id ?? null);
const mapMarkers = computed(() => markers.value.filter((m) => inVariant(m)));

const listed = computed(() => {
    const term = filter.value.trim().toLowerCase();

    return markers.value
        .filter((m) => inVariant(m))
        .filter((m) => !showUnplacedOnly.value || m.x === null)
        .filter((m) => !term || m.name.toLowerCase().includes(term))
        .sort(
            (a, b) =>
                Number(a.x !== null) - Number(b.x !== null) ||
                a.name.localeCompare(b.name),
        );
});

const unplacedCount = computed(
    () => markers.value.filter((m) => m.x === null).length,
);

const geometryType = computed(() =>
    form.value?.marker_type_id
        ? types.value[form.value.marker_type_id]?.geometry
        : 'point',
);

function blank(x: number | null, y: number | null): MarkerForm {
    return {
        marker_type_id: addTypeId.value,
        name: '',
        description: null,
        x,
        y,
        geometry: null,
        floor: null,
        variant: editorVariant.value === 'all' ? null : editorVariant.value,
        loot_table_id: null,
        status: 'published',
        is_visible: true,
        source_id: null,
        source_url: null,
        source_note: null,
        confidence_override: null,
        introduced_version_id: null,
        verified_version_id: null,
        metadata: {},
        items: [],
        objectives: [],
    };
}

async function open(id: number): Promise<void> {
    errors.value = {};

    try {
        const data = await http<MarkerForm>(
            'get',
            showMarker([props.map.slug, id]).url,
        );
        form.value = { ...data, metadata: { ...data.metadata } };
    } catch {
        toast.error('Could not load marker.');
    }
}

function upsertLocal(data: MarkerForm): void {
    const entry: EditorMarker = {
        id: data.id!,
        type_id: data.marker_type_id!,
        marker_type_id: data.marker_type_id!,
        name: data.name,
        x: data.x,
        y: data.y,
        geometry: data.geometry,
        floor: data.floor,
        variant: data.variant,
        status: data.status,
        is_visible: data.is_visible,
        confidence: data.confidence_override ?? data.confidence ?? 0,
        open_reports_count: data.open_reports_count ?? 0,
    };
    const index = markers.value.findIndex((m) => m.id === entry.id);

    if (index === -1) {
        markers.value = [...markers.value, entry];
    } else {
        markers.value = markers.value.map((m) =>
            m.id === entry.id ? entry : m,
        );
    }
}

async function save(): Promise<void> {
    if (!form.value) {
        return;
    }

    saving.value = true;
    errors.value = {};
    const payload = {
        ...form.value,
        items: form.value.items.map(({ id, likelihood, note }) => ({
            id,
            likelihood,
            note,
        })),
        objectives: form.value.objectives.map(({ id, role }) => ({ id, role })),
    };

    try {
        const data = form.value.id
            ? await http<MarkerForm>(
                  'patch',
                  update([props.map.slug, form.value.id]).url,
                  payload,
              )
            : await http<MarkerForm>(
                  'post',
                  store(props.map.slug).url,
                  payload,
              );
        form.value = data;
        upsertLocal(data);
        toast.success('Marker saved');
    } catch (e) {
        if (e instanceof HttpError) {
            errors.value = e.data.errors ?? {};
            toast.error(e.firstError());
        }
    } finally {
        saving.value = false;
    }
}

async function patchPosition(
    id: number,
    x: number | null,
    y: number | null,
): Promise<void> {
    try {
        const data = await http<MarkerForm>(
            'patch',
            update([props.map.slug, id]).url,
            { x, y },
        );
        upsertLocal(data);

        if (form.value?.id === id) {
            form.value.x = data.x;
            form.value.y = data.y;
        }
    } catch (e) {
        toast.error(
            e instanceof HttpError ? e.firstError() : 'Could not move marker.',
        );
    }
}

async function remove(): Promise<void> {
    if (
        !form.value?.id ||
        !confirm(
            `Delete “${form.value.name}”? It can be restored from the database.`,
        )
    ) {
        return;
    }

    await http('delete', destroy([props.map.slug, form.value.id]).url);
    markers.value = markers.value.filter((m) => m.id !== form.value?.id);
    form.value = null;
    toast.success('Marker deleted');
}

async function duplicateMarker(): Promise<void> {
    if (!form.value?.id) {
        return;
    }

    const data = await http<MarkerForm>(
        'post',
        duplicate([props.map.slug, form.value.id]).url,
    );
    upsertLocal(data);
    form.value = data;
    toast.success('Duplicated');
}

async function verifyMarker(): Promise<void> {
    if (!form.value?.id) {
        return;
    }

    const data = await http<MarkerForm>(
        'post',
        verify([props.map.slug, form.value.id]).url,
    );
    form.value = data;
    upsertLocal(data);
    toast.success('Verified for the current game version');
}

function onMapClick(point: { x: number; y: number }): void {
    if (mode.value === 'add') {
        form.value = blank(point.x, point.y);
        errors.value = {};
        mode.value = 'select';
    } else if (mode.value === 'place' && form.value?.id) {
        form.value.x = point.x;
        form.value.y = point.y;
        void patchPosition(form.value.id, point.x, point.y);
        mode.value = 'select';
    } else if (mode.value === 'shape' && form.value) {
        form.value.geometry = [
            ...(form.value.geometry ?? []),
            [point.x, point.y],
        ];

        if (form.value.x === null) {
            form.value.x = point.x;
            form.value.y = point.y;
        }
    }
}

function onMoved(payload: { id: number; x: number; y: number }): void {
    void patchPosition(payload.id, payload.x, payload.y);
}

function addItem(event: Event): void {
    const select = event.target as HTMLSelectElement;
    const id = Number(select.value);
    const item = props.options.items.find((i) => i.id === id);

    if (form.value && item && !form.value.items.some((i) => i.id === id)) {
        form.value.items.push({
            id,
            name: item.name,
            likelihood: null,
            note: null,
        });
    }

    select.value = '';
}

function addObjective(event: Event): void {
    const select = event.target as HTMLSelectElement;
    const id = Number(select.value);
    const objective = props.options.objectives.find((o) => o.id === id);

    if (
        form.value &&
        objective &&
        !form.value.objectives.some((o) => o.id === id)
    ) {
        form.value.objectives.push({ id, name: objective.name, role: null });
    }

    select.value = '';
}

function focusSelected(): void {
    if (form.value?.x != null && form.value.y != null) {
        gameMap.value?.flyTo(form.value.x, form.value.y, 2);
    }
}

watch(selectedId, focusSelected);

onMounted(() => {
    const id = Number(
        new URLSearchParams(window.location.search).get('marker'),
    );

    if (id) {
        void open(id);
    }
});

const selectClass =
    'h-9 w-full rounded-md border bg-transparent px-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none [&>option]:bg-popover';
</script>

<template>
    <Head :title="`Edit ${map.name}`" />

    <div
        class="dark flex h-svh flex-col overflow-hidden bg-background text-foreground"
    >
        <header
            class="flex h-12 shrink-0 items-center gap-3 border-b bg-card px-3"
        >
            <Link
                :href="mapsIndex()"
                class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Maps
            </Link>
            <BrandMark compact />
            <h1 class="text-base font-semibold tracking-wide uppercase">
                Editing {{ map.name }}
            </h1>
            <div class="ml-auto flex items-center gap-2">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="editMap(map.slug)"
                        >Map settings &amp; image</Link
                    >
                </Button>
                <Button as-child variant="outline" size="sm">
                    <a
                        :href="publicMap(map.slug).url"
                        target="_blank"
                        rel="noopener"
                        >View public <ExternalLink class="size-3.5"
                    /></a>
                </Button>
            </div>
        </header>

        <div class="flex min-h-0 flex-1">
            <!-- Marker list -->
            <aside class="flex w-72 shrink-0 flex-col border-r bg-card">
                <div class="space-y-2 border-b p-3">
                    <div class="flex items-center gap-2">
                        <select
                            v-model.number="addTypeId"
                            :class="selectClass"
                            aria-label="Type for new markers"
                        >
                            <optgroup
                                v-for="category in categories"
                                :key="category.id"
                                :label="category.name"
                            >
                                <option
                                    v-for="type in category.types"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.name }}
                                </option>
                            </optgroup>
                        </select>
                        <Button
                            size="sm"
                            :variant="mode === 'add' ? 'default' : 'secondary'"
                            :aria-pressed="mode === 'add'"
                            @click="mode = mode === 'add' ? 'select' : 'add'"
                        >
                            <Plus class="size-4" /> Add
                        </Button>
                    </div>
                    <div class="relative">
                        <Search
                            class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="filter"
                            class="pl-8"
                            placeholder="Filter markers"
                            aria-label="Filter markers"
                        />
                    </div>
                    <label
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <input v-model="showUnplacedOnly" type="checkbox" />
                        Only unplaced ({{ unplacedCount }})
                    </label>
                    <select
                        v-if="variantOptions.length"
                        v-model="editorVariant"
                        :class="selectClass"
                        aria-label="Raid variant"
                    >
                        <option value="all">All variants</option>
                        <option
                            v-for="v in variantOptions"
                            :key="v.key"
                            :value="v.key"
                        >
                            {{ v.label }} ({{ v.count }})
                        </option>
                    </select>
                </div>
                <ul class="min-h-0 flex-1 overflow-y-auto p-1">
                    <li v-for="marker in listed" :key="marker.id">
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-sm hover:bg-accent/60"
                            :class="{ 'bg-accent': selectedId === marker.id }"
                            @click="open(marker.id)"
                        >
                            <span
                                class="grid size-5 shrink-0 place-items-center rounded-full [&_svg]:size-3 [&_svg]:text-background"
                                :style="{
                                    background: types[marker.type_id]?.color,
                                }"
                                v-html="
                                    iconSvg(
                                        types[marker.type_id]?.icon ??
                                            'map-pin',
                                    )
                                "
                            />
                            <span class="min-w-0 flex-1 truncate">{{
                                marker.name
                            }}</span>
                            <MapPinOff
                                v-if="marker.x === null"
                                class="size-3.5 shrink-0 text-warning"
                                aria-label="Unplaced"
                            />
                            <span
                                v-if="marker.status !== 'published'"
                                class="text-[10px] text-warning uppercase"
                                >{{ marker.status }}</span
                            >
                            <span
                                v-if="marker.open_reports_count"
                                class="rounded bg-destructive/20 px-1 text-[10px] text-destructive"
                                >{{ marker.open_reports_count }}</span
                            >
                        </button>
                    </li>
                </ul>
            </aside>

            <!-- Map -->
            <div class="relative isolate min-w-0 flex-1">
                <GameMap
                    ref="gameMap"
                    :map="map"
                    :markers="mapMarkers"
                    :types="types"
                    :visible-type-ids="allTypeIds"
                    :selected-id="selectedId"
                    :show-labels="true"
                    draggable
                    :crosshair="mode !== 'select'"
                    @select="open"
                    @map-click="onMapClick"
                    @marker-moved="onMoved"
                />
                <div
                    v-if="mode !== 'select'"
                    class="absolute top-3 left-1/2 z-[500] flex -translate-x-1/2 items-center gap-2 rounded-full border bg-card px-4 py-2 text-sm shadow-lg"
                >
                    <MousePointerClick class="size-4 text-warning" />
                    <span v-if="mode === 'add'"
                        >Click the map to place a new
                        {{
                            addTypeId ? types[addTypeId]?.name : 'marker'
                        }}</span
                    >
                    <span v-else-if="mode === 'place'"
                        >Click the map to position “{{ form?.name }}”</span
                    >
                    <span v-else
                        >Click to add shape vertices ({{
                            form?.geometry?.length ?? 0
                        }})</span
                    >
                    <button
                        type="button"
                        aria-label="Done"
                        class="text-muted-foreground hover:text-foreground"
                        @click="mode = 'select'"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                <p
                    class="pointer-events-none absolute bottom-3 left-3 z-[500] rounded bg-card/90 px-2 py-1 text-xs text-muted-foreground"
                >
                    Drag markers to move them. Positions save automatically.
                </p>
            </div>

            <!-- Form -->
            <aside
                v-if="form"
                class="flex w-96 shrink-0 flex-col border-l bg-card"
            >
                <div class="flex items-center gap-2 border-b p-3">
                    <Pencil class="size-4 text-muted-foreground" />
                    <h2 class="flex-1 truncate font-sans text-sm font-semibold">
                        {{ form.id ? form.name : 'New marker' }}
                    </h2>
                    <button
                        type="button"
                        aria-label="Close"
                        class="text-muted-foreground hover:text-foreground"
                        @click="form = null"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                <form
                    class="min-h-0 flex-1 space-y-3 overflow-y-auto p-3 text-sm"
                    @submit.prevent="save"
                >
                    <div class="space-y-1">
                        <Label for="m-type">Type</Label>
                        <select
                            id="m-type"
                            v-model.number="form.marker_type_id"
                            :class="selectClass"
                        >
                            <optgroup
                                v-for="category in categories"
                                :key="category.id"
                                :label="category.name"
                            >
                                <option
                                    v-for="type in category.types"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.name }}
                                </option>
                            </optgroup>
                        </select>
                        <InputError :message="errors.marker_type_id?.[0]" />
                    </div>
                    <div class="space-y-1">
                        <Label for="m-name">Name</Label>
                        <Input
                            id="m-name"
                            v-model="form.name"
                            required
                            maxlength="255"
                        />
                        <InputError :message="errors.name?.[0]" />
                    </div>
                    <div class="space-y-1">
                        <Label for="m-desc">Description</Label>
                        <textarea
                            id="m-desc"
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        />
                    </div>
                    <div class="space-y-1">
                        <Label for="m-cond">Conditions (extracts, keys…)</Label>
                        <Input
                            id="m-cond"
                            v-model="form.metadata.conditions as string"
                            maxlength="1000"
                        />
                    </div>

                    <div class="rounded-md border p-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Position</span>
                            <span v-if="form.x !== null" class="font-mono"
                                >x {{ form.x?.toFixed(2) }} · y
                                {{ form.y?.toFixed(2) }}</span
                            >
                            <span v-else class="text-warning">Not placed</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Button
                                v-if="form.id"
                                type="button"
                                size="sm"
                                variant="secondary"
                                @click="mode = 'place'"
                            >
                                <MousePointerClick class="size-4" />
                                {{
                                    form.x === null
                                        ? 'Place on map'
                                        : 'Re-place'
                                }}
                            </Button>
                            <Button
                                v-if="form.id && form.x !== null"
                                type="button"
                                size="sm"
                                variant="ghost"
                                @click="patchPosition(form.id, null, null)"
                            >
                                <MapPinOff class="size-4" /> Unplace
                            </Button>
                            <template v-if="geometryType !== 'point'">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="secondary"
                                    @click="mode = 'shape'"
                                >
                                    <Shapes class="size-4" /> Draw shape
                                </Button>
                                <Button
                                    v-if="form.geometry?.length"
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    @click="form.geometry = null"
                                >
                                    Clear shape
                                </Button>
                            </template>
                        </div>
                        <InputError
                            :message="errors.x?.[0] ?? errors.geometry?.[0]"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1">
                            <Label for="m-status">Status</Label>
                            <select
                                id="m-status"
                                v-model="form.status"
                                :class="selectClass"
                            >
                                <option
                                    v-for="s in options.statuses"
                                    :key="s.value"
                                    :value="s.value"
                                >
                                    {{ s.label }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <Label for="m-floor">Floor</Label>
                            <Input
                                id="m-floor"
                                :model-value="form.floor ?? ''"
                                maxlength="50"
                                @update:model-value="
                                    form.floor = String($event) || null
                                "
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1">
                            <Label for="m-variant">Raid variant</Label>
                            <select
                                id="m-variant"
                                v-model="form.variant"
                                :class="selectClass"
                            >
                                <option :value="null">All variants</option>
                                <option
                                    v-for="v in variantOptions"
                                    :key="v.key"
                                    :value="v.key"
                                >
                                    {{ v.label }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <Label for="m-loot">Loot pool</Label>
                            <select
                                id="m-loot"
                                v-model.number="form.loot_table_id"
                                :class="selectClass"
                            >
                                <option :value="null">None</option>
                                <option
                                    v-for="t in options.lootTables"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-2"
                        ><input v-model="form.is_visible" type="checkbox" />
                        Visible on public map</label
                    >

                    <fieldset class="space-y-2 rounded-md border p-2">
                        <legend class="px-1 text-xs text-muted-foreground">
                            Source &amp; quality
                        </legend>
                        <select
                            v-model.number="form.source_id"
                            :class="selectClass"
                            aria-label="Source"
                        >
                            <option :value="null">No source</option>
                            <option
                                v-for="s in options.sources"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                        <Input
                            :model-value="form.source_url ?? ''"
                            type="url"
                            placeholder="Source URL"
                            aria-label="Source URL"
                            @update:model-value="
                                form.source_url = String($event) || null
                            "
                        />
                        <InputError :message="errors.source_url?.[0]" />
                        <textarea
                            v-model="form.source_note"
                            rows="2"
                            placeholder="Source note / quote"
                            aria-label="Source note"
                            class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                        />
                        <div class="grid grid-cols-2 gap-2">
                            <select
                                v-model.number="form.introduced_version_id"
                                :class="selectClass"
                                aria-label="Introduced in version"
                            >
                                <option :value="null">
                                    Introduced: unknown
                                </option>
                                <option
                                    v-for="v in options.versions"
                                    :key="v.id"
                                    :value="v.id"
                                >
                                    {{ v.version }}
                                </option>
                            </select>
                            <select
                                v-model.number="form.verified_version_id"
                                :class="selectClass"
                                aria-label="Verified for version"
                            >
                                <option :value="null">Verified: never</option>
                                <option
                                    v-for="v in options.versions"
                                    :key="v.id"
                                    :value="v.id"
                                >
                                    {{ v.version }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <Label
                                for="m-override"
                                class="text-xs whitespace-nowrap"
                                >Confidence override</Label
                            >
                            <Input
                                id="m-override"
                                :model-value="form.confidence_override ?? ''"
                                type="number"
                                min="0"
                                max="100"
                                placeholder="auto"
                                @update:model-value="
                                    form.confidence_override =
                                        $event === '' ? null : Number($event)
                                "
                            />
                        </div>
                        <ConfidenceMeter
                            v-if="form.id"
                            :score="
                                form.confidence_override ?? form.confidence ?? 0
                            "
                        />
                    </fieldset>

                    <fieldset class="space-y-2 rounded-md border p-2">
                        <legend class="px-1 text-xs text-muted-foreground">
                            Known loot
                        </legend>
                        <div
                            v-for="(item, i) in form.items"
                            :key="item.id"
                            class="flex items-center gap-1"
                        >
                            <span class="min-w-0 flex-1 truncate">{{
                                item.name ?? `#${item.id}`
                            }}</span>
                            <select
                                v-model="item.likelihood"
                                class="h-7 rounded border bg-transparent px-1 text-xs [&>option]:bg-popover"
                                aria-label="Likelihood"
                            >
                                <option :value="null">likelihood?</option>
                                <option value="guaranteed">guaranteed</option>
                                <option value="common">common</option>
                                <option value="uncommon">uncommon</option>
                                <option value="rare">rare</option>
                            </select>
                            <button
                                type="button"
                                aria-label="Remove item"
                                class="p-1 text-muted-foreground hover:text-destructive"
                                @click="form.items.splice(i, 1)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                        <select
                            :class="selectClass"
                            aria-label="Add item"
                            @change="addItem"
                        >
                            <option value="">+ Link an item…</option>
                            <option
                                v-for="item in options.items"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ item.name }}
                            </option>
                        </select>
                    </fieldset>

                    <fieldset class="space-y-2 rounded-md border p-2">
                        <legend class="px-1 text-xs text-muted-foreground">
                            Objectives
                        </legend>
                        <div
                            v-for="(objective, i) in form.objectives"
                            :key="objective.id"
                            class="flex items-center gap-1"
                        >
                            <span class="min-w-0 flex-1 truncate">{{
                                objective.name ?? `#${objective.id}`
                            }}</span>
                            <input
                                v-model="objective.role"
                                placeholder="role"
                                class="h-7 w-24 rounded border bg-transparent px-1 text-xs"
                                aria-label="Role"
                            />
                            <button
                                type="button"
                                aria-label="Remove objective"
                                class="p-1 text-muted-foreground hover:text-destructive"
                                @click="form.objectives.splice(i, 1)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                        <select
                            :class="selectClass"
                            aria-label="Add objective"
                            @change="addObjective"
                        >
                            <option value="">+ Link an objective…</option>
                            <option
                                v-for="o in options.objectives"
                                :key="o.id"
                                :value="o.id"
                            >
                                {{ o.name }}
                            </option>
                        </select>
                    </fieldset>

                    <p v-if="form.id" class="text-xs text-muted-foreground">
                        {{ form.confirmations_count ?? 0 }} confirmations ·
                        {{ form.open_reports_count ?? 0 }} open reports · last
                        verified
                        {{
                            form.last_verified_at
                                ? new Date(
                                      form.last_verified_at,
                                  ).toLocaleDateString()
                                : 'never'
                        }}
                    </p>
                </form>
                <div class="grid grid-cols-2 gap-2 border-t p-3">
                    <Button :disabled="saving" class="col-span-2" @click="save"
                        ><Save class="size-4" /> Save</Button
                    >
                    <template v-if="form.id">
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="verifyMarker"
                            ><BadgeCheck class="size-4" /> Verify now</Button
                        >
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="duplicateMarker"
                            ><Copy class="size-4" /> Duplicate</Button
                        >
                        <Button
                            variant="ghost"
                            size="sm"
                            class="col-span-2 text-destructive"
                            @click="remove"
                            ><Trash2 class="size-4" /> Delete</Button
                        >
                    </template>
                </div>
            </aside>
        </div>
        <Toaster />
    </div>
</template>
