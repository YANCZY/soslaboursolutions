<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye, Filter, Power, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import type { Site } from '@/types/site';


const props = withDefaults(defineProps<{
    sites?: Site[];
    canManageSites?: boolean;
}>(), {
    sites: () => [],
    canManageSites: false,
});

const search = ref('');
type StatusFilter = 'active' | 'inactive' | 'all';

const status = ref<StatusFilter>('active');

const statusFilterLabel = computed(() => {
    if (status.value === 'all') {
        return 'All';
    }

    return status.value === 'active' ? 'Active' : 'Inactive';
});
const page = ref(1);
const perPage = ref(10);

const detailsOpen = ref(false);
const selectedSite = ref<Site | null>(null);

const columns = [
    { key: 'site_name', label: 'Site Name' },
    { key: 'company', label: 'Company' },
    { key: 'status', label: 'Status' },
    { key: 'street', label: 'Street' },
    { key: 'city', label: 'Suburb/City' },
    { key: 'state', label: 'State' },
    { key: 'postcode', label: 'Postcode' },
    { key: 'country', label: 'Country' },
] as const;

const siteValues = (site: Site) => ({
    site_name: site.site_name?.trim() || site.company.company_name,
    company: site.company.company_name,
    status: site.is_active ? 'Active' : 'Inactive',
    street: site.street,
    city: site.city,
    state: site.state,
    postcode: site.postcode,
    country: site.country,
});

const filteredSites = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.sites.filter((site) => {
        const matchesStatus =
            status.value === 'all' ||
            (status.value === 'active' && site.is_active) ||
            (status.value === 'inactive' && !site.is_active);

        const matchesSearch =
            !query ||
            Object.values(siteValues(site)).some((value) =>
                value.toLowerCase().includes(query),
            );

        return matchesStatus && matchesSearch;
    });
});

const total = computed(() => filteredSites.value.length);

const totalPages = computed(() =>
    Math.max(1, Math.ceil(total.value / perPage.value)),
);

const visibleSites = computed(() => {
    const start = (page.value - 1) * perPage.value;

    return filteredSites.value.slice(start, start + perPage.value);
});

const from = computed(() =>
    total.value === 0 ? 0 : (page.value - 1) * perPage.value + 1,
);

const to = computed(() =>
    Math.min(page.value * perPage.value, total.value),
);

watch([search, status, perPage], () => {
    page.value = 1;
});

watch(totalPages, (value) => {
    page.value = Math.min(page.value, value);
});

const openDetails = (site: Site) => {
    selectedSite.value = site;
    detailsOpen.value = true;
};

const confirmationOpen = ref(false);
const siteToManage = ref<Site | null>(null);
const targetStatus = ref(false);
const processing = ref(false);

const confirmationAction = computed(() =>
    targetStatus.value ? 'Activate' : 'Deactivate',
);

const openStatusConfirmation = (site: Site) => {
    if (!props.canManageSites || processing.value) {
return;
}

    siteToManage.value = { ...site };
    targetStatus.value = !site.is_active;
    confirmationOpen.value = true;
};

const updateConfirmationOpen = (open: boolean) => {
    if (processing.value) {
return;
}

    confirmationOpen.value = open;

    if (!open) {
        siteToManage.value = null;
    }
};

const confirmStatusChange = () => {
    const site = siteToManage.value;

    if (!site || !props.canManageSites || processing.value){
         return;
    }

    const action = confirmationAction.value;
    processing.value = true;

    router.patch(
        `/settings/company/sites/${site.id}/status`,
        { is_active: targetStatus.value },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                confirmationOpen.value = false;
                siteToManage.value = null;
                toast.success(
                    action === 'Activate'
                        ? 'Site activated.'
                        : 'Site deactivated.',
                );
            },
            onError: (errors) => {
                toast.error(
                    Object.values(errors)[0] ??
                    'Unable to update site status.',
                );
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

</script>

<template>
    <div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div
            class="relative w-40 transition-[width] duration-200 ease-in-out hover:w-64 focus-within:w-64"
        >
            <Search
                class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
            />

            <Input
                v-model="search"
                type="search"
                placeholder="Search sites..."
                aria-label="Search sites"
                class="h-8 pl-8 text-sm"
            />
        </div>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    class="h-8 gap-2"
                    title="Filter sites"
                    aria-label="Filter sites"
                >
                    <Filter class="size-4" />
                    {{ statusFilterLabel }}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end">
                <DropdownMenuItem @click="status = 'active'">
                    Active
                </DropdownMenuItem>

                <DropdownMenuItem @click="status = 'all'">
                    All
                </DropdownMenuItem>

                <DropdownMenuItem @click="status = 'inactive'">
                    Inactive
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>

        <div class="overflow-hidden rounded-md border">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[80rem] text-sm">
                    <thead class="bg-muted text-left">
                        <tr>
                            <th
                                v-for="column in columns"
                                :key="column.key"
                                scope="col"
                                class="whitespace-nowrap px-4 py-3 font-medium"
                            >
                                {{ column.label }}
                            </th>

                            <th
                                scope="col"
                                class="px-4 py-3 text-center font-medium"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="site in visibleSites"
                            :key="site.id"
                            class="border-t transition-colors hover:bg-muted/30"
                        >
                            <td
                                v-for="column in columns"
                                :key="column.key"
                                class="px-4 py-3"
                            >
                                <span
                                    v-if="column.key === 'status'"
                                    class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        site.is_active
                                            ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-300'
                                            : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300'
                                    "
                                >
                                    {{ siteValues(site).status }}
                                </span>

                                <span
                                    v-else
                                    class="block max-w-64 break-words"
                                >
                                    {{ siteValues(site)[column.key] }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        class="size-8 shrink-0"
                                        :title="`View ${siteValues(site).site_name}`"
                                        :aria-label="`View ${siteValues(site).site_name}`"
                                        @click="openDetails(site)"
                                    >
                                        <Eye class="size-4" />
                                    </Button>

                                    <button
                                        v-if="props.canManageSites"
                                        type="button"
                                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md border transition-all disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="
                                            site.is_active
                                                ? 'border-border bg-muted text-foreground shadow-inner ring-1 ring-border'
                                                : 'border-border bg-background text-muted-foreground shadow-sm hover:bg-muted/60 hover:text-foreground'
                                        "
                                        :title="`${site.is_active ? 'Deactivate' : 'Activate'} ${siteValues(site).site_name}`"
                                        :aria-label="`${site.is_active ? 'Deactivate' : 'Activate'} ${siteValues(site).site_name}`"
                                        :disabled="processing"
                                        @click="openStatusConfirmation(site)"
                                    >
                                        <Power class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="visibleSites.length === 0">
                            <td
                                colspan="9"
                                class="px-4 py-10 text-center"
                            >
                                <p class="font-medium">
                                    {{
                                        sites.length === 0
                                            ? 'No sites available.'
                                            : 'No matching sites found.'
                                    }}
                                </p>

                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{
                                        sites.length === 0
                                            ? 'Company sites will appear here.'
                                            : 'Try another search or status filter.'
                                    }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col gap-3 border-t px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-muted-foreground">
                    Showing {{ from }} to {{ to }} of {{ total }} sites
                </p>

                <div class="flex flex-wrap items-center gap-3">
                    <label class="flex items-center gap-2 text-sm">
                        Rows
                        <select
                            v-model.number="perPage"
                            class="h-9 rounded-md border border-input bg-background px-2"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                    </label>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="page === 1"
                        @click="page--"
                    >
                        Previous
                    </Button>

                    <span class="text-sm text-muted-foreground">
                        Page {{ page }} of {{ totalPages }}
                    </span>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="page >= totalPages"
                        @click="page++"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <Dialog v-model:open="detailsOpen">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Site details</DialogTitle>
                <DialogDescription>
                    View the site's company, status, and address.
                </DialogDescription>
            </DialogHeader>

            <dl
                v-if="selectedSite"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2"
            >
                <div
                    v-for="column in columns"
                    :key="column.key"
                    class="min-w-0 rounded-lg border p-4"
                >
                    <dt class="text-sm text-muted-foreground">
                        {{ column.label }}
                    </dt>
                    <dd class="mt-1 break-words text-sm font-medium">
                        {{ siteValues(selectedSite)[column.key] }}
                    </dd>
                </div>
            </dl>
        </DialogContent>
    </Dialog>
    <!-- UPDATE SITE STATUS -->
    <Dialog
        :open="confirmationOpen"
        @update:open="updateConfirmationOpen"
    >
        <DialogContent
            class="sm:max-w-md"
            :show-close-button="!processing"
            @escape-key-down="(event) => {
                if (processing) event.preventDefault();
            }"
            @interact-outside="(event) => {
                if (processing) event.preventDefault();
            }"
        >
            <DialogHeader>
                <DialogTitle>
                    {{ confirmationAction }} site?
                </DialogTitle>

                <DialogDescription v-if="siteToManage">
                    {{ confirmationAction }}
                    {{ siteValues(siteToManage).site_name }}
                    for {{ siteToManage.company.company_name }}
                    at {{ siteToManage.street }}, {{ siteToManage.city }}?
                    Its status will change to
                    {{ targetStatus ? 'Active' : 'Inactive' }}.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="processing"
                    @click="updateConfirmationOpen(false)"
                >
                    Cancel
                </Button>

                <Button
                    type="button"
                    :variant="targetStatus ? 'default' : 'destructive'"
                    :disabled="processing"
                    @click="confirmStatusChange"
                >
                    {{ processing ? 'Processing...' : confirmationAction }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
