<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import NativeSelect from './NativeSelect.vue';
import type { IdName, ItemRow } from './types';

const rows = defineModel<ItemRow[]>({ required: true });

const props = defineProps<{
    /** Field name the server validates, e.g. "items" or "ingredients". */
    name: string;
    items: IdName[];
    errors: Record<string, string | undefined>;
    withRole?: boolean;
    addLabel?: string;
}>();

const itemOptions = computed(() =>
    props.items.map((item) => ({ value: item.id, label: item.name })),
);
const roleOptions = [
    { value: 'required', label: 'Required' },
    { value: 'reward', label: 'Reward' },
];

function newUid(): string {
    return Math.random().toString(36).slice(2, 10);
}

function add(): void {
    rows.value = [
        ...rows.value,
        {
            uid: newUid(),
            id: null,
            quantity: '1',
            ...(props.withRole ? { role: 'required' as const } : {}),
        },
    ];
}

function patch(index: number, change: Partial<ItemRow>): void {
    rows.value = rows.value.map((row, i) =>
        i === index ? { ...row, ...change } : row,
    );
}

function remove(index: number): void {
    rows.value = rows.value.filter((_, i) => i !== index);
}

function error(index: number, field: string): string | undefined {
    return props.errors[`${props.name}.${index}.${field}`];
}
</script>

<template>
    <div class="space-y-2">
        <p
            v-if="!rows.length"
            class="rounded-md border border-dashed p-4 text-center text-sm text-muted-foreground"
        >
            No items yet.
        </p>
        <div
            v-for="(row, index) in rows"
            :key="row.uid"
            class="grid items-start gap-2 rounded-md border bg-background/40 p-2"
            :class="
                withRole
                    ? 'sm:grid-cols-[1fr_6rem_8rem_auto]'
                    : 'sm:grid-cols-[1fr_6rem_auto]'
            "
        >
            <div>
                <label :for="`${name}-${row.uid}-id`" class="sr-only"
                    >Item {{ index + 1 }}</label
                >
                <NativeSelect
                    :id="`${name}-${row.uid}-id`"
                    :model-value="row.id"
                    :options="itemOptions"
                    placeholder="— Choose an item —"
                    @update:model-value="
                        patch(index, {
                            id: typeof $event === 'number' ? $event : null,
                        })
                    "
                />
                <InputError :message="error(index, 'id')" />
            </div>
            <div>
                <label :for="`${name}-${row.uid}-qty`" class="sr-only"
                    >Quantity for item {{ index + 1 }}</label
                >
                <Input
                    :id="`${name}-${row.uid}-qty`"
                    :model-value="row.quantity"
                    type="number"
                    min="1"
                    max="9999"
                    placeholder="Qty"
                    @update:model-value="
                        patch(index, { quantity: String($event) })
                    "
                />
                <InputError :message="error(index, 'quantity')" />
            </div>
            <div v-if="withRole">
                <label :for="`${name}-${row.uid}-role`" class="sr-only"
                    >Role for item {{ index + 1 }}</label
                >
                <NativeSelect
                    :id="`${name}-${row.uid}-role`"
                    :model-value="row.role ?? 'required'"
                    :options="roleOptions"
                    @update:model-value="
                        patch(index, {
                            role: $event === 'reward' ? 'reward' : 'required',
                        })
                    "
                />
                <InputError :message="error(index, 'role')" />
            </div>
            <Button
                type="button"
                size="icon"
                variant="ghost"
                class="text-destructive hover:text-destructive"
                :aria-label="`Remove item ${index + 1}`"
                @click="remove(index)"
            >
                <Trash2 />
            </Button>
        </div>
        <InputError :message="errors[name]" />
        <Button type="button" variant="outline" size="sm" @click="add"
            ><Plus /> {{ addLabel ?? 'Add item' }}</Button
        >
    </div>
</template>
