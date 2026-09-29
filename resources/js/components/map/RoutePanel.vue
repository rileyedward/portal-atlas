<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    Copy,
    Eye,
    Pencil,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { http, HttpError } from '@/lib/http';
import { destroy, store, update } from '@/routes/api/routes';
import type { RaidRoute, RoutePoint } from '@/types/game';

const props = defineProps<{
    mapId: number;
    routes: RaidRoute[];
    drawing: boolean;
    points: RoutePoint[];
}>();

const emit = defineEmits<{
    'update:points': [points: RoutePoint[]];
    'update:drawing': [drawing: boolean];
    'update:routes': [routes: RaidRoute[]];
    show: [route: RaidRoute | null];
}>();

const editingId = ref<number | null>(null);
const name = ref('');
const description = ref('');
const isPublic = ref(false);
const error = ref<string | null>(null);
const saving = ref(false);

function start(route?: RaidRoute): void {
    editingId.value = route?.id ?? null;
    name.value = route?.name ?? '';
    description.value = route?.description ?? '';
    isPublic.value = route?.is_public ?? false;
    error.value = null;
    emit('update:points', route ? [...route.points] : []);
    emit('update:drawing', true);
}

function cancel(): void {
    emit('update:drawing', false);
    emit('update:points', []);
    editingId.value = null;
}

function move(index: number, delta: number): void {
    const next = [...props.points];
    const [point] = next.splice(index, 1);
    next.splice(index + delta, 0, point);
    emit('update:points', next);
}

function remove(index: number): void {
    emit(
        'update:points',
        props.points.filter((_, i) => i !== index),
    );
}

function relabel(index: number, label: string): void {
    emit(
        'update:points',
        props.points.map((p, i) => (i === index ? { ...p, label } : p)),
    );
}

async function save(): Promise<void> {
    saving.value = true;
    error.value = null;
    const payload = {
        name: name.value,
        description: description.value || null,
        is_public: isPublic.value,
        points: props.points,
    };

    try {
        if (editingId.value) {
            const saved = await http<RaidRoute>(
                'patch',
                update(editingId.value).url,
                payload,
            );
            emit(
                'update:routes',
                props.routes.map((r) => (r.id === saved.id ? saved : r)),
            );
        } else {
            const saved = await http<RaidRoute>('post', store().url, {
                ...payload,
                map_id: props.mapId,
            });
            emit('update:routes', [saved, ...props.routes]);
        }

        toast.success('Route saved');
        emit('update:drawing', false);
    } catch (e) {
        error.value =
            e instanceof HttpError ? e.firstError() : 'Could not save route.';
    } finally {
        saving.value = false;
    }
}

async function removeRoute(route: RaidRoute): Promise<void> {
    if (!confirm(`Delete route “${route.name}”?`)) {
        return;
    }

    await http('delete', destroy(route.id).url);
    emit(
        'update:routes',
        props.routes.filter((r) => r.id !== route.id),
    );
    emit('show', null);
}

function share(route: RaidRoute): void {
    if (!route.is_public || !route.share_token) {
        toast.info('Make the route public to share it.');

        return;
    }

    const url = new URL(window.location.href);
    url.search = '';
    url.searchParams.set('route', route.share_token);
    void navigator.clipboard.writeText(url.toString());
    toast.success('Share link copied');
}
</script>

<template>
    <div class="space-y-3">
        <template v-if="drawing">
            <p class="text-xs text-muted-foreground">
                Click the map to add stops. Clicking near a marker snaps to it.
            </p>
            <Input v-model="name" placeholder="Route name" maxlength="120" />
            <textarea
                v-model="description"
                rows="2"
                maxlength="2000"
                placeholder="Description (optional)"
                class="w-full rounded-md border bg-transparent px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            />
            <ol class="space-y-1">
                <li
                    v-for="(point, index) in points"
                    :key="index"
                    class="flex items-center gap-1.5"
                >
                    <span
                        class="grid size-5 shrink-0 place-items-center rounded-full bg-warning text-[10px] font-bold text-background"
                        >{{ index + 1 }}</span
                    >
                    <input
                        :value="point.label ?? ''"
                        class="h-7 min-w-0 flex-1 rounded border bg-transparent px-2 text-xs"
                        :placeholder="`Stop ${index + 1}`"
                        :aria-label="`Label for stop ${index + 1}`"
                        @input="
                            relabel(
                                index,
                                ($event.target as HTMLInputElement).value,
                            )
                        "
                    />
                    <button
                        type="button"
                        class="p-1 text-muted-foreground hover:text-foreground disabled:opacity-30"
                        :disabled="index === 0"
                        aria-label="Move up"
                        @click="move(index, -1)"
                    >
                        <ArrowUp class="size-3.5" />
                    </button>
                    <button
                        type="button"
                        class="p-1 text-muted-foreground hover:text-foreground disabled:opacity-30"
                        :disabled="index === points.length - 1"
                        aria-label="Move down"
                        @click="move(index, 1)"
                    >
                        <ArrowDown class="size-3.5" />
                    </button>
                    <button
                        type="button"
                        class="p-1 text-muted-foreground hover:text-destructive"
                        aria-label="Remove stop"
                        @click="remove(index)"
                    >
                        <X class="size-3.5" />
                    </button>
                </li>
            </ol>
            <label class="flex items-center gap-2 text-xs">
                <input v-model="isPublic" type="checkbox" /> Public (shareable
                link)
            </label>
            <InputError :message="error ?? undefined" />
            <div class="flex gap-2">
                <Button
                    size="sm"
                    class="flex-1"
                    :disabled="saving || points.length < 2 || !name"
                    @click="save"
                >
                    Save route
                </Button>
                <Button size="sm" variant="ghost" @click="cancel"
                    >Cancel</Button
                >
            </div>
        </template>

        <template v-else>
            <Button
                size="sm"
                variant="secondary"
                class="w-full"
                @click="start()"
            >
                <Plus class="size-4" /> New route
            </Button>
            <p v-if="!routes.length" class="text-xs text-muted-foreground">
                No saved routes for this map yet.
            </p>
            <ul class="space-y-1">
                <li
                    v-for="route in routes"
                    :key="route.id"
                    class="group flex items-center gap-1 rounded-md px-2 py-1.5 hover:bg-accent/50"
                >
                    <button
                        type="button"
                        class="min-w-0 flex-1 truncate text-left text-sm"
                        @click="emit('show', route)"
                    >
                        {{ route.name }}
                        <span class="text-xs text-muted-foreground"
                            >· {{ route.points.length }} stops</span
                        >
                    </button>
                    <button
                        type="button"
                        class="p-1 text-muted-foreground hover:text-foreground"
                        aria-label="Show route"
                        @click="emit('show', route)"
                    >
                        <Eye class="size-3.5" />
                    </button>
                    <button
                        type="button"
                        class="p-1 text-muted-foreground hover:text-foreground"
                        aria-label="Edit route"
                        @click="start(route)"
                    >
                        <Pencil class="size-3.5" />
                    </button>
                    <button
                        type="button"
                        class="p-1 text-muted-foreground hover:text-foreground"
                        aria-label="Copy share link"
                        @click="share(route)"
                    >
                        <Copy class="size-3.5" />
                    </button>
                    <button
                        type="button"
                        class="p-1 text-muted-foreground hover:text-destructive"
                        aria-label="Delete route"
                        @click="removeRoute(route)"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </li>
            </ul>
        </template>
    </div>
</template>
