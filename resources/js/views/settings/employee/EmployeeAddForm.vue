<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type UserType = {
    id: number;
    user_type_name: string;
};

const props = defineProps<{
    userTypes: UserType[];
}>();

const emit = defineEmits<{
    success: [];
}>();

const profileSearch = ref('');
const profileLookupOpen = ref(false);
const selectedUserTypeId = ref<number | null>(null);

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    mobile: '',
    user_type_id: null as number | null,
});

const filteredUserTypes = computed(() => {
    const value = profileSearch.value.trim().toLowerCase();

    if (!value) {
        return props.userTypes;
    }

    return props.userTypes.filter((userType) =>
        userType.user_type_name.toLowerCase().includes(value),
    );
});

const selectUserType = (userType: UserType) => {
    selectedUserTypeId.value = userType.id;
    profileSearch.value = userType.user_type_name;
    profileLookupOpen.value = false;
};

watch(profileSearch, (value) => {
    if (value.trim() === '') {
        selectedUserTypeId.value = null;
        profileLookupOpen.value = true;
    }
});

const closeProfileLookup = () => {
    window.setTimeout(() => {
        profileLookupOpen.value = false;
    }, 100);
};

const saveEmployee = () => {
    form.user_type_id = selectedUserTypeId.value;

    form.post('/settings/employee', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            profileSearch.value = '';
            selectedUserTypeId.value = null;
            toast.success('User has been added. Account setup email will be sent shortly.');
            emit('success');
        },
    });
};
</script>

<template>
    <DialogContent class="sm:max-w-lg">
        <DialogHeader>
            <DialogTitle>Add user</DialogTitle>
            <DialogDescription>
                Create a user profile for workspace access.
            </DialogDescription>
        </DialogHeader>

        <div class="grid gap-4 py-2 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="employee_first_name">First name</Label>
                <Input
                    id="employee_first_name"
                    v-model="form.first_name"
                    placeholder="First name"
                />
                <InputError :message="form.errors.first_name" />
            </div>

            <div class="grid gap-2">
                <Label for="employee_last_name">Last name</Label>
                <Input
                    id="employee_last_name"
                    v-model="form.last_name"
                    placeholder="Last name"
                />
                <InputError :message="form.errors.last_name" />
            </div>

            <div class="grid gap-2">
                <Label for="employee_email">Email</Label>
                <Input
                    id="employee_email"
                    v-model="form.email"
                    type="email"
                    placeholder="email@example.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="employee_phone">Phone</Label>
                <Input
                    id="employee_phone"
                    v-model="form.phone"
                    placeholder="Phone number"
                />
                <InputError :message="form.errors.phone" />
            </div>

            <div class="grid gap-2 sm:col-span-2">
                <Label for="employee_mobile">Mobile</Label>
                <Input
                    id="employee_mobile"
                    v-model="form.mobile"
                    placeholder="Mobile number"
                />
                <InputError :message="form.errors.mobile" />
            </div>

            <div class="grid gap-2 sm:col-span-2">
                <Label for="employee_profile">Profile</Label>

                <div class="relative">
                    <Input
                    id="employee_profile"
                    v-model="profileSearch"
                    type="search"
                    placeholder="Search profile..."
                    autocomplete="off"
                    @focus="profileLookupOpen = true"
                    @input="profileLookupOpen = true"
                    @blur="closeProfileLookup"
                    @keydown.escape="profileLookupOpen = false"
                />

                    <InputError :message="form.errors.user_type_id" />

                    <div
                        v-if="profileLookupOpen"
                        class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                    >
                        <button
                            v-for="userType in filteredUserTypes"
                            :key="userType.id"
                            type="button"
                            class="flex w-full items-center rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                            @mousedown.prevent="selectUserType(userType)"
                        >
                            {{ userType.user_type_name }}
                        </button>

                        <div
                            v-if="filteredUserTypes.length === 0"
                            class="px-2 py-2 text-sm text-muted-foreground"
                        >
                            No profiles found.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DialogFooter>
            <DialogClose as-child>
                <Button type="button" variant="secondary">
                    Cancel
                </Button>
            </DialogClose>

            <Button
                type="button"
                class="bg-red-600 text-white"
                :disabled="form.processing"
                @click="saveEmployee"
            >
                Save user
            </Button>
        </DialogFooter>
    </DialogContent>
</template>
