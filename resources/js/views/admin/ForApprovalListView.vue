<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type ApprovalStatus = 'pending' | 'approved' | 'rejected';

type ApprovalRecord = {
    id: number;
    date: string;
    name: string;
    company: string;
    check_in: string | null;
    check_out: string | null;
    total_work_hours: number;
    total_overtime: number;
    approval_status: ApprovalStatus;
};

const props = defineProps<{
    approvalRecords: ApprovalRecord[];
    filters: {
        status: ApprovalStatus;
    };
}>();

const search = ref('');

const statusFilter = ref<ApprovalStatus>(props.filters.status);

const changeStatusFilter = (status: ApprovalStatus) => {
    statusFilter.value = status;

    router.get(
        '/for-approvals',
        {
            status: status === 'pending' ? undefined : status,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['approvalRecords', 'filters'],
        },
    );
};

const processingRecordId = ref<number | null>(null);

const updateApprovalStatus = (
    record: ApprovalRecord,
    status: 'approved' | 'rejected',
) => {
    const action = status === 'approved' ? 'approve' : 'reject';

    processingRecordId.value = record.id;

    router.patch(
        `/for-approvals/${record.id}/${action}`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    status === 'approved'
                        ? 'Timesheet has been approved.'
                        : 'Timesheet has been rejected.',
                );
            },
            onError: () => {
                toast.error('Unable to update approval status.');
            },
            onFinish: () => {
                processingRecordId.value = null;
            },
        },
    );
};

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

const formatDuration = (seconds: number) => {
    if (!seconds || seconds <= 0) {
        return '-';
    }

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    return `${hours}h ${minutes}m`;
};

const formatApprovalStatus = (status: ApprovalStatus) => {
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

const approvalStatusClass = (status: ApprovalStatus) => {
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


const filteredApprovalRecords = computed(() => {
    const value = search.value.trim().toLowerCase();

    if (!value) {
        return props.approvalRecords;
    }

    return props.approvalRecords.filter((record) =>
        Object.values(record).some((field) =>
            String(field).toLowerCase().includes(value),
        ),
    );
});
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col gap-4">
        <div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div class="flex items-center gap-2">
                <div class="relative w-full sm:w-64">
                    <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search approvals..."
                        class="pl-9"
                    />
                </div>

                <Select
                    :model-value="statusFilter"
                    @update:model-value="changeStatusFilter($event as ApprovalStatus)"
                >
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="Filter" />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="pending">For Approval</SelectItem>
                        <SelectItem value="approved">Approved</SelectItem>
                        <SelectItem value="rejected">Rejected</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-md border bg-card">
            <div class="min-h-0 flex-1 overflow-auto">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="sticky top-0 z-10 bg-muted text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Company</th>
                            <th class="px-4 py-3 font-medium">Check-In</th>
                            <th class="px-4 py-3 font-medium">Check Out</th>
                            <th class="px-4 py-3 font-medium">Total Work Hours</th>
                            <th class="px-4 py-3 font-medium">Total Overtime</th>
                            <th class="min-w-[120px] px-4 py-3 font-medium">Status</th>
                            <th class="w-28 px-4 py-3 text-center font-medium">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="record in filteredApprovalRecords"
                            :key="record.id"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3">
                                {{ record.date }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ record.name }}
                            </td>
                            <td class="px-4 py-3">
                                {{ record.company }}
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
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center rounded-md border px-2 py-1 text-xs font-medium"
                                    :class="approvalStatusClass(record.approval_status)"
                                >
                                    {{ formatApprovalStatus(record.approval_status) }}
                                </span>
                            </td>
                            <td class="w-28 px-4 py-3">
                                <div
                                    v-if="record.approval_status === 'pending'"
                                    class="flex justify-center gap-2"
                                >
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        aria-label="Approve record"
                                        title="Approve"
                                        :disabled="processingRecordId === record.id"
                                        @click="updateApprovalStatus(record, 'approved')"
                                    >
                                        <Check class="size-4" />
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="icon-sm"
                                        aria-label="Reject record"
                                        title="Reject"
                                        :disabled="processingRecordId === record.id"
                                        @click="updateApprovalStatus(record, 'rejected')"
                                    >
                                        <X class="size-4" />
                                    </Button>
                                </div>
                                <span
                                    v-else
                                    class="block text-center text-xs text-muted-foreground"
                                >
                                    -
                                </span>
                            </td>
                        </tr>

                        <tr v-if="filteredApprovalRecords.length === 0">
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
                    Showing {{ filteredApprovalRecords.length }} approval records
                </p>
            </div>
        </div>
    </div>
</template>
