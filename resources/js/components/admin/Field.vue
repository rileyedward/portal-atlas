<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

defineProps<{
    id: string;
    label: string;
    error?: string;
    hint?: string;
    required?: boolean;
    class?: string;
}>();
</script>

<template>
    <div class="grid content-start gap-1.5" :class="$props.class">
        <Label :for="id">
            {{ label }}
            <span v-if="required" class="text-destructive" aria-hidden="true"
                >*</span
            >
        </Label>
        <slot />
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <InputError :message="error" />
    </div>
</template>
