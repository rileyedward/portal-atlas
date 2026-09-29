<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ExternalLink,
    ImageOff,
    Pencil,
    Plus,
    Trash2,
    Waypoints,
} from '@lucide/vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { rowClass, tableClass, theadClass } from '@/components/admin/types';
import { Button } from '@/components/ui/button';
import { create, destroy, edit, editor } from '@/routes/admin/maps';
import { show as publicMap } from '@/routes/maps';

type MapRow = {
    id: number;
    slug: string;
    name: string;
    status: string;
    markers_count: number;
    has_image: boolean;
};

defineProps<{ maps: MapRow[] }>();

function remove(map: MapRow): void {
    if (map.markers_count > 0) {
        window.alert(
            'This map still has markers. Archive it instead of deleting it.',
        );

        return;
    }

    if (
        window.confirm(`Delete the map "${map.name}"? This cannot be undone.`)
    ) {
        router.delete(destroy(map.slug).url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Maps" />

    <PageHeader
        title="Maps"
        description="Map metadata, base images and publication status. Place markers in the editor."
    >
        <template #actions>
            <Button as-child>
                <Link :href="create()"><Plus /> New map</Link>
            </Button>
        </template>
    </PageHeader>

    <div
        v-if="maps.length"
        class="max-h-[calc(100svh-12rem)] overflow-auto rounded-lg border bg-card"
    >
        <table :class="tableClass">
            <thead :class="theadClass">
                <tr>
                    <th scope="col" class="px-3 py-2">Name</th>
                    <th scope="col" class="px-3 py-2">Status</th>
                    <th scope="col" class="px-3 py-2 text-right">Markers</th>
                    <th scope="col" class="px-3 py-2">Image</th>
                    <th scope="col" class="px-3 py-2">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="map in maps" :key="map.id" :class="rowClass">
                    <td class="px-3 py-2">
                        <Link
                            :href="edit(map.slug)"
                            class="font-medium hover:text-primary"
                            >{{ map.name }}</Link
                        >
                        <span class="block text-xs text-muted-foreground">{{
                            map.slug
                        }}</span>
                    </td>
                    <td class="px-3 py-2">
                        <StatusBadge :status="map.status" />
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums">
                        {{ map.markers_count }}
                    </td>
                    <td class="px-3 py-2">
                        <span v-if="map.has_image" class="text-xs text-success"
                            >Uploaded</span
                        >
                        <span
                            v-else
                            class="flex items-center gap-1 text-xs text-warning"
                        >
                            <ImageOff class="size-3.5" /> Missing
                        </span>
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex justify-end gap-1">
                            <Button as-child size="sm" variant="secondary">
                                <Link :href="editor(map.slug)"
                                    ><Waypoints /> Editor</Link
                                >
                            </Button>
                            <Button as-child size="icon-sm" variant="ghost">
                                <Link
                                    :href="edit(map.slug)"
                                    :aria-label="`Edit ${map.name}`"
                                    ><Pencil
                                /></Link>
                            </Button>
                            <Button as-child size="icon-sm" variant="ghost">
                                <a
                                    :href="publicMap(map.slug).url"
                                    target="_blank"
                                    rel="noopener"
                                    :aria-label="`View ${map.name} publicly`"
                                >
                                    <ExternalLink />
                                </a>
                            </Button>
                            <Button
                                size="icon-sm"
                                variant="ghost"
                                class="text-destructive hover:text-destructive"
                                :aria-label="`Delete ${map.name}`"
                                @click="remove(map)"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <EmptyState
        v-else
        title="No maps yet"
        description="Create a map, upload its base image, then add markers in the editor."
    >
        <Button as-child>
            <Link :href="create()"><Plus /> New map</Link>
        </Button>
    </EmptyState>
</template>
