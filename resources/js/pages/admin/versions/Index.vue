<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Star, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import {
    formatDate,
    rowClass,
    tableClass,
    theadClass,
} from '@/components/admin/types';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy, store, update } from '@/routes/admin/versions';

type Version = {
    id: number;
    version: string;
    name: string | null;
    released_at: string | null;
    is_current: boolean;
    notes: string | null;
    source_url: string | null;
};

defineProps<{ versions: Version[] }>();

type VersionFields = {
    version: string;
    name: string;
    released_at: string;
    is_current: boolean;
    notes: string;
    source_url: string;
};

const blank = (): VersionFields => ({
    version: '',
    name: '',
    released_at: '',
    is_current: false,
    notes: '',
    source_url: '',
});

const fields = (v: Version): VersionFields => ({
    version: v.version,
    name: v.name ?? '',
    released_at: v.released_at ? v.released_at.slice(0, 10) : '',
    is_current: v.is_current,
    notes: v.notes ?? '',
    source_url: v.source_url ?? '',
});

const createForm = useForm(blank());
const editForm = useForm(blank());
const editingId = ref<number | null>(null);
const showCreate = ref(false);

function startEdit(v: Version): void {
    editingId.value = v.id;
    editForm.defaults(fields(v));
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

function makeCurrent(v: Version): void {
    router.patch(
        update(v.id).url,
        { ...fields(v), is_current: true },
        { preserveScroll: true },
    );
}

function remove(v: Version): void {
    if (
        window.confirm(
            `Delete version ${v.version}? Content verified on it will lose that reference.`,
        )
    ) {
        router.delete(destroy(v.id).url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Game versions" />

    <PageHeader
        title="Game versions"
        description="Patches and builds. The current version drives “verified this version” checks and staleness warnings."
    >
        <template #actions>
            <Button v-if="!showCreate" @click="showCreate = true"
                ><Plus /> Add version</Button
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
            <label for="new-version" class="text-xs text-muted-foreground"
                >Version *</label
            >
            <Input
                id="new-version"
                v-model="createForm.version"
                v-focus
                required
                placeholder="e.g. 0.9.2"
            />
            <InputError :message="createForm.errors.version" />
        </div>
        <div class="grid gap-1">
            <label for="new-name" class="text-xs text-muted-foreground"
                >Name</label
            >
            <Input id="new-name" v-model="createForm.name" />
            <InputError :message="createForm.errors.name" />
        </div>
        <div class="grid gap-1">
            <label for="new-released" class="text-xs text-muted-foreground"
                >Released</label
            >
            <Input
                id="new-released"
                v-model="createForm.released_at"
                type="date"
            />
            <InputError :message="createForm.errors.released_at" />
        </div>
        <div class="grid gap-1">
            <label for="new-source" class="text-xs text-muted-foreground"
                >Patch notes URL</label
            >
            <Input
                id="new-source"
                v-model="createForm.source_url"
                type="url"
                placeholder="https://"
            />
            <InputError :message="createForm.errors.source_url" />
        </div>
        <div class="grid gap-1 sm:col-span-2 lg:col-span-3">
            <label for="new-notes" class="text-xs text-muted-foreground"
                >Notes</label
            >
            <Input id="new-notes" v-model="createForm.notes" />
            <InputError :message="createForm.errors.notes" />
        </div>
        <label class="flex items-center gap-2 self-end pb-2 text-sm">
            <input
                v-model="createForm.is_current"
                type="checkbox"
                class="size-4 accent-primary"
            />
            Current version
        </label>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-4">
            <Button type="submit" :disabled="createForm.processing"
                >Add version</Button
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
        v-if="versions.length"
        class="max-h-[calc(100svh-12rem)] overflow-auto rounded-lg border bg-card"
    >
        <table :class="tableClass">
            <thead :class="theadClass">
                <tr>
                    <th scope="col" class="px-3 py-2">Version</th>
                    <th scope="col" class="px-3 py-2">Name</th>
                    <th scope="col" class="px-3 py-2">Released</th>
                    <th scope="col" class="px-3 py-2">Notes / source</th>
                    <th scope="col" class="px-3 py-2">Current</th>
                    <th scope="col" class="px-3 py-2">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <template v-for="v in versions" :key="v.id">
                    <tr
                        v-if="editingId === v.id"
                        class="border-t bg-accent/20 align-top"
                        @keydown.enter="
                            ($event.target as HTMLElement).tagName ===
                                'INPUT' && saveEdit()
                        "
                        @keydown.esc="editingId = null"
                    >
                        <td class="px-2 py-2">
                            <label :for="`v-${v.id}-version`" class="sr-only"
                                >Version</label
                            >
                            <Input
                                :id="`v-${v.id}-version`"
                                v-model="editForm.version"
                                v-focus
                                class="h-8"
                            />
                            <InputError :message="editForm.errors.version" />
                        </td>
                        <td class="px-2 py-2">
                            <label :for="`v-${v.id}-name`" class="sr-only"
                                >Name</label
                            >
                            <Input
                                :id="`v-${v.id}-name`"
                                v-model="editForm.name"
                                class="h-8"
                            />
                            <InputError :message="editForm.errors.name" />
                        </td>
                        <td class="px-2 py-2">
                            <label :for="`v-${v.id}-released`" class="sr-only"
                                >Released</label
                            >
                            <Input
                                :id="`v-${v.id}-released`"
                                v-model="editForm.released_at"
                                type="date"
                                class="h-8"
                            />
                            <InputError
                                :message="editForm.errors.released_at"
                            />
                        </td>
                        <td class="space-y-1 px-2 py-2">
                            <label :for="`v-${v.id}-notes`" class="sr-only"
                                >Notes</label
                            >
                            <Input
                                :id="`v-${v.id}-notes`"
                                v-model="editForm.notes"
                                class="h-8"
                                placeholder="Notes"
                            />
                            <InputError :message="editForm.errors.notes" />
                            <label :for="`v-${v.id}-source`" class="sr-only"
                                >Patch notes URL</label
                            >
                            <Input
                                :id="`v-${v.id}-source`"
                                v-model="editForm.source_url"
                                type="url"
                                class="h-8"
                                placeholder="Patch notes URL"
                            />
                            <InputError :message="editForm.errors.source_url" />
                        </td>
                        <td class="px-2 py-2">
                            <label
                                class="flex items-center gap-2 pt-1.5 text-sm"
                            >
                                <input
                                    v-model="editForm.is_current"
                                    type="checkbox"
                                    class="size-4 accent-primary"
                                />
                                <span class="sr-only">Current version</span>
                            </label>
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
                        <td class="px-3 py-2 font-medium tabular-nums">
                            {{ v.version }}
                        </td>
                        <td class="px-3 py-2">{{ v.name ?? '—' }}</td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ formatDate(v.released_at) }}
                        </td>
                        <td class="max-w-72 px-3 py-2 text-muted-foreground">
                            <span class="line-clamp-2">{{
                                v.notes ?? ''
                            }}</span>
                            <a
                                v-if="v.source_url"
                                :href="v.source_url"
                                target="_blank"
                                rel="noopener"
                                class="text-xs text-primary hover:underline"
                            >
                                Patch notes
                            </a>
                        </td>
                        <td class="px-3 py-2">
                            <span
                                v-if="v.is_current"
                                class="inline-flex items-center gap-1 text-xs font-medium text-primary"
                            >
                                <Star class="size-3.5 fill-current" /> Current
                            </span>
                            <Button
                                v-else
                                size="sm"
                                variant="ghost"
                                class="h-7 text-xs"
                                @click="makeCurrent(v)"
                                >Make current</Button
                            >
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end gap-1">
                                <Button
                                    size="icon-sm"
                                    variant="ghost"
                                    :aria-label="`Edit ${v.version}`"
                                    @click="startEdit(v)"
                                    ><Pencil
                                /></Button>
                                <Button
                                    size="icon-sm"
                                    variant="ghost"
                                    class="text-destructive hover:text-destructive"
                                    :aria-label="`Delete ${v.version}`"
                                    @click="remove(v)"
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
        title="No game versions yet"
        description="Add the current game build so content can be verified against it."
    >
        <Button @click="showCreate = true"><Plus /> Add version</Button>
    </EmptyState>
</template>
