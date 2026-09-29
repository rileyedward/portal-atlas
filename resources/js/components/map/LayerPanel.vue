<script setup lang="ts">
import { ChevronRight } from '@lucide/vue';
import { ref } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { iconSvg } from '@/lib/markerIcons';
import type { MarkerCategoryData } from '@/types/game';

defineProps<{
    categories: MarkerCategoryData[];
    counts: Record<number, number>;
    isTypeVisible: (id: number) => boolean;
    categoryState: (c: MarkerCategoryData) => boolean | 'indeterminate';
}>();

const emit = defineEmits<{
    setType: [id: number, visible: boolean];
    setCategory: [category: MarkerCategoryData, visible: boolean];
}>();

const expanded = ref<string[]>([]);

function toggle(slug: string): void {
    expanded.value = expanded.value.includes(slug)
        ? expanded.value.filter((s) => s !== slug)
        : [...expanded.value, slug];
}

function total(
    category: MarkerCategoryData,
    counts: Record<number, number>,
): number {
    return category.types.reduce((sum, t) => sum + (counts[t.id] ?? 0), 0);
}
</script>

<template>
    <ul class="space-y-0.5" aria-label="Map layers">
        <li v-for="category in categories" :key="category.id">
            <div
                class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-accent/50"
            >
                <Checkbox
                    :id="`layer-${category.slug}`"
                    :model-value="categoryState(category)"
                    @update:model-value="
                        (v) => emit('setCategory', category, v === true)
                    "
                />
                <span
                    class="grid size-5 place-items-center rounded-full [&_svg]:size-3 [&_svg]:text-background"
                    :style="{ background: category.color }"
                    v-html="iconSvg(category.icon)"
                />
                <label
                    :for="`layer-${category.slug}`"
                    class="flex-1 cursor-pointer text-sm font-medium"
                >
                    {{ category.name }}
                </label>
                <span class="text-xs text-muted-foreground tabular-nums">{{
                    total(category, counts)
                }}</span>
                <button
                    type="button"
                    class="rounded p-0.5 text-muted-foreground hover:text-foreground"
                    :aria-expanded="expanded.includes(category.slug)"
                    :aria-label="`Show ${category.name} sub-types`"
                    @click="toggle(category.slug)"
                >
                    <ChevronRight
                        class="size-4 transition-transform"
                        :class="{
                            'rotate-90': expanded.includes(category.slug),
                        }"
                    />
                </button>
            </div>
            <ul
                v-if="expanded.includes(category.slug)"
                class="mt-0.5 mb-1 ml-6 space-y-0.5 border-l pl-2"
            >
                <li
                    v-for="type in category.types"
                    :key="type.id"
                    class="flex items-center gap-2 rounded px-2 py-1 hover:bg-accent/40"
                >
                    <Checkbox
                        :id="`layer-type-${type.id}`"
                        :model-value="isTypeVisible(type.id)"
                        @update:model-value="
                            (v) => emit('setType', type.id, v === true)
                        "
                    />
                    <label
                        :for="`layer-type-${type.id}`"
                        class="flex-1 cursor-pointer text-[13px] text-muted-foreground"
                    >
                        {{ type.name }}
                    </label>
                    <span class="text-xs text-muted-foreground tabular-nums">{{
                        counts[type.id] ?? 0
                    }}</span>
                </li>
            </ul>
        </li>
    </ul>
</template>
