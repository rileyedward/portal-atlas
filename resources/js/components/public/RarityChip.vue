<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ rarity: string | null }>();

const tones: Record<string, string> = {
    common: 'border-zinc-500/40 bg-zinc-500/15 text-zinc-300',
    uncommon: 'border-green-500/40 bg-green-500/15 text-green-400',
    rare: 'border-blue-500/40 bg-blue-500/15 text-blue-400',
    epic: 'border-purple-500/40 bg-purple-500/15 text-purple-400',
};

const tone = computed(
    () =>
        (props.rarity && tones[props.rarity.toLowerCase()]) ||
        'border-border text-muted-foreground',
);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap capitalize"
        :class="tone"
    >
        {{ rarity ?? 'Unknown' }}
    </span>
</template>
