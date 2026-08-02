<script setup lang="ts">
import { EllipsisVertical, FileText, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import ExportPdfDialog from '@/components/ExportPdfDialog.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import { exportBrandedPdf } from '@/lib/exportBrandedPdf';

const emit = defineEmits<{
    add: [];
    edit: [allowance: TravelAllowance];
    delete: [allowance: TravelAllowance];
}>();

type TravelAllowance = {
    id: number;
    date: string;
    name: string;
    client_id: number;
    company: string;
    description: string;
    rate: number;
    quantity: number;
    amount: number;
    approval_status?: 'pending' | 'approved' | 'rejected' | null;
};

const props = withDefaults(
    defineProps<{
        travelAllowances?: TravelAllowance[];
    }>(),
    {
        travelAllowances: () => [],
    },
);

type PaginationItem = {
    key: string;
    label: string;
    page: number;
    active: boolean;
    disabled: boolean;
};

const rowsPerPage = ref('15');
const currentPage = ref(1);
const search = ref('');
const exportDialogOpen = ref(false);

const selectedTravelAllowanceIds = ref<number[]>([]);


const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-AU', {
        style: 'currency',
        currency: 'AUD',
    }).format(value);
};

const formatDate = (value: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));
};

const formatApprovalStatus = (status?: string | null) => {
    if (!status) {
        return '-';
    }

    if (status === 'pending') {
        return 'Pending';
    }

    if (status === 'approved') {
        return 'Approved';
    }

    if (status === 'rejected') {
        return 'Rejected';
    }

    return '-';
};

const isSubmittable = (status?: string | null) => {

    return !status;
};

const isApprovalLocked = (status?: string | null) => {
    return status === 'pending' || status === 'approved' || status === 'rejected';
};

const approvalLockedMessage = (status?: string | null) => {
    if (status === 'pending') {
        return 'This travel allowance is already for approval.';
    }

    if (status === 'approved') {
        return 'This travel allowance has already been approved.';
    }

    if (status === 'rejected') {
        return 'This travel allowance has already been rejected.';
    }

    return '';
};

const approvalStatusClass = (status?: string | null) => {
    if (status === 'pending') {
        return 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-300';
    }

    if (status === 'approved') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300';
    }

    if (status === 'rejected') {
        return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300';
    }

    return 'border-border bg-muted/40 text-muted-foreground';
};

const filteredTravelAllowances = computed(() => {
    const value = search.value.trim().toLowerCase();

    if (!value) {
        return props.travelAllowances;
    }

    return props.travelAllowances.filter((allowance) => {
        const date = formatDate(String(allowance.date ?? '')).toLowerCase();
        const company = String(allowance.company ?? '').toLowerCase();
        const status = formatApprovalStatus(allowance.approval_status).toLowerCase();

        return [date, company, status].some((field) => field.includes(value));
    });
});

const openExportDialog = () => {
    exportDialogOpen.value = true;
};

const exportTravelAllowanceAsPdf = (range: { startDate: string; endDate: string }) => {
    const records = filteredTravelAllowances.value.filter((allowance) =>
        allowance.date >= range.startDate &&
        allowance.date <= range.endDate,
    );

    if (records.length === 0) {
        toast.error('No travel allowance records found for the selected date range.');

        return;
    }

    const result = exportBrandedPdf({
        title: 'Travel Allowance Report',
        records,
        columns: [
            { header: 'Date', value: (allowance) => formatDate(allowance.date) },
            { header: 'Name', value: (allowance) => allowance.name },
            { header: 'Company', value: (allowance) => allowance.company },
            { header: 'Description', value: (allowance) => allowance.description || '-' },
            { header: 'Status', value: (allowance) => formatApprovalStatus(allowance.approval_status) },
            { header: 'Quantity', value: (allowance) => allowance.quantity },
            { header: 'Rate', value: (allowance) => formatCurrency(allowance.rate) },
            { header: 'Amount', value: (allowance) => formatCurrency(allowance.amount) },
        ],
    });

    if (result === 'blocked') {
        toast.error('Please allow pop-ups to export the travel allowance PDF.');

        return;
    }

    exportDialogOpen.value = false;
};

const totalPages = computed(() =>
    Math.max(
        1,
        Math.ceil(filteredTravelAllowances.value.length / Number(rowsPerPage.value)),
    ),
);

const paginatedTravelAllowances = computed(() => {
    const start = (currentPage.value - 1) * Number(rowsPerPage.value);

    return filteredTravelAllowances.value.slice(
        start,
        start + Number(rowsPerPage.value),
    );
});

const firstRow = computed(() => {
    if (filteredTravelAllowances.value.length === 0) {
        return 0;
    }

    return (currentPage.value - 1) * Number(rowsPerPage.value) + 1;
});

const lastRow = computed(() =>
    Math.min(
        currentPage.value * Number(rowsPerPage.value),
        filteredTravelAllowances.value.length,
    ),
);

const isTravelAllowanceSelected = (travelAllowanceId: number) => {
    return selectedTravelAllowanceIds.value.includes(travelAllowanceId);
};

const toggleTravelAllowanceSelection = (
    allowance: TravelAllowance,
    selected: boolean | 'indeterminate',
) => {
    if (!isSubmittable(allowance.approval_status)) {
        toast.error(approvalLockedMessage(allowance.approval_status));

        return;
    }

    if (selected === true) {
        if (!selectedTravelAllowanceIds.value.includes(allowance.id)) {
            selectedTravelAllowanceIds.value.push(allowance.id);
        }

        return;
    }

    selectedTravelAllowanceIds.value = selectedTravelAllowanceIds.value.filter(
        (id) => id !== allowance.id,
    );
};

const groupSelectionState = (
    records: TravelAllowance[],
): boolean | 'indeterminate' => {
    const selectableRecords = records.filter((record) =>
        isSubmittable(record.approval_status),
    );

    if (selectableRecords.length === 0) {
        return false;
    }

    const selectedRecordCount = selectableRecords.filter((record) =>
        selectedTravelAllowanceIds.value.includes(record.id),
    ).length;

    if (selectedRecordCount === 0) {
        return false;
    }

    if (selectedRecordCount === selectableRecords.length) {
        return true;
    }

    return 'indeterminate';
};

const toggleGroupSelection = (
    records: TravelAllowance[],
    selected: boolean | 'indeterminate',
) => {
    const groupRecordIds = records
        .filter((record) => isSubmittable(record.approval_status))
        .map((record) => record.id);

    if (selected === true) {
        selectedTravelAllowanceIds.value = Array.from(
            new Set([
                ...selectedTravelAllowanceIds.value,
                ...groupRecordIds,
            ]),
        );

        return;
    }

    selectedTravelAllowanceIds.value = selectedTravelAllowanceIds.value.filter(
        (id) => !groupRecordIds.includes(id),
    );
};

const selectedGroupRecords = (records: TravelAllowance[]) => {
    return records.filter(
        (record) =>
            selectedTravelAllowanceIds.value.includes(record.id) &&
            isSubmittable(record.approval_status),
    );
};

const hasSelectedGroupRecords = (records: TravelAllowance[]) => {
    return selectedGroupRecords(records).length > 0;
};

const submitGroupForApproval = async (records: TravelAllowance[]) => {
    const recordsToSubmit = selectedGroupRecords(records);

    if (recordsToSubmit.length === 0) {
        alert('Please select at least one travel allowance record to submit.');

        return;
    }

    const response = await fetch('/travel-allowance/submit-for-approval', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN':
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
        body: JSON.stringify({
            travel_allowance_ids: recordsToSubmit.map((record) => record.id),
        }),
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        alert(data?.message ?? 'Unable to submit travel allowance for approval.');

        return;
    }

    window.location.reload();
};
const groupedTravelAllowances = computed(() => {
    const groups = new Map<
        string,
        {
            date: string;
            records: TravelAllowance[];
        }
    >();

    for (const allowance of paginatedTravelAllowances.value) {
        if (!groups.has(allowance.date)) {
            groups.set(allowance.date, {
                date: allowance.date,
                records: [],
            });
        }

        groups.get(allowance.date)?.records.push(allowance);
    }

    return Array.from(groups.values());
});

const paginationItems = computed<PaginationItem[]>(() => {
    const pages: PaginationItem[] = [
        {
            key: 'previous',
            label: 'Previous',
            page: currentPage.value - 1,
            active: false,
            disabled: currentPage.value === 1,
        },
    ];

    for (let page = 1; page <= totalPages.value; page++) {
        pages.push({
            key: `page-${page}`,
            label: String(page),
            page,
            active: page === currentPage.value,
            disabled: false,
        });
    }

    pages.push({
        key: 'next',
        label: 'Next',
        page: currentPage.value + 1,
        active: false,
        disabled: currentPage.value === totalPages.value,
    });

    return pages;
});

const goToPage = (item: PaginationItem) => {
    if (!item.disabled) {
        currentPage.value = item.page;
        selectedTravelAllowanceIds.value = [];
    }
};

watch([rowsPerPage, search], () => {
    currentPage.value = 1;
    selectedTravelAllowanceIds.value = [];
});
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col gap-4">
        <div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:w-72">
                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search date, company, status..."
                    class="pl-9"
                />
            </div>

            <div class="flex items-center gap-2">
                <Button type="button" class="gap-2" @click="emit('add')">
                    <Plus class="size-4" />
                    Add Allowance
                </Button>

                <DropdownMenu>
                    <DropdownMenuTrigger :as-child="true">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon-sm"
                            aria-label="Travel allowance actions"
                        >
                            <EllipsisVertical class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end" class="w-44">
                        <DropdownMenuItem class="cursor-pointer" @click="openExportDialog">
                            <FileText class="size-4" />
                            Export as PDF
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-md border bg-card">
            <div class="min-h-0 flex-1 overflow-x-auto overflow-y-auto">
                <table class="w-full min-w-[1050px] text-sm">
                    <thead class="sticky top-0 z-10 bg-muted text-left text-muted-foreground">
                        <tr>
                            <th class="w-12 px-4 py-3">
                                <span class="sr-only">Select travel allowance</span>
                            </th>
                            <th class="min-w-[140px] px-4 py-3 font-medium">Date</th>
                            <th class="min-w-[140px] px-4 py-3 font-medium">Name</th>
                            <th class="min-w-[220px] px-4 py-3 font-medium">Company</th>
                            <th class="min-w-[280px] px-4 py-3 font-medium">Description</th>
                            <th class="min-w-[120px] px-4 py-3 font-medium">Status</th>
                            <th class="min-w-[110px] px-4 py-3 text-right font-medium">Quantity</th>
                            <th class="min-w-[140px] px-4 py-3 text-right font-medium">Rate</th>
                            <th class="min-w-[140px] px-4 py-3 text-right font-medium">Amount</th>
                            <th class="min-w-[110px] px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template
                            v-for="group in groupedTravelAllowances"
                            :key="group.date"
                        >
                            <tr class="border-t border-border bg-muted/30">
                                <td class="w-12 px-4 py-2">
                                    <Checkbox
                                        :id="`travel-allowance-group-${group.date}`"
                                        :model-value="groupSelectionState(group.records)"
                                        :disabled="group.records.every((record) => !isSubmittable(record.approval_status))"
                                        :aria-label="`Select all travel allowance records for ${group.date}`"
                                        @update:model-value="
                                            toggleGroupSelection(group.records, $event)
                                        "
                                    />
                                </td>

                                <td colspan="10" class="px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold">
                                            {{ formatDate(group.date) }}
                                        </span>

                                        <Button
                                            v-if="hasSelectedGroupRecords(group.records)"
                                            type="button"
                                            size="sm"
                                            @click="submitGroupForApproval(group.records)"
                                        >
                                            Submit For Approval
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-for="allowance in group.records"
                                :key="allowance.id"
                                class="border-t border-border"
                            >
                                <td class="w-12 px-4 py-3">
                                    <Checkbox
                                        :id="`travel-allowance-record-${allowance.id}`"
                                        :model-value="isTravelAllowanceSelected(allowance.id)"
                                        :aria-label="`Select travel allowance record for ${allowance.name}`"
                                        @update:model-value="
                                            toggleTravelAllowanceSelection(allowance, $event)
                                        "
                                    />
                                </td>
                                <td class="px-4 py-3 align-top">
                                    {{ formatDate(allowance.date) }}
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    {{ allowance.name }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    {{ allowance.company }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div
                                        class="max-w-full overflow-hidden text-ellipsis whitespace-nowrap leading-5"
                                        :title="allowance.description || '-'"
                                    >
                                        {{ allowance.description || '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-md border px-2 py-1 text-xs font-medium"
                                        :class="approvalStatusClass(allowance.approval_status)"
                                    >
                                        {{ formatApprovalStatus(allowance.approval_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ allowance.quantity }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ formatCurrency(allowance.rate) }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    {{ formatCurrency(allowance.amount) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            aria-label="Edit travel allowance"
                                            :disabled="isApprovalLocked(allowance.approval_status)"
                                            @click="emit('edit', allowance)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>

                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-destructive hover:text-destructive disabled:text-muted-foreground"
                                            aria-label="Delete travel allowance"
                                            :disabled="isApprovalLocked(allowance.approval_status)"
                                            @click="emit('delete', allowance)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="filteredTravelAllowances.length === 0">
                            <td
                                colspan="10"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No travel allowance records found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="shrink-0 border-t bg-background px-4 py-3">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <p class="text-sm text-muted-foreground">
                Showing {{ firstRow }} to {{ lastRow }} of
                {{ filteredTravelAllowances.length }} travel allowance records
            </p>

            <Select v-model="rowsPerPage">
                <SelectTrigger class="h-9 w-24">
                    <SelectValue />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value="15">15</SelectItem>
                    <SelectItem value="25">25</SelectItem>
                    <SelectItem value="50">50</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="item in paginationItems"
                        :key="item.key"
                        type="button"
                        class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm"
                        :class="{
                            'bg-primary text-primary-foreground': item.active,
                            'pointer-events-none opacity-50': item.disabled,
                        }"
                        :disabled="item.disabled"
                        @click="goToPage(item)"
                    >
                        {{ item.label }}
                    </button>
                </div>
            </div>
        </div>
        </div>
        <ExportPdfDialog
            v-model:open="exportDialogOpen"
            title="Export Travel Allowance PDF"
            description="Select the travel allowance date range you want to include in the report."
            @export="exportTravelAllowanceAsPdf"
        />
    </div>
</template>
