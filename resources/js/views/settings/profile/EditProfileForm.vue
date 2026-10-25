<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import ShiftTimePicker from '@/components/ShiftTimePicker.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { send } from '@/routes/verification';
import type { LocationOption } from '@/types/location';


type UserProfile = {
    first_name: string;
    last_name: string;
    email: string;
    phone?: string | null;
    mobile?: string | null;
    email_verified_at?: string | null;
};

type ProfileDetails = {
    job_role?: string | null;
    travel_allowance?: string | number | null;
    travel_allowance_currency?: string | null;
    salary?: string | number | null;
    start_shift?: string | null;
    end_shift?: string | null;
};

const props = defineProps<{
    open: boolean;
    user: UserProfile;
    profile: ProfileDetails;
    locations: LocationOption[];
    selectedLocationId?: number | null;
    mustVerifyEmail: boolean;
    status?: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', open: boolean): void;
    (e: 'saved'): void;
}>();

const companySearch = ref('');
const selectedCompanyLabel = ref('');
const companyLookupOpen = ref(false);

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    mobile: '',
    location_assignment_id: null as number | null,
    job_role: '',
    salary: '',
    travel_allowance: '',
    travel_allowance_currency: '',
    start_shift: '',
    end_shift: '',
});

const populateWorkFields = (location: LocationOption | null) => {
    form.job_role = location?.job_role ?? '';
    form.salary = location?.salary == null
        ? ''
        : String(location.salary);
    form.travel_allowance = location?.travel_allowance == null
        ? ''
        : String(location.travel_allowance);
    form.travel_allowance_currency =
        location?.travel_allowance_currency ?? '';
    form.start_shift = location?.start_shift ?? '';
    form.end_shift = location?.end_shift ?? '';
};

const populateForm = () => {
    const location = props.locations.find(
        (item) => item.id === props.selectedLocationId,
    ) ?? props.locations[0] ?? null;

    form.first_name = props.user.first_name ?? '';
    form.last_name = props.user.last_name ?? '';
    form.email = props.user.email ?? '';
    form.phone = props.user.phone ?? '';
    form.mobile = props.user.mobile ?? '';
    form.location_assignment_id = location?.id ?? null;

    selectedCompanyLabel.value = location?.label ?? '';
    companySearch.value = selectedCompanyLabel.value;

    populateWorkFields(location);
    companyLookupOpen.value = false;
    form.clearErrors();
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
populateForm();
}
    },
    { immediate: true },
);

const filteredCompanies = computed(() => {
    const value = companySearch.value.trim().toLowerCase();
    const selectedValue =
        selectedCompanyLabel.value.trim().toLowerCase();

    const searchValue =
        form.location_assignment_id && value === selectedValue
            ? ''
            : value;

    return props.locations.filter((location) =>
        location.id !== form.location_assignment_id
        && (
            !searchValue
            || location.label.toLowerCase().includes(searchValue)
        ),
    );
});

watch(companySearch, (value) => {
    if (value.trim() === '') {
        form.location_assignment_id = null;
        selectedCompanyLabel.value = '';
        populateWorkFields(null);
        companyLookupOpen.value = props.locations.length > 0;
    }
});

const selectCompany = (location: LocationOption) => {
    form.location_assignment_id = location.id;
    selectedCompanyLabel.value = location.label;
    companySearch.value = location.label;

    populateWorkFields(location);
    companyLookupOpen.value = false;
};

const closeCompanyLookup = () => {
    window.setTimeout(() => {
        companyLookupOpen.value = false;
    }, 100);
};

const toBackendTime = (value: string | null) => {
    if (!value) {
        return '';
    }

    const trimmedValue = value.trim();

    if (/^\d{2}:\d{2}$/.test(trimmedValue)) {
        return trimmedValue;
    }

    if (/^\d{2}:\d{2}:\d{2}$/.test(trimmedValue)) {
        return trimmedValue.slice(0, 5);
    }

    const match = trimmedValue.match(/^(0[1-9]|1[0-2]):([0-5][0-9])\s(AM|PM)$/i);

    if (!match) {
        return trimmedValue;
    }

    let hour = Number(match[1]);
    const minute = match[2];
    const period = match[3].toUpperCase();

    if (period === 'PM' && hour !== 12) {
        hour += 12;
    }

    if (period === 'AM' && hour === 12) {
        hour = 0;
    }

    return `${String(hour).padStart(2, '0')}:${minute}`;
};

function submit() {
    form
        .transform((data) => {
            const payload = {
                ...data,
                start_shift: toBackendTime(data.start_shift),
                end_shift: toBackendTime(data.end_shift),
            };

            return payload;
        })
        .patch('/settings/profile', {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
        },
    });
}


</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
       <DialogContent
            :show-close-button="false"
            class="flex max-h-[85vh] w-full flex-col overflow-hidden sm:max-w-4xl"
        >
            <form class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
                <DialogHeader class="sticky top-0 z-10 border-b bg-background px-6 py-4">
                    <div class="flex items-start justify-between gap-3 pr-1">
                        <div class="space-y-1">
                            <DialogTitle>Edit profile</DialogTitle>
                            <DialogDescription>
                                Update your account and profile details.
                            </DialogDescription>
                        </div>

                        <button
                            type="button"
                            class="mt-1 mr-1 inline-flex size-9 shrink-0 items-center justify-center rounded-md text-muted-foreground transition hover:bg-muted hover:text-foreground"
                            @click="emit('update:open', false)"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                </DialogHeader>
                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-4">
                     <section class="space-y-4">
                        <div class="flex items-center gap-4">
                            <h3 class="shrink-0 text-base font-semibold">Personal details</h3>
                            <div class="h-px flex-1 bg-border"></div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="first_name">First name</Label>
                                <Input id="first_name" v-model="form.first_name" name="first_name" />
                                <InputError :message="form.errors.first_name" />
                            </div>

                            <div class="space-y-2">
                                <Label for="last_name">Last name</Label>
                                <Input id="last_name" v-model="form.last_name" name="last_name" />
                                <InputError :message="form.errors.last_name" />
                            </div>

                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input id="email" v-model="form.email" type="email" name="email" />
                                <InputError :message="form.errors.email" />
                            </div>

                            <div class="space-y-2">
                                <Label for="phone">Phone</Label>
                                <Input id="phone" v-model="form.phone" name="phone" />
                                <InputError :message="form.errors.phone" />
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4 pt-2">
                        <div class="flex items-center gap-4">
                            <h3 class="shrink-0 text-base font-semibold">Work details</h3>
                            <div class="h-px flex-1 bg-border"></div>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="company">Company</Label>

                            <div class="relative">
                                <Input id="company" v-model="companySearch" type="search"
                                    placeholder="Search company or site..." autocomplete="off"
                                    @focus="companyLookupOpen = props.locations.length > 0"
                                    @input="companyLookupOpen = props.locations.length > 0" @blur="closeCompanyLookup"
                                    @keydown.escape="companyLookupOpen = false" />

                                <InputError :message="form.errors.location_assignment_id" />

                                <div v-if="companyLookupOpen && filteredCompanies.length > 0"
                                    class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md">
                                    <button v-for="company in filteredCompanies" :key="company.id" type="button"
                                        class="flex w-full items-center rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                        @mousedown.prevent="selectCompany(company)">
                                        {{ company.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="salary">Salary</Label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-muted-foreground">
                                        AU$
                                    </span>
                                    <Input
                                        id="salary"
                                        v-model="form.salary"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="salary"
                                        class="pl-12"
disabled
                                    />
                                </div>
                                <InputError :message="form.errors.salary" />
                            </div>

                            <div class="space-y-2">
                                <Label for="travel_allowance">Travel allowance</Label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-muted-foreground">
                                        AU$
                                    </span>
                                    <Input
                                        id="travel_allowance"
                                        v-model="form.travel_allowance"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="travel_allowance"
                                        class="pl-12"
disabled
                                    />
                                </div>
                                <InputError :message="form.errors.travel_allowance" />
                            </div>

                            <div class="space-y-2">
                                <Label for="travel_allowance_currency">Travel currency</Label>
                                <Input
                                    id="travel_allowance_currency"
                                    v-model="form.travel_allowance_currency"
                                    name="travel_allowance_currency"
                                    maxlength="3"
                                    placeholder="AUD"
disabled
                                />
                                <InputError :message="form.errors.travel_allowance_currency" />
                            </div>
                            <div class="space-y-2">
                                <Label for="job_role">Job role</Label>
                                <Input id="job_role" v-model="form.job_role" name="job_role" placeholder="Job role"
                                    disabled />
                                <InputError :message="form.errors.job_role" />
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4 pt-2">
                        <div class="flex items-center gap-4">
                            <h3 class="shrink-0 text-base font-semibold">Shift details</h3>
                            <div class="h-px flex-1 bg-border"></div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="start_shift">Start shift</Label>
                                <ShiftTimePicker
                                    id="start_shift"
                                    v-model="form.start_shift"
                                    name="start_shift"
                                    placeholder="Select start time"
                                    disabled
                                />
                                <InputError :message="form.errors.start_shift" />
                            </div>

                            <div class="space-y-2">
                                <Label for="end_shift">End shift</Label>
                                <ShiftTimePicker
                                    id="end_shift"
                                    v-model="form.end_shift"
                                    name="end_shift"
                                    placeholder="Select end time"
                                    disabled
                                />
                                <InputError :message="form.errors.end_shift" />
                            </div>
                        </div>
                    </section>

                </div>



                <div
                    v-if="mustVerifyEmail && !user.email_verified_at"
                    class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3"
                >
                    <p class="text-sm text-amber-900">
                        Your email address is unverified.
                        <Link
                            :href="send()"
                            as="button"
                            class="font-medium underline underline-offset-4"
                        >
                            Click here to resend the verification email.
                        </Link>
                    </p>

                    <div
                        v-if="status === 'verification-link-sent'"
                        class="mt-2 text-sm font-medium text-green-600"
                    >
                        A new verification link has been sent to your email address.
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </Button>

                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="update-profile-button"
                    >
                        Update
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
