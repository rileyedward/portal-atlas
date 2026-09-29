<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    Check,
    ExternalLink,
    Mail,
    MapPin,
    MapPinned,
    MessageSquareWarning,
    Pencil,
    RotateCcw,
    Search,
    X,
} from '@lucide/vue';
import { reactive, ref } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { formatDate } from '@/components/admin/types';
import type { Paginated } from '@/components/admin/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { applySuggestion, index, update } from '@/routes/admin/reports';
import type { Option } from '@/types/game';

type FeedbackRow = {
    id: number;
    type: string;
    type_label: string;
    status: string;
    message: string | null;
    context: string | null;
    page_url: string | null;
    reporter: {
        name: string | null;
        email: string | null;
        registered: boolean;
    };
    resolver: string | null;
    resolution_note: string | null;
    resolved_at: string | null;
    created_at: string;
    subject: {
        kind: string;
        name: string | null;
        detail: string | null;
        edit_url: string | null;
        public_url: string | null;
    } | null;
    suggestion: {
        x: number;
        y: number;
        map: { slug: string; name: string } | null;
        can_apply: boolean;
        current: { x: number; y: number } | null;
    } | null;
};

const props = defineProps<{
    reports: Paginated<FeedbackRow>;
    filters: { status: string; kind: string; type: string; search: string };
    statuses: Option[];
    types: Option[];
    counts: { open: number; with_position: number };
}>();

const filters = reactive({ ...props.filters });

const statusTabs = [
    { value: 'open', label: 'Open' },
    { value: 'accepted', label: 'Resolved' },
    { value: 'rejected', label: 'Dismissed' },
    { value: 'all', label: 'All' },
];
const kinds = [
    { value: 'all', label: 'Everything' },
    { value: 'data', label: 'About specific data' },
    { value: 'position', label: 'With a suggested position' },
    { value: 'general', label: 'General site feedback' },
];

function apply(changes: Partial<typeof filters> = {}): void {
    Object.assign(filters, changes);
    router.get(
        index().url,
        {
            status: filters.status,
            kind: filters.kind === 'all' ? undefined : filters.kind,
            type: filters.type || undefined,
            search: filters.search || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const searchLater = useDebounceFn(() => apply(), 300);

const notes = reactive<Record<number, string>>({});
const busy = ref<number | null>(null);

function setStatus(row: FeedbackRow, status: string): void {
    busy.value = row.id;
    router.patch(
        update(row.id).url,
        {
            status,
            resolution_note: notes[row.id] ?? row.resolution_note ?? null,
        },
        { preserveScroll: true, onFinish: () => (busy.value = null) },
    );
}

function applyPosition(row: FeedbackRow): void {
    if (
        !confirm(
            'Move the marker to the suggested position and resolve this feedback?',
        )
    ) {
        return;
    }

    busy.value = row.id;
    router.post(
        applySuggestion(row.id).url,
        { resolution_note: notes[row.id] || null },
        { preserveScroll: true, onFinish: () => (busy.value = null) },
    );
}

function mapLink(row: FeedbackRow): string | null {
    const s = row.suggestion;

    return s?.map ? `/maps/${s.map.slug}` : null;
}
</script>

<template>
    <Head title="Feedback" />

    <PageHeader
        title="Feedback"
        description="Everything players send: problems with specific markers, items and objectives, suggested positions, missing information and general site feedback. Open reports lower the confidence of the thing they're about until you resolve or dismiss them."
    >
        <template #actions>
            <span class="text-sm text-muted-foreground">
                {{ counts.open }} open · {{ counts.with_position }} with
                suggested positions
            </span>
        </template>
    </PageHeader>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <div class="flex rounded-md border p-0.5" role="tablist">
            <button
                v-for="tab in statusTabs"
                :key="tab.value"
                type="button"
                role="tab"
                :aria-selected="filters.status === tab.value"
                class="rounded px-3 py-1 text-sm transition"
                :class="
                    filters.status === tab.value
                        ? 'bg-accent text-foreground'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="apply({ status: tab.value })"
            >
                {{ tab.label }}
            </button>
        </div>
        <select
            v-model="filters.kind"
            class="h-9 rounded-md border bg-transparent px-2 text-sm [&>option]:bg-popover"
            aria-label="Kind of feedback"
            @change="apply()"
        >
            <option v-for="k in kinds" :key="k.value" :value="k.value">
                {{ k.label }}
            </option>
        </select>
        <select
            v-model="filters.type"
            class="h-9 rounded-md border bg-transparent px-2 text-sm [&>option]:bg-popover"
            aria-label="Feedback type"
            @change="apply()"
        >
            <option value="">All types</option>
            <option v-for="t in types" :key="t.value" :value="t.value">
                {{ t.label }}
            </option>
        </select>
        <div class="relative ml-auto w-full sm:w-64">
            <Search
                class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
            />
            <Input
                v-model="filters.search"
                class="pl-8"
                placeholder="Search messages"
                aria-label="Search messages"
                @update:model-value="searchLater"
            />
        </div>
    </div>

    <EmptyState
        v-if="!reports.data.length"
        title="Nothing here"
        description="No feedback matches these filters."
    />

    <ul v-else class="space-y-3">
        <li
            v-for="row in reports.data"
            :key="row.id"
            class="rounded-lg border bg-card p-4"
        >
            <div class="flex flex-wrap items-start gap-3">
                <MessageSquareWarning
                    class="mt-0.5 size-4 shrink-0 text-warning"
                />
                <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ row.type_label }}</span>
                        <StatusBadge
                            :status="row.status"
                            :label="
                                statuses.find((s) => s.value === row.status)
                                    ?.label
                            "
                        />
                        <span class="text-xs text-muted-foreground">
                            #{{ row.id }} · {{ formatDate(row.created_at) }}
                        </span>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        <template v-if="row.subject">
                            {{ row.subject.kind }}:
                            <span class="text-foreground">{{
                                row.subject.name ?? '(deleted)'
                            }}</span>
                            <template v-if="row.subject.detail">
                                · {{ row.subject.detail }}</template
                            >
                        </template>
                        <template v-else>General feedback</template>
                        <template v-if="row.context">
                            · {{ row.context }}</template
                        >
                    </p>

                    <p
                        v-if="row.message"
                        class="rounded-md bg-muted/40 px-3 py-2 text-sm whitespace-pre-line"
                    >
                        {{ row.message }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground italic">
                        No message.
                    </p>

                    <div
                        v-if="row.suggestion"
                        class="flex flex-wrap items-center gap-3 rounded-md border border-warning/30 bg-warning/5 px-3 py-2 text-sm"
                    >
                        <MapPin class="size-4 text-warning" />
                        <span>
                            Suggested position: x
                            {{ row.suggestion.x.toFixed(2) }}, y
                            {{ row.suggestion.y.toFixed(2) }}
                            <span
                                v-if="row.suggestion.current"
                                class="text-muted-foreground"
                            >
                                (currently x
                                {{ row.suggestion.current.x.toFixed(2) }}, y
                                {{ row.suggestion.current.y.toFixed(2) }})
                            </span>
                            <span v-else class="text-muted-foreground">
                                (currently not placed)</span
                            >
                        </span>
                        <Link
                            v-if="mapLink(row)"
                            :href="mapLink(row)!"
                            class="text-primary hover:underline"
                            >View {{ row.suggestion.map?.name }}</Link
                        >
                        <Button
                            v-if="
                                row.suggestion.can_apply &&
                                row.status === 'open'
                            "
                            size="sm"
                            class="ml-auto"
                            :disabled="busy === row.id"
                            @click="applyPosition(row)"
                        >
                            <MapPinned class="size-4" /> Apply position
                        </Button>
                    </div>

                    <p
                        class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                    >
                        <span>
                            From
                            {{
                                row.reporter.name ??
                                (row.reporter.email
                                    ? 'guest'
                                    : 'anonymous guest')
                            }}
                        </span>
                        <a
                            v-if="row.reporter.email"
                            :href="`mailto:${row.reporter.email}?subject=${encodeURIComponent('Your feedback #' + row.id)}`"
                            class="inline-flex items-center gap-1 text-primary hover:underline"
                        >
                            <Mail class="size-3" /> {{ row.reporter.email }}
                        </a>
                        <a
                            v-if="row.page_url"
                            :href="row.page_url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 hover:text-foreground"
                        >
                            Sent from {{ row.page_url }}
                            <ExternalLink class="size-3" />
                        </a>
                    </p>

                    <p
                        v-if="row.status !== 'open'"
                        class="text-xs text-muted-foreground"
                    >
                        {{
                            row.status === 'accepted' ? 'Resolved' : 'Dismissed'
                        }}
                        by {{ row.resolver ?? 'someone' }}
                        {{ row.resolved_at ? formatDate(row.resolved_at) : '' }}
                        <template v-if="row.resolution_note">
                            — “{{ row.resolution_note }}”</template
                        >
                    </p>
                </div>

                <div class="flex shrink-0 flex-col gap-1.5">
                    <Button
                        v-if="row.subject?.edit_url"
                        as-child
                        size="sm"
                        variant="secondary"
                    >
                        <a :href="row.subject.edit_url"
                            ><Pencil class="size-4" /> Fix it</a
                        >
                    </Button>
                    <Button
                        v-if="row.subject?.public_url"
                        as-child
                        size="sm"
                        variant="ghost"
                    >
                        <a
                            :href="row.subject.public_url"
                            target="_blank"
                            rel="noopener"
                            ><ExternalLink class="size-4" /> View</a
                        >
                    </Button>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-end gap-2 border-t pt-3">
                <div class="min-w-0 flex-1">
                    <TextArea
                        :id="`note-${row.id}`"
                        v-model="notes[row.id]"
                        :rows="1"
                        :placeholder="
                            row.resolution_note ??
                            'Resolution note (what you changed, or why it was dismissed)'
                        "
                        aria-label="Resolution note"
                    />
                </div>
                <template v-if="row.status === 'open'">
                    <Button
                        size="sm"
                        :disabled="busy === row.id"
                        @click="setStatus(row, 'accepted')"
                    >
                        <Check class="size-4" /> Resolve
                    </Button>
                    <Button
                        size="sm"
                        variant="secondary"
                        :disabled="busy === row.id"
                        @click="setStatus(row, 'rejected')"
                    >
                        <X class="size-4" /> Dismiss
                    </Button>
                </template>
                <Button
                    v-else
                    size="sm"
                    variant="secondary"
                    :disabled="busy === row.id"
                    @click="setStatus(row, 'open')"
                >
                    <RotateCcw class="size-4" /> Reopen
                </Button>
            </div>
        </li>
    </ul>

    <Pagination class="mt-4" :paginator="reports" />
</template>
