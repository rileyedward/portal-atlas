<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Copy, Globe, Lock, Map as MapIcon, Route, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { formatDate } from '@/components/public/options';
import { Button } from '@/components/ui/button';
import { http, HttpError } from '@/lib/http';
import { destroy } from '@/routes/api/routes';
import { show as showMap } from '@/routes/maps';
import { show as planner } from '@/routes/planner';

type SavedRoute = {
    id: number;
    name: string;
    description: string | null;
    stops: number;
    is_public: boolean;
    share_token: string | null;
    map: { slug: string; name: string };
    updated_at: string | null;
};

const props = defineProps<{ routes: SavedRoute[] }>();

const list = ref<SavedRoute[]>([...props.routes]);
watch(
    () => props.routes,
    (routes) => {
        list.value = [...routes];
    },
);
const deleting = ref<number | null>(null);

// Private routes aren't loadable via token; the map's route panel lists them for the owner.
function routeUrl(route: SavedRoute): string {
    return route.is_public && route.share_token
        ? showMap(route.map.slug, { query: { route: route.share_token } }).url
        : showMap(route.map.slug).url;
}

async function copyShareLink(route: SavedRoute): Promise<void> {
    try {
        await navigator.clipboard.writeText(
            new URL(routeUrl(route), window.location.origin).toString(),
        );
        toast.success('Share link copied');
    } catch {
        toast.error('Could not copy the link.');
    }
}

async function remove(route: SavedRoute): Promise<void> {
    if (
        !window.confirm(
            `Delete the route “${route.name}”? This can’t be undone.`,
        )
    ) {
        return;
    }

    deleting.value = route.id;

    try {
        await http('delete', destroy(route.id).url);
        list.value = list.value.filter((r) => r.id !== route.id);
        toast.success('Route deleted');
    } catch (e) {
        toast.error(
            e instanceof HttpError
                ? e.firstError()
                : 'Could not delete the route.',
        );
    } finally {
        deleting.value = null;
    }
}
</script>

<template>
    <Head title="My routes">
        <meta
            name="description"
            content="Your saved Active Matter raid routes."
        />
    </Head>

    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-6">
            <h1
                class="font-display text-3xl font-semibold tracking-wide uppercase"
            >
                My routes
            </h1>
            <p class="mt-1 max-w-2xl text-muted-foreground">
                Routes you drew on the maps. Public routes can be shared with a
                link.
            </p>
        </header>

        <ul v-if="list.length" class="grid gap-3 md:grid-cols-2">
            <li
                v-for="route in list"
                :key="route.id"
                class="flex flex-col rounded-lg border bg-card p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2
                            class="truncate text-lg font-semibold tracking-wide uppercase"
                        >
                            {{ route.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ route.map.name }} · {{ route.stops }} stop{{
                                route.stops === 1 ? '' : 's'
                            }}
                            <template v-if="route.updated_at">
                                · updated
                                {{ formatDate(route.updated_at) }}</template
                            >
                        </p>
                    </div>
                    <span
                        class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 text-xs"
                        :class="
                            route.is_public
                                ? 'border-primary/40 text-primary'
                                : 'text-muted-foreground'
                        "
                    >
                        <component
                            :is="route.is_public ? Globe : Lock"
                            class="size-3"
                        />
                        {{ route.is_public ? 'Public' : 'Private' }}
                    </span>
                </div>
                <p v-if="route.description" class="mt-2 line-clamp-3 text-sm">
                    {{ route.description }}
                </p>
                <div class="mt-auto flex flex-wrap gap-2 pt-4">
                    <Button as-child variant="secondary" size="sm">
                        <Link :href="routeUrl(route)"
                            ><MapIcon class="size-4" /> Open</Link
                        >
                    </Button>
                    <Button
                        v-if="route.is_public && route.share_token"
                        variant="secondary"
                        size="sm"
                        @click="copyShareLink(route)"
                    >
                        <Copy class="size-4" /> Copy share link
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="ml-auto text-destructive"
                        :disabled="deleting === route.id"
                        :aria-label="`Delete route ${route.name}`"
                        @click="remove(route)"
                    >
                        <Trash2 class="size-4" /> Delete
                    </Button>
                </div>
            </li>
        </ul>

        <div v-else class="rounded-lg border border-dashed p-8 text-center">
            <Route class="mx-auto mb-2 size-8 text-muted-foreground" />
            <p class="font-medium">No saved routes yet.</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                Open a map and use the route tool to draw and save one, or let
                the raid planner suggest an order for you.
            </p>
            <Button as-child variant="secondary" class="mt-4">
                <Link :href="planner()">Open the raid planner</Link>
            </Button>
        </div>
    </div>
</template>
