<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    BookMarked,
    Crosshair,
    Flag,
    GitBranch,
    Hammer,
    LayoutDashboard,
    Map as MapIcon,
    Menu,
    Package,
    Shapes,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import BrandMark from '@/components/game/BrandMark.vue';
import { Toaster } from '@/components/ui/sonner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { home } from '@/routes';
import { dashboard } from '@/routes/admin';
import { index as data } from '@/routes/admin/data';
import { index as items } from '@/routes/admin/items';
import { index as maps } from '@/routes/admin/maps';
import { index as objectives } from '@/routes/admin/objectives';
import { index as recipes } from '@/routes/admin/recipes';
import { index as reports } from '@/routes/admin/reports';
import { index as sources } from '@/routes/admin/sources';
import { index as taxonomy } from '@/routes/admin/taxonomy';
import { index as users } from '@/routes/admin/users';
import { index as versions } from '@/routes/admin/versions';

const page = usePage();
const can = computed(() => page.props.auth.can);
const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const mobileOpen = ref(false);

const sections = computed(() => [
    {
        title: 'Content',
        items: [
            {
                title: 'Dashboard',
                href: dashboard().url,
                icon: LayoutDashboard,
                exact: true,
            },
            { title: 'Maps & editor', href: maps().url, icon: MapIcon },
            { title: 'Items', href: items().url, icon: Package },
            { title: 'Objectives', href: objectives().url, icon: Crosshair },
            { title: 'Recipes & upgrades', href: recipes().url, icon: Hammer },
        ],
    },
    {
        title: 'Data quality',
        items: [
            {
                title: 'Feedback',
                href: reports().url,
                icon: Flag,
                badge: page.props.openFeedbackCount ?? 0,
            },
            { title: 'Sources', href: sources().url, icon: BookMarked },
            { title: 'Game versions', href: versions().url, icon: GitBranch },
            { title: 'Marker types', href: taxonomy().url, icon: Shapes },
            {
                title: 'Import / export',
                href: data().url,
                icon: ArrowLeftRight,
            },
        ],
    },
    ...(can.value.administer
        ? [
              {
                  title: 'Admin',
                  items: [
                      {
                          title: 'Users & roles',
                          href: users().url,
                          icon: Users,
                      },
                  ],
              },
          ]
        : []),
]);

function active(href: string, exact = false): boolean {
    return exact ? isCurrentUrl(href) : isCurrentOrParentUrl(href);
}
</script>

<template>
    <div class="flex min-h-svh bg-background">
        <aside
            class="fixed inset-y-0 left-0 z-40 w-60 -translate-x-full border-r bg-sidebar transition-transform md:static md:translate-x-0"
            :class="{ 'translate-x-0': mobileOpen }"
            aria-label="Admin navigation"
        >
            <div class="flex h-14 items-center justify-between border-b px-4">
                <Link :href="home()"><BrandMark /></Link>
                <button
                    type="button"
                    class="md:hidden"
                    aria-label="Close menu"
                    @click="mobileOpen = false"
                >
                    <X class="size-4" />
                </button>
            </div>
            <nav class="space-y-5 p-3">
                <div v-for="section in sections" :key="section.title">
                    <h2
                        class="mb-1 px-2 font-sans text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        {{ section.title }}
                    </h2>
                    <Link
                        v-for="item in section.items"
                        :key="item.href"
                        :href="item.href"
                        class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm transition"
                        :class="
                            active(item.href, 'exact' in item && item.exact)
                                ? 'bg-sidebar-accent font-medium text-foreground'
                                : 'text-muted-foreground hover:bg-sidebar-accent/60 hover:text-foreground'
                        "
                        @click="mobileOpen = false"
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.title }}
                        <span
                            v-if="'badge' in item && item.badge"
                            class="ml-auto rounded-full bg-warning/20 px-1.5 text-[11px] font-semibold text-warning tabular-nums"
                            :aria-label="`${item.badge} open`"
                            >{{ item.badge }}</span
                        >
                    </Link>
                </div>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="flex h-14 items-center gap-3 border-b px-4 md:hidden"
            >
                <button
                    type="button"
                    aria-label="Open menu"
                    @click="mobileOpen = true"
                >
                    <Menu class="size-5" />
                </button>
                <span class="font-display font-semibold tracking-wide uppercase"
                    >Admin</span
                >
            </header>
            <main class="mx-auto w-full max-w-6xl flex-1 p-4 md:p-8">
                <slot />
            </main>
        </div>
        <Toaster />
    </div>
</template>
