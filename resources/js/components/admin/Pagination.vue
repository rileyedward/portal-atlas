<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Paginated } from './types';

const props = defineProps<{ paginator: Paginated<unknown> }>();

function label(raw: string): string {
    if (raw.includes('Previous') || raw.includes('&laquo;')) {
        return 'Previous';
    }

    if (raw.includes('Next') || raw.includes('&raquo;')) {
        return 'Next';
    }

    return raw;
}

const links = computed(() =>
    props.paginator.links.map((link) => ({ ...link, text: label(link.label) })),
);
</script>

<template>
    <nav
        v-if="paginator.last_page > 1 || paginator.total > 0"
        class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm"
        aria-label="Pagination"
    >
        <p class="text-muted-foreground">
            <template v-if="paginator.total > 0">
                Showing {{ paginator.from }}–{{ paginator.to }} of
                {{ paginator.total }}
            </template>
        </p>
        <ul
            v-if="paginator.last_page > 1"
            class="flex flex-wrap items-center gap-1"
        >
            <li v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url && !link.active"
                    :href="link.url"
                    preserve-scroll
                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 transition hover:border-primary/50 hover:bg-accent/40"
                >
                    {{ link.text }}
                </Link>
                <span
                    v-else
                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2"
                    :class="
                        link.active
                            ? 'border-primary/60 bg-primary/15 font-semibold text-primary'
                            : 'opacity-40'
                    "
                    :aria-current="link.active ? 'page' : undefined"
                >
                    {{ link.text }}
                </span>
            </li>
        </ul>
    </nav>
</template>
