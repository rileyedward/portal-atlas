<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ColorField from '@/components/admin/ColorField.vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { rowClass, slugify, tableClass } from '@/components/admin/types';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ICON_KEYS, iconSvg } from '@/lib/markerIcons';
import { update as updateCategory } from '@/routes/admin/taxonomy/categories';
import { store as storeItemCategory } from '@/routes/admin/taxonomy/item-categories';
import {
    destroy as destroyType,
    store as storeType,
    update as updateType,
} from '@/routes/admin/taxonomy/types';
import type { Option } from '@/types/game';

type MarkerType = {
    id: number;
    marker_category_id: number;
    slug: string;
    name: string;
    icon: string | null;
    color: string | null;
    geometry: string;
    description: string | null;
    sort_order: number;
};

type MarkerCategory = {
    id: number;
    slug: string;
    name: string;
    color: string;
    icon: string;
    visible_by_default: boolean;
    sort_order: number;
    types_count: number;
    types: MarkerType[];
};

type ItemCategory = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    sort_order: number;
    items_count: number;
};

const props = defineProps<{
    categories: MarkerCategory[];
    itemCategories: ItemCategory[];
    geometries: Option[];
}>();

const geometryLabel = computed(() =>
    Object.fromEntries(props.geometries.map((g) => [g.value, g.label])),
);

// --- Marker categories -------------------------------------------------------

const categoryForm = useForm({
    name: '',
    color: '#000000',
    icon: '',
    visible_by_default: true,
    sort_order: '0',
});
const editingCategory = ref<number | null>(null);

function editCategory(c: MarkerCategory): void {
    editingCategory.value = c.id;
    categoryForm.defaults({
        name: c.name,
        color: c.color,
        icon: c.icon,
        visible_by_default: c.visible_by_default,
        sort_order: String(c.sort_order),
    });
    categoryForm.reset();
    categoryForm.clearErrors();
}

function saveCategory(): void {
    if (editingCategory.value === null) {
        return;
    }

    categoryForm
        .transform((data) => ({ ...data, sort_order: data.sort_order || '0' }))
        .submit(updateCategory(editingCategory.value), {
            preserveScroll: true,
            onSuccess: () => (editingCategory.value = null),
        });
}

// --- Marker types --------------------------------------------------------------

type TypeTarget =
    | { mode: 'create'; categoryId: number }
    | { mode: 'edit'; id: number };

const typeForm = useForm({
    marker_category_id: 0,
    name: '',
    slug: '',
    icon: '',
    color: '',
    geometry: 'point',
    description: '',
    sort_order: '0',
});
const typeTarget = ref<TypeTarget | null>(null);
const typeSlugTouched = ref(false);

function isEditingType(t: MarkerType): boolean {
    return typeTarget.value?.mode === 'edit' && typeTarget.value.id === t.id;
}

function isCreatingIn(c: MarkerCategory): boolean {
    return (
        typeTarget.value?.mode === 'create' &&
        typeTarget.value.categoryId === c.id
    );
}

function startCreateType(c: MarkerCategory): void {
    typeTarget.value = { mode: 'create', categoryId: c.id };
    typeSlugTouched.value = false;
    typeForm.defaults({
        marker_category_id: c.id,
        name: '',
        slug: '',
        icon: '',
        color: '',
        geometry: props.geometries[0]?.value ?? 'point',
        description: '',
        sort_order: String(c.types.length),
    });
    typeForm.reset();
    typeForm.clearErrors();
}

function startEditType(t: MarkerType): void {
    typeTarget.value = { mode: 'edit', id: t.id };
    typeSlugTouched.value = true;
    typeForm.defaults({
        marker_category_id: t.marker_category_id,
        name: t.name,
        slug: t.slug,
        icon: t.icon ?? '',
        color: t.color ?? '',
        geometry: t.geometry,
        description: t.description ?? '',
        sort_order: String(t.sort_order),
    });
    typeForm.reset();
    typeForm.clearErrors();
}

function onTypeName(value: string | number): void {
    typeForm.name = String(value);

    if (!typeSlugTouched.value) {
        typeForm.slug = slugify(typeForm.name);
    }
}

function saveType(): void {
    const target = typeTarget.value;

    if (!target) {
        return;
    }

    typeForm
        .transform((data) => ({ ...data, sort_order: data.sort_order || '0' }))
        .submit(target.mode === 'edit' ? updateType(target.id) : storeType(), {
            preserveScroll: true,
            onSuccess: () => (typeTarget.value = null),
        });
}

function removeType(t: MarkerType): void {
    if (
        window.confirm(
            `Delete the marker type "${t.name}"? Types still used by markers cannot be deleted.`,
        )
    ) {
        router.delete(destroyType(t.id).url, { preserveScroll: true });
    }
}

function onTypeKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        typeTarget.value = null;
    } else if (
        event.key === 'Enter' &&
        (event.target as HTMLElement).tagName === 'INPUT'
    ) {
        event.preventDefault();
        saveType();
    }
}

// --- Item categories -----------------------------------------------------------

const itemCategoryForm = useForm({ name: '', slug: '', description: '' });
const itemSlugTouched = ref(false);

function onItemCategoryName(value: string | number): void {
    itemCategoryForm.name = String(value);

    if (!itemSlugTouched.value) {
        itemCategoryForm.slug = slugify(itemCategoryForm.name);
    }
}

function addItemCategory(): void {
    itemCategoryForm.submit(storeItemCategory(), {
        preserveScroll: true,
        onSuccess: () => {
            itemCategoryForm.reset();
            itemSlugTouched.value = false;
        },
    });
}
</script>

<template>
    <Head title="Marker types" />

    <PageHeader
        title="Marker types"
        description="Categories group marker types in the map's layer panel. Types define each marker's icon and whether it's a point, area or path."
    />

    <datalist id="icon-keys">
        <option v-for="key in ICON_KEYS" :key="key" :value="key" />
    </datalist>

    <section
        aria-labelledby="marker-categories-heading"
        class="mb-10 space-y-4"
    >
        <h2 id="marker-categories-heading" class="sr-only">
            Marker categories
        </h2>
        <EmptyState
            v-if="!categories.length"
            title="No marker categories"
            description="Marker categories are created by the database seeder."
        />

        <article
            v-for="category in categories"
            :key="category.id"
            class="overflow-hidden rounded-lg border bg-card"
        >
            <!-- Category header / editor -->
            <form
                v-if="editingCategory === category.id"
                class="grid gap-3 border-b bg-accent/20 p-4 sm:grid-cols-2 lg:grid-cols-5"
                novalidate
                @submit.prevent="saveCategory"
                @keydown.esc="editingCategory = null"
            >
                <div class="grid gap-1">
                    <label
                        :for="`cat-${category.id}-name`"
                        class="text-xs text-muted-foreground"
                        >Name *</label
                    >
                    <Input
                        :id="`cat-${category.id}-name`"
                        v-model="categoryForm.name"
                        v-focus
                        class="h-8"
                    />
                    <InputError :message="categoryForm.errors.name" />
                </div>
                <div class="grid gap-1">
                    <label
                        :for="`cat-${category.id}-color`"
                        class="text-xs text-muted-foreground"
                        >Colour *</label
                    >
                    <ColorField
                        :id="`cat-${category.id}-color`"
                        v-model="categoryForm.color"
                    />
                    <InputError :message="categoryForm.errors.color" />
                </div>
                <div class="grid gap-1">
                    <label
                        :for="`cat-${category.id}-icon`"
                        class="text-xs text-muted-foreground"
                        >Icon *</label
                    >
                    <Input
                        :id="`cat-${category.id}-icon`"
                        v-model="categoryForm.icon"
                        list="icon-keys"
                        class="h-8"
                    />
                    <InputError :message="categoryForm.errors.icon" />
                </div>
                <div class="grid gap-1">
                    <label
                        :for="`cat-${category.id}-sort`"
                        class="text-xs text-muted-foreground"
                        >Sort order</label
                    >
                    <Input
                        :id="`cat-${category.id}-sort`"
                        v-model="categoryForm.sort_order"
                        type="number"
                        min="0"
                        class="h-8"
                    />
                    <InputError :message="categoryForm.errors.sort_order" />
                </div>
                <label class="flex items-center gap-2 self-end pb-1.5 text-sm">
                    <input
                        v-model="categoryForm.visible_by_default"
                        type="checkbox"
                        class="size-4 accent-primary"
                    />
                    Visible by default
                </label>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="categoryForm.processing"
                        >Save category</Button
                    >
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        @click="editingCategory = null"
                        >Cancel</Button
                    >
                </div>
            </form>
            <header
                v-else
                class="flex flex-wrap items-center gap-3 border-b p-4"
            >
                <span
                    class="flex size-8 items-center justify-center rounded-md [&>svg]:size-4"
                    :style="{
                        backgroundColor: `${category.color}26`,
                        color: category.color,
                    }"
                    aria-hidden="true"
                    v-html="iconSvg(category.icon)"
                />
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold tracking-wide uppercase">
                        {{ category.name }}
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        {{ category.slug }} · {{ category.types_count }} types ·
                        sort {{ category.sort_order }}
                    </p>
                </div>
                <span
                    class="inline-flex items-center gap-1 text-xs"
                    :class="
                        category.visible_by_default
                            ? 'text-success'
                            : 'text-muted-foreground'
                    "
                >
                    <component
                        :is="category.visible_by_default ? Eye : EyeOff"
                        class="size-3.5"
                    />
                    {{
                        category.visible_by_default
                            ? 'Shown by default'
                            : 'Hidden by default'
                    }}
                </span>
                <Button
                    size="sm"
                    variant="ghost"
                    @click="editCategory(category)"
                    ><Pencil /> Edit</Button
                >
            </header>

            <!-- Types -->
            <div class="overflow-x-auto">
                <table :class="tableClass">
                    <thead
                        class="text-[11px] tracking-wider text-muted-foreground uppercase"
                    >
                        <tr>
                            <th scope="col" class="px-3 py-2">Type</th>
                            <th scope="col" class="px-3 py-2">Slug</th>
                            <th scope="col" class="px-3 py-2">Geometry</th>
                            <th scope="col" class="px-3 py-2">Icon / colour</th>
                            <th scope="col" class="px-3 py-2">Description</th>
                            <th scope="col" class="px-3 py-2 text-right">
                                Sort
                            </th>
                            <th scope="col" class="px-3 py-2">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="type in category.types" :key="type.id">
                            <tr v-if="!isEditingType(type)" :class="rowClass">
                                <td class="px-3 py-2 font-medium">
                                    {{ type.name }}
                                </td>
                                <td
                                    class="px-3 py-2 font-mono text-xs text-muted-foreground"
                                >
                                    {{ type.slug }}
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{
                                        geometryLabel[type.geometry] ??
                                        type.geometry
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        class="inline-flex items-center gap-2"
                                    >
                                        <span
                                            class="[&>svg]:size-4"
                                            :style="{
                                                color:
                                                    type.color ??
                                                    category.color,
                                            }"
                                            aria-hidden="true"
                                            v-html="
                                                iconSvg(
                                                    type.icon ?? category.icon,
                                                )
                                            "
                                        />
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >{{
                                                type.icon ??
                                                `${category.icon} (inherited)`
                                            }}</span
                                        >
                                    </span>
                                </td>
                                <td
                                    class="max-w-64 px-3 py-2 text-muted-foreground"
                                >
                                    <span class="line-clamp-2">{{
                                        type.description ?? ''
                                    }}</span>
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    {{ type.sort_order }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            :aria-label="`Edit ${type.name}`"
                                            @click="startEditType(type)"
                                        >
                                            <Pencil />
                                        </Button>
                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            class="text-destructive hover:text-destructive"
                                            :aria-label="`Delete ${type.name}`"
                                            @click="removeType(type)"
                                        >
                                            <Trash2 />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-else
                                class="border-t bg-accent/20 align-top"
                                @keydown="onTypeKeydown"
                            >
                                <td colspan="7" class="p-3">
                                    <fieldset
                                        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                                    >
                                        <legend class="sr-only">
                                            Edit {{ type.name }}
                                        </legend>
                                        <div class="grid gap-1">
                                            <label
                                                :for="`type-${type.id}-name`"
                                                class="text-xs text-muted-foreground"
                                                >Name *</label
                                            >
                                            <Input
                                                :id="`type-${type.id}-name`"
                                                :model-value="typeForm.name"
                                                v-focus
                                                class="h-8"
                                                @update:model-value="onTypeName"
                                            />
                                            <InputError
                                                :message="typeForm.errors.name"
                                            />
                                        </div>
                                        <div class="grid gap-1">
                                            <label
                                                :for="`type-${type.id}-slug`"
                                                class="text-xs text-muted-foreground"
                                                >Slug *</label
                                            >
                                            <Input
                                                :id="`type-${type.id}-slug`"
                                                v-model="typeForm.slug"
                                                class="h-8"
                                            />
                                            <InputError
                                                :message="typeForm.errors.slug"
                                            />
                                        </div>
                                        <div class="grid gap-1">
                                            <label
                                                :for="`type-${type.id}-category`"
                                                class="text-xs text-muted-foreground"
                                                >Category *</label
                                            >
                                            <NativeSelect
                                                :id="`type-${type.id}-category`"
                                                v-model="
                                                    typeForm.marker_category_id
                                                "
                                                :options="
                                                    categories.map((c) => ({
                                                        value: c.id,
                                                        label: c.name,
                                                    }))
                                                "
                                                class="h-8"
                                            />
                                            <InputError
                                                :message="
                                                    typeForm.errors
                                                        .marker_category_id
                                                "
                                            />
                                        </div>
                                        <div class="grid gap-1">
                                            <label
                                                :for="`type-${type.id}-geometry`"
                                                class="text-xs text-muted-foreground"
                                                >Geometry *</label
                                            >
                                            <NativeSelect
                                                :id="`type-${type.id}-geometry`"
                                                v-model="typeForm.geometry"
                                                :options="geometries"
                                                class="h-8"
                                            />
                                            <InputError
                                                :message="
                                                    typeForm.errors.geometry
                                                "
                                            />
                                        </div>
                                        <div class="grid gap-1">
                                            <label
                                                :for="`type-${type.id}-icon`"
                                                class="text-xs text-muted-foreground"
                                                >Icon</label
                                            >
                                            <Input
                                                :id="`type-${type.id}-icon`"
                                                v-model="typeForm.icon"
                                                list="icon-keys"
                                                class="h-8"
                                                :placeholder="`${category.icon} (inherit)`"
                                            />
                                            <InputError
                                                :message="typeForm.errors.icon"
                                            />
                                        </div>
                                        <div class="grid gap-1">
                                            <label
                                                :for="`type-${type.id}-color`"
                                                class="text-xs text-muted-foreground"
                                                >Colour</label
                                            >
                                            <ColorField
                                                :id="`type-${type.id}-color`"
                                                v-model="typeForm.color"
                                                :fallback="category.color"
                                                placeholder="Inherit"
                                            />
                                            <InputError
                                                :message="typeForm.errors.color"
                                            />
                                        </div>
                                        <div class="grid gap-1">
                                            <label
                                                :for="`type-${type.id}-sort`"
                                                class="text-xs text-muted-foreground"
                                                >Sort order</label
                                            >
                                            <Input
                                                :id="`type-${type.id}-sort`"
                                                v-model="typeForm.sort_order"
                                                type="number"
                                                min="0"
                                                class="h-8"
                                            />
                                            <InputError
                                                :message="
                                                    typeForm.errors.sort_order
                                                "
                                            />
                                        </div>
                                        <div
                                            class="grid gap-1 sm:col-span-2 lg:col-span-4"
                                        >
                                            <label
                                                :for="`type-${type.id}-description`"
                                                class="text-xs text-muted-foreground"
                                                >Description</label
                                            >
                                            <Input
                                                :id="`type-${type.id}-description`"
                                                v-model="typeForm.description"
                                                class="h-8"
                                            />
                                            <InputError
                                                :message="
                                                    typeForm.errors.description
                                                "
                                            />
                                        </div>
                                        <div
                                            class="flex gap-2 sm:col-span-2 lg:col-span-4"
                                        >
                                            <Button
                                                size="sm"
                                                :disabled="typeForm.processing"
                                                @click="saveType"
                                                >Save type</Button
                                            >
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click="typeTarget = null"
                                                >Cancel</Button
                                            >
                                        </div>
                                    </fieldset>
                                </td>
                            </tr>
                        </template>

                        <tr
                            v-if="isCreatingIn(category)"
                            class="border-t bg-accent/20 align-top"
                            @keydown="onTypeKeydown"
                        >
                            <td colspan="7" class="p-3">
                                <fieldset
                                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                                >
                                    <legend
                                        class="mb-2 text-xs font-semibold tracking-wider uppercase"
                                    >
                                        New type in {{ category.name }}
                                    </legend>
                                    <div class="grid gap-1">
                                        <label
                                            :for="`new-type-${category.id}-name`"
                                            class="text-xs text-muted-foreground"
                                            >Name *</label
                                        >
                                        <Input
                                            :id="`new-type-${category.id}-name`"
                                            :model-value="typeForm.name"
                                            v-focus
                                            class="h-8"
                                            @update:model-value="onTypeName"
                                        />
                                        <InputError
                                            :message="typeForm.errors.name"
                                        />
                                    </div>
                                    <div class="grid gap-1">
                                        <label
                                            :for="`new-type-${category.id}-slug`"
                                            class="text-xs text-muted-foreground"
                                            >Slug *</label
                                        >
                                        <Input
                                            :id="`new-type-${category.id}-slug`"
                                            v-model="typeForm.slug"
                                            class="h-8"
                                            @input="typeSlugTouched = true"
                                        />
                                        <InputError
                                            :message="typeForm.errors.slug"
                                        />
                                    </div>
                                    <div class="grid gap-1">
                                        <label
                                            :for="`new-type-${category.id}-geometry`"
                                            class="text-xs text-muted-foreground"
                                            >Geometry *</label
                                        >
                                        <NativeSelect
                                            :id="`new-type-${category.id}-geometry`"
                                            v-model="typeForm.geometry"
                                            :options="geometries"
                                            class="h-8"
                                        />
                                        <InputError
                                            :message="typeForm.errors.geometry"
                                        />
                                    </div>
                                    <div class="grid gap-1">
                                        <label
                                            :for="`new-type-${category.id}-icon`"
                                            class="text-xs text-muted-foreground"
                                            >Icon</label
                                        >
                                        <Input
                                            :id="`new-type-${category.id}-icon`"
                                            v-model="typeForm.icon"
                                            list="icon-keys"
                                            class="h-8"
                                            :placeholder="`${category.icon} (inherit)`"
                                        />
                                        <InputError
                                            :message="typeForm.errors.icon"
                                        />
                                    </div>
                                    <div class="grid gap-1">
                                        <label
                                            :for="`new-type-${category.id}-color`"
                                            class="text-xs text-muted-foreground"
                                            >Colour</label
                                        >
                                        <ColorField
                                            :id="`new-type-${category.id}-color`"
                                            v-model="typeForm.color"
                                            :fallback="category.color"
                                            placeholder="Inherit"
                                        />
                                        <InputError
                                            :message="typeForm.errors.color"
                                        />
                                    </div>
                                    <div class="grid gap-1">
                                        <label
                                            :for="`new-type-${category.id}-sort`"
                                            class="text-xs text-muted-foreground"
                                            >Sort order</label
                                        >
                                        <Input
                                            :id="`new-type-${category.id}-sort`"
                                            v-model="typeForm.sort_order"
                                            type="number"
                                            min="0"
                                            class="h-8"
                                        />
                                        <InputError
                                            :message="
                                                typeForm.errors.sort_order
                                            "
                                        />
                                    </div>
                                    <div class="grid gap-1 sm:col-span-2">
                                        <label
                                            :for="`new-type-${category.id}-description`"
                                            class="text-xs text-muted-foreground"
                                            >Description</label
                                        >
                                        <Input
                                            :id="`new-type-${category.id}-description`"
                                            v-model="typeForm.description"
                                            class="h-8"
                                        />
                                        <InputError
                                            :message="
                                                typeForm.errors.description
                                            "
                                        />
                                    </div>
                                    <div
                                        class="flex gap-2 sm:col-span-2 lg:col-span-4"
                                    >
                                        <Button
                                            size="sm"
                                            :disabled="typeForm.processing"
                                            @click="saveType"
                                            >Add type</Button
                                        >
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            @click="typeTarget = null"
                                            >Cancel</Button
                                        >
                                    </div>
                                </fieldset>
                            </td>
                        </tr>
                        <tr
                            v-if="
                                !category.types.length &&
                                !isCreatingIn(category)
                            "
                        >
                            <td
                                colspan="7"
                                class="px-3 py-4 text-center text-muted-foreground"
                            >
                                No types in this category.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="!isCreatingIn(category)" class="border-t p-2">
                <Button
                    size="sm"
                    variant="ghost"
                    @click="startCreateType(category)"
                    ><Plus /> Add type</Button
                >
            </div>
        </article>
    </section>

    <section aria-labelledby="item-categories-heading">
        <h2
            id="item-categories-heading"
            class="mb-1 font-display text-xl font-semibold tracking-wide uppercase"
        >
            Item categories
        </h2>
        <p class="mb-4 text-sm text-muted-foreground">
            Used to group items in the item database.
        </p>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div
                v-if="itemCategories.length"
                class="overflow-x-auto rounded-lg border bg-card"
            >
                <table :class="tableClass">
                    <thead
                        class="text-[11px] tracking-wider text-muted-foreground uppercase"
                    >
                        <tr>
                            <th scope="col" class="px-3 py-2">Name</th>
                            <th scope="col" class="px-3 py-2">Slug</th>
                            <th scope="col" class="px-3 py-2">Description</th>
                            <th scope="col" class="px-3 py-2 text-right">
                                Items
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="ic in itemCategories"
                            :key="ic.id"
                            :class="rowClass"
                        >
                            <td class="px-3 py-2 font-medium">{{ ic.name }}</td>
                            <td
                                class="px-3 py-2 font-mono text-xs text-muted-foreground"
                            >
                                {{ ic.slug }}
                            </td>
                            <td class="px-3 py-2 text-muted-foreground">
                                <span class="line-clamp-2">{{
                                    ic.description ?? ''
                                }}</span>
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                {{ ic.items_count }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                title="No item categories"
                description="Add the first one using the form."
            />

            <form
                class="space-y-3 self-start rounded-lg border bg-card p-4"
                novalidate
                @submit.prevent="addItemCategory"
            >
                <h3 class="text-sm font-semibold tracking-wider uppercase">
                    Add item category
                </h3>
                <div class="grid gap-1">
                    <label for="ic-name" class="text-xs text-muted-foreground"
                        >Name *</label
                    >
                    <Input
                        id="ic-name"
                        :model-value="itemCategoryForm.name"
                        class="h-8"
                        @update:model-value="onItemCategoryName"
                    />
                    <InputError :message="itemCategoryForm.errors.name" />
                </div>
                <div class="grid gap-1">
                    <label for="ic-slug" class="text-xs text-muted-foreground"
                        >Slug *</label
                    >
                    <Input
                        id="ic-slug"
                        v-model="itemCategoryForm.slug"
                        class="h-8"
                        @input="itemSlugTouched = true"
                    />
                    <InputError :message="itemCategoryForm.errors.slug" />
                </div>
                <div class="grid gap-1">
                    <label
                        for="ic-description"
                        class="text-xs text-muted-foreground"
                        >Description</label
                    >
                    <Input
                        id="ic-description"
                        v-model="itemCategoryForm.description"
                        class="h-8"
                    />
                    <InputError
                        :message="itemCategoryForm.errors.description"
                    />
                </div>
                <Button
                    type="submit"
                    size="sm"
                    :disabled="itemCategoryForm.processing"
                    ><Plus /> Add category</Button
                >
            </form>
        </div>
    </section>
</template>
