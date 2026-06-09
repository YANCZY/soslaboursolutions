<script setup lang="ts">
import { Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input';



type AttendanceUser = {
    first_name: string;
    last_name: string;
};

type Attendance = {
    id: number;
    check_in_date: string;
    check_in_time: string | null;
    check_out_time: string | null;
    lunch_start_time: string | null;
    lunch_end_time: string | null;
    user?: AttendanceUser | null;
};

const props = defineProps<{
    attendanceRecords: Attendance[];
}>();

const search = ref('');

const formatUserName = (user?: AttendanceUser | null) => {
    if (!user) {
        return '-';
    }

    return [user.first_name, user.last_name].filter(Boolean).join(' ') || '-';
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }).format(new Date(`${date}T00:00:00`));
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

const filteredAttendanceRecords = computed(() => {
    const value = search.value.trim().toLowerCase();

    if (!value) {
        return props.attendanceRecords;
    }

    return props.attendanceRecords.filter((record) => {
        const userName = formatUserName(record.user).toLowerCase();
        const date = formatDate(record.check_in_date).toLowerCase();
        const checkIn = formatTime(record.check_in_time).toLowerCase();
        const checkOut = formatTime(record.check_out_time).toLowerCase();
        const lunchStart = formatTime(record.lunch_start_time).toLowerCase();
        const lunchEnd = formatTime(record.lunch_end_time).toLowerCase();

        return [
            userName,
            date,
            checkIn,
            checkOut,
            lunchStart,
            lunchEnd,
        ].some((field) => field.includes(value));
    });
});
</script>

<template>
    <div class="mx-auto w-[96%] space-y-3">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold tracking-tight">
                Attendance
            </h2>

            <div
                class="relative w-44 transition-[width] duration-200 ease-in-out hover:w-64 focus-within:w-64"
            >
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search attendance..."
                    class="h-8 pl-8 text-sm"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-md border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-weight-bold">Name</th>
                            <th class="px-4 py-3 font-weight-bold">Date</th>
                            <th class="px-4 py-3 font-weight-bold">Check In</th>
                            <th class="px-4 py-3 font-weight-bold">Check Out</th>
                            <th class="px-4 py-3 font-weight-bold">Lunch Start</th>
                            <th class="px-4 py-3 font-weight-bold">Lunch End</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="record in filteredAttendanceRecords"
                            :key="record.id"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ formatUserName(record.user) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatDate(record.check_in_date) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatTime(record.check_in_time) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatTime(record.check_out_time) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatTime(record.lunch_start_time) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatTime(record.lunch_end_time) }}
                            </td>
                        </tr>

                        <tr v-if="filteredAttendanceRecords.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No attendance records found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
