<script setup lang="ts">

const lastUpdated = 'September 20, 2026';

const tabs = [
    { key: 'privacy', label: 'Privacy Policy' },
    { key: 'terms', label: 'Terms of Use' },
    { key: 'security', label: 'Security' },
] as const;

type PolicyTab = (typeof tabs)[number]['key'];

const activeTab = defineModel<PolicyTab>('activeTab', {
    default: 'privacy',
});

</script>

<template>
    <div class="flex min-h-0 flex-col gap-4">
        <div
            class="grid grid-cols-1 gap-2 border-b pb-4 sm:grid-cols-3"
            role="tablist"
            aria-label="Policy sections"
        >
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.key"
                class="rounded-md border px-3 py-2 text-sm font-medium transition-colors"
                :class="
                    activeTab === tab.key
                        ? 'border-primary bg-primary/10 text-primary'
                        : 'border-border text-muted-foreground hover:bg-muted hover:text-foreground'
                "
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <div class="min-h-0 overflow-y-auto pr-1">
            <article class="space-y-6 text-sm leading-6 text-muted-foreground">
                <section v-if="activeTab === 'privacy'" class="space-y-5">
                    <h3 class="text-lg font-semibold text-foreground">Privacy Policy</h3>
                    <!-- Privacy content here -->
                </section>

                <section v-else-if="activeTab === 'terms'" class="space-y-5">
                    <h3 class="text-lg font-semibold text-foreground">Terms of Use</h3>
                    <!-- Terms content here -->
                </section>

                <section v-else class="space-y-5">
                    <h3 class="text-lg font-semibold text-foreground">Security</h3>
                    <!-- Security content here -->
                </section>

                <div class="border-t pt-4 text-xs text-muted-foreground">
                    <p>Last updated: {{ lastUpdated }}</p>
                    <p>
                        These policies are provided for the operation of the SOS Labour Solutions
                        portal and may be updated from time to time.
                    </p>
                </div>
            </article>
        </div>
    </div>
</template>
