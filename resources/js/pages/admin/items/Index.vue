<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { rowClass, tableClass, theadClass } from '@/components/admin/types';
import type { Paginated } from '@/components/admin/types';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit, index } from '@/routes/admin/items';

type ItemRow = {
    id: number;
    slug: string;
    name: string;
    category: string | null;
    rarity: string | null;
    status: string;
    confidence: number;
    markers_count: number;
    uses_count: number;
};

const props = defineProps<{
    items: Paginated<ItemRow>;
    filters: { search: string };
}>();

const search = ref(props.filters.search);
let timer: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(index.url(), value ? { search: value } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
});

onBeforeUnmount(() => clearTimeout(timer));

function remove(item: ItemRow): void {
    if (window.confirm(`Delete the item "${item.name}"?`)) {
        router.delete(destroy(item.slug).url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Items" />

    <PageHeader
        title="Items"
        description="The item database: loot, crafting materials and quest items."
    >
        <template #actions>
            <Button as-child>
                <Link :href="create()"><Plus /> New item</Link>
            </Button>
        </template>
    </PageHeader>

    <div class="relative mb-4 max-w-sm">
        <label for="item-search" class="sr-only">Search items</label>
        <Search
            class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            id="item-search"
            v-model="search"
            type="search"
            placeholder="Search by name…"
            class="pl-8"
        />
    </div>

    <template v-if="items.data.length">
        <div
            class="max-h-[calc(100svh-15rem)] overflow-auto rounded-lg border bg-card"
        >
            <table :class="tableClass">
                <thead :class="theadClass">
                    <tr>
                        <th scope="col" class="px-3 py-2">Name</th>
                        <th scope="col" class="px-3 py-2">Category</th>
                        <th scope="col" class="px-3 py-2">Rarity</th>
                        <th scope="col" class="px-3 py-2">Status</th>
                        <th scope="col" class="px-3 py-2">Confidence</th>
                        <th scope="col" class="px-3 py-2 text-right">
                            Markers
                        </th>
                        <th scope="col" class="px-3 py-2 text-right">
                            Used in
                        </th>
                        <th scope="col" class="px-3 py-2">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in items.data"
                        :key="item.id"
                        :class="rowClass"
                    >
                        <td class="px-3 py-2">
                            <Link
                                :href="edit(item.slug)"
                                class="font-medium hover:text-primary"
                                >{{ item.name }}</Link
                            >
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ item.category ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ item.rarity ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            <StatusBadge :status="item.status" />
                        </td>
                        <td class="px-3 py-2">
                            <ConfidenceMeter :score="item.confidence" compact />
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            {{ item.markers_count }}
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            {{ item.uses_count }}
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end gap-1">
                                <Button as-child size="icon-sm" variant="ghost">
                                    <Link
                                        :href="edit(item.slug)"
                                        :aria-label="`Edit ${item.name}`"
                                        ><Pencil
                                    /></Link>
                                </Button>
                                <Button
                                    size="icon-sm"
                                    variant="ghost"
                                    class="text-destructive hover:text-destructive"
                                    :aria-label="`Delete ${item.name}`"
                                    @click="remove(item)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :paginator="items" />
    </template>
    <EmptyState
        v-else
        :title="filters.search ? 'No matching items' : 'No items yet'"
        :description="
            filters.search
                ? `Nothing matches “${filters.search}”.`
                : 'Add items manually or import them from a data file.'
        "
    />
</template>
