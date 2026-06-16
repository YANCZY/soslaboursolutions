<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';

import ClientListView from '@/views/clients/ClientListView.vue';
import ClientsAddForm from '@/views/clients/ClientsAddForm.vue';

type Client = {
    id: number;
    company_name: string;
    phone: string | null;
    industry: string | null;
    website: string | null;
    company_address: string | null;
};

const props = defineProps<{
    clients: Client[];
    view: 'list' | 'add';
    saveRequestId: string | null;
}>();


defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clients',
            },
        ],
    },
});

const activeView = ref<'list' | 'add'>(props.view);

let pollTimer: number | undefined;

const checkSaveStatus = async () => {
    if (!props.saveRequestId) {
        return;
    }

    const response = await fetch(`/clients/save-requests/${props.saveRequestId}`);
    const data = await response.json();

    if (data.status === 'completed') {
        window.clearInterval(pollTimer);
        toast.success('Company record has been saved.');

        if (activeView.value === 'list') {
            router.reload({ only: ['clients'] });
        }
    }

    if (data.status === 'failed') {
        window.clearInterval(pollTimer);
        toast.error('Company record could not be saved.');
    }
};

onMounted(() => {
    if (props.saveRequestId) {
        pollTimer = window.setInterval(checkSaveStatus, 1000);
    }
});

onUnmounted(() => {
    window.clearInterval(pollTimer);
});
</script>

<template>
    <Head title="Clients" />
    <div class="absolute inset-x-0 top-16 bottom-0 flex min-h-0 flex-col overflow-hidden p-4">
        <ClientListView
            v-if="activeView === 'list'"
            :clients="props.clients"
            @add-client="activeView = 'add'"
        />
        <div
            v-else
            class="min-h-0 flex-1 overflow-hidden rounded-md border bg-background"
        >
            <ClientsAddForm @cancel="activeView = 'list'" />
        </div>
    </div>
</template>
