<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlarmClockOff, Clock, Coffee } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

const page = usePage();

type Attendance = {
    id: number;
    check_in_date: string;
    status: 'checked_in' | 'lunch_break' | 'checked_out';
    check_in_time: string | null;
    check_out_time: string | null;
    lunch_start_time: string | null;
    lunch_end_time: string | null;
    total_working_seconds: number;
};

const props = defineProps<{
    attendance: Attendance | null;
}>();

const emit = defineEmits<{
    'attendance-updated': [];
}>();

const activeAttendance = ref<Attendance | null>(props.attendance);

const currentDateTime = ref(new Date());
const checkInAt = computed(() => {
    if (!activeAttendance.value?.check_in_date || !activeAttendance.value.check_in_time) {
        return null;
    }

    const checkInDateTime = new Date(
        `${activeAttendance.value.check_in_date} ${activeAttendance.value.check_in_time}`,
    );

    return Number.isNaN(checkInDateTime.getTime()) ? null : checkInDateTime;
});

let currentTimeTimer: number | undefined;

const authUser = computed(() => page.props.auth.user);


const authUserFullName = computed(() =>
    [authUser.value.first_name, authUser.value.last_name]
        .filter(Boolean)
        .join(' '),
);

const userInitials = computed(() =>
    [authUser.value.first_name, authUser.value.last_name]
        .filter(Boolean)
        .map((name) => name.charAt(0))
        .join('')
        .toUpperCase(),
);

const greeting = computed(() => {
    const hour = currentDateTime.value.getHours();

    if (hour < 12) {
        return 'Good Morning';
    }

    if (hour < 18) {
        return 'Good Afternoon';
    }

    return 'Good Evening';
});

const formattedCurrentDateTime = computed(() => {
    const time = currentDateTime.value.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });

    const date = currentDateTime.value.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

    return `${time}, ${date}`;
});

const formattedPunchInTime = computed(() => {
    if (!checkInAt.value) {
        return 'Not checked in yet';
    }

    return `Clocked in: ${checkInAt.value.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    })}`;
});

const checkedIn = computed(() =>
    activeAttendance.value?.status === 'checked_in' ||
    activeAttendance.value?.status === 'lunch_break',
);

const onLunchBreak = computed(() =>
    activeAttendance.value?.status === 'lunch_break',
);

const formatSeconds = (totalSeconds: number) => {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes
        .toString()
        .padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};

const formattedWorkClock = computed(() => {
    if (activeAttendance.value?.status === 'checked_out') {
        return formatSeconds(activeAttendance.value.total_working_seconds ?? 0);
    }

    if (!checkInAt.value || Number.isNaN(checkInAt.value.getTime())) {
        return '00:00:00';
    }

    const elapsedSeconds = Math.max(
        0,
        Math.floor(
            (currentDateTime.value.getTime() - checkInAt.value.getTime()) /
                1000,
        ),
    );

    return formatSeconds(elapsedSeconds);
});

const postAttendanceAction = async (url: string) => {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Timezone': Intl.DateTimeFormat().resolvedOptions().timeZone,
            'X-CSRF-TOKEN':
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
        },
    });

    if (!response.ok) {

    console.error(await response.text());

    return;
}

    const data = await response.json();

    activeAttendance.value = data.attendance;
    emit('attendance-updated');
};

const toggleCheckIn = async () => {
    if (checkedIn.value) {
        await postAttendanceAction('/attendance/check-out');

        return;
    }

    await postAttendanceAction('/attendance/check-in');
};

const toggleLunchBreak = async () => {
    if (onLunchBreak.value) {
        await postAttendanceAction('/attendance/lunch/end');

        return;
    }

    await postAttendanceAction('/attendance/lunch/start');
};

onMounted(() => {

    currentTimeTimer = window.setInterval(() => {
        currentDateTime.value = new Date();
    }, 1000);
});

onBeforeUnmount(() => {
    if (currentTimeTimer) {
        window.clearInterval(currentTimeTimer);
    }
});

</script>

<template>
    <Card class="overflow-hidden">
                <CardContent
                    class="flex flex-col items-center gap-5 p-4 text-center sm:p-6"
                >
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ greeting }}, {{ authUser.first_name }}
                        </p>

                        <p class="text-xl font-semibold tracking-tight">
                            {{ formattedCurrentDateTime }}
                        </p>
                    </div>

                    <Avatar
                        class="size-28 border-4 border-primary/20 shadow-sm"
                    >
                        <AvatarImage
                            v-if="authUser.avatar"
                            :src="authUser.avatar"
                            :alt="authUserFullName"
                        />
                        <AvatarFallback class="text-2xl font-semibold">
                            {{ userInitials }}
                        </AvatarFallback>
                    </Avatar>

                    <div
                        class="flex w-full items-center justify-center gap-2 rounded-md border border-border bg-muted/40 px-4 py-3 text-sm font-medium text-foreground sm:w-auto dark:bg-muted/20"
                    >
                        <Clock class="size-4 shrink-0 text-primary" />
                        <span>{{ formattedPunchInTime }}</span>
                        <p v-if="onLunchBreak" class="text-sm font-medium text-amber-600">
                         Lunch Break
                        </p>
                    </div>



                    <div class="flex w-full items-center gap-2">
                        <Button
                            type="button"
                            class="h-11 flex-1"
                            :class="checkedIn ? 'bg-red-600 text-white hover:bg-red-700' : ''"
                            @click="toggleCheckIn"
                        >
                            <AlarmClockOff
                                v-if="checkedIn"
                                class="size-4 shrink-0"
                            />
                            <Clock v-else class="size-4 shrink-0" />
                            <span class="text-lg tabular-nums sm:text-xl">{{
                                formattedWorkClock
                            }}</span>
                            <!-- <span>
                                {{ checkedIn ? 'Check Out' : 'Check In'}}
                            </span> -->
                        </Button>

                        <Button
                            v-if="checkedIn"
                            type="button"
                            variant="outline"
                            size="icon"
                            class="size-11"
                            :class="
                                onLunchBreak
                                    ? 'border-amber-500/40 bg-amber-100 text-amber-800 hover:bg-amber-200 dark:border-amber-400/40 dark:bg-amber-400/15 dark:text-amber-300 dark:hover:bg-amber-400/25'
                                    : 'dark:border-border dark:bg-muted/20 dark:text-muted-foreground dark:hover:bg-muted/40'
                            "
                            @click="toggleLunchBreak"
                        >
                            <Coffee class="size-4" />
                        </Button>
                    </div>
                </CardContent>
            </Card>
</template>
