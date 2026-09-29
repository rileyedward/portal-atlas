<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useFeedback } from '@/composables/useFeedback';
import { about } from '@/routes';

const name = usePage().props.name;
const { openFeedback } = useFeedback();
</script>

<template>
    <footer class="border-t">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
        >
            <p>
                {{ name }} is an unofficial, free, community-made fan project.
                It is not produced, approved or endorsed by Gaijin Entertainment
                or Matter Team. Active Matter is a trademark of its respective
                owners.
            </p>
            <span class="flex shrink-0 gap-4">
                <button
                    type="button"
                    class="hover:text-foreground"
                    @click="openFeedback()"
                >
                    Send feedback
                </button>
                <Link :href="about()" class="hover:text-foreground">
                    About &amp; data policy
                </Link>
            </span>
        </div>
    </footer>
</template>
