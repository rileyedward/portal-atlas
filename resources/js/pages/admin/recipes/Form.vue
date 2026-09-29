<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from '@lucide/vue';
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
import { destroy, index, store, update } from '@/routes/admin/recipes';
import type { Option } from '@/types/game';

type RecipeRecord = {
    id: number;
    slug: string;
    name: string;
    station: string | null;
    level: number | null;
    output_item_id: number | null;
    output_quantity: number | null;
    description: string | null;
    source_id: number | null;
    source_url: string | null;
    confidence: number | null;
    verified_version_id: number | null;
    kind: string;
    status: string;
    ingredients: { id: number; quantity: number }[];
};

const props = defineProps<{
    recipe: RecipeRecord | null;
    kinds: Option[];
    statuses: Option[];
    items: IdName[];
    sources: IdName[];
    versions: VersionOption[];
}>();

const str = (value: number | null | undefined): string =>
    value === null || value === undefined ? '' : String(value);

const form = useForm({
    name: props.recipe?.name ?? '',
    slug: props.recipe?.slug ?? '',
    kind: props.recipe?.kind ?? props.kinds[0]?.value ?? '',
    station: props.recipe?.station ?? '',
    level: str(props.recipe?.level),
    output_item_id: props.recipe?.output_item_id ?? null,
    output_quantity: str(props.recipe?.output_quantity ?? 1),
    description: props.recipe?.description ?? '',
    status: props.recipe?.status ?? 'published',
    source_id: props.recipe?.source_id ?? null,
    source_url: props.recipe?.source_url ?? '',
    // The column is NOT NULL, so never submit an empty value.
    confidence: str(props.recipe?.confidence ?? 0),
    verified_version_id: props.recipe?.verified_version_id ?? null,
    ingredients: toItemRows(props.recipe?.ingredients),
});

const slugTouched = ref(props.recipe !== null);

const itemOptions = computed(() =>
    props.items.map((i) => ({ value: i.id, label: i.name })),
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
    const route = props.recipe ? update(props.recipe.id) : store();

    // output_quantity and confidence are NOT NULL columns; fall back to their defaults.
    form.transform((data) => ({
        ...data,
        output_quantity: data.output_quantity || '1',
        confidence: data.confidence || '0',
        ingredients: fromItemRows(data.ingredients),
    })).submit(route, {
        preserveScroll: true,
    });
}

function remove(): void {
    if (props.recipe && window.confirm(`Delete "${props.recipe.name}"?`)) {
        router.delete(destroy(props.recipe.id).url);
    }
}
</script>

<template>
    <Head :title="recipe ? `Edit ${recipe.name}` : 'New recipe'" />

    <PageHeader :title="recipe ? recipe.name : 'New recipe'">
        <template #eyebrow>
            <Link
                :href="index()"
                class="mb-1 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-3" /> All recipes
            </Link>
        </template>
        <template v-if="recipe" #actions>
            <StatusBadge :status="recipe.status" />
            <ConfidenceMeter
                v-if="recipe.confidence !== null"
                :score="recipe.confidence"
                compact
            />
        </template>
    </PageHeader>

    <form class="space-y-5" novalidate @submit.prevent="submit">
        <FormSection title="Recipe">
            <Field id="name" label="Name" required :error="form.errors.name">
                <Input
                    id="name"
                    :model-value="form.name"
                    required
                    autocomplete="off"
                    @update:model-value="onName"
                />
            </Field>
            <Field id="slug" label="Slug" required :error="form.errors.slug">
                <Input
                    id="slug"
                    v-model="form.slug"
                    required
                    autocomplete="off"
                    @input="slugTouched = true"
                />
            </Field>
            <Field id="kind" label="Kind" required :error="form.errors.kind">
                <NativeSelect id="kind" v-model="form.kind" :options="kinds" />
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
            <Field id="station" label="Station" :error="form.errors.station">
                <Input id="station" v-model="form.station" maxlength="255" />
            </Field>
            <Field id="level" label="Level" :error="form.errors.level">
                <Input
                    id="level"
                    v-model="form.level"
                    type="number"
                    min="0"
                    max="100"
                />
            </Field>
            <Field
                id="output_item_id"
                label="Output item"
                :error="form.errors.output_item_id"
            >
                <NativeSelect
                    id="output_item_id"
                    v-model="form.output_item_id"
                    :options="itemOptions"
                    placeholder="— None (upgrade) —"
                />
            </Field>
            <Field
                id="output_quantity"
                label="Output quantity"
                :error="form.errors.output_quantity"
            >
                <Input
                    id="output_quantity"
                    v-model="form.output_quantity"
                    type="number"
                    min="1"
                />
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
                    :rows="4"
                />
            </Field>
        </FormSection>

        <section
            class="rounded-lg border bg-card p-4 md:p-5"
            aria-labelledby="ingredients-heading"
        >
            <h2
                id="ingredients-heading"
                class="text-sm font-semibold tracking-wider uppercase"
            >
                Ingredients
            </h2>
            <p class="mb-4 text-xs text-muted-foreground">
                Each item may appear only once.
            </p>
            <ItemRowsEditor
                v-model="form.ingredients"
                name="ingredients"
                :items="items"
                :errors="errors"
                add-label="Add ingredient"
            />
        </section>

        <FormSection
            title="Provenance & confidence"
            description="Leave unknowns empty — never guess."
        >
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
                id="confidence"
                label="Confidence"
                :error="form.errors.confidence"
                hint="0–100. Use 0 when unverified."
            >
                <Input
                    id="confidence"
                    v-model="form.confidence"
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
                recipe ? 'Save recipe' : 'Create recipe'
            }}</Button>
            <Button as-child variant="ghost"
                ><Link :href="index()">Cancel</Link></Button
            >
            <span v-if="form.isDirty" class="text-xs text-muted-foreground"
                >Unsaved changes</span
            >
            <Button
                v-if="recipe"
                type="button"
                variant="ghost"
                class="ml-auto text-destructive hover:text-destructive"
                @click="remove"
            >
                <Trash2 /> Delete
            </Button>
        </div>
    </form>
</template>
