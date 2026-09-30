<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Link } from '@inertiajs/vue3';
import { index as items } from '@/routes/items';

const confidenceFactors = [
    {
        title: 'Starting reliability',
        text: 'Well-established information starts with a higher score than a single unconfirmed tip.',
    },
    {
        title: 'Community confirmations',
        text: 'Signed-in players can confirm that an entry is still accurate. Each confirmation raises the score a little, up to a cap.',
    },
    {
        title: 'Editor verification',
        text: 'An editor verifying an entry in game gives it a strong boost.',
    },
    {
        title: 'Open reports',
        text: 'Unresolved reports of incorrect information lower the score until a moderator reviews them.',
    },
    {
        title: 'Staleness by version',
        text: 'Entries never verified, verified on an older game version, or not re-checked in about six months lose confidence, because patches move things.',
    },
];
</script>

<template>
    <SeoHead />

    <div class="mx-auto max-w-3xl px-4 py-8 md:py-12">
        <h1
            class="font-display text-3xl font-semibold tracking-wide uppercase md:text-4xl"
        >
            About
        </h1>

        <div class="mt-6 space-y-10 leading-relaxed">
            <section aria-labelledby="what-title" class="space-y-3">
                <h2
                    id="what-title"
                    class="font-display text-xl font-semibold tracking-wide uppercase"
                >
                    What this is
                </h2>
                <p>
                    A community-made interactive map and raid companion for
                    <em>Active Matter</em>: maps with extraction points, loot,
                    objectives and threats, an item database that answers “where
                    do I find this?” and “should I keep it?”, and a raid
                    planner.
                </p>
                <p
                    class="rounded-lg border border-warning/40 bg-warning/10 p-4 text-sm"
                >
                    This is an <strong>unofficial fan project</strong>. It is
                    not affiliated with, endorsed by, or sponsored by Gaijin
                    Entertainment or Matter Team. <em>Active Matter</em> and
                    related names are trademarks of their respective owners.
                </p>
            </section>

            <section aria-labelledby="policy-title" class="space-y-3">
                <h2
                    id="policy-title"
                    class="font-display text-xl font-semibold tracking-wide uppercase"
                >
                    Data policy
                </h2>
                <ul class="list-disc space-y-2 pl-5">
                    <li>
                        <strong>Every entry has a confidence score</strong> and
                        the game version it was last verified for. You can see
                        both on each item, objective and map marker.
                    </li>
                    <li>
                        <strong>Nothing is guessed.</strong> If a value,
                        location or use isn’t documented, it is shown as
                        “Unknown” — not filled in with an estimate.
                    </li>
                    <li>
                        <strong>Community reporting.</strong> Anyone can report
                        incorrect or outdated information from any entry.
                        Reports are reviewed by moderators.
                    </li>
                </ul>
            </section>

            <section aria-labelledby="confidence-title" class="space-y-3">
                <h2
                    id="confidence-title"
                    class="font-display text-xl font-semibold tracking-wide uppercase"
                >
                    How confidence works
                </h2>
                <p>
                    Each entry has a score from 0 to 100% that tells you how
                    much to trust it. It is calculated from:
                </p>
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="factor in confidenceFactors"
                        :key="factor.title"
                        class="rounded-lg border bg-card p-4"
                    >
                        <dt class="font-medium">{{ factor.title }}</dt>
                        <dd class="mt-1 text-sm text-muted-foreground">
                            {{ factor.text }}
                        </dd>
                    </div>
                </dl>
                <p class="text-sm text-muted-foreground">
                    A low score doesn’t mean an entry is wrong — only that it
                    hasn’t been confirmed much yet. Check the verified version
                    before relying on it.
                </p>
            </section>

            <section aria-labelledby="contact-title" class="space-y-3">
                <h2
                    id="contact-title"
                    class="font-display text-xl font-semibold tracking-wide uppercase"
                >
                    Contact &amp; takedowns
                </h2>
                <p>
                    Found a mistake? Use the feedback icon next to any entry, or
                    the Feedback button in the corner of every page. For
                    anything else — including rights holders requesting removal
                    of content — please open an issue or contact the
                    maintainers, and we’ll respond promptly.
                </p>
            </section>

            <p>
                <Link :href="items()" class="text-primary hover:underline"
                    >Browse the item database →</Link
                >
            </p>
        </div>
    </div>
</template>
