<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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

type Company = {
    id: number;
    company_name: string;
};

type WorkDetail = {
    client_id: number;
    travel_allowance?: string | number | null;
    travel_allowance_currency?: string | null;
};

const props = defineProps<{
    open: boolean;
    companies: Company[];
    workDetails: WorkDetail[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const page = usePage();

const authUser = computed(() => page.props.auth.user);

const today = computed(() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
});

const fullName = computed(() =>
    [authUser.value.first_name, authUser.value.last_name]
        .filter(Boolean)
        .join(' '),
);

const form = useForm({
    date: today.value,
    name: fullName.value,
    client_id: null as number | null,
    company_search: '',
    description: '',
    rate: '',
    quantity: '1',
    amount: '0.00',
});

const companyLookupOpen = ref(false);
const companyError = ref('');

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            companyLookupOpen.value = false;
            companyError.value = '';
            form.defaults({
                date: today.value,
                name: fullName.value,
                client_id: null,
                company_search: '',
                description: '',
                rate: '',
                quantity: '1',
                amount: '0.00',
            });
            form.reset();
            form.clearErrors();
        }
    },
);

const filteredCompanies = computed(() => {
    const value = form.company_search.trim().toLowerCase();

    return props.companies.filter((company) =>
        !value || company.company_name.toLowerCase().includes(value),
    );
});

const selectedWorkDetail = computed(() =>
    props.workDetails.find((detail) => detail.client_id === form.client_id) ?? null,
);

watch(
    () => form.client_id,
    () => {
        form.rate =
            selectedWorkDetail.value?.travel_allowance !== null &&
            selectedWorkDetail.value?.travel_allowance !== undefined
                ? String(selectedWorkDetail.value.travel_allowance)
                : '';
    },
);

const selectCompany = (company: Company) => {
    form.client_id = company.id;
    form.company_search = company.company_name;
    companyLookupOpen.value = false;
    companyError.value = '';
};

const closeCompanyLookup = () => {
    window.setTimeout(() => {
        companyLookupOpen.value = false;
    }, 100);
};

const amount = computed(() => {
    const rate = Number(form.rate || 0);
    const quantity = Number(form.quantity || 0);

    return (rate * quantity).toFixed(2);
});

watch(amount, (value) => {
    form.amount = value;
}, { immediate: true });

const submit = () => {
    companyError.value = '';

    if (!form.client_id) {
        companyError.value = 'Company is required.';

        return;
    }

    form.name = fullName.value;
    form.amount = amount.value;

    form.post('/travel-allowance', {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            :show-close-button="false"
            class="w-full max-w-[calc(100%-2rem)] sm:max-w-3xl"
        >
            <div class="flex max-h-[85vh] min-h-0 flex-col overflow-hidden">
                <DialogHeader class="border-b bg-background px-6 py-4">
                    <div class="flex items-start justify-between gap-3 pr-1">
                        <div class="space-y-1">
                            <DialogTitle>Add Travel Allowance</DialogTitle>
                            <DialogDescription>
                                Create a travel allowance request.
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
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="travel_allowance_date">
                                Date <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="travel_allowance_date"
                                v-model="form.date"
                                type="date"
                            />
                            <InputError :message="form.errors.date" />
                        </div>

                        <div class="space-y-2">
                            <Label for="travel_allowance_company">
                                Company <span class="text-destructive">*</span>
                            </Label>

                            <div class="relative">
                                <Input
                                    id="travel_allowance_company"
                                    v-model="form.company_search"
                                    type="search"
                                    placeholder="Select company..."
                                    autocomplete="off"
                                    @focus="companyLookupOpen = true"
                                    @input="companyLookupOpen = true"
                                    @blur="closeCompanyLookup"
                                    @keydown.escape="companyLookupOpen = false"
                                />

                                <InputError :message="companyError" />

                                <div
                                    v-if="companyLookupOpen && filteredCompanies.length > 0"
                                    class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                                >
                                    <button
                                        v-for="company in filteredCompanies"
                                        :key="company.id"
                                        type="button"
                                        class="flex w-full items-center rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                        @mousedown.prevent="selectCompany(company)"
                                    >
                                        {{ company.company_name }}
                                    </button>
                                </div>
                                <InputError :message="companyError || form.errors.client_id" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="travel_allowance_name">Name</Label>
                            <Input
                                id="travel_allowance_name"
                                v-model="form.name"
                                disabled
                            />
                        </div>
                         <div class="space-y-2">
                            <Label for="travel_allowance_rate">
                                Rate <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="travel_allowance_rate"
                                v-model="form.rate"
                                type="number"
                                step="0.01"
                                min="0"
                            />
                            <InputError :message="form.errors.rate" />
                        </div>
                        <div class="space-y-2">
                            <Label for="travel_allowance_description">Description</Label>
                            <textarea
                                id="travel_allowance_description"
                                v-model="form.description"
                                rows="4"
                                placeholder="Enter travel allowance details"
                                class="w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                        <div class="space-y-2">
                            <Label for="travel_allowance_quantity">
                                Quantity <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="travel_allowance_quantity"
                                v-model="form.quantity"
                                type="number"
                                step="1"
                                min="1"
                            />
                            <InputError :message="form.errors.quantity" />
                        </div>

                        <div class="space-y-2">
                            <Label for="travel_allowance_amount">Amount</Label>
                            <Input
                                id="travel_allowance_amount"
                                :model-value="amount"
                                disabled
                            />
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2 border-t bg-background px-6 py-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </Button>

                    <Button type="button" :disabled="form.processing" @click="submit">
                        Save
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
