<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, Calendar1Icon, ClipboardCheck, Contact2, LayoutGrid, Wallet } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const peopleItems: NavItem[] = [
    {
        title: 'Clients',
        href: '/clients',
        icon: Building2
    },
    {
        title: 'Contractors',
        href: '/contractors',
        icon: Contact2
    },
];

const workspaceItems: NavItem[] = [
    {
        title: 'Attendance',
        href: '/attendance',
        icon: Calendar1Icon
    },
    {
        title: 'Travel Allowance',
         href: '/travel-allowance',
        icon: Wallet
    },
    {
        title: 'For Approvals',
        href: '/for-approvals',
        icon: ClipboardCheck
    },

];

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" :people-items="peopleItems" :workspace-items="workspaceItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
