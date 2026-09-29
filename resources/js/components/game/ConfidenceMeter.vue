<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{ score: number; label?: string; compact?: boolean }>(),
    { label: undefined, compact: false },
);

const tone = computed(() => {
    if (props.score >= 80) {
        return 'bg-success';
    }

    if (props.score >= 55) {
        return 'bg-anomaly';
    }

    if (props.score >= 30) {
        return 'bg-warning';
    }

    return 'bg-destructive';
});

const text = computed(
    () =>
        props.label ??
        (props.score >= 80
            ? 'High'
            : props.score >= 55
              ? 'Medium'
              : props.score >= 30
                ? 'Low'
                : 'Unverified'),
);
</script>

<template>
    <div
        class="flex items-center gap-2"
        role="meter"
        :aria-valuenow="score"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-label="`Confidence ${score}% (${text})`"
    >
        <div
            class="h-1.5 overflow-hidden rounded-full bg-muted"
            :class="compact ? 'w-12' : 'w-full max-w-40'"
        >
            <div
                class="h-full rounded-full transition-all"
                :class="tone"
                :style="{ width: `${Math.max(4, score)}%` }"
            />
        </div>
        <span class="text-xs whitespace-nowrap text-muted-foreground">
            <template v-if="!compact">{{ score }}% · </template>{{ text }}
        </span>
    </div>
</template>
