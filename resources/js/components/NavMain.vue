<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    peopleItems?: NavItem[];
    workspaceItems?: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <div class="flex items-center gap-3 px-2">
            <div class="h-px w-4 bg-sidebar-border/70" />
            <SidebarGroupLabel class="h-auto shrink-0 px-0">
                Home
            </SidebarGroupLabel>
            <div class="h-px flex-1 bg-sidebar-border/70" />
        </div>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
        <div class="mt-4 flex items-center gap-3 px-2">
            <div class="h-px w-4 bg-sidebar-border/70" />
            <SidebarGroupLabel class="h-auto shrink-0 px-0">
                People
            </SidebarGroupLabel>
            <div class="h-px flex-1 bg-sidebar-border/70" />
        </div>
        <SidebarMenu>
            <SidebarMenuItem
                v-for="item in peopleItems ?? []"
                :key="item.title"
            >
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" v-if="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
         <div class="mt-4 flex items-center gap-3 px-2">
            <div class="h-px w-4 bg-sidebar-border/70" />
            <SidebarGroupLabel class="h-auto shrink-0 px-0">
                Workspace
            </SidebarGroupLabel>
            <div class="h-px flex-1 bg-sidebar-border/70" />
        </div>
        <SidebarMenu>
            <SidebarMenuItem
                v-for="item in workspaceItems ?? []"
                :key="item.title"
            >
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" v-if="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
