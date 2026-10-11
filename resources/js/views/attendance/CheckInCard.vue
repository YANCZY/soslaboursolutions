<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlarmClockOff, Clock, Coffee, Info } from 'lucide-vue-next';

import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';


// ==========
import type { LocationOption } from '@/types/location';
import ForgotCheckoutModal from '@/views/attendance/ForgotCheckoutModal.vue';
import type { Attendance } from '@/types/attendance';


const page = usePage();

const props = defineProps<{
    attendance: Attendance | null;
    locations: LocationOption[]
}>();

const emit = defineEmits<{
    'attendance-updated': [];
}>();

const selectedCompanyStorageKey = 'attendance:selected-location-assignment-id';
const activeAttendance = ref<Attendance | null>(props.attendance);

const selectedLocationId = ref<number | null>(
    props.locations.length === 1 ? props.locations[0].id : null,
);

const companyError = ref('');

const checkInPending = ref(false);

const selectedLocation = computed(() =>
    props.locations.find((location) => location.id === selectedLocationId.value) ?? null,
);

const companySearch = ref(selectedLocation.value?.label ?? '');
const companyLookupOpen = ref(false);

const companySelectionDisabled = computed(() =>
    props.locations.length <= 1 || checkedIn.value || checkInPending.value,
);

const filteredCompanies = computed(() => {
    const value = companySearch.value.trim().toLowerCase();

    return props.locations.filter((location) =>
        !value || location.label.toLowerCase().includes(value),
    );
});

const selectCompany = (company: LocationOption) => {
    selectedLocationId.value = company.id;
    companySearch.value = company.label;
    companyLookupOpen.value = false;
    companyError.value = '';
    localStorage.setItem(selectedCompanyStorageKey, String(company.id));
};

const closeCompanyLookup = () => {
    window.setTimeout(() => {
        companyLookupOpen.value = false;
    }, 100);
};

const forgotLogoutModalOpen = ref(false);
const forgotLogoutAttendance = ref<Attendance | null>(null);
const forgotLogoutCheckOutTime = ref('');
const forgotLogoutError = ref('');

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

const formatShiftTime = (time: string | null) => {
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

const postAttendanceAction = async (url: string, payload: Record<string, unknown> = {}) => {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Timezone': Intl.DateTimeFormat().resolvedOptions().timeZone,
            'X-CSRF-TOKEN':
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
        },
        body: JSON.stringify(payload),
    });

    const rawText = await response.text();
    const data = rawText ? JSON.parse(rawText) : null;


    if (!response.ok) {
        if (data?.requires_forgot_logout_modal && data?.attendance) {
            forgotLogoutAttendance.value = data.attendance;
            forgotLogoutCheckOutTime.value = '';
            forgotLogoutError.value = '';
            forgotLogoutModalOpen.value = true;

            return;
        }

        toast.error(
            data?.errors?.geolocation?.[0]
            ?? data?.message
            ?? 'Attendance request failed.',
        );

        return;
    }

    if (data?.action === 'requires_forgot_checkout' && data?.attendance) {
        forgotLogoutAttendance.value = data.attendance;
        forgotLogoutCheckOutTime.value = '';
        forgotLogoutError.value = '';
        forgotLogoutModalOpen.value = true;

        return;
    }

    activeAttendance.value = data?.attendance ?? null;
    emit('attendance-updated');
};

const getCurrentPosition = (): Promise<GeolocationPosition> =>
    new Promise((resolve, reject) => {
        if (!window.isSecureContext) {
            reject(new Error(
                'Location check-in requires HTTPS.',
            ));
            return;
        }

        if (!navigator.geolocation) {
            reject(new Error(
                'Your browser does not support location access.',
            ));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            resolve,
            (error) => {
                const messages: Record<number, string> = {
                    1: 'Allow location access to check in.',
                    2: 'Your location is unavailable. Enable location services and try again.',
                    3: 'Getting your location timed out. Please try again.',
                };

                reject(new Error(
                    messages[error.code]
                    ?? 'Unable to get your location.',
                ));
            },
            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 15000,
            },
        );
    });

const toggleCheckIn = async () => {

    if (checkInPending.value) return;

    if (checkedIn.value) {
        await postAttendanceAction('/attendance/check-out');
        return;
    }

    const locationId = selectedLocationId.value;

    if (!locationId) {
        companyError.value =
            'Please select a company or site before checking in.';
        return;
    }

    checkInPending.value = true;
    companyError.value = '';

    try {
        const position = await getCurrentPosition();

        await postAttendanceAction('/attendance/check-in', {
            location_assignment_id: locationId,
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            captured_at: Math.round(position.timestamp),
        });
    } catch (error) {
        toast.error(
            error instanceof Error
                ? error.message
                : 'Unable to verify your location.',
        );
    } finally {
        checkInPending.value = false;
    }
};

const toggleLunchBreak = async () => {
    if (onLunchBreak.value) {
        await postAttendanceAction('/attendance/lunch/end');

        return;
    }

    await postAttendanceAction('/attendance/lunch/start');
};

const saveForgotLogout = async () => {
    forgotLogoutError.value = '';

    if (!forgotLogoutAttendance.value?.id || !forgotLogoutCheckOutTime.value) {
        forgotLogoutError.value = 'Check-out time is required.';

        return;
    }

    const response = await fetch('/attendance/forgot-check-out', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Timezone': Intl.DateTimeFormat().resolvedOptions().timeZone,
            'X-CSRF-TOKEN':
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
        },
        body: JSON.stringify({
            attendance_id: forgotLogoutAttendance.value.id,
            check_out_time: forgotLogoutCheckOutTime.value,
        }),
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        forgotLogoutError.value = data?.message ?? 'Unable to save check-out time.';

        return;
    }

    activeAttendance.value = null;
    forgotLogoutModalOpen.value = false;
    forgotLogoutAttendance.value = null;
    forgotLogoutCheckOutTime.value = '';
    forgotLogoutError.value = '';
    emit('attendance-updated');
};

onMounted(() => {

    const storedCompanyId = Number(localStorage.getItem(selectedCompanyStorageKey));

    if (
        props.locations.length > 1 &&
        props.locations.some((location) => location.id === storedCompanyId)
    ) {
        selectedLocationId.value = storedCompanyId;
        companySearch.value =
            props.locations.find((location) => location.id === storedCompanyId)?.label ?? '';
    }

    if (props.locations.length === 1) {
        localStorage.setItem(selectedCompanyStorageKey, String(props.locations[0].id));
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
class="flex flex-col items-center gap-3 p-4 text-center sm:p-4">
            <div class="w-full max-w-[16rem] text-left">
                <div class="relative">
                    <TooltipProvider v-if="selectedLocation" :delay-duration="100">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <button type="button"
                                    class="absolute inset-y-0 left-2 z-10 flex items-center text-primary hover:text-primary/80"
                                    :aria-label="`View shift for ${selectedLocation.label}`" @mousedown.prevent>
                                    <Info class="size-4" />
                                </button>
                            </TooltipTrigger>

                            <TooltipContent side="top" align="center">
                                Shift:
                                {{
                                    selectedLocation.start_shift && selectedLocation.end_shift
                                        ? `${formatShiftTime(selectedLocation.start_shift)} -
                                ${formatShiftTime(selectedLocation.end_shift)}`
                                        : 'No shift assigned'
                                }}
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                    <input id="attendance_company" v-model="companySearch" type="search" placeholder="Select company..."
                        autocomplete="off" :disabled="companySelectionDisabled"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 pl-8 text-sm disabled:cursor-not-allowed disabled:opacity-70"
                        @focus="companyLookupOpen = !companySelectionDisabled"
                        @input="companyLookupOpen = !companySelectionDisabled" @blur="closeCompanyLookup"
                        @keydown.escape="companyLookupOpen = false" />

                    <div v-if="companyLookupOpen && filteredCompanies.length > 0"
                        class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md">
                        <button v-for="company in filteredCompanies" :key="company.id" type="button"
                            class="flex w-full items-center rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                            @mousedown.prevent="selectCompany(company)">
                            {{ company.label }}
                        </button>
                    </div>
                </div>

                <p v-if="companyError" class="text-xs text-destructive">
                    {{ companyError }}
                </p>
            </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ greeting }}, {{ authUser.first_name }}
                        </p>

                        <p class="text-xl font-semibold tracking-tight">
                            {{ formattedCurrentDateTime }}
                        </p>
                    </div>

                    <Avatar
class="size-24 border-4 border-primary/20 shadow-sm"
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
                class="flex w-full items-center justify-center gap-2 rounded-md border border-border bg-muted/40 px-4 py-2 text-sm font-medium text-foreground sm:w-auto dark:bg-muted/20"
                    >
                        <Clock class="size-4 shrink-0 text-primary" />
                        <span>{{ formattedPunchInTime }}</span>
                        <p v-if="onLunchBreak" class="text-sm font-medium text-amber-600">
                         Lunch Break
                        </p>
                    </div>

                    <p
                        v-if="checkInPending"
                        role="status"
                        class="text-center text-sm text-muted-foreground"
                    >
                        Checking your location…
                    </p>

                    <div class="flex w-full items-center gap-2">
                        <Button
                            type="button"
class="h-10 flex-1"
                            :class="checkedIn ? 'bg-red-600 text-white hover:bg-red-700' : ''"
                            :disabled="checkInPending"
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
            <ForgotCheckoutModal
                :open="forgotLogoutModalOpen"
                :attendance="forgotLogoutAttendance"
                :check-out-time="forgotLogoutCheckOutTime"
                :error="forgotLogoutError"
                @update:open="forgotLogoutModalOpen = $event"
                @update:check-out-time="forgotLogoutCheckOutTime = $event"
                @save="saveForgotLogout"
            />
</template>
