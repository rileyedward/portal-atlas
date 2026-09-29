<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useDebounceFn, useMagicKeys, whenever } from '@vueuse/core';
import {
    CornerDownLeft,
    Crosshair,
    Loader2,
    Map as MapIcon,
    MapPin,
    Package,
    Search,
} from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { useFeedback } from '@/composables/useFeedback';
import { http } from '@/lib/http';
import { search as searchRoute } from '@/routes/api';
import { show as showItem } from '@/routes/items';
import { show as showMap } from '@/routes/maps';
import { show as showObjective } from '@/routes/objectives';
import type { SearchResponse, SearchResult } from '@/types/game';

const props = withDefaults(
    defineProps<{ currentMap?: string | null; compact?: boolean }>(),
    { currentMap: null, compact: false },
);

const emit = defineEmits<{
    /** Emitted instead of navigating when a marker on the current map is chosen. */
    focusMarker: [id: number];
}>();

const open = ref(false);
const query = ref('');
const loading = ref(false);
const response = ref<SearchResponse | null>(null);
const active = ref(0);
const input = ref<HTMLInputElement | null>(null);
let controller: AbortController | null = null;

const keys = useMagicKeys({
    passive: false,
    onEventFired(event) {
        const target = event.target as HTMLElement | null;
        const typing =
            target?.tagName === 'INPUT' ||
            target?.tagName === 'TEXTAREA' ||
            target?.isContentEditable;

        if (
            event.key === '/' &&
            event.type === 'keydown' &&
            !typing &&
            !open.value
        ) {
            event.preventDefault();
            open.value = true;
        }

        if (
            (event.metaKey || event.ctrlKey) &&
            event.key === 'k' &&
            event.type === 'keydown'
        ) {
            event.preventDefault();
            open.value = !open.value;
        }
    },
});
whenever(keys.escape, () => (open.value = false));

const flat = computed<SearchResult[]>(
    () => response.value?.groups.flatMap((g) => g.results) ?? [],
);

const run = useDebounceFn(async (term: string) => {
    controller?.abort();

    if (term.trim().length < 2) {
        response.value = null;
        loading.value = false;

        return;
    }

    controller = new AbortController();
    loading.value = true;

    try {
        response.value = await http<SearchResponse>(
            'get',
            searchRoute({
                query: { q: term, map: props.currentMap ?? undefined },
            }).url,
            undefined,
            controller.signal,
        );
        active.value = 0;
    } catch (e) {
        if ((e as Error).name !== 'AbortError') {
            response.value = null;
        }
    } finally {
        loading.value = false;
    }
}, 200);

watch(query, (term) => {
    loading.value = term.trim().length >= 2;
    run(term);
});

watch(open, async (isOpen) => {
    if (isOpen) {
        await nextTick();
        input.value?.focus();
    }
});

function goToMarker(mapSlug: string, markerId: number): void {
    open.value = false;

    if (mapSlug === props.currentMap) {
        emit('focusMarker', markerId);

        return;
    }

    router.visit(showMap(mapSlug, { query: { marker: markerId } }).url);
}

function choose(result: SearchResult): void {
    if (result.type === 'marker' && result.map) {
        goToMarker(result.map.slug, result.id);

        return;
    }

    open.value = false;

    if (result.type === 'item' && result.slug) {
        router.visit(showItem(result.slug).url);
    } else if (result.type === 'objective' && result.slug) {
        router.visit(showObjective(result.slug).url);
    } else if (result.type === 'map' && result.slug) {
        router.visit(showMap(result.slug).url);
    }
}

const { openFeedback } = useFeedback();

function reportMissing(): void {
    const term = query.value.trim();
    open.value = false;
    openFeedback({
        type: 'missing_data',
        context: `Search: ${term}`,
        message: `I searched for “${term}” and found nothing. It should show: `,
    });
}

function onKeydown(event: KeyboardEvent): void {
    if (!flat.value.length) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        active.value = (active.value + 1) % flat.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        active.value =
            (active.value - 1 + flat.value.length) % flat.value.length;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        choose(flat.value[active.value]);
    }
}

function indexOf(result: SearchResult): number {
    return flat.value.indexOf(result);
}

const icons = {
    item: Package,
    marker: MapPin,
    objective: Crosshair,
    map: MapIcon,
};
</script>

<template>
    <button
        type="button"
        class="group flex h-9 items-center gap-2 rounded-md border bg-card/80 px-3 text-sm text-muted-foreground shadow-sm backdrop-blur transition hover:border-primary/50 hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        :class="compact ? 'w-9 justify-center px-0' : 'w-full max-w-md'"
        aria-label="Search items, locations and objectives"
        @click="open = true"
    >
        <Search class="size-4 shrink-0" />
        <template v-if="!compact">
            <span class="truncate">Search items, places, objectives…</span>
            <kbd
                class="ml-auto hidden rounded border bg-muted px-1.5 font-mono text-[10px] sm:inline"
                >/</kbd
            >
        </template>
    </button>

    <Dialog v-model:open="open">
        <DialogContent
            class="top-[12vh] translate-y-0 gap-0 overflow-hidden p-0 sm:max-w-xl"
            :show-close-button="false"
        >
            <DialogTitle class="sr-only">Search</DialogTitle>
            <DialogDescription class="sr-only">
                Search the map database. Use arrow keys to move and Enter to
                open.
            </DialogDescription>
            <div class="flex items-center gap-3 border-b px-4">
                <Search class="size-4 text-muted-foreground" />
                <input
                    ref="input"
                    v-model="query"
                    type="search"
                    role="combobox"
                    aria-expanded="true"
                    aria-controls="search-results"
                    :aria-activedescendant="
                        flat.length ? `search-result-${active}` : undefined
                    "
                    class="h-12 w-full bg-transparent text-base outline-none placeholder:text-muted-foreground"
                    placeholder="Try “radio”, “extraction”, “fire station”…"
                    @keydown="onKeydown"
                />
                <Loader2
                    v-if="loading"
                    class="size-4 animate-spin text-muted-foreground"
                />
            </div>

            <div
                id="search-results"
                role="listbox"
                class="max-h-[60vh] overflow-y-auto p-2"
            >
                <p
                    v-if="query.trim().length < 2"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    Search every map at once. Press
                    <kbd class="rounded border px-1 font-mono text-xs">/</kbd>
                    anywhere to open this.
                </p>
                <p
                    v-else-if="!loading && response && response.total === 0"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    Nothing found for “{{ query }}”. It may not be documented
                    yet.
                    <button
                        type="button"
                        class="mt-2 block w-full text-primary hover:underline"
                        @click="reportMissing"
                    >
                        Tell us what's missing
                    </button>
                </p>

                <section
                    v-for="group in response?.groups ?? []"
                    :key="group.key"
                    class="mb-2"
                >
                    <h3
                        class="px-3 pt-2 pb-1 font-sans text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        {{ group.label }}
                    </h3>
                    <div
                        v-for="result in group.results"
                        :id="`search-result-${indexOf(result)}`"
                        :key="`${result.type}-${result.id}`"
                        role="option"
                        :aria-selected="indexOf(result) === active"
                        class="cursor-pointer rounded-md px-3 py-2"
                        :class="
                            indexOf(result) === active
                                ? 'bg-accent'
                                : 'hover:bg-accent/60'
                        "
                        @mouseenter="active = indexOf(result)"
                        @click="choose(result)"
                    >
                        <div class="flex items-center gap-3">
                            <component
                                :is="icons[result.type]"
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium">
                                    {{ result.name }}
                                </div>
                                <div
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ result.subtitle
                                    }}<template v-if="result.rarity">
                                        · {{ result.rarity }}</template
                                    >
                                    <template
                                        v-if="
                                            result.type === 'marker' &&
                                            result.x === null
                                        "
                                    >
                                        · position not mapped yet</template
                                    >
                                </div>
                            </div>
                            <CornerDownLeft
                                v-if="indexOf(result) === active"
                                class="size-3.5 text-muted-foreground"
                            />
                        </div>
                        <div
                            v-if="result.found_at?.length"
                            class="mt-2 ml-7 space-y-1 border-l pl-3"
                        >
                            <div
                                v-for="place in result.found_at"
                                :key="place.map.slug"
                                class="text-xs"
                            >
                                <span class="font-medium">{{
                                    place.map.name
                                }}</span>
                                <span class="text-muted-foreground"> — </span>
                                <template
                                    v-for="(marker, i) in place.markers"
                                    :key="marker.id"
                                >
                                    <button
                                        type="button"
                                        class="text-primary hover:underline"
                                        @click.stop="
                                            goToMarker(
                                                place.map.slug,
                                                marker.id,
                                            )
                                        "
                                    >
                                        {{ marker.name }}</button
                                    ><span v-if="i < place.markers.length - 1"
                                        >,
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div
                            v-else-if="result.type === 'item'"
                            class="mt-1 ml-7 text-xs text-muted-foreground"
                        >
                            Locations: unknown
                        </div>
                    </div>
                </section>
            </div>
        </DialogContent>
    </Dialog>
</template>
