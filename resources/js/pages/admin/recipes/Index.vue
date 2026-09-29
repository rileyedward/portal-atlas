<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { rowClass, tableClass, theadClass } from '@/components/admin/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, destroy, edit } from '@/routes/admin/recipes';

type RecipeRow = {
    id: number;
    name: string;
    kind: string;
    station: string | null;
    level: number | null;
    output: string | null;
    ingredients_count: number;
    status: string;
};

const props = defineProps<{ recipes: RecipeRow[] }>();

const filter = ref('');
const visible = computed(() => {
    const needle = filter.value.trim().toLowerCase();

    return needle
        ? props.recipes.filter((r) =>
              `${r.name} ${r.kind} ${r.station ?? ''} ${r.output ?? ''}`
                  .toLowerCase()
                  .includes(needle),
          )
        : props.recipes;
});

function remove(recipe: RecipeRow): void {
    if (window.confirm(`Delete "${recipe.name}"?`)) {
        router.delete(destroy(recipe.id).url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Recipes & upgrades" />

    <PageHeader
        title="Recipes & upgrades"
        description="Crafting recipes and station upgrades with their ingredient lists."
    >
        <template #actions>
            <Button as-child>
                <Link :href="create()"><Plus /> New recipe</Link>
            </Button>
        </template>
    </PageHeader>

    <template v-if="recipes.length">
        <div class="relative mb-4 max-w-sm">
            <label for="recipe-filter" class="sr-only">Filter recipes</label>
            <Search
                class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                id="recipe-filter"
                v-model="filter"
                type="search"
                placeholder="Filter by name, station or output…"
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
                        <th scope="col" class="px-3 py-2">Station</th>
                        <th scope="col" class="px-3 py-2 text-right">Level</th>
                        <th scope="col" class="px-3 py-2">Output</th>
                        <th scope="col" class="px-3 py-2 text-right">
                            Ingredients
                        </th>
                        <th scope="col" class="px-3 py-2">Status</th>
                        <th scope="col" class="px-3 py-2">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="recipe in visible"
                        :key="recipe.id"
                        :class="rowClass"
                    >
                        <td class="px-3 py-2">
                            <Link
                                :href="edit(recipe.id)"
                                class="font-medium hover:text-primary"
                                >{{ recipe.name }}</Link
                            >
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ recipe.kind }}
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ recipe.station ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            {{ recipe.level ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ recipe.output ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            {{ recipe.ingredients_count }}
                        </td>
                        <td class="px-3 py-2">
                            <StatusBadge :status="recipe.status" />
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end gap-1">
                                <Button as-child size="icon-sm" variant="ghost">
                                    <Link
                                        :href="edit(recipe.id)"
                                        :aria-label="`Edit ${recipe.name}`"
                                        ><Pencil
                                    /></Link>
                                </Button>
                                <Button
                                    size="icon-sm"
                                    variant="ghost"
                                    class="text-destructive hover:text-destructive"
                                    :aria-label="`Delete ${recipe.name}`"
                                    @click="remove(recipe)"
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
                            No recipes match “{{ filter }}”.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </template>
    <EmptyState
        v-else
        title="No recipes yet"
        description="Add recipes manually or import them from a data file."
    />
</template>
