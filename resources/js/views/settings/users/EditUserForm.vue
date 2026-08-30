<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
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

type WorkDetail = {
    client_id: number;
    job_role?: string | null;
    salary?: string | number | null;
    travel_allowance?: string | number | null;
    travel_allowance_currency?: string | null;
    start_shift?: string | null;
    end_shift?: string | null;
};

type Company = {
    id: number;
    company_name: string;
};

type UserType = {
    id: number;
    user_type_name: string;
};

type User = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    company_work_details: WorkDetail[];
    user_type_id: number;
    phone: string | null;
    mobile: string | null;
    clients: Company[];
};

const props = defineProps<{
    open: boolean;
    user: User;
    companies: Company[];
    userTypes: UserType[];
}>();

const emit = defineEmits<{
    (e: 'update:open', open: boolean): void;
    (e: 'saved'): void;
}>();

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    mobile: '',
    user_type_id: null as number | null,
    client_ids: [] as number[],
    work_detail: {
        client_id: null as number | null,
        salary: '',
        travel_allowance: '',
        travel_allowance_currency: 'AUD',
        job_role: '',
        start_shift: '',
        end_shift: '',
    },
});

const companySearch = ref('');
const companyLookupOpen = ref(false);
const companyVisibleLimit = ref(5);

const selectedCompanies = computed(() =>
    props.companies.filter((company) => form.client_ids.includes(company.id)),
);

const fillWorkDetail = (companyId: number | null) => {
    const detail = props.user.company_work_details.find(
        (workDetail) => workDetail.client_id === companyId,
    );

    form.work_detail.client_id = companyId;
    form.work_detail.salary = detail?.salary !== null && detail?.salary !== undefined ? String(detail.salary) : '';
    form.work_detail.travel_allowance =
        detail?.travel_allowance !== null && detail?.travel_allowance !== undefined
            ? String(detail.travel_allowance)
            : '';
    form.work_detail.travel_allowance_currency = detail?.travel_allowance_currency ?? 'AUD';
    form.work_detail.job_role = detail?.job_role ?? '';
    form.work_detail.start_shift = detail?.start_shift ?? '';
    form.work_detail.end_shift = detail?.end_shift ?? '';
};

const filteredCompanies = computed(() => {
    const value = companySearch.value.trim().toLowerCase();

    return props.companies.filter((company) => {
        const matchesSearch =
            !value || company.company_name.toLowerCase().includes(value);

        const isAlreadySelected = form.client_ids.includes(company.id);

        return matchesSearch && !isAlreadySelected;
    });
});

const visibleCompanies = computed(() =>
    filteredCompanies.value.slice(0, companyVisibleLimit.value),
);

const hasMoreCompanies = computed(
    () => companyVisibleLimit.value < filteredCompanies.value.length,
);

const resetForm = () => {
    form.first_name = props.user.first_name ?? '';
    form.last_name = props.user.last_name ?? '';
    form.email = props.user.email ?? '';
    form.phone = props.user.phone ?? '';
    form.mobile = props.user.mobile ?? '';
    form.user_type_id = props.user.user_type_id;
    form.client_ids = props.user.clients.map((client) => client.id);
    companySearch.value = '';
    companyLookupOpen.value = false;
    companyVisibleLimit.value = 5;
    form.clearErrors();
};

watch(
    () => props.open,
    (open) => {
        if (open) {
            resetForm();
            fillWorkDetail(form.client_ids[0] ?? null);
        }
    },
    { immediate: true },
);

const selectCompany = (company: Company) => {
    if (form.client_ids.includes(company.id)) {
        return;
    }

    if (!form.work_detail.client_id) {
        fillWorkDetail(company.id);
    }

    if (form.client_ids.length >= 3) {
        form.setError('client_ids', 'You can select up to 3 companies only.');

        return;
    }

    form.clearErrors('client_ids');
    form.client_ids = [...form.client_ids, company.id];
    companySearch.value = '';
    companyLookupOpen.value = true;
    companyVisibleLimit.value = 5;
};

const removeCompany = (companyId: number) => {
    form.client_ids = form.client_ids.filter((id) => id !== companyId);

    if (form.work_detail.client_id === companyId) {
        fillWorkDetail(form.client_ids[0] ?? null);
    }
};

const closeCompanyLookup = () => {
    window.setTimeout(() => {
        companyLookupOpen.value = false;
    }, 100);
};

const loadMoreCompanies = (event: Event) => {
    const target = event.target as HTMLElement;

    const isNearBottom =
        target.scrollTop + target.clientHeight >= target.scrollHeight - 8;

    if (!isNearBottom || !hasMoreCompanies.value) {
        return;
    }

    companyVisibleLimit.value += 4;
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

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            work_detail: {
                ...data.work_detail,
                start_shift: toBackendTime(data.work_detail.start_shift),
                end_shift: toBackendTime(data.work_detail.end_shift),
            },
        }))
        .patch(`/settings/users/${props.user.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('User has been updated.');
                emit('update:open', false);
                emit('saved');
            },
        });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Edit user</DialogTitle>
                <DialogDescription>
                    Update user details and company access.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="edit_first_name">First name</Label>
                        <Input id="edit_first_name" v-model="form.first_name" />
                        <InputError :message="form.errors.first_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_last_name">Last name</Label>
                        <Input id="edit_last_name" v-model="form.last_name" />
                        <InputError :message="form.errors.last_name" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="edit_email">Email</Label>
                    <Input id="edit_email" v-model="form.email" type="email" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="edit_phone">Phone</Label>
                        <Input id="edit_phone" v-model="form.phone" />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_mobile">Mobile</Label>
                        <Input id="edit_mobile" v-model="form.mobile" />
                        <InputError :message="form.errors.mobile" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="edit_user_type">Profile</Label>
                    <select
                        id="edit_user_type"
                        v-model="form.user_type_id"
                        class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option :value="null" disabled>Select profile</option>
                        <option v-for="userType in userTypes" :key="userType.id" :value="userType.id">
                            {{ userType.user_type_name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.user_type_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit_company">Companies</Label>

                    <div class="relative">
                        <Input
                            id="edit_company"
                            v-model="companySearch"
                            type="search"
                            placeholder="Search company..."
                            autocomplete="off"
                            @focus="companyLookupOpen = true"
                            @input="companyLookupOpen = true"
                            @blur="closeCompanyLookup"
                            @keydown.escape="companyLookupOpen = false"
                        />

                        <div v-if="selectedCompanies.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="company in selectedCompanies"
                                :key="company.id"
                                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium"
                            >
                                {{ company.company_name }}

                                <button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground"
                                    :aria-label="`Remove ${company.company_name}`"
                                    @mousedown.prevent
                                    @click="removeCompany(company.id)"
                                >
                                    x
                                </button>
                            </span>
                        </div>

                        <div
                            v-if="companyLookupOpen"
                            class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                            @scroll="loadMoreCompanies"
                        >
                            <button
                                v-for="company in visibleCompanies"
                                :key="company.id"
                                type="button"
                                class="flex w-full items-center rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                @mousedown.prevent="selectCompany(company)"
                            >
                                {{ company.company_name }}
                            </button>

                            <div v-if="hasMoreCompanies" class="px-2 py-2 text-xs text-muted-foreground">
                                Type to search more company...
                            </div>

                            <div v-if="filteredCompanies.length === 0" class="px-2 py-2 text-sm text-muted-foreground">
                                No companies found.
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        Selected: {{ selectedCompanies.length }} of 3
                    </p>

                    <InputError :message="form.errors.client_ids" />
                </div>

                <section v-if="selectedCompanies.length > 0" class="space-y-4 border-t pt-4">
                    <div class="flex items-center gap-4">
                        <h3 class="shrink-0 text-base font-semibold">Work details</h3>
                        <div class="h-px flex-1 bg-border"></div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="edit_work_company">Company</Label>
                            <select
                                id="edit_work_company"
                                v-model="form.work_detail.client_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                @change="fillWorkDetail(Number(form.work_detail.client_id))"
                            >
                                <option :value="null" disabled>Select company</option>
                                <option v-for="company in selectedCompanies" :key="company.id" :value="company.id">
                                    {{ company.company_name }}
                                </option>
                            </select>
                            <InputError :message="form.errors['work_detail.client_id']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit_salary">Salary</Label>
                            <Input id="edit_salary" v-model="form.work_detail.salary" type="number" step="0.01" min="0" />
                            <InputError :message="form.errors['work_detail.salary']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit_travel_allowance">Travel Allowance</Label>
                            <Input id="edit_travel_allowance" v-model="form.work_detail.travel_allowance" type="number" step="0.01" min="0" />
                            <InputError :message="form.errors['work_detail.travel_allowance']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit_travel_currency">Travel Currency</Label>
                            <Input id="edit_travel_currency" v-model="form.work_detail.travel_allowance_currency" disabled />
                            <InputError :message="form.errors['work_detail.travel_allowance_currency']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit_job_role">Job Role</Label>
                            <Input id="edit_job_role" v-model="form.work_detail.job_role" />
                            <InputError :message="form.errors['work_detail.job_role']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit_start_shift">Start Shift</Label>
                            <ShiftTimePicker id="edit_start_shift" v-model="form.work_detail.start_shift" />
                            <InputError :message="form.errors['work_detail.start_shift']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit_end_shift">End Shift</Label>
                            <ShiftTimePicker id="edit_end_shift" v-model="form.work_detail.end_shift" />
                            <InputError :message="form.errors['work_detail.end_shift']" />
                        </div>
                    </div>
                </section>
            </div>

            <DialogFooter>
                <Button type="button" variant="secondary" @click="emit('update:open', false)">
                    Cancel
                </Button>

                <Button type="button" :disabled="form.processing" @click="submit">
                    Update
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
