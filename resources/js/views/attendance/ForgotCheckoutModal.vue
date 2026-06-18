<script setup lang="ts">
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

type Attendance = {
    id: number;
    check_in_date: string;
    check_in_time: string | null;
    check_out_time: string | null;
};

defineProps<{
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

                    <div class="space-y-2">
                        <Label for="forgot_logout_check_out_time">Check Out Time</Label>
                        <Input
                            id="forgot_logout_check_out_time"
                            :model-value="checkOutTime"
                            type="time"
                            required
                            @update:model-value="emit('update:checkOutTime', String($event ?? ''))"
                        />
                        <InputError :message="error" />
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
