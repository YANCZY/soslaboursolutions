<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import AttendanceTable from '@/views/attendance/AttendanceTable.vue';
import CheckInCard from '@/views/attendance/CheckInCard.vue';
import TotalWorkingHoursCard from '@/views/attendance/TotalWorkingHoursCard.vue';

type Company = {
    id: number;
    company_name: string;
};

const props = defineProps<{
    todayAttendance: Record<string, unknown> | null;
    weeklyAttendance: Record<string, unknown>[];
    attendanceRecords: Record<string, unknown>[];
    companies: Company[];
}>();

const refreshAttendance = () => {
    router.reload({
        only: ['todayAttendance', 'weeklyAttendance', 'attendanceRecords', 'companies'],
        preserveScroll: true,
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Attendance',
            },
        ],
    },
});
</script>
<template>
    <Head title="Attendance" />
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-hidden p-4 sm:p-4">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,24rem)_1fr]">
            <CheckInCard
                :attendance="props.todayAttendance"
                :companies="props.companies"
                @attendance-updated="refreshAttendance"
            />
            <TotalWorkingHoursCard :weekly-attendance="weeklyAttendance" @attendance-updated="refreshAttendance" />
        </div>

        <Card>
            <AttendanceTable
                :attendance-records="props.attendanceRecords"
                :companies="props.companies"
                @attendance-updated="refreshAttendance"
            />
        </Card>
    </div>
</template>
