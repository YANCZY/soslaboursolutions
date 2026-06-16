<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const emit = defineEmits<{
    cancel: [];
}>();

const form = useForm({
    company_name: '',
    company_address: '',
    company_address_2: '',
    company_address_city: '',
    company_address_state: '',
    company_address_country: 'Australia',
    industry: '',
    industry_description: '',
    phone: '',
    trade: '',
    company_type: '',
    website: '',
});

const saveClient = (nextAction: 'list' | 'add') => {
    form.transform((data) => ({
        ...data,
        next_action: nextAction,
    })).post('/clients');
};


</script>

<template>
    <form
    class="flex h-full min-h-0 flex-col"
    @submit.prevent
>
    <div class="min-h-0 flex-1 space-y-8 overflow-y-auto p-6">
        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-semibold">Company details</h2>
                <p class="text-sm text-muted-foreground">
                    Enter the client's general company information.
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
    <!-- Left column -->
    <div class="space-y-4">
        <div class="w-full space-y-2 md:w-5/5">
            <Label for="company_name">
                Company Name
                <span class="text-destructive">*</span>
            </Label>

            <Input
                id="company_name"
                v-model="form.company_name"
                placeholder="Enter company name"
                autocomplete="organization"
                required
            />
        </div>

        <div class="w-full space-y-2 md:w-5/5">
            <Label for="company_type">Company Type</Label>

            <Select v-model="form.company_type">
                <SelectTrigger id="company_type" class="w-full">
                    <SelectValue placeholder="Select company type" />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value="potential-customer">
                        Potential Customer
                    </SelectItem>
                    <SelectItem value="customer">Customer</SelectItem>
                    <SelectItem value="competitor">Competitor</SelectItem>
                    <SelectItem value="out-of-sector">
                        Out of Sector
                    </SelectItem>
                    <SelectItem value="low-compliance">
                        Low-Compliance/Informal Operations
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="w-full space-y-2 md:w-5/5">
            <Label for="trades">Trade</Label>

            <Select v-model="form.trade">
                <SelectTrigger id="trades" class="w-full">
                    <SelectValue placeholder="Select trade" />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value="blaster">Blaster</SelectItem>
                    <SelectItem value="machine-operator">
                        Machine Operator
                    </SelectItem>
                    <SelectItem value="welder-fabricator">
                        Welder Fabricator
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>

    <!-- Right column -->
    <div class="space-y-4">
        <div class="w-full space-y-2 md:w-5/5">
            <Label for="industry">Industry</Label>

            <Select v-model="form.industry">
                <SelectTrigger id="industry" class="w-full">
                    <SelectValue placeholder="Select industry" />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value="alternative-fuel">
                        Alternative Fuel
                    </SelectItem>
                    <SelectItem value="contractor">
                        Contractor
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="w-full space-y-2 md:w-5/5">
            <Label for="industry_description">
                Industry Description
            </Label>

            <textarea
                id="industry_description"
                v-model="form.industry_description"
                rows="7"
                placeholder="Describe the company's industry and operations"
                class="w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
            />
        </div>
    </div>
</div>
        </section>
                <section class="space-y-4 border-t pt-6">
            <div>
                <h2 class="text-lg font-semibold">Company address</h2>
                <p class="text-sm text-muted-foreground">
                    Enter the company's Australian business address.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="w-full space-y-2 md:w-5/5">
                    <Label for="company_address">Address</Label>
                    <Input
                        id="company_address"
                        v-model="form.company_address"
                        placeholder="Street address"
                        autocomplete="address-line1"
                    />
                </div>

                <div class="w-full space-y-2 md:w-5/5">
                    <Label for="company_address_2">Address 2</Label>
                    <Input
                        id="company_address_2"
                        v-model="form.company_address_2"
                        placeholder="Suite, unit, building, or floor"
                        autocomplete="address-line2"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="company_address_city">City</Label>
                    <Input
                        id="company_address_city"
                        v-model="form.company_address_city"
                        placeholder="Enter city"
                        autocomplete="address-level2"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="company_address_state">State</Label>
                    <Select v-model="form.company_address_state">
                        <SelectTrigger id="company_address_state" class="w-full">
                            <SelectValue placeholder="Select state" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ACT">Australian Capital Territory</SelectItem>
                            <SelectItem value="NSW">New South Wales</SelectItem>
                            <SelectItem value="NT">Northern Territory</SelectItem>
                            <SelectItem value="QLD">Queensland</SelectItem>
                            <SelectItem value="SA">South Australia</SelectItem>
                            <SelectItem value="TAS">Tasmania</SelectItem>
                            <SelectItem value="VIC">Victoria</SelectItem>
                            <SelectItem value="WA">Western Australia</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="w-full space-y-2 md:w-[85%]">
                    <Label for="company_address_country">Country</Label>
                    <Input
                        id="company_address_country"
                        v-model="form.company_address_country"
                        autocomplete="country-name"
                    />
                </div>
            </div>
        </section>
                <section class="space-y-4 border-t pt-6">
            <h2 class="text-lg font-semibold">Contact information</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="phone">Phone</Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        placeholder="e.g. 0412 345 678"
                        autocomplete="tel"
                        pattern="^(?:\+61|0)[2-478](?:[ -]?\d){8}$"
                        title="Enter an Australian phone number, such as 0412 345 678 or +61 412 345 678"
                    />
                    <p class="text-xs text-muted-foreground">
                        Use an Australian number such as 0412 345 678.
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="website">Website</Label>
                    <Input
                        id="website"
                        v-model="form.website"
                        type="url"
                        placeholder="https://example.com.au"
                        autocomplete="url"
                    />
                </div>
            </div>
        </section>


        <div class="flex justify-end gap-3 border-t pt-6">

            <Button
                type="button"
                :disabled="form.processing"
                @click="saveClient('list')"
            >
                Save Client
            </Button>
            <Button
                type="button"
                :disabled="form.processing"
                @click="saveClient('add')"
            >
                Save and Add New
            </Button>
            <Button type="button" variant="outline"  @click="emit('cancel')">Cancel</Button>
        </div>
    </div>
    </form>
</template>
