<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div class="rounded-lg border border-dashed p-10 text-center">
        <p class="font-medium">{{ title }}</p>
        <p v-if="description" class="mt-1 text-sm text-muted-foreground">
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-4 flex justify-center gap-2">
            <slot />
        </div>
    </div>
</template>
