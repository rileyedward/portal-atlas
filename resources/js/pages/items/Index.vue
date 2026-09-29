<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import {
    RARITY_ORDER,
    rarityRank,
    selectClass,
} from '@/components/public/options';
import RarityChip from '@/components/public/RarityChip.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { show } from '@/routes/items';

type ItemRow = {
    slug: string;
    name: string;
    category: string | null;
    rarity: string | null;
    locations: number;
    confidence: number;
};

type SortKey = 'name' | 'category' | 'rarity' | 'locations' | 'confidence';

const props = defineProps<{ items: ItemRow[]; categories: string[] }>();

const PAGE_SIZE = 100;

const query = ref('');
const category = ref('');
const rarity = ref('');
const sortKey = ref<SortKey>('name');
const sortDir = ref<'asc' | 'desc'>('asc');
const limit = ref(PAGE_SIZE);

const columns: { key: SortKey; label: string; class?: string }[] = [
    { key: 'name', label: 'Item' },
    { key: 'category', label: 'Category', class: 'hidden md:table-cell' },
    { key: 'rarity', label: 'Rarity' },
    { key: 'locations', label: 'Locations', class: 'hidden sm:table-cell' },
    { key: 'confidence', label: 'Confidence', class: 'hidden lg:table-cell' },
];

const filtered = computed(() => {
    const needle = query.value.trim().toLowerCase();

    const rows = props.items.filter(
        (item) =>
            (!needle || item.name.toLowerCase().includes(needle)) &&
            (!category.value || item.category === category.value) &&
            (!rarity.value ||
                (item.rarity ?? '').toLowerCase() === rarity.value),
    );

    const dir = sortDir.value === 'asc' ? 1 : -1;

    return [...rows].sort((a, b) => {
        let result: number;

        switch (sortKey.value) {
            case 'category':
                result = (a.category ?? '￿').localeCompare(b.category ?? '￿');
                break;
            case 'rarity':
                result = rarityRank(a.rarity) - rarityRank(b.rarity);
                break;
            case 'locations':
                result = a.locations - b.locations;
                break;
            case 'confidence':
                result = a.confidence - b.confidence;
                break;
            default:
                result = 0;
        }

        return result * dir || a.name.localeCompare(b.name);
    });
});

const visible = computed(() => filtered.value.slice(0, limit.value));

watch([query, category, rarity, sortKey, sortDir], () => {
    limit.value = PAGE_SIZE;
});

function sortBy(key: SortKey): void {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value =
            key === 'locations' || key === 'confidence' ? 'desc' : 'asc';
    }
}

function ariaSort(key: SortKey): 'ascending' | 'descending' | 'none' {
    if (sortKey.value !== key) {
        return 'none';
    }

    return sortDir.value === 'asc' ? 'ascending' : 'descending';
}

function reset(): void {
    query.value = '';
    category.value = '';
    rarity.value = '';
}
</script>

<template>
    <Head title="Item database">
        <meta
            name="description"
            content="Searchable Active Matter item database: categories, rarity, known loot locations and confidence scores for every item, with sources."
        />
    </Head>

    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-6">
            <h1
                class="font-display text-3xl font-semibold tracking-wide uppercase"
            >
                Item database
            </h1>
            <p class="mt-1 max-w-2xl text-muted-foreground">
                Where to find each item and whether it is worth keeping.
                Locations and details are only listed once they're documented.
            </p>
        </header>

        <form
            class="mb-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]"
            role="search"
            @submit.prevent
        >
            <div class="relative">
                <label for="item-filter" class="sr-only"
                    >Filter items by name</label
                >
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    id="item-filter"
                    v-model="query"
                    type="search"
                    placeholder="Filter by name…"
                    class="pl-9"
                    autocomplete="off"
                />
            </div>
            <div>
                <label for="item-category" class="sr-only">Category</label>
                <select
                    id="item-category"
                    v-model="category"
                    :class="selectClass"
                    class="sm:w-48"
                >
                    <option value="">All categories</option>
                    <option
                        v-for="name in categories"
                        :key="name"
                        :value="name"
                    >
                        {{ name }}
                    </option>
                </select>
            </div>
            <div>
                <label for="item-rarity" class="sr-only">Rarity</label>
                <select
                    id="item-rarity"
                    v-model="rarity"
                    :class="selectClass"
                    class="capitalize sm:w-40"
                >
                    <option value="">All rarities</option>
                    <option
                        v-for="tier in RARITY_ORDER"
                        :key="tier"
                        :value="tier"
                    >
                        {{ tier }}
                    </option>
                </select>
            </div>
        </form>

        <p class="mb-2 text-sm text-muted-foreground" aria-live="polite">
            {{ filtered.length }} of {{ items.length }} items
        </p>

        <div
            v-if="filtered.length"
            class="overflow-hidden rounded-lg border bg-card"
        >
            <table class="w-full text-sm">
                <thead
                    class="border-b bg-muted/30 text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            :aria-sort="ariaSort(column.key)"
                            class="px-3 py-2 font-medium"
                            :class="column.class"
                        >
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 tracking-wide uppercase hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                @click="sortBy(column.key)"
                            >
                                {{ column.label }}
                                <ArrowUp
                                    v-if="
                                        sortKey === column.key &&
                                        sortDir === 'asc'
                                    "
                                    class="size-3"
                                />
                                <ArrowDown
                                    v-else-if="sortKey === column.key"
                                    class="size-3"
                                />
                                <ArrowUpDown v-else class="size-3 opacity-40" />
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="item in visible"
                        :key="item.slug"
                        class="hover:bg-accent/30"
                    >
                        <td class="px-3 py-2.5">
                            <Link
                                :href="show(item.slug)"
                                class="font-medium text-primary hover:underline"
                            >
                                {{ item.name }}
                            </Link>
                            <span
                                class="block text-xs text-muted-foreground md:hidden"
                            >
                                {{ item.category ?? 'Unknown category' }}
                                <span class="sm:hidden">
                                    · {{ item.locations }} known locations</span
                                >
                            </span>
                        </td>
                        <td class="hidden px-3 py-2.5 md:table-cell">
                            <span v-if="item.category">{{
                                item.category
                            }}</span>
                            <span v-else class="text-muted-foreground"
                                >Unknown</span
                            >
                        </td>
                        <td class="px-3 py-2.5">
                            <RarityChip :rarity="item.rarity" />
                        </td>
                        <td
                            class="hidden px-3 py-2.5 tabular-nums sm:table-cell"
                        >
                            <span
                                :class="{
                                    'text-muted-foreground':
                                        item.locations === 0,
                                }"
                            >
                                {{
                                    item.locations === 0
                                        ? 'None mapped'
                                        : item.locations
                                }}
                            </span>
                        </td>
                        <td class="hidden px-3 py-2.5 lg:table-cell">
                            <ConfidenceMeter :score="item.confidence" compact />
                        </td>
                    </tr>
                </tbody>
            </table>
            <div
                v-if="visible.length < filtered.length"
                class="border-t p-3 text-center"
            >
                <Button variant="secondary" @click="limit += PAGE_SIZE">
                    Show
                    {{ Math.min(PAGE_SIZE, filtered.length - visible.length) }}
                    more ({{ filtered.length - visible.length }} remaining)
                </Button>
            </div>
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
        >
            <template v-if="items.length">
                No items match these filters.
                <Button variant="link" @click="reset">Clear filters</Button>
            </template>
            <template v-else>No items have been published yet.</template>
        </div>
    </div>
</template>
