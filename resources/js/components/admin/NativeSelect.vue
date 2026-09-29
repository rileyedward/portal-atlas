<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { SelectOption } from './types';

const model = defineModel<string | number | null>();

const props = defineProps<{
    options: SelectOption[];
    /** When set, adds an empty choice with this label that maps to null. */
    placeholder?: string;
    class?: string;
}>();

const value = computed({
    get: () =>
        model.value === null || model.value === undefined
            ? ''
            : String(model.value),
    set: (raw: string) => {
        if (raw === '') {
            model.value = null;

            return;
        }

        model.value =
            props.options.find((option) => String(option.value) === raw)
                ?.value ?? raw;
    },
});
</script>

<template>
    <select
        v-model="value"
        :class="
            cn(
                'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive dark:bg-input/30 [&>option]:bg-popover [&>option]:text-popover-foreground',
                props.class,
            )
        "
    >
        <option v-if="placeholder !== undefined" value="">
            {{ placeholder }}
        </option>
        <option
            v-for="option in options"
            :key="option.value"
            :value="String(option.value)"
        >
            {{ option.label }}
        </option>
    </select>
</template>
