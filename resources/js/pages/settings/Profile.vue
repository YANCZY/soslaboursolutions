<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';


// =======
import EditProfileForm from '@/views/settings/profile/EditProfileForm.vue';


type Company = {
    id: number;
    company_name: string;
};

type WorkDetail = {
    client_id: number;
    job_role?: string | null;
    salary?: string | number | null;
    travel_allowance?: string | number | null;
    travel_allowance_currency?: string | null;
    start_shift?: string | null;
    end_shift?: string | null;
    company?: Company | null;
};

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    profile: {
        job_role?: string | null;
        travel_allowance?: string | number | null;
        travel_allowance_currency?: string | null;
        salary?: string | number | null;
    };
    companies: Company[];
    workDetail?: WorkDetail | null;
    workDetails: WorkDetail[];
    selectedCompanyId?: number | null;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: '/settings',
            },
            {
                title: 'Profile',
                href: '/settings/profile',
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isEditing = ref(false);

const selectedCompany = computed(() =>
    props.companies.find((company) => company.id === props.selectedCompanyId) ?? null,
);
</script>

<template>
    <Head title="Profile" />

    <h1 class="sr-only">Profile</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile information"
            description="Update your profile details"
        />

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>First name</Label>
                    <div class="rounded-md border px-3 py-2 text-sm">{{ user.first_name }}</div>
                </div>

                <div class="grid gap-2">
                    <Label>Last name</Label>
                    <div class="rounded-md border px-3 py-2 text-sm">{{ user.last_name }}</div>
                </div>

                <div class="grid gap-2">
                    <Label>Email address</Label>
                    <div class="rounded-md border px-3 py-2 text-sm">{{ user.email }}</div>
                </div>

                <div class="grid gap-2">
                    <Label>Phone</Label>
                    <div class="rounded-md border px-3 py-2 text-sm">{{ user.phone || '-' }}</div>
                </div>

                <div class="grid gap-2">
                    <Label>Mobile</Label>
                    <div class="rounded-md border px-3 py-2 text-sm">{{ user.mobile || '-' }}</div>
                </div>

                <div class="grid gap-2">
                    <Label>Company</Label>
                    <div class="rounded-md border px-3 py-2 text-sm">
                        {{ props.workDetail?.company?.company_name || selectedCompany?.company_name || '-' }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Job role</Label>
                    <div class="rounded-md border px-3 py-2 text-sm">{{ props.workDetail?.job_role || '-' }}</div>
                </div>

                <div class="grid gap-2">
                    <Label>Salary</Label>
                    <div class="rounded-md border px-3 py-2 text-sm">AU$ {{ props.workDetail?.salary || '0.00' }}</div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button type="button" @click="isEditing = true">
                    <Pencil class="mr-1 size-4" />
                    Edit
                </Button>
            </div>
        </div>
        <EditProfileForm
            v-model:open="isEditing"
            :user="user"
            :profile="props.profile"
:companies="props.companies"
            :work-details="props.workDetails" :selected-company-id="props.selectedCompanyId"
            :must-verify-email="mustVerifyEmail"
            :status="status"
            @saved="isEditing = false"
        />
    </div>

    <DeleteUser />
</template>
