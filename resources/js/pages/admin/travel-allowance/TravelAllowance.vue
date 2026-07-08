<script setup lang="ts">
import { Head, useForm  } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';


import AddTravelAllowance from '@/views/travel-allowance/AddTravelAllowance.vue';
import EditTravelAllowance from '@/views/travel-allowance/EditTravelAllowance.vue';
import TravelAllowanceListView from '@/views/travel-allowance/TravelAllowanceListView.vue';

type TravelAllowance = {
    id: number;
    date: string;
    name: string;
    client_id: number;
    company: string;
    description: string;
    rate: number;
    quantity: number;
    amount: number;
};

type Company = {
    id: number;
    company_name: string;
};

type WorkDetail = {
    client_id: number;
    travel_allowance?: string | number | null;
    travel_allowance_currency?: string | null;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Travel Allowance',
            },
        ],
    },
});

const props = withDefaults(
    defineProps<{
        travelAllowances?: TravelAllowance[];
        companies?: Company[];
        workDetails?: WorkDetail[];
    }>(),
    {
        travelAllowances: () => [],
        companies: () => [],
        workDetails: () => [],
    },
);

const addTravelAllowanceOpen = ref(false);
const editTravelAllowanceOpen = ref(false);
const selectedTravelAllowance = ref<TravelAllowance | null>(null);
const deleteTravelAllowanceOpen = ref(false);

const deleteForm = useForm({});

const openEditTravelAllowance = (allowance: TravelAllowance) => {
    selectedTravelAllowance.value = allowance;
    editTravelAllowanceOpen.value = true;
};

const openDeleteTravelAllowance = (allowance: TravelAllowance) => {
    selectedTravelAllowance.value = allowance;
    deleteTravelAllowanceOpen.value = true;
};

const deleteTravelAllowance = () => {
    if (!selectedTravelAllowance.value) {
        return;
    }

    deleteForm.delete(`/travel-allowance/${selectedTravelAllowance.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteTravelAllowanceOpen.value = false;
            selectedTravelAllowance.value = null;
        },
    });
};
</script>

<template>
    <Head title="Travel Allowance" />

    <div class="absolute inset-x-0 top-16 bottom-0 flex min-h-0 flex-col overflow-hidden p-4">
       <TravelAllowanceListView
            :travel-allowances="props.travelAllowances"
            @add="addTravelAllowanceOpen = true"
            @edit="openEditTravelAllowance"
            @delete="openDeleteTravelAllowance"
        />

        <AddTravelAllowance
            :open="addTravelAllowanceOpen"
            :companies="props.companies"
            :work-details="props.workDetails"
            @update:open="addTravelAllowanceOpen = $event"
        />
        <EditTravelAllowance
            :open="editTravelAllowanceOpen"
            :travel-allowance="selectedTravelAllowance"
            :companies="props.companies"
            :work-details="props.workDetails"
            @update:open="editTravelAllowanceOpen = $event"
        />
        <Dialog v-model:open="deleteTravelAllowanceOpen">
            <DialogContent class="w-full max-w-[calc(100%-2rem)] sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete travel allowance record?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. Are you sure you want to delete this record?
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="deleteForm.processing"
                        @click="deleteTravelAllowance"
                    >
                        Yes, delete
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        @click="deleteTravelAllowanceOpen = false"
                    >
                        Cancel
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
