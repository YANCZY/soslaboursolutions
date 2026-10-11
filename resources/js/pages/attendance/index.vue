<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import type { LocationOption } from '@/types/location';
import AttendanceTable from '@/views/attendance/AttendanceTable.vue';
import CheckInCard from '@/views/attendance/CheckInCard.vue';
import TotalWorkingHoursCard from '@/views/attendance/TotalWorkingHoursCard.vue';

import type {
    Attendance,
} from '@/types/attendance';

const props = defineProps<{
    todayAttendance: Attendance | null;
    weeklyAttendance: Attendance[];
    attendanceRecords: Attendance[];
    locations: LocationOption[]
}>();

const refreshAttendance = () => {
    router.reload({
        only: ['todayAttendance', 'weeklyAttendance', 'attendanceRecords', 'locations'],
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
                :locations="props.locations"
                @attendance-updated="refreshAttendance"
            />
            <TotalWorkingHoursCard :weekly-attendance="weeklyAttendance" @attendance-updated="refreshAttendance" />
        </div>

        <Card>
            <AttendanceTable
                :attendance-records="props.attendanceRecords"
                :locations="props.locations"
                @attendance-updated="refreshAttendance"
            />
        </Card>
    </div>
</template>
