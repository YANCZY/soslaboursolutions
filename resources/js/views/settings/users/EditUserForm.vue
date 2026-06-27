<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
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

const selectedCompanies = computed(() =>
    props.companies.filter((company) => form.client_ids.includes(company.id)),
);

const resetForm = () => {
    form.first_name = props.user.first_name ?? '';
    form.last_name = props.user.last_name ?? '';
    form.email = props.user.email ?? '';
    form.phone = props.user.phone ?? '';
    form.mobile = props.user.mobile ?? '';
    form.user_type_id = props.user.user_type_id;
    form.client_ids = props.user.clients.map((client) => client.id);
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

const toggleCompany = (companyId: number) => {
    if (form.client_ids.includes(companyId)) {
        form.client_ids = form.client_ids.filter((id) => id !== companyId);

        return;
    }

    if (form.client_ids.length >= 3) {
        form.setError('client_ids', 'You can select up to 3 companies only.');

        return;
    }

    form.clearErrors('client_ids');
    form.client_ids = [...form.client_ids, companyId];
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
                    <Label>Companies</Label>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="company in companies"
                            :key="company.id"
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="form.client_ids.includes(company.id)
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-background text-foreground hover:bg-muted'"
                            @click="toggleCompany(company.id)"
                        >
                            {{ company.company_name }}
                        </button>
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
