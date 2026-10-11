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
import type { Company } from '@/types/company';

defineProps<{
    open: boolean;
    company: Company | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const detailFields = [
    { key: 'company_type', label: 'Company type' },
    { key: 'trade', label: 'Trade' },
    { key: 'industry', label: 'Industry' },
    { key: 'phone', label: 'Phone' },
    { key: 'website', label: 'Website' },
    { key: 'company_address', label: 'Address line 1' },
    { key: 'company_address_2', label: 'Address line 2' },
    { key: 'company_address_city', label: 'City' },
    { key: 'company_address_state', label: 'State' },
    { key: 'company_address_country', label: 'Country' },
    {
        key: 'industry_description',
        label: 'Industry description',
    },
] as const;

const displayValue = (value: string | null): string => {
    return value?.trim() || '—';
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Company details</DialogTitle>
                <DialogDescription>
                    View company information and contact details.
                </DialogDescription>
            </DialogHeader>

            <div v-if="company" class="space-y-5">
                <div class="rounded-lg border bg-muted/30 p-4">
                    <p class="text-sm text-muted-foreground">
                        Company name
                    </p>

                    <h2 class="mt-1 break-words text-xl font-semibold">
                        {{ company.company_name }}
                    </h2>

                    <span
                        class="mt-3 inline-flex rounded-full border px-2.5 py-1 text-xs font-medium"
                        :class="
                            company.is_active
                                ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-300'
                                : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300'
                        "
                    >
                        {{ company.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="field in detailFields"
                        :key="field.key"
                        class="min-w-0 rounded-lg border p-4"
                        :class="{
                            'sm:col-span-2':
                                field.key === 'industry_description',
                        }"
                    >
                        <dt class="text-sm text-muted-foreground">
                            {{ field.label }}
                        </dt>

                        <dd
                            class="mt-1 whitespace-pre-wrap break-words text-sm font-medium"
                        >
                            {{ displayValue(company[field.key]) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                >
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
