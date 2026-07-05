<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Card, CardContent } from '@/components/ui/card';

type Attendance = {
    id: number;
    check_in_date: string;
    status: 'checked_in' | 'lunch_break' | 'checked_out';
    approval_status: 'pending' | 'approved' | 'rejected' | null;
    check_in_time: string | null;
    check_out_time: string | null;
    lunch_start_time: string | null;
    lunch_end_time: string | null;
    total_working_seconds: number;
};

const props = defineProps<{
    weeklyAttendance: Attendance[];
}>();

const currentDateTime = ref(new Date());
let timer: number | undefined;

const today = new Date();

const weekStart = new Date(today);
weekStart.setHours(0, 0, 0, 0);

const currentDay = today.getDay(); // Sunday = 0, Monday = 1
const mondayOffset = currentDay === 0 ? -6 : 1 - currentDay;

weekStart.setDate(today.getDate() + mondayOffset);

const dayFormatter = new Intl.DateTimeFormat('en-US', {
    weekday: 'short',
});

const dateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: '2-digit',
});

const dateKeyFormatter = new Intl.DateTimeFormat('en-CA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
});

const formatSeconds = (totalSeconds: number) => {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);

    return `${hours.toString().padStart(2, '0')}:${minutes
        .toString()
        .padStart(2, '0')}`;
};

const parseDateTime = (date: string, time: string | null) => {
    if (!time) {
        return null;
    }

    const parsed = new Date(`${date} ${time}`);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const secondsBetween = (start: Date | null, end: Date | null) => {
    if (!start || !end) {
        return 0;
    }

    return Math.max(0, Math.floor((end.getTime() - start.getTime()) / 1000));
};

const liveWorkedSeconds = (attendance: Attendance) => {
    const checkInAt = parseDateTime(
        attendance.check_in_date,
        attendance.check_in_time,
    );

    const checkOutAt = parseDateTime(
        attendance.check_in_date,
        attendance.check_out_time,
    );

    const lunchStartAt = parseDateTime(
        attendance.check_in_date,
        attendance.lunch_start_time,
    );

    const lunchEndAt = parseDateTime(
        attendance.check_in_date,
        attendance.lunch_end_time,
    );

    const endAt =
        attendance.status === 'checked_out'
            ? checkOutAt
            : currentDateTime.value;

    const lunchSeconds =
        lunchStartAt && lunchEndAt
            ? secondsBetween(lunchStartAt, lunchEndAt)
            : 0;

    return Math.max(0, secondsBetween(checkInAt, endAt) - lunchSeconds);
};

const workdaySeconds = 8 * 60 * 60;

const progressPercent = (attendance: Attendance) => {
    const workedSeconds = liveWorkedSeconds(attendance);

    return Math.min(100, Math.max(0, (workedSeconds / workdaySeconds) * 100));
};

const progressFromSeconds = (seconds: number) => {
    return Math.min(100, Math.max(0, (seconds / workdaySeconds) * 100));
};

const timelineForRecords = (records: Attendance[]) => {
    const dots = [];
    const segments = [];

    let cursorSeconds = 0;

    for (const attendance of records) {
        const checkInAt = parseDateTime(
            attendance.check_in_date,
            attendance.check_in_time,
        );

        const lunchStartAt = parseDateTime(
            attendance.check_in_date,
            attendance.lunch_start_time,
        );

        const lunchEndAt = parseDateTime(
            attendance.check_in_date,
            attendance.lunch_end_time,
        );

        const checkOutAt = parseDateTime(
            attendance.check_in_date,
            attendance.check_out_time,
        );

        dots.push({
            key: `${attendance.id}-check-in`,
            class: 'bg-green-500',
            progress: progressFromSeconds(cursorSeconds),
        });

        const firstGreenEnd =
            lunchStartAt ??
            checkOutAt ??
            (attendance.status === 'checked_in' ? currentDateTime.value : null);

        const firstGreenSeconds = secondsBetween(checkInAt, firstGreenEnd);

        if (firstGreenSeconds > 0) {
            segments.push({
                key: `${attendance.id}-green-start`,
                class: 'bg-green-500',
                left: progressFromSeconds(cursorSeconds),
                width: progressFromSeconds(firstGreenSeconds),
            });
        }

        cursorSeconds += firstGreenSeconds;

        if (lunchStartAt) {
            dots.push({
                key: `${attendance.id}-lunch-start`,
                class: 'bg-orange-400',
                progress: progressFromSeconds(cursorSeconds),
            });
        }

        const orangeEnd =
            lunchEndAt ??
            (attendance.status === 'lunch_break' ? currentDateTime.value : null);

        const orangeSeconds = secondsBetween(lunchStartAt, orangeEnd);

        if (orangeSeconds > 0) {
            segments.push({
                key: `${attendance.id}-orange-lunch`,
                class: 'bg-orange-400',
                left: progressFromSeconds(cursorSeconds),
                width: progressFromSeconds(orangeSeconds),
            });
        }

        cursorSeconds += orangeSeconds;

        if (lunchEndAt) {
            dots.push({
                key: `${attendance.id}-lunch-end`,
                class: 'bg-green-500',
                progress: progressFromSeconds(cursorSeconds),
            });
        }

        const secondGreenEnd =
            checkOutAt ??
            (attendance.status === 'checked_in' && lunchEndAt
                ? currentDateTime.value
                : null);

        const secondGreenSeconds = secondsBetween(lunchEndAt, secondGreenEnd);

        if (secondGreenSeconds > 0) {
            segments.push({
                key: `${attendance.id}-green-after-lunch`,
                class: 'bg-green-500',
                left: progressFromSeconds(cursorSeconds),
                width: progressFromSeconds(secondGreenSeconds),
            });
        }

        cursorSeconds += secondGreenSeconds;

        if (checkOutAt) {
            dots.push({
                key: `${attendance.id}-check-out`,
                class: 'bg-red-500',
                progress: progressFromSeconds(cursorSeconds),
            });
        }
    }

    return { dots, segments };
};

const latestStatusForDay = (records: Attendance[]) => {
    return records.at(-1)?.status ?? null;
};

const lineClassForDay = (records: Attendance[]) => {
    const status = latestStatusForDay(records);

    if (status === 'lunch_break') {
        return 'bg-orange-400';
    }

    if (status === 'checked_out') {
        return 'bg-red-500';
    }

    if (status === 'checked_in') {
        return 'bg-green-500';
    }

    return 'bg-border';
};

const dotClassForDay = (records: Attendance[]) => {
    const status = latestStatusForDay(records);

    if (status === 'lunch_break') {
        return 'bg-orange-400';
    }

    if (status === 'checked_out') {
        return 'bg-red-500';
    }

    if (status === 'checked_in') {
        return 'bg-green-500';
    }

    return 'bg-muted-foreground/30';
};

const currentWeekDays = computed(() =>
    Array.from({ length: 7 }, (_, index) => {
        const date = new Date(weekStart);
        date.setDate(weekStart.getDate() + index);

        const key = dateKeyFormatter.format(date);

        const records = props.weeklyAttendance
            .filter((attendance) => attendance.check_in_date === key)
            .sort((a, b) => a.id - b.id);

        const totalSeconds = records.reduce(
            (sum, attendance) => sum + liveWorkedSeconds(attendance),
            0,
        );

        const latestRecord = records.at(-1) ?? null;

        const timeline = timelineForRecords(records);

        return {
            key,
            day: dayFormatter.format(date),
            date: dateFormatter.format(date),
            isToday: date.toDateString() === today.toDateString(),
            records,
            latestRecord,
            hoursWorked: formatSeconds(totalSeconds),
            progress: latestRecord ? progressPercent(latestRecord) : 0,
            lineClass: lineClassForDay(records),
            dotClass: dotClassForDay(records),
            dots: timeline.dots,
            segments: timeline.segments,
        };
    }),
);

onMounted(() => {
    timer = window.setInterval(() => {
        currentDateTime.value = new Date();
    }, 1000);
});

onBeforeUnmount(() => {
    if (timer) {
        window.clearInterval(timer);
    }
});
</script>

<template>
    <Card class="h-[27rem] overflow-hidden">
        <CardContent class="flex h-full flex-col p-4 sm:p-6">
            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-2">
                <div
                    v-for="day in currentWeekDays"
                    :key="day.key"
                    class="grid min-h-16 grid-cols-[5rem_1fr_6rem] items-center gap-4 rounded-sm bg-background px-4 py-3"
                >
                    <div class="text-center">
                        <div class="text-sm font-semibold text-foreground">
                            {{ day.day }}
                        </div>

                        <div
                            class="mx-auto mt-1 flex h-7 min-w-14 items-center justify-center rounded-md px-2 text-sm"
                            :class="
                                day.isToday
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ day.date }}
                        </div>
                    </div>

                    <div class="relative flex h-4 items-center">
                        <div class="absolute left-0 right-0 h-px bg-border" />

                        <div
                            v-for="segment in day.segments"
                            :key="segment.key"
                            class="absolute h-1 rounded-full transition-all duration-500"
                            :class="segment.class"
                            :style="{
                                left: `${segment.left}%`,
                                width: `${segment.width}%`,
                            }"
                        />

                        <span
                            v-for="dot in day.dots"
                            :key="dot.key"
                            class="absolute z-10 size-2 -translate-x-1/2 rounded-full"
                            :class="dot.class"
                            :style="{ left: `${dot.progress}%` }"
                        />
                    </div>

                    <div class="text-right">
                        <div class="font-semibold tabular-nums text-foreground">
                            {{ day.hoursWorked }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Hrs worked
                        </div>
                    </div>
                </div>
            </div>


        </CardContent>
    </Card>
</template>
