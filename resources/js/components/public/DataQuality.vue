<script setup lang="ts">
import { ExternalLink } from '@lucide/vue';
import ConfidenceMeter from '@/components/game/ConfidenceMeter.vue';
import { formatDate } from './options';

defineProps<{
    confidence: { score: number; label: string };
    /** Only sent to editors/admins; players never see where data came from. */
    source?: { name: string | null; url: string | null } | null;
    lastVerifiedAt: string | null;
    verifiedVersion: string | null;
}>();
</script>

<template>
    <section
        class="space-y-3 rounded-lg border bg-card p-4"
        aria-labelledby="data-quality-title"
    >
        <h2
            id="data-quality-title"
            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Data quality
        </h2>
        <ConfidenceMeter :score="confidence.score" :label="confidence.label" />
        <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
            <template v-if="source !== undefined">
                <dt class="text-muted-foreground">Source</dt>
                <dd class="min-w-0">
                    <a
                        v-if="source?.url"
                        :href="source.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex max-w-full items-center gap-1 text-primary hover:underline"
                    >
                        <span class="truncate">{{
                            source.name ?? 'Source link'
                        }}</span>
                        <ExternalLink class="size-3 shrink-0" />
                    </a>
                    <span v-else-if="source?.name">{{ source.name }}</span>
                    <span v-else class="text-muted-foreground">Unknown</span>
                </dd>
            </template>
            <dt class="text-muted-foreground">Last verified</dt>
            <dd>{{ formatDate(lastVerifiedAt) }}</dd>
            <dt class="text-muted-foreground">Game version</dt>
            <dd>{{ verifiedVersion ?? 'Unknown' }}</dd>
        </dl>
        <slot />
    </section>
</template>
