<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { index as usersIndex } from '@/routes/settings/users';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: '/settings',
            },
            {
                title: 'Users',
                href: usersIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Users" />

    <h1 class="sr-only">Users</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Users"
            description="Manage users who can access this workspace"
        />
    </div>
</template>
