<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';


type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

let notificationPollTimer: number | undefined;

const refreshNotifications = () => {
    router.reload({
        only: ['auth'],
        preserveScroll: true,
        preserveState: true,
    });
};

onMounted(() => {
    notificationPollTimer = window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            refreshNotifications();
        }
    }, 7000);
});

onBeforeUnmount(() => {
    if (notificationPollTimer) {
        window.clearInterval(notificationPollTimer);
    }
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
