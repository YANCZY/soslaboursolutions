<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { open as openNotification } from '@/routes/notifications';
import type { AppNotification } from '@/types/auth';

const props = defineProps<{
    notifications: AppNotification[];
    unreadCount: number;
}>();

const selectedTab = ref<'all' | 'unread'>('all');

const visibleNotifications = computed(() => {
    if (selectedTab.value === 'unread') {
        return props.notifications.filter((notification) => !notification.is_read);
    }

    return props.notifications;
});

const refreshNotifications = () => {
    router.reload({
        only: ['auth'],
        preserveScroll: true,
        preserveState: true,
    });
};

const csrfToken = () =>
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const markAllAsRead = async () => {
    await fetch('/notifications/mark-all-as-read', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
    });

    refreshNotifications();
};
</script>

<template>
    <div class="w-full p-3">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Notifications</h2>

            <span
                v-if="unreadCount > 0"
                class="rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white"
            >
                {{ unreadCount }}
            </span>
        </div>

        <div class="mb-3 flex gap-2">
            <button
                type="button"
                class="rounded-full px-3 py-1 text-sm font-medium"
                :class="selectedTab === 'all' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                @click="selectedTab = 'all'"
            >
                All
            </button>

            <button
                type="button"
                class="rounded-full px-3 py-1 text-sm font-medium"
                :class="selectedTab === 'unread' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                @click="selectedTab = 'unread'"
            >
                Unread
            </button>
            <button
                v-if="unreadCount > 0"
                type="button"
                class="ml-auto text-xs font-medium text-primary hover:underline"
                @click="markAllAsRead"
            >
                Mark all as read
            </button>
        </div>

        <div v-if="visibleNotifications.length === 0" class="rounded-md border px-3 py-3 text-sm text-muted-foreground">
            No notifications
        </div>

        <div v-else class="max-h-96 space-y-1 overflow-y-auto pr-1">
            <DropdownMenuItem
                v-for="notification in visibleNotifications"
                :key="notification.id"
                :as-child="true"
                class="cursor-pointer items-start rounded-md"
            >
                <Link
                    :href="openNotification(notification.id)"
                    method="post"
                    as="button"
                    class="flex w-full items-start gap-3 rounded-md px-2 py-2 text-left"
                    :class="!notification.is_read ? 'bg-muted' : ''"
                    @finish="refreshNotifications"
                >
                    <div class="relative mt-0.5">
                        <Bell class="h-4 w-4 text-muted-foreground" />
                        <span
                            v-if="!notification.is_read"
                            class="absolute -top-1 -right-1 size-2 rounded-full bg-red-500"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium leading-5">
                            {{ notification.title }}
                        </p>

                        <p class="text-xs leading-5 text-muted-foreground">
                            {{ notification.message }}
                        </p>

                        <p
                            v-if="notification.week_label"
                            class="mt-1 text-[11px] text-muted-foreground"
                        >
                            {{ notification.week_label }}
                        </p>
                    </div>
                </Link>
            </DropdownMenuItem>
        </div>
    </div>
</template>
