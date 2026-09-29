<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Waypoints } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import Field from '@/components/admin/Field.vue';
import FormSection from '@/components/admin/FormSection.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { slugify } from '@/components/admin/types';
import type { IdName, VersionOption } from '@/components/admin/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { editor, index, store, update } from '@/routes/admin/maps';
import { show as publicMap } from '@/routes/maps';
import type { Option } from '@/types/game';

type MapRecord = {
    id: number;
    slug: string;
    name: string;
    summary: string | null;
    description: string | null;
    status: string;
    width: number;
    height: number;
    sort_order: number | null;
    game_version_id: number | null;
    source_id: number | null;
    source_url: string | null;
    image_attribution: string | null;
    image_url: string | null;
};

const props = defineProps<{
    map: MapRecord | null;
    statuses: Option[];
    versions: VersionOption[];
    sources: IdName[];
}>();

const form = useForm({
    name: props.map?.name ?? '',
    slug: props.map?.slug ?? '',
    summary: props.map?.summary ?? '',
    description: props.map?.description ?? '',
    status: props.map?.status ?? 'draft',
    width: String(props.map?.width ?? 1000),
    height: String(props.map?.height ?? 1000),
    sort_order: String(props.map?.sort_order ?? 0),
    game_version_id: props.map?.game_version_id ?? null,
    source_id: props.map?.source_id ?? null,
    source_url: props.map?.source_url ?? '',
    image_attribution: props.map?.image_attribution ?? '',
    image: null as File | null,
});

const slugTouched = ref(props.map !== null);
const fileInput = ref<HTMLInputElement | null>(null);
const preview = ref<string | null>(null);

const versionOptions = computed(() =>
    props.versions.map((v) => ({ value: v.id, label: v.version })),
);
const sourceOptions = computed(() =>
    props.sources.map((s) => ({ value: s.id, label: s.name })),
);

function onName(value: string | number): void {
    form.name = String(value);

    if (!slugTouched.value) {
        form.slug = slugify(form.name);
    }
}

function onFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.image = file;

    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = file ? URL.createObjectURL(file) : null;
}

onBeforeUnmount(() => {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }
});

function submit(): void {
    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.image = null;

            if (fileInput.value) {
                fileInput.value.value = '';
            }

            if (preview.value) {
                URL.revokeObjectURL(preview.value);
                preview.value = null;
            }
        },
    };

    if (props.map) {
        // PHP only parses multipart bodies on POST, so spoof the PUT method.
        form.transform((data) => ({
            ...data,
            sort_order: data.sort_order || '0',
            _method: 'put',
        })).post(update.url(props.map.slug), options);
    } else {
        form.transform((data) => ({
            ...data,
            sort_order: data.sort_order || '0',
        })).post(store.url(), options);
    }
}
</script>

<template>
    <Head :title="map ? `Edit ${map.name}` : 'New map'" />

    <PageHeader :title="map ? map.name : 'New map'">
        <template #eyebrow>
            <Link
                :href="index()"
                class="mb-1 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-3" /> All maps
            </Link>
        </template>
        <template v-if="map" #actions>
            <StatusBadge :status="map.status" />
            <Button as-child variant="outline" size="sm">
                <a
                    :href="publicMap(map.slug).url"
                    target="_blank"
                    rel="noopener"
                    ><ExternalLink /> Public page</a
                >
            </Button>
            <Button as-child size="sm">
                <Link :href="editor(map.slug)"><Waypoints /> Open editor</Link>
            </Button>
        </template>
    </PageHeader>

    <form class="space-y-5" novalidate @submit.prevent="submit">
        <FormSection title="Basics">
            <Field id="name" label="Name" required :error="form.errors.name">
                <Input
                    id="name"
                    :model-value="form.name"
                    required
                    autocomplete="off"
                    @update:model-value="onName"
                />
            </Field>
            <Field
                id="slug"
                label="Slug"
                required
                :error="form.errors.slug"
                hint="Used in the public URL. Letters, numbers, dashes."
            >
                <Input
                    id="slug"
                    v-model="form.slug"
                    required
                    autocomplete="off"
                    @input="slugTouched = true"
                />
            </Field>
            <Field
                id="status"
                label="Status"
                required
                :error="form.errors.status"
            >
                <NativeSelect
                    id="status"
                    v-model="form.status"
                    :options="statuses"
                />
            </Field>
            <Field
                id="sort_order"
                label="Sort order"
                :error="form.errors.sort_order"
            >
                <Input
                    id="sort_order"
                    v-model="form.sort_order"
                    type="number"
                    min="0"
                    max="1000"
                />
            </Field>
            <Field
                id="summary"
                label="Summary"
                class="sm:col-span-2"
                :error="form.errors.summary"
                hint="One or two sentences shown on map cards."
            >
                <Input id="summary" v-model="form.summary" maxlength="500" />
            </Field>
            <Field
                id="description"
                label="Description"
                class="sm:col-span-2"
                :error="form.errors.description"
            >
                <TextArea
                    id="description"
                    v-model="form.description"
                    :rows="6"
                />
            </Field>
        </FormSection>

        <FormSection
            title="Base image"
            description="PNG, JPG or WebP up to 20 MB. Width and height are read from the uploaded file."
        >
            <div class="sm:col-span-2">
                <div class="grid gap-4 md:grid-cols-[minmax(0,16rem)_1fr]">
                    <div
                        class="flex aspect-square items-center justify-center overflow-hidden rounded-md border bg-muted/40"
                    >
                        <img
                            v-if="preview ?? map?.image_url"
                            :src="preview ?? map?.image_url ?? undefined"
                            alt="Map base image preview"
                            class="h-full w-full object-contain"
                        />
                        <span v-else class="text-xs text-muted-foreground"
                            >No image uploaded</span
                        >
                    </div>
                    <div class="grid content-start gap-4">
                        <Field
                            id="image"
                            label="Upload image"
                            :error="form.errors.image"
                        >
                            <input
                                id="image"
                                ref="fileInput"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80"
                                @change="onFile"
                            />
                        </Field>
                        <progress
                            v-if="form.progress"
                            :value="form.progress.percentage"
                            max="100"
                            class="w-full"
                        >
                            {{ form.progress.percentage }}%
                        </progress>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Field
                                id="width"
                                label="Width (px)"
                                required
                                :error="form.errors.width"
                            >
                                <Input
                                    id="width"
                                    v-model="form.width"
                                    type="number"
                                    min="100"
                                    max="20000"
                                />
                            </Field>
                            <Field
                                id="height"
                                label="Height (px)"
                                required
                                :error="form.errors.height"
                            >
                                <Input
                                    id="height"
                                    v-model="form.height"
                                    type="number"
                                    min="100"
                                    max="20000"
                                />
                            </Field>
                        </div>
                        <Field
                            id="image_attribution"
                            label="Image attribution"
                            :error="form.errors.image_attribution"
                            hint="Credit for the map artwork, if it isn't yours."
                        >
                            <Input
                                id="image_attribution"
                                v-model="form.image_attribution"
                                maxlength="255"
                            />
                        </Field>
                    </div>
                </div>
            </div>
        </FormSection>

        <FormSection title="Provenance">
            <Field
                id="game_version_id"
                label="Game version"
                :error="form.errors.game_version_id"
            >
                <NativeSelect
                    id="game_version_id"
                    v-model="form.game_version_id"
                    :options="versionOptions"
                    placeholder="— None —"
                />
            </Field>
            <Field id="source_id" label="Source" :error="form.errors.source_id">
                <NativeSelect
                    id="source_id"
                    v-model="form.source_id"
                    :options="sourceOptions"
                    placeholder="— None —"
                />
            </Field>
            <Field
                id="source_url"
                label="Source URL"
                class="sm:col-span-2"
                :error="form.errors.source_url"
            >
                <Input
                    id="source_url"
                    v-model="form.source_url"
                    type="url"
                    placeholder="https://"
                />
            </Field>
        </FormSection>

        <div
            class="sticky bottom-0 -mx-1 flex items-center gap-3 border-t bg-background/95 px-1 py-3 backdrop-blur"
        >
            <Button type="submit" :disabled="form.processing">{{
                map ? 'Save map' : 'Create map'
            }}</Button>
            <Button as-child variant="ghost">
                <Link :href="index()">Cancel</Link>
            </Button>
            <span v-if="form.isDirty" class="text-xs text-muted-foreground"
                >Unsaved changes</span
            >
        </div>
    </form>
</template>
