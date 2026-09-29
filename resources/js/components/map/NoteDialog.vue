<script setup lang="ts">
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { MapNote } from '@/types/game';

const props = defineProps<{
    open: boolean;
    note: Partial<MapNote> | null;
    error?: string | null;
    saving?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
    save: [note: Partial<MapNote>];
    delete: [id: number];
}>();

const colors = [
    '#facc15',
    '#f97316',
    '#ef4444',
    '#a855f7',
    '#38bdf8',
    '#22c55e',
];
const form = ref<Partial<MapNote>>({});

watch(
    () => props.note,
    (note) => {
        form.value = { color: colors[0], is_shared: false, ...note };
    },
    { immediate: true },
);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    form.id ? 'Edit note' : 'New note'
                }}</DialogTitle>
                <DialogDescription>
                    Notes are private to your account unless you choose to share
                    them.
                </DialogDescription>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="emit('save', form)">
                <div class="space-y-1.5">
                    <Label for="note-title">Title</Label>
                    <Input
                        id="note-title"
                        v-model="form.title"
                        maxlength="120"
                        required
                        placeholder="e.g. Good solo loot route"
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="note-body">Details</Label>
                    <textarea
                        id="note-body"
                        v-model="form.body"
                        rows="3"
                        maxlength="2000"
                        class="w-full rounded-md border bg-transparent px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    />
                </div>
                <fieldset class="flex items-center gap-2">
                    <legend class="mb-1.5 text-sm font-medium">Color</legend>
                    <button
                        v-for="color in colors"
                        :key="color"
                        type="button"
                        class="size-6 rounded-full border-2 transition"
                        :class="
                            form.color === color
                                ? 'scale-110 border-foreground'
                                : 'border-transparent'
                        "
                        :style="{ background: color }"
                        :aria-label="`Color ${color}`"
                        :aria-pressed="form.color === color"
                        @click="form.color = color"
                    />
                </fieldset>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_shared" type="checkbox" />
                    Allow sharing via link
                </label>
                <InputError :message="error ?? undefined" />
                <DialogFooter class="gap-2">
                    <Button
                        v-if="form.id"
                        type="button"
                        variant="destructive"
                        class="mr-auto"
                        @click="emit('delete', form.id)"
                    >
                        Delete
                    </Button>
                    <Button type="submit" :disabled="saving || !form.title">
                        Save note
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
