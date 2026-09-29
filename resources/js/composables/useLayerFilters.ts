import { useSessionStorage } from '@vueuse/core';
import { computed } from 'vue';
import type { Ref } from 'vue';
import type { MarkerCategoryData } from '@/types/game';

/**
 * Layer visibility for the map. Hidden type ids are persisted for the browser
 * session so filters survive map switches and reloads.
 */
export function useLayerFilters(categories: Ref<MarkerCategoryData[]>) {
    const initialHidden = categories.value
        .filter((c) => !c.visible_by_default)
        .flatMap((c) => c.types.map((t) => t.id));

    const hidden = useSessionStorage<number[]>(
        'map.hiddenTypes',
        initialHidden,
    );
    const showLabels = useSessionStorage<boolean>('map.showLabels', false);
    const showNotes = useSessionStorage<boolean>('map.showNotes', true);

    const visibleTypeIds = computed(() =>
        categories.value
            .flatMap((c) => c.types.map((t) => t.id))
            .filter((id) => !hidden.value.includes(id)),
    );

    function isTypeVisible(id: number): boolean {
        return !hidden.value.includes(id);
    }

    function categoryState(
        category: MarkerCategoryData,
    ): boolean | 'indeterminate' {
        const visible = category.types.filter((t) => isTypeVisible(t.id));

        if (visible.length === category.types.length) {
            return true;
        }

        return visible.length === 0 ? false : 'indeterminate';
    }

    function setType(id: number, visible: boolean): void {
        hidden.value = visible
            ? hidden.value.filter((h) => h !== id)
            : [...new Set([...hidden.value, id])];
    }

    function setCategory(category: MarkerCategoryData, visible: boolean): void {
        const ids = category.types.map((t) => t.id);
        hidden.value = visible
            ? hidden.value.filter((h) => !ids.includes(h))
            : [...new Set([...hidden.value, ...ids])];
    }

    function showAll(): void {
        hidden.value = [];
    }

    function hideAll(): void {
        hidden.value = categories.value.flatMap((c) =>
            c.types.map((t) => t.id),
        );
    }

    return {
        hidden,
        showLabels,
        showNotes,
        visibleTypeIds,
        isTypeVisible,
        categoryState,
        setType,
        setCategory,
        showAll,
        hideAll,
    };
}
