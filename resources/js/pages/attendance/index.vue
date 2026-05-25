<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import CheckInCard from '@/views/attendance/CheckInCard.vue';
import TotalWorkingHoursCard from '@/views/attendance/TotalWorkingHoursCard.vue';

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
            <CheckInCard />
            <TotalWorkingHoursCard />
        </div>

        <Card>
            <!-- Attendance table -->
        </Card>
    </div>
</template>
