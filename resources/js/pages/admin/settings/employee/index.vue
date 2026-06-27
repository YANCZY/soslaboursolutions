<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Filter, Plus, Search, User } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import EmployeeAddForm from '@/views/settings/employee/EmployeeAddForm.vue';



type User = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    status: 'active' | 'inactive' | 'pending';
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

type UserType = {
    id: number;
    user_type_name: string;
};


const props = defineProps<{
    employees: PaginatedUsers;
    userTypes: UserType[];
    filters: {
        search: string;
        status: 'active' | 'inactive' | 'pending' | 'all';
    };
}>();

const search = ref(props.filters.search ?? '');

type StatusFilter = 'active' | 'inactive' | 'pending' | 'all';

const statusFilter = ref<StatusFilter>(props.filters.status ?? 'active');

const statusFilterLabel = computed(() => {
    if (statusFilter.value === 'all') {
        return 'All';
    }

    return statusLabel(statusFilter.value);
});

const applyFilters = () => {
    router.get(
        '/settings/employee',
        {
            search: search.value || undefined,
            status: statusFilter.value === 'active' ? undefined : statusFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const changeStatusFilter = (status: StatusFilter) => {
    statusFilter.value = status;
    applyFilters();
};

const addUserDialogOpen = ref(false);

watch(search, (value, _oldValue, onCleanup) => {

    const searchDelay = window.setTimeout(() => {
        applyFilters();
    },500);

    onCleanup(() => window.clearTimeout(searchDelay));

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
    if (status === 'active') {
        return 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-300';
    }

    if (status === 'pending') {
        return 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/60 dark:bg-orange-950/40 dark:text-orange-300';
    }

    return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300';
};

const toggleStatus = (user: User) => {
    const message =
        user.status === 'active'
            ? 'User has been deactivated.'
            : 'User has been activated.';

    router.patch(
        `/settings/employee/${user.id}/toggle-status`,
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
                title: 'Employee',
                href: '/settings/employee',
            },
        ],
    },
});
</script>

<template>
    <Head title="Employee" />

    <SettingsLayout>
        <h1 class="sr-only">Employee</h1>

        <div class="flex flex-col gap-4">
            <Heading
                variant="small"
                title="Employee"
                description="Manage employees for this company"
            />

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Dialog v-model:open="addUserDialogOpen">
                    <DialogTrigger as-child>
                        <Button type="button" class="h-8 gap-2 text-white hover:bg-red-700">
                            <Plus class="size-4" />
                            Add employee
                        </Button>
                    </DialogTrigger>

                    <EmployeeAddForm :user-types="userTypes" @success="addUserDialogOpen = false" />
                </Dialog>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative w-40 transition-[width] duration-200 ease-in-out hover:w-64 focus-within:w-64">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" type="search" placeholder="Search employees..."
                            class="h-8 pl-8 text-sm" />
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button type="button" variant="outline" class="h-8 gap-2" title="Filter employees"
                                aria-label="Filter employees">
                                <Filter class="size-4" />
                                {{ statusFilterLabel }}
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="changeStatusFilter('active')">
                                Active
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="changeStatusFilter('all')">
                                All
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="changeStatusFilter('pending')">
                                Pending
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="changeStatusFilter('inactive')">
                                Inactive
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
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
                                <th class="w-[21%] px-4 py-3 font-medium">
                                    Email
                                </th>
                                <th class="w-[10%] px-4 py-3 font-medium">
                                    Status
                                </th>
                                <th class="w-[8%] px-4 py-3 font-medium">
                                    Phone
                                </th>
                                <th class="w-[8%] px-4 py-3 font-medium">
                                    Mobile
                                </th>
                                <th class="w-[8%] px-4 py-3 text-center font-medium">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="user in employees.data" :key="user.id" class="border-t">
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
                                        :class="statusBadgeClass(user.status)">
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
                                    <button type="button"
                                        class="inline-flex size-8 items-center justify-center rounded-md border transition-all"
                                        :class="user.status === 'active'
                                            ? 'border-border bg-muted text-foreground shadow-inner ring-1 ring-border'
                                            : 'border-border bg-background text-muted-foreground shadow-sm hover:bg-muted/60 hover:text-foreground'
    " :title="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                        :aria-label="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                        @click="toggleStatus(user)">
                                        <User class="size-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="employees.data.length === 0">
                                <td colspan="8" class="px-4 py-6 text-center text-muted-foreground">
                                    No employees found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y md:hidden">
                    <article v-for="user in employees.data" :key="user.id" class="space-y-4 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-medium">
                                    {{ user.first_name }} {{ user.last_name }}
                                </h2>
                                <p class="text-sm break-words text-muted-foreground">
                                    {{ user.email }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span class="rounded-full border px-2.5 py-1 text-xs font-medium"
                                    :class="statusBadgeClass(user.status)">
                                    {{ statusLabel(user.status) }}
                                </span>

                                <button type="button"
                                    class="inline-flex size-8 items-center justify-center rounded-md border transition-all"
                                    :class="user.status === 'active'
                                        ? 'border-border bg-muted text-foreground shadow-inner ring-1 ring-border'
                                        : 'border-border bg-background text-muted-foreground shadow-sm hover:bg-muted/60 hover:text-foreground'
    " :title="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                    :aria-label="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                    @click="toggleStatus(user)">
                                    <User class="size-4" />
                                </button>
                            </div>
                        </div>

                        <dl class="grid grid-cols-1 gap-3 text-sm">
                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">
                                    Company
                                </dt>
                                <dd class="mt-1">
                                    {{ user.client?.company_name ?? '-' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">
                                    Phone
                                </dt>
                                <dd class="mt-1">
                                    {{ user.phone ?? '-' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-muted-foreground">
                                    Mobile
                                </dt>
                                <dd class="mt-1">
                                    {{ user.mobile ?? '-' }}
                                </dd>
                            </div>
                        </dl>
                    </article>

                    <div v-if="employees.data.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                        No employees found.
                    </div>
                </div>

                <div class="border-t px-4 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ employees.from }} to {{ employees.to }} of
                            {{ employees.total }} employees
                        </p>

                        <div class="flex flex-wrap items-center gap-2">
                            <Link v-for="link in employees.links" :key="link.label" :href="link.url ?? '#'"
                                preserve-scroll
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm"
                                :class="{
    'bg-primary text-primary-foreground': link.active,
    'pointer-events-none opacity-50': !link.url,
}">
                                {{ paginationLabel(link.label) }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SettingsLayout>
</template>
