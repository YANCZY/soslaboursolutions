<script setup lang="ts">
import { Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';



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
    total_working_seconds: number | null;
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

const REGULAR_WORK_SECONDS = 8 * 60 * 60;

const formatDuration = (seconds: number | null | undefined) => {
    if (!seconds || seconds <= 0) {
        return '-';
    }

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    return `${hours}h ${minutes}m`;
};

const secondsBetweenTimes = (start: string | null, end: string | null) => {
    if (!start || !end) {
        return 0;
    }

    const [startHours, startMinutes, startSeconds = '0'] = start.split(':');
    const [endHours, endMinutes, endSeconds = '0'] = end.split(':');

    const startTotal =
        Number(startHours) * 3600 +
        Number(startMinutes) * 60 +
        Number(startSeconds);

    const endTotal =
        Number(endHours) * 3600 +
        Number(endMinutes) * 60 +
        Number(endSeconds);

    return Math.max(0, endTotal - startTotal);
};

const totalLunchBreakSeconds = (record: Attendance) => {
    return secondsBetweenTimes(record.lunch_start_time, record.lunch_end_time);
};

const totalOvertimeSeconds = (record: Attendance) => {
    return Math.max(0, (record.total_working_seconds ?? 0) - REGULAR_WORK_SECONDS);
};

const addSecondsToTime = (time: string | null, seconds: number) => {
    if (!time) {
        return null;
    }

    const [hours, minutes, timeSeconds = '0'] = time.split(':');

    const date = new Date(2000, 0, 1, Number(hours), Number(minutes), Number(timeSeconds));
    date.setSeconds(date.getSeconds() + seconds);

    return date.toTimeString().slice(0, 8);
};

const lunchBreakDetails = (record: Attendance) => {
    if (!record.lunch_start_time || !record.lunch_end_time) {
        return [
            {
                label: 'Lunch Break',
                value: 'No lunch break recorded',
            },
        ];
    }

    return [
        {
            label: 'Lunch Break Start',
            value: formatTime(record.lunch_start_time),
        },
        {
            label: 'Lunch Break End',
            value: formatTime(record.lunch_end_time),
        },
    ];
};

const overtimeStartTime = (record: Attendance) => {
    const lunchSeconds = totalLunchBreakSeconds(record);

    return addSecondsToTime(
        record.check_in_time,
        REGULAR_WORK_SECONDS + lunchSeconds,
    );
};

const overtimeDetails = (record: Attendance) => {
    if (totalOvertimeSeconds(record) <= 0 || !record.check_out_time) {
        return [
            {
                label: 'Overtime',
                value: 'No overtime recorded',
            },
        ];
    }

    return [
        {
            label: 'Overtime Start',
            value: formatTime(overtimeStartTime(record)),
        },
        {
            label: 'Overtime End',
            value: formatTime(record.check_out_time),
        },
    ];
};



const filteredAttendanceRecords = computed(() => {
    const value = search.value.trim().toLowerCase();

    const sortedRecords = [...props.attendanceRecords].sort((a, b) => {
        const first = `${a.check_in_date} ${a.check_in_time ?? '00:00:00'}`;
        const second = `${b.check_in_date} ${b.check_in_time ?? '00:00:00'}`;

        return second.localeCompare(first);
    });

    if (!value) {
        return sortedRecords;
    }

    return sortedRecords.filter((record) => {
        const userName = formatUserName(record.user).toLowerCase();
        const date = formatDate(record.check_in_date).toLowerCase();
        const checkIn = formatTime(record.check_in_time).toLowerCase();
        const checkOut = formatTime(record.check_out_time).toLowerCase();
        const totalWorkHours = formatDuration(record.total_working_seconds).toLowerCase();
        const totalLunchBreak = formatDuration(totalLunchBreakSeconds(record)).toLowerCase();
        const totalOvertime = formatDuration(totalOvertimeSeconds(record)).toLowerCase();

        return [
            userName,
            date,
            checkIn,
            checkOut,
            totalWorkHours,
            totalLunchBreak,
            totalOvertime,
        ].some((field) => field.includes(value));
    });
});


const groupedAttendanceRecords = computed(() => {
    const groups = new Map<string, Attendance[]>();

    for (const record of filteredAttendanceRecords.value) {
        if (!groups.has(record.check_in_date)) {
            groups.set(record.check_in_date, []);
        }

        groups.get(record.check_in_date)?.push(record);
    }

    return Array.from(groups, ([date, records]) => ({
        date,
        records,
    }));
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
                            <th class="px-4 py-3 font-weight-bold">Total Work Hours</th>
                            <th class="px-4 py-3 font-weight-bold">Total Lunch Break</th>
                            <th class="px-4 py-3 font-weight-bold">Total Overtime</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template
                            v-for="group in groupedAttendanceRecords"
                            :key="group.date"
                        >
                            <tr class="border-t border-border bg-muted/30">
                                <td
                                    colspan="7"
                                    class="px-4 py-2 text-sm font-semibold"
                                >
                                    {{ formatDate(group.date) }}
                                </td>
                            </tr>

                            <tr
                                v-for="record in group.records"
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
                                    {{ formatDuration(record.total_working_seconds) }}
                                </td>
                                <td class="px-4 py-3">
                                    <TooltipProvider :delay-duration="100">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="inline-flex min-h-8 max-w-full items-center rounded-md px-2 text-left transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                >
                                                    {{ formatDuration(totalLunchBreakSeconds(record)) }}
                                                </button>
                                            </TooltipTrigger>

                                            <TooltipContent
                                                side="top"
                                                align="center"
                                                class="max-w-[calc(100vw-2rem)] border border-border bg-popover px-3 py-2 text-popover-foreground shadow-md"
                                            >
                                                <div class="space-y-1">
                                                    <div
                                                        v-for="detail in lunchBreakDetails(record)"
                                                        :key="detail.label"
                                                        class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-2"
                                                    >
                                                        <span class="font-medium">{{ detail.label }}:</span>
                                                        <span class="text-muted-foreground">{{ detail.value }}</span>
                                                    </div>
                                                </div>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </td>
                                <td class="px-4 py-3">
                                    <TooltipProvider :delay-duration="100">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="inline-flex min-h-8 max-w-full items-center rounded-md px-2 text-left transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                >
                                                    {{ formatDuration(totalOvertimeSeconds(record)) }}
                                                </button>
                                            </TooltipTrigger>

                                            <TooltipContent
                                                side="top"
                                                align="center"
                                                class="max-w-[calc(100vw-2rem)] border border-border bg-popover px-3 py-2 text-popover-foreground shadow-md"
                                            >
                                                <div class="space-y-1">
                                                    <div
                                                        v-for="detail in overtimeDetails(record)"
                                                        :key="detail.label"
                                                        class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-2"
                                                    >
                                                        <span class="font-medium">{{ detail.label }}:</span>
                                                        <span class="text-muted-foreground">{{ detail.value }}</span>
                                                    </div>
                                                </div>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="filteredAttendanceRecords.length === 0">
                            <td
                                colspan="7"
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
