<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    profile: {
        job_role?: string | null;
        travel_allowance?: string | number | null;
        travel_allowance_currency?: string | null;
        salary?: string | number | null;
    };
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

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="first_name">First name</Label>
                    <Input
                        id="first_name"
                        class="mt-1 block w-full"
                        name="first_name"
                        :default-value="user.first_name"
                        required
                        autocomplete="given-name"
                        placeholder="First name"
                    />
                    <InputError class="mt-2" :message="errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name">Last name</Label>
                    <Input
                        id="last_name"
                        class="mt-1 block w-full"
                        name="last_name"
                        :default-value="user.last_name"
                        required
                        autocomplete="family-name"
                        placeholder="Last name"
                    />
                    <InputError class="mt-2" :message="errors.last_name" />
                </div>

                <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    name="phone"
                    :default-value="user.phone ?? undefined"
                    placeholder="Phone"
                />
                <InputError class="mt-2" :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="mobile">Mobile</Label>
                <Input
                    id="mobile"
                    name="mobile"
                    :default-value="user.mobile ?? undefined"
                    placeholder="Mobile"
                />
                <InputError class="mt-2" :message="errors.mobile" />
            </div>


            <div class="grid gap-2">
                    <Label for="job_role">Job role</Label>
                    <Input
                        id="job_role"
                        name="job_role"
                        :default-value="props.profile.job_role ?? undefined"
                        placeholder="Job role"
                    />
                    <InputError class="mt-2" :message="errors.job_role" />
                </div>

                <div class="grid gap-2">
                    <Label for="travel_allowance">Travel allowance</Label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-muted-foreground">
                            AU$
                        </span>
                        <Input
                            id="travel_allowance"
                            type="number"
                            step="0.01"
                            min="0"
                            name="travel_allowance"
                            class="pl-12"
                            :default-value="props.profile.travel_allowance ?? undefined"
                            placeholder="0.00"
                        />
                    </div>
                    <InputError class="mt-2" :message="errors.travel_allowance" />
                </div>

                <div class="grid gap-2" hidden>
                    <Label for="travel_allowance_currency">Travel allowance currency</Label>
                    <Input
                        id="travel_allowance_currency"
                        name="travel_allowance_currency"
                        maxlength="3"
                        disabled
                        :default-value="props.profile.travel_allowance_currency ?? undefined"
                        placeholder="AUD"
                    />
                    <InputError class="mt-2" :message="errors.travel_allowance_currency" />
                </div>

                <div class="grid gap-2">
                    <Label for="salary">Salary</Label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-muted-foreground">
                            AU$
                        </span>
                        <Input
                            id="salary"
                            type="number"
                            step="0.01"
                            min="0"
                            name="salary"
                            class="pl-12"
                            :default-value="props.profile.salary ?? undefined"
                            placeholder="0.00"
                        />
                    </div>
                    <InputError class="mt-2" :message="errors.salary" />
                </div>
            </div>

             <div v-if="mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
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



            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
