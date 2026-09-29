<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

withDefaults(defineProps<{ compact?: boolean }>(), { compact: false });

const name = usePage().props.name;
</script>

<template>
    <span class="flex items-center gap-2">
        <svg
            viewBox="0 0 32 32"
            class="size-7 shrink-0 text-anomaly"
            aria-hidden="true"
        >
            <circle
                cx="16"
                cy="16"
                r="12"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-dasharray="58 18"
            />
            <circle
                cx="16"
                cy="16"
                r="6"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                opacity="0.6"
            />
            <circle cx="16" cy="16" r="2" fill="currentColor" />
        </svg>
        <span
            v-if="!compact"
            class="font-display text-base leading-none font-semibold tracking-wide uppercase"
        >
            {{ name }}
        </span>
    </span>
</template>
