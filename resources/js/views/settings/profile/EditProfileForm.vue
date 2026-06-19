<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
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
import { send } from '@/routes/verification';


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
    mustVerifyEmail: boolean;
    status?: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', open: boolean): void;
    (e: 'saved'): void;
}>();


const form = useForm({
    first_name: props.user.first_name ?? '',
    last_name: props.user.last_name ?? '',
    email: props.user.email ?? '',
    phone: props.user.phone ?? '',
    mobile: props.user.mobile ?? '',
    job_role: props.profile.job_role ?? '',
    travel_allowance:
        props.profile.travel_allowance !== null &&
        props.profile.travel_allowance !== undefined
            ? String(props.profile.travel_allowance)
            : '',
    travel_allowance_currency:
        props.profile.travel_allowance_currency ?? 'AUD',
    salary:
        props.profile.salary !== null && props.profile.salary !== undefined
            ? String(props.profile.salary)
            : '',
    start_shift: props.profile.start_shift ?? '',
    end_shift: props.profile.end_shift ?? '',
});

function submit() {
    form.patch('/settings/profile', {
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
                            <h3 class="shrink-0 text-base font-semibold">Compensation</h3>
                            <div class="h-px flex-1 bg-border"></div>
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
                                />
                                <InputError :message="form.errors.travel_allowance_currency" />
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
                                <Input
                                    id="start_shift"
                                    v-model="form.start_shift"
                                    type="time"
                                    name="start_shift"
                                />
                                <InputError :message="form.errors.start_shift" />
                            </div>

                            <div class="space-y-2">
                                <Label for="end_shift">End shift</Label>
                                <Input
                                    id="end_shift"
                                    v-model="form.end_shift"
                                    type="time"
                                    name="end_shift"
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
