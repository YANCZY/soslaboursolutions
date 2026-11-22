<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

type PendingUser = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    status: 'active' | 'inactive' | 'pending';
};

const props = defineProps<{
    user: PendingUser;
    endpoint: '/settings/users' | '/settings/employee';
}>();

const deleting = ref(false);

const deleteUser = () => {
    if (props.user.status !== 'pending' || deleting.value) {
        return;
    }

    const name =
        `${props.user.first_name} ${props.user.last_name}`.trim()
        || props.user.email;

    const confirmed = window.confirm(
        `Permanently delete ${name}?\n\n`
        + 'This removes the pending account and its uploaded profile files. '
        + 'This action cannot be undone.',
    );

    if (!confirmed) {
        return;
    }

    deleting.value = true;

    router.delete(`${props.endpoint}/${props.user.id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success('Pending user deleted.');
        },
        onError: (errors) => {
            toast.error(
                errors.delete_user
                ?? Object.values(errors)[0]
                ?? 'Unable to delete this user.',
            );
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};
</script>

<template>
    <button
        v-if="user.status === 'pending'"
        type="button"
        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md border border-red-200 bg-background text-red-600 shadow-sm transition-colors hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40"
        title="Delete pending user"
        :aria-label="`Delete pending user ${user.first_name} ${user.last_name}`"
        :disabled="deleting"
        @click="deleteUser"
    >
        <Trash2 class="size-4" />
    </button>
</template>
