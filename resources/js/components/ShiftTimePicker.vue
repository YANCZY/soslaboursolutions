<script setup lang="ts">
import { Clock, ChevronUp, ChevronDown } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

const props = withDefaults(defineProps<{
    id?: string;
    name?: string;
    modelValue?: string | null;
    placeholder?: string;
}>(), {
    modelValue: '',
    placeholder: 'Select time',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const open = ref(false);

const triggerRef = ref<HTMLInputElement | null>(null);
const panelRef = ref<HTMLDivElement | null>(null);
const placement = ref<'top' | 'bottom'>('bottom');

const updatePlacement = async () => {
    await nextTick();

    if (!triggerRef.value || !panelRef.value) {
        return;
    }

    const triggerRect = triggerRef.value.getBoundingClientRect();
    const panelHeight = panelRef.value.offsetHeight;
    const spaceBelow = window.innerHeight - triggerRect.bottom;
    const spaceAbove = triggerRect.top;

    placement.value =
        spaceBelow < panelHeight + 12 && spaceAbove > spaceBelow
            ? 'top'
            : 'bottom';
};

const toggleOpen = async () => {
    open.value = !open.value;

    if (open.value) {
        await updatePlacement();
    }
};

const parsedTime = computed(() => {
    if (!props.modelValue) {
        return {
            hour: 9,
            minute: 0,
            period: 'AM' as 'AM' | 'PM',
        };
    }

    const [rawHour, rawMinute] = props.modelValue.split(':').map(Number);

    return {
        hour: rawHour % 12 || 12,
        minute: rawMinute,
        period: rawHour >= 12 ? 'PM' as const : 'AM' as const,
    };
});

const displayValue = computed(() => {
    if (!props.modelValue) {
        return '';
    }

    return `${String(parsedTime.value.hour).padStart(2, '0')}:${String(parsedTime.value.minute).padStart(2, '0')} ${parsedTime.value.period}`;
});

const manualValue = ref(displayValue.value);
const manualError = ref('');

watch(displayValue, (value) => {
    manualValue.value = value;
    manualError.value = '';
});

const emitTime = (hour: number, minute: number, period: 'AM' | 'PM') => {
    let formattedHour = hour;

    if (period === 'PM' && hour !== 12) {
        formattedHour += 12;
    }

    if (period === 'AM' && hour === 12) {
        formattedHour = 0;
    }

    emit(
        'update:modelValue',
        `${String(formattedHour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`,
    );
};

const commitManualValue = () => {
    const value = manualValue.value.trim().toUpperCase();

    if (!value) {
        manualError.value = '';
        emit('update:modelValue', '');

        return;
    }

    const match = value.match(/^(0[1-9]|1[0-2]):([0-5][0-9])\s(AM|PM)$/);

    if (!match) {
        manualError.value = 'Use format HH:MM AM/PM.';

        return;
    }

    const hour = Number(match[1]);
    const minute = Number(match[2]);
    const period = match[3] as 'AM' | 'PM';

    manualError.value = '';
    emitTime(hour, minute, period);
};

const updateHour = (direction: 1 | -1) => {
    let hour = parsedTime.value.hour + direction;

    if (hour > 12) {
        hour = 1;
    }

    if (hour < 1) {
        hour = 12;
    }

    emitTime(hour, parsedTime.value.minute, parsedTime.value.period);
};

const updateMinute = (direction: 1 | -1) => {
    let minute = parsedTime.value.minute + direction * 5;

    if (minute >= 60) {
        minute = 0;
    }

    if (minute < 0) {
        minute = 55;
    }

    emitTime(parsedTime.value.hour, minute, parsedTime.value.period);
};

const updatePeriod = (period: 'AM' | 'PM') => {
    emitTime(parsedTime.value.hour, parsedTime.value.minute, period);
};
</script>

<template>
    <div class="relative">
        <div class="relative">
        <input
            :id="id"
            ref="triggerRef"
            v-model="manualValue"
            type="text"
            :placeholder="placeholder"
            class="h-10 w-full rounded-md border bg-background px-3 pr-10 text-sm shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground focus-visible:ring-[3px]"
:class="manualError ? 'border-destructive focus-visible:ring-destructive/20' : 'border-input focus-visible:border-ring focus-visible:ring-ring/50'"
            @blur="commitManualValue"
            @keydown.enter.prevent="commitManualValue"
        >

        <button
            type="button"
            class="absolute top-0 right-0 flex h-10 w-10 items-center justify-center text-muted-foreground hover:text-foreground"
            @mousedown.prevent
            @click="toggleOpen"
        >
            <Clock class="size-4" />
        </button>
    </div>

    <p v-if="manualError" class="mt-1 text-xs text-destructive">
            {{ manualError }}
    </p>

        <input
            v-if="name"
            type="hidden"
            :name="name"
            :value="modelValue ?? ''"
        />

        <div
            v-if="open"
            ref="panelRef"
            class="absolute z-50 w-full rounded-md border bg-popover p-4 text-popover-foreground shadow-md"
            :class="placement === 'top' ? 'bottom-full mb-2' : 'top-full mt-2'"
        >
            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                <div class="grid justify-items-center gap-3">
                    <button type="button" class="text-muted-foreground hover:text-foreground" @click="updateHour(1)">
                        <ChevronUp class="size-5" />
                    </button>

                    <span class="text-xl font-semibold tabular-nums">
                        {{ String(parsedTime.hour).padStart(2, '0') }}
                    </span>

                    <button type="button" class="text-muted-foreground hover:text-foreground" @click="updateHour(-1)">
                        <ChevronDown class="size-5" />
                    </button>
                </div>

                <span class="text-xl font-semibold">:</span>

                <div class="grid justify-items-center gap-3">
                    <button type="button" class="text-muted-foreground hover:text-foreground" @click="updateMinute(1)">
                        <ChevronUp class="size-5" />
                    </button>

                    <span class="text-xl font-semibold tabular-nums">
                        {{ String(parsedTime.minute).padStart(2, '0') }}
                    </span>

                    <button type="button" class="text-muted-foreground hover:text-foreground" @click="updateMinute(-1)">
                        <ChevronDown class="size-5" />
                    </button>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 border-t pt-4">
                <button
                    type="button"
                    class="border px-3 py-2 text-sm font-medium"
                    :class="parsedTime.period === 'AM' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
                    @click="updatePeriod('AM')"
                >
                    AM
                </button>

                <button
                    type="button"
                    class="border border-l-0 px-3 py-2 text-sm font-medium"
                    :class="parsedTime.period === 'PM' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
                    @click="updatePeriod('PM')"
                >
                    PM
                </button>
            </div>
        </div>
    </div>
</template>
