<script setup lang="ts">
import { MessageSquareWarning } from '@lucide/vue';
import FeedbackDialog from '@/components/game/FeedbackDialog.vue';
import SiteFooter from '@/components/game/SiteFooter.vue';
import SiteHeader from '@/components/game/SiteHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { useFeedback } from '@/composables/useFeedback';

const { openFeedback } = useFeedback();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <a
            href="#main"
            class="sr-only z-50 rounded bg-primary px-3 py-2 text-primary-foreground focus:not-sr-only focus:fixed focus:top-2 focus:left-2"
        >
            Skip to content
        </a>
        <SiteHeader />
        <main id="main" class="flex-1">
            <slot />
        </main>
        <SiteFooter />

        <button
            type="button"
            class="fixed right-4 bottom-4 z-40 inline-flex items-center gap-2 rounded-full border bg-card/95 px-3 py-2 text-sm text-muted-foreground shadow-lg backdrop-blur transition hover:border-warning/50 hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            aria-label="Send feedback"
            title="Something off? Send feedback"
            @click="openFeedback()"
        >
            <MessageSquareWarning class="size-4 text-warning" />
            <span class="hidden sm:inline">Feedback</span>
        </button>

        <FeedbackDialog />
        <Toaster />
    </div>
</template>
