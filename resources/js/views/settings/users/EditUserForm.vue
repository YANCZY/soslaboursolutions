<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
});

const companySearch = ref('');
const companyLookupOpen = ref(false);
const companyVisibleLimit = ref(5);

const selectedCompanies = computed(() =>
    props.companies.filter((company) => form.client_ids.includes(company.id)),
);

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
        }
    },
    { immediate: true },
);

const selectCompany = (company: Company) => {
    if (form.client_ids.includes(company.id)) {
        return;
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

const submit = () => {
    form.patch(`/settings/users/${props.user.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('update:open', false);
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
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
                    ×
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
