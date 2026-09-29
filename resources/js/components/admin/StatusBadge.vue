<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ status: string; label?: string }>();

const tone = computed(() => {
    switch (props.status) {
        case 'published':
        case 'accepted':
            return 'border-success/40 bg-success/10 text-success';
        case 'draft':
        case 'open':
            return 'border-warning/40 bg-warning/10 text-warning';
        case 'hidden':
        case 'archived':
            return 'border-border bg-muted text-muted-foreground';
        case 'removed':
        case 'rejected':
            return 'border-destructive/40 bg-destructive/10 text-destructive';
        default:
            return 'border-border text-muted-foreground';
    }
});

const text = computed(
    () =>
        props.label ??
        props.status.charAt(0).toUpperCase() + props.status.slice(1),
);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium whitespace-nowrap"
        :class="tone"
    >
        {{ text }}
    </span>
</template>
