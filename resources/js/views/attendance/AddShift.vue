<script setup lang="ts">
import { Info } from 'lucide-vue-next';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';


type Company = {
    id: number;
    company_name: string;
};

type AddShiftForm = {
    check_in_date: string;
    check_in_time: string;
    check_out_time: string;
    client_id: string;
    covering_for: string;
};

defineProps<{
    open: boolean;
    form: AddShiftForm;
    companies: Company[];
    error?: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:form': [value: AddShiftForm];
    save: [];
}>();

const updateFormField = (form: AddShiftForm, field: keyof AddShiftForm, value: string | number) => {
    emit('update:form', {
        ...form,
        [field]: String(value ?? ''),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="w-full max-w-[calc(100%-2rem)] sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Add Shift</DialogTitle>
                <DialogDescription>
                    Manually record a completed shift.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-2 sm:grid-cols-2">
                <div class="space-y-2 sm:col-span-2">
                    <Label for="add_shift_date">
                        Date <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="add_shift_date"
                        :model-value="form.check_in_date"
                        type="date"
                        required
                        @update:model-value="updateFormField(form, 'check_in_date', $event)"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="add_shift_check_in">
                        Check In <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="add_shift_check_in"
                        :model-value="form.check_in_time"
                        type="time"
                        required
                        @update:model-value="updateFormField(form, 'check_in_time', $event)"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="add_shift_check_out">
                        Check Out <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="add_shift_check_out"
                        :model-value="form.check_out_time"
                        type="time"
                        required
                        @update:model-value="updateFormField(form, 'check_out_time', $event)"
                    />
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <Label for="add_shift_company">
                        Company <span class="text-destructive">*</span>
                    </Label>
                    <Select
                        :model-value="form.client_id"
                        @update:model-value="updateFormField(form, 'client_id', $event)"
                    >
                        <SelectTrigger id="add_shift_company">
                            <SelectValue placeholder="Select company" />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem
                                v-for="company in companies"
                                :key="company.id"
                                :value="String(company.id)"
                            >
                                {{ company.company_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <div class="flex items-center gap-2">
                        <Label for="add_shift_covering_for">
                            Covering For
                            <span class="text-xs text-muted-foreground">
                                (optional)
                            </span>
                        </Label>

                        <TooltipProvider :delay-duration="100">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <button
                                        type="button"
                                        class="inline-flex size-6 items-center justify-center rounded-sm text-muted-foreground hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        aria-label="About the Covering For field"
                                    >
                                        <Info class="size-4" />
                                    </button>
                                </TooltipTrigger>

                                <TooltipContent
                                    side="top"
                                    class="max-w-xs"
                                >
                                    Enter the employee’s name if you covered their shift.
                                    Leave blank if this was your own shift.
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>

                    <Input
                        id="add_shift_covering_for"
                        :model-value="form.covering_for"
                        placeholder="Enter employee name"
                        maxlength="255"
                        @update:model-value="updateFormField(form, 'covering_for', $event)"
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
