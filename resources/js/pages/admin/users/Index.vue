<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import EmptyState from '@/components/admin/EmptyState.vue';
import NativeSelect from '@/components/admin/NativeSelect.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import {
    formatDate,
    rowClass,
    tableClass,
    theadClass,
} from '@/components/admin/types';
import type { Paginated } from '@/components/admin/types';
import { Input } from '@/components/ui/input';
import { index, update } from '@/routes/admin/users';
import type { Option } from '@/types/game';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string | null;
};

const props = defineProps<{
    users: Paginated<UserRow>;
    roles: Option[];
    filters: { search: string };
}>();

const page = usePage();
const currentUserId = computed(() => page.props.auth.user?.id ?? null);
const saving = ref<number | null>(null);
const resetKey = ref(0);

const search = ref(props.filters.search);
let timer: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(index.url(), value ? { search: value } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
});

onBeforeUnmount(() => clearTimeout(timer));

function changeRole(
    user: UserRow,
    role: string | number | null | undefined,
): void {
    if (typeof role !== 'string' || role === user.role) {
        return;
    }

    const label = props.roles.find((r) => r.value === role)?.label ?? role;

    if (!window.confirm(`Change ${user.name}'s role to ${label}?`)) {
        // Re-mount the select so it snaps back to the saved role.
        resetKey.value++;

        return;
    }

    router.patch(
        update(user.id).url,
        { role },
        {
            preserveScroll: true,
            onStart: () => (saving.value = user.id),
            onFinish: () => (saving.value = null),
        },
    );
}
</script>

<template>
    <Head title="Users & roles" />

    <PageHeader
        title="Users & roles"
        description="Editors can manage content; admins can also manage users. You can't change your own role."
    />

    <div class="relative mb-4 max-w-sm">
        <label for="user-search" class="sr-only">Search users</label>
        <Search
            class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            id="user-search"
            v-model="search"
            type="search"
            placeholder="Search name or email…"
            class="pl-8"
        />
    </div>

    <template v-if="users.data.length">
        <div
            class="max-h-[calc(100svh-15rem)] overflow-auto rounded-lg border bg-card"
        >
            <table :class="tableClass">
                <thead :class="theadClass">
                    <tr>
                        <th scope="col" class="px-3 py-2">Name</th>
                        <th scope="col" class="px-3 py-2">Email</th>
                        <th scope="col" class="px-3 py-2">Joined</th>
                        <th scope="col" class="px-3 py-2">Role</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        :class="rowClass"
                    >
                        <td class="px-3 py-2 font-medium">
                            {{ user.name }}
                            <span
                                v-if="user.id === currentUserId"
                                class="ml-1 text-xs text-muted-foreground"
                                >(you)</span
                            >
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ user.email }}
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ formatDate(user.created_at) }}
                        </td>
                        <td class="w-40 px-3 py-1.5">
                            <label :for="`role-${user.id}`" class="sr-only"
                                >Role for {{ user.name }}</label
                            >
                            <NativeSelect
                                :id="`role-${user.id}`"
                                :key="`${user.id}-${resetKey}`"
                                :model-value="user.role"
                                :options="roles"
                                :disabled="
                                    user.id === currentUserId ||
                                    saving === user.id
                                "
                                class="h-8"
                                @update:model-value="changeRole(user, $event)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :paginator="users" />
    </template>
    <EmptyState
        v-else
        :title="filters.search ? 'No matching users' : 'No users yet'"
        :description="
            filters.search ? `Nobody matches “${filters.search}”.` : undefined
        "
    />
</template>
