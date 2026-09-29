<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';

const model = defineModel<string>({ required: true });

const props = defineProps<{
    id: string;
    /** Colour shown in the picker while the text value is empty. */
    fallback?: string;
    placeholder?: string;
}>();

const valid = computed(() => /^#[0-9a-fA-F]{6}$/.test(model.value));
</script>

<template>
    <div class="flex items-center gap-2">
        <input
            type="color"
            :value="valid ? model : (props.fallback ?? '#2dd4bf')"
            class="h-8 w-9 shrink-0 cursor-pointer rounded border bg-transparent p-0.5"
            :aria-label="`Pick colour for ${props.id}`"
            @input="model = ($event.target as HTMLInputElement).value"
        />
        <Input
            :id="id"
            v-model="model"
            class="h-8 font-mono text-xs"
            maxlength="7"
            :placeholder="placeholder ?? '#rrggbb'"
        />
    </div>
</template>
