<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { rowClass, tableClass, theadClass } from '@/components/admin/types';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit } from '@/routes/admin/objectives';

type ObjectiveRow = {
    id: number;
    slug: string;
    name: string;
    kind: string;
    map: string | null;
    status: string;
    confidence: number;
    markers_count: number;
    items_count: number;
};

const props = defineProps<{ objectives: ObjectiveRow[] }>();

const filter = ref('');
const visible = computed(() => {
    const needle = filter.value.trim().toLowerCase();

    return needle
        ? props.objectives.filter((o) =>
              `${o.name} ${o.kind} ${o.map ?? ''}`
                  .toLowerCase()
                  .includes(needle),
          )
        : props.objectives;
});

function remove(objective: ObjectiveRow): void {
    if (window.confirm(`Delete the objective "${objective.name}"?`)) {
        router.delete(destroy(objective.slug).url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Objectives" />

    <PageHeader
        title="Objectives"
        description="Investigations, contracts and targets, with the items they require or reward."
    >
        <template #actions>
            <Button as-child>
                <Link :href="create()"><Plus /> New objective</Link>
            </Button>
        </template>
    </PageHeader>

    <template v-if="objectives.length">
        <div class="relative mb-4 max-w-sm">
            <label for="objective-filter" class="sr-only"
                >Filter objectives</label
            >
            <Search
                class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                id="objective-filter"
                v-model="filter"
                type="search"
                placeholder="Filter by name, kind or map…"
                class="pl-8"
            />
        </div>
        <div
            class="max-h-[calc(100svh-15rem)] overflow-auto rounded-lg border bg-card"
        >
            <table :class="tableClass">
                <thead :class="theadClass">
                    <tr>
                        <th scope="col" class="px-3 py-2">Name</th>
                        <th scope="col" class="px-3 py-2">Kind</th>
                        <th scope="col" class="px-3 py-2">Map</th>
                        <th scope="col" class="px-3 py-2">Status</th>
                        <th scope="col" class="px-3 py-2">Confidence</th>
                        <th scope="col" class="px-3 py-2 text-right">
                            Markers
                        </th>
                        <th scope="col" class="px-3 py-2 text-right">Items</th>
                        <th scope="col" class="px-3 py-2">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="objective in visible"
                        :key="objective.id"
                        :class="rowClass"
                    >
                        <td class="px-3 py-2">
                            <Link
                                :href="edit(objective.slug)"
                                class="font-medium hover:text-primary"
                                >{{ objective.name }}</Link
                            >
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ objective.kind }}
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ objective.map ?? 'Any' }}
                        </td>
                        <td class="px-3 py-2">
                            <StatusBadge :status="objective.status" />
                        </td>
                        <td class="px-3 py-2">
                            <ConfidenceMeter
                                :score="objective.confidence"
                                compact
                            />
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            {{ objective.markers_count }}
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            {{ objective.items_count }}
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end gap-1">
                                <Button as-child size="icon-sm" variant="ghost">
                                    <Link
                                        :href="edit(objective.slug)"
                                        :aria-label="`Edit ${objective.name}`"
                                        ><Pencil
                                    /></Link>
                                </Button>
                                <Button
                                    size="icon-sm"
                                    variant="ghost"
                                    class="text-destructive hover:text-destructive"
                                    :aria-label="`Delete ${objective.name}`"
                                    @click="remove(objective)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!visible.length">
                        <td
                            colspan="8"
                            class="px-3 py-6 text-center text-muted-foreground"
                        >
                            No objectives match “{{ filter }}”.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </template>
    <EmptyState
        v-else
        title="No objectives yet"
        description="Add objectives manually or import them from a data file."
    />
</template>
