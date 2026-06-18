<script setup lang="ts">
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

type Attendance = {
    id: number;
    check_in_date: string;
    check_in_time: string | null;
    check_out_time: string | null;
    lunch_start_time: string | null;
    lunch_end_time: string | null;
};

const props = defineProps<{
    open: boolean;
    attendance: Attendance | null;
    form: {
        check_in_date: string;
        check_in_time: string;
        check_out_time: string;
        lunch_start_time: string;
        lunch_end_time: string;
    };
    error?: string;
}>();

type AttendanceForm = {
    check_in_date: string;
    check_in_time: string;
    check_out_time: string;
    lunch_start_time: string;
    lunch_end_time: string;
};

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:form': [value: AttendanceForm];
    save: [];
}>();

const updateFormField = (field: keyof AttendanceForm, value: string | number) => {
    emit('update:form', {
        ...props.form,
        [field]: String(value ?? ''),
    });
};

</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="w-full max-w-[calc(100%-2rem)] sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Edit attendance</DialogTitle>
                <DialogDescription>
                    Update the date, check-in, check-out, and lunch break times.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-2 sm:grid-cols-2">
                <div class="space-y-2 sm:col-span-2">
                    <Label for="attendance_date">Date</Label>
                    <Input
                        id="attendance_date"
                        :model-value="form.check_in_date"
                        type="date"
                        @update:model-value="updateFormField('check_in_date', $event)"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="check_in_time">Check In</Label>
                    <Input
                        id="check_in_time"
                        :model-value="form.check_in_time"
                        type="time"
                        @update:model-value="updateFormField('check_in_time', $event)"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="check_out_time">Check Out</Label>
                    <Input
                        id="check_out_time"
                        :model-value="form.check_out_time"
                        type="time"
                        @update:model-value="updateFormField('check_out_time', $event)"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="lunch_start_time">Lunch Break Start</Label>
                    <Input
                        id="lunch_start_time"
                        :model-value="form.lunch_start_time"
                        type="time"
                        @update:model-value="updateFormField('lunch_start_time', $event)"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="lunch_end_time">Lunch Break End</Label>
                    <Input
                        id="lunch_end_time"
                        :model-value="form.lunch_end_time"
                        type="time"
                        @update:model-value="updateFormField('lunch_end_time', $event)"
                    />
                </div>
            </div>

            <p v-if="error" class="text-sm text-destructive">
                {{ error }}
            </p>

            <DialogFooter>
                <Button type="button" @click="emit('save')">
                    Save
                </Button>

                <Button type="button" variant="outline" @click="emit('update:open', false)">
                    Cancel
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
