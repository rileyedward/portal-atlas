<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/admin/Field.vue';
import FormSection from '@/components/admin/FormSection.vue';
import ItemRowsEditor from '@/components/admin/ItemRowsEditor.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { fromItemRows, slugify, toItemRows } from '@/components/admin/types';
import type { IdName, VersionOption } from '@/components/admin/types';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { editor } from '@/routes/admin/maps';
import { destroy, index, store, update } from '@/routes/admin/objectives';
import { show as publicObjective } from '@/routes/objectives';
import type { Option } from '@/types/game';

type ObjectiveRecord = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    map_id: number | null;
    giver: string | null;
    rewards: string | null;
    source_id: number | null;
    source_url: string | null;
    source_note: string | null;
    confidence: number;
    confidence_override: number | null;
    introduced_version_id: number | null;
    verified_version_id: number | null;
    kind: string;
    status: string;
    items: { id: number; quantity: number; role: string | null }[];
    markers: { id: number; name: string; map: string; map_slug: string }[];
};

const props = defineProps<{
    objective: ObjectiveRecord | null;
    kinds: Option[];
    statuses: Option[];
    maps: IdName[];
    items: IdName[];
    sources: IdName[];
    versions: VersionOption[];
}>();

const o = props.objective;

const form = useForm({
    name: o?.name ?? '',
    slug: o?.slug ?? '',
    kind: o?.kind ?? props.kinds[0]?.value ?? '',
    map_id: o?.map_id ?? null,
    description: o?.description ?? '',
    giver: o?.giver ?? '',
    rewards: o?.rewards ?? '',
    status: o?.status ?? 'published',
    source_id: o?.source_id ?? null,
    source_url: o?.source_url ?? '',
    source_note: o?.source_note ?? '',
    confidence_override:
        o?.confidence_override === null || o?.confidence_override === undefined
            ? ''
            : String(o.confidence_override),
    introduced_version_id: o?.introduced_version_id ?? null,
    verified_version_id: o?.verified_version_id ?? null,
    items: toItemRows(o?.items, true),
});

const slugTouched = ref(o !== null);

const mapOptions = computed(() =>
    props.maps.map((m) => ({ value: m.id, label: m.name })),
);
const sourceOptions = computed(() =>
    props.sources.map((s) => ({ value: s.id, label: s.name })),
);
const versionOptions = computed(() =>
    props.versions.map((v) => ({ value: v.id, label: v.version })),
);
const errors = computed(
    () => form.errors as Record<string, string | undefined>,
);

function onName(value: string | number): void {
    form.name = String(value);

    if (!slugTouched.value) {
        form.slug = slugify(form.name);
    }
}

function submit(): void {
    const route = props.objective ? update(props.objective.slug) : store();

    form.transform((data) => ({
        ...data,
        items: fromItemRows(data.items),
    })).submit(route, { preserveScroll: true });
}

function remove(): void {
    if (
        props.objective &&
        window.confirm(`Delete the objective "${props.objective.name}"?`)
    ) {
        router.delete(destroy(props.objective.slug).url);
    }
}
</script>

<template>
    <Head :title="objective ? `Edit ${objective.name}` : 'New objective'" />

    <PageHeader :title="objective ? objective.name : 'New objective'">
        <template #eyebrow>
            <Link
                :href="index()"
                class="mb-1 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-3" /> All objectives
            </Link>
        </template>
        <template v-if="objective" #actions>
            <StatusBadge :status="objective.status" />
            <Button as-child variant="outline" size="sm">
                <a
                    :href="publicObjective(objective.slug).url"
                    target="_blank"
                    rel="noopener"
                    ><ExternalLink /> Public page</a
                >
            </Button>
        </template>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
        <form class="min-w-0 space-y-5" novalidate @submit.prevent="submit">
            <FormSection title="Objective">
                <Field
                    id="name"
                    label="Name"
                    required
                    :error="form.errors.name"
                >
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
                    id="kind"
                    label="Kind"
                    required
                    :error="form.errors.kind"
                >
                    <NativeSelect
                        id="kind"
                        v-model="form.kind"
                        :options="kinds"
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
                <Field id="map_id" label="Map" :error="form.errors.map_id">
                    <NativeSelect
                        id="map_id"
                        v-model="form.map_id"
                        :options="mapOptions"
                        placeholder="— Any / not map-specific —"
                    />
                </Field>
                <Field id="giver" label="Given by" :error="form.errors.giver">
                    <Input id="giver" v-model="form.giver" maxlength="255" />
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
                        :rows="5"
                    />
                </Field>
                <Field
                    id="rewards"
                    label="Rewards (free text)"
                    class="sm:col-span-2"
                    :error="form.errors.rewards"
                    hint="Non-item rewards such as reputation or currency. Item rewards go below."
                >
                    <TextArea id="rewards" v-model="form.rewards" :rows="2" />
                </Field>
            </FormSection>

            <section
                class="rounded-lg border bg-card p-4 md:p-5"
                aria-labelledby="items-heading"
            >
                <h2
                    id="items-heading"
                    class="text-sm font-semibold tracking-wider uppercase"
                >
                    Items
                </h2>
                <p class="mb-4 text-xs text-muted-foreground">
                    Items the objective requires you to hand in, or gives as a
                    reward.
                </p>
                <ItemRowsEditor
                    v-model="form.items"
                    name="items"
                    :items="items"
                    :errors="errors"
                    with-role
                />
            </section>

            <FormSection
                title="Provenance & confidence"
                description="Leave unknowns empty — never guess."
            >
                <Field
                    id="source_id"
                    label="Source"
                    :error="form.errors.source_id"
                >
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
                    :error="form.errors.source_url"
                >
                    <Input
                        id="source_url"
                        v-model="form.source_url"
                        type="url"
                        placeholder="https://"
                    />
                </Field>
                <Field
                    id="source_note"
                    label="Source note"
                    class="sm:col-span-2"
                    :error="form.errors.source_note"
                >
                    <TextArea
                        id="source_note"
                        v-model="form.source_note"
                        :rows="2"
                    />
                </Field>
                <Field
                    id="introduced_version_id"
                    label="Introduced in"
                    :error="form.errors.introduced_version_id"
                >
                    <NativeSelect
                        id="introduced_version_id"
                        v-model="form.introduced_version_id"
                        :options="versionOptions"
                        placeholder="— Unknown —"
                    />
                </Field>
                <Field
                    id="verified_version_id"
                    label="Verified on"
                    :error="form.errors.verified_version_id"
                >
                    <NativeSelect
                        id="verified_version_id"
                        v-model="form.verified_version_id"
                        :options="versionOptions"
                        placeholder="— Not verified —"
                    />
                </Field>
                <Field
                    id="confidence_override"
                    label="Confidence override"
                    :error="form.errors.confidence_override"
                    hint="0–100. Leave empty to use the calculated score."
                >
                    <Input
                        id="confidence_override"
                        v-model="form.confidence_override"
                        type="number"
                        min="0"
                        max="100"
                    />
                </Field>
            </FormSection>

            <div
                class="sticky bottom-0 -mx-1 flex items-center gap-3 border-t bg-background/95 px-1 py-3 backdrop-blur"
            >
                <Button type="submit" :disabled="form.processing">{{
                    objective ? 'Save objective' : 'Create objective'
                }}</Button>
                <Button as-child variant="ghost"
                    ><Link :href="index()">Cancel</Link></Button
                >
                <span v-if="form.isDirty" class="text-xs text-muted-foreground"
                    >Unsaved changes</span
                >
                <Button
                    v-if="objective"
                    type="button"
                    variant="ghost"
                    class="ml-auto text-destructive hover:text-destructive"
                    @click="remove"
                >
                    <Trash2 /> Delete
                </Button>
            </div>
        </form>

        <aside v-if="objective" class="space-y-4">
            <div class="rounded-lg border bg-card p-4 text-sm">
                <h2 class="mb-3 text-xs font-semibold tracking-wider uppercase">
                    Confidence
                </h2>
                <ConfidenceMeter
                    :score="
                        objective.confidence_override ?? objective.confidence
                    "
                />
                <p class="mt-2 text-xs text-muted-foreground">
                    Calculated: {{ objective.confidence }}%
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <h2 class="mb-2 text-xs font-semibold tracking-wider uppercase">
                    Markers ({{ objective.markers.length }})
                </h2>
                <ul v-if="objective.markers.length" class="space-y-1">
                    <li v-for="marker in objective.markers" :key="marker.id">
                        <Link
                            :href="
                                editor(marker.map_slug, {
                                    query: { marker: marker.id },
                                })
                            "
                            class="hover:text-primary"
                        >
                            {{ marker.name }}
                        </Link>
                        <span class="text-xs text-muted-foreground">
                            · {{ marker.map }}</span
                        >
                    </li>
                </ul>
                <p v-else class="text-muted-foreground">
                    Link markers to this objective in the map editor.
                </p>
            </div>
        </aside>
    </div>
</template>
