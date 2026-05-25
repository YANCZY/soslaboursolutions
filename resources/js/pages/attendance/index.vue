<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
} from '@/components/ui/card';

const page = usePage();

const currentDateTime = ref(new Date());
const checkInAt = ref<Date | null>(null);
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

    return `Punch In at ${checkInAt.value.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    })}`;
});


const checkedIn = ref(false);
const onLunchBreak = ref(false);
const elapsedSeconds = ref(0);
let clockTimer: number | undefined;

const formattedWorkClock = computed(() =>{

    const hours = Math.floor(elapsedSeconds.value / 3600);
    const minutes = Math.floor((elapsedSeconds.value % 3600) / 60);
    const seconds = elapsedSeconds.value % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes
        .toString()
        .padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

});

const workClockClass = computed(() => {
    if (onLunchBreak.value) {
        return 'bg-[#2F2E2E] text-white ring-1 ring-orange-200';
    }

    if (checkedIn.value) {
        return 'bg-[#0EACD8] text-white ring-1 ring-green-200';
    }

    return 'bg-[#F60419] text-white ring-1 ring-red-200';
});

const toggleCheckIn = () => {
    checkedIn.value = !checkedIn.value;

    if (checkedIn.value) {
        checkInAt.value = new Date();

        clockTimer = window.setInterval(() => {
            elapsedSeconds.value += 1;
        }, 1000);

        return;
    }

    if (clockTimer) {
        window.clearInterval(clockTimer);
    }

    onLunchBreak.value = false;
};

const toggleLunchBreak = () => {
    onLunchBreak.value = !onLunchBreak.value;
};

onMounted(() => {
    currentTimeTimer = window.setInterval(() => {
        currentDateTime.value = new Date();
    }, 1000);
});

onBeforeUnmount(() => {
    if (clockTimer) {
        window.clearInterval(clockTimer);
    }

    if (currentTimeTimer) {
        window.clearInterval(currentTimeTimer);
    }
});

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

       <div class="grid gap-4 xl:grid-cols-[24rem_1fr]">
    <Card class="overflow-hidden">
        <CardContent class="flex flex-col items-center gap-5 p-6 text-center">
            <div class="space-y-1">
                <p class="text-sm font-medium text-muted-foreground">
                    {{ greeting }}, {{ authUser.first_name }}
                </p>

                <p class="text-xl font-semibold tracking-tight">
                    {{ formattedCurrentDateTime }}
                </p>
            </div>

            <Avatar class="size-28 border-4 border-primary/20 shadow-sm">
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
                class="rounded-md px-4 py-2 text-sm font-semibold"
                :class="workClockClass"
            >
                Work Clock : {{ formattedWorkClock }}
            </div>

            <div class="flex items-center gap-2 text-sm font-medium">
                <Clock class="size-4 text-primary" />
                <span>{{ formattedPunchInTime }}</span>
            </div>

            <div class="flex w-full items-center gap-2">
                <Button
                    type="button"
                    class="h-11 flex-1"
                    @click="toggleCheckIn"
                >
                    <LogOut v-if="checkedIn" class="size-4" />
                    <LogIn v-else class="size-4" />
                    {{ checkedIn ? 'Check Out' : 'Check In' }}
                </Button>

                <Button
                    v-if="checkedIn"
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-11"
                    :class="onLunchBreak ? 'bg-amber-50 text-amber-700' : ''"
                    @click="toggleLunchBreak"
                >
                    <Coffee class="size-4" />
                </Button>
            </div>
        </CardContent>
    </Card>

    <Card>
        <CardContent class="p-6 text-sm text-muted-foreground">
            Summary cards will go here next.
        </CardContent>
    </Card>
</div>

        <Card>
            <!-- Attendance table -->
        </Card>
    </div>

</template>
