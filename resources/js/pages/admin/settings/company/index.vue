<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings/Layout.vue';


type Company = {
    id: number;
    company_name: string;
    trade: string | null;
    industry: string | null;
    website: string | null;
    company_address_state: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedCompanies = {
    data: Company[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    companies: PaginatedCompanies;
    filters: {
        search: string;
    };
}>();

const search = ref(props.filters.search ?? '');

watch(search, (value, _oldValue, onCleanup) => {
    const searchDelay = window.setTimeout(() => {
        router.get(
            '/settings/company',
            {
                search: value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 500);

    onCleanup(() => window.clearTimeout(searchDelay));
});

const paginationLabel = (label: string) => {
    return label
        .replace('&laquo; Previous', 'Previous')
        .replace('Next &raquo;', 'Next');
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: '/settings',
            },
            {
                title: 'Company',
                href: '/settings/company',
            },
        ],
    },
});
</script>

<template>
    <Head title="Company" />

    <SettingsLayout>
    <div class="space-y-4">
        <Heading
            variant="small"
            title="Company"
            description="Manage company settings for this workspace"
        />

        <div class="flex items-center">
            <div
                class="relative w-40 transition-[width] duration-200 ease-in-out hover:w-64 focus-within:w-64"
            >
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                />

                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search companies..."
                    class="h-8 pl-8 text-sm"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-md border">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] table-fixed text-sm">
                    <thead class="bg-muted text-left">
                        <tr>
                            <th class="w-[26%] px-4 py-3 font-medium">Company Name</th>
                            <th class="w-[18%] px-4 py-3 font-medium">Trade</th>
                            <th class="w-[21%] px-4 py-3 font-medium">Industry</th>
                            <th class="w-[20%] px-4 py-3 font-medium">Website</th>
                            <th class="w-[15%] px-4 py-3 font-medium">State</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="company in props.companies.data"
                            :key="company.id"
                            class="border-t"
                        >
                            <td class="truncate px-4 py-3">
                                {{ company.company_name }}
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ company.trade ?? '-' }}
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ company.industry ?? '-' }}
                            </td>
                            <td class="truncate px-4 py-3">
                                <a
                                    v-if="company.website"
                                    :href="company.website"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary underline-offset-4 hover:underline"
                                >
                                    {{ company.website }}
                                </a>
                                <span v-else>-</span>
                            </td>
                            <td class="truncate px-4 py-3">
                                {{ company.company_address_state ?? '-' }}
                            </td>
                        </tr>

                        <tr v-if="props.companies.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-6 text-center text-muted-foreground"
                            >
                                No companies found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ props.companies.from }} to {{ props.companies.to }} of
                        {{ props.companies.total }} companies
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            v-for="link in props.companies.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            preserve-scroll
                            class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm"
                            :class="{
                                'bg-primary text-primary-foreground': link.active,
                                'pointer-events-none opacity-50': !link.url,
                            }"
                        >
                            {{ paginationLabel(link.label) }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</SettingsLayout>
</template>
