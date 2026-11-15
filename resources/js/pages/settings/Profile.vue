<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Camera, Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';

import type { LocationOption } from '@/types/location';


// =======
import type { ProfileDocumentItem } from '@/types/profile-document';
import EditProfileForm from '@/views/settings/profile/EditProfileForm.vue';
import ProfileDocuments from '@/views/settings/profile/ProfileDocuments.vue';


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
    locations: LocationOption[];
    workDetail?: WorkDetail | null;
    workDetails: WorkDetail[];
    selectedLocationId?: number | null;
    documents: ProfileDocumentItem[];
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

const selectedLocation = computed(() =>
    props.locations.find(
        (location) => location.id === props.selectedLocationId,
    ) ?? null,
);

const fullName = computed(() =>
    [user.value.first_name, user.value.last_name]
        .filter(Boolean)
        .join(' '),
);

const photoInput = ref<HTMLInputElement | null>(null);
const bannerInput = ref<HTMLInputElement | null>(null);

const imageForm = useForm({
    kind: 'photo' as 'photo' | 'banner',
    image: null as File | null,
});

function uploadImage(event: Event, kind: 'photo' | 'banner') {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file || imageForm.processing) {
        input.value = '';

        return;
    }

    imageForm.clearErrors();
    const allowedTypes = ['image/jpeg', 'image/png'];
    const allowedExtension = /\.(jpe?g|png)$/i.test(file.name);

    if (!allowedTypes.includes(file.type) || !allowedExtension) {
        imageForm.setError(
            'image',
            'Only PNG and JPEG (.png, .jpg, .jpeg) images are accepted. Please export your image in one of these formats.',
        );

        input.value = '';

        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        imageForm.setError(
            'image',
            'Your image must be 5 MB or smaller.',
        );

        input.value = '';

        return;
    }

    imageForm.kind = kind;
    imageForm.image = file;

    imageForm.post('/settings/profile/image', {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            imageForm.reset('image');
            input.value = '';
        },
    });
}

const information = computed(() => [
    {
        label: 'First name',
        value: user.value.first_name || '—',
    },
    {
        label: 'Last name',
        value: user.value.last_name || '—',
    },
    {
        label: 'Email address',
        value: user.value.email || '—',
    },
    {
        label: 'Phone',
        value: user.value.phone || '—',
    },
    {
        label: 'Mobile',
        value: user.value.mobile || '—',
    },
    {
        label: 'Company / site',
        value: selectedLocation.value?.label || 'Not assigned',
    },
    {
        label: 'Job role',
        value: props.workDetail?.job_role || 'Not assigned',
    },
    {
        label: 'Salary',
        value: props.workDetail?.salary == null
            ? 'Not specified'
            : new Intl.NumberFormat('en-AU', {
                style: 'currency',
                currency: 'AUD',
            }).format(Number(props.workDetail.salary)),
    },
]);
</script>

<template>
    <Head title="Profile" />

    <div class="space-y-8">
        <h1 class="sr-only">Profile</h1>

        <input
            ref="photoInput"
            type="file"
            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
            class="hidden"
            aria-label="Choose profile photo"
            :disabled="imageForm.processing"
            @change="uploadImage($event, 'photo')"
        />

        <input
            ref="bannerInput"
            type="file"
            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
            class="hidden"
            aria-label="Choose profile banner"
            :disabled="imageForm.processing"
            @change="uploadImage($event, 'banner')"
        />

        <section
            class="overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-sm"
            aria-labelledby="profile-name"
        >
            <div
                class="relative aspect-[4/1] min-h-36 bg-gradient-to-r from-cyan-800 via-cyan-600 to-sky-400"
            >
                <img
                    v-if="user.profile_banner_url"
                    :src="user.profile_banner_url"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                />

                <Button
                    type="button"
                    variant="secondary"
                    class="absolute top-4 right-4 shadow-sm"
                    :disabled="imageForm.processing"
                    @click="bannerInput?.click()"
                >
                    <Camera class="size-4" />
                    Change banner
                </Button>
            </div>

            <div class="px-5 pb-6 sm:px-8 sm:pb-8">
                <div class="relative -mt-14 mb-5 flex items-end justify-between gap-4">
                    <div class="relative shrink-0">
                        <div
                            class="flex size-28 items-center justify-center overflow-hidden rounded-full border-4 border-card bg-muted text-3xl font-semibold text-muted-foreground sm:size-36 sm:text-4xl"
                        >
                            <img
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="fullName"
                                class="h-full w-full object-cover"
                            />

                            <span v-else>
                                {{ getInitials(fullName) }}
                            </span>
                        </div>

                        <Button
                            type="button"
                            variant="secondary"
                            size="icon"
                            class="absolute right-0 bottom-1 rounded-full border shadow-sm"
                            aria-label="Change profile photo"
                            :disabled="imageForm.processing"
                            @click="photoInput?.click()"
                        >
                            <Camera class="size-4" />
                        </Button>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        class="mb-1"
                        @click="isEditing = true"
                    >
                        <Pencil class="size-4" />
                        Edit profile
                    </Button>
                </div>

                <div class="space-y-2">
                    <h2
                        id="profile-name"
                        class="break-words text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        {{ fullName }}
                    </h2>

                    <p class="text-base sm:text-lg">
                        {{ props.workDetail?.job_role || 'Job role not assigned' }}
                    </p>

                    <p class="break-words text-sm text-muted-foreground">
                        {{ selectedLocation?.label || 'Company / site not assigned' }}
                    </p>
                </div>

                <div class="mt-5 space-y-2" aria-live="polite">
                    <p class="text-xs text-muted-foreground">
                        PNG or JPEG only (.png, .jpg, .jpeg), up to 5 MB.
                        Maximum upload dimensions: 4096 x 4096 pixels.
                        Choosing an image uploads it immediately.
                        We automatically crop and compress profile photos to fit
                        400 x 400 and banners to fit 1200 x 300 pixels.
                        Transparent backgrounds become white.
                    </p>

                    <p
                        v-if="imageForm.processing"
                        class="text-sm text-muted-foreground"
                    >
                        Uploading
                        <span v-if="imageForm.progress">
                            {{ imageForm.progress.percentage }}%
                        </span>
                        …
                    </p>

                    <p
                        v-else-if="imageForm.wasSuccessful"
                        class="text-sm text-muted-foreground"
                    >
                        Image updated.
                    </p>

                    <InputError :message="imageForm.errors.image" />
                    <InputError :message="imageForm.errors.kind" />
                </div>

                <div class="mt-8 border-t pt-6">
                    <h3 class="text-lg font-semibold">
                        Profile information
                    </h3>

                    <dl class="mt-5 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                        <div
                            v-for="item in information"
                            :key="item.label"
                            class="min-w-0 space-y-1"
                        >
                            <dt class="text-sm text-muted-foreground">
                                {{ item.label }}
                            </dt>

                            <dd class="break-words text-sm font-medium">
                                {{ item.value }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>
        
        <ProfileDocuments :documents="props.documents" />

        <EditProfileForm
            v-model:open="isEditing"
            :user="user"
            :profile="props.profile"
            :locations="props.locations"
            :selected-location-id="props.selectedLocationId"
            :must-verify-email="mustVerifyEmail"
            :status="status"
            @saved="isEditing = false"
        />

        <DeleteUser />
    </div>
</template>
