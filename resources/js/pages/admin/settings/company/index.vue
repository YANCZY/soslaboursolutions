<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Trash2, Power } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings/Layout.vue';




type Company = {
    id: number;
    company_name: string;
    trade: string | null;
    industry: string | null;
    website: string | null;
    company_address_state: string | null;
    is_active: boolean;
    can_delete: boolean;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedCompanies = {
    data: Company[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    companies: PaginatedCompanies;
    filters: {
        search: string;

    };
    can_manage_companies: boolean;
}>();

const search = ref(props.filters.search ?? '');

const processingId = ref<number | null>(null);
const confirmationOpen = ref(false);
const companyToManage = ref<Company | null>(null);

const confirmationAction = computed(() => {
    const company = companyToManage.value;

    if (!company) return '';
    if (company.can_delete) return 'Delete';

    return company.is_active ? 'Deactivate' : 'Activate';
});

const confirmationDescription = computed(() => {
    const company = companyToManage.value;

    if (!company) return '';

    if (company.can_delete) {
        return `Delete ${company.company_name}? This permanently removes the company and cannot be undone.`;
    }

    if (company.is_active) {
        return `Deactivate ${company.company_name}? It will no longer be available for new selections or check-ins. Existing records will be retained.`;
    }

    return `Activate ${company.company_name}? It will become available for selection and check-in again.`;
});

const openCompanyConfirmation = (company: Company) => {
    if (!props.can_manage_companies || processingId.value !== null) return;

    companyToManage.value = { ...company };
    confirmationOpen.value = true;
};

const updateConfirmationOpen = (open: boolean) => {
    if (processingId.value !== null) {
        return;
    }

    confirmationOpen.value = open;

    if (!open) {
        companyToManage.value = null;
    }
};

const confirmCompanyAction = () => {
    const company = companyToManage.value;

    if (
        !company ||
        !props.can_manage_companies ||
        processingId.value !== null
    ) {
        return;
    }

    const action = confirmationAction.value;
    processingId.value = company.id;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            confirmationOpen.value = false;
            companyToManage.value = null;
            toast.success(`${action} completed.`);
        },
        onError: (errors: Record<string, string>) => {
            toast.error(Object.values(errors)[0] ?? 'Action failed.');
        },
        onFinish: () => {
            processingId.value = null;
        },
    };

    if (company.can_delete) {
        router.delete(`/settings/company/${company.id}`, options);
    } else {
        router.patch(
            `/settings/company/${company.id}/status`,
            { is_active: !company.is_active },
            options,
        );
    }
};

watch(search, (value, _oldValue, onCleanup) => {
    const searchDelay = window.setTimeout(() => {
        router.get(
            '/settings/company',
            {
                search: value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 500);

    onCleanup(() => window.clearTimeout(searchDelay));
});

const paginationLabel = (label: string) => {
    return label
        .replace('&laquo; Previous', 'Previous')
        .replace('Next &raquo;', 'Next');
};

const statusBadgeClass = (isActive: boolean) => {
    return isActive
        ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-300'
        : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300';
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: '/settings',
            },
            {
                title: 'Company',
                href: '/settings/company',
            },
        ],
    },
});
</script>

<template>
    <Head title="Company" />

    <SettingsLayout>
    <div class="space-y-4">
        <Heading
            variant="small"
            title="Company"
            description="Manage company settings for this workspace"
        />

        <div class="flex items-center">
            <div
                class="relative w-40 transition-[width] duration-200 ease-in-out hover:w-64 focus-within:w-64"
            >
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                />

                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search companies..."
                    class="h-8 pl-8 text-sm"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-md border">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[64rem] table-fixed text-sm">
                    <thead class="bg-muted text-left">
                        <tr>
                           <th class="w-[24%] px-4 py-3 font-medium">Company Name</th>
                            <th class="w-[14%] px-4 py-3 font-medium">Trade</th>
                            <th class="w-[16%] px-4 py-3 font-medium">Industry</th>
                            <th class="w-[18%] px-4 py-3 font-medium">Website</th>
                            <th class="w-[10%] px-4 py-3 font-medium">State</th>
                            <th class="w-[10%] px-4 py-3 font-medium">Status</th>
                            <th class="w-[8%] px-4 py-3 text-center font-medium">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="company in props.companies.data"
                            :key="company.id"
                            class="border-t"
                        >
                            <td class="truncate px-4 py-3">
                                {{ company.company_name }}
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ company.trade ?? '-' }}
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ company.industry ?? '-' }}
                            </td>
                            <td class="truncate px-4 py-3">
                                <a
                                    v-if="company.website"
                                    :href="company.website"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary underline-offset-4 hover:underline"
                                >
                                    {{ company.website }}
                                </a>
                                <span v-else>-</span>
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ company.company_address_state ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                    :class="statusBadgeClass(company.is_active)"
                                >
                                    {{ company.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div
                                    v-if="props.can_manage_companies"
                                    class="flex items-center justify-center"
                                >
                                    <button
                                        v-if="company.can_delete"
                                        type="button"
                                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md border border-border bg-background text-destructive shadow-sm transition-all hover:border-destructive/40 hover:bg-destructive/10 disabled:cursor-not-allowed disabled:opacity-50"
                                        :title="`Delete ${company.company_name}`"
                                        :aria-label="`Delete ${company.company_name}`"
                                        :disabled="processingId !== null"
                                        @click="openCompanyConfirmation(company)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md border transition-all disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="company.is_active
                                            ? 'border-border bg-muted text-foreground shadow-inner ring-1 ring-border'
                                            : 'border-border bg-background text-muted-foreground shadow-sm hover:bg-muted/60 hover:text-foreground'"
                                        :title="`${company.is_active ? 'Deactivate' : 'Activate'} ${company.company_name}`"
                                        :aria-label="`${company.is_active ? 'Deactivate' : 'Activate'} ${company.company_name}`"
                                        :disabled="processingId !== null"
                                        @click="openCompanyConfirmation(company)"
                                    >
                                        <Power class="size-4" />
                                    </button>
                                </div>

                                <span v-else class="block text-center text-muted-foreground">
                                    —
                                </span>
                            </td>
                        </tr>

                        <tr v-if="props.companies.data.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-6 text-center text-muted-foreground"
                            >
                                No companies found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ props.companies.from }} to {{ props.companies.to }} of
                        {{ props.companies.total }} companies
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            v-for="link in props.companies.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            preserve-scroll
                            class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm"
                            :class="{
                                'bg-primary text-primary-foreground': link.active,
                                'pointer-events-none opacity-50': !link.url,
                            }"
                        >
                            {{ paginationLabel(link.label) }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog
        :open="confirmationOpen"
        @update:open="updateConfirmationOpen"
    >
        <DialogContent
            class="sm:max-w-md"
            :show-close-button="processingId === null"
            @escape-key-down="(event) => {
                if (processingId !== null) event.preventDefault();
            }"
            @interact-outside="(event) => {
                if (processingId !== null) event.preventDefault();
            }"
        >
            <DialogHeader>
                <DialogTitle>
                    {{ confirmationAction }} company?
                </DialogTitle>

                <DialogDescription>
                    {{ confirmationDescription }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="processingId !== null"
                    @click="updateConfirmationOpen(false)"
                >
                    Cancel
                </Button>

                <Button
                    type="button"
                    :variant="confirmationAction === 'Activate'
                        ? 'default'
                        : 'destructive'"
                    :disabled="processingId !== null"
                    @click="confirmCompanyAction"
                >
                    {{ processingId !== null
                        ? 'Processing...'
                        : confirmationAction }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

</SettingsLayout>
</template>
