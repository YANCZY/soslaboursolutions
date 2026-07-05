<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell, ChevronsUpDown } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import type { AppNotification } from '@/types/auth';

const page = usePage();
const user = computed(() => page.props.auth.user);
const notifications = computed(
    () => (page.props.auth.notifications ?? []) as AppNotification[],
);
const unreadNotificationsCount = computed(
    () => page.props.auth.unread_notifications_count ?? 0,
);
const { isMobile, state } = useSidebar();
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger
                    type="button"
                    data-sidebar="menu-button"
                    data-slot="sidebar-menu-button"
                    data-size="lg"
                    data-test="sidebar-menu-button"
                    class="peer/menu-button flex h-12 w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm ring-sidebar-ring outline-hidden transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:p-0! [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0"
                >
                    <UserInfo :user="user" />

                    <span
                        class="relative inline-flex size-8 items-center justify-center rounded-md text-sidebar-foreground/70 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        aria-hidden="true"
                    >
                        <Bell class="size-4" />
                        <span
                            v-if="unreadNotificationsCount > 0"
                            class="absolute -top-1 -right-1 inline-flex min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold leading-none text-white"
                        >
                            {{ unreadNotificationsCount }}
                        </span>
                    </span>

                    <ChevronsUpDown class="ml-auto size-4" />
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-72 rounded-lg"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'right'
                              : 'top'
                    "
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent
                        :user="user"
                        :notifications="notifications"
                        :unread-count="unreadNotificationsCount"
                    />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
