<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, User } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings/Layout.vue';



type User = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    status: 'active' | 'inactive';
    phone: string | null;
    mobile: string | null;
    client: {
        id: number;
        company_name: string;
    } | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: User[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    users: PaginatedUsers;
    filters: {
        search: string;
    };
}>();

const search = ref(props.filters.search ?? '');

watch(search, (value) => {
    router.get(
        '/settings/users',
        {
            search: value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
});

const paginationLabel = (label: string) => {
    return label
        .replace('&laquo; Previous', 'Previous')
        .replace('Next &raquo;', 'Next');
};

const statusLabel = (status: User['status']) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const statusBadgeClass = (status: User['status']) => {
    return status === 'active'
        ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-300'
        : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300';
};

const toggleStatus = (user: User) => {
    const message =
        user.status === 'active'
            ? 'User has been deactivated.'
            : 'User has been activated.';

    router.patch(
        `/settings/users/${user.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(message),
        },
    );
};


defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: '/settings',
            },
            {
                title: 'Users',
                href: '/settings/users',
            },
        ],
    },
});
</script>

<template>
    <Head title="Users" />

    <SettingsLayout>
        <h1 class="sr-only">Users</h1>

        <div class="flex flex-col gap-4">
            <Heading
                variant="small"
                title="Users"
                description="Manage users who can access this workspace"
            />

            <div
                class="relative w-40 transition-[width] duration-200 ease-in-out hover:w-64 focus-within:w-64"
            >
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search users..."
                    class="h-8 pl-8 text-sm"
                />
            </div>

            <div class="w-full max-w-7xl overflow-hidden rounded-md border">

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[68rem] table-fixed text-sm">
                    <thead class="bg-muted text-left">
                        <tr>
                            <th class="w-[13%] px-4 py-3 font-medium">
                                First name
                            </th>
                            <th class="w-[13%] px-4 py-3 font-medium">
                                Last name
                            </th>
                            <th class="w-[21%] px-4 py-3 font-medium">
                                Company
                            </th>
                            <th class="w-[21%] px-4 py-3 font-medium">Email</th>
                            <th class="w-[10%] px-4 py-3 font-medium">
                                Status
                            </th>
                            <th class="w-[8%] px-4 py-3 font-medium">Phone</th>
                            <th class="w-[8%] px-4 py-3 font-medium">Mobile</th>
                            <th
                                class="w-[8%] px-4 py-3 text-center font-medium"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-t"
                        >
                            <td class="truncate px-4 py-3">
                                {{ user.first_name }}
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ user.last_name }}
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ user.client?.company_name ?? '-' }}
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ user.email }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                    :class="statusBadgeClass(user.status)"
                                >
                                    {{ statusLabel(user.status) }}
                                </span>
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ user.phone ?? '-' }}
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ user.mobile ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                               <button
                                    type="button"
                                    class="inline-flex size-8 items-center justify-center rounded-md border transition-all"
                                    :class="
                                        user.status === 'active'
                                            ? 'border-border bg-muted text-foreground shadow-inner ring-1 ring-border'
                                            : 'border-border bg-background text-muted-foreground shadow-sm hover:bg-muted/60 hover:text-foreground'
                                    "
                                    :title="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                    :aria-label="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                    @click="toggleStatus(user)"
                                >
                                    <User class="size-4" />
                                </button>
                            </td>
                        </tr>
                        <!-- No users found -->
                        <tr v-if="users.data.length === 0">
                            <td
                                colspan="8"
                                class="px-4 py-6 text-center text-muted-foreground"
                            >
                                No users found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="divide-y md:hidden">
                <article
                    v-for="user in users.data"
                    :key="user.id"
                    class="space-y-4 p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-sm font-medium">
                                {{ user.first_name }} {{ user.last_name }}
                            </h2>
                            <p
                                class="text-sm break-words text-muted-foreground"
                            >
                                {{ user.email }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="rounded-full border px-2.5 py-1 text-xs font-medium"
                                :class="statusBadgeClass(user.status)"
                            >
                                {{ statusLabel(user.status) }}
                            </span>

                            <button
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-md border transition-all"
                                :class="
                                    user.status === 'active'
                                        ? 'border-border bg-muted text-foreground shadow-inner ring-1 ring-border'
                                        : 'border-border bg-background text-muted-foreground shadow-sm hover:bg-muted/60 hover:text-foreground'
                                "
                                :title="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                :aria-label="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                @click="toggleStatus(user)"
                            >
                                <User class="size-4" />
                            </button>
                        </div>
                    </div>
                    <dl class="grid grid-cols-1 gap-3 text-sm">
                        <div>
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Company
                            </dt>
                            <dd class="mt-1">
                                {{ user.client?.company_name ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Phone
                            </dt>
                            <dd class="mt-1">{{ user.phone ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Mobile
                            </dt>
                            <dd class="mt-1">{{ user.mobile ?? '-' }}</dd>
                        </div>
                    </dl>
                </article>

                <div
                    v-if="users.data.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    No users found.
                </div>
            </div>

            <!-- Pagination -->
            <div class="border-t px-4 py-3">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-muted-foreground">
                        Showing {{ users.from }} to {{ users.to }} of
                        {{ users.total }} users
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            preserve-scroll
                            class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm"
                            :class="{
                                'bg-primary text-primary-foreground':
                                    link.active,
                                'pointer-events-none opacity-50': !link.url,
                            }"
                        >
                            {{ paginationLabel(link.label) }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- End Pagination -->
            </div>
        </div>
    </SettingsLayout>
</template>
