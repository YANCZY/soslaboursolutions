<script setup lang="ts">
import { Check, Filter, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type ApprovalRecord = {
    id: number;
    date: string;
    name: string;
    company: string;
    check_in: string | null;
    check_out: string | null;
    total_work_hours: number;
    total_overtime: number;
};

const props = defineProps<{
    approvalRecords: ApprovalRecord[];
}>();

const search = ref('');

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

                <Button variant="outline" size="icon" title="Filter approvals">
                    <Filter class="size-4" />
                </Button>
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
                            <td class="w-28 px-4 py-3">
                                <div class="flex justify-center gap-2">
                                    <Button
                                        type="button"
                                        size="icon-sm"
                                        aria-label="Approve record"
                                        title="Approve"
                                    >
                                        <Check class="size-4" />
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="icon-sm"
                                        aria-label="Reject record"
                                        title="Reject"
                                    >
                                        <X class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredApprovalRecords.length === 0">
                            <td
                                colspan="8"
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
