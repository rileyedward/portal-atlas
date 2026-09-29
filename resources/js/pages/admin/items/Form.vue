<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, BadgeCheck, ExternalLink, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Field from '@/components/admin/Field.vue';
import FormSection from '@/components/admin/FormSection.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { formatDate, slugify } from '@/components/admin/types';
import type { IdName, VersionOption } from '@/components/admin/types';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { destroy, index, store, update, verify } from '@/routes/admin/items';
import { editor } from '@/routes/admin/maps';
import { edit as editRecipe } from '@/routes/admin/recipes';
import { show as publicItem } from '@/routes/items';
import type { Option } from '@/types/game';

type ItemRecord = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    item_category_id: number | null;
    rarity: string | null;
    value: number | null;
    weight: number | string | null;
    source_id: number | null;
    source_url: string | null;
    source_note: string | null;
    confidence: number;
    confidence_override: number | null;
    introduced_version_id: number | null;
    verified_version_id: number | null;
    status: string;
    last_verified_at: string | null;
    markers: { id: number; name: string; map: string; map_slug: string }[];
    recipes: IdName[];
    objectives: IdName[];
};

const props = defineProps<{
    item: ItemRecord | null;
    categories: IdName[];
    sources: IdName[];
    versions: VersionOption[];
    statuses: Option[];
}>();

const str = (value: number | string | null | undefined): string =>
    value === null || value === undefined ? '' : String(value);

const form = useForm({
    name: props.item?.name ?? '',
    slug: props.item?.slug ?? '',
    description: props.item?.description ?? '',
    item_category_id: props.item?.item_category_id ?? null,
    rarity: props.item?.rarity ?? '',
    value: str(props.item?.value),
    weight: str(props.item?.weight),
    status: props.item?.status ?? 'published',
    source_id: props.item?.source_id ?? null,
    source_url: props.item?.source_url ?? '',
    source_note: props.item?.source_note ?? '',
    confidence_override: str(props.item?.confidence_override),
    introduced_version_id: props.item?.introduced_version_id ?? null,
    verified_version_id: props.item?.verified_version_id ?? null,
});

const slugTouched = ref(props.item !== null);
const verifying = ref(false);

const categoryOptions = computed(() =>
    props.categories.map((c) => ({ value: c.id, label: c.name })),
);
const sourceOptions = computed(() =>
    props.sources.map((s) => ({ value: s.id, label: s.name })),
);
const versionOptions = computed(() =>
    props.versions.map((v) => ({ value: v.id, label: v.version })),
);
const verifiedVersion = computed(
    () =>
        props.versions.find((v) => v.id === props.item?.verified_version_id)
            ?.version ?? null,
);

function onName(value: string | number): void {
    form.name = String(value);

    if (!slugTouched.value) {
        form.slug = slugify(form.name);
    }
}

function submit(): void {
    if (props.item) {
        form.submit(update(props.item.slug), { preserveScroll: true });
    } else {
        form.submit(store(), { preserveScroll: true });
    }
}

function markVerified(): void {
    if (!props.item) {
        return;
    }

    router.post(
        verify(props.item.slug).url,
        {},
        {
            preserveScroll: true,
            onStart: () => (verifying.value = true),
            onFinish: () => (verifying.value = false),
        },
    );
}

function remove(): void {
    if (props.item && window.confirm(`Delete the item "${props.item.name}"?`)) {
        router.delete(destroy(props.item.slug).url);
    }
}
</script>

<template>
    <Head :title="item ? `Edit ${item.name}` : 'New item'" />

    <PageHeader :title="item ? item.name : 'New item'">
        <template #eyebrow>
            <Link
                :href="index()"
                class="mb-1 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-3" /> All items
            </Link>
        </template>
        <template v-if="item" #actions>
            <StatusBadge :status="item.status" />
            <Button as-child variant="outline" size="sm">
                <a
                    :href="publicItem(item.slug).url"
                    target="_blank"
                    rel="noopener"
                    ><ExternalLink /> Public page</a
                >
            </Button>
            <Button
                size="sm"
                variant="secondary"
                :disabled="verifying"
                @click="markVerified"
            >
                <BadgeCheck /> Verify for current version
            </Button>
        </template>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
        <form class="min-w-0 space-y-5" novalidate @submit.prevent="submit">
            <FormSection title="Item">
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
                    id="item_category_id"
                    label="Category"
                    :error="form.errors.item_category_id"
                >
                    <NativeSelect
                        id="item_category_id"
                        v-model="form.item_category_id"
                        :options="categoryOptions"
                        placeholder="— Uncategorised —"
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
                <Field id="rarity" label="Rarity" :error="form.errors.rarity">
                    <Input id="rarity" v-model="form.rarity" maxlength="20" />
                </Field>
                <div class="grid grid-cols-2 gap-4">
                    <Field id="value" label="Value" :error="form.errors.value">
                        <Input
                            id="value"
                            v-model="form.value"
                            type="number"
                            min="0"
                            step="1"
                        />
                    </Field>
                    <Field
                        id="weight"
                        label="Weight"
                        :error="form.errors.weight"
                    >
                        <Input
                            id="weight"
                            v-model="form.weight"
                            type="number"
                            min="0"
                            step="any"
                        />
                    </Field>
                </div>
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
            </FormSection>

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
                    item ? 'Save item' : 'Create item'
                }}</Button>
                <Button as-child variant="ghost"
                    ><Link :href="index()">Cancel</Link></Button
                >
                <span v-if="form.isDirty" class="text-xs text-muted-foreground"
                    >Unsaved changes</span
                >
                <Button
                    v-if="item"
                    type="button"
                    variant="ghost"
                    class="ml-auto text-destructive hover:text-destructive"
                    @click="remove"
                >
                    <Trash2 /> Delete
                </Button>
            </div>
        </form>

        <aside v-if="item" class="space-y-4">
            <div class="rounded-lg border bg-card p-4 text-sm">
                <h2 class="mb-3 text-xs font-semibold tracking-wider uppercase">
                    Confidence
                </h2>
                <ConfidenceMeter
                    :score="item.confidence_override ?? item.confidence"
                />
                <dl class="mt-3 space-y-1 text-xs">
                    <div class="flex justify-between gap-2">
                        <dt class="text-muted-foreground">Calculated</dt>
                        <dd class="tabular-nums">{{ item.confidence }}%</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-muted-foreground">Verified on</dt>
                        <dd>{{ verifiedVersion ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-muted-foreground">Last verified</dt>
                        <dd>{{ formatDate(item.last_verified_at) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border bg-card p-4 text-sm">
                <h2 class="mb-2 text-xs font-semibold tracking-wider uppercase">
                    Found at ({{ item.markers.length }})
                </h2>
                <ul v-if="item.markers.length" class="space-y-1">
                    <li v-for="marker in item.markers" :key="marker.id">
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
                    Not linked to any marker.
                </p>
            </div>

            <div class="rounded-lg border bg-card p-4 text-sm">
                <h2 class="mb-2 text-xs font-semibold tracking-wider uppercase">
                    Used in recipes ({{ item.recipes.length }})
                </h2>
                <ul v-if="item.recipes.length" class="space-y-1">
                    <li v-for="recipe in item.recipes" :key="recipe.id">
                        <Link
                            :href="editRecipe(recipe.id)"
                            class="hover:text-primary"
                            >{{ recipe.name }}</Link
                        >
                    </li>
                </ul>
                <p v-else class="text-muted-foreground">None.</p>
            </div>

            <div class="rounded-lg border bg-card p-4 text-sm">
                <h2 class="mb-2 text-xs font-semibold tracking-wider uppercase">
                    Objectives ({{ item.objectives.length }})
                </h2>
                <ul v-if="item.objectives.length" class="space-y-1">
                    <li
                        v-for="objective in item.objectives"
                        :key="objective.id"
                    >
                        {{ objective.name }}
                    </li>
                </ul>
                <p v-else class="text-muted-foreground">None.</p>
            </div>
        </aside>
    </div>
</template>
