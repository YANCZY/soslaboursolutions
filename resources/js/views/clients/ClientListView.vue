<script setup lang="ts">
import { Filter, Plus, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';


import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const emit = defineEmits<{
    addClient: [];
}>();

type Client = {
    id: number;
    company_name: string;
    phone: string;
    industry: string;
    website: string;
    company_address: string;
};

type PaginationItem = {
    key: string;
    label: string;
    page: number;
    active: boolean;
    disabled: boolean;
};

const props = defineProps<{
    clients: Client[];
}>();

const search = ref('');
const rowsPerPage = ref('10');
const currentPage = ref(1);

const filteredClients = computed(() => {
    const value = search.value.trim().toLowerCase();

    if (!value) {
        return props.clients;
    }

    return props.clients.filter((client) =>
        Object.values(client).some((field) =>
            String(field).toLowerCase().includes(value),
        ),
    );
});

const totalPages = computed(() =>
    Math.max(
        1,
        Math.ceil(
            filteredClients.value.length / Number(rowsPerPage.value),
        ),
    ),
);

const paginatedClients = computed(() => {
    const start = (currentPage.value - 1) * Number(rowsPerPage.value);

    return filteredClients.value.slice(
        start,
        start + Number(rowsPerPage.value),
    );
});

const firstRow = computed(() => {
    if (filteredClients.value.length === 0) {
        return 0;
    }

    return (currentPage.value - 1) * Number(rowsPerPage.value) + 1;
});

const lastRow = computed(() =>
    Math.min(
        currentPage.value * Number(rowsPerPage.value),
        filteredClients.value.length,
    ),
);

const paginationItems = computed<PaginationItem[]>(() => {
    const pages: PaginationItem[] = [
        {
            key: 'previous',
            label: 'Previous',
            page: currentPage.value - 1,
            active: false,
            disabled: currentPage.value === 1,
        },
    ];

    for (let page = 1; page <= totalPages.value; page++) {
        pages.push({
            key: `page-${page}`,
            label: String(page),
            page,
            active: page === currentPage.value,
            disabled: false,
        });
    }

    pages.push({
        key: 'next',
        label: 'Next',
        page: currentPage.value + 1,
        active: false,
        disabled: currentPage.value === totalPages.value,
    });

    return pages;
});

const goToPage = (item: PaginationItem) => {
    if (!item.disabled) {
        currentPage.value = item.page;
    }
};

watch([search, rowsPerPage], () => {
    currentPage.value = 1;
});
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col gap-4">
        <div
            class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-2">
                <div class="relative w-full sm:w-64">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />

                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search clients..."
                        class="pl-9"
                    />
                </div>

                <Button
                    variant="outline"
                    size="icon"
                    title="Filter clients"
                >
                    <Filter class="size-4" />
                </Button>
            </div>

            <Button class="gap-2" @click="emit('addClient')">
                <Plus class="size-4" />
                Add Client
            </Button>
        </div>
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-md border">
            <div class="min-h-0 flex-1 overflow-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="sticky top-0 z-10 bg-muted text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                Company Name
                            </th>
                            <th class="px-4 py-3 font-medium">Phone</th>
                            <th class="px-4 py-3 font-medium">Industry</th>
                            <th class="px-4 py-3 font-medium">Website</th>
                            <th class="px-4 py-3 font-medium">
                                Company Address
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="client in paginatedClients"
                            :key="client.id"
                            class="border-t"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ client.company_name }}
                            </td>
                            <td class="px-4 py-3">
                                {{ client.phone }}
                            </td>
                            <td class="px-4 py-3">
                                {{ client.industry }}
                            </td>
                            <td class="px-4 py-3 text-primary">
                                {{ client.website }}
                            </td>
                            <td class="px-4 py-3">
                                {{ client.company_address }}
                            </td>
                        </tr>

                        <tr v-if="paginatedClients.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No clients found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
                        <div class="shrink-0 border-t bg-background px-4 py-3">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ firstRow }} to {{ lastRow }} of
                            {{ filteredClients.length }} clients
                        </p>

                        <Select v-model="rowsPerPage">
                            <SelectTrigger class="h-9 w-24">
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem value="10">10</SelectItem>
                                <SelectItem value="20">20</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="item in paginationItems"
                            :key="item.key"
                            type="button"
                            class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm"
                            :class="{
                                'bg-primary text-primary-foreground':
                                    item.active,
                                'pointer-events-none opacity-50':
                                    item.disabled,
                            }"
                            :disabled="item.disabled"
                            @click="goToPage(item)"
                        >
                            {{ item.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
