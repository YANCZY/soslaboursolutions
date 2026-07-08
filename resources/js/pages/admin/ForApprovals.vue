<script setup lang="ts">
import { Head } from '@inertiajs/vue3';



import ForApprovalListView from '@/views/admin/ForApprovalListView.vue';

type AttendanceApprovalRecord = {
    id: number;
    date: string;
    name: string;
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

defineProps<{
    attendanceApprovalRecords: AttendanceApprovalRecord[];
    travelAllowanceApprovalRecords: TravelAllowanceApprovalRecord[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'For Approvals',
            },
        ],
    },
});
</script>

<template>
    <Head title="For Approvals" />

    <div class="absolute inset-x-0 top-16 bottom-0 flex min-h-0 flex-col overflow-hidden p-4">
        <ForApprovalListView
            :attendance-approval-records="attendanceApprovalRecords"
            :travel-allowance-approval-records="travelAllowanceApprovalRecords"
        />
    </div>
</template>
