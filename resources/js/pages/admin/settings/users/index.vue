<script setup lang="ts">
import { Head, Link, router, useForm  } from '@inertiajs/vue3';
import { Filter, Pencil, Plus, Search, User } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';


import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import EditUserForm from '@/views/settings/users/EditUserForm.vue';



type User = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    user_type_id: number;
    status: 'active' | 'inactive' | 'pending';
    phone: string | null;
    mobile: string | null;
    clients: {
        id: number;
        company_name: string;
    }[];
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

type Company = {
    id: number;
    company_name: string;
};

type UserType = {
    id: number;
    user_type_name: string;
};


const props = defineProps<{
    users: PaginatedUsers;
    companies: Company[];
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
        '/settings/users',
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


const companySearch = ref('');
const companyLookupOpen = ref(false);
const companyVisibleLimit = ref(5);
const selectedCompanyIds = ref<number[]>([]);

const profileSearch = ref('');
const profileLookupOpen = ref(false);
const selectedUserTypeId = ref<number | null>(null);

const addUserDialogOpen = ref(false);

const editUserDialogOpen = ref(false);
const selectedUser = ref<User | null>(null);

const openEditUser = (user: User) => {
    selectedUser.value = user;
    editUserDialogOpen.value = true;
};

const addUserForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    client_ids: [] as number[],
    user_type_id: null as number | null,
});


const filteredCompanies = computed(() => {
    const value = companySearch.value.trim().toLowerCase();

    return props.companies.filter((company) => {
        const matchesSearch =
            !value || company.company_name.toLowerCase().includes(value);

        const isAlreadySelected = selectedCompanyIds.value.includes(company.id);

        return matchesSearch && !isAlreadySelected;
    });
});

const visibleCompanies = computed(() =>
    filteredCompanies.value.slice(0, companyVisibleLimit.value),
);

const hasMoreCompanies = computed(
    () => companyVisibleLimit.value < filteredCompanies.value.length,
);

const filteredUserTypes = computed(() => {
    const value = profileSearch.value.trim().toLowerCase();

    if (!value) {
        return props.userTypes;
    }

    return props.userTypes.filter((userType) =>
        userType.user_type_name.toLowerCase().includes(value),
    );
});

const selectCompany = (company: Company) => {
    if (selectedCompanyIds.value.includes(company.id)) {
        return;
    }

    if (selectedCompanyIds.value.length >= 3) {
        toast.error('You can select up to 3 companies only.');

        return;
    }

    selectedCompanyIds.value.push(company.id);
    companySearch.value = '';
    companyLookupOpen.value = true;
    companyVisibleLimit.value = 5;
};

const selectedCompanies = computed(() =>
    props.companies.filter((company) =>
        selectedCompanyIds.value.includes(company.id),
    ),
);

const removeCompany = (companyId: number) => {
    selectedCompanyIds.value = selectedCompanyIds.value.filter(
        (id) => id !== companyId,
    );
};

const loadMoreCompanies = (event: Event) => {
    const target = event.target as HTMLElement;

    const isNearBottom =
        target.scrollTop + target.clientHeight >= target.scrollHeight - 8;

    if (!isNearBottom || !hasMoreCompanies.value) {
        return;
    }

    companyVisibleLimit.value += 4;
};

const selectUserType = (userType: UserType) => {
    selectedUserTypeId.value = userType.id;
    profileSearch.value = userType.user_type_name;
    profileLookupOpen.value = false;
};

const closeProfileLookup = () => {
    window.setTimeout(() => {
        profileLookupOpen.value = false;
    }, 100);
};

const closeCompanyLookup = () => {
    window.setTimeout(() => {
        companyLookupOpen.value = false;
    }, 100);
};

const saveUser = () => {
    addUserForm.client_ids = selectedCompanyIds.value;
    addUserForm.user_type_id = selectedUserTypeId.value;

    addUserForm.post('/settings/users', {
        preserveScroll: true,
        onSuccess: () => {
            addUserDialogOpen.value = false;
            addUserForm.reset();
            companySearch.value = '';
            selectedCompanyIds.value = [];
            profileSearch.value = '';
            selectedUserTypeId.value = null;
            toast.success('User has been added. Account setup email will be sent shortly.');
        },

    });
};



watch(search, (value, _oldValue, onCleanup) => {

    const searchDelay = window.setTimeout(() => {
        applyFilters();
    },500);

    onCleanup(() => window.clearTimeout(searchDelay));

});

watch(companySearch, () => {
    companyVisibleLimit.value = 5;
});

watch(profileSearch, (value) => {
    if (value.trim() === '') {
        selectedUserTypeId.value = null;
        profileLookupOpen.value = true;
    }
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

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Dialog v-model:open="addUserDialogOpen">
                    <DialogTrigger as-child>
                        <Button type="button"
class="h-8 gap-2 text-white hover:bg-red-700">
                            <Plus class="size-4" />
                            Add user
                        </Button>
                    </DialogTrigger>

                    <DialogContent class="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Add user</DialogTitle>
                            <DialogDescription>
                                Create a user profile for workspace access.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-4 py-2 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="first_name">First name</Label>
                                <Input id="first_name" v-model="addUserForm.first_name" placeholder="First name" />
                                <InputError :message="addUserForm.errors.first_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="last_name">Last name</Label>
                                <Input id="last_name" v-model="addUserForm.last_name" placeholder="Last name" />
                                <InputError :message="addUserForm.errors.last_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="addUserForm.email" type="email"
                                    placeholder="email@example.com" />
                                <InputError :message="addUserForm.errors.email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="phone">Phone</Label>
                                <Input id="phone" v-model="addUserForm.phone" placeholder="Phone number" />
                                <InputError :message="addUserForm.errors.phone" />
                            </div>

                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="profile">Profile</Label>

                                <div class="relative">
                                    <Input id="profile" v-model="profileSearch" type="search"
                                        placeholder="Search profile..." autocomplete="off"
                                        @focus="profileLookupOpen = true" @input="profileLookupOpen = true"
                                        @blur="closeProfileLookup" @keydown.escape="profileLookupOpen = false" />

                                    <InputError :message="addUserForm.errors.user_type_id" />

                                    <div v-if="profileLookupOpen"
                                        class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md">
                                        <button v-for="userType in filteredUserTypes" :key="userType.id" type="button"
                                            class="flex w-full items-center rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                            @mousedown.prevent="selectUserType(userType)">
                                            {{ userType.user_type_name }}
                                        </button>

                                        <div v-if="filteredUserTypes.length === 0"
                                            class="px-2 py-2 text-sm text-muted-foreground">
                                            No profiles found.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="company">Company</Label>

                                <div class="relative">
                                    <Input id="company" v-model="companySearch" type="search"
                                        placeholder="Search company..." autocomplete="off"
                                        @focus="companyLookupOpen = true" @input="companyLookupOpen = true"
                                        @blur="closeCompanyLookup"
                                        @keydown.escape="companyLookupOpen = false" />
                                    <InputError :message="addUserForm.errors.client_ids" />
                                    <div v-if="selectedCompanies.length > 0" class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="company in selectedCompanies" :key="company.id"
                                            class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium">
                                            {{ company.company_name }}

                                            <button type="button" class="text-muted-foreground hover:text-foreground"
                                                :aria-label="`Remove ${company.company_name}`" @mousedown.prevent
                                                @click="removeCompany(company.id)">
                                                ×
                                            </button>
                                        </span>
                                    </div>

                                    <div v-if="companyLookupOpen"
                                        class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                                        @scroll="loadMoreCompanies">
                                        <button v-for="company in visibleCompanies" :key="company.id" type="button"
                                            class="flex w-full items-center rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                            @mousedown.prevent="selectCompany(company)">
                                            {{ company.company_name }}
                                        </button>

                                        <div v-if="hasMoreCompanies" class="px-2 py-2 text-xs text-muted-foreground">
                                            Type to search more company...
                                        </div>

                                        <div v-if="filteredCompanies.length === 0"
                                            class="px-2 py-2 text-sm text-muted-foreground">
                                            No companies found.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button type="button" class="bg-red-600 text-white" :disabled="addUserForm.processing"
                                @click="saveUser">
                                Save user
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative w-40 transition-[width] duration-200 ease-in-out hover:w-64 focus-within:w-64">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" type="search" placeholder="Search users..." class="h-8 pl-8 text-sm" />
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button type="button" variant="outline" class="h-8 gap-2" title="Filter users"
                                aria-label="Filter users">
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
                            <tr v-for="user in users.data" :key="user.id" class="border-t">
                                <td class="truncate px-4 py-3">
                                    {{ user.first_name }}
                                </td>
                                <td class="truncate px-4 py-3">
                                    {{ user.last_name }}
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="user.clients.length" class="flex min-w-0 items-center gap-2">
                                        <span class="truncate">
                                            {{ user.clients[0].company_name }}
                                        </span>

                                        <TooltipProvider v-if="user.clients.length > 1" :delay-duration="100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <button type="button"
                                                        class="inline-flex shrink-0 items-center rounded-full border border-border bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                                        +{{ user.clients.length - 1 }}
                                                    </button>
                                                </TooltipTrigger>

                                                <TooltipContent side="top" align="center"
                                                    class="max-w-[calc(100vw-2rem)] border border-border bg-popover px-3 py-2 text-popover-foreground shadow-md">
                                                    <div class="space-y-1">
                                                        <div v-for="client in user.clients.slice(1)" :key="client.id"
                                                            class="text-sm">
                                                            {{ client.company_name }}
                                                        </div>
                                                    </div>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>

                                    <span v-else>-</span>
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
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button"
                                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-md border border-border bg-background text-muted-foreground shadow-sm transition-all hover:bg-muted/60 hover:text-foreground"
                                            title="Edit user" aria-label="Edit user" @click="openEditUser(user)">
                                            <Pencil class="size-4" />
                                        </button>

                                        <button type="button"
                                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-md border transition-all"
                                            :class="user.status === 'active'
                                                ? 'border-border bg-muted text-foreground shadow-inner ring-1 ring-border'
    : 'border-border bg-background text-muted-foreground shadow-sm hover:bg-muted/60 hover:text-foreground'"
                                            :title="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                            :aria-label="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                            @click="toggleStatus(user)">
                                            <User class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="8" class="px-4 py-6 text-center text-muted-foreground">
                                    No users found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y md:hidden">
                    <article v-for="user in users.data" :key="user.id" class="space-y-4 p-4">
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
                                    <div v-if="user.clients.length" class="flex min-w-0 items-center gap-2">
                                        <span class="truncate">
                                            {{ user.clients[0].company_name }}
                                        </span>

                                        <TooltipProvider v-if="user.clients.length > 1" :delay-duration="100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <button type="button"
                                                        class="inline-flex shrink-0 items-center rounded-full border border-border bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                                        +{{ user.clients.length - 1 }}
                                                    </button>
                                                </TooltipTrigger>

                                                <TooltipContent side="top" align="center"
                                                    class="max-w-[calc(100vw-2rem)] border border-border bg-popover px-3 py-2 text-popover-foreground shadow-md">
                                                    <div class="space-y-1">
                                                        <div v-for="client in user.clients.slice(1)" :key="client.id"
                                                            class="text-sm">
                                                            {{ client.company_name }}
                                                        </div>
                                                    </div>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>

                                    <span v-else>-</span>
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

                    <div v-if="users.data.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                        No users found.
                    </div>
                </div>

                <div class="border-t px-4 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ users.from }} to {{ users.to }} of
                            {{ users.total }} users
                        </p>

                        <div class="flex flex-wrap items-center gap-2">
                            <Link v-for="link in users.links" :key="link.label" :href="link.url ?? '#'" preserve-scroll
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
        <EditUserForm v-if="selectedUser" v-model:open="editUserDialogOpen" :user="selectedUser" :companies="companies"
            :user-types="userTypes" @saved="selectedUser = null" />
    </SettingsLayout>
</template>
