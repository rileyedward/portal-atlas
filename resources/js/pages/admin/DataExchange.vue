<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    Download,
    FileJson,
    Upload,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { exportMethod, importMethod } from '@/routes/admin/data';

type ImportResult = {
    dry_run: boolean;
    created: number;
    updated: number;
    unchanged: number;
    errors: string[];
    warnings: string[];
};

defineProps<{ maps: { id: number; slug: string; name: string }[] }>();

const page = usePage();

function isImportResult(value: unknown): value is ImportResult {
    return (
        typeof value === 'object' &&
        value !== null &&
        'dry_run' in value &&
        Array.isArray((value as ImportResult).errors) &&
        Array.isArray((value as ImportResult).warnings)
    );
}

const result = computed<ImportResult | null>(() => {
    const value = page.flash?.importResult;

    return isImportResult(value) ? value : null;
});

const form = useForm({ file: null as File | null, dry_run: true });
const fileInput = ref<HTMLInputElement | null>(null);
const fileName = ref<string | null>(null);

function onFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.file = file;
    fileName.value = file?.name ?? null;
    form.clearErrors('file');
}

function submit(): void {
    form.post(importMethod.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Keep the file selected after a dry run so it can be applied right away.
            if (!form.dry_run) {
                form.reset('file');
                fileName.value = null;

                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            }
        },
    });
}

const markerExample = `{
  "format": "active-matter-map/v1",
  "map": "<map-slug>",
  "version": "<game version>",
  "markers": [
    {
      "id": 123,
      "type": "<marker-type-slug>",
      "name": "…",
      "description": "…",
      "x": 42.5,
      "y": 61.0,
      "status": "published",
      "source": "<source name>",
      "source_url": "https://…",
      "verified_version": "<game version>",
      "items": ["<item-slug>"],
      "objectives": ["<objective-slug>"]
    }
  ]
}`;

const gameDataExample = `{
  "format": "active-matter-data/v1",
  "versions": [{ "version": "…", "is_current": true }],
  "sources": [{ "name": "…", "kind": "community_wiki", "reliability": 70 }],
  "maps": [{ "slug": "…", "name": "…" }],
  "item_categories": [{ "slug": "…", "name": "…" }],
  "items": [{ "slug": "…", "name": "…", "category": "<category-slug>" }],
  "recipes": [{ "slug": "…", "name": "…", "kind": "…",
                "ingredients": [{ "item": "<item-slug>", "quantity": 2 }] }],
  "objectives": [{ "slug": "…", "name": "…", "kind": "…", "map": "<map-slug>",
                   "items": [{ "item": "<item-slug>", "quantity": 1, "role": "required" }] }]
}`;
</script>

<template>
    <Head title="Import / export" />

    <PageHeader
        title="Import / export"
        description="Move data in and out as JSON. Imports run in one transaction, never delete anything, and default to a dry run."
    />

    <div class="grid gap-6 lg:grid-cols-2">
        <section
            aria-labelledby="export-heading"
            class="rounded-lg border bg-card p-4 md:p-5"
        >
            <h2
                id="export-heading"
                class="mb-1 text-sm font-semibold tracking-wider uppercase"
            >
                Export map markers
            </h2>
            <p class="mb-4 text-xs text-muted-foreground">
                Downloads every marker on a map in the
                <code class="font-mono">active-matter-map/v1</code> format.
            </p>
            <ul v-if="maps.length" class="divide-y rounded-md border">
                <li
                    v-for="map in maps"
                    :key="map.id"
                    class="flex items-center justify-between gap-3 px-3 py-2 text-sm"
                >
                    <span class="min-w-0">
                        <span class="block truncate font-medium">{{
                            map.name
                        }}</span>
                        <span
                            class="block font-mono text-xs text-muted-foreground"
                            >{{ map.slug }}</span
                        >
                    </span>
                    <Button as-child size="sm" variant="outline">
                        <a
                            :href="exportMethod(map.slug).url"
                            :download="`${map.slug}-markers.json`"
                        >
                            <Download /> Download JSON
                        </a>
                    </Button>
                </li>
            </ul>
            <EmptyState
                v-else
                title="No maps"
                description="Create a map before exporting markers."
            />
        </section>

        <section aria-labelledby="import-heading" class="space-y-4">
            <form
                class="rounded-lg border bg-card p-4 md:p-5"
                novalidate
                @submit.prevent="submit"
            >
                <h2
                    id="import-heading"
                    class="mb-1 text-sm font-semibold tracking-wider uppercase"
                >
                    Import JSON
                </h2>
                <p class="mb-4 text-xs text-muted-foreground">
                    Accepts a map marker file (<code class="font-mono"
                        >active-matter-map/v1</code
                    >) or a reference game-data file (<code class="font-mono"
                        >active-matter-data/v1</code
                    >). Max 10 MB.
                </p>

                <label
                    for="import-file"
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-md border border-dashed p-6 text-center text-sm transition focus-within:border-primary focus-within:ring-2 focus-within:ring-ring/50 hover:border-primary/60 hover:bg-accent/20"
                >
                    <FileJson class="size-6 text-muted-foreground" />
                    <span v-if="fileName" class="font-medium">{{
                        fileName
                    }}</span>
                    <span v-else class="text-muted-foreground"
                        >Choose a
                        <span class="font-mono">.json</span> file</span
                    >
                    <input
                        id="import-file"
                        ref="fileInput"
                        type="file"
                        accept="application/json,.json"
                        class="sr-only"
                        @change="onFile"
                    />
                </label>
                <InputError class="mt-1" :message="form.errors.file" />

                <label class="mt-4 flex items-start gap-2 text-sm">
                    <input
                        v-model="form.dry_run"
                        type="checkbox"
                        class="mt-0.5 size-4 accent-primary"
                    />
                    <span>
                        <span class="font-medium">Dry run</span>
                        <span class="block text-xs text-muted-foreground"
                            >Validate and report what would change without
                            saving anything.</span
                        >
                    </span>
                </label>
                <InputError :message="form.errors.dry_run" />

                <progress
                    v-if="form.progress"
                    :value="form.progress.percentage"
                    max="100"
                    class="mt-3 w-full"
                >
                    {{ form.progress.percentage }}%
                </progress>

                <Button
                    type="submit"
                    class="mt-4"
                    :variant="form.dry_run ? 'secondary' : 'default'"
                    :disabled="!form.file || form.processing"
                >
                    <Upload />
                    {{
                        form.dry_run ? 'Validate (dry run)' : 'Import and apply'
                    }}
                </Button>
            </form>

            <div
                v-if="result"
                class="rounded-lg border p-4 md:p-5"
                :class="
                    result.errors.length
                        ? 'border-destructive/50 bg-destructive/5'
                        : 'border-success/40 bg-success/5'
                "
                role="status"
                aria-live="polite"
            >
                <h3
                    class="flex items-center gap-2 text-sm font-semibold tracking-wider uppercase"
                >
                    <XCircle
                        v-if="result.errors.length"
                        class="size-4 text-destructive"
                    />
                    <CheckCircle2 v-else class="size-4 text-success" />
                    <template v-if="result.errors.length"
                        >Import failed — nothing was saved</template
                    >
                    <template v-else-if="result.dry_run"
                        >Dry run passed — nothing was saved yet</template
                    >
                    <template v-else>Import applied</template>
                </h3>

                <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-md border bg-card p-2">
                        <dt
                            class="text-[11px] tracking-wider text-muted-foreground uppercase"
                        >
                            {{ result.dry_run ? 'Would create' : 'Created' }}
                        </dt>
                        <dd
                            class="font-display text-xl font-semibold tabular-nums"
                        >
                            {{ result.created }}
                        </dd>
                    </div>
                    <div class="rounded-md border bg-card p-2">
                        <dt
                            class="text-[11px] tracking-wider text-muted-foreground uppercase"
                        >
                            {{ result.dry_run ? 'Would update' : 'Updated' }}
                        </dt>
                        <dd
                            class="font-display text-xl font-semibold tabular-nums"
                        >
                            {{ result.updated }}
                        </dd>
                    </div>
                    <div class="rounded-md border bg-card p-2">
                        <dt
                            class="text-[11px] tracking-wider text-muted-foreground uppercase"
                        >
                            Unchanged
                        </dt>
                        <dd
                            class="font-display text-xl font-semibold tabular-nums"
                        >
                            {{ result.unchanged }}
                        </dd>
                    </div>
                </dl>

                <div v-if="result.errors.length" class="mt-4">
                    <h4
                        class="mb-1 text-xs font-semibold text-destructive uppercase"
                    >
                        Errors ({{ result.errors.length }})
                    </h4>
                    <ul
                        class="max-h-64 list-disc space-y-0.5 overflow-auto pl-5 text-sm"
                    >
                        <li v-for="(error, i) in result.errors" :key="i">
                            {{ error }}
                        </li>
                    </ul>
                </div>
                <div v-if="result.warnings.length" class="mt-4">
                    <h4
                        class="mb-1 flex items-center gap-1 text-xs font-semibold text-warning uppercase"
                    >
                        <AlertTriangle class="size-3.5" /> Warnings ({{
                            result.warnings.length
                        }})
                    </h4>
                    <ul
                        class="max-h-64 list-disc space-y-0.5 overflow-auto pl-5 text-sm text-muted-foreground"
                    >
                        <li v-for="(warning, i) in result.warnings" :key="i">
                            {{ warning }}
                        </li>
                    </ul>
                </div>
                <p
                    v-if="result.dry_run && !result.errors.length"
                    class="mt-4 text-xs text-muted-foreground"
                >
                    Untick “Dry run” and import the same file to apply these
                    changes.
                </p>
            </div>
        </section>
    </div>

    <section
        aria-labelledby="format-heading"
        class="mt-8 rounded-lg border bg-card p-4 md:p-5"
    >
        <h2
            id="format-heading"
            class="mb-3 text-sm font-semibold tracking-wider uppercase"
        >
            File formats
        </h2>
        <div class="grid gap-6 text-sm lg:grid-cols-2">
            <div>
                <h3 class="mb-1 font-medium">
                    Map markers —
                    <code class="font-mono text-primary"
                        >active-matter-map/v1</code
                    >
                </h3>
                <ul class="mb-3 list-disc space-y-1 pl-5 text-muted-foreground">
                    <li>
                        <code class="font-mono">map</code> is the slug of an
                        existing map — create the map first.
                    </li>
                    <li>
                        <code class="font-mono">type</code> is a marker type
                        slug (see Marker types).
                    </li>
                    <li>
                        <code class="font-mono">x</code>/<code class="font-mono"
                            >y</code
                        >
                        are 0–100 percent from the top left. Omit both for an
                        unplaced marker.
                        <code class="font-mono">geometry</code> is an optional
                        <code class="font-mono">[[x, y], …]</code> list for
                        areas and paths.
                    </li>
                    <li>
                        <code class="font-mono">status</code>: draft, published
                        (default), hidden or removed.
                    </li>
                    <li>
                        Unknown <code class="font-mono">source</code> names or
                        versions are warnings and ignored; unknown
                        item/objective slugs are errors.
                    </li>
                    <li>
                        Matching: an <code class="font-mono">id</code> on the
                        same map is updated; otherwise a marker with the same
                        type and name; otherwise a new marker is created.
                    </li>
                </ul>
                <pre
                    class="overflow-x-auto rounded-md border bg-background p-3 font-mono text-xs leading-relaxed"
                    >{{ markerExample }}</pre>
            </div>
            <div>
                <h3 class="mb-1 font-medium">
                    Reference data —
                    <code class="font-mono text-primary"
                        >active-matter-data/v1</code
                    >
                </h3>
                <ul class="mb-3 list-disc space-y-1 pl-5 text-muted-foreground">
                    <li>
                        All keys are optional:
                        <code class="font-mono">versions</code>,
                        <code class="font-mono">sources</code>,
                        <code class="font-mono">maps</code>,
                        <code class="font-mono">item_categories</code>,
                        <code class="font-mono">items</code>,
                        <code class="font-mono">recipes</code>,
                        <code class="font-mono">objectives</code>.
                    </li>
                    <li>
                        Rows are upserted by natural key: version string, source
                        name, or slug.
                    </li>
                    <li>
                        References between rows (category, item, map, source,
                        version) use those same keys.
                    </li>
                    <li>
                        Any error rolls back the whole file. Nothing is ever
                        deleted.
                    </li>
                </ul>
                <pre
                    class="overflow-x-auto rounded-md border bg-background p-3 font-mono text-xs leading-relaxed"
                    >{{ gameDataExample }}</pre>
                <p class="mt-2 text-xs text-muted-foreground">
                    Placeholders only — see
                    <code class="font-mono">docs/data-import.md</code> for the
                    full reference and CLI usage.
                </p>
            </div>
        </div>
    </section>
</template>
