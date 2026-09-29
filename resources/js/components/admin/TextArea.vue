<script setup lang="ts">
import { cn } from '@/lib/utils';

const model = defineModel<string | null>();

const props = defineProps<{ class?: string; rows?: number }>();
</script>

<template>
    <textarea
        :value="model ?? ''"
        :rows="props.rows ?? 3"
        :class="
            cn(
                'w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30',
                props.class,
            )
        "
        @input="model = ($event.target as HTMLTextAreaElement).value"
    />
</template>
