<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, EllipsisVertical, FileText, Filter, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import ExportPdfDialog from '@/components/ExportPdfDialog.vue';

import { Button } from '@/components/ui/button';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { exportBrandedPdf } from '@/lib/exportBrandedPdf';

const csrfToken = () =>
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

type AttendanceApprovalRecord = {
    id: number;
    date: string;
    name: string;
    covering_for?: string | null;
    company: string;
    check_in: string | null;
    check_out: string | null;
    total_work_hours: number;
    total_overtime: number;
    approval_status: 'pending' | 'approved' | 'rejected' | null;
};

type TravelAllowanceApprovalRecord = {
    id: number;
    date: string;
    name: string;
    company: string;
    description: string;
    rate: number;
    quantity: number;
    amount: number;
    approval_status: 'pending' | 'approved' | 'rejected' | null;
};

const props = defineProps<{
    attendanceApprovalRecords: AttendanceApprovalRecord[];
    travelAllowanceApprovalRecords: TravelAllowanceApprovalRecord[];
}>();

const search = ref('');
const initialApprovalType = new URLSearchParams(window.location.search).get('approval_type');

const selectedApprovalType = ref<'attendance' | 'travel-allowance'>(
    initialApprovalType === 'travel-allowance' ? 'travel-allowance' : 'attendance',
);
const showStatusFilter = ref(false);
const selectedStatus = ref<'all' | 'pending' | 'approved' | 'rejected'>('all');
const exportDialogOpen = ref(false);

const formatTime = (time: string | null) => {
    if (!time) {
        return '-';
    }

    const [hours, minutes] = time.split(':');

    return new Date(2000, 0, 1, Number(hours), Number(minutes))
        .toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        });
};

const formatDate = (value: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));
};

const isActionLocked = (status?: string | null) => {
    return status === 'approved' || status === 'rejected';
};

const formatDuration = (seconds: number) => {
    if (!seconds || seconds <= 0) {
        return '-';
    }

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    return `${hours}h ${minutes}m`;
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-AU', {
        style: 'currency',
        currency: 'AUD',
    }).format(value);
};

const formatApprovalStatus = (status?: string | null) => {
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

const filteredAttendanceApprovalRecords = computed(() => {
    const value = search.value.trim().toLowerCase();

    return props.attendanceApprovalRecords.filter((record) => {
        const matchesSearch =
            !value ||
            Object.values(record).some((field) =>
                String(field).toLowerCase().includes(value),
            );

        const matchesStatus =
            selectedStatus.value === 'all' ||
            record.approval_status === selectedStatus.value;

        return matchesSearch && matchesStatus;
    });
});

const filteredTravelAllowanceApprovalRecords = computed(() => {
    const value = search.value.trim().toLowerCase();

    return props.travelAllowanceApprovalRecords.filter((record) => {
        const fields = [
            formatDate(record.date),
            record.name,
            record.company,
            record.description,
            formatCurrency(record.rate),
            String(record.quantity),
            formatCurrency(record.amount),
            formatApprovalStatus(record.approval_status),
        ];

        const matchesSearch =
            !value ||
            fields.some((field) =>
                String(field).toLowerCase().includes(value),
            );

        const matchesStatus =
            selectedStatus.value === 'all' ||
            record.approval_status === selectedStatus.value;

        return matchesSearch && matchesStatus;
    });
});

const visibleApprovalCount = computed(() =>
    selectedApprovalType.value === 'attendance'
        ? filteredAttendanceApprovalRecords.value.length
        : filteredTravelAllowanceApprovalRecords.value.length,
);

const exportDialogTitle = computed(() =>
    selectedApprovalType.value === 'attendance'
        ? 'Export For Approval Attendance PDF'
        : 'Export For Approval Travel Allowance PDF',
);

const openExportDialog = () => {
    exportDialogOpen.value = true;
};

const exportForApprovalsAsPdf = (range: { startDate: string; endDate: string }) => {
    if (selectedApprovalType.value === 'attendance') {
        const records = filteredAttendanceApprovalRecords.value.filter((record) =>
            record.date >= range.startDate &&
            record.date <= range.endDate,
        );

        if (records.length === 0) {
            toast.error('No attendance approval records found for the selected date range.');

            return;
        }

        const result = exportBrandedPdf({
            title: 'For Approval Attendance Report',
            records,
            columns: [
                { header: 'Date', value: (record) => formatDate(record.date) },
                { header: 'Name', value: (record) => record.name },
                { header: 'Company', value: (record) => record.company },
                { header: 'Status', value: (record) => formatApprovalStatus(record.approval_status) },
                { header: 'Check In', value: (record) => formatTime(record.check_in) },
                { header: 'Check Out', value: (record) => formatTime(record.check_out) },
                { header: 'Total Work Hours', value: (record) => formatDuration(record.total_work_hours) },
                { header: 'Total Overtime', value: (record) => formatDuration(record.total_overtime) },
            ],
        });

        if (result === 'blocked') {
            toast.error('Please allow pop-ups to export the approval PDF.');

            return;
        }

        exportDialogOpen.value = false;

        return;
    }

    const records = filteredTravelAllowanceApprovalRecords.value.filter((record) =>
        record.date >= range.startDate &&
        record.date <= range.endDate,
    );

    if (records.length === 0) {
        toast.error('No travel allowance approval records found for the selected date range.');

        return;
    }

    const result = exportBrandedPdf({
        title: 'For Approval Travel Allowance Report',
        records,
        columns: [
            { header: 'Date', value: (record) => formatDate(record.date) },
            { header: 'Name', value: (record) => record.name },
            { header: 'Company', value: (record) => record.company },
            { header: 'Description', value: (record) => record.description || '-' },
            { header: 'Status', value: (record) => formatApprovalStatus(record.approval_status) },
            { header: 'Quantity', value: (record) => record.quantity },
            { header: 'Rate', value: (record) => formatCurrency(record.rate) },
            { header: 'Amount', value: (record) => formatCurrency(record.amount) },
        ],
    });

    if (result === 'blocked') {
        toast.error('Please allow pop-ups to export the approval PDF.');

        return;
    }

    exportDialogOpen.value = false;
};

const refreshApprovalRecords = (successMessage: string) => {
    router.reload({
        only: ['attendanceApprovalRecords', 'travelAllowanceApprovalRecords'],
        preserveScroll: true,
        onSuccess: () => {
            toast.success(successMessage);
        },
    });
};

const approveRecord = async (id: number) => {
    try {
        const isAttendance = selectedApprovalType.value === 'attendance';

        const url = isAttendance
            ? `/attendance/${id}/approve`
            : `/travel-allowance/${id}/approve`;

        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            body: JSON.stringify({}),
        });

        if (!response.ok) {
            const error = await response.text();

            throw new Error(error || 'Failed to approve record.');
        }

        const successMessage = isAttendance
            ? 'You approved Attendance.'
            : 'You approved Travel Allowance.';

        refreshApprovalRecords(successMessage);
    } catch (error) {
        console.error(error);
        toast.error('Unable to approve record.');
    }
};

const rejectRecord = async (id: number) => {
    try {
        const isAttendance = selectedApprovalType.value === 'attendance';

        const url = isAttendance
            ? `/attendance/${id}/reject`
            : `/travel-allowance/${id}/reject`;

        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            body: JSON.stringify({}),
        });

        if (!response.ok) {
            const error = await response.text();

            throw new Error(error || 'Failed to reject record.');
        }

        const successMessage = isAttendance
            ? 'You rejected Attendance.'
            : 'You rejected Travel Allowance.';

        refreshApprovalRecords(successMessage);
    } catch (error) {
        console.error(error);
        toast.error('Unable to reject record.');
    }
};
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col gap-4">
        <div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-64">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search approvals..."
                        class="pl-9"
                    />
                </div>

                <div class="relative">
                    <Button variant="outline" size="icon" title="Filter approvals"
                        @click="showStatusFilter = !showStatusFilter">
                        <Filter class="size-4" />
                    </Button>

                    <div v-if="showStatusFilter"
                        class="absolute top-11 left-0 z-20 w-52 rounded-md border bg-popover p-3 shadow-md">
                        <div class="space-y-2">
                            <p class="text-sm font-medium">Status</p>

                            <Select v-model="selectedStatus">
                                <SelectTrigger class="w-full">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="rejected">Rejected</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <Select v-model="selectedApprovalType">
                    <SelectTrigger class="h-9 w-full sm:w-[190px]">
                        <SelectValue />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="attendance">Attendance</SelectItem>
                        <SelectItem value="travel-allowance">Travel Allowance</SelectItem>
                    </SelectContent>
                </Select>

                <DropdownMenu>
                    <DropdownMenuTrigger :as-child="true">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            aria-label="For approval actions"
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
            <div class="min-h-0 flex-1 overflow-auto">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="sticky top-0 z-10 bg-muted text-left text-muted-foreground">
                        <tr v-if="selectedApprovalType === 'attendance'">
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Company</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Check-In</th>
                            <th class="px-4 py-3 font-medium">Check Out</th>
                            <th class="px-4 py-3 font-medium">Total Work Hours</th>
                            <th class="px-4 py-3 font-medium">Total Overtime</th>
                            <th class="w-28 px-4 py-3 text-center font-medium">Action</th>
                        </tr>

                        <tr v-else>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Company</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Quantity</th>
                            <th class="px-4 py-3 text-right font-medium">Rate</th>
                            <th class="px-4 py-3 text-right font-medium">Amount</th>
                            <th class="w-28 px-4 py-3 text-center font-medium">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template v-if="selectedApprovalType === 'attendance'">
                            <tr
v-for="record in filteredAttendanceApprovalRecords" :key="`attendance-${record.id}`"
                                class="border-t border-border">
                                <td class="px-4 py-3">
                                    {{ formatDate(record.date) }}
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    <div class="flex items-center gap-2 whitespace-nowrap">
                                        <span>{{ record.name }}</span>

                                        <TooltipProvider v-if="record.covering_for" :delay-duration="100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <span
                                                        class="inline-flex shrink-0 cursor-default items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300"
                                                    >
                                                        Shift Cover
                                                    </span>
                                                </TooltipTrigger>

                                                <TooltipContent
                                                    side="top"
                                                    align="center"
                                                    :side-offset="4"
                                                    class="border border-border bg-popover px-3 py-2 text-popover-foreground shadow-md"
                                                >
                                                    <div class="space-y-1 text-sm">
                                                        <div class="font-medium">Covering For</div>
                                                        <div class="text-muted-foreground">
                                                            {{ record.covering_for }}
                                                        </div>
                                                    </div>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    {{ record.company }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-md border px-2 py-1 text-xs font-medium"
                                        :class="approvalStatusClass(record.approval_status)">
                                        {{ formatApprovalStatus(record.approval_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ formatTime(record.check_in) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ formatTime(record.check_out) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ formatDuration(record.total_work_hours) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ formatDuration(record.total_overtime) }}
                                </td>
                                <td class="w-28 px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <Button type="button" size="icon-sm" aria-label="Approve record" title="Approve"
                                            :disabled="isActionLocked(record.approval_status)"
                                            @click="approveRecord(record.id)">
                                            <Check class="size-4" />
                                        </Button>

                                        <Button type="button" variant="destructive" size="icon-sm"
                                            aria-label="Reject record" title="Reject"
                                            :disabled="isActionLocked(record.approval_status)"
                                            @click="rejectRecord(record.id)">
                                            <X class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template v-else>
                            <tr v-for="record in filteredTravelAllowanceApprovalRecords"
                                :key="`travel-allowance-${record.id}`" class="border-t border-border">
                                <td class="px-4 py-3">
                                    {{ formatDate(record.date) }}
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    {{ record.name }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ record.company }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ record.description || '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-md border px-2 py-1 text-xs font-medium"
                                        :class="approvalStatusClass(record.approval_status)">
                                        {{ formatApprovalStatus(record.approval_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ record.quantity }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ formatCurrency(record.rate) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ formatCurrency(record.amount) }}
                                </td>
                                <td class="w-28 px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <Button type="button" size="icon-sm" aria-label="Approve record" title="Approve"
                                            :disabled="isActionLocked(record.approval_status)"
                                            @click="approveRecord(record.id)">
                                            <Check class="size-4" />
                                        </Button>

                                        <Button type="button" variant="destructive" size="icon-sm"
                                            aria-label="Reject record" title="Reject"
                                            :disabled="isActionLocked(record.approval_status)"
                                            @click="rejectRecord(record.id)">
                                            <X class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="visibleApprovalCount === 0">
                            <td
colspan="9"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No approval records found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="shrink-0 border-t bg-background px-4 py-3">
                <p class="text-sm text-muted-foreground">
                    Showing {{ visibleApprovalCount }} approval records
                </p>
            </div>
        </div>
        <ExportPdfDialog
            v-model:open="exportDialogOpen"
            :title="exportDialogTitle"
            description="Select the approval date range you want to include in the report."
            @export="exportForApprovalsAsPdf"
        />
    </div>
</template>
