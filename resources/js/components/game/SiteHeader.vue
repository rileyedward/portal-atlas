<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { computed } from 'vue';
import BrandMark from '@/components/game/BrandMark.vue';
import GlobalSearch from '@/components/game/GlobalSearch.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { home, login, register } from '@/routes';
import { index as items } from '@/routes/items';
import { index as objectives } from '@/routes/objectives';
import { show as planner } from '@/routes/planner';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { isCurrentOrParentUrl } = useCurrentUrl();

const nav = [
    { title: 'Maps', href: home().url },
    { title: 'Items', href: items().url },
    { title: 'Objectives', href: objectives().url },
    { title: 'Raid planner', href: planner().url },
];
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b bg-background/85 backdrop-blur supports-[backdrop-filter]:bg-background/70"
    >
        <div class="mx-auto flex h-14 max-w-7xl items-center gap-4 px-4">
            <Sheet>
                <SheetTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="md:hidden"
                        aria-label="Open navigation"
                    >
                        <Menu class="size-5" />
                    </Button>
                </SheetTrigger>
                <SheetContent side="left" class="w-72">
                    <SheetHeader>
                        <SheetTitle><BrandMark /></SheetTitle>
                    </SheetHeader>
                    <nav class="grid gap-1 px-4">
                        <Link
                            v-for="item in nav"
                            :key="item.href"
                            :href="item.href"
                            class="rounded-md px-3 py-2 text-sm hover:bg-accent"
                        >
                            {{ item.title }}
                        </Link>
                    </nav>
                </SheetContent>
            </Sheet>

            <Link :href="home()" class="shrink-0" aria-label="Home">
                <BrandMark />
            </Link>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Main">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="rounded-md px-3 py-1.5 text-sm text-muted-foreground transition hover:text-foreground"
                    :class="{
                        'bg-accent text-foreground':
                            item.href !== '/' &&
                            isCurrentOrParentUrl(item.href),
                    }"
                >
                    {{ item.title }}
                </Link>
            </nav>

            <div class="ml-auto flex flex-1 items-center justify-end gap-2">
                <div class="hidden w-full max-w-xs sm:block">
                    <GlobalSearch />
                </div>
                <div class="sm:hidden">
                    <GlobalSearch compact />
                </div>

                <DropdownMenu v-if="user">
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="rounded-full"
                            aria-label="Account menu"
                        >
                            <span
                                class="grid size-8 place-items-center rounded-full bg-accent text-xs font-semibold"
                            >
                                {{ getInitials(user.name) }}
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="user" />
                    </DropdownMenuContent>
                </DropdownMenu>
                <template v-else>
                    <Link
                        :href="login()"
                        class="hidden rounded-md px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground sm:block"
                    >
                        Log in
                    </Link>
                    <Button as-child size="sm">
                        <Link :href="register()">Sign up</Link>
                    </Button>
                </template>
            </div>
        </div>
    </header>
</template>
