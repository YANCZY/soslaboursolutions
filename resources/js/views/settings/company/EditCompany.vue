<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';
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
import type { Company } from '@/types/company';

const props = defineProps<{
    open: boolean;
    company: Company | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const form = useForm({
    company_name: '',
    company_type: '',
    trade: '',
    industry: '',
    industry_description: '',
    phone: '',
    website: '',
    company_address: '',
    company_address_2: '',
    company_address_city: '',
    company_address_state: '',
    company_address_zip: '',
    company_address_country: '',
});

const fields = [
    { key: 'company_name', label: 'Company name', maxLength: 255 },
    { key: 'company_type', label: 'Company type', maxLength: 100 },
    { key: 'trade', label: 'Trade', maxLength: 100 },
    { key: 'industry', label: 'Industry', maxLength: 100 },
    { key: 'phone', label: 'Phone', maxLength: 20 },
    { key: 'website', label: 'Website', maxLength: 255 },
    { key: 'company_address', label: 'Address line 1', maxLength: 255 },
    { key: 'company_address_2', label: 'Address line 2', maxLength: 255 },
    { key: 'company_address_city', label: 'Suburb/City', maxLength: 255 },
    { key: 'company_address_state', label: 'State', maxLength: 255 },
    { key: 'company_address_zip', label: 'Postcode', maxLength: 20 },
    { key: 'company_address_country', label: 'Country', maxLength: 255 },
    {
        key: 'industry_description',
        label: 'Industry description',
        maxLength: 10000,
    },
] as const;

watch(
    [() => props.open, () => props.company?.id],
    ([open]) => {
        const company = props.company;

        if (!open || !company) {
            return;
        }

        form.clearErrors();

        for (const field of fields) {
            form[field.key] = company[field.key] ?? '';
        }
    },
    { immediate: true },
);

const updateOpen = (open: boolean) => {
    if (form.processing) {
        return;
    }

    emit('update:open', open);
};

const save = () => {
    if (!props.company || form.processing) {
        return;
    }

    form.patch(`/settings/company/${props.company.id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit('update:open', false);
            toast.success('Company updated.');
        },
        onError: () => {
            toast.error(
                'Unable to save the company. Check the highlighted fields.',
            );
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="updateOpen">
        <DialogContent
            class="max-h-[85vh] overflow-y-auto sm:max-w-2xl"
            :show-close-button="!form.processing"
            @escape-key-down="(event) => {
                if (form.processing) event.preventDefault();
            }"
            @interact-outside="(event) => {
                if (form.processing) event.preventDefault();
            }"
        >
            <DialogHeader>
                <DialogTitle>Edit company</DialogTitle>
                <DialogDescription>
                    Update company information, contact details, and address.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="save">
                <fieldset
                    :disabled="form.processing"
                    class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2"
                >
                    <div
                        v-for="field in fields"
                        :key="field.key"
                        class="min-w-0 space-y-2"
                        :class="
                            field.key === 'industry_description'
                                ? 'sm:col-span-2'
                                : ''
                        "
                    >
                        <Label :for="`edit-company-${field.key}`">
                            {{ field.label }}
                            {{ field.key === 'company_name' ? '*' : '' }}
                        </Label>

                        <textarea
                            v-if="field.key === 'industry_description'"
                            :id="`edit-company-${field.key}`"
                            v-model="form[field.key]"
                            rows="4"
                            :maxlength="field.maxLength"
                            :aria-invalid="Boolean(form.errors[field.key])"
                            :aria-describedby="
                                form.errors[field.key]
                                    ? `edit-company-${field.key}-error`
                                    : undefined
                            "
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm disabled:opacity-50"
                        />

                        <Input
                            v-else
                            :id="`edit-company-${field.key}`"
                            v-model="form[field.key]"
                            :type="
                                field.key === 'website'
                                    ? 'url'
                                    : field.key === 'phone'
                                      ? 'tel'
                                      : 'text'
                            "
                            :maxlength="field.maxLength"
                            :required="field.key === 'company_name'"
                            :aria-invalid="Boolean(form.errors[field.key])"
                            :aria-describedby="
                                form.errors[field.key]
                                    ? `edit-company-${field.key}-error`
                                    : undefined
                            "
                        />

                        <p
                            v-if="form.errors[field.key]"
                            :id="`edit-company-${field.key}-error`"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors[field.key] }}
                        </p>
                    </div>
                </fieldset>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="updateOpen(false)"
                    >
                        Cancel
                    </Button>

                    <Button
                        type="submit"
                        :disabled="form.processing || !company"
                    >
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
