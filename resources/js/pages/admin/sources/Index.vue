<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { rowClass, tableClass, theadClass } from '@/components/admin/types';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy, store, update } from '@/routes/admin/sources';
import type { Option } from '@/types/game';

type Source = {
    id: number;
    name: string;
    kind: string;
    url: string | null;
    reliability: number;
    notes: string | null;
};

const props = defineProps<{ sources: Source[]; kinds: Option[] }>();

type SourceFields = {
    name: string;
    kind: string;
    url: string;
    reliability: string;
    notes: string;
};

const blank = (): SourceFields => ({
    name: '',
    kind: props.kinds[0]?.value ?? '',
    url: '',
    reliability: '50',
    notes: '',
});

const createForm = useForm(blank());
const editForm = useForm(blank());
const editingId = ref<number | null>(null);
const showCreate = ref(false);

const kindLabel = computed(() =>
    Object.fromEntries(props.kinds.map((k) => [k.value, k.label])),
);

function startEdit(s: Source): void {
    editingId.value = s.id;
    editForm.defaults({
        name: s.name,
        kind: s.kind,
        url: s.url ?? '',
        reliability: String(s.reliability),
        notes: s.notes ?? '',
    });
    editForm.reset();
    editForm.clearErrors();
}

function saveEdit(): void {
    if (editingId.value === null) {
        return;
    }

    editForm.submit(update(editingId.value), {
        preserveScroll: true,
        onSuccess: () => (editingId.value = null),
    });
}

function create(): void {
    createForm.submit(store(), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreate.value = false;
        },
    });
}

function remove(s: Source): void {
    if (
        window.confirm(
            `Delete the source "${s.name}"? Content citing it will lose its source.`,
        )
    ) {
        router.delete(destroy(s.id).url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Sources" />

    <PageHeader
        title="Sources"
        description="Where our data comes from. Reliability (0–100) feeds the confidence score of everything citing the source."
    >
        <template #actions>
            <Button v-if="!showCreate" @click="showCreate = true"
                ><Plus /> Add source</Button
            >
        </template>
    </PageHeader>

    <form
        v-if="showCreate"
        class="mb-6 grid gap-3 rounded-lg border bg-card p-4 sm:grid-cols-2 lg:grid-cols-4"
        novalidate
        @submit.prevent="create"
    >
        <div class="grid gap-1">
            <label for="new-name" class="text-xs text-muted-foreground"
                >Name *</label
            >
            <Input id="new-name" v-model="createForm.name" v-focus required />
            <InputError :message="createForm.errors.name" />
        </div>
        <div class="grid gap-1">
            <label for="new-kind" class="text-xs text-muted-foreground"
                >Kind *</label
            >
            <NativeSelect
                id="new-kind"
                v-model="createForm.kind"
                :options="kinds"
            />
            <InputError :message="createForm.errors.kind" />
        </div>
        <div class="grid gap-1">
            <label for="new-url" class="text-xs text-muted-foreground"
                >URL</label
            >
            <Input
                id="new-url"
                v-model="createForm.url"
                type="url"
                placeholder="https://"
            />
            <InputError :message="createForm.errors.url" />
        </div>
        <div class="grid gap-1">
            <label for="new-reliability" class="text-xs text-muted-foreground"
                >Reliability (0–100) *</label
            >
            <Input
                id="new-reliability"
                v-model="createForm.reliability"
                type="number"
                min="0"
                max="100"
                required
            />
            <InputError :message="createForm.errors.reliability" />
        </div>
        <div class="grid gap-1 sm:col-span-2 lg:col-span-4">
            <label for="new-notes" class="text-xs text-muted-foreground"
                >Notes</label
            >
            <Input id="new-notes" v-model="createForm.notes" />
            <InputError :message="createForm.errors.notes" />
        </div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-4">
            <Button type="submit" :disabled="createForm.processing"
                >Add source</Button
            >
            <Button
                type="button"
                variant="ghost"
                @click="
                    ((showCreate = false),
                    createForm.reset(),
                    createForm.clearErrors())
                "
                >Cancel</Button
            >
        </div>
    </form>

    <div
        v-if="sources.length"
        class="max-h-[calc(100svh-12rem)] overflow-auto rounded-lg border bg-card"
    >
        <table :class="tableClass">
            <thead :class="theadClass">
                <tr>
                    <th scope="col" class="px-3 py-2">Name</th>
                    <th scope="col" class="px-3 py-2">Kind</th>
                    <th scope="col" class="px-3 py-2">Reliability</th>
                    <th scope="col" class="px-3 py-2">Notes</th>
                    <th scope="col" class="px-3 py-2">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <template v-for="s in sources" :key="s.id">
                    <tr
                        v-if="editingId === s.id"
                        class="border-t bg-accent/20 align-top"
                        @keydown.enter="
                            ($event.target as HTMLElement).tagName ===
                                'INPUT' && saveEdit()
                        "
                        @keydown.esc="editingId = null"
                    >
                        <td class="space-y-1 px-2 py-2">
                            <label :for="`s-${s.id}-name`" class="sr-only"
                                >Name</label
                            >
                            <Input
                                :id="`s-${s.id}-name`"
                                v-model="editForm.name"
                                v-focus
                                class="h-8"
                            />
                            <InputError :message="editForm.errors.name" />
                            <label :for="`s-${s.id}-url`" class="sr-only"
                                >URL</label
                            >
                            <Input
                                :id="`s-${s.id}-url`"
                                v-model="editForm.url"
                                type="url"
                                class="h-8"
                                placeholder="https://"
                            />
                            <InputError :message="editForm.errors.url" />
                        </td>
                        <td class="px-2 py-2">
                            <label :for="`s-${s.id}-kind`" class="sr-only"
                                >Kind</label
                            >
                            <NativeSelect
                                :id="`s-${s.id}-kind`"
                                v-model="editForm.kind"
                                :options="kinds"
                                class="h-8"
                            />
                            <InputError :message="editForm.errors.kind" />
                        </td>
                        <td class="px-2 py-2">
                            <label
                                :for="`s-${s.id}-reliability`"
                                class="sr-only"
                                >Reliability</label
                            >
                            <Input
                                :id="`s-${s.id}-reliability`"
                                v-model="editForm.reliability"
                                type="number"
                                min="0"
                                max="100"
                                class="h-8 w-24"
                            />
                            <InputError
                                :message="editForm.errors.reliability"
                            />
                        </td>
                        <td class="px-2 py-2">
                            <label :for="`s-${s.id}-notes`" class="sr-only"
                                >Notes</label
                            >
                            <Input
                                :id="`s-${s.id}-notes`"
                                v-model="editForm.notes"
                                class="h-8"
                            />
                            <InputError :message="editForm.errors.notes" />
                        </td>
                        <td class="px-2 py-2">
                            <div class="flex justify-end gap-1">
                                <Button
                                    size="sm"
                                    :disabled="editForm.processing"
                                    @click="saveEdit"
                                    >Save</Button
                                >
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="editingId = null"
                                    >Cancel</Button
                                >
                            </div>
                        </td>
                    </tr>
                    <tr v-else :class="rowClass">
                        <td class="px-3 py-2">
                            <span class="font-medium">{{ s.name }}</span>
                            <a
                                v-if="s.url"
                                :href="s.url"
                                target="_blank"
                                rel="noopener"
                                class="ml-1 inline-flex align-middle text-muted-foreground hover:text-primary"
                                :aria-label="`Open ${s.name}`"
                            >
                                <ExternalLink class="size-3.5" />
                            </a>
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ kindLabel[s.kind] ?? s.kind }}
                        </td>
                        <td class="px-3 py-2">
                            <ConfidenceMeter
                                :score="s.reliability"
                                :label="`${s.reliability}`"
                                compact
                            />
                        </td>
                        <td class="max-w-72 px-3 py-2 text-muted-foreground">
                            <span class="line-clamp-2">{{
                                s.notes ?? ''
                            }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end gap-1">
                                <Button
                                    size="icon-sm"
                                    variant="ghost"
                                    :aria-label="`Edit ${s.name}`"
                                    @click="startEdit(s)"
                                    ><Pencil
                                /></Button>
                                <Button
                                    size="icon-sm"
                                    variant="ghost"
                                    class="text-destructive hover:text-destructive"
                                    :aria-label="`Delete ${s.name}`"
                                    @click="remove(s)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <EmptyState
        v-else-if="!showCreate"
        title="No sources yet"
        description="Add sources so every data point can cite where it came from."
    >
        <Button @click="showCreate = true"><Plus /> Add source</Button>
    </EmptyState>
</template>
