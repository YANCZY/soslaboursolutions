<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { computed } from 'vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Attendance = {
    id: number;
    check_in_date: string;
    check_in_time: string | null;
    check_out_time: string | null;
};

const props = defineProps<{
    open: boolean;
    attendance: Attendance | null;
    checkOutTime: string;
    error?: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'update:checkOutTime', value: string): void;
    (e: 'save'): void;
}>();

const checkOutPeriod = computed({
    get: () => {
        if (!props.checkOutTime) {
            return 'AM';
        }

        const [hours] = props.checkOutTime.split(':');

        return Number(hours) >= 12 ? 'PM' : 'AM';
    },
    set: (period: string) => {
        if (!props.checkOutTime) {
            return;
        }

        const [hours, minutes = '00'] = props.checkOutTime.split(':');
        let hour = Number(hours);

        if (period === 'AM' && hour >= 12) {
            hour -= 12;
        }

        if (period === 'PM' && hour < 12) {
            hour += 12;
        }

        emit(
            'update:checkOutTime',
            `${String(hour).padStart(2, '0')}:${minutes}`,
        );
    },
});

const formatTimeToAmPm = (time: string | null) => {
    if (!time) {
        return '';
    }

    const [hours, minutes] = time.split(':');

    return new Date(2000, 0, 1, Number(hours), Number(minutes))
        .toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        });
};

</script>

<template>
    <Dialog :open="open">
        <DialogContent
            :show-close-button="false"
            class="w-full max-w-[calc(100%-2rem)] sm:max-w-md"
        >
            <div class="flex max-h-[85vh] min-h-0 flex-col overflow-hidden">
                <DialogHeader class="sticky top-0 z-10 border-b bg-background px-6 py-4">
                    <div class="flex items-start justify-between gap-3 pr-1">
                        <div class="space-y-1">
                            <DialogTitle>You Forgot to Logout</DialogTitle>
                            <DialogDescription>
                                Your shift exceeded 19 hours. Please confirm the correct check-out time.
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

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-4">
                    <div class="space-y-2">
                        <Label for="forgot_logout_date">Date</Label>
                        <Input
                            id="forgot_logout_date"
                            :model-value="attendance?.check_in_date ?? ''"
                            disabled
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="forgot_logout_check_in_time">Check In Time</Label>
                        <Input
                            id="forgot_logout_check_in_time"
                            :model-value="formatTimeToAmPm(attendance?.check_in_time ?? null)"
                            disabled
                        />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-[1fr_7rem]">
                        <div class="space-y-2">
                            <Label for="forgot_logout_check_out_time">Check Out Time</Label>
                            <Input
                                id="forgot_logout_check_out_time"
                                :model-value="checkOutTime"
                                type="time"
                                required
                                @update:model-value="emit('update:checkOutTime', String($event ?? ''))"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="forgot_logout_check_out_period">AM/PM</Label>
                            <Select
                                id="forgot_logout_check_out_period"
                                v-model="checkOutPeriod"
                                :disabled="!checkOutTime"
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="AM">AM</SelectItem>
                                    <SelectItem value="PM">PM</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <InputError class="sm:col-span-2" :message="error" />
                    </div>


                </div>

                <DialogFooter class="sticky bottom-0 z-10 gap-2 border-t bg-background px-6 py-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('update:open', false)"
                    >
                        Cancel
                    </Button>

                    <Button type="button" @click="emit('save')">
                        Save
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
