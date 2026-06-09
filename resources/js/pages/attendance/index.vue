<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import AttendanceTable from '@/views/attendance/AttendanceTable.vue';
import CheckInCard from '@/views/attendance/CheckInCard.vue';
import TotalWorkingHoursCard from '@/views/attendance/TotalWorkingHoursCard.vue';


defineProps<{
    todayAttendance: Record<string, unknown> | null;
    weeklyAttendance: Record<string, unknown>[];
}>();

const refreshAttendance = () => {
    router.reload({
        only: ['todayAttendance', 'weeklyAttendance'],
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
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,24rem)_1fr]">
            <CheckInCard
                :attendance="todayAttendance"
                @attendance-updated="refreshAttendance"
            />
            <TotalWorkingHoursCard :weekly-attendance="weeklyAttendance" />
        </div>

        <Card>
             <AttendanceTable :attendance-records="weeklyAttendance" />
        </Card>
    </div>
</template>
