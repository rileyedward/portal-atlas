<script setup lang="ts">
import { MessageSquareWarning } from '@lucide/vue';
import { useFeedback } from '@/composables/useFeedback';
import type { FeedbackOptions } from '@/composables/useFeedback';

/**
 * Small "something off?" trigger. Icon-only by default; pass a label to show
 * text next to the icon.
 */
const props = withDefaults(
    defineProps<{ options?: FeedbackOptions; label?: string; hint?: string }>(),
    { options: () => ({}), label: undefined, hint: undefined },
);

const { openFeedback } = useFeedback();

const title =
    props.hint ??
    (props.options.subject
        ? `Something off with ${props.options.subject.name}${props.options.context ? ` (${props.options.context})` : ''}? Send feedback`
        : 'Something off? Send feedback');
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md text-muted-foreground transition hover:text-warning focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        :class="label ? 'px-2 py-1 text-xs' : 'p-1'"
        :title="title"
        :aria-label="title"
        @click="openFeedback(options)"
    >
        <MessageSquareWarning :class="label ? 'size-3.5' : 'size-4'" />
        <span v-if="label">{{ label }}</span>
    </button>
</template>
