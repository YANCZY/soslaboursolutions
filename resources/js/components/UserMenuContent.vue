<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Bell, LogOut, Settings } from 'lucide-vue-next';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import type { User } from '@/types';
import type { AppNotification } from '@/types/auth';

type Props = {
    user: User;
    notifications: AppNotification[];
    unreadCount: number;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator />

    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" href="/settings/profile" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator />

    <div class="px-2 py-2">
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium">
                <Bell class="h-4 w-4" />
                <span>Notifications</span>
            </div>
            <span
                v-if="unreadCount > 0"
                class="inline-flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-semibold text-white"
            >
                {{ unreadCount }}
            </span>
        </div>

        <div v-if="notifications.length === 0" class="rounded-md border px-3 py-2 text-sm text-muted-foreground">
            No notifications
        </div>

        <div v-else class="space-y-1">
            <DropdownMenuItem
                v-for="notification in notifications"
                :key="notification.id"
                :as-child="true"
                class="cursor-pointer items-start"
            >
                <Link
                    :href="route('notifications.open', notification.id)"
                    method="post"
                    as="button"
                    class="flex w-full items-start gap-3 rounded-md px-2 py-2 text-left"
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
                        <p class="text-xs text-muted-foreground leading-5">
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

    <DropdownMenuSeparator />

    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            href="/logout"
            method="post"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
