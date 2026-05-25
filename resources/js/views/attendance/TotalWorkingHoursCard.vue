<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';

const summaryItems = [
    {
        label: 'Total Working Hours',
        value: '08h 36m',
        dotClass: 'bg-muted-foreground/40',
    },
    {
        label: 'Checked In',
        value: '07h 20m',
        dotClass: 'bg-emerald-500',
    },
    {
        label: 'Lunch Break',
        value: '01h 00m',
        dotClass: 'bg-amber-500',
    },
    {
        label: 'Checked Out',
        value: '00h 16m',
        dotClass: 'bg-red-500',
    },
    {
        label: 'Overtime',
        value: '00h 00m',
        dotClass: 'bg-blue-500',
    },
];

const timelineSegments = [
    {
        label: 'Checked in',
        width: '62%',
        class: 'bg-emerald-500 dark:bg-emerald-400',
    },
    {
        label: 'Lunch break',
        width: '15%',
        class: 'bg-amber-500 dark:bg-amber-400',
    },
    {
        label: 'Checked out',
        width: '18%',
        class: 'bg-red-500 dark:bg-red-400',
    },
    {
        label: 'Overtime',
        width: '5%',
        class: 'bg-blue-500 dark:bg-blue-400',
    },
];

const timeMarkers = [
    '12 AM',
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '10',
    '11',
    '12 PM',
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '10',
    '11',
    '12 AM',
];
</script>

<template>
    <Card class="overflow-hidden">
        <CardContent class="flex h-full flex-col gap-6 p-4 sm:p-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div
                    v-for="item in summaryItems"
                    :key="item.label"
                    class="min-w-0"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="size-2 shrink-0 rounded-full"
                            :class="item.dotClass"
                        />
                        <p
                            class="truncate text-sm font-medium text-muted-foreground"
                        >
                            {{ item.label }}
                        </p>
                    </div>

                    <p
                        class="mt-1 text-xl font-bold tracking-tight text-foreground tabular-nums"
                    >
                        {{ item.value }}
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto pb-1">
                <div class="min-w-[54rem] space-y-4">
                    <div class="relative px-1">
                        <div
                            class="absolute inset-x-1 top-1/2 grid -translate-y-1/2 grid-cols-[repeat(25,minmax(0,1fr))]"
                        >
                            <span
                                v-for="(_, index) in timeMarkers"
                                :key="`line-${index}`"
                                class="h-10 border-l border-border/70 last:border-r dark:border-border/50"
                            />
                        </div>

                        <div
                            class="relative flex h-3 overflow-hidden border-y border-border bg-muted/40 shadow-sm dark:bg-muted/20"
                        >
                            <div
                                v-for="segment in timelineSegments"
                                :key="segment.label"
                                class="h-full"
                                :class="segment.class"
                                :style="{ width: segment.width }"
                                :title="segment.label"
                            />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[repeat(25,minmax(0,1fr))] text-[0.6875rem] font-medium text-muted-foreground"
                    >
                        <span
                            v-for="(marker, index) in timeMarkers"
                            :key="`marker-${index}`"
                            class="text-center first:text-left last:text-right"
                        >
                            {{ marker }}
                        </span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
