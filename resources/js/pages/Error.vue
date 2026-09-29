<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useFeedback } from '@/composables/useFeedback';
import { home } from '@/routes';

const props = defineProps<{ status: number }>();
const { openFeedback } = useFeedback();

const copy = computed(
    () =>
        ({
            403: {
                title: 'No access',
                text: "You don't have permission to see this page.",
            },
            404: {
                title: 'Lost in the anomaly',
                text: "This page doesn't exist, or it was moved.",
            },
            429: {
                title: 'Slow down',
                text: 'Too many requests. Wait a moment and try again.',
            },
            500: {
                title: 'Something broke',
                text: "An unexpected error happened on our side. It's been logged.",
            },
            503: {
                title: 'Back soon',
                text: "We're doing some maintenance. Please check back in a few minutes.",
            },
        })[props.status] ?? {
            title: 'Something went wrong',
            text: 'Please try again.',
        },
);
</script>

<template>
    <Head :title="copy.title" />
    <section
        class="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center"
    >
        <p class="font-display text-6xl font-semibold text-anomaly">
            {{ status }}
        </p>
        <h1 class="mt-4 text-2xl font-semibold tracking-wide uppercase">
            {{ copy.title }}
        </h1>
        <p class="mt-2 text-muted-foreground">{{ copy.text }}</p>
        <div class="mt-8 flex gap-3">
            <Button as-child
                ><Link :href="home()">Back to the maps</Link></Button
            >
            <Button
                v-if="status >= 500"
                variant="secondary"
                @click="
                    openFeedback({
                        type: 'bug',
                        context: `Error ${status}`,
                    })
                "
            >
                Report this
            </Button>
        </div>
    </section>
</template>
