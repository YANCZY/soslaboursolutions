<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlarmClockOff, Clock, Coffee } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

const page = usePage();

const currentDateTime = ref(new Date());
const checkInAt = ref<Date | null>(null);
let currentTimeTimer: number | undefined;

const authUser = computed(() => page.props.auth.user);

const checkInStorageKey = computed(
    () => `attendance:${authUser.value.id}:check-in-at`,
);

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

const checkedIn = ref(false);
const onLunchBreak = ref(false);

const formattedWorkClock = computed(() => {
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

    const hours = Math.floor(elapsedSeconds / 3600);
    const minutes = Math.floor((elapsedSeconds % 3600) / 60);
    const seconds = elapsedSeconds % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes
        .toString()
        .padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

const toggleCheckIn = () => {
    checkedIn.value = !checkedIn.value;

    if (checkedIn.value) {
        checkInAt.value = new Date();
        window.localStorage.setItem(
            checkInStorageKey.value,
            checkInAt.value.toISOString(),
        );

        return;
    }

    onLunchBreak.value = false;
    checkInAt.value = null;
    window.localStorage.removeItem(checkInStorageKey.value);
};

const toggleLunchBreak = () => {
    onLunchBreak.value = !onLunchBreak.value;
};

onMounted(() => {
    const storedCheckInAt = window.localStorage.getItem(
        checkInStorageKey.value,
    );

    if (storedCheckInAt) {
        const parsedCheckInAt = new Date(storedCheckInAt);

        if (Number.isNaN(parsedCheckInAt.getTime())) {
            window.localStorage.removeItem(checkInStorageKey.value);
        } else {
            checkInAt.value = parsedCheckInAt;
            checkedIn.value = true;
        }
    }

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
                    </div>

                    <div class="flex w-full items-center gap-2">
                        <Button
                            type="button"
                            class="h-11 flex-1"
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
