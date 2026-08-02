<script setup lang="ts">
import { ref, watch } from 'vue';
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

const props = withDefaults(defineProps<{
    open: boolean;
    title?: string;
    description?: string;
}>(), {
    title: 'Export PDF',
    description: 'Select the date range you want to include in the report.',
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    export: [range: { startDate: string; endDate: string }];
}>();

const startDate = ref('');
const endDate = ref('');
const error = ref('');

watch(
    () => props.open,
    (open) => {
        if (open) {
            error.value = '';
        }
    },
);

const closeDialog = () => {
    emit('update:open', false);
};

const submitExport = () => {
    error.value = '';

    if (!startDate.value || !endDate.value) {
        error.value = 'Please select a start date and end date.';

        return;
    }

    if (startDate.value > endDate.value) {
        error.value = 'Start date cannot be after end date.';

        return;
    }

    emit('export', {
        startDate: startDate.value,
        endDate: endDate.value,
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="w-full max-w-[calc(100%-2rem)] sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-2">
                <div class="space-y-2">
                    <Label for="export_pdf_start_date">Start Date</Label>
                    <Input
                        id="export_pdf_start_date"
                        v-model="startDate"
                        type="date"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="export_pdf_end_date">End Date</Label>
                    <Input
                        id="export_pdf_end_date"
                        v-model="endDate"
                        type="date"
                    />
                </div>

                <p v-if="error" class="text-sm text-destructive">
                    {{ error }}
                </p>
            </div>

            <DialogFooter>
                <Button type="button" @click="submitExport">
                    Export
                </Button>

                <Button type="button" variant="outline" @click="closeDialog">
                    Cancel
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
