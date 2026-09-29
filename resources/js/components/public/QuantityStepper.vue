<script setup lang="ts">
import { Minus, Plus } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        label: string;
        min?: number;
        max?: number;
        disabled?: boolean;
    }>(),
    { min: 0, max: 9999, disabled: false },
);

const model = defineModel<number>({ required: true });
const emit = defineEmits<{ commit: [value: number] }>();

function set(value: number): void {
    const next = Math.min(
        props.max,
        Math.max(props.min, Math.round(value) || 0),
    );

    if (next !== model.value) {
        model.value = next;
        emit('commit', next);
    }
}
</script>

<template>
    <div
        class="inline-flex items-center rounded-md border"
        role="group"
        :aria-label="label"
    >
        <button
            type="button"
            class="grid size-9 place-items-center text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:opacity-40"
            :aria-label="`Decrease ${label}`"
            :disabled="disabled || model <= min"
            @click="set(model - 1)"
        >
            <Minus class="size-4" />
        </button>
        <input
            type="number"
            inputmode="numeric"
            class="h-9 w-12 [appearance:textfield] border-x bg-transparent text-center text-sm tabular-nums focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none [&::-webkit-inner-spin-button]:appearance-none"
            :aria-label="label"
            :min="min"
            :max="max"
            :value="model"
            :disabled="disabled"
            @change="set(Number(($event.target as HTMLInputElement).value))"
        />
        <button
            type="button"
            class="grid size-9 place-items-center text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:opacity-40"
            :aria-label="`Increase ${label}`"
            :disabled="disabled || model >= max"
            @click="set(model + 1)"
        >
            <Plus class="size-4" />
        </button>
    </div>
</template>
