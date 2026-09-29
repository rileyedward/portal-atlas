<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <slot name="eyebrow" />
            <h1
                class="font-display text-2xl font-semibold tracking-wide uppercase"
            >
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="mt-1 max-w-2xl text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
