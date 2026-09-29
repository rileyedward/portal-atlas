<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <section class="rounded-lg border bg-card p-4 md:p-5">
        <header class="mb-4">
            <h2 class="text-sm font-semibold tracking-wider uppercase">
                {{ title }}
            </h2>
            <p v-if="description" class="mt-0.5 text-xs text-muted-foreground">
                {{ description }}
            </p>
        </header>
        <div class="grid gap-4 sm:grid-cols-2">
            <slot />
        </div>
    </section>
</template>
